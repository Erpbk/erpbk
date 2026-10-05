<?php

namespace App\Http\Controllers;

use App\Helpers\IConstants;
use App\Helpers\Common;
use App\Helpers\Currency;
use App\Models\AdminCompany;
use App\Models\Calculations;
use App\Models\Company;
use App\Models\Services;
use App\Models\Settings;
use App\Services\Company\CompanyContactEmailSync;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use App\Traits\GlobalPagination;
use App\Support\DashboardCardRegistry;
use App\Support\DocumentExpiryDashboard;
use App\Support\PublicStorageDisk;

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
    $user = auth()->user();
    $dashboardCards = DashboardCardRegistry::cardsForUser($user);
    $documentExpiryAlerts = DocumentExpiryDashboard::forUser($user);

    return view('content.dashboard', compact('dashboardCards', 'documentExpiryAlerts'));
  }

  public function settings(Request $request)
  {

    if (!auth()->user()->hasPermissionTo('settings_company_setting_view')) {
        abort(403, 'Unauthorized action.');
      }

    $isSettingsPanel = (bool) (View::shared('settings_panel') ?? false);
    $currentCompany = $request->attributes->get('company');

    if ($isSettingsPanel && $currentCompany instanceof Company && $request->isMethod('post')) {
      if (!auth()->user()->hasPermissionTo('settings_company_setting_create') || !auth()->user()->hasPermissionTo('settings_company_setting_edit')) {
        return back()->with('error', 'You are not authorized to create or edit company details.');
      }
      $validated = $request->validate([
        'company_name' => [
          'required',
          'string',
          'max:255',
          Rule::unique('companies', 'name')->ignore($currentCompany->id),
        ],
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
        'company_primary_color' => 'nullable|string|max:20',
        'company_secondary_color' => 'nullable|string|max:20',
        'settings.currency_code' => ['nullable', 'string', 'max:10', Rule::in(Currency::catalogCodes())],
        'settings.vat_number' => 'nullable|string|max:50',
        'settings.vat_percentage' => 'nullable|numeric',
        'currency_icon' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'currency_icon_mode' => 'nullable|in:default,custom',
        'remove_currency_icon' => 'nullable|boolean',
      ]);

      $nameChanged = $validated['company_name'] !== $currentCompany->name;
      $currentCompany->name = $validated['company_name'];
      if ($nameChanged) {
        $currentCompany->slug = Company::generateUniqueSlug($currentCompany->name, $currentCompany->id);
      }

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
      if ($request->filled('company_primary_color')) {
        $currentCompany->primary_color = $request->input('company_primary_color');
      }
      if ($request->filled('company_secondary_color')) {
        $currentCompany->secondary_color = $request->input('company_secondary_color');
      }

      if ($request->hasFile('company_logo')) {
        $path = $request->file('company_logo')->store('company-logos', 'public');
        if (!empty($currentCompany->logo)) {
          Storage::disk('public')->delete($currentCompany->logo);
        }
        $currentCompany->logo = $path;
      }

      $currentCompany->save();
      $settings = [
        'company_logo'    => $currentCompany->logo,
        'company_name'    => $currentCompany->name,
        'company_address' => $currentCompany->address,
        'company_phone'   => $currentCompany->phone,
        'company_email'   => $currentCompany->email,
        'company_city'    => $currentCompany->city,
        'company_country' => $currentCompany->country,
      ];

      foreach ($settings as $name => $value) {
        Settings::updateOrCreate(
          [
            'name' => $name,
            'company_id' => $currentCompany->id,
          ],
          [
            'name' => $name,
            'value' => $value,
            'company_id' => $currentCompany->id,
          ]
        );
      }

      if ($newEmail !== $currentEmail) {
        app(CompanyContactEmailSync::class)->apply($currentCompany, $currentEmail, $newEmail);
      } else {
        AdminCompany::syncFromCentralCompany($currentCompany);
      }

      $settingsPayload = (array) $request->post('settings', []);
      unset($settingsPayload['currency_code'], $settingsPayload['currency_symbol'], $settingsPayload['currency_icon']);

      foreach ($settingsPayload as $key => $value) {
        Settings::updateOrCreate(
          ['name' => $key, 'company_id' => $currentCompany->id],
          ['name' => $key, 'value' => $value, 'company_id' => $currentCompany->id]
        );
      }

      $this->persistCompanyCurrencySettings($request, $currentCompany);

      return back()->with('success', __('Company details updated successfully.'));
    }

    if ($request->isMethod('post') && $request->post('settings') && $currentCompany instanceof Company) {
      $request->validate([
        'settings.currency_code' => ['nullable', 'string', 'max:10', Rule::in(Currency::catalogCodes())],
        'currency_icon' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'currency_icon_mode' => 'nullable|in:default,custom',
        'remove_currency_icon' => 'nullable|boolean',
      ]);

      $settingsPayload = (array) $request->post('settings', []);
      unset($settingsPayload['currency_code'], $settingsPayload['currency_symbol'], $settingsPayload['currency_icon']);

      foreach ($settingsPayload as $key => $value) {
        Settings::updateOrCreate(
          ['name' => $key, 'company_id' => $currentCompany->id],
          ['name' => $key, 'value' => $value, 'company_id' => $currentCompany->id]
        );
      }

      $this->persistCompanyCurrencySettings($request, $currentCompany);
      session()->flash('success', 'Settings updated successfully.');
    }

    $companyId = $currentCompany instanceof Company ? $currentCompany->id : null;
    $settingsQuery = Settings::query();
    if ($companyId !== null) {
      $settingsQuery->where('company_id', $companyId);
    }
    $settings = $settingsQuery->pluck('value', 'name');
    $currencyCatalog = Currency::catalog();

    return view('content.settings', compact('settings', 'currentCompany', 'currencyCatalog'));
  }

  private function persistCompanyCurrencySettings(Request $request, Company $company): void
  {
    $code = strtoupper(trim((string) $request->input('settings.currency_code', Currency::code())));
    $catalog = Currency::catalog();
    if ($code === '' || ! isset($catalog[$code])) {
      $code = 'AED';
    }

    $symbol = Currency::defaultSymbolForCode($code);
    $existingIcon = (string) (Settings::query()
      ->where('company_id', $company->id)
      ->where('name', 'currency_icon')
      ->value('value') ?? '');

    $mode = (string) $request->input('currency_icon_mode', $existingIcon !== '' ? 'custom' : 'default');
    $removeIcon = $request->boolean('remove_currency_icon') || $mode === 'default';
    $iconPath = $existingIcon;

    if ($removeIcon && $existingIcon !== '') {
      if (PublicStorageDisk::exists($existingIcon)) {
        PublicStorageDisk::disk()->delete($existingIcon);
      }
      $iconPath = '';
    }

    if ($mode === 'custom' && $request->hasFile('currency_icon')) {
      $path = $request->file('currency_icon')->store('currency-icons', 'public');
      if ($existingIcon !== '' && $existingIcon !== $path && PublicStorageDisk::exists($existingIcon)) {
        PublicStorageDisk::disk()->delete($existingIcon);
      }
      $iconPath = $path;
    }

    foreach ([
      'currency_code' => $code,
      'currency_symbol' => $symbol,
      'currency_icon' => $iconPath,
    ] as $name => $value) {
      Settings::updateOrCreate(
        ['name' => $name, 'company_id' => $company->id],
        ['name' => $name, 'value' => $value, 'company_id' => $company->id]
      );
    }

    Currency::clearCache($company->id);
    cache()->forget('settings');
  }
}
