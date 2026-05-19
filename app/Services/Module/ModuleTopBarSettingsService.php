<?php

namespace App\Services\Module;

use App\Models\ErpModuleTopCategory;
use App\Models\ErpModuleTopOption;
use App\Support\ErpModuleRegistry;
use App\Support\ModuleFieldSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ModuleTopBarSettingsService
{
    public function __construct(
        protected TopBarFilterService $filterService,
        protected ModuleTopBarFieldValueResolver $fieldValueResolver
    ) {}

    public function normalizeModuleKey(string $module): string
    {
        return ErpModuleRegistry::resolveTopBarModuleKey($module);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Model>
     */
    public function categoriesForModule(string $moduleKey)
    {
        $moduleKey = $this->normalizeModuleKey($moduleKey);
        $config = ErpModuleRegistry::topBarConfig($moduleKey);

        if ($config === null) {
            return collect();
        }

        if (($config['storage'] ?? '') === 'dedicated') {
            $modelClass = $config['category_model'];

            return $modelClass::with('options')
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();
        }

        $categories = ErpModuleTopCategory::query()
            ->with('options')
            ->where('module_key', $moduleKey)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        if ($categories->isEmpty()) {
            $this->migrateLegacyRtaFinesCategories($moduleKey);
            $categories = ErpModuleTopCategory::query()
                ->with('options')
                ->where('module_key', $moduleKey)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();
        }

        if ($categories->isEmpty()) {
            $this->seedPresetCategories($moduleKey);
            $categories = ErpModuleTopCategory::query()
                ->with('options')
                ->where('module_key', $moduleKey)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();
        }

        return $categories;
    }

    protected function migrateLegacyRtaFinesCategories(string $moduleKey): void
    {
        if (!ErpModuleRegistry::isRtaFinesTopBarModule($moduleKey)) {
            return;
        }

        $scopedStatus = $moduleKey === 'rta_fines_paid' ? 'paid' : 'unpaid';
        $companyId = auth()->user()->company_id ?? null;

        $legacyCategories = ErpModuleTopCategory::query()
            ->with('options')
            ->where('module_key', 'rta_fines')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        if ($legacyCategories->isEmpty()) {
            return;
        }

        foreach ($legacyCategories as $legacyCategory) {
            $matchingOptions = $legacyCategory->options->filter(function ($option) use ($scopedStatus) {
                return strtolower(trim((string) $option->name)) === $scopedStatus;
            });

            if ($matchingOptions->isEmpty()) {
                $categoryName = strtolower((string) $legacyCategory->name);
                if (!str_contains($categoryName, $scopedStatus)) {
                    continue;
                }
                $matchingOptions = $legacyCategory->options;
            }

            if ($matchingOptions->isEmpty()) {
                continue;
            }

            $exists = ErpModuleTopCategory::query()
                ->where('module_key', $moduleKey)
                ->where('name', $legacyCategory->name)
                ->where('db_column', $legacyCategory->db_column)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->exists();

            if ($exists) {
                continue;
            }

            $category = ErpModuleTopCategory::create([
                'module_key' => $moduleKey,
                'company_id' => $companyId,
                'name' => $legacyCategory->name,
                'db_column' => $legacyCategory->db_column,
                'filter_type' => $legacyCategory->filter_type,
                'display_order' => $legacyCategory->display_order,
                'is_active' => $legacyCategory->is_active,
                'show_in_top_bar' => $legacyCategory->show_in_top_bar,
                'show_in_view_cards' => $legacyCategory->show_in_view_cards,
            ]);

            $order = 1;
            foreach ($matchingOptions as $option) {
                ErpModuleTopOption::create([
                    'category_id' => $category->id,
                    'name' => $option->name,
                    'display_order' => $order++,
                    'is_active' => $option->is_active,
                    'show_in_top_bar' => $option->show_in_top_bar,
                    'show_in_view_cards' => $option->show_in_view_cards,
                ]);
            }
        }
    }

    public function seedPresetCategories(string $moduleKey): void
    {
        $moduleKey = $this->normalizeModuleKey($moduleKey);
        $config = ErpModuleRegistry::topBarConfig($moduleKey);
        $presets = $config['preset_categories'] ?? [];

        if ($presets === [] || ($config['storage'] ?? '') !== 'generic') {
            return;
        }

        $companyId = auth()->user()->company_id ?? null;

        foreach ($presets as $index => $preset) {
            $dbColumn = (string) ($preset['db_column'] ?? '');
            if ($dbColumn === '') {
                continue;
            }

            $exists = ErpModuleTopCategory::query()
                ->where('module_key', $moduleKey)
                ->where('db_column', $dbColumn)
                ->where('name', (string) ($preset['name'] ?? ''))
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->exists();

            if ($exists) {
                continue;
            }

            $category = ErpModuleTopCategory::create([
                'module_key' => $moduleKey,
                'company_id' => $companyId,
                'name' => (string) ($preset['name'] ?? ucwords(str_replace('_', ' ', $dbColumn))),
                'db_column' => $dbColumn,
                'filter_type' => $preset['filter_type'] ?? 'exact_match',
                'display_order' => $index + 1,
                'is_active' => true,
                'show_in_top_bar' => true,
                'show_in_view_cards' => false,
            ]);

            $order = 1;
            foreach ($preset['options'] ?? [] as $optionPreset) {
                $optionName = trim((string) ($optionPreset['name'] ?? ''));
                if ($optionName === '') {
                    continue;
                }

                ErpModuleTopOption::create([
                    'category_id' => $category->id,
                    'name' => $optionName,
                    'display_order' => $order++,
                    'is_active' => true,
                    'show_in_top_bar' => true,
                    'show_in_view_cards' => false,
                ]);
            }

            if ($order === 1) {
                $this->seedOptionsFromFieldSettings($moduleKey, $category);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function selectableColumns(string $moduleKey): array
    {
        return $this->filterService->selectableColumns($moduleKey);
    }

    /**
     * @return list<string>
     */
    public function allowedFilterTypes(): array
    {
        return array_keys(config('top_bar_filters.filter_types', []));
    }

    public function storeCategory(string $moduleKey, array $input): Model
    {
        $moduleKey = $this->normalizeModuleKey($moduleKey);
        $config = ErpModuleRegistry::topBarConfig($moduleKey);
        if ($config === null) {
            throw new \InvalidArgumentException('Module has no top bar configuration.');
        }

        $columns = array_keys($this->selectableColumns($moduleKey));
        $columnKey = \App\Support\ModuleTopBarRoutes::columnFieldForModule($moduleKey);

        $validated = validator($input, [
            $columnKey => ['required', 'string', Rule::in($columns)],
            'name' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $dbColumn = $validated[$columnKey];
        $humanName = trim((string) ($validated['name'] ?? ''));
        if ($humanName === '') {
            $humanName = ucwords(str_replace('_', ' ', $dbColumn));
        }

        if (($config['storage'] ?? '') === 'dedicated') {
            $category = $this->storeDedicatedCategory($config, $dbColumn, $humanName, null);
        } else {
            $category = $this->storeGenericCategory($moduleKey, $dbColumn, $humanName, null);
        }

        $this->seedOptionsFromFieldSettings($moduleKey, $category);

        return $category;
    }

    /**
     * Create top-bar options from Module Fields Settings defaults (dropdown options, etc.).
     */
    public function seedOptionsFromFieldSettings(string $moduleKey, Model $category): int
    {
        $moduleKey = $this->normalizeModuleKey($moduleKey);
        $config = ErpModuleRegistry::topBarConfig($moduleKey);
        if ($config === null) {
            return 0;
        }

        $column = $this->categoryDbColumn($config, $category);
        $values = $this->fieldValueResolver->configuredValuesForColumn($moduleKey, $column);
        if ($values === []) {
            return 0;
        }

        return $this->storeOptions($moduleKey, [
            'category_id' => $category->getKey(),
            'selected_values' => $values,
        ]);
    }

    protected function storeGenericCategory(string $moduleKey, string $dbColumn, string $name, ?string $filterType): Model
    {
        $companyId = auth()->user()->company_id ?? null;
        $exists = ErpModuleTopCategory::query()
            ->where('module_key', $moduleKey)
            ->where('db_column', $dbColumn)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->exists();

        if ($exists) {
            throw new \RuntimeException('This column is already configured as a category.');
        }

        return ErpModuleTopCategory::create([
            'module_key' => $moduleKey,
            'company_id' => $companyId,
            'name' => $name,
            'db_column' => $dbColumn,
            'filter_type' => $filterType,
            'display_order' => ((int) ErpModuleTopCategory::where('module_key', $moduleKey)->max('display_order')) + 1,
            'is_active' => true,
        ]);
    }

    protected function storeDedicatedCategory(array $config, string $dbColumn, string $name, ?string $filterType): Model
    {
        $modelClass = $config['category_model'];
        $columnAttr = (string) ($config['column_attribute'] ?? 'db_column');
        $companyId = auth()->user()->company_id ?? null;

        $existsQuery = $modelClass::query()->where($columnAttr, $dbColumn);
        if ($companyId && Schema::hasColumn((new $modelClass)->getTable(), 'company_id')) {
            $existsQuery->where('company_id', $companyId);
        }
        if ($existsQuery->exists()) {
            throw new \RuntimeException('This column is already configured as a category.');
        }

        return $modelClass::create([
            'name' => $name,
            $columnAttr => $dbColumn,
            'filter_type' => $filterType,
            'display_order' => ((int) $modelClass::max('display_order')) + 1,
            'is_active' => true,
        ]);
    }

    public function categoryFieldValues(string $moduleKey, int $categoryId): array
    {
        $moduleKey = $this->normalizeModuleKey($moduleKey);
        $config = ErpModuleRegistry::topBarConfig($moduleKey);
        if ($config === null) {
            return ['success' => false, 'message' => 'Invalid module.', 'values' => []];
        }

        $category = $this->findCategory($config, $moduleKey, $categoryId);
        $column = $this->categoryDbColumn($config, $category);
        $table = (string) ($config['source_table'] ?? ModuleFieldSource::resolveSourceTable($moduleKey));

        if ($column === '' || $table === '' || !Schema::hasColumn($table, $column)) {
            return ['success' => false, 'message' => 'Category source column is invalid.', 'values' => []];
        }

        $configuredValues = $this->fieldValueResolver->configuredValuesForColumn($moduleKey, $column);

        $tableValues = DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->all();

        $values = $this->fieldValueResolver->mergeConfiguredAndTableValues($configuredValues, $tableValues);

        return ['success' => true, 'column' => $column, 'values' => $values];
    }

    public function storeOptions(string $moduleKey, array $input): int
    {
        $config = ErpModuleRegistry::topBarConfig($this->normalizeModuleKey($moduleKey));
        if ($config === null) {
            return 0;
        }

        $optionClass = ($config['storage'] ?? '') === 'generic'
            ? ErpModuleTopOption::class
            : $config['option_model'];

        $categoryTable = ($config['storage'] ?? '') === 'generic'
            ? 'erp_module_top_categories'
            : (new ($config['category_model']))->getTable();

        $validated = validator($input, [
            'category_id' => ['required', 'integer', "exists:{$categoryTable},id"],
            'selected_values' => ['nullable', 'array'],
            'selected_values.*' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $categoryId = (int) $validated['category_id'];
        $items = collect($validated['selected_values'] ?? [])
            ->map(fn($v) => trim((string) $v))
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values();

        if ($items->isEmpty()) {
            $single = trim((string) ($validated['name'] ?? ''));
            if ($single !== '') {
                $items = collect([$single]);
            }
        }

        $nextOrder = ((int) $optionClass::where('category_id', $categoryId)->max('display_order')) + 1;
        $created = 0;

        $optionTable = (new $optionClass)->getTable();
        $optionPayloadBase = ['is_active' => true];
        if (Schema::hasColumn($optionTable, 'show_in_top_bar')) {
            $optionPayloadBase['show_in_top_bar'] = true;
        }
        if (Schema::hasColumn($optionTable, 'show_in_view_cards')) {
            $optionPayloadBase['show_in_view_cards'] = false;
        }

        foreach ($items as $item) {
            if ($optionClass::where('category_id', $categoryId)->where('name', $item)->exists()) {
                continue;
            }
            $optionClass::create(array_merge($optionPayloadBase, [
                'category_id' => $categoryId,
                'name' => $item,
                'display_order' => $nextOrder++,
            ]));
            $created++;
        }

        return $created;
    }

    protected function findCategory(array $config, string $moduleKey, int $id): Model
    {
        if (($config['storage'] ?? '') === 'generic') {
            return ErpModuleTopCategory::query()
                ->where('module_key', $moduleKey)
                ->where('id', $id)
                ->firstOrFail();
        }

        return $config['category_model']::findOrFail($id);
    }

    protected function categoryDbColumn(array $config, Model $category): string
    {
        if (($config['storage'] ?? '') === 'generic') {
            return trim((string) ($category->db_column ?? ''));
        }

        $attr = (string) ($config['column_attribute'] ?? 'db_column');

        return trim((string) ($category->{$attr} ?? ''));
    }
}
