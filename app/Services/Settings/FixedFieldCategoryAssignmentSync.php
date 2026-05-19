<?php

namespace App\Services\Settings;

use Illuminate\Database\Eloquent\Model;

final class FixedFieldCategoryAssignmentSync
{
    /**
     * Create or reconcile fixed-field category assignments using a slug map.
     *
     * @param  list<string>  $fieldKeys
     * @param  array<string, list<string>>  $slugMap
     * @param  class-string<Model>  $assignmentClass
     * @param  callable(): \Illuminate\Database\Eloquent\Builder  $categoryQuery
     */
    public static function sync(
        array $fieldKeys,
        array $slugMap,
        string $assignmentClass,
        callable $categoryQuery,
        string $fallbackSlug = 'other',
        ?string $reconcileFromSlug = 'general',
    ): void {
        $categories = $categoryQuery()->orderBy('display_order')->orderBy('id')->get();
        if ($categories->isEmpty()) {
            return;
        }

        $slugToId = $categories
            ->filter(fn ($cat) => $cat->slug !== null && $cat->slug !== '')
            ->pluck('id', 'slug')
            ->map(fn ($id) => (int) $id)
            ->all();

        $fallbackCategoryId = $slugToId[$fallbackSlug] ?? (int) $categories->first()->id;
        $reconcileFromCategoryId = $reconcileFromSlug !== null && $reconcileFromSlug !== ''
            ? ($slugToId[$reconcileFromSlug] ?? null)
            : null;

        $fieldToSlug = [];
        foreach ($slugMap as $slug => $keys) {
            foreach ($keys as $fieldKey) {
                $fieldToSlug[$fieldKey] = $slug;
            }
        }

        $orderCounters = [];

        foreach ($fieldKeys as $fieldKey) {
            $targetSlug = $fieldToSlug[$fieldKey] ?? $fallbackSlug;
            $targetCategoryId = $slugToId[$targetSlug] ?? $fallbackCategoryId;

            /** @var Model|null $assignment */
            $assignment = $assignmentClass::query()->where('field_key', $fieldKey)->first();

            if ($assignment) {
                if (
                    $reconcileFromCategoryId !== null
                    && (int) $assignment->category_id === $reconcileFromCategoryId
                    && isset($fieldToSlug[$fieldKey])
                    && (int) $targetCategoryId !== $reconcileFromCategoryId
                ) {
                    $assignment->category_id = $targetCategoryId;
                    $assignment->save();
                }

                continue;
            }

            if (! isset($orderCounters[$targetCategoryId])) {
                $orderCounters[$targetCategoryId] = (int) $assignmentClass::query()
                    ->where('category_id', $targetCategoryId)
                    ->max('display_order');
            }

            $orderCounters[$targetCategoryId]++;

            $assignmentClass::query()->create([
                'field_key' => $fieldKey,
                'category_id' => $targetCategoryId,
                'display_order' => $orderCounters[$targetCategoryId],
                'is_visible' => true,
                'is_required' => false,
            ]);
        }
    }

    /**
     * @param  class-string<Model>  $customFieldClass
     */
    public static function assignCustomFieldsWithoutCategory(
        string $customFieldClass,
        int $categoryId,
    ): void {
        $customFieldClass::query()
            ->whereNull('category_id')
            ->update(['category_id' => $categoryId]);
    }
}
