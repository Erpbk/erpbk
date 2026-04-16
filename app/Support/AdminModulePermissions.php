<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AdminModulePermissions
{
    /**
     * Ensure admin-module permissions exist.
     *
     * Spatie middleware throws if a permission name doesn't exist, so we create
     * the permission rows on-demand the first time the admin panel is hit.
     */
    public static function ensureForAdminPanel(): void
    {
        static $done = false;
        if ($done) {
            return;
        }

        $permissionTable = config('permission.table_names.permissions', 'permissions');
        if (!Schema::hasTable($permissionTable)) {
            // Permissions not set up yet (e.g. fresh install before running migrations).
            return;
        }

        $guard = Config::get('auth.defaults.guard', 'web');

        $permissionNames = [
            // Admin companies
            'companies_view',
            'companies_approve',
            'companies_reject',

            // Blogs
            'blogs_view',
            'blogs_create',
            'blogs_edit',
            'blogs_delete',

            // Testimonials
            'testimonials_view',
            'testimonials_create',
            'testimonials_edit',
            'testimonials_delete',

            // Policies
            'privacy_policy_view',
            'privacy_policy_edit',
            'terms_conditions_view',
            'terms_conditions_edit',

            // Users module (admin panel user list + role assignment)
            
        ];

        $registrar = app(PermissionRegistrar::class);
        $createdAny = false;

        foreach ($permissionNames as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);

            $createdAny = $createdAny || $permission->wasRecentlyCreated;
        }

        if ($createdAny) {
            $registrar->forgetCachedPermissions();
        }

        $done = true;
    }
}

