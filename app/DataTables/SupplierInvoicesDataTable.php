<?php

namespace App\DataTables;

use App\Models\SupplierInvoices;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class SupplierInvoicesDataTable extends DataTable
{
  public function dataTable($query)
  {
    $dataTable = new EloquentDataTable($query);

    $dataTable->addColumn('action', 'supplier_invoices.datatables_actions');

    $dataTable->addColumn('supplier_id', function (SupplierInvoices $supplierInvoices) {
      return @$supplierInvoices->supplier->supplier_id . '-' . @$supplierInvoices->supplier->name;
    });

    $dataTable->addColumn('billing_month', function (SupplierInvoices $supplierInvoices) {
      return date('M Y', strtotime($supplierInvoices->billing_month));
    });

    $dataTable->filterColumn('supplier_id', function ($query, $keyword) {
      $query->whereHas('supplier', function ($q) use ($keyword) {
        $q->where('supplier_id', 'like', "%{$keyword}%")
          ->orWhere('name', 'like', "%{$keyword}%");
      });
    });

    $dataTable->rawColumns(['supplier_id', 'action']);
    return $dataTable;
  }

  public function query(SupplierInvoices $model)
  {
    $query = $model->newQuery()->with(['supplier']);

    if ($this->supplier_id) {
      $query->where('supplier_id', $this->supplier_id);
    }
    if (request('month')) {
      $query->where(\DB::raw('DATE_FORMAT(billing_month, "%Y-%m")'), '=', request('month'));
    }

    return $query;
  }

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
        'buttons' => [],
      ]);
  }

  protected function getColumns()
  {
    return [
      
      'inv_date',
      'inv_id',
      'billing_month',
      'supplier_id' => ['title' => 'Supplier'],
      'descriptions',
      'total_amount'
    ];
  }

  protected function filename(): string
  {
    return 'supplier_invoices_datatable_' . time();
  }
}
