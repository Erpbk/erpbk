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
}

