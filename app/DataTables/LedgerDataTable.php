<?php

namespace App\DataTables;

use App\Helpers\Common;
use App\Models\BikeMaintenance;
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
      if ($row->reference_type == 'Bike Maintenance') {
        $maintenance = BikeMaintenance::where('id',$row->reference_id)->first();
        $voucher_ID = 'MAINT-'.str_pad($maintenance->id, 6, '0', STR_PAD_LEFT);
        $voucher_text = '<span class="d-none">' . $voucher_ID . '</span><a href="' . route('bike-maintenance.invoice', $maintenance) . '" target="_blank" class="no-print" >' . $voucher_ID . '</a>';
        if($maintenance->attachment)
          $view_file = '  <a href="' . url('storage2/' . $maintenance->attachment) . '" class="no-print"  target="_blank">View File</a>';
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
    $accountid = '';
    $accountName = "All Accounts";
    if ($this->account_id) {
        $account = \App\Models\Accounts::find($this->account_id);
        $accountid = $account->id;
        $accountName = $account ? $account->account_code . '-' . $account->name : "All Accounts";
    }
    
    return $this->builder()
      ->columns($this->getColumns())
      ->minifiedAjax()
      ->parameters([
        'dom' => "<'row'<'col-md-6'><'col-md-6 d-flex justify-content-end'B>>" . // Export buttons fully right-aligned
          "<'row'<'col-md-6'><'col-md-6'f>>" . // Search box on the right
          "<'row'<'col-md-12'tr>>" .
          "<'row'<'col-md-5'i l><'col-md-7'p>>", // Info (left) and Pagination (right)
        'order' => [[0, 'asc']], // Order by date ascending
        'ordering' => false,
        'pageLength' => 50,
        'lengthMenu' => [
                [50, 100, 150, 200, -1], // Values to display in the dropdown
                [50, 100, 150, 200, 'All'] // Labels for the dropdown
            ],
        'stateSave' => false, 
        'responsive' => true,
        'footerCallback' => "function(row, data, start, end, display) {
                    var api = this.api();
                    var intVal = function(i) {
                        return typeof i === 'string' ? parseFloat(i.replace(/[\$,]/g, '')) : (typeof i === 'number' ? i : 0);
                    };

                    totalDebit = api.column(6, { page: 'current' }).data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);
                    totalCredit = api.column(7, { page: 'current' }).data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);
                    totalBalance = api.column(8, { page: 'current' }).data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);

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
              if(!account){
                account = "' . $accountid . '";
              }
              var month = new URLSearchParams(window.location.search).get("month");
              var url = "' . route('ledger.export') . '?";
              if (account) url += "account=" + account + "&";
              if (month) url += "month=" + month;
              window.location.href = url;
            }'
          ],
          [
            'extend' => 'print',
            'className' => 'btn btn-primary btn-sm no-corner',
            'text' => '<i class="fa fa-print"></i>&nbsp;Print',
            'title' => '',
            'autoPrint' => false,
            'exportOptions' => [
                'columns' => ':visible',
            ],
            'customize' => 'function(win) {
                // COMPLETELY override the print functionality
                // Don\'t let DataTables do its default print
                
                // Get the table
                var $table = $(win.document.body).find("table.dataTable");
                if ($table.length === 0) {
                    // If no table found, just do default behavior
                    return;
                }
                
                var totalRows = $table.find("tbody tr").length;
                var accountText = "' . $accountName . '";

                var searchValue = $(".dataTable").DataTable().search();
                var searchText = searchValue ? \'<strong>Search:</strong> \' + searchValue + \' | \' : \'\';
                
                // Get filter values from the page
                var accountText = $("select[name=\'account\'] option:selected").text() || accountText;
                var monthValue = $("input[name=\'month\']").val();
                var monthText = formatMonthText(monthValue);
                
                // Format month function
                function formatMonthText(monthValue) {
                    if (!monthValue) return "All Months";
                    var parts = monthValue.split("-");
                    var year = parts[0];
                    var month = parseInt(parts[1]);
                    var monthNames = ["January", "February", "March", "April", "May", "June",
                                      "July", "August", "September", "October", "November", "December"];
                    return monthNames[month - 1] + " " + year;
                }
                
                // Get current date
                var currentDate = new Date().toLocaleDateString("en-US", {
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                    hour: "2-digit",
                    minute: "2-digit"
                });
                
                // Remove DataTables classes for clean print
                $table.removeClass("dataTable display");
                $table.find(".sorting, .sorting_asc, .sorting_desc").removeClass("sorting sorting_asc sorting_desc");
                $table.find("th").removeAttr("tabindex");
                
                // Create custom print layout
                var printContent = \'<!DOCTYPE html>\' +
                    \'<html>\' +
                    \'<head>\' +
                    \'<title>Ledger Report</title>\' +
                    \'<meta charset="UTF-8">\' +
                    \'<style>\' +
                    \'@page { margin: 1cm; }\' +
                    \'body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }\' +
                    \'.print-header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }\' +
                    \'.print-header h2 { margin: 0 0 5px 0; font-size: 18px; }\' +
                    \'.print-header .info { font-size: 11px; color: #666; }\' +
                    \'table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 10px; }\' +
                    \'table th, table td { border: 1px solid #000; padding: 6px; text-align: left; }\' +
                    \'table th { background-color: #f0f0f0; font-weight: bold; text-align: center; }\' +
                    \'.print-footer { margin-top: 20px; font-size: 10px; color: #666; text-align: center; }\' +
                    \'.no-print { display: none !important; }\' +
                    \'@media print { \' +
                    \'body { padding: 0; }\' +
                    \'.print-header { page-break-after: avoid; }\' +
                    \'table { page-break-inside: auto; }\' +
                    \'table tr { page-break-inside: avoid; page-break-after: auto; }\' +
                    \'}\' +
                    \'</style>\' +
                    \'</head>\' +
                    \'<body>\' +
                    \'<div class="print-header">\' +
                    \'<h2>Account Statement</h2>\' +
                    \'<div class="info">\' +
                    \'<strong>Account:</strong> \' + accountText + \' | \' +
                    \'<strong>Month:</strong> \' + monthText + \' | \' +
                    searchText +
                    \'<strong>Total Transactions:</strong> \' + totalRows + \' | \' +
                    \'<strong>Generated:</strong> \' + currentDate +
                    \'</div>\' +
                    \'</div>\' +
                    $table[0].outerHTML +
                    \'<div class="print-footer">Generated on \' + currentDate + 
                    \'</body>\' +
                    \'</html>\';
                
                // Close the DataTables print window immediately
                win.close();
                
                // Open our custom print window
                var customPrintWindow = window.open(\'\', \'_blank\', \'width=1200,height=800\');
                customPrintWindow.document.write(printContent);
                customPrintWindow.document.close();
                
                // Auto-print after content loads
                setTimeout(function() {
                    customPrintWindow.focus();
                    customPrintWindow.print();
                    
                    // Optionally close after printing
                    customPrintWindow.onafterprint = function() {
                        setTimeout(function() {
                            customPrintWindow.close();
                        }, 500);
                    };
                }, 500);
                
                return false;
            }'
          ],
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
