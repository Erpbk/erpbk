<?php

namespace App\Services\Agreements;

use App\Models\AgreementCategory;
use App\Models\AgreementTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class AgreementModuleService
{
    /** @var array<string, bool>|null */
    private static ?array $modulesWithContractsCache = null;

    /** @var array<string, list<array{id:int,name:string,template_id:?int,show_url:?string,preview_url:?string,record_preview_pattern:?string}>> */
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
        $keys = $this->configuredModuleKeys();

        return $keys !== [] ? implode('|', $keys) : 'riders|employees';
    }

    /**
     * All ERP modules that may be assigned to an agreement category.
     *
     * @return list<string>
     */
    public function assignableModuleKeys(): array
    {
        $excluded = config('agreement_modules.excluded_from_assignment', [
            'dashboard',
            'recycle_bin',
            'agreements',
            'documents',
            'accounts',
            'vouchers',
            'vat',
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
        return config("agreement_modules.modules.{$module}.permissions", ['agreements_view']);
    }

    public function authorize(string $module): void
    {
        if (! $this->isConfiguredModule($module)) {
            abort(404);
        }

        foreach ($this->permissionsFor($module) as $permission) {
            if (Gate::allows($permission) || Gate::allows('gn_settings')) {
                return;
            }
        }

        abort(403, 'Unauthorized');
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

    public function moduleLabel(string $module): string
    {
        return \App\Models\Settings::getMenuLabel($module);
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
     * Action-menu payload for agreements assigned to a module.
     *
     * @return list<array{id:int,name:string,template_id:?int,show_url:?string,preview_url:?string,record_preview_pattern:?string}>
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

        $companySlug = $this->companySlug();
        if ($companySlug === null) {
            return self::$actionMenuItemsCache[$module] = [];
        }

        try {
            $agreements = $this->agreementsForModule($module);
        } catch (\Throwable) {
            return self::$actionMenuItemsCache[$module] = [];
        }

        $configured = $this->isConfiguredModule($module);
        $items = [];

        foreach ($agreements as $agreement) {
            try {
                $template = $agreement->contractTemplate() ?? $agreement->defaultTemplate;
                $templateId = $template?->id;
                $showUrl = null;
                $previewUrl = null;
                $recordPreviewPattern = null;

                if ($configured) {
                    $showUrl = route('module-agreements.show', [
                        'company_slug' => $companySlug,
                        'module' => $module,
                        'category' => $agreement->id,
                    ]);
                }

                if ($templateId) {
                    $previewUrl = route('agreements.preview', [
                        'company_slug' => $companySlug,
                        'id' => $templateId,
                    ]);
                }

                if ($configured && $templateId) {
                    $recordPreviewPattern = route('module-contracts.preview', [
                        'company_slug' => $companySlug,
                        'module' => $module,
                        'record' => '__RECORD__',
                    ]) . '?template_id=' . $templateId;
                }

                $items[] = [
                    'id' => (int) $agreement->id,
                    'name' => (string) $agreement->name,
                    'template_id' => $templateId ? (int) $templateId : null,
                    'show_url' => $showUrl,
                    'preview_url' => $previewUrl,
                    'record_preview_pattern' => $recordPreviewPattern,
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return self::$actionMenuItemsCache[$module] = $items;
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
