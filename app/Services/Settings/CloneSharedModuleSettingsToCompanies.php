<?php

namespace App\Services\Settings;

use App\Services\Permissions\TopBarOptionPermissionSync;
use App\Services\Permissions\TopBarPermissionSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copy shared/global module-settings rows so every company owns a distinct copy.
 * Never leaves settings rows with company_id NULL when companies exist.
 */
final class CloneSharedModuleSettingsToCompanies
{
    /** @var array<string, array<int, array<int, int>>> table => sourceId => companyId => newId */
    private array $idMap = [];

    public function run(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        $companyIds = DB::table('companies')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        if ($companyIds === []) {
            return;
        }

        $this->dropGlobalUniques();

        $this->cloneCategoryGroup(
            'bike_categories',
            'bike_field_category_assignments',
            'bike_custom_fields',
            'bike_document_types',
            $companyIds
        );
        $this->cloneAssignFields('bike_assign_field_assignments', 'bike_custom_fields', $companyIds);
        $this->cloneTopGroup('bike_top_categories', 'bike_top_options', ['bike_column'], 'bike_list', $companyIds);

        $this->cloneCategoryGroup(
            'rider_categories',
            'rider_field_category_assignments',
            'rider_custom_fields',
            'rider_document_types',
            $companyIds
        );
        $this->cloneTopGroup('rider_top_categories', 'rider_top_options', ['rider_column'], 'riders', $companyIds);

        $this->cloneCategoryGroup(
            'employee_categories',
            'employee_field_category_assignments',
            'employee_custom_fields',
            'employee_document_types',
            $companyIds
        );
        $this->cloneTopGroup('employee_top_categories', 'employee_top_options', ['employee_column'], 'employees', $companyIds);

        $this->cloneCategoryGroup(
            'cheque_categories',
            'cheque_field_category_assignments',
            'cheque_custom_fields',
            'cheque_document_types',
            $companyIds
        );
        $this->cloneTopGroup('cheque_top_categories', 'cheque_top_options', ['cheque_column'], 'cheques', $companyIds);

        $this->cloneModuleSettings($companyIds);
        $this->cloneAssignFields('sim_assign_field_assignments', 'module_custom_fields', $companyIds);
        $this->cloneTopGroup('erp_module_top_categories', 'erp_module_top_options', ['module_key', 'db_column'], 'erp', $companyIds);

        $this->mergeDuplicateKeys();
        $this->addCompanyUniques();
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function cloneCategoryGroup(
        string $categoriesTable,
        string $assignmentsTable,
        string $customFieldsTable,
        ?string $documentsTable,
        array $companyIds,
    ): void {
        $this->distributeAndClone($categoriesTable, ['slug'], $companyIds, null, null);
        $this->distributeAndClone($assignmentsTable, ['field_key'], $companyIds, 'category_id', $categoriesTable);
        $this->distributeAndClone($customFieldsTable, ['label', 'data_type', 'category_id'], $companyIds, 'category_id', $categoriesTable);
        if ($documentsTable !== null) {
            $this->distributeAndClone($documentsTable, ['key'], $companyIds, null, null);
        }
    }

    /**
     * @param  list<int>  $companyIds
     * @param  list<string>  $matchColumns
     */
    private function cloneTopGroup(
        string $categoriesTable,
        string $optionsTable,
        array $matchColumns,
        ?string $permissionModule,
        array $companyIds,
    ): void {
        $this->distributeAndClone($categoriesTable, $matchColumns, $companyIds, null, null);
        $this->distributeAndClone($optionsTable, ['name', 'category_id'], $companyIds, 'category_id', $categoriesTable);

        if ($permissionModule === null || ! Schema::hasTable($categoriesTable)) {
            return;
        }

        $newCategoryIds = [];
        foreach ($this->idMap[$categoriesTable] ?? [] as $sourceId => $byCompany) {
            foreach ($byCompany as $companyId => $newId) {
                if ($newId !== $sourceId) {
                    $newCategoryIds[$newId] = true;
                }
            }
        }

        foreach (array_keys($newCategoryIds) as $categoryId) {
            try {
                $model = $this->topCategoryModel($categoriesTable);
                if ($model === null) {
                    continue;
                }
                $instance = $model::withoutGlobalScopes()->find($categoryId);
                if ($instance) {
                    $moduleKey = $permissionModule === 'erp'
                        ? (string) ($instance->module_key ?? 'module')
                        : $permissionModule;
                    TopBarPermissionSync::syncForCategory($moduleKey, $instance);
                }
            } catch (\Throwable $e) {
                // Permission sync is best-effort during data clone.
            }
        }

        if (Schema::hasTable($optionsTable)) {
            foreach (array_keys($newCategoryIds) as $categoryId) {
                $options = DB::table($optionsTable)->where('category_id', $categoryId)->get();
                $optionModel = $this->topOptionModel($optionsTable);
                if ($optionModel === null) {
                    continue;
                }
                foreach ($options as $option) {
                    try {
                        $instance = $optionModel::withoutGlobalScopes()->find($option->id);
                        if ($instance) {
                            $moduleKey = $permissionModule;
                            if ($permissionModule === 'erp' || $categoriesTable === 'erp_module_top_categories') {
                                $cat = DB::table($categoriesTable)->where('id', $categoryId)->first();
                                $moduleKey = (string) ($cat->module_key ?? 'module');
                            }
                            TopBarOptionPermissionSync::syncOption($moduleKey, $instance);
                        }
                    } catch (\Throwable $e) {
                        // Permission sync is best-effort during data clone.
                    }
                }
            }
        }
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function cloneAssignFields(string $table, string $customFieldsTable, array $companyIds): void
    {
        $this->distributeAndClone($table, ['field_key', 'custom_field_id'], $companyIds, 'custom_field_id', $customFieldsTable);
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function cloneModuleSettings(array $companyIds): void
    {
        $this->distributeAndClone('module_setting_categories', ['module_key', 'slug'], $companyIds, null, null);
        $this->distributeAndClone(
            'module_field_category_assignments',
            ['module_key', 'field_key'],
            $companyIds,
            'category_id',
            'module_setting_categories'
        );
        $this->distributeAndClone(
            'module_custom_fields',
            ['module_key', 'label', 'data_type', 'category_id'],
            $companyIds,
            'category_id',
            'module_setting_categories'
        );
        $this->distributeAndClone('module_document_types', ['module_key', 'key'], $companyIds, null, null);
    }

    /**
     * @param  list<string>  $matchColumns
     * @param  list<int>  $companyIds
     */
    private function distributeAndClone(
        string $table,
        array $matchColumns,
        array $companyIds,
        ?string $fkColumn,
        ?string $fkTable,
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
            return;
        }

        $usableMatch = array_values(array_filter(
            $matchColumns,
            fn (string $col) => Schema::hasColumn($table, $col)
        ));

        $this->claimNullRows($table, $usableMatch, $companyIds, $fkColumn, $fkTable);
        $this->cloneFromTemplate($table, $usableMatch, $companyIds, $fkColumn, $fkTable);
        $this->mapEquivalentRows($table, $usableMatch, $companyIds, $fkColumn, $fkTable);
        $this->seedIdentityMap($table, $companyIds);
        if (! $this->isParentSettingsTable($table)) {
            $this->deleteRemainingNulls($table);
        }
    }

    /**
     * Map template-company row ids onto equivalent rows already owned by other companies.
     *
     * @param  list<string>  $matchColumns
     * @param  list<int>  $companyIds
     */
    private function mapEquivalentRows(
        string $table,
        array $matchColumns,
        array $companyIds,
        ?string $fkColumn,
        ?string $fkTable,
    ): void {
        $templateCompanyId = $this->templateCompanyId($table, $companyIds);
        if ($templateCompanyId === null) {
            return;
        }

        $templateRows = DB::table($table)
            ->where('company_id', $templateCompanyId)
            ->orderBy('id')
            ->get();

        foreach ($templateRows as $row) {
            $this->rememberId($table, (int) $row->id, $templateCompanyId, (int) $row->id);
            foreach ($companyIds as $companyId) {
                if ($companyId === $templateCompanyId) {
                    continue;
                }
                $payload = $this->rowToInsertArray($table, $row, $companyId, $fkColumn, $fkTable);
                if ($payload === null) {
                    continue;
                }
                $existingId = $this->matchingRowId($table, $matchColumns, $payload, $companyId);
                if ($existingId !== null) {
                    $this->rememberId($table, (int) $row->id, $companyId, $existingId);
                }
            }
        }
    }

    /**
     * @param  list<string>  $matchColumns
     * @param  list<int>  $companyIds
     */
    private function claimNullRows(
        string $table,
        array $matchColumns,
        array $companyIds,
        ?string $fkColumn,
        ?string $fkTable,
    ): void {
        $nullRows = DB::table($table)->whereNull('company_id')->orderBy('id')->get();
        foreach ($nullRows as $row) {
            $claimed = false;
            foreach ($companyIds as $companyId) {
                $payload = $this->rowToInsertArray($table, $row, $companyId, $fkColumn, $fkTable);
                if ($payload === null) {
                    continue;
                }
                if ($this->matchingRowExists($table, $matchColumns, $payload, $companyId)) {
                    continue;
                }
                if (! $claimed) {
                    DB::table($table)->where('id', $row->id)->update(['company_id' => $companyId]);
                    $this->rememberId($table, (int) $row->id, $companyId, (int) $row->id);
                    $claimed = true;
                    continue;
                }
                $newId = DB::table($table)->insertGetId($payload);
                $this->rememberId($table, (int) $row->id, $companyId, $newId);
            }
            if (! $claimed) {
                // Keep the row so FK children are not cascade-deleted. First company takes ownership.
                DB::table($table)->where('id', $row->id)->update(['company_id' => $companyIds[0]]);
                $this->rememberId($table, (int) $row->id, $companyIds[0], (int) $row->id);
            }
        }
    }

    /**
     * @param  list<string>  $matchColumns
     * @param  list<int>  $companyIds
     */
    private function cloneFromTemplate(
        string $table,
        array $matchColumns,
        array $companyIds,
        ?string $fkColumn,
        ?string $fkTable,
    ): void {
        $templateCompanyId = $this->templateCompanyId($table, $companyIds);
        if ($templateCompanyId === null) {
            return;
        }

        $templateRows = DB::table($table)
            ->where('company_id', $templateCompanyId)
            ->orderBy('id')
            ->get();

        foreach ($companyIds as $companyId) {
            if ($companyId === $templateCompanyId) {
                continue;
            }
            if ($this->companyHasRows($table, $companyId) && $this->isParentSettingsTable($table)) {
                continue;
            }
            foreach ($templateRows as $row) {
                $payload = $this->rowToInsertArray($table, $row, $companyId, $fkColumn, $fkTable);
                if ($payload === null) {
                    continue;
                }
                if ($this->matchingRowExists($table, $matchColumns, $payload, $companyId)) {
                    $existingId = $this->matchingRowId($table, $matchColumns, $payload, $companyId);
                    if ($existingId !== null) {
                        $this->rememberId($table, (int) $row->id, $companyId, $existingId);
                    }
                    continue;
                }
                $newId = DB::table($table)->insertGetId($payload);
                $this->rememberId($table, (int) $row->id, $companyId, $newId);
            }
        }
    }

    private function isParentSettingsTable(string $table): bool
    {
        return in_array($table, [
            'bike_categories',
            'rider_categories',
            'employee_categories',
            'cheque_categories',
            'module_setting_categories',
            'bike_top_categories',
            'rider_top_categories',
            'employee_top_categories',
            'cheque_top_categories',
            'erp_module_top_categories',
        ], true);
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function seedIdentityMap(string $table, array $companyIds): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
            return;
        }

        $rows = DB::table($table)->whereNotNull('company_id')->get(['id', 'company_id']);
        foreach ($rows as $row) {
            $this->rememberId($table, (int) $row->id, (int) $row->company_id, (int) $row->id);
        }
    }

    private function deleteRemainingNulls(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
            return;
        }

        DB::table($table)->whereNull('company_id')->delete();
    }

    /**
     * @param  list<int>  $companyIds
     */
    private function templateCompanyId(string $table, array $companyIds): ?int
    {
        foreach ($companyIds as $companyId) {
            if ($this->companyHasRows($table, $companyId)) {
                return $companyId;
            }
        }

        return null;
    }

    private function companyHasRows(string $table, int $companyId): bool
    {
        return DB::table($table)->where('company_id', $companyId)->exists();
    }

    /**
     * @param  list<string>  $matchColumns
     * @param  array<string, mixed>  $payload
     */
    private function matchingRowExists(string $table, array $matchColumns, array $payload, int $companyId): bool
    {
        return $this->matchingRowId($table, $matchColumns, $payload, $companyId) !== null;
    }

    /**
     * @param  list<string>  $matchColumns
     * @param  array<string, mixed>  $payload
     */
    private function matchingRowId(string $table, array $matchColumns, array $payload, int $companyId): ?int
    {
        $query = DB::table($table)->where('company_id', $companyId);
        $usable = false;
        foreach ($matchColumns as $column) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }
            $usable = true;
            $value = $payload[$column];
            if ($value === null) {
                $query->whereNull($column);
            } else {
                $query->where($column, $value);
            }
        }
        if (! $usable) {
            return null;
        }

        $id = $query->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rowToInsertArray(
        string $table,
        object $row,
        int $companyId,
        ?string $fkColumn,
        ?string $fkTable,
    ): ?array {
        $payload = (array) $row;
        unset($payload['id']);
        $payload['company_id'] = $companyId;

        if ($fkColumn !== null && $fkTable !== null && array_key_exists($fkColumn, $payload)) {
            $oldFk = $payload[$fkColumn];
            if ($oldFk === null || $oldFk === '') {
                $payload[$fkColumn] = null;
            } else {
                $mapped = $this->idMap[$fkTable][(int) $oldFk][$companyId] ?? null;
                if ($mapped === null) {
                    return null;
                }
                $payload[$fkColumn] = $mapped;
            }
        }

        return $payload;
    }

    private function rememberId(string $table, int $sourceId, int $companyId, int $newId): void
    {
        $this->idMap[$table][$sourceId][$companyId] = $newId;
    }

    private function topCategoryModel(string $table): ?string
    {
        return match ($table) {
            'bike_top_categories' => \App\Models\BikeTopCategory::class,
            'rider_top_categories' => \App\Models\RiderTopCategory::class,
            'employee_top_categories' => \App\Models\EmployeeTopCategory::class,
            'cheque_top_categories' => \App\Models\ChequeTopCategory::class,
            'erp_module_top_categories' => \App\Models\ErpModuleTopCategory::class,
            default => null,
        };
    }

    private function topOptionModel(string $table): ?string
    {
        return match ($table) {
            'bike_top_options' => \App\Models\BikeTopOption::class,
            'rider_top_options' => \App\Models\RiderTopOption::class,
            'employee_top_options' => \App\Models\EmployeeTopOption::class,
            'cheque_top_options' => \App\Models\ChequeTopOption::class,
            'erp_module_top_options' => \App\Models\ErpModuleTopOption::class,
            default => null,
        };
    }

    private function dropGlobalUniques(): void
    {
        $this->dropIndexes([
            ['bike_categories', 'bike_categories_slug_unique'],
            ['rider_categories', 'rider_categories_slug_unique'],
            ['employee_categories', 'employee_categories_slug_unique'],
            ['bike_field_category_assignments', 'bike_field_category_assignments_field_key_unique'],
            ['rider_field_category_assignments', 'rider_field_category_assignments_field_key_unique'],
            ['employee_field_category_assignments', 'employee_field_category_assignments_field_key_unique'],
            ['bike_document_types', 'bike_document_types_key_unique'],
            ['rider_document_types', 'rider_document_types_key_unique'],
            ['employee_document_types', 'employee_document_types_key_unique'],
            ['module_field_category_assignments', 'module_field_category_assignments_module_key_field_key_unique'],
            ['module_document_types', 'module_document_types_module_key_key_unique'],
            ['bike_assign_field_assignments', 'bike_assign_field_assignments_field_key_unique'],
            ['sim_assign_field_assignments', 'sim_assign_field_assignments_field_key_unique'],
        ]);

        $this->dropUniquesMissingCompanyId('bike_categories', ['slug']);
        $this->dropUniquesMissingCompanyId('rider_categories', ['slug']);
        $this->dropUniquesMissingCompanyId('employee_categories', ['slug']);
        $this->dropUniquesMissingCompanyId('bike_field_category_assignments', ['field_key']);
        $this->dropUniquesMissingCompanyId('rider_field_category_assignments', ['field_key']);
        $this->dropUniquesMissingCompanyId('employee_field_category_assignments', ['field_key']);
        $this->dropUniquesMissingCompanyId('bike_document_types', ['key']);
        $this->dropUniquesMissingCompanyId('rider_document_types', ['key']);
        $this->dropUniquesMissingCompanyId('employee_document_types', ['key']);
        $this->dropUniquesMissingCompanyId('module_field_category_assignments', ['module_key', 'field_key']);
        $this->dropUniquesMissingCompanyId('module_document_types', ['module_key', 'key']);
        $this->dropUniquesMissingCompanyId('bike_assign_field_assignments', ['field_key']);
        $this->dropUniquesMissingCompanyId('sim_assign_field_assignments', ['field_key']);
    }

    /**
     * Drop unique indexes that enforce global uniqueness on the given columns (no company_id).
     *
     * @param  list<string>  $keyColumns
     */
    private function dropUniquesMissingCompanyId(string $table, array $keyColumns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $database = Schema::getConnection()->getDatabaseName();
        $rows = DB::select(
            'SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS cols
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND NON_UNIQUE = 0 AND INDEX_NAME <> ?
             GROUP BY INDEX_NAME',
            [$database, $table, 'PRIMARY']
        );

        $wanted = implode(',', $keyColumns);
        foreach ($rows as $row) {
            $cols = (string) $row->cols;
            if ($cols === $wanted || $cols === implode(',', array_reverse($keyColumns))) {
                $this->dropIndexIfExists($table, (string) $row->INDEX_NAME);
            }
        }
    }

    /**
     * @param  list<array{0: string, 1: string}>  $indexes
     */
    private function dropIndexes(array $indexes): void
    {
        foreach ($indexes as [$table, $indexName]) {
            $this->dropIndexIfExists($table, $indexName);
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        try {
            Schema::table($table, function ($blueprint) use ($indexName) {
                $blueprint->dropUnique($indexName);
            });
        } catch (\Throwable $e) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
            } catch (\Throwable $ignored) {
            }
        }
    }

    private function mergeDuplicateKeys(): void
    {
        $this->mergeGroup('bike_categories', ['company_id', 'slug'], [
            ['bike_field_category_assignments', 'category_id'],
            ['bike_custom_fields', 'category_id'],
        ]);
        $this->mergeGroup('rider_categories', ['company_id', 'slug'], [
            ['rider_field_category_assignments', 'category_id'],
            ['rider_custom_fields', 'category_id'],
        ]);
        $this->mergeGroup('employee_categories', ['company_id', 'slug'], [
            ['employee_field_category_assignments', 'category_id'],
            ['employee_custom_fields', 'category_id'],
        ]);
        $this->mergeGroup('cheque_categories', ['company_id', 'slug'], [
            ['cheque_field_category_assignments', 'category_id'],
            ['cheque_custom_fields', 'category_id'],
        ]);
        $this->mergeGroup('module_setting_categories', ['company_id', 'module_key', 'slug'], [
            ['module_field_category_assignments', 'category_id'],
            ['module_custom_fields', 'category_id'],
        ]);
        $this->mergeGroup('bike_field_category_assignments', ['company_id', 'field_key'], []);
        $this->mergeGroup('rider_field_category_assignments', ['company_id', 'field_key'], []);
        $this->mergeGroup('employee_field_category_assignments', ['company_id', 'field_key'], []);
        $this->mergeGroup('bike_document_types', ['company_id', 'key'], []);
        $this->mergeGroup('rider_document_types', ['company_id', 'key'], []);
        $this->mergeGroup('employee_document_types', ['company_id', 'key'], []);
        $this->mergeGroup('module_field_category_assignments', ['company_id', 'module_key', 'field_key'], []);
        $this->mergeGroup('module_document_types', ['company_id', 'module_key', 'key'], []);
        $this->mergeGroup('bike_assign_field_assignments', ['company_id', 'field_key'], []);
        $this->mergeGroup('sim_assign_field_assignments', ['company_id', 'field_key'], []);
    }

    /**
     * @param  list<string>  $groupColumns
     * @param  list<array{0: string, 1: string}>  $children
     */
    private function mergeGroup(string $table, array $groupColumns, array $children): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        foreach ($groupColumns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        $nullableKey = in_array('slug', $groupColumns, true) ? 'slug'
            : (in_array('field_key', $groupColumns, true) ? 'field_key' : null);

        $select = implode(', ', array_map(fn ($col) => "`{$col}`", $groupColumns));
        $nullFilter = $nullableKey ? " AND `{$nullableKey}` IS NOT NULL" : '';
        $rows = DB::select(
            "SELECT {$select}, COUNT(*) AS c, GROUP_CONCAT(id ORDER BY id) AS ids
             FROM `{$table}`
             WHERE company_id IS NOT NULL{$nullFilter}
             GROUP BY {$select}
             HAVING c > 1"
        );

        foreach ($rows as $row) {
            $ids = array_map('intval', explode(',', (string) $row->ids));
            if (count($ids) < 2) {
                continue;
            }
            $keepId = array_shift($ids);
            foreach ($children as [$childTable, $fk]) {
                if (! Schema::hasTable($childTable) || ! Schema::hasColumn($childTable, $fk)) {
                    continue;
                }
                DB::table($childTable)->whereIn($fk, $ids)->update([$fk => $keepId]);
            }
            DB::table($table)->whereIn('id', $ids)->delete();
        }
    }

    private function addCompanyUniques(): void
    {
        $this->addUniqueIfMissing('bike_categories', ['company_id', 'slug'], 'bike_categories_company_slug_unique');
        $this->addUniqueIfMissing('rider_categories', ['company_id', 'slug'], 'rider_categories_company_slug_unique');
        $this->addUniqueIfMissing('employee_categories', ['company_id', 'slug'], 'employee_categories_company_slug_unique');
        $this->addUniqueIfMissing('bike_field_category_assignments', ['company_id', 'field_key'], 'bike_field_assignments_company_field_unique');
        $this->addUniqueIfMissing('rider_field_category_assignments', ['company_id', 'field_key'], 'rider_field_assignments_company_field_unique');
        $this->addUniqueIfMissing('employee_field_category_assignments', ['company_id', 'field_key'], 'employee_field_assignments_company_field_unique');
        $this->addUniqueIfMissing('bike_document_types', ['company_id', 'key'], 'bike_document_types_company_key_unique');
        $this->addUniqueIfMissing('rider_document_types', ['company_id', 'key'], 'rider_document_types_company_key_unique');
        $this->addUniqueIfMissing('employee_document_types', ['company_id', 'key'], 'employee_document_types_company_key_unique');
        $this->addUniqueIfMissing('module_setting_categories', ['company_id', 'module_key', 'slug'], 'module_setting_categories_company_module_slug_unique');
        $this->addUniqueIfMissing('module_field_category_assignments', ['company_id', 'module_key', 'field_key'], 'module_field_assignments_company_module_field_unique');
        $this->addUniqueIfMissing('module_document_types', ['company_id', 'module_key', 'key'], 'module_document_types_company_module_key_unique');
        $this->addUniqueIfMissing('bike_assign_field_assignments', ['company_id', 'field_key'], 'bike_assign_fields_company_field_unique');
        $this->addUniqueIfMissing('sim_assign_field_assignments', ['company_id', 'field_key'], 'sim_assign_fields_company_field_unique');
    }

    /**
     * @param  list<string>  $columns
     */
    private function addUniqueIfMissing(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        try {
            Schema::table($table, function ($blueprint) use ($columns, $indexName) {
                $blueprint->unique($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // Duplicate leftover rows would block the unique index; leave without it rather than fail migrate.
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$database, $table, $indexName]
        );

        return $row !== null;
    }
}
