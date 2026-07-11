<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PermissionTreeBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AdminErpPermissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $admin = auth('admin')->user();
            if (! $admin || ! $admin->hasRole('Super Admin')) {
                abort(403, 'Only Super Admin can manage ERP permissions.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $permissions = Permission::query()
            ->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->orderBy('name')
            ->get();

        return view('admin.erp_permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('admin.erp_permissions.create', [
            'submodules' => [],
            'customPermissions' => [],
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, $this->rules(), $this->messages());

        $guard = 'web';
        $moduleName = trim((string) $request->input('name'));
        $moduleSlug = PermissionTreeBuilder::slugify($moduleName);
        $submodules = $this->normalizedSubmodules($request);
        $extras = $submodules === [] ? $this->normalizedExtras($request) : [];

        DB::beginTransaction();

        try {
            $module = Permission::query()->firstOrCreate(
                ['name' => $moduleName, 'guard_name' => $guard],
                ['parent_id' => null]
            );
            if ($module->parent_id !== null) {
                $module->update(['parent_id' => null]);
            }

            PermissionTreeBuilder::syncModuleTree($module, $moduleSlug, $submodules, $extras, $guard);

            DB::commit();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creating ERP permission module: ' . $e->getMessage());

            return $this->ajaxErrorResponse(
                $request,
                __('Failed to create ERP permission module: :error', ['error' => $e->getMessage()])
            );
        }

        return $this->ajaxSuccessResponse(
            $request,
            __('ERP permission module created successfully.'),
            'admin.erp-permissions.index'
        );
    }

    public function edit(int $permission)
    {
        $permission = Permission::query()->findOrFail($permission);
        if ($permission->parent_id) {
            $permission = Permission::query()->findOrFail($permission->parent_id);
        }

        $moduleSlug = PermissionTreeBuilder::slugify($permission->name);
        $submodules = PermissionTreeBuilder::submoduleNamesForModule($permission);
        $customPermissions = $submodules === []
            ? PermissionTreeBuilder::customLeafNamesForModule($permission, $moduleSlug)
            : [];

        return view('admin.erp_permissions.edit', compact('permission', 'customPermissions', 'submodules'));
    }

    public function update(Request $request, int $permission)
    {
        $this->validate($request, $this->rules(), $this->messages());

        $module = Permission::query()->findOrFail($permission);
        if ($module->parent_id) {
            $module = Permission::query()->findOrFail($module->parent_id);
        }

        $moduleName = trim((string) $request->input('name'));
        $moduleSlug = PermissionTreeBuilder::slugify($moduleName);
        $guard = $module->guard_name ?? 'web';
        $submodules = $this->normalizedSubmodules($request);
        $extras = $submodules === [] ? $this->normalizedExtras($request) : [];

        DB::beginTransaction();

        try {
            $module->update(['name' => $moduleName]);

            PermissionTreeBuilder::syncModuleTree($module, $moduleSlug, $submodules, $extras, $guard);

            DB::commit();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error updating ERP permission module ID: {$permission} - " . $e->getMessage());

            return $this->ajaxErrorResponse(
                $request,
                __('Failed to update ERP permission module: :error', ['error' => $e->getMessage()])
            );
        }

        return $this->ajaxSuccessResponse(
            $request,
            __('ERP permission module updated successfully.'),
            'admin.erp-permissions.index'
        );
    }

    public function destroy(int $permission)
    {
        $module = Permission::query()->findOrFail($permission);
        if ($module->parent_id) {
            $module = Permission::query()->findOrFail($module->parent_id);
        }

        PermissionTreeBuilder::deleteTree((int) $module->id);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.erp-permissions.index')
            ->with('success', __('ERP permission module deleted successfully.'));
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
