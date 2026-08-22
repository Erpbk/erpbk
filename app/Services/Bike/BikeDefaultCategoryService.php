<?php

namespace App\Services\Bike;

use App\Models\BikeCategory;
use App\Models\BikeCustomField;
use App\Models\BikeFieldCategoryAssignment;
use App\Models\Settings;
use App\Services\Settings\FixedFieldCategoryAssignmentSync;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\Schema;

final class BikeDefaultCategoryService
{
    public const DEFAULT_SLUG = 'general';

    public const DEFAULT_LABEL = 'General';

    public function ensure(): BikeCategory
    {
        $category = BikeCategory::withoutGlobalScopes(['company'])
            ->where('slug', self::DEFAULT_SLUG)
            ->first();

        if ($category) {
            return $this->normalizeDefaultCategory($category);
        }

        try {
            return BikeCategory::withoutGlobalScopes(['company'])->create([
                'slug' => self::DEFAULT_SLUG,
                'label' => $this->defaultLabel(),
                'display_order' => 0,
                'is_system' => true,
                'company_id' => null,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if (! $this->isDuplicateSlugException($e)) {
                throw $e;
            }

            $category = BikeCategory::withoutGlobalScopes(['company'])
                ->where('slug', self::DEFAULT_SLUG)
                ->first();

            if ($category) {
                return $this->normalizeDefaultCategory($category);
            }

            throw $e;
        }
    }

    private function normalizeDefaultCategory(BikeCategory $category): BikeCategory
    {
        $dirty = false;

        if ($category->company_id !== null) {
            $category->company_id = null;
            $dirty = true;
        }

        if (! $category->is_system) {
            $category->is_system = true;
            $dirty = true;
        }

        if (trim((string) $category->label) === '') {
            $category->label = $this->defaultLabel();
            $dirty = true;
        }

        if ($dirty) {
            $category->save();
        }

        return $category;
    }

    private function isDuplicateSlugException(\Illuminate\Database\QueryException $e): bool
    {
        $code = (string) $e->getCode();
        $message = $e->getMessage();

        return $code === '23000'
            || str_contains($message, '1062')
            || str_contains($message, 'bike_categories_slug_unique');
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
            $payload = [
                'field_key' => $fieldKey,
                'category_id' => $categoryId,
                'display_order' => $order,
                'display_label' => null,
                'input_type' => null,
                'input_config' => null,
                'is_visible' => true,
                'is_required' => true,
            ];
            $companyId = CompanyContext::id();
            if ($companyId !== null && Schema::hasColumn((new BikeFieldCategoryAssignment())->getTable(), 'company_id')) {
                $payload['company_id'] = $companyId;
            }

            BikeFieldCategoryAssignment::query()->create($payload);
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
