<?php

namespace App\DataTables;

use App\Models\Accounts;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Facades\DataTables;

class AccountsDataTable extends DataTable
{
  /**
   * Build DataTable class.
   *
   * @param mixed $query Results from query() method.
   * @return \Yajra\DataTables\DataTableAbstract
   */
  public function dataTable($query)
  {
    /*  $tree = $this->getAccountTree(); // Fetch hierarchical data
     $flattenedData = $this->flattenAccountTree($tree); */

    // Use DataTables::of() for handling collections
    $dataTable = DataTables::of($query)
      ->addColumn('action', 'accounts.datatables_actions');

    $dataTable
      ->addColumn('parent_id', function (Accounts $accounts) {
        return @$accounts->parent->name ?? '';
      })
      ->toJson();
    $dataTable = $dataTable->rawColumns(['action', 'name', 'parent_id']);

    return $dataTable;
  }

  /**
   * Get query source of dataTable.
   *
   * @param \App\Models\Accounts $model
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function query(Accounts $model)
  {
    return $model->newQuery()->with('parent')->orderBy('parent_id');
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
          /* ['extend' => 'create', 'className' => 'btn btn-default btn-sm no-corner'],
          ['extend' => 'export', 'className' => 'btn btn-default btn-sm no-corner'],
          ['extend' => 'print', 'className' => 'btn btn-default btn-sm no-corner'],
          ['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner'],
          ['extend' => 'reload', 'className' => 'btn btn-default btn-sm no-corner'], */
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

      'id' => ['title' => 'ID'],
      'name' => ['title' => 'Name'],
      'account_code' => ['title' => 'Code'],
      'parent_id' => ['title' => 'Parent'],
      'account_type' => ['title' => 'Type'],
      'opening_balance' => ['title' => 'Balance']
    ];
  }

  /**
   * Get filename for export.
   *
   * @return string
   */
  protected function filename(): string
  {
    return 'accounts_datatable_' . time();
  }

  private function getAccountTree($parentId = null)
  {
    return Accounts::where('parent_id', $parentId)
      ->with([
        'children' => function ($query) {
          $query->with('children'); // Recursive loading
        }
      ])
      ->get();
  }
  private function flattenAccountTree($accounts, $level = 0)
  {
    $result = [];
    foreach ($accounts as $account) {
      $result[] = [
        'id' => $account->id,
        'account_code' => $account->account_code,
        'account_type' => $account->account_type,
        'opening_balance' => $account->opening_balance,
        'name' => str_repeat('-', $level) . ' ' . $account->name,
        /*  'parent_id' => @$account->parent->name ?? '-', */

      ];
      if ($account->children->isNotEmpty()) {
        $result = array_merge($result, $this->flattenAccountTree($account->children, $level + 1));
      }
    }
    return $result;
  }

}
