<?php

namespace App\Services\Settings;

use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

final class PerCompanyDefaultCategory
{
    /**
     * Ensure the system "General" category exists for one company. Never writes company_id NULL.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function ensure(
        string $modelClass,
        string $slug,
        callable $labelResolver,
        ?int $companyId = null,
    ): Model {
        $companyId = self::resolveCompanyId($companyId);

        $existing = $modelClass::withoutGlobalScopes(['company'])
            ->where('slug', $slug)
            ->where('company_id', $companyId)
            ->first();

        if ($existing) {
            return self::normalize($existing, $labelResolver);
        }

        try {
            return $modelClass::withoutGlobalScopes(['company'])->create([
                'slug' => $slug,
                'label' => $labelResolver(),
                'display_order' => 0,
                'is_system' => true,
                'company_id' => $companyId,
            ]);
        } catch (QueryException $e) {
            $existing = $modelClass::withoutGlobalScopes(['company'])
                ->where('slug', $slug)
                ->where('company_id', $companyId)
                ->first();

            if ($existing) {
                return self::normalize($existing, $labelResolver);
            }

            throw $e;
        }
    }

    public static function resolveCompanyId(?int $companyId = null): int
    {
        $companyId = $companyId ?? CompanyContext::id();
        if ($companyId === null || $companyId <= 0) {
            throw new \RuntimeException('Company context is required for module settings.');
        }

        return $companyId;
    }

    /**
     * @return list<int>
     */
    public static function bootstrapCompanyIds(?int $companyId = null): array
    {
        if ($companyId !== null && $companyId > 0) {
            return [$companyId];
        }

        $current = CompanyContext::id();
        if ($current !== null && $current > 0) {
            return [(int) $current];
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('companies')) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('companies')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();
    }

    public static function isDefault(Model $category, string $slug): bool
    {
        return (string) $category->getAttribute('slug') === $slug
            && (bool) $category->getAttribute('is_system');
    }

    private static function normalize(Model $category, callable $labelResolver): Model
    {
        $dirty = false;

        if (! $category->getAttribute('is_system')) {
            $category->setAttribute('is_system', true);
            $dirty = true;
        }

        if (trim((string) $category->getAttribute('label')) === '') {
            $category->setAttribute('label', $labelResolver());
            $dirty = true;
        }

        if ($dirty) {
            $category->save();
        }

        return $category;
    }
}
