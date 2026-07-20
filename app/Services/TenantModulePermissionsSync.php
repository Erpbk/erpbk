<?php

namespace App\Services;

use App\Support\PermissionTreeBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantModulePermissionsSync
{
    /**
     * Drop every permission (and pivot assignments), then rebuild from
     * config/tenant_module_permissions.php and assign leaves to admin roles.
     */
    public static function resetFromConfig(bool $assignToAdminRoles = true): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $roleHasPermissions = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $modelHasPermissions = $tableNames['model_has_permissions'] ?? 'model_has_permissions';

        if (! Schema::hasTable($permissionsTable)) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            if (Schema::hasTable($roleHasPermissions)) {
                DB::table($roleHasPermissions)->delete();
            }
            if (Schema::hasTable($modelHasPermissions)) {
                DB::table($modelHasPermissions)->delete();
            }
            DB::table($permissionsTable)->delete();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        self::sync($assignToAdminRoles);
    }

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
            $moduleSlug = $module['slug'] ?? PermissionTreeBuilder::slugify($parentName);
            $submodules = array_values(array_filter(array_map(
                static fn ($name) => trim((string) $name),
                $module['submodules'] ?? []
            )));
            $extras = $submodules === []
                ? array_values(array_filter(array_map(
                    static fn ($name) => trim((string) $name),
                    $module['extras'] ?? []
                )))
                : [];

            $parent = Permission::query()->firstOrCreate(
                ['name' => $parentName, 'guard_name' => $guard],
                ['parent_id' => null]
            );
            if ($parent->parent_id !== null) {
                $parent->update(['parent_id' => null]);
            }

            PermissionTreeBuilder::syncModuleTree($parent, $moduleSlug, $submodules, $extras, $guard);

            if ($assignToAdminRoles) {
                self::giveTreeToAdminRoles((int) $parent->id);
            }
        }

        foreach (config('tenant_module_permissions.additional_permissions', []) as $group) {
            $parentName = $group['parent'] ?? null;
            if (! is_string($parentName) || $parentName === '') {
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
            'visaloan_create' => 'visa_expense_create',
            'visaloan_edit' => 'visa_expense_edit',
            'visaexpense_view' => 'visa_expense_view',
            'visaexpense_create' => 'visa_expense_create',
            'visaexpense_edit' => 'visa_expense_edit',
            'visaexpense_delete' => 'visa_expense_delete',
            'billing_invoice_view' => 'bike_on_rent_invoices_view',
            'billing_invoice_create' => 'bike_on_rent_invoices_create',
            'billing_invoice_edit' => 'bike_on_rent_invoices_edit',
            'garages_view' => 'garages_garage_view',
            // Employee → Employees module slug rename
            'employee_employee_view' => 'employees_employee_view',
            'employee_employee_create' => 'employees_employee_create',
            'employee_employee_edit' => 'employees_employee_edit',
            'employee_employee_delete' => 'employees_employee_delete',
            'employee_attendance_view' => 'employees_attendance_view',
            'employee_attendance_create' => 'employees_attendance_create',
            'employee_attendance_edit' => 'employees_attendance_edit',
            'employee_attendance_delete' => 'employees_attendance_delete',
            'employee_invoice_view' => 'employees_invoice_view',
            'employee_invoice_create' => 'employees_invoice_create',
            'employee_invoice_edit' => 'employees_invoice_edit',
            'employee_invoice_delete' => 'employees_invoice_delete',
            'employee_payments_view' => 'employees_payments_view',
            'employee_payments_create' => 'employees_payments_create',
            'employee_payments_edit' => 'employees_payments_edit',
            'employee_payments_delete' => 'employees_payments_delete',
            'employee_document_view' => 'employees_document_view',
            'employee_document_create' => 'employees_document_create',
            'employee_document_edit' => 'employees_document_edit',
            'employee_document_delete' => 'employees_document_delete',
            'employee_ledger_view' => 'employees_ledger_view',
            'employee_ledger_create' => 'employees_ledger_create',
            'employee_ledger_edit' => 'employees_ledger_edit',
            'employee_ledger_delete' => 'employees_ledger_delete',
            'employee_voucher_view' => 'employees_voucher_view',
            'employee_voucher_create' => 'employees_voucher_create',
            'employee_voucher_edit' => 'employees_voucher_edit',
            'employee_voucher_delete' => 'employees_voucher_delete',
            // Legacy DB typo "Smis" → Sims
            'smis_sim_view' => 'sims_sim_view',
            'smis_sim_create' => 'sims_sim_create',
            'smis_sim_edit' => 'sims_sim_edit',
            'smis_sim_delete' => 'sims_sim_delete',
            'smis_companies_view' => 'sims_companies_view',
            'smis_companies_create' => 'sims_companies_create',
            'smis_companies_edit' => 'sims_companies_edit',
            'smis_companies_delete' => 'sims_companies_delete',
            'smis_invoices_view' => 'sims_invoices_view',
            'smis_invoices_create' => 'sims_invoices_create',
            'smis_invoices_edit' => 'sims_invoices_edit',
            'smis_invoices_delete' => 'sims_invoices_delete',
            'smis_payments_view' => 'sims_payments_view',
            'smis_payments_create' => 'sims_payments_create',
            'smis_payments_edit' => 'sims_payments_edit',
            'smis_payments_delete' => 'sims_payments_delete',
            'smis_assign_view' => 'sims_assign_view',
            'smis_assign_create' => 'sims_assign_create',
            'smis_assign_edit' => 'sims_assign_edit',
            'smis_assign_delete' => 'sims_assign_delete',
            'smis_export_data_create' => 'sims_export_data_create',
        ];

        foreach ($permissionMap as $oldName => $newName) {
            $oldPermission = Permission::query()->where('name', $oldName)->where('guard_name', 'web')->first();
            $newPermission = Permission::query()->where('name', $newName)->where('guard_name', 'web')->first();
            if (! $oldPermission || ! $newPermission) {
                continue;
            }

            foreach (Role::query()->where('guard_name', 'web')->get() as $role) {
                if ($role->hasPermissionTo($oldPermission) && ! $role->hasPermissionTo($newPermission)) {
                    $role->givePermissionTo($newPermission);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected static function giveTreeToAdminRoles(int $permissionId): void
    {
        $permission = Permission::query()->find($permissionId);
        if ($permission && PermissionTreeBuilder::isLeaf($permission)) {
            self::giveToAdminRoles($permission);
        }

        foreach (Permission::query()->where('parent_id', $permissionId)->get() as $child) {
            self::giveTreeToAdminRoles((int) $child->id);
        }
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
