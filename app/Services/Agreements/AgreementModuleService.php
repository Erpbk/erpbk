<?php

namespace App\Services\Agreements;

use App\Models\AgreementCategory;
use App\Models\AgreementTemplate;
use App\Models\Company;
use App\Models\Settings;
use App\Support\ErpModuleRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class AgreementModuleService
{
    /** @var array<string, bool>|null */
    private static ?array $modulesWithContractsCache = null;

    /** @var array<string, list<array{id:int,name:string,index_url:string,show_url:string,preview_url:string,template_id:?int,record_preview_pattern:?string}>> */
    private static array $actionMenuItemsCache = [];

    public function isConfiguredModule(string $module): bool
    {
        return array_key_exists($module, config('agreement_modules.modules', []));
    }

    /**
     * @return list<string>
     */
    public function configuredModuleKeys(): array
    {
        return array_keys(config('agreement_modules.modules', []));
    }

    /**
     * Route constraint for {module} agreement/contract routes.
     */
    public function routePattern(): string
    {
        $keys = array_values(array_unique(array_merge(
            $this->configuredModuleKeys(),
            $this->assignableModuleKeys()
        )));

        return $keys !== [] ? implode('|', $keys) : 'riders|employees';
    }

    public function supportsAgreementListing(string $module): bool
    {
        return $this->isConfiguredModule($module)
            || in_array($module, $this->assignableModuleKeys(), true);
    }

    /**
     * All ERP modules that may be assigned to an agreement category.
     *
     * @return list<string>
     */
    public function assignableModuleKeys(): array
    {
        return app(AgreementPlaceholderCatalog::class)->companyAssignableModuleKeys();
    }

    /**
     * Admin-enabled modules without company visibility filtering.
     *
     * @return list<string>
     */
    public function adminAssignableModuleKeys(): array
    {
        return app(AgreementPlaceholderCatalog::class)->adminEnabledModuleKeys();
    }

    /**
     * Legacy config fallback keys (erp modules minus excluded).
     *
     * @return list<string>
     */
    public function configAssignableModuleKeys(): array
    {
        $excluded = config('agreement_modules.excluded_from_assignment', [
            'dashboard',
            'recycle_bin',
            'agreements',
            'documents',
            'accounts',
            'vouchers',
            'vat',
            'cash_banks',
            'loans',
            'attendance',
            'items',
            'leads',
            'customer_invoices',
            'rta_fines',
            'rta_saliks',
            'inventory',
            'visa_expense',
            'installments',
            'license_expense',
            'legal_case',
            'expenses',
            'assets',
            'cheques',
            'passport_handover',
            'rider_inventory',
        ]);

        return array_values(array_diff(
            array_keys(config('erp_modules.modules', [])),
            $excluded
        ));
    }

    public function moduleHasContracts(string $module): bool
    {
        if (! $this->isConfiguredModule($module)) {
            return false;
        }

        return in_array($module, $this->modulesWithActiveAgreements(), true);
    }

    /**
     * @return list<string>
     */
    public function modulesWithActiveAgreements(): array
    {
        if (self::$modulesWithContractsCache !== null) {
            return self::$modulesWithContractsCache;
        }

        try {
            AgreementCategory::ensureDefaultsForCompany();
            $configured = array_keys(config('agreement_modules.modules', []));
            $active = AgreementCategory::activeModuleKeysWithAgreements();
            self::$modulesWithContractsCache = array_values(array_intersect($configured, $active));
        } catch (\Throwable) {
            self::$modulesWithContractsCache = [];
        }

        return self::$modulesWithContractsCache;
    }

    /**
     * @return list<string>
     */
    public function permissionsFor(string $module): array
    {
        $configured = config("agreement_modules.modules.{$module}.permissions");
        if (is_array($configured) && $configured !== []) {
            return $configured;
        }

        $permissions = ['agreements_view', $module . '_view'];

        $aliases = [
            'attendance' => ['employees_attendance_view', 'riders_attendance_view'],
            'inventory' => ['items_inventory_view'],
            'license_expense' => ['licenseexpense_view'],
            'installments' => ['installments_view'],
            'expenses' => ['expenses_view'],
            'loans' => ['loans_view'],
        ];

        if (isset($aliases[$module])) {
            $permissions = array_merge($permissions, $aliases[$module]);
        }

        return array_values(array_unique($permissions));
    }

    public function authorize(string $module): void
    {
        if (! $this->supportsAgreementListing($module)) {
            abort(404);
        }

        foreach ($this->permissionsFor($module) as $permission) {
            if (Gate::allows($permission) || Gate::allows('gn_settings')) {
                return;
            }
        }

        abort(403, 'Unauthorized');
    }

    public function userCanAccessModule(string $module): bool
    {
        if (! $this->supportsAgreementListing($module)) {
            return false;
        }

        if (Gate::allows('gn_settings')) {
            return true;
        }

        foreach ($this->permissionsFor($module) as $permission) {
            if (Gate::allows($permission)) {
                return true;
            }
        }

        return false;
    }

    public function resolveRecord(string $module, int $recordId): Model
    {
        $modelClass = config("agreement_modules.modules.{$module}.model");
        if (! $modelClass || ! class_exists($modelClass)) {
            abort(404, 'Module record model not configured.');
        }

        /** @var Model $record */
        $record = $modelClass::query()->findOrFail($recordId);

        return $record;
    }

    /**
     * @return array{name: string, code: string, email: string, title: string}
     */
    public function recordMeta(string $module, Model $record): array
    {
        $labelField = config("agreement_modules.modules.{$module}.label_field", 'name');
        $codeField = config("agreement_modules.modules.{$module}.code_field", 'id');
        $emailField = config("agreement_modules.modules.{$module}.email_field", 'email');

        $name = (string) ($record->getAttribute($labelField) ?? '');
        $code = (string) ($record->getAttribute($codeField) ?? $record->getKey());
        $email = (string) ($record->getAttribute($emailField) ?? '');

        if ($email === '' && $module === 'employees') {
            $email = (string) ($record->getAttribute('personal_email') ?? '');
        }

        return [
            'name' => $name,
            'code' => $code,
            'email' => $email,
            'title' => trim($name . ($code !== '' ? ' (' . $code . ')' : '')) . ' — Contracts',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AgreementCategory>
     */
    public function agreementsForModule(string $module)
    {
        return AgreementCategory::query()
            ->assignedToModule($module)
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with('defaultTemplate')
            ->get();
    }

    /**
     * Load a module record and an agreement assigned to that module.
     *
     * @return array{0: Model, 1: AgreementCategory, 2: AgreementTemplate}
     */
    public function resolveAssignedAgreementForRecord(string $module, int $recordId, int $categoryId): array
    {
        $record = $this->resolveRecord($module, $recordId);
        $category = AgreementCategory::query()->findOrFail($categoryId);

        if (! $category->assignedToModule($module)) {
            abort(403, 'This agreement is not assigned to this record.');
        }

        $template = $category->contractTemplate()
            ?? $category->defaultTemplate
            ?? $category->templates()->where('status', true)->orderByDesc('is_default')->first();

        if (! $template) {
            abort(404, 'No template is available for this agreement.');
        }

        return [$record, $category, $template];
    }

    public function recordAgreementsTitle(string $module, Model $record): string
    {
        $label = trim((string) $this->moduleLabel($module));
        $singular = $label !== '' ? \Illuminate\Support\Str::singular($label) : ucfirst(str_replace('_', ' ', $module));
        $meta = $this->recordMeta($module, $record);
        $name = $meta['name'] !== '' ? $meta['name'] : $meta['code'];

        return $singular . ' Agreements - ' . $name;
    }

    public function resolveContractTemplate(int $templateId, string $module): AgreementTemplate
    {
        $template = AgreementTemplate::query()->with('category')->findOrFail($templateId);
        $category = $template->category;

        if (! $category || ! $category->status || ! $category->assignedToModule($module)) {
            abort(403, 'Template is not assigned to this module.');
        }

        $assigned = $category->contractTemplate();
        if (! $assigned || (int) $assigned->id !== (int) $template->id) {
            abort(403, 'Only the contract template assigned in Agreement settings can be used.');
        }

        return $template;
    }

    /**
     * PDF/signatory view object (templates expect rider-like fields).
     */
    public function pdfSubject(string $module, Model $record): object
    {
        $meta = $this->recordMeta($module, $record);

        return (object) [
            'name' => $meta['name'],
            'rider_id' => $meta['code'],
            'email' => $meta['email'],
        ];
    }

    /**
     * Display name for a module: company custom title when set, otherwise the default label.
     */
    public function moduleLabel(string $module): string
    {
        $module = ErpModuleRegistry::normalizeKey($module);
        $labelKeys = $this->labelKeysForModule($module);

        foreach ($labelKeys as $key) {
            $custom = trim((string) ($this->companyLabelOverrides()[$key] ?? ''));
            if ($custom !== '') {
                return $custom;
            }
        }

        $menuLabels = Settings::getMenuLabels();
        foreach ($labelKeys as $key) {
            $label = trim((string) ($menuLabels[$key] ?? ''));
            if ($label !== '' && $label !== $key) {
                return $label;
            }
        }

        return (string) (
            config("company_modules.modules.{$module}.label")
            ?? config("erp_modules.modules.{$module}")
            ?? config("menu_labels.defaults.{$module}")
            ?? ucwords(str_replace('_', ' ', $module))
        );
    }

    /**
     * @return list<string>
     */
    private function labelKeysForModule(string $module): array
    {
        $keys = [$module];

        $primary = config("company_modules.modules.{$module}.primary_label_key");
        if (is_string($primary) && $primary !== '') {
            $keys[] = ErpModuleRegistry::normalizeKey($primary);
        }

        // erp_modules key ↔ sidebar menu_labels key mismatches
        $synonyms = [
            'garages_customers' => 'garage_customers',
            'garage_customers' => 'garages_customers',
        ];
        if (isset($synonyms[$module])) {
            $keys[] = $synonyms[$module];
        }

        foreach (ErpModuleRegistry::menuLabelAliases($module) as $alias) {
            $keys[] = $alias;
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return array<string, string>
     */
    private function companyLabelOverrides(): array
    {
        $company = view()->shared('currentCompany');
        if (! $company instanceof Company || ! is_array($company->modules_settings)) {
            return [];
        }

        $overrides = $company->modules_settings['label_overrides'] ?? [];
        if (! is_array($overrides)) {
            return [];
        }

        $filtered = [];
        foreach ($overrides as $key => $value) {
            if (! is_string($key) || ! is_string($value)) {
                continue;
            }
            $value = trim($value);
            if ($value !== '') {
                $filtered[ErpModuleRegistry::normalizeKey($key)] = $value;
            }
        }

        return $filtered;
    }

    /**
     * ERP assignment key for the current page (matches agreement_categories.assigned_modules).
     */
    public function assignmentKeyFromRequest(?\Illuminate\Http\Request $request = null): ?string
    {
        $request = $request ?? request();
        $route = $request->route();
        $assignable = $this->assignableModuleKeys();

        if ($route) {
            $param = $route->parameter('module');
            if (is_string($param) && in_array($param, $assignable, true)) {
                return $param;
            }
        }

        $name = (string) ($route?->getName() ?? '');
        $prefix = strtolower((string) explode('.', $name)[0]);

        $aliases = [
            'bikes' => 'bikes',
            'bike' => 'bikes',
            'riders' => 'riders',
            'rider' => 'riders',
            'employees' => 'employees',
            'employee' => 'employees',
            'customers' => 'customers',
            'customer' => 'customers',
            'vendors' => 'vendors',
            'vendor' => 'vendors',
            'garages' => 'garages',
            'garage' => 'garages',
            'sims' => 'sims',
            'sim' => 'sims',
            'simcompanies' => 'sims',
            'siminvoices' => 'sims',
            'cheques' => 'cheques',
            'cheque' => 'cheques',
            'rtafines' => 'rta_fines',
            'salik' => 'rta_saliks',
            'fuelcards' => 'fuel_cards',
            'fueldata' => 'fuel_cards',
            'visaexpenses' => 'visa_expense',
            'legalcases' => 'legal_case',
            'fixedassets' => 'assets',
            'banks' => 'cash_banks',
            'leasingcompanies' => 'leasing_companies',
            'suppliers' => 'supplier',
            'recruiters' => 'recruiters',
            'items' => 'items',
            'attendance' => 'attendance',
            'expenses' => 'expenses',
            'inventory' => 'inventory',
            'riderinventory' => 'rider_inventory',
            'installments' => 'installments',
            'licenseexpenses' => 'license_expense',
            'leads' => 'leads',
            'loans' => 'loans',
            'assets' => 'assets',
        ];

        $compact = str_replace(['-', '_'], '', $prefix);
        if (isset($aliases[$compact])) {
            return $aliases[$compact];
        }

        $snake = \Illuminate\Support\Str::snake(str_replace('-', '_', $prefix));
        if (in_array($snake, $assignable, true)) {
            return $snake;
        }

        $topBar = \App\Support\ModuleRouteResolver::fromRequest($request);
        $topBarMap = [
            'bike_list' => 'bikes',
            'rta_fines_unpaid' => 'rta_fines',
            'rta_fines_paid' => 'rta_fines',
        ];
        if ($topBar && isset($topBarMap[$topBar])) {
            return $topBarMap[$topBar];
        }
        if ($topBar && in_array($topBar, $assignable, true)) {
            return $topBar;
        }

        $segments = explode('/', trim((string) $request->path(), '/'));
        if (count($segments) >= 3 && strtolower($segments[0]) === 'app') {
            $pathCompact = str_replace(['-', '_'], '', strtolower($segments[2]));
            if (isset($aliases[$pathCompact])) {
                return $aliases[$pathCompact];
            }
        }

        return null;
    }

    /**
     * Single Action-menu item that opens the module's assigned-agreements page.
     *
     * @return list<array{id:int,name:string,index_url:string,show_url:string,preview_url:string,template_id:?int,record_preview_pattern:?string}>
     */
    public function actionMenuItemsForModule(?string $module = null): array
    {
        $module = $module ?? $this->assignmentKeyFromRequest();
        if ($module === null) {
            return [];
        }

        if (array_key_exists($module, self::$actionMenuItemsCache)) {
            return self::$actionMenuItemsCache[$module];
        }

        if (! $this->userCanAccessModule($module)) {
            return self::$actionMenuItemsCache[$module] = [];
        }

        if (! $this->isConfiguredModule($module)) {
            return self::$actionMenuItemsCache[$module] = [];
        }

        $companySlug = $this->companySlug();
        if ($companySlug === null) {
            return self::$actionMenuItemsCache[$module] = [];
        }

        try {
            $pattern = route('module-record-agreements.index', [
                'company_slug' => $companySlug,
                'module' => $module,
                'record' => '__RECORD__',
            ]);
        } catch (\Throwable) {
            return self::$actionMenuItemsCache[$module] = [];
        }

        return self::$actionMenuItemsCache[$module] = [[
            'id' => 0,
            'name' => 'Agreements',
            'index_url' => $pattern,
            'show_url' => $pattern,
            'preview_url' => $pattern,
            'template_id' => null,
            'record_preview_pattern' => $pattern,
        ]];
    }

    private function companySlug(?\Illuminate\Http\Request $request = null): ?string
    {
        $request = $request ?? request();
        $slug = $request->route('company_slug');
        if (is_string($slug) && $slug !== '') {
            return $slug;
        }

        if ($request->hasSession()) {
            $sessionSlug = $request->session()->get('company_slug');
            if (is_string($sessionSlug) && $sessionSlug !== '') {
                return $sessionSlug;
            }
        }

        $company = $request->attributes->get('company');
        if (is_object($company) && ! empty($company->slug)) {
            return (string) $company->slug;
        }

        return null;
    }
}
