<?php

namespace App\Http\Controllers;

use App\DataTables\RolesDataTable;

use App\Http\Controllers\AppBaseController;
use App\Repositories\RolesRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Schema;
use Flash;
use Illuminate\Validation\Rule;
use App\Helpers\IConstants;

class RolesController extends AppBaseController
{
  use GlobalPagination;
  /** @var RolesRepository $rolesRepository*/
  private $rolesRepository;

  /**
   * User Management lives at settings-panel.users; listing roles alone is not used (avoid redirect()->back() breaking modals).
   */
  protected function redirectToUserManagement()
  {
    return redirect()->route('settings-panel.users.index');
  }

  public function __construct(RolesRepository $rolesRepo)
  {
    $this->rolesRepository = $rolesRepo;
  }

  /**
   * Display a listing of the Roles.
   */
  public function index(RolesDataTable $rolesDataTable)
  {
    /* if (
      !auth()
        ->user()
        ->hasRole(IConstants::ROLE_SUPER_ADMIN)
    ) {
      if (
        !auth()
          ->user()
          ->hasPermissionTo('role_view')
      ) {
        abort(404);
      }
    } */
    return $this->redirectToUserManagement();
  }

  /**
   * Show the form for creating a new Roles.
   */
  public function create()
  {
    return view('roles.create');
  }

  /**
   * Store a newly created Roles in storage.
   */
  public function store(Request $request)
  {
    $companyId = (int) auth()->user()->company_id;
    $uniqueNameRule = Rule::unique('roles', 'name');
    if (Schema::hasColumn('roles', 'company_id')) {
      $uniqueNameRule = Rule::unique('roles', 'name')->where(function ($query) use ($companyId) {
        return $query->where('company_id', $companyId);
      });
    }

    $this->validate($request, [
      'name' => ['required', $uniqueNameRule],
      'permission' => 'required|array|min:1',
      'permission.*' => 'integer|exists:permissions,id',
    ]);

    $payload = ['name' => $request->input('name'), 'guard_name' => 'web'];
    if (Schema::hasColumn('roles', 'company_id')) {
      $payload['company_id'] = $companyId;
    }

    $role = Role::create($payload);
    $this->syncRolePermissionsByIds($role, (array) $request->input('permission', []));

    if ($request->ajax() || $request->expectsJson()) {
      return response()->json([
        'message' => 'Roles saved successfully.',
      ], 200);
    }

    Flash::success('Roles saved successfully.');

    return $this->redirectToUserManagement();
  }

  /**
   * Display the specified Roles.
   */
  public function show(string $company_slug, $role)
  {
    $roles = Role::query()->find($role);

    if (empty($roles) || !$this->roleBelongsToCurrentCompany($roles)) {
      Flash::error('Roles not found');

      return $this->redirectToUserManagement();
    }

    return view('roles.show')->with('roles', $roles);
  }

  /**
   * Show the form for editing the specified Roles.
   */
  public function edit(string $company_slug, $role)
  {
    $roles = Role::query()->find($role);
    if (empty($roles) || !$this->roleBelongsToCurrentCompany($roles)) {
      Flash::error('Roles not found');

      return $this->redirectToUserManagement();
    }

    $rolePermissions = \App\Support\CompanyQuery::table('role_has_permissions')
      ->where('role_has_permissions.role_id', $role)
      ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
      ->all();

    $modulesQuery = Permission::query();
    if (Schema::hasColumn('permissions', 'parent_id')) {
      $modulesQuery->where(function ($q) {
        $q->whereNull('parent_id')->orWhere('parent_id', 0);
      });
    }
    $modules = $modulesQuery->get();

    return view('roles.edit', compact('roles', 'rolePermissions', 'modules'));
  }

  /**
   * Update the specified Roles in storage.
   */
  public function update(Request $request, string $company_slug, $roleId)
  {
    $this->validate($request, [
      'name' => 'required',
      'permission' => 'required|array|min:1',
      'permission.*' => 'integer|exists:permissions,id',
    ]);
    $role = Role::query()->find($roleId);
    if (empty($role) || !$this->roleBelongsToCurrentCompany($role)) {
      if ($request->ajax() || $request->expectsJson()) {
        return response()->json([
          'message' => 'Roles not found',
        ], 404);
      }
      Flash::error('Roles not found');

      return $this->redirectToUserManagement();
    }

    $role->name = $request->input('name');
    $role->save();
    $this->syncRolePermissionsByIds($role, (array) $request->input('permission', []));
    if ($request->ajax() || $request->expectsJson()) {
      return response()->json([
        'message' => 'Roles updated successfully.',
      ], 200);
    }

    Flash::success('Roles updated successfully.');

    return $this->redirectToUserManagement();
  }

  /**
   * Remove the specified Roles from storage.
   *
   * @throws \Exception
   */
  public function destroy(Request $request, string $company_slug, $id)
  {
    $role = Role::find($id);
    if (empty($role) || !$this->roleBelongsToCurrentCompany($role)) {
      Flash::error('Roles not found');

      return $this->redirectToUserManagement();
    }
    if ($role->users()->count() > 0) {
      if ($request->ajax() || $request->expectsJson()) {
        return response()->json([
          'message' => 'Role is assigned to user(s), cannot delete. Assign user(s) to other role then delete this role.',
          'reload' => true
        ], 500);
      }

      Flash::success('Role is assigned to user(s), cannot delete. Assign user(s) to other role then delete this role.');

      return redirect()->back();
    }

    $this->rolesRepository->delete($id);

    if ($request->ajax() || $request->expectsJson()) {
      return response()->json([
        'message' => 'Role Deleted Successfully',
        'reload' => true
      ], 200);
    }

    Flash::success('Roles deleted successfully.');

    return redirect()->back();
  }

  public function get_permissions()
  {
    $resultQuery = Permission::query();
    if (Schema::hasColumn('permissions', 'parent_id')) {
      $resultQuery->where(function ($q) {
        $q->whereNull('parent_id')->orWhere('parent_id', 0);
      });
    }
    $result = $resultQuery->get();
    $htmlData = '';
    foreach ($result as $item) {
      $htmlData .= '<tr>';
      $htmlData .= '<td></td>';
      $htmlData .= '<td>' . $item->name . '</td>';
      $permission = Schema::hasColumn('permissions', 'parent_id')
        ? Permission::where('parent_id', $item->id)->get()
        : collect();
      foreach ($permission as $per) {
        $name = explode('_', $per->name, 2);
        $name = ucwords(str_replace('_', ' ', $name[1]));
        $htmlData .=
          '<td><input type="checkbox" name="permission[]" id="' .
          $per->id .
          '" value="' .
          $per->id .
          '"><label for="' .
          $per->id .
          '">&nbsp;' .
          $name .
          '</label> </td>';
      }
    }
    return compact('htmlData');
  }

  private function roleBelongsToCurrentCompany(?Role $role): bool
  {
    if (!$role) {
      return false;
    }
    if ($role->name === IConstants::ROLE_SUPER_ADMIN) {
      return false;
    }
    if (Schema::hasColumn('roles', 'company_id')) {
      return (int) ($role->company_id ?? 0) === (int) auth()->user()->company_id;
    }

    return true;
  }

  /**
   * Persist role permissions explicitly in role_has_permissions.
   *
   * @param array<int|string> $permissionIds
   */
  private function syncRolePermissionsByIds(Role $role, array $permissionIds): void
  {
    $table = config('permission.table_names.role_has_permissions', 'role_has_permissions');
    $pivotRole = config('permission.column_names.role_pivot_key') ?: 'role_id';
    $pivotPermission = config('permission.column_names.permission_pivot_key') ?: 'permission_id';
    $hasCompanyId = Schema::hasColumn($table, 'company_id');
    $companyId = null;
    if ($hasCompanyId) {
      $companyId = $role->company_id ?? auth()->user()->company_id;
      if ($companyId === null || $companyId === '') {
        throw new \RuntimeException('Cannot sync role permissions: company_id is required on role_has_permissions but is missing.');
      }
      $companyId = (int) $companyId;
    }

    $ids = Permission::query()
      ->whereIn('id', $permissionIds)
      ->pluck('id')
      ->map(fn($id) => (int) $id)
      ->unique()
      ->values()
      ->all();

    DB::transaction(function () use ($table, $pivotRole, $pivotPermission, $role, $ids, $hasCompanyId, $companyId): void {
      // Role is company-scoped; delete all pivot rows for this role id (including orphan NULL company_id rows).
      DB::table($table)->where($pivotRole, (int) $role->id)->delete();

      if (empty($ids)) {
        return;
      }

      $rows = array_map(function (int $permissionId) use ($pivotRole, $pivotPermission, $role, $hasCompanyId, $companyId): array {
        $row = [
          $pivotPermission => $permissionId,
          $pivotRole => (int) $role->id,
        ];
        if ($hasCompanyId && $companyId !== null) {
          $row['company_id'] = $companyId;
        }

        return $row;
      }, $ids);

      \App\Support\CompanyQuery::insert($table, $rows);
    });

    app(PermissionRegistrar::class)->forgetCachedPermissions();
  }
}
