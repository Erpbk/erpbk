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

    public static function visibleFieldKeys(string $moduleKey): array
    {
        return self::visibleAssignments($moduleKey)
            ->pluck('field_key')
            ->values()
            ->all();
    }

    public static function visibleFieldMap(string $moduleKey): array
    {
        return self::visibleAssignments($moduleKey)
            ->mapWithKeys(function ($row) {
                $label = trim((string) ($row->display_label ?: $row->field_label ?: ''));
                return [$row->field_key => ($label !== '' ? $label : $row->field_key)];
            })
            ->all();
    }
}

