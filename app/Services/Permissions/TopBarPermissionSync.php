<?php

namespace App\Services\Permissions;

use App\Models\BikeTopCategory;
use App\Models\ChequeTopCategory;
use App\Models\EmployeeTopCategory;
use App\Models\ErpModuleTopCategory;
use App\Models\RiderTopCategory;
use App\Support\DynamicPermissionModules;
use App\Support\ErpModuleRegistry;
use App\Support\PermissionTreeBuilder;
use App\Support\RoleFieldAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates / maintains Spatie permissions for each Top Bar category so they
 * appear under the "Top Bars" module in role assignment (View toggle per bar).
 *
 * Leaf name: top_bars_{storage}_{categoryId}_view
 * Group name (UI label): "{ModuleLabel}: {CategoryName}"
 */
class TopBarPermissionSync
{
    public const SLUG = 'top_bars';

    /**
     * @return array<string, string> storage key => human module label
     */
    public static function storageLabels(): array
    {
        return [
            'rider' => 'Riders',
            'bike' => 'Bikes',
            'employee' => 'Employees',
            'cheque' => 'Cheques',
        ];
    }

    public static function storageKeyForModule(string $moduleKey): string
    {
        $moduleKey = ErpModuleRegistry::resolveTopBarModuleKey($moduleKey);

        return match (true) {
            in_array($moduleKey, ['riders', 'rider_settings'], true) => 'rider',
            in_array($moduleKey, ['bikes', 'bike_list', 'bike_settings'], true) => 'bike',
            in_array($moduleKey, ['employees', 'employee_settings'], true) => 'employee',
            in_array($moduleKey, ['cheques', 'cheques_settings'], true) => 'cheque',
            default => 'erp',
        };
    }

    public static function leafName(string $storageKey, int $categoryId): string
    {
        return self::SLUG . '_' . $storageKey . '_' . $categoryId . '_view';
    }

    public static function groupLabel(string $storageKey, string $categoryName, ?string $moduleKey = null): string
    {
        $categoryName = trim($categoryName) !== '' ? trim($categoryName) : 'Untitled';

        if ($storageKey === 'erp') {
            $resolved = $moduleKey
                ? ErpModuleRegistry::resolveTopBarModuleKey($moduleKey)
                : 'module';
            $moduleLabel = (string) (
                config('menu_labels.defaults.' . $resolved)
                ?? ucwords(str_replace('_', ' ', $resolved))
            );

            return $moduleLabel . ': ' . $categoryName;
        }

        $moduleLabel = self::storageLabels()[$storageKey] ?? ucfirst($storageKey);

        return $moduleLabel . ': ' . $categoryName;
    }

    /**
     * @return array<string, class-string<Model>>
     */
    public static function storageCategoryModels(): array
    {
        return [
            'rider' => RiderTopCategory::class,
            'bike' => BikeTopCategory::class,
            'employee' => EmployeeTopCategory::class,
            'cheque' => ChequeTopCategory::class,
            'erp' => ErpModuleTopCategory::class,
        ];
    }

    /**
     * @return array{storage: string, id: int}|null
     */
    public static function parseCategoryLeafName(?string $name): ?array
    {
        if (! preg_match(
            '/^' . preg_quote(self::SLUG, '/') . '_(rider|bike|employee|cheque|erp)_(\d+)_view$/',
            (string) $name,
            $m
        )) {
            return null;
        }

        return ['storage' => $m[1], 'id' => (int) $m[2]];
    }

    /**
     * Category keys for the active company ("rider:12"). Company scope applies.
     *
     * @return array<string, true>
     */
    public static function currentCompanyCategoryKeySet(): array
    {
        $set = [];
        foreach (self::storageCategoryModels() as $storage => $class) {
            if (! class_exists($class)) {
                continue;
            }
            try {
                foreach ($class::query()->pluck('id') as $id) {
                    $set[$storage . ':' . (int) $id] = true;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $set;
    }

    /**
     * Display labels keyed by "storage:id" using the live category name (no uniqueness suffix).
     *
     * @return array<string, string>
     */
    public static function currentCompanyCategoryLabels(): array
    {
        $labels = [];
        foreach (self::storageCategoryModels() as $storage => $class) {
            if (! class_exists($class)) {
                continue;
            }
            try {
                $columns = ['id', 'name'];
                if ($storage === 'erp') {
                    $columns[] = 'module_key';
                }
                foreach ($class::query()->get($columns) as $category) {
                    $labels[$storage . ':' . (int) $category->id] = self::groupLabel(
                        $storage,
                        (string) ($category->name ?? ''),
                        $storage === 'erp' ? (string) ($category->module_key ?? 'module') : null
                    );
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $labels;
    }

    /**
     * Category ids for the active company, grouped by storage key.
     *
     * @return array<string, list<int>>
     */
    public static function currentCompanyCategoryIdsByStorage(): array
    {
        $byStorage = [];
        foreach (self::storageCategoryModels() as $storage => $class) {
            if (! class_exists($class)) {
                $byStorage[$storage] = [];
                continue;
            }
            try {
                $byStorage[$storage] = $class::query()
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                $byStorage[$storage] = [];
            }
        }

        return $byStorage;
    }

    public static function syncForCategory(string $moduleKey, Model $category, bool $assignToAdminRoles = true): void
    {
        if (! self::permissionsReady()) {
            return;
        }

        $storageKey = self::storageKeyForModule($moduleKey);
        $categoryId = (int) $category->getKey();
        if ($categoryId <= 0) {
            return;
        }

        $moduleKeyOnModel = $category->getAttribute('module_key');
        $label = self::groupLabel(
            $storageKey,
            (string) ($category->getAttribute('name') ?? ''),
            is_string($moduleKeyOnModel) ? $moduleKeyOnModel : $moduleKey
        );
        // Prefix keeps Spatie global name uniqueness across modules.
        $label = 'Top Bar — ' . $label;

        $root = self::ensureRoot();
        $leafName = self::leafName($storageKey, $categoryId);
        $leaf = Permission::query()->where('name', $leafName)->where('guard_name', 'web')->first();

        if ($leaf) {
            $group = Permission::query()->find((int) $leaf->parent_id);
            if (! $group || (int) $group->parent_id !== (int) $root->id) {
                $group = self::createGroup($root, $label, (int) $leaf->id);
                $leaf->update(['parent_id' => $group->id]);
            } else {
                self::renameGroupUnique($root, $group, $label);
            }
        } else {
            $group = self::createGroup($root, $label);
            $leaf = Permission::query()->create([
                'name' => $leafName,
                'guard_name' => 'web',
                'parent_id' => $group->id,
            ]);
            if ($assignToAdminRoles) {
                self::giveToAdminRoles($leaf);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        RoleFieldAccess::flush();
    }

    public static function removeForCategory(string $moduleKey, int $categoryId): void
    {
        if (! self::permissionsReady() || $categoryId <= 0) {
            return;
        }

        $leafName = self::leafName(self::storageKeyForModule($moduleKey), $categoryId);
        $leaf = Permission::query()->where('name', $leafName)->where('guard_name', 'web')->first();
        if (! $leaf) {
            return;
        }

        // Delete the whole category group (includes nested Top Bar value permissions).
        $groupId = (int) ($leaf->parent_id ?? 0);
        if ($groupId > 0) {
            PermissionTreeBuilder::deleteTree($groupId);
        } else {
            PermissionTreeBuilder::deleteTree((int) $leaf->id);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        RoleFieldAccess::flush();
    }

    /**
     * Sync every Top Bar category from every module (dedicated + generic ERP modules).
     * Uses withoutGlobalScope('company') so company A reconcile cannot prune company B trees.
     */
    public static function syncAll(bool $assignToAdminRoles = true): void
    {
        if (! self::permissionsReady()) {
            return;
        }

        $desired = [];

        foreach (self::allCategories(RiderTopCategory::class) as $category) {
            self::syncForCategory('riders', $category, $assignToAdminRoles);
            $desired[] = self::leafName('rider', (int) $category->id);
        }
        foreach (self::allCategories(BikeTopCategory::class) as $category) {
            self::syncForCategory('bike_list', $category, $assignToAdminRoles);
            $desired[] = self::leafName('bike', (int) $category->id);
        }
        foreach (self::allCategories(EmployeeTopCategory::class) as $category) {
            self::syncForCategory('employees', $category, $assignToAdminRoles);
            $desired[] = self::leafName('employee', (int) $category->id);
        }
        foreach (self::allCategories(ChequeTopCategory::class) as $category) {
            self::syncForCategory('cheques', $category, $assignToAdminRoles);
            $desired[] = self::leafName('cheque', (int) $category->id);
        }
        // Generic storage: garages, customers, vendors, sims, visa_expense, rta_fines_*, etc.
        foreach (self::allCategories(ErpModuleTopCategory::class) as $category) {
            $moduleKey = (string) ($category->module_key ?? 'module');
            self::syncForCategory($moduleKey, $category, $assignToAdminRoles);
            $desired[] = self::leafName('erp', (int) $category->id);
        }

        self::pruneOrphans($desired);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        RoleFieldAccess::flush();
    }

    public static function isEnforced(): bool
    {
        if (! self::permissionsReady()) {
            return false;
        }

        return Permission::query()
            ->where('name', DynamicPermissionModules::TOP_BARS)
            ->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->exists();
    }

    public static function canAccess(string $moduleKey, int $categoryId): bool
    {
        if (RoleFieldAccess::isAdmin()) {
            return true;
        }

        if ($categoryId <= 0) {
            return false;
        }

        $leaf = self::leafName(self::storageKeyForModule($moduleKey), $categoryId);
        if (! RoleFieldAccess::permissionExistsPublic($leaf)) {
            return true;
        }

        return RoleFieldAccess::hasExactPermission($leaf);
    }

    public static function canAccessCategory(string $moduleKey, Model $category): bool
    {
        return self::canAccess($moduleKey, (int) $category->getKey());
    }

    /**
     * @param  Collection<int, Model>  $categories
     * @return Collection<int, Model>
     */
    public static function filterCategories(string $moduleKey, Collection $categories): Collection
    {
        if (RoleFieldAccess::isAdmin() || ! self::isEnforced()) {
            return $categories->values();
        }

        return $categories
            ->filter(fn(Model $category) => self::canAccessCategory($moduleKey, $category))
            ->values();
    }

    protected static function permissionsReady(): bool
    {
        try {
            $table = config('permission.table_names.permissions', 'permissions');

            return Schema::hasTable($table) && Schema::hasColumn($table, 'parent_id');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected static function ensureRoot(): Permission
    {
        $root = Permission::query()->firstOrCreate(
            ['name' => DynamicPermissionModules::TOP_BARS, 'guard_name' => 'web'],
            ['parent_id' => null]
        );
        if ($root->parent_id !== null) {
            $root->update(['parent_id' => null]);
        }

        return $root;
    }

    /**
     * One UI group per top-bar category (never share groups between leaves).
     */
    protected static function createGroup(Permission $root, string $label, ?int $ignoreGroupId = null): Permission
    {
        return Permission::query()->create([
            'name' => self::uniqueGroupName($root, $label, $ignoreGroupId),
            'guard_name' => 'web',
            'parent_id' => $root->id,
        ]);
    }

    protected static function renameGroupUnique(Permission $root, Permission $group, string $label): void
    {
        $unique = self::uniqueGroupName($root, $label, (int) $group->id);
        if ($group->name !== $unique) {
            $group->update(['name' => $unique]);
        }
    }

    protected static function uniqueGroupName(Permission $root, string $label, ?int $ignoreGroupId = null): string
    {
        $base = $label !== '' ? $label : 'Top Bar';
        $candidate = $base;
        $i = 2;
        while (
            Permission::query()
            ->where('parent_id', $root->id)
            ->where('name', $candidate)
            ->when($ignoreGroupId, fn($q) => $q->where('id', '!=', $ignoreGroupId))
            ->exists()
        ) {
            $candidate = $base . ' (' . $i . ')';
            $i++;
        }

        return $candidate;
    }

    /**
     * Remove category permission groups that no longer map to a live Top Bar.
     * Nested Top Bar value groups under a category are left alone unless the whole category is removed.
     *
     * @param  list<string>  $desiredLeafNames
     */
    protected static function pruneOrphans(array $desiredLeafNames): void
    {
        $root = Permission::query()
            ->where('name', DynamicPermissionModules::TOP_BARS)
            ->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->first();
        if (! $root) {
            return;
        }

        $desiredSet = array_fill_keys($desiredLeafNames, true);
        $groups = Permission::query()->where('parent_id', $root->id)->get();
        foreach ($groups as $group) {
            if (! str_starts_with((string) $group->name, DynamicPermissionModules::TOP_BAR_GROUP_PREFIX)) {
                continue;
            }

            $categoryLeaves = Permission::query()
                ->where('parent_id', $group->id)
                ->where('name', 'like', self::SLUG . '_%')
                ->get();

            $keepGroup = false;
            foreach ($categoryLeaves as $leaf) {
                if (isset($desiredSet[$leaf->name])) {
                    $keepGroup = true;
                } else {
                    PermissionTreeBuilder::deleteTree((int) $leaf->id);
                }
            }

            if (! $keepGroup) {
                // Drop the category group and any nested value permissions under it.
                PermissionTreeBuilder::deleteTree((int) $group->id);
            }
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return Collection<int, Model>
     */
    protected static function allCategories(string $modelClass): Collection
    {
        if (! class_exists($modelClass)) {
            return collect();
        }

        try {
            $table = (new $modelClass)->getTable();
            if (! Schema::hasTable($table)) {
                return collect();
            }
        } catch (\Throwable $e) {
            return collect();
        }

        return $modelClass::query()
            ->withoutGlobalScope('company')
            ->orderBy('id')
            ->get();
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
