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
            \App\Models\Vendors::count(),
            \App\Models\Customers::count(),
            \App\Models\Riders::count(),
            \App\Models\Bikes::count(),
            \App\Models\Sims::count()
        ],
        'colors' => ["#706c7e", "#5c98e5", "#0760d3", "#211c1d", "#94baec"]
    ];

    // LINE CHART: x from 0 to 10, y = sin(x)
    $lineData = ['x' => [], 'y' => []];
    for ($x = 0; $x <= 10; $x += 0.5) {
        $lineData['x'][] = $x;
        $lineData['y'][] = sin($x);
    }

    return view('content.dashboard', compact('pieData', 'lineData'));

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
        'company_email' => 'nullable|email|max:255',
        'company_phone' => 'nullable|string|max:50',
        'company_address' => 'nullable|string|max:1000',
        'company_country' => 'nullable|string|max:255',
        'company_city' => 'nullable|string|max:255',
        'company_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp',
      ]);

      $currentCompany->name = $validated['company_name'];
      $currentCompany->email = $validated['company_email'] ?? null;
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
      }

      $currentCompany->save();
      AdminCompany::syncFromCentralCompany($currentCompany);

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
