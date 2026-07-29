<?php

namespace App\Support;

use App\Models\AccountCustomField;
use App\Models\BikeCustomField;
use App\Models\ChequeCustomField;
use App\Models\EmployeeCustomField;
use App\Models\ModuleCustomField;
use App\Models\RiderCustomField;
use App\Models\VoucherCustomField;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * Resolves the field list for a permission "module" (parent permission), combining:
 *   1. Default database columns of the module's backing table (via ModuleFieldSource), and
 *   2. Custom fields created from the Settings → Custom Fields screens (per-entity tables
 *      and the generic module_custom_fields table).
 *
 * The list is fully dynamic: newly created custom fields appear automatically with no code changes.
 */
class RoleModuleFieldResolver
{
    /**
     * Descriptor per permission slug.
     * - table_module: key understood by ModuleFieldSource to resolve the SQL table for default columns.
     * - custom: FQCN of the per-entity custom field model (optional).
     * - exclude: extra column names to hide from the default field list.
     *
     * @return array<string, array{table_module?: string, custom?: class-string, exclude?: list<string>}>
     */
    public static function map(): array
    {
        return [
            'rider' => ['table_module' => 'riders_list', 'custom' => RiderCustomField::class, 'exclude' => RiderCustomField::excludedFromFieldSettings()],
            'employees' => ['table_module' => 'employees', 'custom' => EmployeeCustomField::class, 'exclude' => EmployeeCustomField::excludedFromFieldSettings()],
            'bike' => ['table_module' => 'bike_list', 'custom' => BikeCustomField::class, 'exclude' => BikeCustomField::removedBikeColumns()],
            'account' => ['table_module' => 'accounts', 'custom' => AccountCustomField::class],
            'voucher' => ['table_module' => 'vouchers', 'custom' => VoucherCustomField::class],
            'cheques' => ['table_module' => 'cheques', 'custom' => ChequeCustomField::class],
            'customer' => ['table_module' => 'customers'],
            'vendor' => ['table_module' => 'vendors'],
            'leads' => ['table_module' => 'leads'],
            'recruiter' => ['table_module' => 'recruiters'],
            'supplier' => ['table_module' => 'suppliers'],
            'garage' => ['table_module' => 'garages'],
            'item' => ['table_module' => 'items_list'],
            'inventory' => ['table_module' => 'inventory'],
            'sim' => ['table_module' => 'sims'],
            'fuel' => ['table_module' => 'fuel_cards'],
            'bank' => ['table_module' => 'cash_banks'],
            'loan' => ['table_module' => 'loans'],
            // Expense vouchers are stored in the vouchers table (voucher_type = EXP).
            'expenses' => ['table_module' => 'vouchers'],
            'bike_registration' => ['table_module' => 'bike_registration'],
            'leasing' => ['table_module' => 'leasing_companies'],
            // Fixed assets module is backed by the fixed_assets table.
            'assets' => ['table_module' => 'fixed_assets'],
            'asset' => ['table_module' => 'fixed_assets'],
        ];
    }

    /**
     * Map a top-level module display name (lowercased) to the entity slug used by {@see map()}.
     *
     * @return array<string, string>
     */
    protected static function nameToEntitySlug(): array
    {
        return [
            'riders' => 'rider',
            'rider' => 'rider',
            'employees' => 'employees',
            'bikes' => 'bike',
            'accounts' => 'account',
            'vouchers' => 'voucher',
            'cash & banks' => 'bank',
            'customers' => 'customer',
            'vendors' => 'vendor',
            'suppliers' => 'supplier',
            'garages' => 'garage',
            'items' => 'item',
            'leads' => 'leads',
            'recruiters' => 'recruiter',
            'sims' => 'sim',
            'fuel cards' => 'fuel',
            'loans' => 'loan',
            'expenses' => 'expenses',
            'leasing companies' => 'leasing',
            'assets' => 'assets',
        ];
    }

    /**
     * Resolve the field-entity slug for a given module permission.
     */
    public static function slugForModule(Permission $module): ?string
    {
        $byName = self::nameToEntitySlug();
        $key = mb_strtolower(trim((string) $module->name));
        if (isset($byName[$key])) {
            return $byName[$key];
        }

        // Fallback: normalized module name (e.g. "Fuel Cards" => "fuel_cards").
        $slug = Str::slug($module->name, '_');
        if ($slug !== '' && isset(self::map()[$slug])) {
            return $slug;
        }

        return $slug ?: null;
    }

    /**
     * Whether a module has any manageable fields.
     */
    public static function moduleHasFields(Permission $module): bool
    {
        return self::fieldsForModule($module) !== [];
    }

    /**
     * Resolve the full field list for a module.
     *
     * @return list<array{name: string, label: string, type: string, source: string}>
     */
    public static function fieldsForModule(Permission $module): array
    {
        $slug = self::slugForModule($module);
        if ($slug === null) {
            return [];
        }

        $descriptor = self::map()[$slug] ?? [];
        $tableModule = $descriptor['table_module'] ?? $slug;

        $fields = [];
        $seen = [];

        // 1) Default database columns.
        $exclude = array_flip(array_map('strval', $descriptor['exclude'] ?? []));
        foreach (ModuleFieldSource::schemaFieldKeysForModule($tableModule) as $column) {
            if (isset($exclude[$column]) || isset($seen[$column])) {
                continue;
            }
            $seen[$column] = true;
            $fields[] = [
                'name' => $column,
                'label' => ModuleFieldSource::defaultAssignmentFieldLabel($column),
                'type' => 'default',
                'source' => 'database',
            ];
        }

        // 2) Custom fields from the per-entity custom field model.
        if (!empty($descriptor['custom']) && class_exists($descriptor['custom'])) {
            /** @var class-string $customClass */
            $customClass = $descriptor['custom'];
            foreach (self::customFieldsFromModel($customClass) as $cf) {
                $key = 'cf_' . $cf['id'];
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $fields[] = [
                    'name' => $key,
                    'label' => $cf['label'],
                    'type' => 'custom',
                    'source' => 'custom_field',
                ];
            }
        }

        // 3) Custom fields stored in the generic module_custom_fields table (keyed by module_key).
        if (Schema::hasTable('module_custom_fields')) {
            $moduleKeys = self::genericModuleKeys($tableModule, $slug);
            $generic = ModuleCustomField::query()
                ->whereIn('module_key', $moduleKeys)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get(['id', 'label']);
            foreach ($generic as $cf) {
                $key = 'cf_' . $cf->id;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $fields[] = [
                    'name' => $key,
                    'label' => (string) $cf->label,
                    'type' => 'custom',
                    'source' => 'custom_field',
                ];
            }
        }

        return $fields;
    }

    /**
     * module_custom_fields.module_key holds the ERP module key used by the Settings screens
     * ("items", "sims"), which is not always the permission slug or the ModuleFieldSource key
     * ("item", "items_list"). Match on every equivalent spelling so custom fields created there
     * are offered in the Field Permissions panel instead of silently staying unmanageable.
     *
     * @return list<string>
     */
    protected static function genericModuleKeys(string $tableModule, string $slug): array
    {
        $keys = [$tableModule, $slug, ModuleFieldSource::resolveSourceTable($tableModule)];

        return array_values(array_unique(array_filter(array_map(
            fn ($key) => $key === null ? null : ErpModuleRegistry::settingsFieldsModuleKey((string) $key),
            $keys
        ))));
    }

    /**
     * @param  class-string  $customClass
     * @return list<array{id: int, label: string}>
     */
    protected static function customFieldsFromModel(string $customClass): array
    {
        try {
            $query = $customClass::query();
            if (Schema::hasColumn((new $customClass)->getTable(), 'display_order')) {
                $query->orderBy('display_order');
            }
            $records = $query->orderBy('id')->get(['id', 'label']);
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($records as $record) {
            $label = trim((string) $record->label);
            if ($label === '') {
                continue;
            }
            $out[] = ['id' => (int) $record->id, 'label' => $label];
        }

        return $out;
    }
}
