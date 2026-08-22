<?php

namespace App\Services\Settings;

use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
        ?int $companyId = null,
    ): void {
        $companyId = $companyId ?? CompanyContext::id();
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
        $assignmentTable = (new $assignmentClass())->getTable();
        $assignmentsAreCompanyScoped = $companyId !== null
            && Schema::hasColumn($assignmentTable, 'company_id');

        foreach ($fieldKeys as $fieldKey) {
            $targetSlug = $fieldToSlug[$fieldKey] ?? $fallbackSlug;
            $targetCategoryId = $slugToId[$targetSlug] ?? $fallbackCategoryId;

            $assignmentQuery = $assignmentClass::query()->where('field_key', $fieldKey);
            if ($assignmentsAreCompanyScoped) {
                $assignmentQuery->where('company_id', $companyId);
            }

            /** @var Model|null $assignment */
            $assignment = $assignmentQuery->first();

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

            $payload = [
                'field_key' => $fieldKey,
                'category_id' => $targetCategoryId,
                'display_order' => $orderCounters[$targetCategoryId],
                'is_visible' => true,
                'is_required' => false,
            ];
            if ($assignmentsAreCompanyScoped) {
                $payload['company_id'] = $companyId;
            }

            $assignmentClass::query()->create($payload);
        }
    }

    /**
     * @param  class-string<Model>  $customFieldClass
     */
    public static function assignCustomFieldsWithoutCategory(
        string $customFieldClass,
        int $categoryId,
        ?int $companyId = null,
    ): void {
        $query = $customFieldClass::query()->whereNull('category_id');
        $companyId = $companyId ?? CompanyContext::id();
        $table = (new $customFieldClass())->getTable();
        if ($companyId !== null && Schema::hasColumn($table, 'company_id')) {
            $query->where('company_id', $companyId);
        }
        $query->update(['category_id' => $categoryId]);
    }
}

