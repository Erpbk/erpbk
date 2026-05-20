<?php

namespace App\Http\Controllers;

use App\Helpers\IConstants;
use App\Helpers\Common;
use App\Models\AdminCompany;
use App\Models\Calculations;
use App\Models\Company;
use App\Models\Services;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use App\Traits\GlobalPagination;
use App\Support\DashboardCardRegistry;

class HomeController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('auth');
  }

  /**
   * Show the application dashboard.
   *
   * @return \Illuminate\Contracts\Support\Renderable
   */
  public function index()
  {
    $pieData = [
      'labels' => ["Vendors", "Customers", "Riders", "Bikes", "Sims"],
      'data' => [
        company_table('vendors')->count(),
        company_table('customers')->count(),
        company_table('riders')->count(),
        company_table('bikes')->count(),
        company_table('sims')->count()
      ],
      'colors' => ["#706c7e", "#5c98e5", "#0760d3", "#211c1d", "#94baec"]
    ];

    // LINE CHART: x from 0 to 10, y = sin(x)
    $lineData = ['x' => [], 'y' => []];
    for ($x = 0; $x <= 10; $x += 0.5) {
      $lineData['x'][] = $x;
      $lineData['y'][] = sin($x);
    }

    $dashboardCards = DashboardCardRegistry::cardsForUser(auth()->user());

    return view('content.dashboard', compact('pieData', 'lineData', 'dashboardCards'));
  }

  public function settings(Request $request)
  {

    /*   if (!auth()->user()->hasPermissionTo('setting_view')) {
        abort(403, 'Unauthorized action.');
      } */
    /*    if (\Gate::check("isUser", \Auth::user())) {
         abort(404);
       } */

    $isSettingsPanel = (bool) (View::shared('settings_panel') ?? false);
    $currentCompany = $request->attributes->get('company');

    if ($isSettingsPanel && $currentCompany instanceof Company && $request->isMethod('post')) {
      $validated = $request->validate([
        'company_name' => 'required|string|max:255',
        'company_email' => [
          'nullable',
          'email',
          'max:255',
          new \App\Rules\AvailableCompanyContactEmail($currentCompany->id),
        ],
        'company_phone' => 'nullable|string|max:50',
        'company_address' => 'nullable|string|max:1000',
        'company_country' => 'nullable|string|max:255',
        'company_city' => 'nullable|string|max:255',
        'company_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp',
        'settings.currency_code' => 'nullable|string|max:10',
        'settings.currency_symbol' => 'nullable|string|max:10',
        'settings.vat_number' => 'nullable|string|max:50',
        'settings.vat_percentage' => 'nullable|numeric',
      ]);

      $currentCompany->name = $validated['company_name'];

      $newEmail = isset($validated['company_email'])
        ? strtolower(trim((string) $validated['company_email']))
        : null;
      $currentEmail = strtolower(trim((string) ($currentCompany->email ?? '')));

      if ($newEmail !== $currentEmail) {
        $verifiedEmail = session('company_email_change_verified');
        if (!$verifiedEmail || strtolower((string) $verifiedEmail) !== $newEmail) {
          return back()
            ->withErrors(['company_email' => __('Please verify your new email address using the code we send you.')])
            ->withInput();
        }
        session()->forget(['company_email_change_verified', 'company_email_change_pending']);
      }

      $currentCompany->email = $newEmail ?: null;
      $currentCompany->phone = $validated['company_phone'] ?? null;
      $currentCompany->address = $validated['company_address'] ?? null;
      $currentCompany->country = $validated['company_country'] ?? null;
      $currentCompany->city = $validated['company_city'] ?? null;

      if ($request->hasFile('company_logo')) {
        $path = $request->file('company_logo')->store('company-logos', 'public');
        if (!empty($currentCompany->logo)) {
          Storage::disk('public')->delete($currentCompany->logo);
        }
        $currentCompany->logo = $path;
        Settings::updateOrCreate(['name' => 'company_logo', 'company_id' => $currentCompany->id], ['name' => 'company_logo', 'value' => $path, 'company_id' => $currentCompany->id]);
      }

      $currentCompany->save();
      AdminCompany::syncFromCentralCompany($currentCompany);

      if ($newEmail !== $currentEmail && $newEmail && $currentEmail !== '') {
        User::withoutGlobalScope('company')
          ->where('company_id', $currentCompany->id)
          ->whereRaw('LOWER(TRIM(email)) = ?', [$currentEmail])
          ->where('id', '!=', auth()->id())
          ->update(['email' => $newEmail]);

        if (auth()->check()) {
          $authUser = auth()->user();
          if (
            (int) $authUser->company_id === (int) $currentCompany->id
            && strtolower(trim((string) $authUser->email)) === $currentEmail
          ) {
            $authUser->email = $newEmail;
            $authUser->save();
          }
        }
      }

      foreach ((array) $request->post('settings', []) as $key => $value) {
        Settings::updateOrCreate(['name' => $key], ['name' => $key, 'value' => $value]);
      }

      return back()->with('success', __('Company details updated successfully.'));
    }

    if ($request->post('settings')) {

      foreach ($request->post('settings') as $key => $value) {
        //echo $key.'-'.$value;
        Settings::updateOrCreate(['name' => $key], ['name' => $key, 'value' => $value]);
        session()->flash('success', 'Settings updated successfully.');
      }
    }
    $settings = Settings::pluck('value', 'name');
    return view('content.settings', compact('settings', 'currentCompany'));
  }
}
