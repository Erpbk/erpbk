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
            ->whereNull('company_id')
            ->first();

        if ($category) {
            if (trim((string) $category->label) === '') {
                $category->label = $this->defaultLabel();
                $category->save();
            }

            return $category;
        }

        return RiderCategory::withoutGlobalScopes(['company'])->create([
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
     * Create assignments for fixed rider fields that are not assigned yet.
     *
     * @param  list<string>  $fieldKeys
     */
    public function syncFixedFieldAssignments(array $fieldKeys): void
    {
        FixedFieldCategoryAssignmentSync::sync(
            $fieldKeys,
            RiderCustomField::fixedFieldsSlugMap(),
            RiderFieldCategoryAssignment::class,
            fn () => RiderCategory::query(),
            self::FALLBACK_SLUG,
            self::DEFAULT_SLUG,
        );
    }

    /**
     * Assign custom fields with no category to the "Other" system category.
     */
    public function assignUnassignedCustomFields(?RiderCategory $category = null): void
    {
        if (! Schema::hasTable('rider_custom_fields')) {
            return;
        }

        $categoryId = $category
            ? (int) $category->id
            : ($this->categoryIdForSlug(self::FALLBACK_SLUG) ?? (int) $this->ensure()->id);

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
        $this->pruneNonEssentialAssignments();
        $fieldKeys = $this->discoverAssignableFieldKeys();
        $this->syncFixedFieldAssignments($fieldKeys);
        $this->assignUnassignedCustomFields();
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
