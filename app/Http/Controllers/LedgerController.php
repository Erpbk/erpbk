<?php

namespace App\Http\Controllers;

use App\DataTables\LedgerDataTable;
use App\Exports\LedgerExport;
use App\Helpers\Accounts;
use App\Helpers\Common;
use App\Models\Accounts as AccountsModel;
use App\Models\Banks;
use App\Models\Transactions;
use App\Traits\GlobalPagination;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LedgerController extends Controller
{
    use GlobalPagination;

    public function index(Request $request, LedgerDataTable $ledgerDataTable)
    {
        if (! auth()->user()->hasPermissionTo('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }
        if (! $request->has('account') && ! $request->has('month')) {
            $account = Banks::first();
            if ($account) {
                return redirect()->to(request()->fullUrlWithQuery(['account' => $account->account_id]));
            } else {
                return view('ledger.index2', [
                    'data' => false,
                ]);
            }
        }

        return $ledgerDataTable->render('ledger.index2');

        // return $this->indexWithFilters($request);
    }

    /**
     * Handle ledger listing with filters
     */
    // private function indexWithFilters(Request $request)
    // {
    //   // Build query
    //   $query = Transactions::query()->with(['account', 'voucher']);

    //   if ($request->has('account') && !empty($request->account)) {
    //     $query->where('account_id', $request->account);
    //   }

    //   if ($request->has('month') && !empty($request->month)) {
    //     $query->where('billing_month', $request->month . '-01');
    //   }

    //   $query->orderBy('billing_month', 'ASC')->orderBy('trans_date', 'ASC');

    //   // Get all transactions (we need to process them to calculate balances)
    //   $transactions = $query->get();

    //   // Process transactions to create ledger entries
    //   $ledgerData = $this->processLedgerData($transactions, $request);

    //   // Apply quick search filter if provided
    //   if ($request->filled('quick_search')) {
    //     $search = strtolower($request->input('quick_search'));
    //     $ledgerData = array_filter($ledgerData, function ($entry) use ($search) {
    //       return
    //         stripos(strip_tags($entry->date ?? ''), $search) !== false ||
    //         stripos(strip_tags($entry->account_name ?? ''), $search) !== false ||
    //         stripos(strip_tags($entry->billing_month ?? ''), $search) !== false ||
    //         stripos(strip_tags($entry->reference ?? ''), $search) !== false ||
    //         stripos(strip_tags($entry->voucher ?? ''), $search) !== false ||
    //         stripos(strip_tags($entry->narration ?? ''), $search) !== false ||
    //         stripos(strip_tags($entry->debit ?? ''), $search) !== false ||
    //         stripos(strip_tags($entry->credit ?? ''), $search) !== false ||
    //         stripos(strip_tags($entry->balance ?? ''), $search) !== false;
    //     });
    //     // Re-index array after filtering
    //     $ledgerData = array_values($ledgerData);
    //   }

    //   // Use global pagination trait
    //   $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

    //   // Paginate the processed data
    //   $currentPage = $request->input('page', 1);
    //   $perPage = $paginationParams['is_all'] ? count($ledgerData) : $paginationParams['per_page_numeric'];

    //   $offset = ($currentPage - 1) * $perPage;
    //   $items = array_slice($ledgerData, $offset, $perPage);

    //   // Create a paginator manually
    //   $data = new \Illuminate\Pagination\LengthAwarePaginator(
    //     $items,
    //     count($ledgerData),
    //     $perPage,
    //     $currentPage,
    //     ['path' => $request->url(), 'query' => $request->query()]
    //   );

    //   // AJAX Response for filtered results
    //   if ($request->ajax()) {
    //     $tableData = view('ledger.table', [
    //       'data' => $data,
    //     ])->render();
    //     $paginationLinks = $data->links('components.global-pagination')->render();
    //     return response()->json([
    //       'tableData' => $tableData,
    //       'paginationLinks' => $paginationLinks,
    //     ]);
    //   }

    //   return view('ledger.index', [
    //     'data' => $data,
    //   ]);
    // }

    // /**
    //  * Process transactions to create ledger data array
    //  */
    // private function processLedgerData($transactions, $request)
    // {
    //   $openingBalance = $this->getOpeningBalance($request);
    //   $data = [];
    //   $runningBalance = $openingBalance;
    //   $totalDebit = 0;
    //   $totalCredit = 0;

    //   // Add Balance Forward row at the top
    //   $data[] = (object)[
    //     'date' => '',
    //     'account_name' => '',
    //     'billing_month' => '',
    //     'reference' => '',
    //     'voucher' => '',
    //     'narration' => '<b>Balance Forward</b>',
    //     'debit' => '',
    //     'credit' => '',
    //     'balance' => number_format($openingBalance, 2),
    //     'is_balance_forward' => true,
    //   ];

    //   // Process transactions and maintain running balance
    //   foreach ($transactions as $row) {
    //     $runningBalance += $row->debit - $row->credit;
    //     $totalDebit += $row->debit;
    //     $totalCredit += $row->credit;

    //     $view_file = '';
    //     $voucher_ID = '';
    //     $voucher_text = '';

    //     if (isset($row->voucher->attach_file)) {
    //       if ($row->reference_type == 'RTA') {
    //         $view_file = '  <a href="' . url('storage/' . $row->voucher->attach_file) . '" class="no-print"  target="_blank">View File</a>';
    //       } elseif ($row->reference_type == 'LV') {
    //         $view_file = '  <a href="' . url('storage/' . $row->voucher->attach_file) . '" class="no-print"  target="_blank">View File</a>';
    //       } else {
    //         $view_file = '  <a href="' . url('storage/vouchers/' . $row->voucher->attach_file) . '" class="no-print"  target="_blank">View File</a>';
    //       }
    //     }

    //     // Handle different reference types
    //     $voucher_text = $this->getVoucherText($row);

    //     $month = "<span style='white-space: nowrap;'>" . date('M Y', strtotime($row->billing_month)) . "</span>";
    //     $naration = $this->getNarration($row, $view_file);

    //     $data[] = (object)[
    //       'date' => "<span style='white-space: nowrap;'>" . Common::DateFormat($row->trans_date) . "</span>",
    //       'account_name' => ($row->account->account_code ?? 'N/A') . '-' . ($row->account->name ?? 'N/A'),
    //       'billing_month' => $month,
    //       'reference' => ($row->voucher && isset($row->voucher->reference_number)) ? $row->voucher->reference_number : '',
    //       'voucher' => $voucher_text,
    //       'narration' => $naration,
    //       'debit' => number_format($row->debit, 2),
    //       'credit' => number_format($row->credit, 2),
    //       'balance' => number_format($runningBalance, 2),
    //       'is_balance_forward' => false,
    //       'is_total' => false,
    //     ];
    //   }

    //   // Add Total row at the end
    //   $data[] = (object)[
    //     'date' => '',
    //     'account_name' => '',
    //     'billing_month' => '',
    //     'reference' => '',
    //     'voucher' => '',
    //     'narration' => '<b>Total</b>',
    //     'debit' => '<b>' . number_format($totalDebit, 2) . '</b>',
    //     'credit' => '<b>' . number_format($totalCredit, 2) . '</b>',
    //     'balance' => '<b>' . number_format($runningBalance, 2) . '</b>',
    //     'is_balance_forward' => false,
    //     'is_total' => true,
    //   ];

    //   return $data;
    // }

    // /**
    //  * Get voucher text based on reference type
    //  */
    // private function getVoucherText($row)
    // {
    //   $voucherTypes = ['Voucher', 'RTA', 'LV', 'VL', 'INC', 'PN', 'PAY', 'COD', 'Salik Voucher', 'VC', 'AL', 'RiderInvoice'];

    //   if (in_array($row->reference_type, $voucherTypes)) {
    //     $vouchers = \App\Support\CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
    //     if ($vouchers) {
    //       $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
    //       return '<span class="d-none">' . $voucher_ID . '</span><a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
    //     } else {
    //       return '<span class="text-danger">No Voucher Found</span>';
    //     }
    //   }

    //   if ($row->reference_type == 'Invoice') {
    //     $invoice_ID = $row->reference_id;
    //     return '<span class="d-none">RD-' . $invoice_ID . '</span><a href="javascript:void(0);" data-title="Invoice # ' . $invoice_ID . '" data-size="xl" data-action="' . route('riderInvoices.show', $invoice_ID) . '" class="no-print show-modal">RD-' . $invoice_ID . '</a>';
    //   }

    //   return '';
    // }

    // /**
    //  * Get narration text
    //  */
    // private function getNarration($row, $view_file)
    // {
    //   if ($row->reference_type == 'RTA') {
    //     $vouchers = \App\Support\CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
    //     if ($vouchers) {
    //       $fines = \App\Support\CompanyQuery::table('rta_fines')->where('id', $vouchers->ref_id)->first();
    //       if ($fines) {
    //         return $row->narration . ', <b>Ticket Number: </b>' . $fines->ticket_no . ', <b>Bike No: </b>' . $fines->plate_no . ', ' . \Carbon\Carbon::parse($fines->trip_date)->format('d M Y') . ', ' . $view_file;
    //       }
    //     }
    //     return $row->narration . ', ' . $view_file;
    //   } elseif ($row->reference_type == 'LV') {
    //     $visaex = \App\Support\CompanyQuery::table('visa_expenses')->where('id', $row->reference_id)->first();
    //     if ($visaex) {
    //       $rider = \App\Support\CompanyQuery::table('accounts')->where('id', $visaex->rider_id)->first();
    //       if ($rider) {
    //         return 'Paid to <b>' . $rider->name . ' </b>' . $visaex->visa_status . ' Charges ' . $visaex->date . $view_file;
    //       }
    //     }
    //     return $row->narration . ' (Rider not found) ' . $view_file;
    //   }

    //   return $row->narration . ', ' . $view_file;
    // }

    // /**
    //  * Get Opening Balance before the selected date.
    //  */
    // private function getOpeningBalance($request)
    // {
    //   if (!$request->has('month') || empty($request->month)) {
    //     return 0;
    //   }

    //   $account_id = $request->input('account');
    //   if (!$account_id) {
    //     return 0;
    //   }

    //   return Transactions::where('account_id', $account_id)
    //     ->whereDate('billing_month', '<', $request->month . '-01')
    //     ->sum(DB::raw("debit - credit"));
    // }

    // public function ledger()
    // {

    //   $transactions = Transactions::paginate(5);
    //   return view('ledger.ledger', compact('transactions'));
    // }

    public function export(Request $request)
    {
        if (! auth()->user()->hasPermissionTo('gn_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        $account_id = $request->get('account') ?? null;
        $month = $request->get('month') ?? null;
        $from_date = $request->get('from_date') ?? null;
        $to_date = $request->get('to_date') ?? null;
        $account = null;
        if ($account_id) {
            $account = AccountsModel::find($account_id);
        }
        if (! $account) {
            Flash::error('Account not found for export.');

            return redirect()->back();
        }
        $filename = 'ledger_'.
            ($account ? '('.$account->account_code.')_' : '').
            ($month ? 'month('.Common::MonthFormat($month).')_' : '').
            ($to_date ? 'from_'.Common::DateFormat($from_date).'_to_'.Common::DateFormat($to_date).'_' : '').
            'exported('.date('d-M-Y_H-i').')'.'.xlsx';

        return Excel::download(new LedgerExport($account_id, $month, $from_date, $to_date), $filename);
    }

    public function print(Request $request)
    {
        $query = Transactions::query()
            ->with(['voucher']);

        // Account Filter
        if ($request->account) {
            $query->where('account_id', $request->account);
        }

        // Date Filters
        if ($request->from_date && $request->to_date) {

            $query->whereBetween('trans_date', [
                $request->from_date,
                $request->to_date,
            ]);

        } elseif ($request->month) {

            $query->whereDate(
                'billing_month',
                $request->month.'-01'
            );
        }

        $transactions = $query
            ->orderBy('billing_month', 'ASC')
            ->orderBy('trans_date', 'ASC')
            ->get();

        // Opening Balance
        $openingBalance = 0;
        $closingBalance = 0;

        if ($request->account) {

            if ($request->from_date) {

                $openingBalance = Transactions::where(
                    'account_id',
                    $request->account
                )
                    ->where('trans_date', '<', $request->from_date)
                    ->sum(DB::raw('debit - credit'));

                $closingBalance = $openingBalance + Transactions::where(
                    'account_id',
                    $request->account
                )->whereBetween('trans_date', [
                    $request->from_date,
                    $request->to_date,
                ])
                    ->sum(DB::raw('debit - credit'));

            } elseif ($request->month) {

                $openingBalance = Transactions::where(
                    'account_id',
                    $request->account
                )
                    ->whereDate(
                        'billing_month',
                        '<',
                        $request->month.'-01'
                    )
                    ->sum(DB::raw('debit - credit'));

                $closingBalance = $openingBalance + Transactions::where(
                    'account_id',
                    $request->account
                )->whereDate(
                    'billing_month',
                    $request->month.'-01'
                )
                    ->sum(DB::raw('debit - credit'));
            }
        }

        return view('ledger.ledger-print', [
            'transactions' => $transactions,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'request' => $request,
        ]);
    }
}
