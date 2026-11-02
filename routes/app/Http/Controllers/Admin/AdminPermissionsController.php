<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Support\AdminPermissionTreeBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminPermissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $admin = auth('admin')->user();
            if (! $admin || ! $admin->hasRole('Super Admin')) {
                abort(403, 'Only Super Admin can manage permissions.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $permissions = AdminPermission::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('admin.permissions.create', [
            'submodules' => [],
            'customPermissions' => [],
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, $this->rules(), $this->messages());

        $moduleName = trim((string) $request->input('name'));
        $moduleSlug = AdminPermissionTreeBuilder::slugify($moduleName);
        $submodules = $this->normalizedSubmodules($request);
        $extras = $submodules === [] ? $this->normalizedExtras($request) : [];

        DB::connection('mysql_admin')->beginTransaction();

        try {
            $module = AdminPermission::query()->firstOrCreate(
                ['name' => $moduleName, 'parent_id' => null],
                []
            );

            AdminPermissionTreeBuilder::syncModuleTree($module, $moduleSlug, $submodules, $extras);

            DB::connection('mysql_admin')->commit();
        } catch (\Throwable $e) {
            DB::connection('mysql_admin')->rollBack();
            Log::error('Error creating admin permission module: ' . $e->getMessage());

            return $this->ajaxErrorResponse($request, __('Failed to create permission module: :error', ['error' => $e->getMessage()]));
        }

        return $this->ajaxSuccessResponse(
            $request,
            __('Permission module created successfully.'),
            'admin.permissions.index'
        );
    }

    public function edit(AdminPermission $permission)
    {
        if ($permission->parent_id) {
            $permission = AdminPermission::query()->findOrFail($permission->parent_id);
        }

        $moduleSlug = AdminPermissionTreeBuilder::slugify($permission->name);
        $submodules = AdminPermissionTreeBuilder::submoduleNamesForModule($permission);
        $customPermissions = $submodules === []
            ? AdminPermissionTreeBuilder::customLeafNamesForModule($permission, $moduleSlug)
            : [];

        return view('admin.permissions.edit', compact('permission', 'customPermissions', 'submodules'));
    }

    public function update(Request $request, AdminPermission $permission)
    {
        if ($permission->parent_id) {
            $permission = AdminPermission::query()->findOrFail($permission->parent_id);
        }

        $this->validate($request, $this->rules(), $this->messages());

        $moduleName = trim((string) $request->input('name'));
        $moduleSlug = AdminPermissionTreeBuilder::slugify($moduleName);
        $submodules = $this->normalizedSubmodules($request);
        $extras = $submodules === [] ? $this->normalizedExtras($request) : [];

        DB::connection('mysql_admin')->beginTransaction();

        try {
            $permission->update(['name' => $moduleName]);

            AdminPermissionTreeBuilder::syncModuleTree($permission, $moduleSlug, $submodules, $extras);

            DB::connection('mysql_admin')->commit();
        } catch (\Throwable $e) {
            DB::connection('mysql_admin')->rollBack();
            Log::error('Error updating admin permission module: ' . $e->getMessage());

            return $this->ajaxErrorResponse($request, __('Failed to update permission module: :error', ['error' => $e->getMessage()]));
        }

        return $this->ajaxSuccessResponse(
            $request,
            __('Permission module updated successfully.'),
            'admin.permissions.index'
        );
    }

    public function updateRolePermissions(Request $request, AdminRole $role)
    {
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:mysql_admin.admin_permissions,id',
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', __('Permissions updated for role: :role', ['role' => $role->name]));
    }

    public function destroy(AdminPermission $permission)
    {
        if ($permission->parent_id) {
            $permission = AdminPermission::query()->findOrFail($permission->parent_id);
        }

        AdminPermissionTreeBuilder::deleteTree((int) $permission->id);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', __('Permission deleted successfully.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'submodules' => 'nullable|array',
            'submodules.*' => 'nullable|string|max:255',
            'extra' => 'nullable|array',
            'extra.*' => 'string|distinct',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'name.required' => 'Name Required',
            'extra.*.distinct' => 'Duplicate custom permissions are not allowed',
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizedSubmodules(Request $request): array
    {
        if (! $request->boolean('use_submodules')) {
            return [];
        }

        $submodules = $request->input('submodules', []);
        if (! is_array($submodules)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($name) => trim((string) $name),
            $submodules
        ))));
    }

    /**
     * @return list<string>
     */
    private function normalizedExtras(Request $request): array
    {
        $extras = $request->input('extra', []);
        if (! is_array($extras)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            $extras
        )));
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    private function ajaxErrorResponse(Request $request, string $message)
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    private function ajaxSuccessResponse(Request $request, string $message, string $redirectRoute)
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'reload' => true,
            ]);
        }

        return redirect()->route($redirectRoute)->with('success', $message);
    }
}
