<?php

use App\Support\CompanyQuery;
use Illuminate\Database\Query\Builder;

if (! function_exists('company_table')) {
    /**
     * Tenant-safe query builder: filters by current company_id and excludes NULL company_id rows.
     * Use instead of DB::table() in company routes, views, reports, and settings.
     */
    function company_table(string $table, ?string $connection = null): Builder
    {
        return CompanyQuery::table($table, $connection);
    }
}
