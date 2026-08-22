<?php

namespace App\Services\Rider;

use App\Models\RiderCategory;
use App\Models\RiderCustomField;
use App\Models\RiderFieldCategoryAssignment;
use App\Models\Settings;
use App\Services\Settings\FixedFieldCategoryAssignmentSync;
use App\Services\Settings\PerCompanyDefaultCategory;
use Illuminate\Support\Facades\Schema;

final class RiderDefaultCategoryService
{
    public const DEFAULT_SLUG = 'general';

    public const DEFAULT_LABEL = 'General';

    public const FALLBACK_SLUG = 'other';

    public function ensure(?int $companyId = null): RiderCategory
    {
        /** @var RiderCategory $category */
        $category = PerCompanyDefaultCategory::ensure(
            RiderCategory::class,
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
        $query = RiderCategory::query()->whereNotNull('slug');
        if ($companyId !== null) {
            $query = RiderCategory::withoutGlobalScopes(['company'])
                ->where('company_id', $companyId)
                ->whereNotNull('slug');
        }

        return $query
            ->pluck('id', 'slug')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function categoryIdForSlug(string $slug, ?int $companyId = null): ?int
    {
        return $this->categoryIdsBySlug($companyId)[$slug] ?? null;
    }

    /**
     * @param  list<string>  $fieldKeys
     */
    public function syncFixedFieldAssignments(array $fieldKeys, ?int $companyId = null): void
    {
        $category = $this->ensure($companyId);
        $companyId = (int) $category->company_id;
        $categoryId = (int) $category->id;
        $order = (int) RiderFieldCategoryAssignment::withoutGlobalScopes(['company'])
            ->where('category_id', $categoryId)
            ->max('display_order');

        foreach ($fieldKeys as $fieldKey) {
            $exists = RiderFieldCategoryAssignment::withoutGlobalScopes(['company'])
                ->where('field_key', $fieldKey)
                ->where('company_id', $companyId)
                ->exists();
            if ($exists) {
                continue;
            }

            $order++;
            RiderFieldCategoryAssignment::withoutGlobalScopes(['company'])->create([
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

        if (Schema::hasTable('rider_field_category_assignments')) {
            $allowedKeys = RiderCustomField::allFixedFieldKeys();
            if ($allowedKeys !== []) {
                RiderFieldCategoryAssignment::withoutGlobalScopes(['company'])
                    ->where('company_id', $companyId)
                    ->whereIn('field_key', $allowedKeys)
                    ->where(function ($query) {
                        $query->whereNull('is_visible')->orWhere('is_visible', true);
                    })
                    ->update(['category_id' => $categoryId]);
            }
        }

        if (Schema::hasTable('rider_custom_fields')) {
            RiderCustomField::withoutGlobalScopes(['company'])
                ->where('company_id', $companyId)
                ->where(function ($query) {
                    $query->where('is_visible', true)->orWhereNull('is_visible');
                })
                ->update(['category_id' => $categoryId]);
        }
    }

    public function assignUnassignedCustomFields(?RiderCategory $category = null): void
    {
        if (! Schema::hasTable('rider_custom_fields')) {
            return;
        }

        $category = $category ?? $this->ensure();
        $categoryId = (int) $category->id;
        $companyId = $category->company_id !== null ? (int) $category->company_id : null;

        FixedFieldCategoryAssignmentSync::assignCustomFieldsWithoutCategory(
            RiderCustomField::class,
            $categoryId,
            $companyId,
        );
    }

    public function isDefaultCategory(RiderCategory $category): bool
    {
        return PerCompanyDefaultCategory::isDefault($category, self::DEFAULT_SLUG);
    }

    public function bootstrap(?int $companyId = null): void
    {
        foreach (PerCompanyDefaultCategory::bootstrapCompanyIds($companyId) as $id) {
            $defaultCategory = $this->ensure($id);
            $this->pruneNonEssentialAssignments($id);
            $this->syncFixedFieldAssignments($this->discoverAssignableFieldKeys(), $id);
            $this->assignUnassignedCustomFields($defaultCategory);
        }
    }

    public function pruneNonEssentialAssignments(?int $companyId = null): void
    {
        if (! Schema::hasTable('rider_field_category_assignments')) {
            return;
        }

        $allowed = array_flip(RiderCustomField::allFixedFieldKeys());
        $query = RiderFieldCategoryAssignment::withoutGlobalScopes(['company'])
            ->whereNotIn('field_key', array_keys($allowed));
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }
        $query->delete();
    }

    /**
     * @return list<string>
     */
    public function discoverAssignableFieldKeys(): array
    {
        if (! Schema::hasTable('riders')) {
            return [];
        }

        $keys = RiderCustomField::allFixedFieldKeys();
        $riderColumns = array_flip(Schema::getColumnListing('riders'));

        return array_values(array_filter($keys, fn (string $fieldKey) => isset($riderColumns[$fieldKey])));
    }

    protected function defaultLabel(): string
    {
        $menuLabel = Settings::getMenuLabel('rider_settings');
        if ($menuLabel !== '' && strcasecmp($menuLabel, 'rider_settings') !== 0) {
            return $menuLabel;
        }

        return self::DEFAULT_LABEL;
    }
}
