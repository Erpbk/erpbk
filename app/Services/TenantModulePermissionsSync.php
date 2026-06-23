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
            if ($parent->parent_id !== null) {
                $parent->update(['parent_id' => null]);
            }

            foreach (['view', 'create', 'edit', 'delete'] as $action) {
                $child = Permission::query()->firstOrCreate(
                    ['name' => $slug . '_' . $action, 'guard_name' => $guard],
                    ['parent_id' => $parent->id]
                );
                if ((int) $child->parent_id !== (int) $parent->id) {
                    $child->update(['parent_id' => $parent->id]);
                }
                if ($assignToAdminRoles) {
                    self::giveToAdminRoles($child);
                }
            }

            foreach ($module['extras'] ?? [] as $extraName) {
                $child = Permission::query()->firstOrCreate(
                    ['name' => $extraName, 'guard_name' => $guard],
                    ['parent_id' => $parent->id]
                );
                if ((int) $child->parent_id !== (int) $parent->id) {
                    $child->update(['parent_id' => $parent->id]);
                }
                if ($assignToAdminRoles) {
                    self::giveToAdminRoles($child);
                }
            }
        }

        foreach (config('tenant_module_permissions.additional_permissions', []) as $group) {
            $parentName = $group['parent'] ?? null;
            if (!is_string($parentName) || $parentName === '') {
                continue;
            }

            $parent = Permission::query()->firstOrCreate(
                ['name' => $parentName, 'guard_name' => $guard],
                ['parent_id' => null]
            );

            foreach ($group['permissions'] ?? [] as $permName) {
                $permName = trim((string) $permName);
                if ($permName === '') {
                    continue;
                }

                $child = Permission::query()->firstOrCreate(
                    ['name' => $permName, 'guard_name' => $guard],
                    ['parent_id' => $parent->id]
                );
                if ((int) $child->parent_id !== (int) $parent->id) {
                    $child->update(['parent_id' => $parent->id]);
                }
                if ($assignToAdminRoles) {
                    self::giveToAdminRoles($child);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Migrate legacy permission assignments when module structure changes.
     */
    public static function migrateLegacyPermissionAssignments(): void
    {
        $permissionMap = [
            'visaloan_create' => 'installment_create',
            'visaloan_edit' => 'installment_edit',
        ];

        foreach ($permissionMap as $oldName => $newName) {
            $oldPermission = Permission::query()->where('name', $oldName)->where('guard_name', 'web')->first();
            $newPermission = Permission::query()->where('name', $newName)->where('guard_name', 'web')->first();
            if (!$oldPermission || !$newPermission) {
                continue;
            }

            foreach (Role::query()->where('guard_name', 'web')->get() as $role) {
                if ($role->hasPermissionTo($oldPermission) && !$role->hasPermissionTo($newPermission)) {
                    $role->givePermissionTo($newPermission);
                }
            }
        }

        $showInMenu = Permission::query()->where('name', 'visaexpense_show_in_menu')->where('guard_name', 'web')->first();
        $viewPermission = Permission::query()->where('name', 'visaexpense_view')->where('guard_name', 'web')->first();
        if ($showInMenu && $viewPermission) {
            foreach (Role::query()->where('guard_name', 'web')->get() as $role) {
                if ($role->hasPermissionTo($viewPermission) && !$role->hasPermissionTo($showInMenu)) {
                    $role->givePermissionTo($showInMenu);
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
