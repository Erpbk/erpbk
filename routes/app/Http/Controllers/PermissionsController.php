<?php

namespace App\Http\Controllers;

use App\DataTables\PermissionsDataTable;
use App\Http\Requests\CreatePermissionsRequest;
use App\Http\Requests\UpdatePermissionsRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\PermissionsRepository;
use App\Support\DynamicPermissionModules;
use App\Support\PermissionTreeBuilder;
use App\Traits\GlobalPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Route;

use Flash;

class PermissionsController extends AppBaseController
{
    use GlobalPagination;
    /** @var PermissionsRepository $permissionsRepository*/
    private $permissionsRepository;

    public function __construct(PermissionsRepository $permissionsRepo)
    {
        $this->permissionsRepository = $permissionsRepo;
    }

    /**
     * Display a listing of the Permissions.
     */
    public function index(PermissionsDataTable $permissionsDataTable)
    {
        return $permissionsDataTable->render('permissions.index');
    }


    /**
     * Show the form for creating a new Permissions.
     */
    public function create()
    {
        $this->ensurePermissionWriteAllowed();

        return view('permissions.create', [
            'submodules' => [],
            'customPermissions' => [],
        ]);
    }

    /**
     * Store a newly created Permissions in storage.
     */
    public function store(Request $request)
    {
        $this->ensurePermissionWriteAllowed();

        $rules = [
            'name' => 'required|string|max:255',
            'submodules' => 'nullable|array',
            'submodules.*' => 'nullable|string|max:255',
            'extra' => 'nullable|array',
            'extra.*' => 'string|distinct',
        ];

        $message = [
            'name.required' => 'Name Required',
            'extra.*.distinct' => 'Duplicate custom permissions are not allowed',
        ];

        $this->validate($request, $rules, $message);

        $guard = $request->input('guard_name', 'web');
        $moduleName = trim((string) $request->input('name'));
        if (DynamicPermissionModules::isReservedRoot($moduleName)) {
            $msg = 'The "' . $moduleName . '" module is managed automatically from Module Settings / Rider Statuses.';
            Flash::error($msg);
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return redirect()->back()->withInput();
        }
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
            Log::error('Error creating permission module: ' . $e->getMessage());
            Flash::error('Failed to save permissions: ' . $e->getMessage());

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'Failed to save permissions: ' . $e->getMessage()], 422);
            }

            return redirect()->back()->withInput();
        }

        Flash::success(' permissions saved successfully.');

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => 'Permissions saved successfully.',
                'reload' => true,
            ], 200);
        }

        return redirect()->back();
    }

    /**
     * Display the specified Permissions.
     */
    public function show($id)
    {
        $permissions = $this->permissionsRepository->find($id);

        if (empty($permissions)) {
            Flash::error('Permissions not found');

            return redirect(route('settings-panel.permissions.index'));
        }

        return view('permissions.show')->with('permissions', $permissions);
    }

    /**
     * Show the form for editing the specified Permissions.
     */
    public function edit($id)
    {
        $this->ensurePermissionWriteAllowed();

        $permission = $this->permissionsRepository->find($id);

        if (empty($permission)) {
            Flash::error('Permissions not found');

            return redirect(route('settings-panel.permissions.index'));
        }

        $moduleSlug = PermissionTreeBuilder::slugify($permission->name);
        $submodules = PermissionTreeBuilder::submoduleNamesForModule($permission);
        $customPermissions = $submodules === []
            ? PermissionTreeBuilder::customLeafNamesForModule($permission, $moduleSlug)
            : [];

        return view('permissions.edit', compact('permission', 'customPermissions', 'submodules'));
    }

    /**
     * Update the specified Permissions in storage.
     */
    public function update(Request $request, $id)
    {
        $this->ensurePermissionWriteAllowed();

        $rules = [
            'name' => 'required|string|max:255',
            'submodules' => 'nullable|array',
            'submodules.*' => 'nullable|string|max:255',
            'extra' => 'nullable|array',
            'extra.*' => 'string|distinct',
        ];

        $message = [
            'name.required' => 'Name Required',
            'extra.*.distinct' => 'Duplicate custom permissions are not allowed',
        ];

        $this->validate($request, $rules, $message);

        $module = Permission::query()->findOrFail($id);
        $moduleName = trim((string) $request->input('name'));
        if (
            DynamicPermissionModules::isReservedRoot($module->name)
            || DynamicPermissionModules::isReservedRoot($moduleName)
        ) {
            $reservedName = DynamicPermissionModules::isReservedRoot($module->name)
                ? $module->name
                : $moduleName;
            $msg = 'The "' . $reservedName . '" module is managed automatically and cannot be edited here.';
            Flash::error($msg);
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return redirect()->back()->withInput();
        }
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
            Log::error("Error updating Permission ID: {$id} - " . $e->getMessage());
            Flash::error('Failed to update permissions: ' . $e->getMessage());

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'Failed to update permissions: ' . $e->getMessage()], 422);
            }

            return redirect()->back()->withInput();
        }

        Flash::success('Permissions updated successfully.');

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => 'Permissions updated successfully.',
                'reload' => true,
            ], 200);
        }

        return redirect(route('settings-panel.permissions.index'));
    }

    /**
     * Remove the specified Permissions from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $this->ensurePermissionWriteAllowed();

        $permissions = $this->permissionsRepository->find($id);

        if (empty($permissions)) {
            Flash::error('Permissions not found');
            return redirect(route('settings-panel.permissions.index'));
        }

        if (DynamicPermissionModules::isReservedRoot($permissions->name)) {
            Flash::error('The "' . $permissions->name . '" module is managed automatically and cannot be deleted here.');

            return redirect(route('settings-panel.permissions.index'));
        }

        DB::beginTransaction();
        try {
            PermissionTreeBuilder::deleteTree((int) $id);

            DB::commit();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            Flash::success('Permissions deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting Permission ID: {$id} - " . $e->getMessage());
            Flash::error('Error deleting Permission: ' . $e->getMessage());
        }

        return redirect(route('settings-panel.permissions.index'));
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

    private function ensurePermissionWriteAllowed(): void
    {
        if (!Route::currentRouteNamed('settings-panel.permissions.*')) {
            return;
        }

        if (auth()->check() && auth()->user()->isAdmin()) {
            return;
        }

        abort(403, 'Permission management is restricted to company administrators.');
    }
}
