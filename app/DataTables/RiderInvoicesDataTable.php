<?php

namespace App\DataTables;

use App\Helpers\Common;
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

    $dataTable
      ->addColumn('invoice_number', function (RiderInvoices $riderInvoices) {
        $url = route('riderInvoices.show', $riderInvoices->id);
        $label = e($riderInvoices->invoice_number);

        return '<a href="javascript:void(0);" data-action="'.e($url).'" data-size="xl" class="show-modal-right">'.$label.'</a>';
      })
      ->addColumn('inv_date', function (RiderInvoices $riderInvoices) {
        return $riderInvoices->inv_date ? Common::DateFormat($riderInvoices->inv_date) : '';
      })
      ->addColumn('billing_month', function (RiderInvoices $riderInvoices) {
        return date('M Y', strtotime($riderInvoices->billing_month));
      })
      ->addColumn('project', function (RiderInvoices $riderInvoices) {
        return e($riderInvoices->customer?->name ?? $riderInvoices->rider?->customer?->name ?? '—');
      })
      ->editColumn('descriptions', function (RiderInvoices $riderInvoices) {
        return e((string) ($riderInvoices->descriptions ?? ''));
      })
      ->addColumn('status', function (RiderInvoices $riderInvoices) {
        return view('invoices.partials.status_badge', [
          'status' => $riderInvoices->paymentStatusKey(),
        ])->render();
      })
      ->addColumn('paid_amount', function (RiderInvoices $riderInvoices) {
        return number_format((float) $riderInvoices->paid_amount, 2);
      })
      ->addColumn('balance', function (RiderInvoices $riderInvoices) {
        return number_format((float) $riderInvoices->balance, 2);
      });

    $dataTable->filterColumn('inv_date', function ($query, $keyword) {
      $query->whereRaw("DATE_FORMAT(inv_date, '%d-%m-%Y') LIKE ?", ["%{$keyword}%"])
        ->orWhereRaw("DATE_FORMAT(inv_date, '%Y-%m-%d') LIKE ?", ["%{$keyword}%"]);
    });

    $dataTable->filterColumn('billing_month', function ($query, $keyword) {
      $query->whereRaw("DATE_FORMAT(billing_month, '%b %Y') like ?", ["%{$keyword}%"]);
    });

    $dataTable->filterColumn('project', function ($query, $keyword) {
      $query->where(function ($outer) use ($keyword) {
        $outer->whereHas('customer', function ($q) use ($keyword) {
          $q->where('name', 'like', '%'.$keyword.'%');
        })->orWhereHas('rider.customer', function ($q) use ($keyword) {
          $q->where('name', 'like', '%'.$keyword.'%');
        });
      });
    });

    $dataTable->filterColumn('invoice_number', function ($query, $keyword) {
      $keyword = trim((string) $keyword);
      if ($keyword === '') {
        return;
      }

      if (preg_match('/^(?:RINV-?)?0*(\d+)$/i', $keyword, $matches)) {
        $query->where('id', (int) $matches[1]);

        return;
      }

      $query->where('id', 'like', '%'.$keyword.'%');
    });

    $dataTable->rawColumns(['invoice_number', 'status']);

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
    $query = $model->newQuery()->with(['rider.customer', 'customer']);

    if ($this->rider_id) {
      $query->where('rider_id', $this->rider_id);
    }
    if (request('month')) {
      $query->where(DB::raw('DATE_FORMAT(billing_month, "%Y-%m")'), '=', request('month'));
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
      ->parameters([
        'dom' => "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" .
          "<'row'<'col-sm-12'tr>>" .
          "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        'stateSave' => false,
        'ordering' => false,
        'pageLength' => 50,
        'scrollX' => true,
        'autoWidth' => false,
        'responsive' => false,
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
    // Rider profile invoices tab always filters by rider — skip redundant Rider column.
    return [
      'invoice_number' => ['title' => 'Invoice No', 'width' => '110px'],
      'inv_date' => ['title' => 'Inv Date', 'width' => '110px'],
      'billing_month' => ['title' => 'Billing Month', 'width' => '110px'],
      'project' => ['title' => 'Project', 'width' => '140px'],
      'descriptions' => [
        'title' => 'Descriptions',
        'className' => 'col-descriptions',
      ],
      'total_amount' => ['title' => 'Total', 'width' => '100px'],
      'paid_amount' => ['title' => 'Paid Amount', 'width' => '110px'],
      'balance' => ['title' => 'Balance', 'width' => '100px'],
      'status' => ['title' => 'Status', 'width' => '90px'],
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
