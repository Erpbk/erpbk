<?php

namespace App\Support;

use App\Models\RoleFieldPermission;
use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * Central resolver for the CURRENT user's effective module + field permissions.
 *
 * Field permissions live in {@see RoleFieldPermission} keyed by role_id + module_id
 * (the top-level permission row id) + field_name (a DB column, or "cf_{id}" for a
 * custom field). Module (CRUD) permissions are standard Spatie permissions named
 * "{slug}_{action}".
 *
 * Aggregation rules (a user may have several roles):
 *   - Most-permissive union across the user's roles.
 *   - Sensible permissive defaults: when NO role explicitly restricts a field it is
 *     visible + editable (required = false). This keeps every existing form/table
 *     working until an admin actually configures restrictions.
 *   - Administrator / Super Admin bypass everything (see everything, edit everything).
 *
 * All lookups are memoised per request.
 */
class RoleFieldAccess
{
    /** @var array<string, int|null> entity slug => top module permission id */
    protected static array $moduleIdCache = [];

    /** @var array<int, array<string, array{visible: bool, editable: bool, required: bool, seen: int}>> */
    protected static array $fieldPermCache = [];

    protected static ?array $userRoleIds = null;

    protected static ?bool $isAdminCache = null;

    protected static ?array $userPermNames = null;

    /** @var array<int, list<string>> module id => descendant "_view" permission names */
    protected static array $moduleViewCache = [];

    /** @var array<string, list<string>> "moduleId|action" => descendant "_{action}" permission names */
    protected static array $moduleActionCache = [];

    /** @var array<int, list<string>> user id => effective permission names */
    protected static array $userNamesById = [];

    /** @var array<string, int|null> lowercase top-module display name => module id */
    protected static array $moduleIdByNameCache = [];

    /**
     * Reset the per-request memo (useful in tests / role switches).
     */
    public static function flush(): void
    {
        self::$moduleIdCache = [];
        self::$fieldPermCache = [];
        self::$userRoleIds = null;
        self::$isAdminCache = null;
        self::$userPermNames = null;
        self::$moduleViewCache = [];
        self::$moduleActionCache = [];
        self::$userNamesById = [];
        self::$moduleIdByNameCache = [];
    }

    protected static function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Administrators and Super Admins bypass all field / module restrictions.
     */
    public static function isAdmin(): bool
    {
        if (self::$isAdminCache !== null) {
            return self::$isAdminCache;
        }

        $user = self::currentUser();

        return self::$isAdminCache = ($user !== null && $user->isAdmin());
    }

    /**
     * @return list<int>
     */
    public static function roleIds(): array
    {
        if (self::$userRoleIds !== null) {
            return self::$userRoleIds;
        }

        $user = self::currentUser();
        if ($user === null) {
            return self::$userRoleIds = [];
        }

        return self::$userRoleIds = $user->roles
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Resolve the top-level permission (module) id backing an entity, e.g. "rider".
     */
    public static function moduleId(string $entityKey): ?int
    {
        $entityKey = mb_strtolower(trim($entityKey));
        if ($entityKey === '') {
            return null;
        }
        if (array_key_exists($entityKey, self::$moduleIdCache)) {
            return self::$moduleIdCache[$entityKey];
        }

        $resolved = null;
        try {
            $topModules = Permission::query()
                ->where(function ($q) {
                    $q->whereNull('parent_id')->orWhere('parent_id', 0);
                })
                ->get(['id', 'name', 'parent_id']);

            foreach ($topModules as $module) {
                if (RoleModuleFieldResolver::slugForModule($module) === $entityKey) {
                    $resolved = (int) $module->id;
                    break;
                }
            }
        } catch (\Throwable $e) {
            $resolved = null;
        }

        // Fallback for modules whose display-name slug doesn't match the code's entity slug
        // (e.g. "rtafine" -> "RTA Fines"), so field permissions still resolve & enforce.
        if ($resolved === null) {
            $nameMap = [
                'rtafine' => 'RTA Fines',
                'salik' => 'RTA Saliks',
                'licenseexpense' => 'License Expense',
                'legalcase' => 'Legal Case',
                'agreement' => 'Agreements',
                'asset' => 'Assets',
                'visaexpense' => 'Visa Expense',
                'installment' => 'Visa Expense',
                'visaloan' => 'Visa Expense',
            ];
            if (isset($nameMap[$entityKey])) {
                $resolved = self::moduleIdByName($nameMap[$entityKey]);
            }
        }

        // Some entities are sub-modules of the permission tree rather than top-level modules
        // (e.g. "Cheques" lives under "Cash & Banks"). Their field permissions are stored
        // against the sub-module's own permission id, so resolve those too.
        if ($resolved === null) {
            $resolved = self::subModuleId($entityKey);
        }

        return self::$moduleIdCache[$entityKey] = $resolved;
    }

    /**
     * Resolve a non-top-level permission node whose slug matches the entity key.
     *
     * Only an unambiguous match counts: names such as "Inventory" appear under several
     * parents, and silently picking one would apply another module's field rules.
     */
    protected static function subModuleId(string $entityKey): ?int
    {
        try {
            $candidates = [];
            $nodes = Permission::query()->get(['id', 'name', 'parent_id']);
            foreach ($nodes as $node) {
                $name = (string) $node->name;
                if (preg_match('/_(view|create|edit|delete)$/', $name)) {
                    continue;
                }
                if ((int) ($node->parent_id ?? 0) === 0) {
                    continue;
                }
                if (RoleModuleFieldResolver::slugForModule($node) === $entityKey) {
                    $candidates[] = (int) $node->id;
                }
            }

            return count($candidates) === 1 ? $candidates[0] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Canonical field name used in {@see RoleFieldPermission} for a custom field id.
     */
    public static function customFieldName(int|string $customFieldId): string
    {
        return 'cf_' . $customFieldId;
    }

    /**
     * @return list<string> the current user's effective permission names (direct + via roles)
     */
    protected static function userPermissionNames(): array
    {
        if (self::$userPermNames !== null) {
            return self::$userPermNames;
        }

        $user = self::currentUser();
        if ($user === null) {
            return self::$userPermNames = [];
        }

        try {
            return self::$userPermNames = $user->getAllPermissions()->pluck('name')->map(fn ($n) => (string) $n)->all();
        } catch (\Throwable $e) {
            return self::$userPermNames = [];
        }
    }

    /**
     * All "*_view" permission names in a module's subtree (module + descendants).
     * Robust to the hierarchical naming scheme (e.g. "riders_rider_view").
     *
     * @return list<string>
     */
    protected static function moduleViewPermissionNames(int $moduleId): array
    {
        if (isset(self::$moduleViewCache[$moduleId])) {
            return self::$moduleViewCache[$moduleId];
        }

        $names = [];
        try {
            $all = Permission::query()->get(['id', 'name', 'parent_id']);
            $byParent = [];
            foreach ($all as $perm) {
                $byParent[(int) ($perm->parent_id ?? 0)][] = $perm;
            }

            $stack = [$moduleId];
            $visited = [];
            while ($stack !== []) {
                $current = array_pop($stack);
                if (isset($visited[$current])) {
                    continue;
                }
                $visited[$current] = true;
                foreach ($byParent[$current] ?? [] as $child) {
                    if (str_ends_with((string) $child->name, '_view')) {
                        $names[] = (string) $child->name;
                    }
                    $stack[] = (int) $child->id;
                }
            }
        } catch (\Throwable $e) {
            $names = [];
        }

        return self::$moduleViewCache[$moduleId] = array_values(array_unique($names));
    }

    /**
     * Whether the current user may access (see) a module at all — i.e. holds at least one
     * "view" permission somewhere in the module's subtree. Falls back to allow when the
     * module or its view leaves cannot be resolved, so it never locks users out by mistake.
     */
    public static function canAccessModule(string $entityKey): bool
    {
        if (self::isAdmin()) {
            return true;
        }

        $user = self::currentUser();
        if ($user === null) {
            return true;
        }

        $moduleId = self::moduleId($entityKey);
        if ($moduleId === null) {
            return true;
        }

        $viewLeaves = self::moduleViewPermissionNames($moduleId);
        if ($viewLeaves === []) {
            return true;
        }

        return array_intersect($viewLeaves, self::userPermissionNames()) !== [];
    }

    /**
     * All "*_{action}" permission names in a module's subtree (module + descendants).
     * Robust to the hierarchical naming scheme (e.g. "bikes_bike_view", "sims_sim_edit").
     *
     * @return list<string>
     */
    protected static function moduleActionPermissionNames(int $moduleId, string $action): array
    {
        $cacheKey = $moduleId . '|' . $action;
        if (isset(self::$moduleActionCache[$cacheKey])) {
            return self::$moduleActionCache[$cacheKey];
        }

        $suffix = '_' . $action;
        $names = [];
        try {
            $all = Permission::query()->get(['id', 'name', 'parent_id']);
            $byParent = [];
            foreach ($all as $perm) {
                $byParent[(int) ($perm->parent_id ?? 0)][] = $perm;
            }

            $stack = [$moduleId];
            $visited = [];
            while ($stack !== []) {
                $current = array_pop($stack);
                if (isset($visited[$current])) {
                    continue;
                }
                $visited[$current] = true;
                foreach ($byParent[$current] ?? [] as $child) {
                    if (str_ends_with((string) $child->name, $suffix)) {
                        $names[] = (string) $child->name;
                    }
                    $stack[] = (int) $child->id;
                }
            }
        } catch (\Throwable $e) {
            $names = [];
        }

        return self::$moduleActionCache[$cacheKey] = array_values(array_unique($names));
    }

    protected static function permissionExists(string $ability): bool
    {
        try {
            return Permission::query()->where('name', $ability)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Public wrapper for callers that need to know whether a Spatie permission row exists.
     */
    public static function permissionExistsPublic(string $ability): bool
    {
        return self::permissionExists($ability);
    }

    /**
     * Whether the current user actually holds this permission name (no missing-row fallback).
     */
    public static function holdsPermission(string $ability): bool
    {
        $ability = trim($ability);
        if ($ability === '') {
            return false;
        }

        if (self::isAdmin()) {
            return true;
        }

        return in_array($ability, self::userPermissionNames(), true);
    }

    /**
     * Strict exact-name check (no flat→hierarchical bridging).
     * Admins always pass. Missing permission rows are treated as allow (pre-sync safe).
     */
    public static function hasExactPermission(string $ability, ?User $user = null): bool
    {
        $ability = trim($ability);
        if ($ability === '') {
            return true;
        }

        if ($user === null) {
            if (self::isAdmin()) {
                return true;
            }
            $user = self::currentUser();
            if ($user === null) {
                return false;
            }
            $names = self::userPermissionNames();
        } else {
            try {
                if ($user->isAdmin()) {
                    return true;
                }
            } catch (\Throwable $e) {
                // fall through
            }
            try {
                $names = $user->getAllPermissions()->pluck('name')->map(fn ($n) => (string) $n)->all();
            } catch (\Throwable $e) {
                $names = [];
            }
        }

        if (in_array($ability, $names, true)) {
            return true;
        }

        return ! self::permissionExists($ability);
    }

    /**
     * Centralised, NON-THROWING permission check — the single entry point for module/action
     * authorization anywhere in the app. Prefer the global helper user_can() over calling
     * Spatie's hasPermissionTo() directly (which throws PermissionDoesNotExist and breaks
     * pages whenever a permission has been renamed to the hierarchical scheme).
     *
     * Resolution order:
     *   1. Administrators / Super Admins pass everything.
     *   2. Exact permission name held by the user  -> allow.
     *   3. CRUD ability ("{module}_{view|create|edit|delete}"): resolve the module and allow
     *      when the user holds ANY matching "*_{action}" leaf in that module's subtree. This
     *      bridges old flat names (e.g. "voucher_view") to the new hierarchical ones
     *      (e.g. "vouchers_view", "bikes_bike_view").
     *   4. Otherwise: deny only if the permission still exists but the user lacks it; if the
     *      name no longer exists at all, do not block (avoids locking users out mid-migration).
     */
    public static function userCan(string $ability, ?User $user = null): bool
    {
        $ability = trim($ability);
        if ($ability === '') {
            return true;
        }

        if ($user === null) {
            if (self::isAdmin()) {
                return true;
            }
            $user = self::currentUser();
            if ($user === null) {
                return false;
            }
            $names = self::userPermissionNames();
        } else {
            try {
                if ($user->isAdmin()) {
                    return true;
                }
            } catch (\Throwable $e) {
                // fall through to name-based checks
            }
            try {
                $names = $user->getAllPermissions()->pluck('name')->map(fn ($n) => (string) $n)->all();
            } catch (\Throwable $e) {
                $names = [];
            }
        }

        if (in_array($ability, $names, true)) {
            return true;
        }

        if (preg_match('/^(.+)_(view|create|edit|delete)$/', $ability, $m)) {
            $moduleId = self::moduleId($m[1]);
            if ($moduleId === null) {
                return true;
            }
            $leaves = self::moduleActionPermissionNames($moduleId, $m[2]);

            return $leaves === [] ? true : array_intersect($leaves, $names) !== [];
        }

        return ! self::permissionExists($ability);
    }

    /**
     * Legacy flat ability tokens (the part before _view/_create/...) that don't resolve to a
     * top module via their entity slug, mapped to the correct top-module display name(s).
     * Also used for sub-items whose flat token isn't itself a top module (they inherit access
     * from their parent module). Value may be a string or a list of module names (any grants).
     *
     * @return array<string, string|list<string>>
     */
    protected static function moduleAliases(): array
    {
        return [
            // Top modules whose display-name slug != the code's flat token
            'rtafine' => 'RTA Fines',
            'rtafine_paid' => 'RTA Fines',
            'salik' => 'RTA Saliks',
            'licenseexpense' => 'License Expense',
            'legalcase' => 'Legal Case',
            'agreement' => 'Agreements',
            'asset' => 'Assets',
            // Sub-items that live under a parent module (inherit the parent's access)
            'riderinvoice' => 'Riders',
            'riderinventory' => 'Riders',
            'employeeinvoice' => 'Employees',
            'customer_invoice' => 'Customers',
            'sim_invoice' => 'Sims',
            'bike_registration' => 'Bikes',
            'loan_installment' => 'Loans',
            'installment' => 'Visa Expense',
            'visaloan' => 'Visa Expense',
            'billing_invoice' => 'Bike On Rent',
            'leasing_company_invoice' => 'Leasing Companies',
            'vat_return' => 'Vat',
            'company_documents' => 'Documents',
            'attendance' => ['Employees', 'Riders'],
        ];
    }

    /**
     * Non-CRUD legacy abilities mapped to a module's VIEW access (grant if the user can view
     * that module), or the sentinel 'ALLOW' to grant for any authenticated user.
     *
     * @return array<string, string>
     */
    protected static function directAbilityMap(): array
    {
        return [
            'dashboard_view' => 'ALLOW',
            'payments_view' => 'ALLOW',
            'gn_ledger' => 'Accounts',
        ];
    }

    protected static function moduleIdByName(string $name): ?int
    {
        $key = mb_strtolower(trim($name));
        if ($key === '') {
            return null;
        }
        if (array_key_exists($key, self::$moduleIdByNameCache)) {
            return self::$moduleIdByNameCache[$key];
        }

        $resolved = null;
        try {
            $module = Permission::query()
                ->where(function ($q) {
                    $q->whereNull('parent_id')->orWhere('parent_id', 0);
                })
                ->whereRaw('LOWER(name) = ?', [$key])
                ->first(['id']);
            $resolved = $module ? (int) $module->id : null;
        } catch (\Throwable $e) {
            $resolved = null;
        }

        return self::$moduleIdByNameCache[$key] = $resolved;
    }

    /**
     * @return list<string>
     */
    protected static function permissionNamesForUser(User $user): array
    {
        $id = (int) $user->getKey();
        if (isset(self::$userNamesById[$id])) {
            return self::$userNamesById[$id];
        }

        try {
            return self::$userNamesById[$id] = $user->getAllPermissions()->pluck('name')->map(fn ($n) => (string) $n)->all();
        } catch (\Throwable $e) {
            return self::$userNamesById[$id] = [];
        }
    }

    /**
     * @param  list<string>  $names
     */
    protected static function userHasModuleActionByName(string $moduleName, string $action, array $names): bool
    {
        $moduleId = self::moduleIdByName($moduleName);
        if ($moduleId === null) {
            return false;
        }
        $leaves = self::moduleActionPermissionNames($moduleId, $action);

        return $leaves !== [] && array_intersect($leaves, $names) !== [];
    }

    /**
     * Conservative Gate::before fallback used for LEGACY / flat ability names (e.g. the
     * "voucher_view", "salik_view", "gn_ledger" strings still used throughout Blade @can
     * directives and the sidebar). It ONLY ever returns true (grant) or null (defer) — it
     * never returns false, so it can't override policies or other grant paths, and it never
     * blindly grants unknown abilities.
     *
     * Behaviour:
     *   - If the ability still exists as a real permission -> defer (Spatie already decided).
     *   - dashboard/payments (no dedicated permission) -> allow for authenticated users.
     *   - Otherwise map the flat name to the correct top module's hierarchical leaves and
     *     grant only when the user actually holds a matching permission.
     */
    public static function gateFallback(?User $user, string $ability): ?bool
    {
        if (! $user instanceof User) {
            return null;
        }
        $ability = trim($ability);
        if ($ability === '') {
            return null;
        }

        // Real, currently-defined permission -> let Spatie's own check decide.
        if (self::permissionExists($ability)) {
            return null;
        }

        $names = self::permissionNamesForUser($user);

        $direct = self::directAbilityMap();
        if (isset($direct[$ability])) {
            $target = $direct[$ability];
            if ($target === 'ALLOW') {
                return true;
            }

            return self::userHasModuleActionByName($target, 'view', $names) ? true : null;
        }

        if (preg_match('/^(.+)_(view|create|edit|delete)$/', $ability, $m)) {
            $token = $m[1];
            $action = $m[2];

            $aliases = self::moduleAliases();
            if (isset($aliases[$token])) {
                foreach ((array) $aliases[$token] as $moduleName) {
                    if (self::userHasModuleActionByName($moduleName, $action, $names)) {
                        return true;
                    }
                }

                return null;
            }

            $moduleId = self::moduleId($token);
            if ($moduleId === null) {
                return null;
            }
            $leaves = self::moduleActionPermissionNames($moduleId, $action);

            return ($leaves !== [] && array_intersect($leaves, $names) !== []) ? true : null;
        }

        return null;
    }

    /**
     * @return array<string, array{visible: bool, editable: bool, required: bool, seen: int}>
     */
    protected static function loadModule(int $moduleId): array
    {
        if (isset(self::$fieldPermCache[$moduleId])) {
            return self::$fieldPermCache[$moduleId];
        }

        $map = [];
        $roleIds = self::roleIds();

        if ($roleIds !== []) {
            try {
                // Roles are already company-specific, so the rows are selected by role_id alone.
                // The company scope is skipped on purpose: rows saved with a missing or stale
                // company_id would otherwise be dropped and the granted field would stay hidden.
                $rows = RoleFieldPermission::query()
                    ->withoutGlobalScope('company')
                    ->where('module_id', $moduleId)
                    ->whereIn('role_id', $roleIds)
                    ->get(['field_name', 'visible', 'editable', 'required']);

                foreach ($rows as $row) {
                    $field = (string) $row->field_name;
                    if (!isset($map[$field])) {
                        $map[$field] = ['visible' => false, 'editable' => false, 'required' => false, 'seen' => 0];
                    }
                    $map[$field]['visible'] = $map[$field]['visible'] || (bool) $row->visible;
                    $map[$field]['editable'] = $map[$field]['editable'] || (bool) $row->editable;
                    $map[$field]['required'] = $map[$field]['required'] || (bool) $row->required;
                    $map[$field]['seen']++;
                }
            } catch (\Throwable $e) {
                $map = [];
            }
        }

        return self::$fieldPermCache[$moduleId] = $map;
    }

    /**
     * Effective permission for a single field of an entity for the current user.
     *
     * @return array{visible: bool, editable: bool, required: bool}
     */
    public static function field(string $entityKey, string $fieldName): array
    {
        $allow = ['visible' => true, 'editable' => true, 'required' => false];

        if (self::isAdmin()) {
            return $allow;
        }

        $roleIds = self::roleIds();
        if ($roleIds === []) {
            // No role context (e.g. console) — do not hide anything.
            return $allow;
        }

        $moduleId = self::moduleId($entityKey);
        if ($moduleId === null) {
            return $allow;
        }

        $entry = self::loadModule($moduleId)[$fieldName] ?? null;
        if ($entry === null) {
            // No role restricts this field.
            return $allow;
        }

        // Visibility stays most-permissive: a role with no row still defaults to visible.
        $someRoleDefaults = $entry['seen'] < count($roleIds);
        $visible = $someRoleDefaults || $entry['visible'];

        // Editable uses the union of EXPLICIT rows only (OR in loadModule).
        // A role that saved editable=0 must lock the field; another role without a
        // row must not silently re-grant edit rights.
        $editable = $entry['editable'] && $visible;
        $required = $entry['required'] && $visible;

        return ['visible' => $visible, 'editable' => $editable, 'required' => $required];
    }

    public static function canView(string $entityKey, string $fieldName): bool
    {
        return self::field($entityKey, $fieldName)['visible'];
    }

    public static function canEdit(string $entityKey, string $fieldName): bool
    {
        return self::field($entityKey, $fieldName)['editable'];
    }

    public static function isRequired(string $entityKey, string $fieldName): bool
    {
        return self::field($entityKey, $fieldName)['required'];
    }

    /**
     * Filter a list of candidate field names down to those visible to the current user.
     *
     * @param  list<string>  $fieldNames
     * @return list<string>
     */
    public static function visibleFieldNames(string $entityKey, array $fieldNames): array
    {
        if (self::isAdmin()) {
            return array_values($fieldNames);
        }

        return array_values(array_filter($fieldNames, fn ($name) => self::canView($entityKey, (string) $name)));
    }

    /**
     * HTML attribute string to make a non-editable field read-only while keeping it visible.
     */
    public static function editableAttrs(string $entityKey, string $fieldName): string
    {
        return self::canEdit($entityKey, $fieldName) ? '' : 'readonly disabled';
    }

    /**
     * Fields the current user may see but not edit for an entity (visible + !editable).
     * Used by the global form-lock script and Blade helpers.
     *
     * @return array<string, true> field_name => true
     */
    public static function nonEditableFieldMap(string $entityKey): array
    {
        if (self::isAdmin() || self::roleIds() === [] || self::moduleId($entityKey) === null) {
            return [];
        }

        $moduleId = self::moduleId($entityKey);
        $map = self::loadModule($moduleId);
        $out = [];
        foreach (array_keys($map) as $field) {
            if (self::canView($entityKey, $field) && !self::canEdit($entityKey, $field)) {
                $out[$field] = true;
            }
        }

        return $out;
    }

    /**
     * Resolve RoleFieldAccess entity slug from an ERP / route module key.
     */
    public static function entityKeyFromModuleKey(?string $moduleKey): ?string
    {
        if ($moduleKey === null || $moduleKey === '') {
            return null;
        }

        $moduleKey = str_replace('-', '_', strtolower(trim($moduleKey)));

        $aliases = [
            'riders' => 'rider',
            'riders_list' => 'rider',
            'bikes' => 'bike',
            'bike_list' => 'bike',
            'customers' => 'customer',
            'vendors' => 'vendor',
            'suppliers' => 'supplier',
            'garages' => 'garage',
            'accounts' => 'account',
            'vouchers' => 'voucher',
            'cash_banks' => 'bank',
            'banks' => 'bank',
            'sims' => 'sim',
            'fuel_cards' => 'fuel',
            'loans' => 'loan',
            'items_list' => 'item',
            'items' => 'item',
            'recruiters' => 'recruiter',
            'leasing_companies' => 'leasing',
            'fixed_assets' => 'assets',
            'cheques' => 'cheques',
            'employees' => 'employees',
            'expenses' => 'expenses',
            'bike_registration' => 'bike_registration',
        ];

        $entity = $aliases[$moduleKey] ?? $moduleKey;

        return self::moduleId($entity) !== null ? $entity : null;
    }

    /**
     * Normalise a datatable/index column key to the canonical field permission name.
     * "custom_field_values.7" => "cf_7"; plain DB columns are returned unchanged.
     */
    public static function columnField(string $columnKey): string
    {
        $prefix = 'custom_field_values.';
        if (str_starts_with($columnKey, $prefix)) {
            return 'cf_' . substr($columnKey, strlen($prefix));
        }

        return $columnKey;
    }

    /**
     * Whether an index/table column is visible. Non-field columns (actions, computed
     * aliases, etc.) are never restricted because they are absent from the catalog.
     */
    public static function canViewColumn(string $entityKey, ?string $columnKey): bool
    {
        if ($columnKey === null || $columnKey === '') {
            return true;
        }

        return self::canView($entityKey, self::columnField((string) $columnKey));
    }

    /**
     * Remove role-hidden columns from a datatable column definition list. Applied at the
     * controller (source) so the table, the column-control chooser, and exports all agree.
     *
     * @param  list<array<string, mixed>>  $columns
     * @return list<array<string, mixed>>
     */
    public static function filterTableColumns(array $columns, string $entityKey): array
    {
        if (self::isAdmin()) {
            return array_values($columns);
        }

        return array_values(array_filter($columns, function ($col) use ($entityKey) {
            $key = is_array($col) ? ($col['data'] ?? ($col['key'] ?? null)) : null;

            return self::canViewColumn($entityKey, $key);
        }));
    }

    /**
     * Defence-in-depth for writes: remove request values the current user may not edit,
     * so hidden / read-only fields cannot be changed via a crafted request.
     *
     * Fixed columns are simply dropped (on update the model keeps its stored value because
     * the key is absent). Custom field values under "custom_field_values" are handled per id;
     * when an existing value is supplied it is restored so updates never wipe locked fields.
     *
     * @param  array<string, mixed>  $input
     * @param  array<int|string, mixed>|null  $existingCustom  current custom_field_values (for updates)
     * @return array<string, mixed>
     */
    public static function stripNonEditableInput(array $input, string $entityKey, ?array $existingCustom = null): array
    {
        if (self::isAdmin() || self::roleIds() === [] || self::moduleId($entityKey) === null) {
            return $input;
        }

        foreach ($input as $key => $value) {
            if ($key === 'custom_field_values' || $key === '_token' || $key === '_method') {
                continue;
            }
            if (!self::canEdit($entityKey, (string) $key)) {
                unset($input[$key]);
            }
        }

        if (isset($input['custom_field_values']) && is_array($input['custom_field_values'])) {
            foreach ($input['custom_field_values'] as $id => $value) {
                if (self::canEdit($entityKey, self::customFieldName($id))) {
                    continue;
                }
                if ($existingCustom !== null && array_key_exists($id, $existingCustom)) {
                    $input['custom_field_values'][$id] = $existingCustom[$id];
                } else {
                    unset($input['custom_field_values'][$id]);
                }
            }
        }

        return $input;
    }
}
