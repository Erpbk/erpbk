<?php

namespace App\DataTables;

use App\Helpers\Common;
use App\Helpers\General;
use App\Models\Vouchers;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\DB;

class VouchersDataTable extends DataTable
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


    $dataTable->addColumn('id', function (Vouchers $row) {
      $voucherId = $row->voucher_type . '-' . str_pad($row->id, '4', '0', STR_PAD_LEFT);
      return '<a href="' . route('vouchers.show', $row->id) . '" class="text-primary" target="_blank">' . $voucherId . '</a>';
    })->filterColumn('id', function ($query, $keyword) {
      $query->whereRaw("CONCAT(voucher_type, '-', LPAD(id, 4, '0')) LIKE ?", ["%{$keyword}%"]);
    })->toJson();

    $dataTable->addColumn('trans_date', function (Vouchers $row) {
      return Common::DateFormat($row->trans_date);
    })->filterColumn('trans_date', function ($query, $keyword) {
      $query->whereRaw("DATE_FORMAT(trans_date, '%d-%m-%Y') LIKE ?", ["%{$keyword}%"])
        ->orWhereRaw("DATE_FORMAT(trans_date, '%Y-%m-%d') LIKE ?", ["%{$keyword}%"]);
    })->toJson();

    $dataTable->addColumn('trans_code', function (Vouchers $row) {
      return $row->trans_code;
    })->filterColumn('trans_code', function ($query, $keyword) {
      $query->where('trans_code', 'LIKE', "%{$keyword}%");
    })->toJson();

    $dataTable->addColumn('billing_month', function (Vouchers $row) {
      return Common::MonthFormat($row->billing_month);
    })->filterColumn('billing_month', function ($query, $keyword) {
      $query->whereRaw("DATE_FORMAT(billing_month, '%M %Y') LIKE ?", ["%{$keyword}%"])
        ->orWhereRaw("DATE_FORMAT(billing_month, '%Y-%m') LIKE ?", ["%{$keyword}%"]);
    })->toJson();

    $dataTable->addColumn('voucher_type', function (Vouchers $row) {
      return General::VoucherType($row->voucher_type);
    })->filterColumn('voucher_type', function ($query, $keyword) {
      $voucherTypes = \App\Helpers\General::VoucherType();
      $matchingTypes = [];
      foreach ($voucherTypes as $code => $name) {
        if (stripos($name, $keyword) !== false || stripos($code, $keyword) !== false) {
          $matchingTypes[] = $code;
        }
      }
      if (!empty($matchingTypes)) {
        $query->whereIn('voucher_type', $matchingTypes);
      }
    })->toJson();

    $dataTable->addColumn('Created_By', function (Vouchers $row) {
      return Common::UserName($row->Created_By);
    })->toJson();

    $dataTable->addColumn('Updated_By', function (Vouchers $row) {
      return Common::UserName($row->Updated_By);
    })->toJson();
    $dataTable->addColumn('attach_file', function (Vouchers $row) {
      $view_file = '';
      if ($row->attach_file) {
        if ($row->voucher_type == 'RFV') {
          $view_file = '  <a href="' . url('storage/' . $row->attach_file) . '" class="no-print"  target="_blank">View</a>';
        } else {
          $view_file = '  <a href="' . url('storage/vouchers/' . $row->attach_file) . '" class="no-print"  target="_blank">View</a>';
        }
      }
      return $view_file;
    })->toJson();




    $dataTable->rawColumns(['role', 'action', 'attach_file', 'id']);
    $dataTable->addColumn('action', 'vouchers.datatables_actions');

    return $dataTable;
  }

  /**
   * Get query source of dataTable.
   *
   * @param \App\Models\Vouchers $model
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function query(Vouchers $model)
  {
    return $model->newQuery()
      ->select([
        'vouchers.*',
        DB::raw("CONCAT(voucher_type, '-', LPAD(id, 4, '0')) as voucher_id")
      ])
      ->orderByDesc('id');
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
        'pageLength' => 50,
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
      'id' => ['title' => 'Voucher ID', 'searchable' => true],
      'trans_date' => ['title' => 'Date', 'searchable' => true],
      'trans_code' => ['title' => 'Trans Code', 'searchable' => true],
      'billing_month' => ['title' => 'Month', 'searchable' => true],
      'voucher_type' => ['title' => 'Type', 'searchable' => true],
      'amount' => ['title' => 'Amount', 'searchable' => true],
      'Created_By' => ['title' => 'Created By', 'searchable' => false],
      'Updated_By' => ['title' => 'Updated By', 'searchable' => false],
      'attach_file' => ['title' => 'File', 'searchable' => false, 'orderable' => false]
    ];
  }

  /**
   * Get filename for export.
   *
   * @return string
   */
  protected function filename(): string
  {
    return 'vouchers_datatable_' . time();
  }
}
