<?php

namespace App\Http\Controllers;

use App\Helpers\IConstants;
use App\Models\RoleFieldPermission;
use App\Support\CompanyContext;
use App\Support\CompanyQuery;
use App\Support\RoleModuleFieldResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Single-page Role Permission manager: module (CRUD) permissions on the left,
 * dynamic field-level permissions on the right, saved together in one transaction.
 *
 * The permission tree is hierarchical (module -> optional sub-modules -> {action} leaves,
 * e.g. "bikes_bike_view"). Each top-level module shows one toggle per action that aggregates
 * every descendant leaf for that action.
 */
class RolePermissionController extends AppBaseController
{
    private const ACTIONS = ['view', 'create', 'edit', 'delete'];

    /**
     * Render the two-panel permission management page for a role.
     */
    public function index(Request $request, string $company_slug, $roleId)
    {
        $role = Role::query()->find($roleId);
        if (empty($role) || !$this->roleAccessible($role)) {
            return redirect()->route('settings-panel.users.index', ['company_slug' => $company_slug]);
        }

        [$topModules, $byParent] = $this->permissionTree();
        $assigned = $this->assignedPermissionIds($role);

        $savedFieldPerms = RoleFieldPermission::query()
            ->where('role_id', $role->id)
            ->get()
            ->groupBy('module_id');

        $moduleRows = [];
        $moduleEnabled = 0;
        $moduleTotal = 0;
        $fieldStats = [];
        $fieldEnabled = 0;
        $fieldTotal = 0;

        foreach ($topModules as $module) {
            $subNodes = $this->submodulesFor($byParent, $module);
            if ($subNodes === []) {
                // Module without any CRUD leaves (rare) — still show it so fields can be managed.
                $subNodes = [];
            }

            $submodules = [];
            $modLeafTotal = 0;
            $modLeafEnabled = 0;

            foreach ($subNodes as $node) {
                $actions = [];
                foreach (self::ACTIONS as $action) {
                    $leafId = $node['actions'][$action];
                    if ($leafId === null) {
                        $actions[$action] = null;
                        continue;
                    }
                    $enabled = isset($assigned[$leafId]);
                    $actions[$action] = ['id' => $leafId, 'enabled' => $enabled];
                    $modLeafTotal++;
                    if ($enabled) {
                        $modLeafEnabled++;
                    }
                }
                $submodules[] = [
                    'name' => $node['name'],
                    'is_root' => $node['is_root'],
                    'actions' => $actions,
                ];
            }

            $moduleTotal += $modLeafTotal;
            $moduleEnabled += $modLeafEnabled;

            $isFlat = count($submodules) === 1 && $submodules[0]['is_root'];

            $fields = RoleModuleFieldResolver::fieldsForModule($module);
            $hasFields = $fields !== [];

            $savedForModule = ($savedFieldPerms->get($module->id) ?? collect())->keyBy('field_name');
            $mEnabled = 0;
            $mTotal = count($fields) * 3;
            foreach ($fields as $field) {
                $saved = $savedForModule->get($field['name']);
                $visible = $saved ? (bool) $saved->visible : true;
                $editable = $saved ? (bool) $saved->editable : true;
                $required = $saved ? (bool) $saved->required : false;
                $mEnabled += ($visible ? 1 : 0) + ($editable ? 1 : 0) + ($required ? 1 : 0);
            }
            $fieldTotal += $mTotal;
            $fieldEnabled += $mEnabled;
            $fieldStats[$module->id] = ['total' => $mTotal, 'enabled' => $mEnabled];

            $moduleRows[] = [
                'id' => $module->id,
                'name' => $module->name,
                'is_flat' => $isFlat,
                'leaf_total' => $modLeafTotal,
                'leaf_enabled' => $modLeafEnabled,
                'submodules' => $submodules,
                'has_fields' => $hasFields,
                'field_count' => count($fields),
            ];
        }

        $totalPermissions = $moduleTotal + $fieldTotal;
        $enabledPermissions = $moduleEnabled + $fieldEnabled;

        return view('roles.permissions.index', [
            'role' => $role,
            'moduleRows' => $moduleRows,
            'fieldStats' => $fieldStats,
            'summary' => [
                'total' => $totalPermissions,
                'enabled' => $enabledPermissions,
                'percent' => $totalPermissions > 0 ? (int) round(($enabledPermissions / $totalPermissions) * 100) : 0,
                'module_total' => $moduleTotal,
                'module_enabled' => $moduleEnabled,
            ],
        ]);
    }

    /**
     * AJAX: return the field-permission rows for a single module.
     */
    public function moduleFields(Request $request, string $company_slug, $roleId, $moduleId)
    {
        $role = Role::query()->find($roleId);
        if (empty($role) || !$this->roleAccessible($role)) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $module = Permission::query()->find($moduleId);
        if (empty($module)) {
            return response()->json(['message' => 'Module not found'], 404);
        }

        $fields = RoleModuleFieldResolver::fieldsForModule($module);
        $saved = RoleFieldPermission::query()
            ->where('role_id', $role->id)
            ->where('module_id', $module->id)
            ->get()
            ->keyBy('field_name');

        $rows = [];
        foreach ($fields as $field) {
            $existing = $saved->get($field['name']);
            $rows[] = [
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['type'],
                'visible' => $existing ? (bool) $existing->visible : true,
                'editable' => $existing ? (bool) $existing->editable : true,
                'required' => $existing ? (bool) $existing->required : false,
            ];
        }

        $html = view('roles.permissions._fields', [
            'moduleId' => $module->id,
            'moduleName' => $module->name,
            'rows' => $rows,
        ])->render();

        return response()->json([
            'html' => $html,
            'module_id' => $module->id,
            'field_count' => count($rows),
        ]);
    }

    /**
     * Persist module (CRUD) permissions and field permissions in one transaction.
     */
    public function save(Request $request, string $company_slug, $roleId)
    {
        $role = Role::query()->find($roleId);
        if (empty($role) || !$this->roleAccessible($role)) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        // Payloads arrive JSON-encoded (see save JS) so a large field set is not
        // silently truncated by PHP's max_input_vars; still accept plain arrays for safety.
        $permChanges = $this->decodePayload($request->input('perm_changes', []));
        $fieldPayload = $this->decodePayload($request->input('fields', []));

        DB::transaction(function () use ($role, $permChanges, $fieldPayload) {
            $this->saveModulePermissions($role, $permChanges);
            $this->saveFieldPermissions($role, $fieldPayload);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(['message' => 'Permissions saved successfully.']);
    }

    /**
     * Load the full permission tree once.
     *
     * @return array{0: Collection<int, Permission>, 1: Collection<int, Collection<int, Permission>>}
     */
    private function permissionTree(): array
    {
        $all = Permission::query()->orderBy('name')->get();
        $byParent = $all->groupBy(fn($p) => (int) ($p->parent_id ?? 0));

        $hasParentId = Schema::hasColumn('permissions', 'parent_id');
        $topModules = $hasParentId
            ? $all->filter(fn($p) => $p->parent_id === null || (int) $p->parent_id === 0)->sortBy('name')->values()
            : $all;

        return [$topModules, $byParent];
    }

    /**
     * Find every "leaf-bearing" node in a module's subtree (a node that directly owns CRUD leaves),
     * returning each with its action => leaf id map. The module itself counts when it owns leaves directly.
     *
     * @param  Collection<int, Collection<int, Permission>>  $byParent
     * @return list<array{id: int, name: string, is_root: bool, actions: array<string, int|null>}>
     */
    private function submodulesFor(Collection $byParent, Permission $root): array
    {
        // Gather the module node plus all descendants.
        $nodes = [$root];
        $queue = [$root];
        while ($queue !== []) {
            $node = array_pop($queue);
            foreach ($byParent->get((int) $node->id, collect()) as $child) {
                $nodes[] = $child;
                $queue[] = $child;
            }
        }

        $out = [];
        foreach ($nodes as $node) {
            $actions = ['view' => null, 'create' => null, 'edit' => null, 'delete' => null];
            $found = false;
            foreach ($byParent->get((int) $node->id, collect()) as $child) {
                foreach (self::ACTIONS as $action) {
                    if (Str::endsWith($child->name, '_' . $action)) {
                        $actions[$action] = (int) $child->id;
                        $found = true;
                    }
                }
            }
            if ($found) {
                $out[] = [
                    'id' => (int) $node->id,
                    'name' => $node->name,
                    'is_root' => (int) $node->id === (int) $root->id,
                    'actions' => $actions,
                ];
            }
        }

        return $out;
    }

    /**
     * @return array<int, int>  permission_id => permission_id
     */
    private function assignedPermissionIds(Role $role): array
    {
        return CompanyQuery::table('role_has_permissions')
            ->where('role_has_permissions.role_id', $role->id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();
    }

    /**
     * Apply only the toggles the admin changed, so untouched (possibly partial) sub-permissions are preserved.
     *
     * @param  array<int, array{ids?: array, enabled?: mixed}>  $changes
     */
    private function saveModulePermissions(Role $role, array $changes): void
    {
        $current = array_map('intval', array_values($this->assignedPermissionIds($role)));
        $set = array_fill_keys($current, true);

        foreach ($changes as $change) {
            $ids = array_map('intval', (array) ($change['ids'] ?? []));
            $enabled = $this->boolInput($change['enabled'] ?? false);
            foreach ($ids as $id) {
                if ($id <= 0) {
                    continue;
                }
                if ($enabled) {
                    $set[$id] = true;
                } else {
                    unset($set[$id]);
                }
            }
        }

        $finalIds = Permission::query()
            ->whereIn('id', array_keys($set))
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $table = config('permission.table_names.role_has_permissions', 'role_has_permissions');
        $pivotRole = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $pivotPermission = config('permission.column_names.permission_pivot_key') ?: 'permission_id';
        $hasCompanyId = Schema::hasColumn($table, 'company_id');
        $companyId = null;
        if ($hasCompanyId) {
            $companyId = $role->company_id ?? optional(auth()->user())->company_id ?? CompanyContext::id();
        }

        DB::table($table)->where($pivotRole, (int) $role->id)->delete();

        if ($finalIds === []) {
            return;
        }

        $rows = array_map(function (int $permissionId) use ($pivotRole, $pivotPermission, $role, $hasCompanyId, $companyId) {
            $row = [
                $pivotPermission => $permissionId,
                $pivotRole => (int) $role->id,
            ];
            if ($hasCompanyId && $companyId !== null) {
                $row['company_id'] = (int) $companyId;
            }

            return $row;
        }, $finalIds);

        CompanyQuery::insert($table, $rows);
    }

    /**
     * Upsert field permissions for the submitted fields, enforcing the visibility rules server-side.
     *
     * @param  array<int, array<string, mixed>>  $fieldPayload
     */
    private function saveFieldPermissions(Role $role, array $fieldPayload): void
    {
        $companyId = optional(auth()->user())->company_id ?? CompanyContext::id();

        foreach ($fieldPayload as $row) {
            $moduleId = (int) ($row['module_id'] ?? 0);
            $fieldName = trim((string) ($row['field_name'] ?? ''));
            if ($moduleId <= 0 || $fieldName === '') {
                continue;
            }

            $visible = $this->boolInput($row['visible'] ?? false);
            $editable = $this->boolInput($row['editable'] ?? false);
            $required = $this->boolInput($row['required'] ?? false);

            // Enforce rules: no visibility => nothing else; editable/required imply visible.
            if ($editable || $required) {
                $visible = true;
            }
            if (!$visible) {
                $editable = false;
                $required = false;
            }

            RoleFieldPermission::query()->updateOrCreate(
                [
                    'role_id' => (int) $role->id,
                    'module_id' => $moduleId,
                    'field_name' => $fieldName,
                ],
                [
                    'company_id' => $companyId !== null ? (int) $companyId : null,
                    'visible' => $visible,
                    'editable' => $editable,
                    'required' => $required,
                ]
            );
        }
    }

    private function boolInput($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Accept either a JSON-encoded string (the normal client payload) or a plain array.
     *
     * @return array<int|string, mixed>
     */
    private function decodePayload($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    private function roleAccessible(Role $role): bool
    {
        if ($role->name === IConstants::ROLE_SUPER_ADMIN) {
            return false;
        }
        if (Schema::hasColumn('roles', 'company_id')) {
            return (int) ($role->company_id ?? 0) === (int) (optional(auth()->user())->company_id ?? CompanyContext::id());
        }

        return true;
    }
}
