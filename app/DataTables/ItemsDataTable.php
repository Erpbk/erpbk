<?php

namespace App\DataTables;

use App\Models\Items;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class ItemsDataTable extends DataTable
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

    $dataTable->addColumn('action', 'items.datatables_actions');
    $dataTable
      ->addColumn('status', function (Items $items) {
        if ($items->status == 1) {
          return '<span class="badge  bg-success">Active</span>';
        } else {
          return '<span class="badge  bg-danger">Inactive</span>';
        }
      })
      ->toJson();
    $dataTable->addColumn('customer_id', function (Items $items) {
      return $items?->customer?->name ?? ' - ';
    });
    $dataTable->addColumn('supplier_id', function (Items $items) {
      return $items?->supplier?->name ?? ' - ';
    });
    $dataTable->rawColumns(['status', 'action']);
    return $dataTable;
  }

  /**
   * Get query source of dataTable.
   *
   * @param \App\Models\Items $model
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function query(Items $model)
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
    return $this->builder()
      ->columns($this->getColumns())
      ->minifiedAjax()
      ->addAction(['width' => '120px', 'printable' => false])
      ->parameters([
        'dom' => 'Bfrtip',
        'stateSave' => false,
        'order' => [[0, 'desc']],
        'pageLength' => 100,
        'responsive' => true,
        'buttons' => [
          // Enable Buttons as per your need
//                    ['extend' => 'create', 'className' => 'btn btn-default btn-sm no-corner',],
//                    ['extend' => 'export', 'className' => 'btn btn-default btn-sm no-corner',],
//                    ['extend' => 'print', 'className' => 'btn btn-default btn-sm no-corner',],
//                    ['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner',],
//                    ['extend' => 'reload', 'className' => 'btn btn-default btn-sm no-corner',],
        ],
        'language' => [
          'processing' => '<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>'
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
      'customer_id' => ['title' => 'Customer'],
      'supplier_id' => ['title' => 'Supplier'],
      'name',
      'price',
      'vat',
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
    return 'items_datatable_' . time();
  }
}
