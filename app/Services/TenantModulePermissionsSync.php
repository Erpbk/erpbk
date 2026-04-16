<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantModulePermissionsSync
{
    /**
     * Ensure default module permissions (and roles) exist on the current default DB connection (tenant).
     */
    public static function sync(bool $assignToAdminRoles = true): void
    {
        $guard = 'web';

        foreach (config('tenant_module_permissions.assign_roles', []) as $roleName) {
            Role::query()->firstOrCreate(
                ['name' => $roleName, 'guard_name' => $guard],
                []
            );
        }

        foreach (config('tenant_module_permissions.modules', []) as $module) {
            $parentName = $module['parent'];
            $slug = $module['slug'];

            $parent = Permission::query()->firstOrCreate(
                ['name' => $parentName, 'guard_name' => $guard],
                ['parent_id' => null]
            );

            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $child = Permission::query()->firstOrCreate(
                    ['name' => $slug . '_' . $action, 'guard_name' => $guard],
                    ['parent_id' => $parent->id]
                );
                if ($assignToAdminRoles) {
                    self::giveToAdminRoles($child);
                }
            }

            foreach ($module['extras'] ?? [] as $extraName) {
                $child = Permission::query()->firstOrCreate(
                    ['name' => $extraName, 'guard_name' => $guard],
                    ['parent_id' => $parent->id]
                );
                if ($assignToAdminRoles) {
                    self::giveToAdminRoles($child);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected static function giveToAdminRoles(Permission $permission): void
    {
        foreach (config('tenant_module_permissions.assign_roles', []) as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();
            if ($role) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
