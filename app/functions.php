<?php

use App\Support\CompanyQuery;
use Illuminate\Database\Query\Builder;

if (! function_exists('storage_url')) {
    /**
     * URL for a file on the public disk (asset storage/…) or local disk (storage2/…).
     */
    function storage_url(?string $path): ?string
    {
        return \App\Support\StorageUrl::resolve($path);
    }
}

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
