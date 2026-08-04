<?php

namespace App\Services\Permissions;

use App\Models\BikeTopOption;
use App\Models\ChequeTopOption;
use App\Models\EmployeeTopOption;
use App\Models\ErpModuleTopCategory;
use App\Models\ErpModuleTopOption;
use App\Models\RiderTopOption;
use App\Support\DynamicPermissionModules;
use App\Support\ErpModuleRegistry;
use App\Support\PermissionTreeBuilder;
use App\Support\RoleFieldAccess;
use App\Support\TopBarNumericStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Spatie permissions for each Top Bar option / value.
 *
 * Nesting (under the category group):
 *   Top Bars → Top Bar — {Module}: {Category}
 *     → {Option name} (group) → top_bar_values_{storage}_{optionId}_view
 *
 * Rider Status options are handled by {@see RiderStatusPermissionSync} instead.
 */
class TopBarOptionPermissionSync
{
    public const SLUG = 'top_bar_values';

    public static function leafName(string $storageKey, int $optionId): string
    {
        return self::SLUG . '_' . $storageKey . '_' . $optionId . '_view';
    }

    public static function isRiderStatusOption(Model $option): bool
    {
        if (! $option instanceof RiderTopOption) {
            return false;
        }

        return RiderStatusPermissionSync::isStatusOption($option);
    }

    public static function syncOption(string $moduleKey, Model $option, bool $assignToAdminRoles = true): void
    {
        if (! self::permissionsReady() || self::isRiderStatusOption($option)) {
            return;
        }

        $optionId = (int) $option->getKey();
        if ($optionId <= 0) {
            return;
        }

        $storageKey = TopBarPermissionSync::storageKeyForModule($moduleKey);
        $category = $option->relationLoaded('category')
            ? $option->category
            : $option->category()->first();

        if (! $category) {
            return;
        }

        // Ensure the parent Top Bar category permission group exists.
        TopBarPermissionSync::syncForCategory($moduleKey, $category, $assignToAdminRoles);

        $categoryGroup = self::categoryGroupFor($storageKey, (int) $category->getKey());
        if (! $categoryGroup) {
            return;
        }

        $optionName = trim((string) ($option->getAttribute('name') ?? ''));
        if ($optionName === '') {
            $optionName = 'Value #' . $optionId;
        }

        $leafName = self::leafName($storageKey, $optionId);
        $leaf = Permission::query()->where('name', $leafName)->where('guard_name', 'web')->first();

        if ($leaf) {
            $group = Permission::query()->find((int) $leaf->parent_id);
            if (! $group || (int) $group->parent_id !== (int) $categoryGroup->id) {
                $group = self::createGroup($categoryGroup, $optionName);
                $leaf->update(['parent_id' => $group->id]);
            } else {
                self::renameGroupUnique($categoryGroup, $group, $optionName);
            }
        } else {
            $group = self::createGroup($categoryGroup, $optionName);
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

    public static function removeOption(string $moduleKey, int $optionId): void
    {
        if (! self::permissionsReady() || $optionId <= 0) {
            return;
        }

        $leaf = Permission::query()
            ->where('name', self::leafName(TopBarPermissionSync::storageKeyForModule($moduleKey), $optionId))
            ->where('guard_name', 'web')
            ->first();
        if (! $leaf) {
            return;
        }

        $groupId = (int) ($leaf->parent_id ?? 0);
        PermissionTreeBuilder::deleteTree((int) $leaf->id);
        if ($groupId > 0 && Permission::query()->where('parent_id', $groupId)->count() === 0) {
            Permission::query()->where('id', $groupId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        RoleFieldAccess::flush();
    }

    /**
     * Remove all value permissions nested under a Top Bar category.
     */
    public static function removeAllForCategory(string $moduleKey, int $categoryId): void
    {
        if (! self::permissionsReady() || $categoryId <= 0) {
            return;
        }

        $categoryGroup = self::categoryGroupFor(
            TopBarPermissionSync::storageKeyForModule($moduleKey),
            $categoryId
        );
        if (! $categoryGroup) {
            return;
        }

        foreach (Permission::query()->where('parent_id', $categoryGroup->id)->get() as $child) {
            if (str_ends_with((string) $child->name, '_view')) {
                continue;
            }
            PermissionTreeBuilder::deleteTree((int) $child->id);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        RoleFieldAccess::flush();
    }

    /**
     * Sync every Top Bar value/option from every module (dedicated + generic).
     */
    public static function syncAll(bool $assignToAdminRoles = true): void
    {
        if (! self::permissionsReady()) {
            return;
        }

        $desired = [];

        foreach (self::allOptions(RiderTopOption::class) as $option) {
            if (self::isRiderStatusOption($option)) {
                continue;
            }
            self::syncOption('riders', $option, $assignToAdminRoles);
            $desired[] = self::leafName('rider', (int) $option->id);
        }
        foreach (self::allOptions(BikeTopOption::class) as $option) {
            self::syncOption('bike_list', $option, $assignToAdminRoles);
            $desired[] = self::leafName('bike', (int) $option->id);
        }
        foreach (self::allOptions(EmployeeTopOption::class) as $option) {
            self::syncOption('employees', $option, $assignToAdminRoles);
            $desired[] = self::leafName('employee', (int) $option->id);
        }
        foreach (self::allOptions(ChequeTopOption::class) as $option) {
            self::syncOption('cheques', $option, $assignToAdminRoles);
            $desired[] = self::leafName('cheque', (int) $option->id);
        }
        foreach (self::allOptions(ErpModuleTopOption::class) as $option) {
            $moduleKey = (string) ($option->category?->module_key ?? 'module');
            self::syncOption($moduleKey, $option, $assignToAdminRoles);
            $desired[] = self::leafName('erp', (int) $option->id);
        }

        self::pruneOrphans($desired);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        RoleFieldAccess::flush();
    }

    public static function canAccess(string $moduleKey, int $optionId): bool
    {
        if (RoleFieldAccess::isAdmin()) {
            return true;
        }
        if ($optionId <= 0) {
            return false;
        }

        $leaf = self::leafName(TopBarPermissionSync::storageKeyForModule($moduleKey), $optionId);
        if (! RoleFieldAccess::permissionExistsPublic($leaf)) {
            return true;
        }

        return RoleFieldAccess::hasExactPermission($leaf);
    }

    public static function canAccessOption(string $moduleKey, Model $option): bool
    {
        if (self::isRiderStatusOption($option)) {
            return RiderStatusPermissionSync::canAccessOptionId((int) $option->getKey());
        }

        return self::canAccess($moduleKey, (int) $option->getKey());
    }

    /**
     * @param  Collection<int, Model>  $options
     * @return Collection<int, Model>
     */
    public static function filterOptions(string $moduleKey, Collection $options): Collection
    {
        if (RoleFieldAccess::isAdmin() || ! TopBarPermissionSync::isEnforced()) {
            return $options->values();
        }

        return $options
            ->filter(fn (Model $option) => self::canAccessOption($moduleKey, $option))
            ->values();
    }

    /**
     * Restrict a module listing query to Top Bar values the user may access.
     *
     * @deprecated Record listings are no longer restricted by Top Bar value permissions.
     *             Permissions only control filter / value visibility.
     */
    public static function applyListingRestrictions(Builder $query, string $moduleKey): void
    {
        // Intentionally no-op: Top Bar permissions must not hide records.
    }

    /**
     * @return Collection<int, Model>
     */
    protected static function categoriesForModule(string $moduleKey, array $config): Collection
    {
        if (($config['storage'] ?? '') === 'generic') {
            return ErpModuleTopCategory::query()
                ->with('options')
                ->where('module_key', $moduleKey)
                ->orderBy('id')
                ->get();
        }

        $modelClass = $config['category_model'] ?? null;
        if (! $modelClass || ! class_exists($modelClass)) {
            return collect();
        }

        return $modelClass::query()->with('options')->orderBy('id')->get();
    }

    protected static function categoryColumn(array $config, Model $category): ?string
    {
        $attr = (string) ($config['column_attribute'] ?? 'db_column');
        if (($config['storage'] ?? '') === 'generic') {
            $attr = 'db_column';
        }

        $column = trim((string) ($category->{$attr} ?? ''));

        return $column !== '' ? $column : null;
    }

    protected static function fallbackTable(string $moduleKey): string
    {
        return match (TopBarPermissionSync::storageKeyForModule($moduleKey)) {
            'rider' => 'riders',
            'bike' => 'bikes',
            'employee' => 'employees',
            'cheque' => 'cheques',
            default => (string) (ErpModuleRegistry::topBarConfig($moduleKey)['source_table'] ?? ''),
        };
    }

    protected static function isDateishColumn(array $config, string $table, string $column): bool
    {
        $configured = $config['date_columns'] ?? [];
        if (is_array($configured) && in_array($column, $configured, true)) {
            return true;
        }

        if (str_contains($column, 'date') || str_ends_with($column, '_at')) {
            return true;
        }

        try {
            $type = Schema::getColumnType($table, $column);

            return in_array($type, ['date', 'datetime', 'datetimetz', 'timestamp'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected static function columnLooksNumeric(string $table, string $column): bool
    {
        if (str_ends_with($column, '_id') || $column === 'status') {
            return true;
        }

        return TopBarNumericStatus::isNumericStatusColumn($table, $column);
    }

    protected static function categoryGroupFor(string $storageKey, int $categoryId): ?Permission
    {
        $catLeaf = Permission::query()
            ->where('name', TopBarPermissionSync::leafName($storageKey, $categoryId))
            ->where('guard_name', 'web')
            ->first();
        if (! $catLeaf || ! $catLeaf->parent_id) {
            return null;
        }

        return Permission::query()->find((int) $catLeaf->parent_id);
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

    protected static function createGroup(Permission $parent, string $label): Permission
    {
        return Permission::query()->create([
            'name' => self::uniqueGroupName($parent, $label),
            'guard_name' => 'web',
            'parent_id' => $parent->id,
        ]);
    }

    protected static function renameGroupUnique(Permission $parent, Permission $group, string $label): void
    {
        $unique = self::uniqueGroupName($parent, $label, (int) $group->id);
        if ($group->name !== $unique) {
            $group->update(['name' => $unique]);
        }
    }

    protected static function uniqueGroupName(Permission $parent, string $label, ?int $ignoreGroupId = null): string
    {
        $base = $label !== '' ? $label : 'Value';
        $candidate = $base;
        $i = 2;
        while (
            Permission::query()
                ->where('parent_id', $parent->id)
                ->where('name', $candidate)
                ->when($ignoreGroupId, fn ($q) => $q->where('id', '!=', $ignoreGroupId))
                ->exists()
        ) {
            $candidate = $base . ' (' . $i . ')';
            $i++;
        }

        return $candidate;
    }

    /**
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

        // Nested under category groups.
        foreach (Permission::query()->where('parent_id', $root->id)->get() as $categoryGroup) {
            if (! str_starts_with((string) $categoryGroup->name, DynamicPermissionModules::TOP_BAR_GROUP_PREFIX)) {
                // Legacy flat "Top Bar Value — …" groups at root — remove if orphaned.
                if (str_starts_with((string) $categoryGroup->name, 'Top Bar Value — ')) {
                    foreach (Permission::query()->where('parent_id', $categoryGroup->id)->get() as $leaf) {
                        if (! in_array($leaf->name, $desiredLeafNames, true)) {
                            PermissionTreeBuilder::deleteTree((int) $leaf->id);
                        }
                    }
                    if (Permission::query()->where('parent_id', $categoryGroup->id)->count() === 0) {
                        $categoryGroup->delete();
                    }
                }
                continue;
            }

            foreach (Permission::query()->where('parent_id', $categoryGroup->id)->get() as $child) {
                if (str_ends_with((string) $child->name, '_view')) {
                    continue;
                }
                foreach (Permission::query()->where('parent_id', $child->id)->get() as $leaf) {
                    if (! in_array($leaf->name, $desiredLeafNames, true)) {
                        PermissionTreeBuilder::deleteTree((int) $leaf->id);
                    }
                }
                if (Permission::query()->where('parent_id', $child->id)->count() === 0) {
                    $child->delete();
                }
            }
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return Collection<int, Model>
     */
    protected static function allOptions(string $modelClass): Collection
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
            ->with(['category' => fn ($q) => $q->withoutGlobalScope('company')])
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
