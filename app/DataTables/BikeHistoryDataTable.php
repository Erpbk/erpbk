<?php

namespace App\DataTables;

use App\Helpers\Common;
use App\Models\BikeHistory;
use Storage;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class BikeHistoryDataTable extends DataTable
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

    $dataTable->addColumn('note_date', function (BikeHistory $row) {
      return $row->note_date ? Common::DateFormat($row->note_date) : '';
    });

    $dataTable->addColumn('project_name', function (BikeHistory $row) {
      if ($row->customer_id) {
        $customer = \App\Models\Customers::find($row->customer_id);
        return $customer->name ?? $row->customer_id;
      }

      return '';
    });

    $dataTable->addColumn('branch_name', function (BikeHistory $row) {
      return $row->branch->name ?? ($row->branch_id ?: '');
    });

    $dataTable->addColumn('bike_number_display', function (BikeHistory $row) {
      return $row->bike_number ?: (@$row->bike->plate ?? '');
    });

    $dataTable->addColumn('history_status_display', function (BikeHistory $row) {
      $status = $row->history_status ?: $row->warehouse;
      if (!$status) {
        return '';
      }
      $class = strtolower($status) === 'joining' ? 'badge bg-label-success' : 'badge bg-label-secondary';

      return '<span class="' . $class . '">' . e($status) . '</span>';
    });

    $dataTable->addColumn('rider_id', function (BikeHistory $row) {
      return @$row->rider->name ?? '';
    });

    $dataTable->addColumn('contract', function (BikeHistory $row) {
      if ($row->contract) {
        return '<a href="' . Storage::url('app/contract/' . $row->contract) . '" data-toggle="tooltip" class="file btn btn-success  btn-sm mr-1" data-modalID="modal-new" target="_blank"><i class="fas fa-file"></i>&nbsp; Signed Contract</a>';
      }

      return 'N/A';
    });

    $dataTable->rawColumns(['contract', 'action', 'history_status_display']);
    return $dataTable->addColumn('action', 'bike_histories.datatables_actions');
  }

  /**
   * Get query source of dataTable.
   *
   * @param \App\Models\BikeHistory $model
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function query(BikeHistory $model)
  {
    $query = $model->newQuery()->with(['branch', 'bike', 'rider']);

    if (request('bike_id')) {
      $query->where('bike_id', request('bike_id'));
    }

    return $query->orderByDesc('id');
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
        'buttons' => [],
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
      'note_date' => ['title' => 'Date', 'data' => 'note_date'],
      'project_name' => ['title' => 'Project', 'orderable' => false, 'searchable' => false],
      'branch_name' => ['title' => 'Branch', 'orderable' => false, 'searchable' => false],
      'bike_number_display' => ['title' => 'Bike Number', 'orderable' => false, 'searchable' => false],
      'fleet_supervisor' => ['title' => 'Fleet Supervisor'],
      'history_status_display' => ['title' => 'Status', 'orderable' => false, 'searchable' => false],
      'rider_id' => ['title' => 'Rider'],
      'notes' => ['title' => 'Notes', 'visible' => false],
      'contract' => ['title' => 'Contract', 'visible' => false],
    ];
  }

  /**
   * Get filename for export.
   *
   * @return string
   */
  protected function filename(): string
  {
    return 'bike_histories_datatable_' . time();
  }
}
