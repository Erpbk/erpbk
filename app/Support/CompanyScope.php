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
     * Apply strict company_id filter (no shared/global rows).
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

        return $query->where($column, $companyId);
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

        if (CompanyContext::shouldApplyScope()) {
            $companyId = CompanyContext::id();
            if ($companyId !== null && Schema::hasColumn($table, 'company_id')) {
                $rule->where(fn ($query) => $query->where('company_id', $companyId));
            }
        }

        return $rule;
    }
}
