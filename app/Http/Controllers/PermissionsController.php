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
    return $permissionsDataTable->render('permissions.index');
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
        ];
        $message=[
            'name.required'=>'Name Required',
        ];
        $this->validate($request, $rules, $message);
        $fixstr=str_replace(' ', '_', strtolower($request->name));
        $data=request()->except(['_token']);
        $id=$request->id;
        if($id=='' || $id==0){
            $ret=Permission::create($data);
                Permission::create(['name'=>$fixstr.'_view', 'parent_id'=>$ret->id]);
                Permission::create(['name'=>$fixstr.'_create','parent_id'=>$ret->id]);
                Permission::create(['name'=>$fixstr.'_edit','parent_id'=>$ret->id]);
                Permission::create(['name'=>$fixstr.'_delete','parent_id'=>$ret->id]);
            
         
        }else{
            Permission::where('parent_id', $id)->delete();
            $ret=Permission::where('id', $id)->update($data);
                Permission::create(['name'=>$fixstr.'_view', 'parent_id'=>$id]);
                Permission::create(['name'=>$fixstr.'_create','parent_id'=>$id]);
                Permission::create(['name'=>$fixstr.'_edit','parent_id'=>$id]);
                Permission::create(['name'=>$fixstr.'_delete','parent_id'=>$id]);
               
            
        }
       

        Flash::success('Permissions saved successfully.');

        return redirect(route('permissions.index'));
    }

    /**
     * Display the specified Permissions.
     */
    public function show($id)
    {
        $permissions = $this->permissionsRepository->find($id);

        if (empty($permissions)) {
            Flash::error('Permissions not found');

            return redirect(route('permissions.index'));
        }

        return view('permissions.show')->with('permissions', $permissions);
    }

    /**
     * Show the form for editing the specified Permissions.
     */
    public function edit($id)
    {
        $permissions = $this->permissionsRepository->find($id);

        if (empty($permissions)) {
            Flash::error('Permissions not found');

            return redirect(route('permissions.index'));
        }

        return view('permissions.edit')->with('permissions', $permissions);
    }

    /**
     * Update the specified Permissions in storage.
     */
    public function update($id, Request $request)
    {
        $permissions = $this->permissionsRepository->find($id);

        if (empty($permissions)) {
            Flash::error('Permissions not found');

            return redirect(route('permissions.index'));
        }

        $permissions = $this->permissionsRepository->update($request->all(), $id);

        Flash::success('Permissions updated successfully.');

        return redirect(route('permissions.index'));
    }

    /**
     * Remove the specified Permissions from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $permissions = $this->permissionsRepository->find($id);
        Permission::where('parent_id', $id)->delete();

        if (empty($permissions)) {
            Flash::error('Permissions not found');

            return redirect(route('permissions.index'));
        }

        $this->permissionsRepository->delete($id);

        Flash::success('Permissions deleted successfully.');

        return redirect(route('permissions.index'));
    }
}
