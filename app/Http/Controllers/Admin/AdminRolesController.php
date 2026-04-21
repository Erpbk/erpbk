<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminRolesController extends Controller
{
    public function create()
    {
        $modules = AdminPermission::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('admin.roles.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:admin_roles,name',
            'permission' => 'required|array|min:1',
            'permission.*' => 'exists:admin_permissions,id',
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
        $modules = AdminPermission::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $selectedPermissionIds = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'modules', 'selectedPermissionIds'));
    }

    public function update(Request $request, AdminRole $role)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('admin_roles', 'name')->ignore($role->id),
            ],
            'permission' => 'required|array|min:1',
            'permission.*' => 'exists:admin_permissions,id',
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
