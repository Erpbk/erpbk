<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class CompanyScope
{
    /**
     * Current company id for tenant requests, or abort when scope is required.
     */
    public static function requireId(): int
    {
        if (! CompanyContext::shouldApplyScope()) {
            return 0;
        }

        $companyId = CompanyContext::id();
        if ($companyId === null) {
            abort(403, 'Company context is required.');
        }

        return $companyId;
    }

    /**
     * Apply strict company_id filter. Rows with company_id NULL are orphan data and are excluded.
     *
     * @param  EloquentBuilder|QueryBuilder  $query
     * @return EloquentBuilder|QueryBuilder
     */
    public static function apply($query, ?string $column = null)
    {
        if (! CompanyContext::shouldApplyScope()) {
            return $query;
        }

        $companyId = CompanyContext::id();
        $column = $column ?? 'company_id';

        if ($companyId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where($column, $companyId)->whereNotNull($column);
    }

    /**
     * Apply strict scope when the table has a company_id column.
     *
     * @param  EloquentBuilder|QueryBuilder  $query
     * @return EloquentBuilder|QueryBuilder
     */
    public static function applyToTable($query, string $table, ?string $connection = null)
    {
        $connectionName = $connection ?: (method_exists($query, 'getConnection')
            ? $query->getConnection()->getName()
            : null) ?: config('database.default');

        if (! Schema::connection($connectionName)->hasColumn($table, 'company_id')) {
            return $query;
        }

        $companyId = CompanyContext::id();
        if ($companyId === null) {
            return $query->whereRaw('0 = 1');
        }

        if (AccountsCompanyScope::appliesToTable($table, $connectionName)) {
            return AccountsCompanyScope::apply($query, $companyId, $table);
        }

        return self::apply($query, self::qualifyColumn($table, 'company_id'));
    }

    public static function qualifyColumn(string $table, string $column): string
    {
        if (str_contains($table, '.')) {
            return $table;
        }

        return $table . '.' . $column;
    }

    /**
     * Build a unique validation rule scoped to the current company when applicable.
     */
    public static function unique(string $table, string $column, mixed $ignore = null): Unique
    {
        $rule = Rule::unique($table, $column);

        if ($ignore !== null) {
            $rule->ignore($ignore);
        }

        if (CompanyContext::shouldApplyScope() && Schema::hasColumn($table, 'company_id')) {
            $companyId = CompanyContext::id();
            $rule->where(function ($query) use ($companyId) {
                if ($companyId === null) {
                    $query->whereNull('company_id');
                } else {
                    $query->where('company_id', $companyId);
                }
            });
        }

        return $rule;
    }
}
