<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\AdminUserDataTable;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUsersController extends Controller
{
    public function index(AdminUserDataTable $adminUserDataTable)
    {
        $roles = AdminRole::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return $adminUserDataTable->render('admin.users.index', compact('roles'));
    }

    public function editRoles(AdminUser $user)
    {
        $roles = AdminRole::query()->orderBy('name')->get();

        return view('admin.users.edit_roles', compact('user', 'roles'));
    }

    public function create()
    {
        $roles = AdminRole::query()->orderBy('name')->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admin_users,email',
            'username' => 'nullable|string|max:255|unique:admin_users,username',
            'password' => 'required|string|min:6|confirmed',
            'status' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:admin_roles,id',
        ]);

        $user = AdminUser::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'password' => $validated['password'],
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        $user->roles()->sync($validated['roles'] ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('Admin user created successfully.'));
    }

    public function edit(AdminUser $user)
    {
        $roles = AdminRole::query()->orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, AdminUser $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('admin_users', 'email')->ignore($user->id),
            ],
            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('admin_users', 'username')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:6|confirmed',
            'status' => 'nullable|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:admin_roles,id',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'status' => (bool) ($validated['status'] ?? false),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);
        $user->roles()->sync($validated['roles'] ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('Admin user updated successfully.'));
    }

    public function destroy(AdminUser $user)
    {
        if ((int) auth('admin')->id() === (int) $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', __('You cannot delete your own account.'));
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('Admin user deleted successfully.'));
    }

    public function updateRoles(Request $request, AdminUser $user)
    {
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:admin_roles,id',
        ]);

        $user->roles()->sync($validated['roles']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('User roles updated.'));
    }
}

