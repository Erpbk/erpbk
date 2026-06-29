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
        return config("agreement_modules.modules.{$module}.permissions", ['agreement_view']);
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
}
