<?php

namespace App\Services\Bike;

use App\Models\BikeCategory;
use App\Models\BikeCustomField;
use App\Models\BikeFieldCategoryAssignment;
use App\Models\Settings;
use App\Services\Settings\FixedFieldCategoryAssignmentSync;
use Illuminate\Support\Facades\Schema;

final class BikeDefaultCategoryService
{
    public const DEFAULT_SLUG = 'general';

    public const DEFAULT_LABEL = 'General';

    public function ensure(): BikeCategory
    {
        $category = BikeCategory::withoutGlobalScopes(['company'])
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

        return BikeCategory::withoutGlobalScopes(['company'])->create([
            'slug' => self::DEFAULT_SLUG,
            'label' => $this->defaultLabel(),
            'display_order' => 0,
            'is_system' => true,
            'company_id' => null,
        ]);
    }

    /**
     * @param  list<string>  $fieldKeys
     */
    public function syncFixedFieldAssignments(array $fieldKeys): void
    {
        $category = $this->ensure();
        $categoryId = (int) $category->id;
        $order = (int) BikeFieldCategoryAssignment::where('category_id', $categoryId)->max('display_order');

        foreach ($fieldKeys as $fieldKey) {
            if (BikeFieldCategoryAssignment::query()->where('field_key', $fieldKey)->exists()) {
                continue;
            }

            $order++;
            BikeFieldCategoryAssignment::query()->create([
                'field_key' => $fieldKey,
                'category_id' => $categoryId,
                'display_order' => $order,
                'display_label' => null,
                'input_type' => null,
                'input_config' => null,
                'is_visible' => true,
                'is_required' => true,
            ]);
        }
    }

    public function relocateVisibleFieldsToDefaultCategory(): void
    {
        $category = $this->ensure();
        $categoryId = (int) $category->id;

        if (Schema::hasTable('bike_field_category_assignments')) {
            $allowedKeys = $this->discoverAssignableFieldKeys();
            if ($allowedKeys !== []) {
                BikeFieldCategoryAssignment::query()
                    ->whereIn('field_key', $allowedKeys)
                    ->where(function ($query) {
                        $query->whereNull('is_visible')->orWhere('is_visible', true);
                    })
                    ->update(['category_id' => $categoryId]);
            }
        }

        if (Schema::hasTable('bike_custom_fields')) {
            BikeCustomField::query()
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

        $categoryId = (int) ($category ?? $this->ensure())->id;

        FixedFieldCategoryAssignmentSync::assignCustomFieldsWithoutCategory(
            BikeCustomField::class,
            $categoryId,
        );
    }

    public function isDefaultCategory(BikeCategory $category): bool
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
