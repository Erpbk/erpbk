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

if (! function_exists('ga_name')) {
    /**
     * Resolve a global account display name by code from the chart of accounts.
     */
    function ga_name(string $code): string
    {
        return \App\Support\GlobalAccounts::name($code);
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

        $attrs = in_array($control, ['select', 'checkbox', 'radio', 'multiselect'], true)
            ? ['disabled' => 'disabled']
            : ['readonly' => 'readonly'];

        // Marker for the global field-permission lock script (Select2, AJAX modals, etc.).
        $attrs['data-rfp-locked'] = '1';

        return $attrs;
    }
}

if (! function_exists('field_input_name')) {
    /**
     * Normalize an HTML input name to a Role Field Permission field key.
     * custom_field_values[7] / custom_field_values.7 => cf_7
     */
    function field_input_name(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return $name;
        }
        if (preg_match('/^custom_field_values\[(\d+)\]$/', $name, $m)
            || preg_match('/^custom_field_values\.(\d+)$/', $name, $m)
            || preg_match('/^voucher_custom_fields\[(\d+)\]$/', $name, $m)) {
            return 'cf_' . $m[1];
        }

        // Strip array suffixes: debit_account_id[] => debit_account_id
        if (str_ends_with($name, '[]')) {
            $name = substr($name, 0, -2);
        }

        return $name;
    }
}

if (! function_exists('delete_approval_enabled')) {
    function delete_approval_enabled(): bool
    {
        return \App\Services\DeleteRequestService::enabled();
    }
}

if (! function_exists('record_is_pending_deletion')) {
    function record_is_pending_deletion($model): bool
    {
        return $model instanceof \Illuminate\Database\Eloquent\Model
            && method_exists($model, 'isPendingDeletion')
            && $model->isPendingDeletion();
    }
}

if (! function_exists('pending_deletion_ids_for')) {
    /**
     * @return array<int, int> id => id
     */
    function pending_deletion_ids_for(string $modelClass): array
    {
        $ids = \App\Services\DeleteRequestService::pendingIdsFor($modelClass);

        return array_combine($ids, $ids) ?: [];
    }
}


if (! function_exists('delete_outcome_message')) {
    /**
     * User-facing message after a destroy action (pending approval vs recycle bin).
     */
    function delete_outcome_message(string $entityName = 'Record', ?string $recycleBinUrl = null): string
    {
        if (request()->attributes->get('delete_approval_created')) {
            return \App\Services\DeleteRequestService::pendingMessage(
                request()->attributes->get('delete_approval_request')
            );
        }

        $link = $recycleBinUrl
            ? ' <a href="' . e($recycleBinUrl) . '" class="alert-link">View Recycle Bin</a> to restore if needed.'
            : '';

        return $entityName . ' moved to Recycle Bin.' . $link;
    }
}
