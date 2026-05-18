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

        return ModuleFieldCategoryAssignment::query()
            ->where('module_key', $moduleKey)
            ->where('is_visible', true)
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

    public static function visibleFieldMap(string $moduleKey): array
    {
        $base = self::visibleAssignments($moduleKey)
            ->mapWithKeys(function ($row) {
                $label = trim((string) ($row->display_label ?: $row->field_label ?: ''));
                return [$row->field_key => ($label !== '' ? $label : $row->field_key)];
            })
            ->all();

        $schemaKeys = ModuleFieldSource::schemaFieldKeysForModule($moduleKey);
        if ($schemaKeys === []) {
            return $base;
        }

        $allowed = array_fill_keys($schemaKeys, true);

        return array_filter(
            $base,
            static function (string $label, string $key) use ($allowed): bool {
                return isset($allowed[$key]);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Schema column keys that should be validated as required, based on module field settings.
     * When no assignments exist yet for the module, all schema columns are treated as required (legacy behaviour).
     *
     * @return list<string>
     */
    public static function requiredSchemaFieldKeysForValidation(string $moduleKey): array
    {
        $schemaList = ModuleFieldSource::schemaFieldKeysForModule($moduleKey);
        if ($schemaList === [] || !Schema::hasTable('module_field_category_assignments')) {
            return $schemaList;
        }

        if (ModuleFieldCategoryAssignment::query()->where('module_key', $moduleKey)->doesntExist()) {
            return $schemaList;
        }

        $table = (new ModuleFieldCategoryAssignment())->getTable();
        $cols = Schema::getColumnListing($table);
        $hasRequired = in_array('is_required', $cols, true);
        $hasVisible = in_array('is_visible', $cols, true);

        $allowed = array_fill_keys($schemaList, true);
        $select = ['field_key'];
        if ($hasRequired) {
            $select[] = 'is_required';
        }
        if ($hasVisible) {
            $select[] = 'is_visible';
        }

        $required = [];
        foreach (ModuleFieldCategoryAssignment::query()->where('module_key', $moduleKey)->get($select) as $row) {
            $key = (string) $row->field_key;
            if (!isset($allowed[$key])) {
                continue;
            }
            $visible = !$hasVisible || $row->is_visible === null ? true : (bool) $row->is_visible;
            $req = $hasRequired && (bool) $row->is_required;
            if ($visible && $req) {
                $required[] = $key;
            }
        }

        return array_values(array_unique($required));
    }

    /**
     * Whether a schema field is marked required (and visible) in module field settings.
     */
    public static function isSchemaFieldRequired(string $moduleKey, string $fieldKey): bool
    {
        return in_array($fieldKey, self::requiredSchemaFieldKeysForValidation($moduleKey), true);
    }

    /**
     * Apply module field assignment required/visible flags to validation rules (same pattern as riders/employees).
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

        if ($schemaKeys === [] || !Schema::hasTable('module_field_category_assignments')) {
            return $baseRules;
        }

        $assignmentTable = (new ModuleFieldCategoryAssignment())->getTable();
        $hasRequiredColumn = Schema::hasColumn($assignmentTable, 'is_required');
        $hasVisibleColumn = Schema::hasColumn($assignmentTable, 'is_visible');

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

        $assignmentColumns = ['field_key'];
        if ($hasRequiredColumn) {
            $assignmentColumns[] = 'is_required';
        }
        if ($hasVisibleColumn) {
            $assignmentColumns[] = 'is_visible';
        }

        $assignments = ModuleFieldCategoryAssignment::query()
            ->where('module_key', $moduleKey)
            ->get($assignmentColumns)
            ->keyBy('field_key');

        $rules = $baseRules;
        $hasAnyAssignment = $assignments->isNotEmpty();

        foreach ($schemaKeys as $fieldKey) {
            if (!isset($columns[$fieldKey])) {
                continue;
            }

            $assignment = $assignments->get($fieldKey);
            $isVisible = !$hasVisibleColumn || !$assignment || $assignment->is_visible === null
                ? true
                : (bool) $assignment->is_visible;
            $isRequired = !$hasAnyAssignment
                ? true
                : (($assignment && $hasRequiredColumn) ? (bool) $assignment->is_required : false);

            $baseRule = $rules[$fieldKey] ?? 'nullable';
            $rules[$fieldKey] = $normalizePresenceRule($baseRule, $isVisible && $isRequired);
        }

        return $rules;
    }
}

