<?php

namespace App\Services\Employee;

use App\Models\EmployeeCategory;
use App\Models\EmployeeCustomField;
use App\Models\EmployeeFieldCategoryAssignment;
use App\Models\Settings;
use App\Services\Settings\FixedFieldCategoryAssignmentSync;
use Illuminate\Support\Facades\Schema;

final class EmployeeDefaultCategoryService
{
    public const DEFAULT_SLUG = 'general';

    public const DEFAULT_LABEL = 'General';

    public const FALLBACK_SLUG = 'other';

    public function ensure(): EmployeeCategory
    {
        $category = EmployeeCategory::withoutGlobalScopes(['company'])
            ->where('slug', self::DEFAULT_SLUG)
            ->whereNull('company_id')
            ->first();

        if ($category) {
            if (trim((string) $category->label) === '') {
                $category->label = $this->defaultLabel();
                $category->save();
            }

            return $category;
        }

        return EmployeeCategory::withoutGlobalScopes(['company'])->create([
            'slug' => self::DEFAULT_SLUG,
            'label' => $this->defaultLabel(),
            'display_order' => 0,
            'is_system' => true,
            'company_id' => null,
        ]);
    }

    /**
     * @return array<string, int>
     */
    public function categoryIdsBySlug(): array
    {
        return EmployeeCategory::query()
            ->whereNotNull('slug')
            ->pluck('id', 'slug')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  list<string>  $fieldKeys
     */
    public function syncFixedFieldAssignments(array $fieldKeys): void
    {
        $category = $this->ensure();
        $categoryId = (int) $category->id;
        $order = (int) EmployeeFieldCategoryAssignment::where('category_id', $categoryId)->max('display_order');

        foreach ($fieldKeys as $fieldKey) {
            if (EmployeeFieldCategoryAssignment::query()->where('field_key', $fieldKey)->exists()) {
                continue;
            }

            $order++;
            EmployeeFieldCategoryAssignment::query()->create([
                'field_key' => $fieldKey,
                'category_id' => $categoryId,
                'display_order' => $order,
                'is_visible' => true,
                'is_required' => false,
            ]);
        }
    }

    public function relocateVisibleFieldsToDefaultCategory(): void
    {
        $category = $this->ensure();
        $categoryId = (int) $category->id;

        if (Schema::hasTable('employee_field_category_assignments')) {
            $allowedKeys = $this->discoverAssignableFieldKeys();
            if ($allowedKeys !== []) {
                EmployeeFieldCategoryAssignment::query()
                    ->whereIn('field_key', $allowedKeys)
                    ->where(function ($query) {
                        $query->whereNull('is_visible')->orWhere('is_visible', true);
                    })
                    ->update(['category_id' => $categoryId]);
            }
        }

        if (Schema::hasTable('employee_custom_fields')) {
            EmployeeCustomField::query()
                ->where(function ($query) {
                    $query->where('is_visible', true)->orWhereNull('is_visible');
                })
                ->update(['category_id' => $categoryId]);
        }
    }

    public function assignUnassignedCustomFields(?EmployeeCategory $category = null): void
    {
        if (! Schema::hasTable('employee_custom_fields')) {
            return;
        }

        $categoryId = (int) ($category ?? $this->ensure())->id;

        FixedFieldCategoryAssignmentSync::assignCustomFieldsWithoutCategory(
            EmployeeCustomField::class,
            $categoryId,
        );
    }

    public function isDefaultCategory(EmployeeCategory $category): bool
    {
        return (string) $category->slug === self::DEFAULT_SLUG
            && (bool) $category->is_system
            && $category->company_id === null;
    }

    public function bootstrap(): void
    {
        $defaultCategory = $this->ensure();
        $this->syncFixedFieldAssignments($this->discoverAssignableFieldKeys());
        $this->assignUnassignedCustomFields($defaultCategory);
    }

    /**
     * @return list<string>
     */
    public function discoverAssignableFieldKeys(): array
    {
        if (! Schema::hasTable('employees')) {
            return [];
        }

        $keys = EmployeeCustomField::allFixedFieldKeys();
        $employeeColumns = array_flip(Schema::getColumnListing('employees'));

        return array_values(array_filter($keys, fn (string $fieldKey) => isset($employeeColumns[$fieldKey])));
    }

    protected function defaultLabel(): string
    {
        $menuLabel = Settings::getMenuLabel('employee_settings');
        if ($menuLabel !== '' && strcasecmp($menuLabel, 'employee_settings') !== 0) {
            return $menuLabel;
        }

        return self::DEFAULT_LABEL;
    }
}
