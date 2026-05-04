<?php

namespace App\Support;

use App\Models\ModuleFieldCategoryAssignment;
use App\Models\ModuleSettingCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Cash & Banks (module_key cash_banks): form column order, labels, and category grouping.
 * All user-editable schema columns are always included; visibility flags are not used to hide fields.
 */
class BankFormLayout
{
    public const MODULE_KEY = 'cash_banks';

    /**
     * Columns not shown on create/edit (system / auto-managed).
     *
     * @return list<string>
     */
    public static function formExcludedKeys(): array
    {
        return ['account_id', 'company_id'];
    }

    /**
     * Bank table columns that should appear on forms and list/detail views.
     *
     * @return list<string>
     */
    public static function userFacingFieldKeys(): array
    {
        $keys = ModuleFieldSource::schemaFieldKeysForModule(self::MODULE_KEY);
        $skip = array_flip(self::formExcludedKeys());

        return array_values(array_filter($keys, fn ($k) => !isset($skip[$k])));
    }

    protected static function scopedCategories(): Collection
    {
        if (!Schema::hasTable('module_setting_categories')) {
            return collect();
        }

        $companyId = optional(auth()->user())->company_id;

        return ModuleSettingCategory::query()
            ->where('module_key', self::MODULE_KEY)
            ->where(function ($query) use ($companyId) {
                $query->whereNull('company_id');
                if ($companyId) {
                    $query->orWhere('company_id', $companyId);
                }
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Human-readable label for a column, preferring Field Settings assignments.
     */
    public static function labelForFieldKey(string $fieldKey): string
    {
        if (!Schema::hasTable('module_field_category_assignments')) {
            return Str::title(str_replace('_', ' ', $fieldKey));
        }

        $row = ModuleFieldCategoryAssignment::query()
            ->where('module_key', self::MODULE_KEY)
            ->where('field_key', $fieldKey)
            ->first();

        if ($row) {
            $label = trim((string) ($row->display_label ?: $row->field_label ?: ''));
            if ($label !== '') {
                return $label;
            }
        }

        return Str::title(str_replace('_', ' ', $fieldKey));
    }

    /**
     * @return array{useCategories: bool, groups: list<array{title: string|null, fields: list<array{key: string, label: string}>}>}
     */
    public static function formGroups(): array
    {
        $fieldKeys = self::userFacingFieldKeys();
        $categories = self::scopedCategories();

        if (!Schema::hasTable('module_field_category_assignments')) {
            $fields = array_map(fn ($key) => ['key' => $key, 'label' => self::labelForFieldKey($key)], $fieldKeys);
            usort($fields, fn ($a, $b) => strcmp($a['key'], $b['key']));

            return [
                'useCategories' => false,
                'groups' => [['title' => null, 'fields' => $fields]],
            ];
        }

        $assignments = ModuleFieldCategoryAssignment::query()
            ->where('module_key', self::MODULE_KEY)
            ->whereIn('field_key', $fieldKeys)
            ->get()
            ->keyBy('field_key');

        $categoriesById = $categories->keyBy('id');

        if ($categories->isEmpty()) {
            $fields = [];
            foreach ($fieldKeys as $key) {
                $a = $assignments->get($key);
                $order = $a ? (int) $a->display_order : 9999;
                $fields[] = ['key' => $key, 'label' => self::labelForFieldKey($key), 'order' => $order];
            }
            usort($fields, function ($a, $b) {
                if ($a['order'] !== $b['order']) {
                    return $a['order'] <=> $b['order'];
                }

                return strcmp($a['key'], $b['key']);
            });
            $fields = array_map(fn ($f) => ['key' => $f['key'], 'label' => $f['label']], $fields);

            return [
                'useCategories' => false,
                'groups' => [['title' => null, 'fields' => $fields]],
            ];
        }

        $byCategory = [];
        $unassigned = [];

        foreach ($fieldKeys as $key) {
            $a = $assignments->get($key);
            $catId = $a && $a->category_id ? (int) $a->category_id : null;
            $order = $a ? (int) $a->display_order : 9999;
            $field = ['key' => $key, 'label' => self::labelForFieldKey($key), 'order' => $order];

            if ($catId && $categoriesById->has($catId)) {
                $byCategory[$catId][] = $field;
            } else {
                $unassigned[] = $field;
            }
        }

        $groups = [];

        foreach ($categories as $cat) {
            $bucket = $byCategory[$cat->id] ?? [];
            usort($bucket, function ($a, $b) {
                if ($a['order'] !== $b['order']) {
                    return $a['order'] <=> $b['order'];
                }

                return strcmp($a['key'], $b['key']);
            });
            $fields = array_map(fn ($f) => ['key' => $f['key'], 'label' => $f['label']], $bucket);
            if ($fields !== []) {
                $groups[] = ['title' => $cat->label, 'fields' => $fields];
            }
        }

        if ($unassigned !== []) {
            usort($unassigned, function ($a, $b) {
                if ($a['order'] !== $b['order']) {
                    return $a['order'] <=> $b['order'];
                }

                return strcmp($a['key'], $b['key']);
            });
            $groups[] = [
                'title' => __('Other'),
                'fields' => array_map(fn ($f) => ['key' => $f['key'], 'label' => $f['label']], $unassigned),
            ];
        }

        return [
            'useCategories' => true,
            'groups' => $groups,
        ];
    }
}
