<?php

namespace App\Services\Permissions;

use App\Models\RiderTopCategory;
use App\Models\RiderTopOption;
use App\Support\DynamicPermissionModules;
use App\Support\PermissionTreeBuilder;
use App\Support\RoleFieldAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates / maintains Spatie permissions for each Rider Status option
 * (RiderTopOption under rider_column = rider_status).
 *
 * Leaf name: rider_statuses_{optionId}_view
 * Group name (UI label): status option name
 */
class RiderStatusPermissionSync
{
    public const SLUG = 'rider_statuses';

    /** Spatie leaf for the "Change Rider Status" capability toggle. */
    public const CHANGE_LEAF = 'rider_statuses_change_view';

    public const CHANGE_GROUP = 'Change Rider Status';

    public static function leafName(int $optionId): string
    {
        return self::SLUG . '_' . $optionId . '_view';
    }

    public static function isStatusOption(RiderTopOption $option): bool
    {
        $category = $option->relationLoaded('category')
            ? $option->category
            : RiderTopCategory::query()
                ->withoutGlobalScope('company')
                ->find((int) $option->category_id);

        return $category
            && trim((string) ($category->rider_column ?? '')) === 'rider_status';
    }

    public static function statusCategoryId(): ?int
    {
        $id = RiderTopCategory::query()
            ->where('rider_column', 'rider_status')
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    public static function syncOption(RiderTopOption $option, bool $assignToAdminRoles = true): void
    {
        if (! self::permissionsReady() || ! self::isStatusOption($option)) {
            return;
        }

        $optionId = (int) $option->getKey();
        if ($optionId <= 0) {
            return;
        }

        $label = trim((string) $option->name);
        if ($label === '') {
            $label = 'Status #' . $optionId;
        }
        $label = 'Rider Status — ' . $label;

        $root = self::ensureRoot();
        $leafName = self::leafName($optionId);
        $leaf = Permission::query()->where('name', $leafName)->where('guard_name', 'web')->first();

        if ($leaf) {
            $group = Permission::query()->find((int) $leaf->parent_id);
            if (! $group || (int) $group->parent_id !== (int) $root->id) {
                $group = self::createGroup($root, $label);
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

    public static function removeOption(int $optionId): void
    {
        if (! self::permissionsReady() || $optionId <= 0) {
            return;
        }

        $leaf = Permission::query()
            ->where('name', self::leafName($optionId))
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

    public static function syncAll(bool $assignToAdminRoles = true): void
    {
        if (! self::permissionsReady()) {
            return;
        }

        $desired = [];
        $desired[] = self::CHANGE_LEAF;
        self::ensureChangePermission($assignToAdminRoles);

        $statusCategoryIds = RiderTopCategory::query()
            ->withoutGlobalScope('company')
            ->where('rider_column', 'rider_status')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($statusCategoryIds !== []) {
            $options = RiderTopOption::query()
                ->withoutGlobalScope('company')
                ->with(['category' => fn ($q) => $q->withoutGlobalScope('company')])
                ->whereIn('category_id', $statusCategoryIds)
                ->orderBy('id')
                ->get();

            foreach ($options as $option) {
                self::syncOption($option, $assignToAdminRoles);
                $desired[] = self::leafName((int) $option->id);
            }
        }

        self::pruneOrphans($desired);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        RoleFieldAccess::flush();
    }

    /**
     * Remove status permissions when the rider_status category is deleted
     * (DB cascade may skip Eloquent option deleted events).
     */
    public static function removeAllForCategory(int $categoryId): void
    {
        if (! self::permissionsReady()) {
            return;
        }

        $statusCategoryId = self::statusCategoryId();
        // Category already deleted — remove every rider_statuses_* leaf.
        if ($statusCategoryId === null || $statusCategoryId === $categoryId) {
            $root = Permission::query()
                ->where('name', DynamicPermissionModules::RIDER_STATUSES)
                ->where(function ($q) {
                    $q->whereNull('parent_id')->orWhere('parent_id', 0);
                })
                ->first();
            if ($root) {
                PermissionTreeBuilder::deleteTree((int) $root->id);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
                RoleFieldAccess::flush();
            }
        }
    }

    public static function isEnforced(): bool
    {
        if (! self::permissionsReady()) {
            return false;
        }

        return Permission::query()
            ->where('name', DynamicPermissionModules::RIDER_STATUSES)
            ->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->exists();
    }

    /**
     * Whether Rider Status permission leaves should restrict filters/dropdowns for the current user.
     * Driven by roles.enforce_rider_status_permissions (any role with the flag off opts out).
     */
    public static function isEnforcedForCurrentUser(): bool
    {
        if (RoleFieldAccess::isAdmin()) {
            return false;
        }

        if (! self::isEnforced()) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (! Schema::hasColumn('roles', 'enforce_rider_status_permissions')) {
            return true;
        }

        $roles = method_exists($user, 'roles') ? $user->roles : collect();
        if ($roles->isEmpty()) {
            return false;
        }

        // Opt-out: if any role has the switch disabled, ignore status permission leaves.
        return $roles->every(static fn ($role): bool => (bool) ($role->enforce_rider_status_permissions ?? true));
    }

    /**
     * Whether the current user may change a rider's status (set / clear / toggle).
     * Independent of which status values they may see in filters.
     */
    public static function canChangeRiderStatus(): bool
    {
        if (RoleFieldAccess::isAdmin()) {
            return true;
        }

        if (! self::permissionsReady()) {
            return true;
        }

        if (! RoleFieldAccess::permissionExistsPublic(self::CHANGE_LEAF)) {
            return true;
        }

        return RoleFieldAccess::hasExactPermission(self::CHANGE_LEAF);
    }

    /**
     * Ensure the Change Rider Status toggle exists under the Rider Statuses root.
     */
    public static function ensureChangePermission(bool $assignToAdminRoles = true): void
    {
        if (! self::permissionsReady()) {
            return;
        }

        $root = self::ensureRoot();
        $leaf = Permission::query()
            ->where('name', self::CHANGE_LEAF)
            ->where('guard_name', 'web')
            ->first();

        if ($leaf) {
            $group = Permission::query()->find((int) $leaf->parent_id);
            if (! $group || (int) $group->parent_id !== (int) $root->id) {
                $group = self::createGroup($root, self::CHANGE_GROUP);
                $leaf->update(['parent_id' => $group->id]);
            } else {
                self::renameGroupUnique($root, $group, self::CHANGE_GROUP);
            }

            return;
        }

        $group = self::createGroup($root, self::CHANGE_GROUP);
        $leaf = Permission::query()->create([
            'name' => self::CHANGE_LEAF,
            'guard_name' => 'web',
            'parent_id' => $group->id,
        ]);
        if ($assignToAdminRoles) {
            self::giveToAdminRoles($leaf);
        }
    }

    public static function canAccessOptionId(int $optionId): bool
    {
        if (RoleFieldAccess::isAdmin() || ! self::isEnforcedForCurrentUser()) {
            return true;
        }

        if ($optionId <= 0) {
            return false;
        }

        // No individual Visible-status leaves granted → treat as unrestricted
        // (Top Bar category permission alone is enough to show/use statuses).
        if (! self::userHasAnyVisibleStatusPermission()) {
            return true;
        }

        $leaf = self::leafName($optionId);
        if (! RoleFieldAccess::permissionExistsPublic($leaf)) {
            return true;
        }

        return RoleFieldAccess::hasExactPermission($leaf);
    }

    public static function canAccessStatusName(?string $statusName): bool
    {
        if (RoleFieldAccess::isAdmin() || ! self::isEnforcedForCurrentUser()) {
            return true;
        }

        if (! self::isEnforced()) {
            return true;
        }

        $statusName = trim((string) $statusName);
        if ($statusName === '') {
            return false;
        }

        $categoryId = self::statusCategoryId();
        if (! $categoryId) {
            return true;
        }

        $option = RiderTopOption::query()
            ->where('category_id', $categoryId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($statusName)])
            ->first();

        if (! $option) {
            return false;
        }

        return self::canAccessOptionId((int) $option->id);
    }

    /**
     * @return list<string>|null  null = unrestricted (admin / not enforced)
     */
    public static function permittedStatusNames(): ?array
    {
        if (RoleFieldAccess::isAdmin() || ! self::isEnforcedForCurrentUser()) {
            return null;
        }

        $categoryId = self::statusCategoryId();
        if (! $categoryId) {
            return [];
        }

        $names = [];
        foreach (
            RiderTopOption::query()
                ->where('category_id', $categoryId)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get(['id', 'name']) as $option
        ) {
            if (self::canAccessOptionId((int) $option->id)) {
                $name = trim((string) $option->name);
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        return $names;
    }

    /**
     * True when the user holds at least one rider_statuses_{id}_view leaf
     * (excluding Change Rider Status).
     */
    public static function userHasAnyVisibleStatusPermission(?Collection $options = null): bool
    {
        if (RoleFieldAccess::isAdmin() || ! self::isEnforcedForCurrentUser()) {
            return false;
        }

        $options = $options ?? collect();
        if ($options->isEmpty()) {
            $categoryId = self::statusCategoryId();
            if (! $categoryId) {
                return false;
            }
            $options = RiderTopOption::query()
                ->where('category_id', $categoryId)
                ->get(['id']);
        }

        foreach ($options as $option) {
            $id = (int) (is_object($option) ? ($option->id ?? $option->getKey()) : 0);
            if ($id <= 0) {
                continue;
            }
            $leaf = self::leafName($id);
            if (RoleFieldAccess::permissionExistsPublic($leaf) && RoleFieldAccess::holdsPermission($leaf)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, RiderTopOption>  $options
     * @return Collection<int, RiderTopOption>
     */
    public static function filterOptions(Collection $options): Collection
    {
        if (RoleFieldAccess::isAdmin() || ! self::isEnforcedForCurrentUser()) {
            return $options->values();
        }

        return $options
            ->filter(fn (RiderTopOption $option) => self::canAccessOptionId((int) $option->id))
            ->values();
    }

    /**
     * Top Bar / view-card visibility: if the role was given Top Bar category access
     * but no individual "Visible statuses" leaves, still show all statuses.
     * Once any status leaf is granted, only those statuses appear.
     *
     * @param  Collection<int, RiderTopOption>  $options
     * @return Collection<int, RiderTopOption>
     */
    public static function filterOptionsForTopBar(Collection $options): Collection
    {
        $options = $options->values();
        if (RoleFieldAccess::isAdmin() || ! self::isEnforcedForCurrentUser()) {
            return $options;
        }

        if (! self::userHasAnyVisibleStatusPermission($options)) {
            return $options;
        }

        return self::filterOptions($options);
    }

    /**
     * @deprecated Rider listings are no longer restricted by Rider Status permissions.
     */
    public static function applyToRiderQuery(Builder $query, string $column = 'riders.rider_status'): void
    {
        // Intentionally no-op: status permissions must not hide rider records.
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
            ['name' => DynamicPermissionModules::RIDER_STATUSES, 'guard_name' => 'web'],
            ['parent_id' => null]
        );
        if ($root->parent_id !== null) {
            $root->update(['parent_id' => null]);
        }

        return $root;
    }

    protected static function createGroup(Permission $root, string $label): Permission
    {
        return Permission::query()->create([
            'name' => self::uniqueGroupName($root, $label),
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
        $base = $label !== '' ? $label : 'Status';
        $candidate = $base;
        $i = 2;
        while (
            Permission::query()
                ->where('parent_id', $root->id)
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
            ->where('name', DynamicPermissionModules::RIDER_STATUSES)
            ->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->first();
        if (! $root) {
            return;
        }

        foreach (Permission::query()->where('parent_id', $root->id)->get() as $group) {
            foreach (Permission::query()->where('parent_id', $group->id)->get() as $leaf) {
                if (! in_array($leaf->name, $desiredLeafNames, true)) {
                    PermissionTreeBuilder::deleteTree((int) $leaf->id);
                }
            }
            if (Permission::query()->where('parent_id', $group->id)->count() === 0) {
                $group->delete();
            }
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
