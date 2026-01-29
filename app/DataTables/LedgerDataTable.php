<?php

namespace App\DataTables;

use App\Helpers\Common;
use App\Models\Transactions;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\DB;

class LedgerDataTable extends DataTable
{
  /**
   * Build DataTable class.
   */
  public function dataTable($query)
  {
    if (request()->has('action')) {
      @ini_set('memory_limit', '1024M');
      @set_time_limit(0);
    }
    $transactions = $query->get();
    $openingBalance = $this->getOpeningBalance();

    $data = [];
    $runningBalance = $openingBalance;
    $totalDebit = 0;
    $totalCredit = 0;

    // Add Balance Forward row at the top
    $data[] = [
      'date' => '',
      'account_name' => '',
      'reference_number' => '',
      'billing_month' => '',
      'voucher' => '',
      'narration' => '<b>Balance Forward</b>',
      'debit' => '',
      'credit' => '',
      'balance' => number_format($openingBalance, 2),
    ];

    // Process transactions and maintain running balance
    foreach ($transactions as $row) {

      $runningBalance += $row->debit - $row->credit;
      $totalDebit += $row->debit;
      $totalCredit += $row->credit;

      $view_file = '';
      $voucher_ID = '';
      $voucher_text = '';
      if (isset($row->voucher->attach_file)) {
        if ($row->reference_type == 'RTA') {
          $view_file = '  <a href="' . url('storage/' . $row->voucher->attach_file) . '" class="no-print"  target="_blank">View File</a>';
        } elseif ($row->reference_type == 'LV') {
          $view_file = '  <a href="' . url('storage/' . $row->voucher->attach_file) . '" class="no-print"  target="_blank">View File</a>';
        } else {
          $view_file = '  <a href="' . url('storage/vouchers/' . $row->voucher->attach_file) . '" class="no-print"  target="_blank">View File</a>';
        }
      }
      if ($row->reference_type == 'Voucher') {
        $vouchers =  DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
        if ($vouchers) {
          $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
          $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
        } else {
          $voucher_text = '<span class="text-danger">No Voucher Found</span>';
        }
      }
      if ($row->reference_type == 'RTA') {
        $vouchers =  DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
        if ($vouchers) {
          $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
          $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
        } else {
          $voucher_text = '<span class="text-danger">No Voucher Found</span>';
        }
      }
      if ($row->reference_type == 'LV' || $row->reference_type == 'VL') {
        $vouchers =  DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
        if ($vouchers) {
          $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
          $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
        } else {
          $voucher_text = '<span class="text-danger">No Voucher Found</span>';
        }
      }
      if ($row->reference_type == 'INC') {
        $vouchers =  DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
        if ($vouchers) {
          $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
          $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
        } else {
          $voucher_text = '<span class="text-danger">No Voucher Found</span>';
        }
      }
      if ($row->reference_type == 'PN') {
        $vouchers =  DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
        if ($vouchers) {
          $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
          $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
        } else {
          $voucher_text = '<span class="text-danger">No Voucher Found</span>';
        }
      }
      if ($row->reference_type == 'PAY') {
        $vouchers =  DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
        if ($vouchers) {
          $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
          $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
        } else {
          $voucher_text = '<span class="text-danger">No Voucher Found</span>';
        }
      }
      if ($row->reference_type == 'COD') {
        $vouchers =  DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
        if ($vouchers) {
          $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
          $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
        } else {
          $voucher_text = '<span class="text-danger">No Voucher Found</span>';
        }
      }
      if ($row->reference_type == 'Salik Voucher') {
        $vouchers =  DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
        if ($vouchers) {
          $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
          $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
        } else {
          $voucher_text = '<span class="text-danger">No Voucher Found</span>';
        }
      }
      if ($row->reference_type == 'VC') {
        $vouchers =  DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
        if ($vouchers) {
          $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
          $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
        } else {
          $voucher_text = '<span class="text-danger">No Voucher Found</span>';
        }
      }
      if ($row->reference_type == 'AL') {
        $vouchers =  DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
        if ($vouchers) {
          $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
          $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
        } else {
          $voucher_text = '<span class="text-danger">No Voucher Found</span>';
        }
      }
      if ($row->reference_type == 'Invoice') {
        $invoice_ID = $row->reference_id;
        $voucher_text = '<span class="d-none">RD-' . $invoice_ID . '</span><a href="javascript:void(0);" data-title="Invoice # ' . $invoice_ID . '" data-size="xl" data-action="' . route('riderInvoices.show', $invoice_ID) . '" class="no-print show-modal">RD-' . $invoice_ID . '</a>';
      }
      if ($row->reference_type == 'RiderInvoice') {
        $vouchers =  DB::table('vouchers')->where('trans_code', $row->trans_code)->first();
        if ($vouchers) {
          $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
          $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
        } else {
          $voucher_text = '<span class="text-danger">No Voucher Found</span>';
        }
      }
      $month = "<span style='white-space: nowrap;'>" . date('M Y', strtotime($row->billing_month)) . "</span>";
      if ($row->reference_type == 'RTA') {
        $vouchers = DB::table('vouchers')->where('trans_code', $row->trans_code)->first();

        if ($vouchers) {
          $fines = DB::table('rta_fines')->where('id', $vouchers->ref_id)->first();

          if ($fines) {
            $naration = $row->narration . ', <b>Ticket Number: </b>' . $fines->ticket_no . ', <b>Bike No: </b>' . $fines->plate_no . ', ' . \Carbon\Carbon::parse($fines->trip_date)->format('d M Y') . ', ' . $view_file;
          } else {
            $naration = $row->narration . ', ' . $view_file;
          }
        } else {
          $naration = $row->narration . ', ' . $view_file;
        }
      } elseif ($row->reference_type == 'LV') {
        $visaex = DB::table('visa_expenses')->where('id', $row->reference_id)->first();
        if ($visaex) {
          $rider = DB::Table('accounts')->where('id', $visaex->rider_id)->first();
          if ($rider) {
            $naration = 'Paid to <b>' . $rider->name . ' </b>' . $visaex->visa_status . ' Charges ' . $visaex->date . $view_file;
          } else {
            $naration = $row->narration . ' (Rider not found) ' . $view_file;
          }
        } else {
          $naration = $row->narration . ' (Visa expense not found) ' . $view_file;
        }
      } else {
        $naration = $row->narration . ', ' . $view_file;
      }
      $reference = '-';
      if($row->voucher){
        $reference = $row->voucher->reference_number ?? '-';
      }
      $data[] = [
        'date' => "<span style='white-space: nowrap;'>" . Common::DateFormat($row->trans_date) . "</span>",
        'account_name' => ($row->account->account_code ?? 'N/A') . '-' . ($row->account->name ?? 'N/A'),
        'reference_number' => $reference,
        'billing_month' => $month,
        'voucher' => $voucher_text,
        'narration' => $naration,
        'debit' => number_format($row->debit, 2),
        'credit' => number_format($row->credit, 2),
        'balance' => number_format($runningBalance, 2),
      ];
    }
    $data[] = [
      'date' => '',
      'account_name' => '',
      'reference_number' => '',
      'billing_month' => '',
      'voucher' => '',
      'narration' => '<b>Total</b>',
      'debit' => '<b>' . number_format($totalDebit, 2) . '</b>',
      'credit' => '<b>' . number_format($totalCredit, 2) . '</b>',
      'balance' => '<b>' . number_format($runningBalance, 2) . '</b>',
    ];
    return datatables()->of($data)->rawColumns(['date', 'debit', 'credit', 'balance', 'narration', 'voucher', 'billing_month']);
  }
  /**
   * Get query source of dataTable.
   */
  public function query(Transactions $model)
  {
    $query = $model->newQuery()->with(['account']);

    if (request('account')) {
      $query->where('account_id', request('account'));
    }
    if ($this->account_id) {
      $query->where('account_id', $this->account_id);
    }

    if (request('month')) {
      $query->where('billing_month', request('month') . '-01');
    }
    $query = $query->orderBy('billing_month', 'ASC')->orderBy('trans_date', 'ASC');
    return $query;
  }

  /**
   * Get Opening Balance before the selected date.
   */
  private function getOpeningBalance()
  {
    if (!request('month')) {
      return 0;
    }

    if (request('account')) {
      $account_id = request('account');
    } else {
      $account_id = $this->account_id;
    }
    return Transactions::where('account_id', $account_id)
      ->whereDate('billing_month', '<', request('month') . '-01')
      ->sum(DB::raw("debit - credit"));
  }

  /**
   * Optional method if you want to use HTML builder.
   */
  public function html()
  {
    return $this->builder()
      ->columns($this->getColumns())
      ->minifiedAjax()
      ->parameters([
        'dom' => "<'row'<'col-md-6'><'col-md-6 d-flex justify-content-end'B>>" . // Export buttons fully right-aligned
          "<'row'<'col-md-6'><'col-md-6'f>>" . // Search box on the right
          "<'row'<'col-md-12'tr>>" .
          "<'row'<'col-md-5'i><'col-md-7'p>>", // Info (left) and Pagination (right)
        'order' => [[0, 'asc']], // Order by date ascending
        'ordering' => false,
        'pageLength' => 50,
        'stateSave' => true, // Ensures balance maintains on pagination
        'responsive' => true,
        'initComplete' => "function(settings, json) {
                // Jump to last page after initialization
                var api = this.api();
                api.on('xhr.dt', function(e, settings, json) {
                  if (json && json.data && json.data.length > 0) {
                    setTimeout(function() {
                        var lastPage = api.page.info().pages - 1;
                        if (lastPage >= 0) {
                          api.page(lastPage).draw('page');
                        }
                    }, 100);
                  }
                });
                var urlParams = new URLSearchParams(window.location.search);
                var month = urlParams.get('month');
                if (month) {
                    api.state.clear();
                }
            }",
        'footerCallback' => "function(row, data, start, end, display) {
                    var api = this.api();
                    var intVal = function(i) {
                        return typeof i === 'string' ? parseFloat(i.replace(/[\$,]/g, '')) : (typeof i === 'number' ? i : 0);
                    };

                    totalDebit = api.column(5, { page: 'current' }).data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);
                    totalCredit = api.column(6, { page: 'current' }).data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);
                    totalBalance = api.column(7, { page: 'current' }).data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);

                    $(api.column(5).footer()).html('<b>' + totalDebit.toFixed(2) + '</b>');
                    $(api.column(6).footer()).html('<b>' + totalCredit.toFixed(2) + '</b>');
                    $(api.column(7).footer()).html('<b>' + totalBalance.toFixed(2) + '</b>');
                }",
        'buttons' => [
          [
            'text' => '<i class="fa fa-file-excel"></i>&nbsp;Export to Excel',
            'className' => 'btn btn-success btn-sm no-corner',
            'action' => 'function(e, dt, button, config) {
              var account = new URLSearchParams(window.location.search).get("account");
              var month = new URLSearchParams(window.location.search).get("month");
              var url = "' . route('ledger.export') . '?";
              if (account) url += "account=" + account + "&";
              if (month) url += "month=" + month;
              window.location.href = url;
            }'
          ],
          ['extend' => 'print', 'className' => 'btn btn-primary btn-sm no-corner', 'text' => '<i class="fa fa-print"></i>&nbsp;Print'],
        ],
        /* 'language' => [
          'processing' => '<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>'
        ], */
      ]);
  }

  /**
   * Get columns.
   */
  protected function getColumns()
  {
    return [
      'date',
      'account_name' => ['title' => 'Account'],
      'reference_number' => ['title' => 'Reference'],
      'billing_month' => ['title' => 'Month'],
      'voucher',
      'narration',
      'debit',
      'credit',
      'balance'
    ];
  }

  /**
   * Get filename for export.
   */
  protected function filename(): string
  {
    return 'ledger_datatable_' . time();
  }
}
