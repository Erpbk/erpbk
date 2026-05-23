<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Resolves backing SQL tables for ERP module keys and which columns represent real schema fields.
 */
class ModuleFieldSource
{
    /**
     * Columns excluded from "database field" lists (audit/meta).
     */
    public static function defaultExcludedFields(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'created_by',
            'updated_by',
            'deleted_by',
        ];
    }

    /**
     * Extra columns to exclude for a given table (JSON blobs, etc.).
     */
    public static function tableSpecificExcludedColumns(string $table): array
    {
        return match ($table) {
            'bikes' => ['custom_field_values'],
            'employees' => [
                'personal_email',
                'personal_contact',
                'emergency_contact',
                'status',
                'profile_image',
                'account_id',
                'custom_field_values',
            ],
            default => [],
        };
    }

    public static function defaultExcludedFieldsForModule(string $module): array
    {
        $table = self::resolveSourceTable($module);

        return array_values(array_unique(array_merge(
            self::defaultExcludedFields(),
            $table ? self::tableSpecificExcludedColumns($table) : []
        )));
    }

    public static function sourceTableMap(): array
    {
        return [
            'bike_list' => 'bikes',
            'cash_banks' => 'banks',
            'riders_list' => 'riders',
            'employees' => 'employees',
            'customers' => 'customers',
            'customer_invoices' => 'customer_invoices',
            'vendors' => 'vendors',
            'leads' => 'leads',
            'recruiters' => 'recruiters',
            'inventory' => 'inventory',
            'attendance' => 'attendance',
            'attendance_records' => 'attendance',
            'documents' => 'files',
            'sims' => 'sims',
            'fuel_cards' => 'fuel_cards',
            'rta_fines' => 'rta_fines',
            'rta_fines_unpaid' => 'rta_fines',
            'rta_fines_paid' => 'rta_fines',
            'rta_saliks' => 'saliks',
            'garages' => 'garages',
            'suppliers' => 'suppliers',
            'leasing_companies' => 'leasing_companies',
            'bike_rent_companies' => 'bike_rent_companies',
            'expenses' => 'expenses',
            'items_list' => 'items',
            'garage_items' => 'garage_items',
            'vouchers' => 'vouchers',
            'accounts' => 'accounts',
            'bike_registration' => 'bike_registrations',
        ];
    }

    public static function resolveSourceTable(string $module): ?string
    {
        $module = str_replace('-', '_', strtolower(trim($module)));
        $map = self::sourceTableMap();
        if (isset($map[$module]) && Schema::hasTable($map[$module])) {
            return $map[$module];
        }

        $normalized = $module;
        $base = preg_replace('/(_list|_settings|_overview|_report|_reports)$/', '', $normalized) ?: $normalized;
        $candidates = array_values(array_unique([
            $normalized,
            $base,
            Str::snake(Str::pluralStudly(Str::studly($base))),
            Str::snake(Str::pluralStudly(Str::studly($normalized))),
            Str::plural($base),
            Str::singular($base),
        ]));

        foreach ($candidates as $candidate) {
            if (Schema::hasTable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Column names on the module's source table that correspond to real stored fields (for Field Settings / validation).
     *
     * @return list<string>
     */
    public static function schemaFieldKeysForModule(string $module): array
    {
        $table = self::resolveSourceTable($module);
        if (!$table || !Schema::hasTable($table)) {
            return [];
        }

        $exclude = self::defaultExcludedFieldsForModule($module);

        return array_values(array_filter(
            Schema::getColumnListing($table),
            fn ($col) => !in_array($col, $exclude, true)
        ));
    }

    public static function isSchemaFieldKey(string $module, string $fieldKey): bool
    {
        return in_array($fieldKey, self::schemaFieldKeysForModule($module), true);
    }

    /**
     * Default assignment label when syncing DB columns into module_field_category_assignments.
     */
    public static function defaultAssignmentFieldLabel(string $column): string
    {
        return match ($column) {
            'branch_id' => 'Branch',
            default => ucwords(str_replace('_', ' ', $column)),
        };
    }

    /**
     * Label for fixed field keys in module settings / forms (handles hyphens in keys).
     */
    public static function humanizeFieldKey(string $key): string
    {
        return match ($key) {
            'branch_id' => 'Branch',
            default => ucwords(str_replace(['_', '-'], ' ', $key)),
        };
    }

    /**
     * Merge module-specific fixed-field specs with sensible defaults (e.g. branch_id → branch dropdown).
     * Keys in {@see $explicit} overwrite defaults.
     *
     * @param  array<string, mixed>|null  $explicit
     * @return array<string, mixed>
     */
    public static function mergeFixedFieldSpec(string $fieldKey, ?array $explicit): array
    {
        $base = match ($fieldKey) {
            'branch_id' => ['type' => 'select', 'dropdown' => 'branch'],
            default => ['type' => 'text'],
        };

        return array_merge($base, $explicit ?? []);
    }
}
