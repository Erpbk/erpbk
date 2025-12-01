<?php

namespace App\DataTables;

use App\Models\RtaFines;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class RtaFinesDataTable extends DataTable
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

    $dataTable->addColumn('action', 'rta_fines.datatables_actions');

    $dataTable->addColumn('rider_id', function (RtaFines $rtaFines) {
      if ($rtaFines->rider_id) {
        return '<a href="' . route('riders.show', $rtaFines->rider_id) . '">' . $rtaFines->rider?->name . '</a>' ?? '-';
      } else {
        return '-';
      }
    });

    $dataTable->rawColumns(['action', 'rider_id']);
    return $dataTable;
  }

  /**
   * Get query source of dataTable.
   *
   * @param \App\Models\RtaFines $model
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function query(RtaFines $model)
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
      'ticket_no',
      'trip_date',
      'trip_time',
      'rider_id' => ['title' => 'Rider'],
      'billing_month',
      'plate_no',
      'total_amount' => ['title' => 'Amount']
    ];
  }

  /**
   * Get filename for export.
   *
   * @return string
   */
  protected function filename(): string
  {
    return 'rta_fines_datatable_' . time();
  }
}
