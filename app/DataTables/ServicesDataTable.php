<?php

namespace App\DataTables;

use App\Helpers\IConstants;
use App\Models\Services;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class ServicesDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

         $dataTable->addColumn('action', 'services.datatables_actions');
         $dataTable->addColumn('status', function(Services $services) {
           if($services->status){
            return '<span class="badge  bg-label-success">Active</span>';

           }else{
            return '<span class="badge  bg-label-danger">Inactive</span>';
           }
        })->toJson();

        $dataTable->addColumn('parent', function(Services $services) {
            return $services->parentName->name??'';
         })->toJson();
        
        $dataTable->rawColumns(['status','parent','action']);
        return $dataTable;
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Services $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Services $model)
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $buttons=[];
        if(auth()->user()->hasRole(IConstants::ROLE_SUPER_ADMIN)){
            $buttons[] = ['extend' => 'export', 'className' => 'dt-buttons btn-sm btn-light'];
            $buttons[] = ['extend' => 'print', 'className' => 'dt-buttons btn-light btn-sm no-corner',];          
                // Enable Buttons as per your need
                //['extend' => 'create', 'className' => 'dt-buttons btn-success btn-sm no-corner',],
               
                //['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner',],
                //['extend' => 'reload', 'className' => 'dt-buttons btn-light btn-sm no-corner',],
            
    }
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['width' => '120px', 'printable' => false])
            ->parameters([
                'dom'       => 'Bfrtip',
                'stateSave' => true,
                'order'     => [[0, 'desc']],
                'buttons'   => $buttons,
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            'id',
            'name',
            'country',
            'parent'=>['title'=>'Parent Service'],
            'status'
            
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'services_' . time();
    }
}
