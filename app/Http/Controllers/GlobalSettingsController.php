<?php

namespace App\Http\Controllers;

use App\Helpers\Currency;
use App\Helpers\Number;
use App\Models\Company;
use App\Support\CompanyContext;
use App\Support\CompanyGlobalPreferences;
use App\Support\PublicStorageDisk;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GlobalSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (! auth()->user()->hasPermissionTo('settings_company_setting_view')) {
            abort(403, 'Unauthorized action.');
        }

        $company = $this->resolveCompany($request);
        $preferences = CompanyGlobalPreferences::all($company?->id);
        $prefConfig = config('global_preferences');

        $previewDateSample = date(CompanyGlobalPreferences::phpDateFormat($company?->id));
        $previewAmount = Number::format(1234567.89, null, $company?->id);
        $previewCurrency = Currency::formatPlain(1234567.89);

        $currencyCatalog = Currency::catalog();
        $selectedCurrencyCode = old('settings.currency_code', Currency::code());
        $selectedCurrencyCode = strtoupper(trim((string) $selectedCurrencyCode));
        if (! isset($currencyCatalog[$selectedCurrencyCode])) {
            $selectedCurrencyCode = 'AED';
        }
        $selectedCurrencySymbol = $currencyCatalog[$selectedCurrencyCode]['symbol']
            ?? Currency::defaultSymbolForCode($selectedCurrencyCode);

        $currencyIconPath = Currency::iconPath();
        $currencyIconExists = $currencyIconPath !== null && PublicStorageDisk::exists($currencyIconPath);
        $currencyIconUrl = $currencyIconExists ? PublicStorageDisk::url($currencyIconPath) : null;
        $currencyIconMode = old('currency_icon_mode', $currencyIconExists ? 'custom' : 'default');

        $brandColors = [
            'primary' => old('company_primary_color', $company->primary_color ?? '#2563eb'),
            'secondary' => old('company_secondary_color', $company->secondary_color ?? '#1e3a8a'),
            'accent' => old('company_accent_color', $company->accent_color ?? '#818cf8'),
        ];

        return view('settings.global_settings', compact(
            'preferences',
            'prefConfig',
            'previewDateSample',
            'previewAmount',
            'previewCurrency',
            'currencyCatalog',
            'selectedCurrencyCode',
            'selectedCurrencySymbol',
            'currencyIconPath',
            'currencyIconExists',
            'currencyIconUrl',
            'currencyIconMode',
            'brandColors',
            'company'
        ));
    }

    public function store(Request $request)
    {
        if (
            ! auth()->user()->hasPermissionTo('settings_company_setting_create')
            || ! auth()->user()->hasPermissionTo('settings_company_setting_edit')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $company = $this->resolveCompany($request);
        if (! $company) {
            return back()->with('error', __('Unable to resolve company for these settings.'));
        }

        $fontKeys = array_keys(config('global_preferences.font_families', []));
        $dateKeys = array_keys(config('global_preferences.date_formats', []));
        $thousandKeys = array_keys(config('global_preferences.thousand_separators', []));
        $decimalSepKeys = array_keys(config('global_preferences.decimal_separators', []));
        $positionKeys = array_keys(config('global_preferences.currency_positions', []));
        $decimalPlaces = config('global_preferences.decimal_places_options', [0, 1, 2, 3, 4]);

        $validated = $request->validate([
            'font_family' => ['required', 'string', Rule::in($fontKeys)],
            'date_format' => ['required', 'string', Rule::in($dateKeys)],
            'decimal_places' => ['required', 'integer', Rule::in($decimalPlaces)],
            'thousand_separator' => ['required', 'string', Rule::in($thousandKeys)],
            'decimal_separator' => ['required', 'string', Rule::in($decimalSepKeys)],
            'currency_position' => ['required', 'string', Rule::in($positionKeys)],
            'company_primary_color' => ['nullable', 'string', 'max:20'],
            'company_secondary_color' => ['nullable', 'string', 'max:20'],
            'company_accent_color' => ['nullable', 'string', 'max:20'],
            'settings.currency_code' => ['required', 'string', 'max:10', Rule::in(Currency::catalogCodes())],
            'currency_icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'currency_icon_mode' => ['nullable', 'in:default,custom'],
            'remove_currency_icon' => ['nullable', 'boolean'],
        ]);

        if ($validated['thousand_separator'] !== 'none'
            && $validated['thousand_separator'] === $validated['decimal_separator']) {
            return back()
                ->withErrors(['decimal_separator' => __('Thousand and decimal separators must be different.')])
                ->withInput();
        }

        CompanyGlobalPreferences::put([
            'font_family' => $validated['font_family'],
            'date_format' => $validated['date_format'],
            'decimal_places' => $validated['decimal_places'],
            'thousand_separator' => $validated['thousand_separator'],
            'decimal_separator' => $validated['decimal_separator'],
            'currency_position' => $validated['currency_position'],
        ], (int) $company->id);

        if ($request->filled('company_primary_color')) {
            $company->primary_color = $request->input('company_primary_color');
        }
        if ($request->filled('company_secondary_color')) {
            $company->secondary_color = $request->input('company_secondary_color');
        }
        if ($request->filled('company_accent_color')) {
            $company->accent_color = $request->input('company_accent_color');
        }
        $company->save();

        Currency::persistForCompany($request, $company);

        $route = $request->route() && str_starts_with((string) $request->route()->getName(), 'settings-panel.')
            ? 'settings-panel.global'
            : 'settings.global';

        return redirect()
            ->route($route, ['company_slug' => $request->route('company_slug') ?? session('company_slug')])
            ->with('success', __('Global settings saved successfully.'));
    }

    private function resolveCompany(Request $request): ?Company
    {
        $fromRequest = $request->attributes->get('company');
        if ($fromRequest instanceof Company) {
            return $fromRequest;
        }

        $companyId = CompanyContext::id() ?? auth()->user()?->company_id;
        if (! $companyId) {
            return null;
        }

        return Company::query()->find((int) $companyId);
    }
}
