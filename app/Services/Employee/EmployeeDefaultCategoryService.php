<?php

namespace App\Services\Employee;

use App\Models\EmployeeCategory;
use App\Models\EmployeeCustomField;
use App\Models\EmployeeFieldCategoryAssignment;
use App\Models\Settings;
use App\Services\Settings\FixedFieldCategoryAssignmentSync;
use App\Services\Settings\PerCompanyDefaultCategory;
use Illuminate\Support\Facades\Schema;

final class EmployeeDefaultCategoryService
{
    public const DEFAULT_SLUG = 'general';

    public const DEFAULT_LABEL = 'General';

    public const FALLBACK_SLUG = 'other';

    public function ensure(?int $companyId = null): EmployeeCategory
    {
        /** @var EmployeeCategory $category */
        $category = PerCompanyDefaultCategory::ensure(
            EmployeeCategory::class,
            self::DEFAULT_SLUG,
            fn () => $this->defaultLabel(),
            $companyId,
        );

        return $category;
    }

    /**
     * @return array<string, int>
     */
    public function categoryIdsBySlug(?int $companyId = null): array
    {
        $query = EmployeeCategory::query()->whereNotNull('slug');
        if ($companyId !== null) {
            $query = EmployeeCategory::withoutGlobalScopes(['company'])
                ->where('company_id', $companyId)
                ->whereNotNull('slug');
        }

        return $query
            ->pluck('id', 'slug')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  list<string>  $fieldKeys
     */
    public function syncFixedFieldAssignments(array $fieldKeys, ?int $companyId = null): void
    {
        $category = $this->ensure($companyId);
        $companyId = (int) $category->company_id;
        $categoryId = (int) $category->id;
        $order = (int) EmployeeFieldCategoryAssignment::withoutGlobalScopes(['company'])
            ->where('category_id', $categoryId)
            ->max('display_order');

        foreach ($fieldKeys as $fieldKey) {
            $exists = EmployeeFieldCategoryAssignment::withoutGlobalScopes(['company'])
                ->where('field_key', $fieldKey)
                ->where('company_id', $companyId)
                ->exists();
            if ($exists) {
                continue;
            }

            $order++;
            EmployeeFieldCategoryAssignment::withoutGlobalScopes(['company'])->create([
                'field_key' => $fieldKey,
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'display_order' => $order,
                'is_visible' => true,
                'is_required' => false,
            ]);
        }
    }

    public function relocateVisibleFieldsToDefaultCategory(?int $companyId = null): void
    {
        $category = $this->ensure($companyId);
        $companyId = (int) $category->company_id;
        $categoryId = (int) $category->id;

        if (Schema::hasTable('employee_field_category_assignments')) {
            $allowedKeys = $this->discoverAssignableFieldKeys();
            if ($allowedKeys !== []) {
                EmployeeFieldCategoryAssignment::withoutGlobalScopes(['company'])
                    ->where('company_id', $companyId)
                    ->whereIn('field_key', $allowedKeys)
                    ->where(function ($query) {
                        $query->whereNull('is_visible')->orWhere('is_visible', true);
                    })
                    ->update(['category_id' => $categoryId]);
            }
        }

        if (Schema::hasTable('employee_custom_fields')) {
            EmployeeCustomField::withoutGlobalScopes(['company'])
                ->where('company_id', $companyId)
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

        $category = $category ?? $this->ensure();
        $categoryId = (int) $category->id;
        $companyId = $category->company_id !== null ? (int) $category->company_id : null;

        FixedFieldCategoryAssignmentSync::assignCustomFieldsWithoutCategory(
            EmployeeCustomField::class,
            $categoryId,
            $companyId,
        );
    }

    public function isDefaultCategory(EmployeeCategory $category): bool
    {
        return PerCompanyDefaultCategory::isDefault($category, self::DEFAULT_SLUG);
    }

    public function bootstrap(?int $companyId = null): void
    {
        foreach (PerCompanyDefaultCategory::bootstrapCompanyIds($companyId) as $id) {
            $defaultCategory = $this->ensure($id);
            $this->syncFixedFieldAssignments($this->discoverAssignableFieldKeys(), $id);
            $this->assignUnassignedCustomFields($defaultCategory);
        }
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
