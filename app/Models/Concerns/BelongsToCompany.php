<?php

namespace App\Models\Concerns;

use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait BelongsToCompany
{
    /**
     * Cache company_id-column lookups per connection+table.
     *
     * @var array<string, bool>
     */
    private static array $companyColumnCache = [];

    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            /** @var Model&self $model */
            $model = $builder->getModel();

            if (!$model->shouldApplyCompanyScope()) {
                return;
            }

            $companyId = $model->resolveScopedCompanyId();
            if ($companyId === null) {
                $builder->whereRaw('0 = 1');

                return;
            }

            $model->applyCompanyScopeConstraint($builder, $companyId);
        });

        static::saving(function (Model $model): void {
            /** @var Model&self $model */
            if (!$model->shouldApplyCompanyScope()) {
                return;
            }

            if (empty($model->getAttribute('company_id'))) {
                $companyId = $model->resolveScopedCompanyId();
                if ($companyId !== null) {
                    $model->setAttribute('company_id', $companyId);
                }
            }
        });
    }

    protected function shouldApplyCompanyScope(): bool
    {
        return CompanyContext::shouldApplyScope() && $this->hasCompanyColumn();
    }

    protected function resolveScopedCompanyId(): ?int
    {
        return CompanyContext::id();
    }

    protected function hasCompanyColumn(): bool
    {
        $table = $this->getTable();
        $connection = $this->getConnectionName() ?: config('database.default');
        $cacheKey = $connection . ':' . $table;

        if (array_key_exists($cacheKey, self::$companyColumnCache)) {
            return self::$companyColumnCache[$cacheKey];
        }

        self::$companyColumnCache[$cacheKey] = Schema::connection($connection)->hasColumn($table, 'company_id');

        return self::$companyColumnCache[$cacheKey];
    }

    /**
     * Apply tenant/company constraint. Rows with company_id NULL are orphan data and are never visible.
     */
    protected function applyCompanyScopeConstraint(Builder $builder, int $companyId): void
    {
        $column = $builder->getModel()->qualifyColumn('company_id');
        $builder->where($column, $companyId)->whereNotNull($column);
    }
}
