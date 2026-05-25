<?php

namespace App\Services\Rider;

use App\Models\RiderCategory;
use App\Models\RiderCustomField;
use App\Models\RiderFieldCategoryAssignment;
use App\Models\Settings;
use App\Services\Settings\FixedFieldCategoryAssignmentSync;
use Illuminate\Support\Facades\Schema;

final class RiderDefaultCategoryService
{
    public const DEFAULT_SLUG = 'general';

    public const DEFAULT_LABEL = 'General';

    public const FALLBACK_SLUG = 'other';

    /**
     * Ensure the default shared "General" rider category exists (for delete protection / overflow).
     */
    public function ensure(): RiderCategory
    {
        $category = RiderCategory::withoutGlobalScopes(['company'])
            ->where('slug', self::DEFAULT_SLUG)
            ->first();

        if ($category) {
            return $this->normalizeDefaultCategory($category);
        }

        try {
            return RiderCategory::withoutGlobalScopes(['company'])->create([
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

            $category = RiderCategory::withoutGlobalScopes(['company'])
                ->where('slug', self::DEFAULT_SLUG)
                ->first();

            if ($category) {
                return $this->normalizeDefaultCategory($category);
            }

            throw $e;
        }
    }

    private function normalizeDefaultCategory(RiderCategory $category): RiderCategory
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
            || str_contains($message, 'rider_categories_slug_unique');
    }

    /**
     * @return array<string, int>
     */
    public function categoryIdsBySlug(): array
    {
        return RiderCategory::query()
            ->whereNotNull('slug')
            ->pluck('id', 'slug')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function categoryIdForSlug(string $slug): ?int
    {
        return $this->categoryIdsBySlug()[$slug] ?? null;
    }

    /**
     * Create assignments for fixed rider fields that are not assigned yet (default category).
     *
     * @param  list<string>  $fieldKeys
     */
    public function syncFixedFieldAssignments(array $fieldKeys): void
    {
        $category = $this->ensure();
        $categoryId = (int) $category->id;
        $order = (int) RiderFieldCategoryAssignment::where('category_id', $categoryId)->max('display_order');

        foreach ($fieldKeys as $fieldKey) {
            if (RiderFieldCategoryAssignment::query()->where('field_key', $fieldKey)->exists()) {
                continue;
            }

            $order++;
            RiderFieldCategoryAssignment::query()->create([
                'field_key' => $fieldKey,
                'category_id' => $categoryId,
                'display_order' => $order,
                'is_visible' => true,
                'is_required' => false,
            ]);
        }
    }

    /**
     * One-time: move all visible essential fields into the default category.
     */
    public function relocateVisibleFieldsToDefaultCategory(): void
    {
        $category = $this->ensure();
        $categoryId = (int) $category->id;

        if (Schema::hasTable('rider_field_category_assignments')) {
            $allowedKeys = RiderCustomField::allFixedFieldKeys();
            if ($allowedKeys !== []) {
                RiderFieldCategoryAssignment::query()
                    ->whereIn('field_key', $allowedKeys)
                    ->where(function ($query) {
                        $query->whereNull('is_visible')->orWhere('is_visible', true);
                    })
                    ->update(['category_id' => $categoryId]);
            }
        }

        if (Schema::hasTable('rider_custom_fields')) {
            RiderCustomField::query()
                ->where(function ($query) {
                    $query->where('is_visible', true)->orWhereNull('is_visible');
                })
                ->update(['category_id' => $categoryId]);
        }
    }

    /**
     * Assign custom fields with no category to the default category.
     */
    public function assignUnassignedCustomFields(?RiderCategory $category = null): void
    {
        if (! Schema::hasTable('rider_custom_fields')) {
            return;
        }

        $categoryId = (int) ($category ?? $this->ensure())->id;

        FixedFieldCategoryAssignmentSync::assignCustomFieldsWithoutCategory(
            RiderCustomField::class,
            $categoryId,
        );
    }

    public function isDefaultCategory(RiderCategory $category): bool
    {
        return (string) $category->slug === self::DEFAULT_SLUG
            && (bool) $category->is_system
            && $category->company_id === null;
    }

    public function bootstrap(): void
    {
        $this->ensure();
        $defaultCategory = $this->ensure();
        $this->pruneNonEssentialAssignments();
        $fieldKeys = $this->discoverAssignableFieldKeys();
        $this->syncFixedFieldAssignments($fieldKeys);
        $this->assignUnassignedCustomFields($defaultCategory);
    }

    /**
     * Remove field assignments that are no longer part of the essential rider form.
     */
    public function pruneNonEssentialAssignments(): void
    {
        if (! Schema::hasTable('rider_field_category_assignments')) {
            return;
        }

        $allowed = array_flip(RiderCustomField::allFixedFieldKeys());

        RiderFieldCategoryAssignment::query()
            ->whereNotIn('field_key', array_keys($allowed))
            ->delete();
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
