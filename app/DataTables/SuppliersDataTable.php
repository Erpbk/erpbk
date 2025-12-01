<?php

namespace App\DataTables;

use App\Models\Supplier;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\Route;



class SuppliersDataTable extends DataTable
{
  
  public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->addColumn('action', function ($supplier) {
                return view('suppliers.datatables_actions', compact('supplier'))->render();
            })
            ->addColumn('name', function ($supplier) {
            $name = '<a href="' . route('suppliers.show', $supplier->id) . '">' . e($supplier->name) . '</a><br/>';
            return $name;
        })
        ->rawColumns(['name', 'action']); // ← THIS IS REQUIRED to render HTML

    }

 
  public function query(Supplier $model)
  {
    return $model->newQuery();
  }

  
  public function html()
  {
    return $this->builder()
      ->columns($this->getColumns())
      ->minifiedAjax()
      ->addAction(['width' => '120px', 'printable' => false])
      ->parameters([
        'dom' => 'Bfrtip',
        'stateSave' => true,
        'order' => [[0, 'desc']],
        'buttons' => [
          // Enable Buttons as per your need
//                    ['extend' => 'create', 'className' => 'btn btn-default btn-sm no-corner',],
//                    ['extend' => 'export', 'className' => 'btn btn-default btn-sm no-corner',],
//                    ['extend' => 'print', 'className' => 'btn btn-default btn-sm no-corner',],
//                    ['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner',],
//                    ['extend' => 'reload', 'className' => 'btn btn-default btn-sm no-corner',],
        ],
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
      'name',
      'email',
      'phone',
      'company_name',
      'address',
      
    ];
  }

  /**
   * Get filename for export.
   *
   * @return string
   */
  protected function filename(): string
  {
    return 'suppliers_datatable_' . time();
  }
}
