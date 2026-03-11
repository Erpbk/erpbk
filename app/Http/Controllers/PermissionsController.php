<?php

namespace App\Http\Controllers;

use App\DataTables\PermissionsDataTable;
use App\Http\Requests\CreatePermissionsRequest;
use App\Http\Requests\UpdatePermissionsRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\PermissionsRepository;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Spatie\Permission\Models\Permission;

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
        if(auth()->user()->hasAnyRole('Administrator','Super Admin')){
            return $permissionsDataTable->render('permissions.index');
            }

        abort(403,'You dont have access to permissions resource');
        
    }


    /**
     * Show the form for creating a new Permissions.
     */
    public function create()
    {
        return view('permissions.create');
    }

    /**
     * Store a newly created Permissions in storage.
     */
    public function store(Request $request)
    {
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
        
        // Create parent permission
        $parent = Permission::create($data);
        
        // Create standard CRUD permissions
        $standardPermissions = ['view', 'create', 'edit', 'delete'];
        foreach($standardPermissions as $perm) {
            Permission::create([
                'name' => $fixstr . '_' . $perm,
                'parent_id' => $parent->id
            ]);
        }
        
        // Create extra custom permissions if provided
        if($request->has('extra') && !empty($request->extra)) {
            // Filter out empty values
            $extraPermissions = array_filter($request->extra, function($value) {
                return !empty(trim($value));
            });
            
            foreach($extraPermissions as $customPerm) {
                // Clean the custom permission name
                $customPerm = str_replace(' ', '_', strtolower(trim($customPerm)));
                
                // Check if it's not empty after cleaning
                if(!empty($customPerm)) {
                    Permission::create([
                        'name' => $fixstr . '_' . $customPerm,
                        'parent_id' => $parent->id
                    ]);
                }
            }
        }
        
        // Optional: Show count of permissions created
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
        
        // Recreate standard CRUD permissions
        $standardPermissions = ['view', 'create', 'edit', 'delete'];
        foreach($standardPermissions as $perm) {
            Permission::create([
                'name' => $fixstr . '_' . $perm,
                'parent_id' => $id
            ]);
        }
        
        // Recreate extra custom permissions if provided
        if($request->has('extra') && !empty($request->extra)) {
            // Filter out empty values
            $extraPermissions = array_filter($request->extra, function($value) {
                return !empty(trim($value));
            });
            
            foreach($extraPermissions as $customPerm) {
                // Clean the custom permission name
                $customPerm = str_replace(' ', '_', strtolower(trim($customPerm)));
                
                // Check if it's not empty after cleaning
                if(!empty($customPerm)) {
                    Permission::create([
                        'name' => $fixstr . '_' . $customPerm,
                        'parent_id' => $id
                    ]);
                }
            }
        }
        
        $totalPermissions = 4 + (isset($extraPermissions) ? count($extraPermissions) : 0);
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
                \Log::info("Deleted {$childPermissionsCount} child permissions for parent permission ID: {$id}");
            }

            // Delete the parent permission
            $this->permissionsRepository->delete($id);

            DB::commit();
            Flash::success('Permissions deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error deleting Permission ID: {$id} - " . $e->getMessage());
            Flash::error('Error deleting Permission: ' . $e->getMessage());
        }

        return redirect(route('settings-panel.permissions.index'));
    }
}
