<?php

namespace App\Services\Agreements;

use App\Models\AdminAgreementAssignableModule;
use App\Models\AdminAgreementPlaceholder;
use App\Models\AgreementPlaceholder;
use App\Support\CompanyModuleVisibility;
use App\Support\ModuleFieldSource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AgreementPlaceholderCatalog
{
    /**
     * Module keys admin has enabled for agreement assignment (no company filter).
     *
     * @return list<string>
     */
    public function adminEnabledModuleKeys(): array
    {
        try {
            if (! Schema::connection('mysql_admin')->hasTable('admin_agreement_assignable_modules')) {
                return $this->fallbackAssignableKeys();
            }

            $keys = AdminAgreementAssignableModule::query()
                ->where('enabled', true)
                ->orderBy('sort_order')
                ->pluck('module_key')
                ->map(fn ($key) => (string) $key)
                ->all();

            $keys = $this->withoutExcludedAssignableKeys($keys);

            return $keys !== [] ? $keys : $this->fallbackAssignableKeys();
        } catch (Throwable) {
            return $this->fallbackAssignableKeys();
        }
    }

    /**
     * Keys shown on company create/edit agreement screens.
     *
     * @return list<string>
     */
    public function companyAssignableModuleKeys(): array
    {
        $erpKeys = array_keys(config('erp_modules.modules', []));

        return array_values(array_filter(
            $this->adminEnabledModuleKeys(),
            static fn (string $key) => in_array($key, $erpKeys, true) && CompanyModuleVisibility::enabled($key)
        ));
    }

    /**
     * Placeholders grouped for the editor sidebar.
     *
     * @return array<string, \Illuminate\Support\Collection<int, object>>
     */
    public function groupedForModule(?string $moduleKey): array
    {
        $moduleKey = $moduleKey !== null ? trim($moduleKey) : '';

        try {
            if (Schema::connection('mysql_admin')->hasTable('admin_agreement_placeholders')) {
                $query = AdminAgreementPlaceholder::query()->orderBy('sort_order');
                if ($moduleKey !== '') {
                    $query->whereIn('module_key', [$moduleKey, 'system']);
                } else {
                    $query->where('module_key', 'system');
                }

                $items = $query->get();
                if ($items->isNotEmpty()) {
                    return $items->groupBy(fn ($row) => $row->group_label ?: 'General')->all();
                }
            }
        } catch (Throwable) {
            // Fall through to tenant catalog.
        }

        return AgreementPlaceholder::grouped();
    }

    /**
     * Flat list of placeholders for a module (including system).
     *
     * @return list<AdminAgreementPlaceholder>
     */
    public function placeholdersForModule(string $moduleKey): array
    {
        try {
            if (! Schema::connection('mysql_admin')->hasTable('admin_agreement_placeholders')) {
                return [];
            }

            return AdminAgreementPlaceholder::query()
                ->whereIn('module_key', [trim($moduleKey), 'system'])
                ->orderBy('sort_order')
                ->get()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<string>
     */
    public function groupLabels(): array
    {
        return array_values(config('agreement_modules.placeholder_groups', [
            'Personal Information',
            'Address Information',
            'Employment Information',
            'Vehicle Information',
            'System Information',
            'Related',
            'General',
        ]));
    }

    /**
     * Flat source_key => label map (for validation / selected value checks).
     *
     * @return array<string, string>
     */
    public function sourceFieldOptions(string $moduleKey): array
    {
        $flat = [];
        foreach ($this->sourceFieldOptionGroups($moduleKey) as $group) {
            foreach ($group['options'] as $key => $label) {
                $flat[$key] = $label;
            }
        }

        return $flat;
    }

    /**
     * Grouped source options for admin optgroups.
     *
     * @return list<array{label: string, options: array<string, string>}>
     */
    public function sourceFieldOptionGroups(string $moduleKey): array
    {
        $system = [
            'label' => 'System',
            'options' => [
                'company_name' => 'Company name',
                'current_date' => 'Current date',
            ],
        ];

        if ($moduleKey === '' || $moduleKey === 'system') {
            return [$system];
        }

        $fkMap = config('agreement_modules.foreign_key_sources', []);
        $fkExclude = config('agreement_modules.foreign_key_exclude', []);
        $expandableOnModule = [];

        $fields = [];
        foreach (ModuleFieldSource::schemaFieldKeysForModule($moduleKey) as $column) {
            if (in_array($column, $fkExclude, true)) {
                continue;
            }
            $fkMeta = $this->resolveForeignKeyMeta($column, $moduleKey);
            if ($fkMeta !== null && ! $this->isSelfIdentityFk($moduleKey, $column, $fkMeta)) {
                $expandableOnModule[$column] = $fkMeta;
                continue;
            }
            $fields[$column] = ModuleFieldSource::defaultAssignmentFieldLabel($column).' (DB)';
        }

        $groups = [$system];
        if ($fields !== []) {
            $groups[] = [
                'label' => 'Fields',
                'options' => $fields,
            ];
        }

        foreach ($expandableOnModule as $fkColumn => $meta) {
            $relatedOptions = $this->relatedSourceOptions($moduleKey, $meta);
            if ($relatedOptions === []) {
                continue;
            }
            $groups[] = [
                'label' => 'Related: '.($meta['label'] ?? Str::headline((string) ($meta['relation'] ?? $fkColumn))),
                'options' => $relatedOptions,
            ];
        }

        return $groups;
    }

    /**
     * @param  array{relation: string, label?: string, module?: string|null, table?: string|null, model?: class-string|null}  $meta
     * @return array<string, string>
     */
    private function relatedSourceOptions(string $parentModule, array $meta): array
    {
        $relation = (string) ($meta['relation'] ?? '');
        if ($relation === '') {
            return [];
        }

        $relatedModule = isset($meta['module']) ? (string) $meta['module'] : '';
        $options = [];

        if ($relatedModule !== '' && $relatedModule !== $parentModule) {
            try {
                if (Schema::connection('mysql_admin')->hasTable('admin_agreement_placeholders')) {
                    $rows = AdminAgreementPlaceholder::query()
                        ->where('module_key', $relatedModule)
                        ->orderBy('sort_order')
                        ->get();
                    foreach ($rows as $row) {
                        $leaf = trim((string) ($row->source_key ?: trim((string) $row->placeholder, '{}')));
                        if ($leaf === '' || str_contains($leaf, '.')) {
                            continue;
                        }
                        if (in_array($leaf, ['company_name', 'current_date'], true)) {
                            continue;
                        }
                        $key = $relation.'.'.$leaf;
                        $label = trim((string) ($row->description ?: $leaf));
                        $options[$key] = $label.' ('.$row->placeholder.')';
                    }
                }
            } catch (Throwable) {
                // Fall through to schema columns.
            }
        }

        if ($options !== []) {
            return $options;
        }

        $table = $meta['table'] ?? null;
        if (! $table && $relatedModule !== '') {
            $table = ModuleFieldSource::resolveSourceTable($relatedModule);
        }
        if (! $table || ! Schema::hasTable($table)) {
            return [];
        }

        $fkMap = config('agreement_modules.foreign_key_sources', []);
        $fkExclude = config('agreement_modules.foreign_key_exclude', []);
        $exclude = array_merge(
            ModuleFieldSource::defaultExcludedFields(),
            ModuleFieldSource::tableSpecificExcludedColumns($table),
            $fkExclude,
            array_keys($fkMap)
        );

        foreach (Schema::getColumnListing($table) as $column) {
            if (in_array($column, $exclude, true)) {
                continue;
            }
            $options[$relation.'.'.$column] = ModuleFieldSource::defaultAssignmentFieldLabel($column).' (DB)';
        }

        return $options;
    }

    /**
     * @param  array{relation?: string, module?: string|null}  $meta
     */
    private function isSelfIdentityFk(string $moduleKey, string $column, array $meta): bool
    {
        $relatedModule = isset($meta['module']) ? (string) $meta['module'] : '';
        if ($relatedModule !== '' && $relatedModule === $moduleKey) {
            return true;
        }

        // Business code columns on their own tables (not FKs).
        return ($moduleKey === 'riders' && $column === 'rider_id')
            || ($moduleKey === 'employees' && $column === 'employee_id');
    }

    /**
     * Resolve FK meta for a column, optionally scoped to a module (`by_module` entries).
     *
     * @return array{relation: string, label?: string, module?: string|null, table?: string|null, model?: class-string|null, fk_column?: string}|null
     */
    public function resolveForeignKeyMeta(string $column, ?string $moduleKey = null): ?array
    {
        $raw = config('agreement_modules.foreign_key_sources.'.$column);
        if (! is_array($raw)) {
            return null;
        }

        if (isset($raw['by_module']) && is_array($raw['by_module'])) {
            if ($moduleKey === null || $moduleKey === '') {
                return null;
            }
            $meta = $raw['by_module'][$moduleKey] ?? null;
            if (! is_array($meta) || empty($meta['relation'])) {
                return null;
            }

            return $meta + ['fk_column' => $column];
        }

        if (empty($raw['relation'])) {
            return null;
        }

        return $raw + ['fk_column' => $column];
    }

    /**
     * Look up FK meta by relation name (for resolver).
     *
     * @return array{relation: string, label?: string, module?: string|null, table?: string|null, model?: class-string|null, fk_column?: string}|null
     */
    public function foreignKeyMetaForRelation(string $relation): ?array
    {
        foreach (config('agreement_modules.foreign_key_sources', []) as $column => $raw) {
            if (! is_array($raw)) {
                continue;
            }

            if (isset($raw['by_module']) && is_array($raw['by_module'])) {
                foreach ($raw['by_module'] as $meta) {
                    if (! is_array($meta)) {
                        continue;
                    }
                    if (($meta['relation'] ?? '') === $relation) {
                        return $meta + ['fk_column' => (string) $column];
                    }
                }
                continue;
            }

            if (($raw['relation'] ?? '') === $relation) {
                return $raw + ['fk_column' => (string) $column];
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function withoutExcludedAssignableKeys(array $keys): array
    {
        $excluded = config('agreement_modules.excluded_from_assignment', []);

        return array_values(array_filter(
            $keys,
            static fn (string $key) => ! in_array($key, $excluded, true)
        ));
    }

    /**
     * @return list<string>
     */
    private function fallbackAssignableKeys(): array
    {
        return $this->withoutExcludedAssignableKeys(
            array_keys(config('erp_modules.modules', []))
        );
    }
}
