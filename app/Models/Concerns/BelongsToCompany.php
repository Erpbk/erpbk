<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
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
            if ($companyId !== null) {
                $model->applyCompanyScopeConstraint($builder, $companyId);
            }
        });

        static::creating(function (Model $model): void {
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
        if (app()->runningInConsole()) {
            return false;
        }

        return $this->hasCompanyColumn();
    }

    protected function resolveScopedCompanyId(): ?int
    {
        $request = request();
        if (!$request) {
            return null;
        }

        $company = $request->attributes->get('company');
        if ($company && isset($company->id)) {
            return (int) $company->id;
        }

        if (Auth::check() && !empty(Auth::user()->company_id)) {
            return (int) Auth::user()->company_id;
        }

        $companySlug = $request->route('company_slug') ?? $request->session()->get('company_slug');
        if (empty($companySlug)) {
            return null;
        }

        $resolvedCompany = Company::query()->where('slug', (string) $companySlug)->first();
        if (!$resolvedCompany && is_numeric($companySlug)) {
            $resolvedCompany = Company::query()->find((int) $companySlug);
        }

        if (!$resolvedCompany) {
            return null;
        }

        $request->attributes->set('company', $resolvedCompany);
        return (int) $resolvedCompany->id;
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
     * Override in models that should see global rows (company_id NULL) + tenant rows.
     */
    protected function includesGlobalCompanyRows(): bool
    {
        return false;
    }

    /**
     * Apply tenant/company constraint to the builder.
     * Models can override this for advanced behavior.
     */
    protected function applyCompanyScopeConstraint(Builder $builder, int $companyId): void
    {
        if ($this->includesGlobalCompanyRows()) {
            $builder->where(function (Builder $query) use ($builder, $companyId): void {
                $query
                    ->where($builder->getModel()->qualifyColumn('company_id'), $companyId)
                    ->orWhereNull($builder->getModel()->qualifyColumn('company_id'));
            });
            return;
        }

        $builder->where($builder->getModel()->qualifyColumn('company_id'), $companyId);
    }
}
