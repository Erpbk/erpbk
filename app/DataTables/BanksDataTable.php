<?php

namespace App\DataTables;

use App\Helpers\Accounts;
use App\Models\Banks;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class BanksDataTable extends DataTable
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

    $dataTable->addColumn('action', 'banks.datatables_actions');
    $dataTable->addColumn('name', function (Banks $banks) {
      $name = '<a href="' . route('bank.files', $banks->id) . '">' . $banks->name . '</a><br/>';
      return $name;
    });
    $dataTable
      ->addColumn('status', function (Banks $banks) {
        if ($banks->status == 1) {
          return '<span class="badge  bg-success">Active</span>';
        } else {
          return '<span class="badge  bg-danger">Inactive</span>';
        }
      })
      ->toJson();
    $dataTable->addColumn('balance', function (Banks $banks) {
      return Accounts::getBalance($banks->account_id);
    });
    $dataTable->rawColumns(['status', 'action', 'name']);
    return $dataTable;
  }

  /**
   * Get query source of dataTable.
   *
   * @param \App\Models\Banks $model
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function query(Banks $model)
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
      'title',
      'account_no',
      'balance',
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
    return 'banks_datatable_' . time();
  }
}
