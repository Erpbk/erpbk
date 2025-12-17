<?php

namespace App\Http\Controllers;

use App\DataTables\RiderInvoicesDataTable;
use App\Helpers\HeadAccount;
use App\Http\Requests\CreateRiderInvoicesRequest;
use App\Http\Requests\UpdateRiderInvoicesRequest;
use App\Http\Controllers\AppBaseController;
use App\Imports\ImportRiderInvoice;
use App\Imports\ImportPaidRiderInvoice;
use App\Models\Accounts;
use App\Models\Items;
use App\Models\RiderInvoices;
use App\Models\Riders;
use App\Models\Transactions;
use App\Repositories\RiderInvoicesRepository;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Flash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class RiderInvoicesController extends AppBaseController
{
  use GlobalPagination;
  /** @var RiderInvoicesRepository $riderInvoicesRepository*/
  private $riderInvoicesRepository;

  public function __construct(RiderInvoicesRepository $riderInvoicesRepo)
  {
    $this->riderInvoicesRepository = $riderInvoicesRepo;
  }

  /**
   * Display a listing of the RiderInvoices.
   */
  public function index(Request $request)
  {
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

    $query = RiderInvoices::query()
      ->orderBy('billing_month', 'desc');

    // Filters
    if ($request->has('id') && !empty($request->id)) {
      $query->where('id', 'like', '%' . $request->id . '%');
    }
    if ($request->has('rider_id') && !empty($request->rider_id)) {
      $query->where('rider_id', $request->rider_id);
    }
    if ($request->has('billing_month') && !empty($request->billing_month)) {
      $billingMonth = \Carbon\Carbon::parse($request->billing_month);
      $query->whereYear('billing_month', $billingMonth->year)
        ->whereMonth('billing_month', $billingMonth->month);
    }
    if ($request->has('vendor_id') && !empty($request->vendor_id)) {
      $query->where('vendor_id', $request->vendor_id);
    }
    if ($request->has('zone') && !empty($request->zone)) {
      $query->where('zone', $request->zone);
    }
    if ($request->has('performance') && !empty($request->performance)) {
      $query->where('performance', $request->performance);
    }
    if ($request->has('status') && !empty($request->status)) {
      $query->where('status', $request->status);
    }

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);

    // ✅ Billing month ka check for total calculation
    $billingMonth = $request->has('billing_month') && !empty($request->billing_month)
      ? \Carbon\Carbon::parse($request->billing_month)
      : now();

    $currentMonthTotal = RiderInvoices::whereYear('billing_month', $billingMonth->year)
      ->whereMonth('billing_month', $billingMonth->month)
      ->sum('total_amount');

    // ✅ AJAX Response
    if ($request->ajax()) {
      $tableData = view('rider_invoices.table', [
        'data' => $data,
        'currentMonthTotal' => $currentMonthTotal
      ])->render();

      $paginationLinks = $data->links('components.global-pagination')->render();

      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
        'currentMonthTotal' => number_format($currentMonthTotal, 1),
      ]);
    }

    // ✅ Normal Response
    return view('rider_invoices.index', [
      'data' => $data,
      'currentMonthTotal' => $currentMonthTotal,
    ]);
  }



  /**
   * Show the form for creating a new RiderInvoices.
   */
  public function create()
  {
    $riders = Riders::dropdown();
    $items = Items::dropdown();
    return view('rider_invoices.create', compact('riders', 'items'));
  }

  /**
   * Store a newly created RiderInvoices in storage.
   */
  public function store(CreateRiderInvoicesRequest $request)
  {
    try {
      $input = $request->all();

      $riderInvoices = $this->riderInvoicesRepository->record($request);

      Flash::success('Rider Invoices saved successfully.');

      return redirect(route('riderInvoices.index'));
    } catch (\Exception $e) {
      Flash::error($e->getMessage());
      return redirect()->back()->withInput();
    }
  }

  /**
   * Display the specified RiderInvoices.
   */
  public function show($id)
  {
    $riderInvoice = $this->riderInvoicesRepository->find($id);

    if (empty($riderInvoice)) {
      Flash::error('Rider Invoices not found');

      return redirect(route('riderInvoices.index'));
    }

    return view('rider_invoices.show')->with('riderInvoice', $riderInvoice);
  }

  /**
   * Show the form for editing the specified RiderInvoices.
   */
  public function edit($id)
  {
    $invoice = $this->riderInvoicesRepository->find($id);

    if (empty($invoice)) {
      Flash::error('Rider Invoices not found');

      return redirect(route('riderInvoices.index'));
    }
    $riders = Riders::dropdown();
    $items = Items::dropdown();

    return view('rider_invoices.edit', compact('riders', 'items', 'invoice'));
  }

  /**
   * Update the specified RiderInvoices in storage.
   */
  public function update($id, UpdateRiderInvoicesRequest $request)
  {
    try {
      $riderInvoices = $this->riderInvoicesRepository->find($id);

      if (empty($riderInvoices)) {
        Flash::error('Rider Invoices not found');

        return redirect(route('riderInvoices.index'));
      }

      $riderInvoices = $this->riderInvoicesRepository->record($request, $id);

      Flash::success('Rider Invoices updated successfully.');

      return redirect(route('riderInvoices.index'));
    } catch (\Exception $e) {
      Flash::error($e->getMessage());
      return redirect()->back()->withInput();
    }
  }

  /**
   * Remove the specified RiderInvoices from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $riderInvoices = $this->riderInvoicesRepository->find($id);

    if (empty($riderInvoices)) {
      Flash::error('Rider Invoices not found');
      return redirect(route('riderInvoices.index'));
    }

    DB::beginTransaction();
    try {
      // Get billing month and affected accounts before deletion
      $trans_code = Transactions::where('reference_type', 'Invoice')
        ->where('reference_id', $id)
        ->value('trans_code');

      if ($trans_code) {
        // Get all accounts involved in this invoice's transactions
        $affectedAccountsData = Transactions::where('trans_code', $trans_code)
          ->select('account_id', 'billing_month')
          ->get();

        // Delete transactions
        $transactions = new TransactionService();
        $transactions->deleteTransaction($trans_code);



        // ✅ FIX: Recalculate ledger for all affected accounts
        foreach ($affectedAccountsData as $transData) {
          if ($transData->account_id && $transData->billing_month) {
            $this->recalculateLedgerAfterDeletion($transData->account_id, $transData->billing_month);
          }
        }
      }

      // Delete the invoice
      $this->riderInvoicesRepository->delete($id);

      DB::commit();
      Flash::success('Rider Invoices deleted successfully.');
    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error("Error deleting Rider Invoice ID: {$id} - " . $e->getMessage());
      Flash::error('Error deleting Rider Invoice: ' . $e->getMessage());
    }

    return redirect(route('riderInvoices.index'));
  }

  /**
   * Recalculate ledger entries after deletion
   * This ensures ledger integrity without deleting all entries
   */
  private function recalculateLedgerAfterDeletion($accountId, $billingMonth)
  {
    // Delete only the ledger entry for this specific billing month
    DB::table('ledger_entries')
      ->where('account_id', $accountId)
      ->where('billing_month', $billingMonth)
      ->delete();

    // Get the last ledger entry before this billing month
    $lastLedger = DB::table('ledger_entries')
      ->where('account_id', $accountId)
      ->where('billing_month', '<', $billingMonth)
      ->orderBy('billing_month', 'desc')
      ->first();

    $openingBalance = $lastLedger ? $lastLedger->closing_balance : 0.00;

    // Recalculate totals for this month after deletion
    $monthTransactions = Transactions::where('account_id', $accountId)
      ->where('billing_month', $billingMonth)
      ->get();

    $debitTotal = $monthTransactions->sum('debit');
    $creditTotal = $monthTransactions->sum('credit');
    $closingBalance = $openingBalance + $debitTotal - $creditTotal;

    // Only insert a new ledger entry if there are still transactions for this month
    if ($monthTransactions->count() > 0) {
      DB::table('ledger_entries')->insert([
        'account_id'      => $accountId,
        'billing_month'   => $billingMonth,
        'opening_balance' => $openingBalance,
        'debit_balance'   => $debitTotal,
        'credit_balance'  => $creditTotal,
        'closing_balance' => $closingBalance,
        'created_at'      => now(),
        'updated_at'      => now(),
      ]);
    }

    \Log::info("Recalculated ledger for account {$accountId} and billing month {$billingMonth}");
  }

  /**
   * Bulk delete multiple rider invoices
   */
  public function bulkDelete(Request $request)
  {
    // Check permission
    if (!auth()->user()->hasPermissionTo('riderinvoice_delete')) {
      return response()->json([
        'success' => false,
        'message' => 'You do not have permission to delete invoices.'
      ], 403);
    }

    try {
      $invoiceIds = $request->input('invoice_ids', []);

      if (empty($invoiceIds)) {
        return response()->json([
          'success' => false,
          'message' => 'No invoices selected for deletion.'
        ], 400);
      }

      $deletedCount = 0;
      $errors = [];

      // Start database transaction for atomicity
      DB::beginTransaction();

      try {
        foreach ($invoiceIds as $invoiceId) {
          try {
            $riderInvoice = $this->riderInvoicesRepository->find($invoiceId);

            if (empty($riderInvoice)) {
              $errors[] = "Invoice ID {$invoiceId} not found.";
              continue;
            }

            // Get transaction code for this invoice
            $trans_code = Transactions::where('reference_type', 'Invoice')
              ->where('reference_id', $invoiceId)
              ->value('trans_code');

            if ($trans_code) {
              // Delete related transactions using TransactionService
              $transactionService = new TransactionService();
              $transactionService->deleteTransaction($trans_code);
            }

            // Delete related rider invoice items
            \DB::table('rider_invoice_items')->where('inv_id', $invoiceId)->delete();



            // Delete the invoice itself
            $this->riderInvoicesRepository->delete($invoiceId);

            $deletedCount++;
          } catch (\Exception $e) {
            $errors[] = "Failed to delete invoice ID {$invoiceId}: " . $e->getMessage();
          }
        }

        // Commit transaction if all deletions were successful
        DB::commit();

        if ($deletedCount > 0) {
          $message = "Successfully deleted {$deletedCount} invoice(s).";
          if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
          }

          return response()->json([
            'success' => true,
            'message' => $message,
            'deleted_count' => $deletedCount,
            'errors' => $errors
          ]);
        } else {
          return response()->json([
            'success' => false,
            'message' => 'No invoices were deleted. Errors: ' . implode(', ', $errors)
          ], 400);
        }
      } catch (\Exception $e) {
        // Rollback transaction on any error
        DB::rollback();
        throw $e;
      }
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'An error occurred during bulk deletion: ' . $e->getMessage()
      ], 500);
    }
  }

  public function import(Request $request)
  {
    if ($request->isMethod('post')) {
      $rules = [
        'file' => 'required|max:50000|mimes:xlsx'
      ];
      $message = [
        'file.required' => 'Excel File Required'
      ];
      $this->validate($request, $rules, $message);
      Excel::import(new ImportRiderInvoice(), $request->file('file'));
    }

    return view('rider_invoices.import');
  }

  /**
   * Import paid rider invoices from Excel
   */
  public function importPaid(Request $request)
  {
    if ($request->isMethod('post')) {
      $rules = [
        'file' => 'required|max:50000|mimes:xlsx'
      ];
      $message = [
        'file.required' => 'Excel File Required'
      ];

      $this->validate($request, $rules, $message);

      try {
        Excel::import(new ImportPaidRiderInvoice(), $request->file('file'));
        Flash::success('Paid rider invoices imported successfully.');
      } catch (\Exception $e) {
        Flash::error('Error importing paid invoices: ' . $e->getMessage());
      }

      return redirect()->back();
    }

    return view('rider_invoices.import_paid');
  }

  /**
   * Mark a single invoice as paid manually
   */
  public function markAsPaid(Request $request, $id)
  {
    if ($request->isMethod('post')) {
      $rules = [
        'bank_account_id' => 'required|exists:accounts,id'
      ];
      $message = [
        'bank_account_id.required' => 'Bank account is required',
        'bank_account_id.exists' => 'Selected bank account does not exist'
      ];

      $this->validate($request, $rules, $message);

      try {
        \DB::beginTransaction();

        // Find the invoice
        $invoice = RiderInvoices::find($id);
        if (!$invoice) {
          Flash::error('Invoice not found.');
          return redirect()->back();
        }

        // Check if invoice is already paid
        if ($invoice->status == 1) {
          Flash::error('Invoice is already marked as paid.');
          return redirect()->back();
        }

        // Get rider information
        $rider = Riders::find($invoice->rider_id);
        if (!$rider) {
          Flash::error('Rider not found.');
          return redirect()->back();
        }

        // Update invoice status to paid
        $invoice->update(['status' => 1]);

        // Create voucher entries (same logic as ImportPaidRiderInvoice)
        $this->createManualPaymentVoucher($invoice, $rider, $request->bank_account_id);

        \DB::commit();
        Flash::success('Invoice marked as paid successfully.');
      } catch (\Exception $e) {
        \DB::rollBack();
        Flash::error('Error marking invoice as paid: ' . $e->getMessage());
      }

      return redirect()->back();
    }

    // GET request - show payment form
    $invoice = RiderInvoices::with('rider')->find($id);
    if (!$invoice) {
      Flash::error('Invoice not found.');
      return redirect()->back();
    }

    if ($invoice->status == 1) {
      Flash::error('Invoice is already marked as paid.');
      return redirect()->back();
    }

    // Get bank accounts for dropdown
    $bankAccounts = Accounts::bankAccountsDropdown();

    return view('rider_invoices.mark_as_paid', compact('invoice', 'bankAccounts'));
  }

  /**
   * Create voucher entries for manual payment
   */
  private function createManualPaymentVoucher($invoice, $rider, $bankAccountId)
  {
    $transactionService = new TransactionService();
    $trans_code = \App\Helpers\Account::trans_code();
    $totalAmount = $invoice->total_amount;
    $invoiceDate = now()->format('Y-m-d');
    $billingMonth = $invoice->billing_month;

    // Debit rider account
    $transactionDataDebit = [
      'account_id' => $rider->account_id,
      'reference_id' => $invoice->id,
      'reference_type' => 'RiderInvoice',
      'trans_code' => $trans_code,
      'trans_date' => $invoiceDate,
      'narration' => "Manual payment for Rider Invoice #" . $invoice->id . ' - ' . ($invoice->descriptions ?? 'Manual Payment'),
      'debit' => $totalAmount,
      'credit' => 0,
      'billing_month' => $billingMonth,
    ];
    $transactionService->recordTransaction($transactionDataDebit);

    // Credit bank account
    $transactionDataCredit = [
      'account_id' => $bankAccountId,
      'reference_id' => $invoice->id,
      'reference_type' => 'RiderInvoice',
      'trans_code' => $trans_code,
      'trans_date' => $invoiceDate,
      'narration' => "Manual payment received for Rider Invoice #" . $invoice->id . ' - ' . ($invoice->descriptions ?? 'Manual Payment'),
      'debit' => 0,
      'credit' => $totalAmount,
      'billing_month' => $billingMonth,
    ];
    $transactionService->recordTransaction($transactionDataCredit);

    // Create voucher record
    $voucherData = [
      'trans_date' => $invoiceDate,
      'voucher_type' => 'RI', // Rider Invoice Payment Voucher
      'payment_type' => 1,
      'payment_from' => $bankAccountId,
      'billing_month' => $billingMonth,
      'amount' => $totalAmount,
      'trans_code' => $trans_code,
      'Created_By' => \Auth::user()->id,
      'remarks' => "Manual payment for Rider Invoice #" . $invoice->id,
    ];

    \DB::table('vouchers')->insert($voucherData);
  }

  public function sendEmail($id, Request $request)
  {

    if ($request->isMethod('post')) {

      $data = [
        'html' => $request->email_message
      ];
      $res = RiderInvoices::with(['riderInv_item'])->where('id', $id)->get();
      $pdf = \PDF::loadView('invoices.rider_invoices.show', ['res' => $res]);

      Mail::send('emails.general', $data, function ($message) use ($request, $pdf) {
        $message->to([$request->email_to]);
        //$message->replyTo([$request->email]);
        $message->subject($request->email_subject);
        $message->attachData($pdf->output(), $request->email_subject . '.pdf');
        $message->priority(3);
      });
    }
    $invoice = RiderInvoices::find($id);
    return view('rider_invoices.send_email', compact('invoice'));
  }
}
