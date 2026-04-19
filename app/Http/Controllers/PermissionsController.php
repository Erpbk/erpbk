<?php

namespace App\Http\Controllers;

use App\DataTables\PermissionsDataTable;
use App\Http\Requests\CreatePermissionsRequest;
use App\Http\Requests\UpdatePermissionsRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\PermissionsRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
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
        return view('permissions.create');
    }

    /**
     * Store a newly created Permissions in storage.
     */
    public function store(Request $request)
    {
        $this->ensurePermissionWriteAllowed();

        $rules = [
            'name' => 'required',
            'extra' => 'nullable|array',
            'extra.*' => 'string|distinct'
        ];
        
        $message = [
            'name.required' => 'Name Required',
            'extra.*.distinct' => 'Duplicate custom permissions are not allowed'
        ];
        
        $this->validate($request, $rules, $message);
        
        // Create base permission name (module name)
        $fixstr = str_replace(' ', '_', strtolower($request->name));
        $data = request()->except(['_token', 'extra']);
        $data['guard_name'] = $data['guard_name'] ?? 'web';

        // Parent + children: idempotent (avoids Spatie PermissionAlreadyExists)
        $parent = Permission::query()->firstOrCreate(
            ['name' => $data['name'], 'guard_name' => $data['guard_name']],
            ['parent_id' => $data['parent_id'] ?? null]
        );

        $standardPermissions = ['view', 'create', 'edit', 'delete'];
        foreach ($standardPermissions as $perm) {
            Permission::query()->firstOrCreate(
                ['name' => $fixstr . '_' . $perm, 'guard_name' => $data['guard_name']],
                ['parent_id' => $parent->id]
            );
        }

        if ($request->has('extra') && !empty($request->extra)) {
            $extraPermissions = array_filter($request->extra, function ($value) {
                return !empty(trim($value));
            });

            foreach ($extraPermissions as $customPerm) {
                $customPerm = str_replace(' ', '_', strtolower(trim($customPerm)));
                if ($customPerm !== '') {
                    Permission::query()->firstOrCreate(
                        ['name' => $fixstr . '_' . $customPerm, 'guard_name' => $data['guard_name']],
                        ['parent_id' => $parent->id]
                    );
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Flash::success(' permissions saved successfully.');

        return redirect(route('settings-panel.permissions.index'));
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
        $fixstr = str_replace(' ', '_', strtolower($permission->name));
        $custom = Permission::where('parent_id', $permission->id)
            ->whereNotIn('name', [
                $fixstr . '_view',
                $fixstr . '_create', 
                $fixstr . '_edit',
                $fixstr . '_delete'
            ])->get();
        $customPermissions = [];
        foreach($custom as $perm){
            $perm = str_replace($fixstr.'_','',$perm->name);
            $customPermissions[] = str_replace('_',' ',$perm);
        }
        return view('permissions.edit', compact('permission','customPermissions','fixstr'));
    }

    /**
     * Update the specified Permissions in storage.
     */
    public function update(Request $request, $id)
    {
        $this->ensurePermissionWriteAllowed();

        $rules = [
            'name' => 'required',
            'extra' => 'nullable|array',
            'extra.*' => 'string|distinct'
        ];
        
        $message = [
            'name.required' => 'Name Required',
            'extra.*.distinct' => 'Duplicate custom permissions are not allowed'
        ];
        
        $this->validate($request, $rules, $message);
        
        // Create base permission name (module name)
        $fixstr = str_replace(' ', '_', strtolower($request->name));
        
        // Find the parent permission
        $parent = Permission::findOrFail($id);
        
        // Update parent permission name
        $parent->update(['name' => $request->name]);
        
        // Delete all existing child permissions
        Permission::where('parent_id', $id)->delete();
        
        $guard = $parent->guard_name ?? 'web';
        $standardPermissions = ['view', 'create', 'edit', 'delete'];
        foreach ($standardPermissions as $perm) {
            Permission::query()->firstOrCreate(
                ['name' => $fixstr . '_' . $perm, 'guard_name' => $guard],
                ['parent_id' => $id]
            );
        }

        if ($request->has('extra') && !empty($request->extra)) {
            $extraPermissions = array_filter($request->extra, function ($value) {
                return !empty(trim($value));
            });

            foreach ($extraPermissions as $customPerm) {
                $customPerm = str_replace(' ', '_', strtolower(trim($customPerm)));
                if ($customPerm !== '') {
                    Permission::query()->firstOrCreate(
                        ['name' => $fixstr . '_' . $customPerm, 'guard_name' => $guard],
                        ['parent_id' => $id]
                    );
                }
            }
        }
        
        $totalPermissions = 4 + (isset($extraPermissions) ? count($extraPermissions) : 0);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Flash::success('Permissions updated successfully. ' . $totalPermissions . ' permissions active.');

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

        DB::beginTransaction();
        try {
            // ✅ FIX: Delete child permissions with proper parent_id filter
            // Check if there are child permissions
            $childPermissionsCount = Permission::where('parent_id', $id)->count();

            if ($childPermissionsCount > 0) {
                // Delete all child permissions first
                Permission::where('parent_id', $id)->delete();
                Log::info("Deleted {$childPermissionsCount} child permissions for parent permission ID: {$id}");
            }

            // Delete the parent permission
            $this->permissionsRepository->delete($id);

            DB::commit();
            Flash::success('Permissions deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting Permission ID: {$id} - " . $e->getMessage());
            Flash::error('Error deleting Permission: ' . $e->getMessage());
        }

        return redirect(route('settings-panel.permissions.index'));
    }

    private function ensurePermissionWriteAllowed(): void
    {
        // In company settings panel, permissions are visible but managed only from admin portal.
        if (Route::currentRouteNamed('settings-panel.permissions.*')) {
            abort(403, 'Permission management is restricted to system administrators.');
        }
    }
}
