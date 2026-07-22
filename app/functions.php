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

/*
|--------------------------------------------------------------------------
| Field-level permission helpers (single source of truth)
|--------------------------------------------------------------------------
| These thin wrappers are the ONE canonical way to check field visibility,
| editability and required-state anywhere in the app (controllers, views,
| exports, jobs). They all delegate to App\Support\RoleFieldAccess so the
| logic lives in exactly one place. Prefer these over calling the class
| directly, and prefer the matching Blade directives inside views:
|   @fieldVisible($module,$field) ... @endfieldVisible
|   @fieldEditable($module,$field) ... @endfieldEditable
|   <input ... @fieldReadonly($module,$field) @fieldRequired($module,$field)>
*/

if (! function_exists('user_can')) {
    /**
     * Centralised, non-throwing module/action permission check. Use this everywhere
     * instead of $user->hasPermissionTo() / auth()->user()->hasPermissionTo(), which
     * throw PermissionDoesNotExist when a permission name no longer exists (e.g. after
     * permissions were regenerated with the hierarchical naming scheme).
     *
     * Understands both old flat names ("voucher_view") and the new hierarchical ones
     * ("vouchers_view", "bikes_bike_view"). Admins bypass. See RoleFieldAccess::userCan().
     */
    function user_can(string $ability, ?\App\Models\User $user = null): bool
    {
        return \App\Support\RoleFieldAccess::userCan($ability, $user);
    }
}

if (! function_exists('field_visible')) {
    /**
     * Whether the current user may SEE a field of a module.
     * $module is the entity slug (e.g. "rider", "customer"); $field is a DB
     * column name or "cf_{id}" / "custom_field_values.{id}" for a custom field.
     */
    function field_visible(string $module, string $field): bool
    {
        return \App\Support\RoleFieldAccess::canViewColumn($module, $field);
    }
}

if (! function_exists('field_editable')) {
    /**
     * Whether the current user may EDIT a field of a module.
     */
    function field_editable(string $module, string $field): bool
    {
        return \App\Support\RoleFieldAccess::canEdit($module, \App\Support\RoleFieldAccess::columnField($field));
    }
}

if (! function_exists('field_required')) {
    /**
     * Whether a field is REQUIRED for the current user (only when visible).
     */
    function field_required(string $module, string $field): bool
    {
        return \App\Support\RoleFieldAccess::isRequired($module, \App\Support\RoleFieldAccess::columnField($field));
    }
}

if (! function_exists('field_lock')) {
    /**
     * Attribute array to merge into a Laravel Collective / HTML control so that a
     * non-editable field is locked while staying visible. Returns [] when editable.
     *
     * Use 'select' for <select>, checkbox, radio and other controls that ignore
     * "readonly" (they need "disabled"); everything else gets "readonly".
     *
     * Example:
     *   {!! Form::text('name', null, ['class' => 'form-control'] + field_lock('customer','name')) !!}
     *   {!! Form::select('branch_id', $opts, null, ['class'=>'form-select'] + field_lock('customer','branch_id','select')) !!}
     *
     * @return array<string, string>
     */
    function field_lock(string $module, string $field, string $control = 'input'): array
    {
        if (field_editable($module, $field)) {
            return [];
        }

        return in_array($control, ['select', 'checkbox', 'radio', 'multiselect'], true)
            ? ['disabled' => 'disabled']
            : ['readonly' => 'readonly'];
    }
}
