<?php

namespace App\DataTables;

use App\Models\RiderInvoices;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\DB;

class RiderInvoicesDataTable extends DataTable
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

    $dataTable->addColumn('action', 'rider_invoices.datatables_actions');

    $dataTable
      ->addColumn('rider_id', function (RiderInvoices $riderInvoices) {
        return @$riderInvoices->rider->rider_id . '-' . @$riderInvoices->rider->name;
      });
    $dataTable
      ->addColumn('billing_month', function (RiderInvoices $riderInvoices) {
        return date('M Y', strtotime($riderInvoices->billing_month));
      });
    $dataTable
      ->addColumn('status', function (RiderInvoices $riderInvoices) {
        return $riderInvoices->status == 1 ? 'Paid' : 'Unpaid';
      });

    // Remove status column from DataTable
    $dataTable->filterColumn('billing_month', function ($query, $keyword) {
      $query->whereRaw("DATE_FORMAT(billing_month, '%b %Y') like ?", ["%{$keyword}%"]);
    });
    $dataTable->filterColumn('rider_id', function ($query, $keyword) {

      $query->whereHas('rider', function ($q) use ($keyword) {
        $q->where('rider_id', 'like', "%{$keyword}%")
          ->orWhere('name', 'like', "%{$keyword}%");
      });
    });

    $dataTable->rawColumns(['rider_id', 'action']);
    return $dataTable;
  }

  /**
   * Get query source of dataTable.
   *
   * @param \App\Models\RiderInvoices $model
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function query(RiderInvoices $model)
  {
    $query = $model->newQuery()->with(['rider']);

    if ($this->rider_id) {
      $query->where('rider_id', $this->rider_id);
    }
    if (request('month')) {
      $query->where(\DB::raw('DATE_FORMAT(billing_month, "%Y-%m")'), '=', request('month'));
    }
    if (request('rider_id')) {
      $query->where('rider_id', request('rider_id'));
    }

    return $query;
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
        'ordering' => false,
        'pageLength' => 50,
        'responsive' => true,
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
      'id',
      'inv_date',
      'billing_month',
      'rider_id' => ['title' => 'Rider'],
      'descriptions',
      'subtotal',
      'vat',
      'total_amount',
      'status', // Add status column
    ];
  }

  /**
   * Get filename for export.
   *
   * @return string
   */
  protected function filename(): string
  {
    return 'rider_invoices_datatable_' . time();
  }
}
