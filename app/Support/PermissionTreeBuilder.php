<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;

class PermissionTreeBuilder
{
    public const STANDARD_ACTIONS = ['view', 'create', 'edit', 'delete'];

    /** Actions that may appear as submodule leaves in addition to STANDARD_ACTIONS. */
    public const EXTRA_ACTIONS = ['upload', 'approve', 'reject', 'force_approve', 'reopen', 'revise'];

    /** All actions the Role Permissions UI can toggle (CRUD + known extras). */
    public const ALL_ACTIONS = ['view', 'create', 'edit', 'delete', 'upload', 'approve', 'reject', 'force_approve', 'reopen', 'revise'];

    public static function slugify(string $name): string
    {
        return str_replace(' ', '_', strtolower(trim($name)));
    }

    public static function isLeaf(Permission $permission): bool
    {
        return ! Permission::query()->where('parent_id', $permission->id)->exists();
    }

    public static function displayLabelForLeaf(string $permissionName): string
    {
        if (preg_match('/_(view|create|edit|delete|upload|approve|reject|force_approve|reopen|revise)$/', $permissionName, $matches)) {
            return ucwords(str_replace('_', ' ', $matches[1]));
        }

        $parts = explode('_', $permissionName);

        return ucwords(str_replace('_', ' ', (string) (end($parts) ?: $permissionName)));
    }

    /**
     * Normalize config / request submodule entries into name + extra_actions.
     *
     * Accepts plain strings ("Payments") or arrays:
     * ['name' => 'COD Voucher', 'extra_actions' => ['upload']].
     *
     * @param  list<string|array{name?: string, extra_actions?: list<string>}>  $submodules
     * @return list<array{name: string, extra_actions: list<string>}>
     */
    public static function normalizeSubmoduleDefinitions(array $submodules): array
    {
        $out = [];
        $seen = [];

        foreach ($submodules as $entry) {
            if (is_string($entry)) {
                $name = trim($entry);
                $extraActions = [];
            } elseif (is_array($entry)) {
                $name = trim((string) ($entry['name'] ?? ''));
                $extraActions = array_values(array_unique(array_filter(array_map(
                    static fn ($a) => strtolower(trim((string) $a)),
                    (array) ($entry['extra_actions'] ?? [])
                ), static fn (string $a) => $a !== '' && in_array($a, self::EXTRA_ACTIONS, true))));
            } else {
                continue;
            }

            if ($name === '' || isset($seen[$name])) {
                continue;
            }

            $seen[$name] = true;
            $out[] = [
                'name' => $name,
                'extra_actions' => $extraActions,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{module: Permission, moduleName: string, submodules: list<array{name: string, permission: Permission, leaves: list<array{id: int, name: string, full_name: string}>}>, leaves: list<array{id: int, name: string, full_name: string}>}>
     */
    public static function modulesForRoleAssignment(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('permissions', 'parent_id')) {
            return [];
        }

        $roots = Permission::query()
            ->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->orderBy('name')
            ->get();

        $modules = [];

        foreach ($roots as $module) {
            $children = Permission::query()
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
                foreach (Permission::query()->where('parent_id', $child->id)->orderBy('name')->get() as $grandchild) {
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
    protected static function leafMeta(Permission $permission): array
    {
        return [
            'id' => (int) $permission->id,
            'name' => self::displayLabelForLeaf($permission->name),
            'full_name' => $permission->name,
        ];
    }

    /**
     * @param  list<string|array{name?: string, extra_actions?: list<string>}>  $submodules
     * @param  list<string>  $extraPermissions
     */
    public static function syncModuleTree(
        Permission $module,
        string $moduleSlug,
        array $submodules,
        array $extraPermissions = [],
        string $guard = 'web'
    ): void {
        $definitions = self::normalizeSubmoduleDefinitions($submodules);

        $extraPermissions = array_values(array_unique(array_filter(array_map(
            static function ($value) {
                $value = trim((string) $value);

                return $value === '' ? null : self::slugify($value);
            },
            $extraPermissions
        ))));

        if ($definitions !== []) {
            self::removeDirectLeafChildren($module);
            self::syncSubmoduleGroups($module, $moduleSlug, $definitions, $guard);

            return;
        }

        self::removeSubmoduleGroups($module);
        self::syncDirectCrudLeaves($module, $moduleSlug, $guard);
        self::syncDirectExtraLeaves($module, $moduleSlug, $extraPermissions, $guard);
    }

    /**
     * @return list<string>
     */
    public static function submoduleNamesForModule(Permission $module): array
    {
        return Permission::query()
            ->where('parent_id', $module->id)
            ->orderBy('name')
            ->get()
            ->filter(static fn (Permission $child) => ! self::isLeaf($child))
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function customLeafNamesForModule(Permission $module, string $moduleSlug): array
    {
        $standard = array_map(
            static fn (string $action) => $moduleSlug . '_' . $action,
            self::STANDARD_ACTIONS
        );

        return Permission::query()
            ->where('parent_id', $module->id)
            ->orderBy('name')
            ->get()
            ->filter(static fn (Permission $child) => self::isLeaf($child))
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
        $children = Permission::query()->where('parent_id', $permissionId)->get();
        foreach ($children as $child) {
            self::deleteTree((int) $child->id);
        }

        Permission::query()->where('id', $permissionId)->delete();
    }

    /**
     * @param  list<array{name: string, extra_actions: list<string>}>  $definitions
     */
    protected static function syncSubmoduleGroups(
        Permission $module,
        string $moduleSlug,
        array $definitions,
        string $guard
    ): void {
        $desiredGroupIds = [];

        foreach ($definitions as $definition) {
            $submoduleName = $definition['name'];
            $extraActions = $definition['extra_actions'];
            $subSlug = self::slugify($submoduleName);
            $subGroup = Permission::query()->firstOrCreate(
                [
                    'name' => $submoduleName,
                    'guard_name' => $guard,
                    'parent_id' => $module->id,
                ],
                []
            );

            $desiredGroupIds[] = (int) $subGroup->id;
            $desiredLeafNames = [];

            foreach (self::STANDARD_ACTIONS as $action) {
                $desiredLeafNames[] = $moduleSlug . '_' . $subSlug . '_' . $action;
            }

            foreach ($extraActions as $action) {
                $desiredLeafNames[] = $moduleSlug . '_' . $subSlug . '_' . $action;
            }

            $desiredLeafNames = array_values(array_unique($desiredLeafNames));

            foreach ($desiredLeafNames as $leafName) {
                Permission::query()->firstOrCreate(
                    ['name' => $leafName, 'guard_name' => $guard],
                    ['parent_id' => $subGroup->id]
                );
            }

            Permission::query()
                ->where('parent_id', $subGroup->id)
                ->whereNotIn('name', $desiredLeafNames)
                ->delete();
        }

        Permission::query()
            ->where('parent_id', $module->id)
            ->whereNotIn('id', $desiredGroupIds)
            ->get()
            ->each(static function (Permission $group): void {
                self::deleteTree((int) $group->id);
            });
    }

    protected static function syncDirectCrudLeaves(Permission $module, string $moduleSlug, string $guard): void
    {
        $desiredNames = [];

        foreach (self::STANDARD_ACTIONS as $action) {
            $desiredNames[] = $moduleSlug . '_' . $action;
        }

        foreach ($desiredNames as $leafName) {
            Permission::query()->firstOrCreate(
                ['name' => $leafName, 'guard_name' => $guard],
                ['parent_id' => $module->id]
            );
        }
    }

    /**
     * @param  list<string>  $extraSlugs
     */
    protected static function syncDirectExtraLeaves(
        Permission $module,
        string $moduleSlug,
        array $extraSlugs,
        string $guard
    ): void {
        $desiredNames = array_map(
            static fn (string $extra) => $moduleSlug . '_' . $extra,
            $extraSlugs
        );

        foreach ($extraSlugs as $extra) {
            Permission::query()->firstOrCreate(
                ['name' => $moduleSlug . '_' . $extra, 'guard_name' => $guard],
                ['parent_id' => $module->id]
            );
        }

        $standard = array_map(
            static fn (string $action) => $moduleSlug . '_' . $action,
            self::STANDARD_ACTIONS
        );

        Permission::query()
            ->where('parent_id', $module->id)
            ->whereNotIn('name', array_merge($standard, $desiredNames))
            ->delete();
    }

    protected static function removeDirectLeafChildren(Permission $module): void
    {
        Permission::query()
            ->where('parent_id', $module->id)
            ->get()
            ->filter(static fn (Permission $child) => self::isLeaf($child))
            ->each(static function (Permission $leaf): void {
                $leaf->delete();
            });
    }

    protected static function removeSubmoduleGroups(Permission $module): void
    {
        Permission::query()
            ->where('parent_id', $module->id)
            ->get()
            ->filter(static fn (Permission $child) => ! self::isLeaf($child))
            ->each(static function (Permission $group): void {
                self::deleteTree((int) $group->id);
            });
    }
}
