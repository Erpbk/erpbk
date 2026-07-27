<?php

namespace App\Support;

use App\Models\ModuleFieldCategoryAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ModuleFieldSettings
{
    public static function visibleAssignments(string $moduleKey): Collection
    {
        if (!Schema::hasTable('module_field_category_assignments')) {
            return collect();
        }

        // Form inclusion is controlled per role via Field Permissions (visible),
        // not via Module Settings is_visible.
        return ModuleFieldCategoryAssignment::query()
            ->where('module_key', $moduleKey)
            ->get();
    }

    /**
     * Only keys that are real columns on the module's source table (predefined fixed fields).
     *
     * @return list<string>
     */
    public static function visibleFixedFieldKeys(string $moduleKey): array
    {
        $schemaKeys = ModuleFieldSource::schemaFieldKeysForModule($moduleKey);
        $assignmentKeys = self::visibleAssignments($moduleKey)->pluck('field_key')->values()->all();

        if ($schemaKeys !== []) {
            $allowed = array_fill_keys($schemaKeys, true);

            return array_values(array_filter($assignmentKeys, fn ($key) => isset($allowed[$key])));
        }

        return $assignmentKeys;
    }

    public static function visibleFieldKeys(string $moduleKey): array
    {
        return self::visibleFixedFieldKeys($moduleKey);
    }

    public static function visibleFieldMap(string $moduleKey, ?string $attendanceRefType = null): array
    {
        $base = self::visibleAssignments($moduleKey)
            ->mapWithKeys(function ($row) {
                $label = trim((string) ($row->display_label ?: $row->field_label ?: ''));
                return [$row->field_key => ($label !== '' ? $label : $row->field_key)];
            })
            ->all();

        $schemaKeys = ModuleFieldSource::schemaFieldKeysForModule($moduleKey);
        if ($schemaKeys === []) {
            $filtered = $base;
        } else {
            $allowed = array_fill_keys($schemaKeys, true);

            $filtered = array_filter(
                $base,
                static function (string $label, string $key) use ($allowed): bool {
                    return isset($allowed[$key]);
                },
                ARRAY_FILTER_USE_BOTH
            );
        }

        if ($moduleKey === AttendanceFieldScope::MODULE_KEY && $attendanceRefType !== null) {
            return AttendanceFieldScope::filterFieldMapForRefType($filtered, $attendanceRefType);
        }

        return $filtered;
    }

    /**
     * Schema column keys that should be validated as required for the current user,
     * based on Role Field Permissions (required + visible + editable).
     *
     * @return list<string>
     */
    public static function requiredSchemaFieldKeysForValidation(string $moduleKey): array
    {
        $schemaList = ModuleFieldSource::schemaFieldKeysForModule($moduleKey);
        if ($schemaList === []) {
            return [];
        }

        $entityKey = self::entityKeyForModule($moduleKey);
        $required = [];
        foreach ($schemaList as $key) {
            if (RoleFieldAccess::isRequired($entityKey, $key) && RoleFieldAccess::canEdit($entityKey, $key)) {
                $required[] = $key;
            }
        }

        return array_values(array_unique($required));
    }

    /**
     * Whether a schema field is required for the current user via Field Permissions.
     */
    public static function isSchemaFieldRequired(string $moduleKey, string $fieldKey): bool
    {
        return in_array($fieldKey, self::requiredSchemaFieldKeysForValidation($moduleKey), true);
    }

    /**
     * Apply role Field Permissions required flags to validation rules.
     * Non-editable fields are never required (user cannot fill them).
     *
     * @param  array<string, string|array<int, mixed>>  $baseRules
     * @param  array{fields?: list<string>, ignore_id?: int|null}  $options
     * @return array<string, string|array<int, mixed>>
     */
    public static function validationRulesForModule(string $moduleKey, array $baseRules, array $options = []): array
    {
        $moduleKey = str_replace('-', '_', strtolower(trim($moduleKey)));
        $table = ModuleFieldSource::resolveSourceTable($moduleKey);
        if (!$table || !Schema::hasTable($table)) {
            return $baseRules;
        }

        $columns = array_flip(Schema::getColumnListing($table));
        $schemaKeys = ModuleFieldSource::schemaFieldKeysForModule($moduleKey);
        if (isset($options['fields']) && is_array($options['fields'])) {
            $allowed = array_fill_keys($options['fields'], true);
            $schemaKeys = array_values(array_filter($schemaKeys, fn ($key) => isset($allowed[$key])));
        }

        if ($schemaKeys === []) {
            return $baseRules;
        }

        $normalizePresenceRule = function ($rule, bool $required) {
            if (is_array($rule)) {
                $tokens = array_values(array_filter($rule, function ($item) {
                    return !is_string($item) || ($item !== 'required' && $item !== 'nullable');
                }));
                array_unshift($tokens, $required ? 'required' : 'nullable');

                return $tokens;
            }

            $tokens = array_values(array_filter(explode('|', (string) $rule), function ($item) {
                return $item !== '' && $item !== 'required' && $item !== 'nullable';
            }));
            array_unshift($tokens, $required ? 'required' : 'nullable');

            return implode('|', $tokens);
        };

        $entityKey = self::entityKeyForModule($moduleKey);
        $rules = $baseRules;

        foreach ($schemaKeys as $fieldKey) {
            if (!isset($columns[$fieldKey])) {
                continue;
            }

            $isRequired = RoleFieldAccess::isRequired($entityKey, $fieldKey)
                && RoleFieldAccess::canEdit($entityKey, $fieldKey);

            $baseRule = $rules[$fieldKey] ?? 'nullable';
            $rules[$fieldKey] = $normalizePresenceRule($baseRule, $isRequired);
        }

        return $rules;
    }

    /**
     * Map module_key (settings) to RoleFieldAccess entity slug.
     */
    protected static function entityKeyForModule(string $moduleKey): string
    {
        $moduleKey = str_replace('-', '_', strtolower(trim($moduleKey)));

        return match ($moduleKey) {
            'riders' => 'rider',
            'bikes' => 'bike',
            'vendors' => 'vendor',
            'customers' => 'customer',
            'cash_banks', 'banks' => 'bank',
            'sims' => 'sim',
            default => $moduleKey,
        };
    }
}

