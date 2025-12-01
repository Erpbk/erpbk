<?php

namespace App\DataTables;

use App\Helpers\Common;
use App\Models\Bikes;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class BikesDataTable extends DataTable
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
    $dataTable->addColumn('rider_id', function (Bikes $row) {
      return $row->rider?->rider_id ?? '-';
    });
    $dataTable->addColumn('expiry_date', function (Bikes $row) {
      return Common::DateFormat($row->expiry_date) ?? '-';
    });

    $dataTable->addColumn('rider_name', function (Bikes $row) {
      if ($row->rider_id) {
        return '<a href="' . route('riders.show', $row->rider_id) . '">' . $row->rider?->name . '</a>' ?? '-';
      } else {
        return '-';
      }
    });

    $dataTable->addColumn('company', function (Bikes $row) {
      return $row->LeasingCompany?->name ?? '-';
    });

    $dataTable->filterColumn('rider_id', function ($query, $keyword) {
      $query->whereHas('rider', function ($q) use ($keyword) {
        $q->where('rider_id', 'like', "%{$keyword}%");
      });
    });

    $dataTable->filterColumn('rider_name', function ($query, $keyword) {
      $query->whereHas('rider', function ($q) use ($keyword) {
        $q->where('name', 'like', "%{$keyword}%");
      });
    });

    $dataTable->filterColumn('company', function ($query, $keyword) {
      $query->whereHas('LeasingCompany', function ($q) use ($keyword) {
        $q->where('name', 'like', "%{$keyword}%");
      });
    });

    $dataTable->rawColumns(['action', 'rider_name']);
    $dataTable->addColumn('action', 'bikes.datatables_actions');

    return $dataTable;
  }

  /**
   * Get query source of dataTable.
   *
   * @param \App\Models\Bikes $model
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function query(Bikes $model)
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
      /*  'id', */
      'bike_code' => ['title' => 'Code'],
      'plate',
      'rider_id' => ['title' => 'Rider ID'],
      'rider_name' => ['title' => 'Rider Name'],
      'contract_number' => ['title' => 'Contract#'],
      'emirates',
      'company',
      'expiry_date' => ['title' => 'Expiry'],
    ];
  }

  /**
   * Get filename for export.
   *
   * @return string
   */
  protected function filename(): string
  {
    return 'bikes_datatable_' . time();
  }
}
