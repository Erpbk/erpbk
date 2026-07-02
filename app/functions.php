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

if (! function_exists('user_avatar_url')) {
    /**
     * URL for a user profile image stored on the public disk.
     */
    function user_avatar_url(?string $imageName): string
    {
        if ($imageName && $imageName !== 'default.png') {
            return storage_url('uploads/' . $imageName) ?? asset('uploads/default.png');
        }

        return asset('uploads/default.png');
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

if (! function_exists('ga_id')) {
    /**
     * Resolve a global account ID by code from the global_accounts registry.
     */
    function ga_id(string $code): int
    {
        return \App\Support\GlobalAccounts::id($code);
    }
}
