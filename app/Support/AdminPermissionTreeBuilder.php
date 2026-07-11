<?php

namespace App\Support;

use App\Models\AdminPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPermissionTreeBuilder
{
    public const STANDARD_ACTIONS = ['view', 'create', 'edit', 'delete'];

    public static function slugify(string $name): string
    {
        return str_replace(' ', '_', strtolower(trim($name)));
    }

    public static function isLeaf(AdminPermission $permission): bool
    {
        return ! AdminPermission::query()->where('parent_id', $permission->id)->exists();
    }

    public static function displayLabelForLeaf(string $permissionName): string
    {
        if (preg_match('/_(view|create|edit|delete)$/', $permissionName, $matches)) {
            return ucfirst($matches[1]);
        }

        $parts = explode('_', $permissionName);

        return ucwords(str_replace('_', ' ', (string) (end($parts) ?: $permissionName)));
    }

    /**
     * @return list<array{module: AdminPermission, moduleName: string, submodules: list<array{name: string, permission: AdminPermission, leaves: list<array{id: int, name: string, full_name: string}>}>, leaves: list<array{id: int, name: string, full_name: string}>}>
     */
    public static function modulesForRoleAssignment(): array
    {
        if (! Schema::connection('mysql_admin')->hasColumn('admin_permissions', 'parent_id')) {
            return [];
        }

        $roots = AdminPermission::query()
            ->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->orderBy('name')
            ->get();

        $modules = [];

        foreach ($roots as $module) {
            $children = AdminPermission::query()
                ->where('parent_id', $module->id)
                ->orderBy('name')
                ->get();

            $submodules = [];
            $directLeaves = [];

            foreach ($children as $child) {
                if (self::isLeaf($child)) {
                    $directLeaves[] = self::leafMeta($child);

                    continue;
                }

                $leaves = [];
                foreach (AdminPermission::query()->where('parent_id', $child->id)->orderBy('name')->get() as $grandchild) {
                    if (self::isLeaf($grandchild)) {
                        $leaves[] = self::leafMeta($grandchild);
                    }
                }

                $submodules[] = [
                    'name' => $child->name,
                    'permission' => $child,
                    'leaves' => $leaves,
                ];
            }

            $modules[] = [
                'module' => $module,
                'moduleName' => $module->name,
                'submodules' => $submodules,
                'leaves' => $directLeaves,
            ];
        }

        return $modules;
    }

    /**
     * @return array{id: int, name: string, full_name: string}
     */
    protected static function leafMeta(AdminPermission $permission): array
    {
        return [
            'id' => (int) $permission->id,
            'name' => self::displayLabelForLeaf($permission->name),
            'full_name' => $permission->name,
        ];
    }

    /**
     * @param  list<string>  $submoduleNames
     * @param  list<string>  $extraPermissions
     */
    public static function syncModuleTree(
        AdminPermission $module,
        string $moduleSlug,
        array $submoduleNames,
        array $extraPermissions = []
    ): void {
        $submoduleNames = array_values(array_unique(array_filter(array_map(
            static fn ($name) => trim((string) $name),
            $submoduleNames
        ))));

        $extraPermissions = array_values(array_unique(array_filter(array_map(
            static function ($value) {
                $value = trim((string) $value);

                return $value === '' ? null : self::slugify($value);
            },
            $extraPermissions
        ))));

        if ($submoduleNames !== []) {
            self::removeDirectLeafChildren($module);
            self::syncSubmoduleGroups($module, $moduleSlug, $submoduleNames);

            return;
        }

        self::removeSubmoduleGroups($module);
        self::syncDirectCrudLeaves($module, $moduleSlug);
        self::syncDirectExtraLeaves($module, $moduleSlug, $extraPermissions);
    }

    /**
     * @return list<string>
     */
    public static function submoduleNamesForModule(AdminPermission $module): array
    {
        return AdminPermission::query()
            ->where('parent_id', $module->id)
            ->orderBy('name')
            ->get()
            ->filter(static fn (AdminPermission $child) => ! self::isLeaf($child))
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function customLeafNamesForModule(AdminPermission $module, string $moduleSlug): array
    {
        $standard = array_map(
            static fn (string $action) => $moduleSlug . '_' . $action,
            self::STANDARD_ACTIONS
        );

        return AdminPermission::query()
            ->where('parent_id', $module->id)
            ->orderBy('name')
            ->get()
            ->filter(static fn (AdminPermission $child) => self::isLeaf($child))
            ->pluck('name')
            ->reject(static fn (string $name) => in_array($name, $standard, true))
            ->map(static function (string $name) use ($moduleSlug) {
                $prefix = $moduleSlug . '_';

                return str_starts_with($name, $prefix)
                    ? str_replace('_', ' ', substr($name, strlen($prefix)))
                    : $name;
            })
            ->values()
            ->all();
    }

    public static function deleteTree(int $permissionId): void
    {
        $children = AdminPermission::query()->where('parent_id', $permissionId)->get();
        foreach ($children as $child) {
            self::deleteTree((int) $child->id);
        }

        DB::connection('mysql_admin')
            ->table('admin_role_has_permissions')
            ->where('admin_permission_id', $permissionId)
            ->delete();

        DB::connection('mysql_admin')
            ->table('admin_model_has_permissions')
            ->where('admin_permission_id', $permissionId)
            ->delete();

        AdminPermission::query()->where('id', $permissionId)->delete();
    }

    /**
     * @param  list<string>  $submoduleNames
     */
    protected static function syncSubmoduleGroups(
        AdminPermission $module,
        string $moduleSlug,
        array $submoduleNames
    ): void {
        $desiredGroupIds = [];

        foreach ($submoduleNames as $submoduleName) {
            $subSlug = self::slugify($submoduleName);
            $subGroup = AdminPermission::query()->firstOrCreate(
                [
                    'name' => $submoduleName,
                    'parent_id' => $module->id,
                ],
                []
            );

            $desiredGroupIds[] = (int) $subGroup->id;
            $desiredLeafNames = [];

            foreach (self::STANDARD_ACTIONS as $action) {
                $desiredLeafNames[] = $moduleSlug . '_' . $subSlug . '_' . $action;
            }

            foreach ($desiredLeafNames as $leafName) {
                AdminPermission::query()->firstOrCreate(
                    ['name' => $leafName, 'parent_id' => $subGroup->id],
                    []
                );
            }

            AdminPermission::query()
                ->where('parent_id', $subGroup->id)
                ->whereNotIn('name', $desiredLeafNames)
                ->delete();
        }

        AdminPermission::query()
            ->where('parent_id', $module->id)
            ->whereNotIn('id', $desiredGroupIds)
            ->get()
            ->each(static function (AdminPermission $group): void {
                self::deleteTree((int) $group->id);
            });
    }

    protected static function syncDirectCrudLeaves(AdminPermission $module, string $moduleSlug): void
    {
        foreach (self::STANDARD_ACTIONS as $action) {
            AdminPermission::query()->firstOrCreate(
                ['name' => $moduleSlug . '_' . $action, 'parent_id' => $module->id],
                []
            );
        }
    }

    /**
     * @param  list<string>  $extraSlugs
     */
    protected static function syncDirectExtraLeaves(
        AdminPermission $module,
        string $moduleSlug,
        array $extraSlugs
    ): void {
        $desiredNames = array_map(
            static fn (string $extra) => $moduleSlug . '_' . $extra,
            $extraSlugs
        );

        foreach ($extraSlugs as $extra) {
            AdminPermission::query()->firstOrCreate(
                ['name' => $moduleSlug . '_' . $extra, 'parent_id' => $module->id],
                []
            );
        }

        $standard = array_map(
            static fn (string $action) => $moduleSlug . '_' . $action,
            self::STANDARD_ACTIONS
        );

        AdminPermission::query()
            ->where('parent_id', $module->id)
            ->whereNotIn('name', array_merge($standard, $desiredNames))
            ->delete();
    }

    protected static function removeDirectLeafChildren(AdminPermission $module): void
    {
        AdminPermission::query()
            ->where('parent_id', $module->id)
            ->get()
            ->filter(static fn (AdminPermission $child) => self::isLeaf($child))
            ->each(static function (AdminPermission $leaf): void {
                self::deleteTree((int) $leaf->id);
            });
    }

    protected static function removeSubmoduleGroups(AdminPermission $module): void
    {
        AdminPermission::query()
            ->where('parent_id', $module->id)
            ->get()
            ->filter(static fn (AdminPermission $child) => ! self::isLeaf($child))
            ->each(static function (AdminPermission $group): void {
                self::deleteTree((int) $group->id);
            });
    }
}
