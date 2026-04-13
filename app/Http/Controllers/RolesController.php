<?php

namespace App\Http\Controllers;

use App\DataTables\RolesDataTable;

use App\Http\Controllers\AppBaseController;
use App\Repositories\RolesRepository;
use DB;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Schema;
use Flash;
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
    $this->validate($request, [
      'name' => 'required|unique:roles,name',
      'permission' => 'required',
    ]);

    $role = Role::create(['name' => $request->input('name')]);
    $role->syncPermissions($request->input('permission'));

    Flash::success('Roles saved successfully.');

    return $this->redirectToUserManagement();
  }

  /**
   * Display the specified Roles.
   */
  public function show(string $company_slug, $role)
  {
    $roles = $this->rolesRepository->find($role);

    if (empty($roles)) {
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
    if (empty($roles)) {
      Flash::error('Roles not found');

      return $this->redirectToUserManagement();
    }

    $rolePermissions = DB::table('role_has_permissions')
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
      'permission' => 'required',
    ]);
    $role = Role::query()->find($roleId);
    if (empty($role)) {
      Flash::error('Roles not found');

      return $this->redirectToUserManagement();
    }

    $role->name = $request->input('name');
    $role->save();
    $role->syncPermissions($request->input('permission'));

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
    if (empty($role)) {
      Flash::error('Roles not found');

      return $this->redirectToUserManagement();
    }
    if($role->users()->count() > 0){
      if($request->ajax()){
        return response()->json([
          'message' => 'Role is assigned to user(s), cannot delete. Assign user(s) to other role then delete this role.',
          'reload' => true
          ],500);
      }

      Flash::success('Role is assigned to user(s), cannot delete. Assign user(s) to other role then delete this role.');

      return redirect()->back();
    }

    $this->rolesRepository->delete($id);

    if($request->ajax()){
      return response()->json([
        'message' => 'Role Deleted Successfully',
        'reload' => true
        ],200);
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
}
