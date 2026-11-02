<?php

namespace App\DataTables;

use App\Helpers\Common;
use App\Models\RiderAttendance;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class RiderAttendanceDataTable extends DataTable
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
    $dataTable->addColumn('date', function (RiderAttendance $row) {
      return Common::DateFormat($row->date);
    });

    $dataTable->addColumn('rider_id', function (RiderAttendance $row) {
      return $row->rider->name ?? '';
    });

    $dataTable->filterColumn('rider_id', function ($query, $keyword) {
      $query->whereHas('rider', function ($q) use ($keyword) {
        $q->where('name', 'like', "%{$keyword}%");
      });
    });

    $dataTable->addColumn('action', 'rider_attendances.datatables_actions');
    return $dataTable;
  }

  /**
   * Get query source of dataTable.
   *
   * @param \App\Models\RiderAttendance $model
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function query(RiderAttendance $model)
  {
    $query = $model->newQuery();
    if ($this->rider_id) {
      $query->where('rider_id', $this->rider_id);
    }
    return $query->orderByDesc('date');
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
      //->addAction(['width' => '120px', 'printable' => false])
      ->parameters([
        'dom' => 'Bfrtip',
        'ordering' => false,
        'pageLength' => 50,
        'responsive' => true,
        'stateSave' => false,
        'order' => [[0, 'desc']],
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
      'date',
      'd_rider_id' => ['title' => 'Rider ID'],
      'rider_id' => ['title' => 'Name'],
      'shift',
      'day',
      'attendance',
      'cdm_id',

    ];
  }

  /**
   * Get filename for export.
   *
   * @return string
   */
  protected function filename(): string
  {
    return 'rider_attendances_datatable_' . time();
  }
}
