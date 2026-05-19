<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Schema;

/**
 * Chart of accounts visibility:
 * - Fixed accounts (is_fixed = true, company_id NULL) are shared across all tenants.
 * - All other accounts are scoped to the current company_id.
 */
final class AccountsCompanyScope
{
    public static function appliesToTable(string $table, ?string $connection = null): bool
    {
        $base = self::baseTableName($table);
        if ($base !== 'accounts') {
            return false;
        }

        $connectionName = $connection ?: config('database.default');

        return Schema::connection($connectionName)->hasColumn($base, 'company_id')
            && Schema::connection($connectionName)->hasColumn($base, 'is_fixed');
    }

    /**
     * @param  EloquentBuilder|QueryBuilder  $query
     * @return EloquentBuilder|QueryBuilder
     */
    public static function apply($query, int $companyId, ?string $table = 'accounts')
    {
        $companyColumn = CompanyScope::qualifyColumn($table, 'company_id');
        $fixedColumn = CompanyScope::qualifyColumn($table, 'is_fixed');

        return $query->where(function ($builder) use ($companyColumn, $fixedColumn, $companyId) {
            $builder->where(function ($shared) use ($fixedColumn, $companyColumn) {
                $shared->where($fixedColumn, true)->whereNull($companyColumn);
            })->orWhere(function ($owned) use ($companyColumn, $companyId) {
                $owned->where($companyColumn, $companyId)->whereNotNull($companyColumn);
            });
        });
    }

    public static function baseTableName(string $table): string
    {
        if (str_contains($table, ' as ')) {
            $table = trim(explode(' as ', $table, 2)[0]);
        }

        if (str_contains($table, ' ')) {
            $table = explode(' ', $table)[0];
        }

        return str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
    }
}
