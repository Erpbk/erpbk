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
use Flash;
use App\Helpers\IConstants;

class RolesController extends AppBaseController
{
    use GlobalPagination;
  /** @var RolesRepository $rolesRepository*/
  private $rolesRepository;

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
    return $rolesDataTable->render('roles.index');
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

    return redirect(route('roles.index'));
  }

  /**
   * Display the specified Roles.
   */
  public function show($id)
  {
    $roles = $this->rolesRepository->find($id);

    if (empty($roles)) {
      Flash::error('Roles not found');

      return redirect(route('roles.index'));
    }

    return view('roles.show')->with('roles', $roles);
  }

  /**
   * Show the form for editing the specified Roles.
   */
  public function edit($id)
  {
    $roles = $this->rolesRepository->find($id);
    $rolePermissions = DB::table('role_has_permissions')
      ->where('role_has_permissions.role_id', $id)
      ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
      ->all();

    if (empty($roles)) {
      Flash::error('Roles not found');

      return redirect(route('roles.index'));
    }

    return view('roles.edit', compact('roles', 'rolePermissions'));
  }

  /**
   * Update the specified Roles in storage.
   */
  public function update($id, Request $request)
  {
    $this->validate($request, [
      'name' => 'required',
      'permission' => 'required',
    ]);
    $role = Role::find($id);
    $role->name = $request->input('name');
    $role->save();
    $role->syncPermissions($request->input('permission'));

    if (empty($roles)) {
      Flash::error('Roles not found');

      return redirect(route('roles.index'));
    }

    $roles = $this->rolesRepository->update($request->all(), $id);

    Flash::success('Roles updated successfully.');

    return redirect(route('roles.index'));
  }

  /**
   * Remove the specified Roles from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $roles = $this->rolesRepository->find($id);

    if (empty($roles)) {
      Flash::error('Roles not found');

      return redirect(route('roles.index'));
    }

    $this->rolesRepository->delete($id);

    Flash::success('Roles deleted successfully.');

    return redirect(route('roles.index'));
  }

  public function get_permissions()
  {
    $result = Permission::where(['parent_id' => 0])
      ->orWhere('parent_id', null)
      ->get();
    $htmlData = '';
    foreach ($result as $item) {
      $htmlData .= '<tr>';
      $htmlData .= '<td></td>';
      $htmlData .= '<td>' . $item->name . '</td>';
      $permission = Permission::where('parent_id', $item->id)->get();
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
