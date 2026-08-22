<?php

namespace App\Services\Bike;

use App\Models\BikeCategory;
use App\Models\BikeCustomField;
use App\Models\BikeFieldCategoryAssignment;
use App\Models\Settings;
use App\Services\Settings\FixedFieldCategoryAssignmentSync;
use App\Services\Settings\PerCompanyDefaultCategory;
use Illuminate\Support\Facades\Schema;

final class BikeDefaultCategoryService
{
    public const DEFAULT_SLUG = 'general';

    public const DEFAULT_LABEL = 'General';

    public function ensure(?int $companyId = null): BikeCategory
    {
        /** @var BikeCategory $category */
        $category = PerCompanyDefaultCategory::ensure(
            BikeCategory::class,
            self::DEFAULT_SLUG,
            fn () => $this->defaultLabel(),
            $companyId,
        );

        return $category;
    }

    /**
     * @param  list<string>  $fieldKeys
     */
    public function syncFixedFieldAssignments(array $fieldKeys, ?int $companyId = null): void
    {
        $category = $this->ensure($companyId);
        $companyId = (int) $category->company_id;
        $categoryId = (int) $category->id;
        $order = (int) BikeFieldCategoryAssignment::withoutGlobalScopes(['company'])
            ->where('category_id', $categoryId)
            ->max('display_order');

        foreach ($fieldKeys as $fieldKey) {
            $exists = BikeFieldCategoryAssignment::withoutGlobalScopes(['company'])
                ->where('field_key', $fieldKey)
                ->where('company_id', $companyId)
                ->exists();
            if ($exists) {
                continue;
            }

            $order++;
            $payload = [
                'field_key' => $fieldKey,
                'category_id' => $categoryId,
                'company_id' => $companyId,
                'display_order' => $order,
                'display_label' => null,
                'input_type' => null,
                'input_config' => null,
                'is_visible' => true,
                'is_required' => true,
            ];

            BikeFieldCategoryAssignment::withoutGlobalScopes(['company'])->create($payload);
        }
    }

    public function relocateVisibleFieldsToDefaultCategory(?int $companyId = null): void
    {
        $category = $this->ensure($companyId);
        $companyId = (int) $category->company_id;
        $categoryId = (int) $category->id;

        if (Schema::hasTable('bike_field_category_assignments')) {
            $allowedKeys = $this->discoverAssignableFieldKeys();
            if ($allowedKeys !== []) {
                BikeFieldCategoryAssignment::withoutGlobalScopes(['company'])
                    ->where('company_id', $companyId)
                    ->whereIn('field_key', $allowedKeys)
                    ->where(function ($query) {
                        $query->whereNull('is_visible')->orWhere('is_visible', true);
                    })
                    ->update(['category_id' => $categoryId]);
            }
        }

        if (Schema::hasTable('bike_custom_fields')) {
            BikeCustomField::withoutGlobalScopes(['company'])
                ->where('company_id', $companyId)
                ->where(function ($query) {
                    $query->where('is_visible', true)->orWhereNull('is_visible');
                })
                ->update(['category_id' => $categoryId]);
        }
    }

    public function assignUnassignedCustomFields(?BikeCategory $category = null): void
    {
        if (! Schema::hasTable('bike_custom_fields')) {
            return;
        }

        $category = $category ?? $this->ensure();
        $categoryId = (int) $category->id;
        $companyId = $category->company_id !== null ? (int) $category->company_id : null;

        FixedFieldCategoryAssignmentSync::assignCustomFieldsWithoutCategory(
            BikeCustomField::class,
            $categoryId,
            $companyId,
        );
    }

    public function isDefaultCategory(BikeCategory $category): bool
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
        return BikeCustomField::allFixedFieldKeys();
    }

    protected function defaultLabel(): string
    {
        $menuLabel = Settings::getMenuLabel('bike_settings');
        if ($menuLabel !== '' && strcasecmp($menuLabel, 'bike_settings') !== 0) {
            return $menuLabel;
        }

        return self::DEFAULT_LABEL;
    }
}
