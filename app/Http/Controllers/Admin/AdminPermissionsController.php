<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPermissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $admin = auth('admin')->user();
            if (!$admin || !$admin->hasRole('Super Admin')) {
                abort(403, 'Only Super Admin can manage permissions.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $permissions = AdminPermission::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();

        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'extra' => 'nullable|array',
            'extra.*' => 'string|distinct',
        ];

        $messages = [
            'name.required' => 'Name Required',
            'extra.*.distinct' => 'Duplicate custom permissions are not allowed',
        ];

        $this->validate($request, $rules, $messages);

        $prefix = str_replace(' ', '_', strtolower(trim($request->name)));

        $parent = AdminPermission::create([
            'name' => $request->name,
            'parent_id' => null,
        ]);

        foreach (['view', 'create', 'edit', 'delete'] as $perm) {
            AdminPermission::create([
                'name' => $prefix . '_' . $perm,
                'parent_id' => $parent->id,
            ]);
        }

        if ($request->has('extra') && !empty($request->extra)) {
            $extras = array_filter($request->extra, fn ($v) => !empty(trim($v)));
            foreach ($extras as $customPerm) {
                $customPerm = str_replace(' ', '_', strtolower(trim($customPerm)));
                if (!empty($customPerm)) {
                    AdminPermission::create([
                        'name' => $prefix . '_' . $customPerm,
                        'parent_id' => $parent->id,
                    ]);
                }
            }
        }

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', __('Permission module created successfully.'));
    }

    public function edit(AdminPermission $permission)
    {
        if ($permission->parent_id) {
            $permission = AdminPermission::query()->findOrFail($permission->parent_id);
        }

        $prefix = str_replace(' ', '_', strtolower($permission->name));

        $customPermissions = AdminPermission::query()
            ->where('parent_id', $permission->id)
            ->whereNotIn('name', [
                $prefix . '_view',
                $prefix . '_create',
                $prefix . '_edit',
                $prefix . '_delete',
            ])
            ->pluck('name')
            ->map(function ($name) use ($prefix) {
                return str_replace('_', ' ', str_replace($prefix . '_', '', $name));
            })
            ->values()
            ->toArray();

        return view('admin.permissions.edit', compact('permission', 'customPermissions'));
    }

    public function update(Request $request, AdminPermission $permission)
    {
        if ($permission->parent_id) {
            $permission = AdminPermission::query()->findOrFail($permission->parent_id);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'extra' => 'nullable|array',
            'extra.*' => 'string|distinct',
        ];

        $messages = [
            'name.required' => 'Name Required',
            'extra.*.distinct' => 'Duplicate custom permissions are not allowed',
        ];

        $this->validate($request, $rules, $messages);

        $prefix = str_replace(' ', '_', strtolower(trim($request->name)));

        $permission->update(['name' => $request->name]);

        // Rebuild generated child permissions for this module.
        AdminPermission::query()->where('parent_id', $permission->id)->delete();

        foreach (['view', 'create', 'edit', 'delete'] as $perm) {
            AdminPermission::create([
                'name' => $prefix . '_' . $perm,
                'parent_id' => $permission->id,
            ]);
        }

        if ($request->has('extra') && !empty($request->extra)) {
            $extras = array_filter($request->extra, fn ($v) => !empty(trim($v)));
            foreach ($extras as $customPerm) {
                $customPerm = str_replace(' ', '_', strtolower(trim($customPerm)));
                if (!empty($customPerm)) {
                    AdminPermission::create([
                        'name' => $prefix . '_' . $customPerm,
                        'parent_id' => $permission->id,
                    ]);
                }
            }
        }

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', __('Permission module updated successfully.'));
    }

    public function updateRolePermissions(Request $request, AdminRole $role)
    {
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:admin_permissions,id',
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', __('Permissions updated for role: :role', ['role' => $role->name]));
    }

    public function destroy(AdminPermission $permission)
    {
        DB::transaction(function () use ($permission) {
            $childIds = AdminPermission::query()
                ->where('parent_id', $permission->id)
                ->pluck('id')
                ->toArray();

            $allIds = array_merge([$permission->id], $childIds);

            // Remove permission mappings first to avoid orphan pivot rows.
            DB::table('admin_role_has_permissions')
                ->whereIn('admin_permission_id', $allIds)
                ->delete();

            DB::table('admin_model_has_permissions')
                ->whereIn('admin_permission_id', $allIds)
                ->delete();

            AdminPermission::query()->whereIn('id', $allIds)->delete();
        });

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', __('Permission deleted successfully.'));
    }
}

