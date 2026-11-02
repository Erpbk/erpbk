<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminRolesController extends Controller
{
    public function create()
    {
        return view('admin.roles.create', [
            'rolePermissions' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mysql_admin.admin_roles,name',
            'permission' => 'required|array|min:1',
            'permission.*' => 'exists:mysql_admin.admin_permissions,id',
        ]);

        $role = AdminRole::query()->create([
            'name' => $validated['name'],
        ]);

        $role->permissions()->sync($validated['permission']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('Role created successfully.'));
    }

    public function edit(AdminRole $role)
    {
        $rolePermissions = $role->permissions->pluck('id')
            ->mapWithKeys(static fn ($id) => [(int) $id => true])
            ->all();

        return view('admin.roles.edit', compact('role', 'rolePermissions'));
    }

    public function update(Request $request, AdminRole $role)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mysql_admin.admin_roles', 'name')->ignore($role->id),
            ],
            'permission' => 'required|array|min:1',
            'permission.*' => 'exists:mysql_admin.admin_permissions,id',
        ]);

        $role->update(['name' => $validated['name']]);
        $role->permissions()->sync($validated['permission']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('Role updated successfully.'));
    }

    public function destroy(AdminRole $role)
    {
        if ($role->name === 'Super Admin') {
            return redirect()
                ->route('admin.users.index')
                ->with('error', __('The Super Admin role cannot be deleted.'));
        }

        if ($role->users()->exists()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', __('Cannot delete a role that is assigned to users.'));
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('Role deleted successfully.'));
    }
}
