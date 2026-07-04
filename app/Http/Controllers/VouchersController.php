<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Helpers\CommonHelper;
use App\Http\Requests\CreateVouchersRequest;
use App\Http\Requests\UpdateVouchersRequest;
use App\Http\Controllers\AppBaseController;
use App\Imports\ImportVoucher;
use App\Imports\VoucherImport;
use App\Models\Accounts\Transaction;
use App\Models\Accounts\TransactionAccount;
use App\Models\Rider;
use App\Models\RiderInvoice;
use App\Models\Transactions;
use App\Models\User;
use App\Models\Banks;
use App\Models\Receipt;
use App\Models\Payment;
use App\Models\Vouchers;
use App\Models\VoucherCustomField;
use App\Models\VoucherType;
use App\Services\TransactionService;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use App\Support\PublicStorageDisk;
use Flash;
use Maatwebsite\Excel\Facades\Excel;
use Response;
use Yajra\DataTables\DataTables;
use DB;
use Carbon\Carbon;

class VouchersController extends Controller
{
  use GlobalPagination, TracksCascadingDeletions;
  /**
   * Display a listing of the Vouchers.
   *
   * @param Request $request
   *
   * @return Response
   */
  public function index(Request $request)
  {
    if (!auth()->user()->hasPermissionTo('voucher_view')) {
      abort(403, 'Unauthorized action.');
    }

    return $this->indexWithFilters($request);
  }

  /**
   * Handle vouchers listing with filters
   */
  private function indexWithFilters(Request $request)
  {
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

    $query = Vouchers::query()->orderBy('id', 'desc');

    // Apply filters
    if ($request->has('voucher_id') && !empty($request->voucher_id)) {
      $voucherId = $request->voucher_id;

      $query->where(function ($q) use ($voucherId) {
        $q->whereRaw("CONCAT(voucher_type, '-', LPAD(id, 4, '0')) LIKE ?", ["%{$voucherId}%"])
          ->orWhere('id', 'like', "%{$voucherId}%")
          ->orWhere('voucher_type', 'like', "%{$voucherId}%");
      });
    }
    if ($request->has('trans_date') && !empty($request->trans_date)) {
      $query->whereDate('trans_date', $request->trans_date);
    }

    if ($request->has('billing_month') && !empty($request->billing_month)) {
      $billingMonth = Carbon::parse($request->billing_month);
      $query->whereYear('billing_month', $billingMonth->year)
        ->whereMonth('billing_month', $billingMonth->month);
    }

    if ($request->has('voucher_type') && !empty($request->voucher_type)) {
      $query->where('voucher_type', $request->voucher_type);
    }


    if ($request->has('created_by') && !empty($request->created_by)) {
      $query->where('Created_By', $request->created_by);
    }

    // Quick search across multiple fields
    if ($request->filled('quick_search')) {
      $search = $request->input('quick_search');
      $query->where(function ($q) use ($search) {
        $q->whereRaw("CONCAT(voucher_type, '-', LPAD(id, 4, '0')) LIKE ?", ["%{$search}%"])
          ->orWhere('id', 'like', "%{$search}%")
          ->orWhere('voucher_type', 'like', "%{$search}%")
          ->orWhere('amount', 'like', "%{$search}%")
          ->orWhere('Created_By', 'like', "%{$search}%")
          ->orWhere('Updated_By', 'like', "%{$search}%");
      });
    }

    // Debug: Log the SQL query for troubleshooting
    if (config('app.debug')) {
      \Log::info('Voucher Filter Query: ' . $query->toSql());
      \Log::info('Voucher Filter Bindings: ' . json_encode($query->getBindings()));
    }

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);

    $voucherModuleKey = 'vouchers';
    $voucherTypesForFilter = VoucherType::activeCodeLabelMapForModuleWithEditAccess($voucherModuleKey);
    $editDeleteFlags = VoucherType::getEditDeleteFlagsByModule($voucherModuleKey);

    // AJAX Response for filtered results
    if ($request->ajax()) {
      $tableData = view('vouchers.table', [
        'data' => $data,
        'editDeleteFlags' => $editDeleteFlags,
      ])->render();
      $paginationLinks = $data->links('components.global-pagination')->render();
      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
      ]);
    }

    return view('vouchers.index', [
      'data' => $data,
      'voucherTypesForFilter' => $voucherTypesForFilter,
      'editDeleteFlags' => $editDeleteFlags,
    ]);
  }

  /**
   * Return voucher list fragment for the left sidebar (when voucher detail panel is open).
   */
  public function listSidebar(Request $request)
  {
    if (!auth()->user()->hasPermissionTo('voucher_view')) {
      abort(403, 'Unauthorized action.');
    }

    $query = Vouchers::query()->orderBy('id', 'desc');

    if ($request->filled('voucher_type')) {
      $query->where('voucher_type', $request->voucher_type);
    }
    if ($request->filled('quick_search')) {
      $search = $request->input('quick_search');
      $query->where(function ($q) use ($search) {
        $q->whereRaw("CONCAT(voucher_type, '-', LPAD(id, 4, '0')) LIKE ?", ["%{$search}%"])
          ->orWhere('id', 'like', "%{$search}%")
          ->orWhere('amount', 'like', "%{$search}%");
      });
    }

    $data = $query->paginate(20)->appends($request->query());
    $voucherTypesForCreate = VoucherType::activeCodeLabelMapForModuleWithEditAccess('vouchers');

    return view('vouchers.list_sidebar', compact('data', 'voucherTypesForCreate'));
  }

  /**
   * Show the form for creating a new Vouchers.
   * Only voucher types assigned to the vouchers module are allowed (vt query param).
   *
   * @return Response
   */
  public function create(Request $request)
  {
    $vouchers = null;
    $allowedTypes = VoucherType::activeCodeLabelMapForModuleWithEditAccess('vouchers');
    $vt = $request->query('vt');
    if ($vt !== null && !array_key_exists($vt, $allowedTypes)) {
      $vt = count($allowedTypes) > 0 ? array_key_first($allowedTypes) : null;
    }
    $voucherCustomFields = VoucherCustomField::orderBy('display_order')->get();
    return view('vouchers.create', compact('voucherCustomFields', 'vt','vouchers'));
  }

  /**
   * Store a newly created Vouchers in storage.
   *
   * @param CreateVouchersRequest $request
   *
   * @return Response
   */
  public function store(Request $request, $company_slug, VoucherService $voucherService)
  {
    $allowedTypes = VoucherType::activeCodeLabelMapForModule('vouchers');
    if (!array_key_exists($request->voucher_type ?? '', $allowedTypes)) {
      return response()->json(['errors' => ['voucher_type' => ['The selected voucher type is not allowed for this module.']]], 422);
    }
    try {
      $request->billing_month = $request->billing_month . "-01";
      $request->merge(['custom_field_values' => $request->input('voucher_custom_fields', [])]);



      /** @var Vouchers $vouchers */
      if ($request->voucher_type == 'JV') {
        if (array_sum($request->dr_amount) != array_sum($request->cr_amount)) {

          return response()->json(['errors' => ['error' => 'Total debit and credit must be equal.']], 422);
        }
        $result = $voucherService->JournalVoucher($request);
      }
      /* if ($request->voucher_type == 5) {
        $result = $voucherService->InvoiceVoucher($request);
      }
      if ($request->voucher_type == 9) {
        $result = $voucherService->SimVoucher($request);
      } */
      /*  if ($request->voucher_type == 11) {
           $result = $voucherService->FuelVoucher($request);
       }
       if ($request->voucher_type == 10) {
           $result = $voucherService->RentVoucher($request);
       }
       if ($request->voucher_type == 8) {
           $result = $voucherService->RtaVoucher($request);
       } */

      if ($request->voucher_type == 'VL') {
        $result = $voucherService->loanvoucher($request);
      }
      if (in_array($request->voucher_type, ['LV', 'LE'])) {
        $result = $voucherService->DefaultVoucher($request, 'debit');
      }
      if (in_array($request->voucher_type, ['AL'])) {
        $result = $voucherService->DefaultVoucher($request, 'debit');
      }
      if (in_array($request->voucher_type, ['COD'])) {
        $result = $voucherService->DefaultVoucher($request, 'debit');
      }
      if (in_array($request->voucher_type, ['PENALTY'])) {
        $result = $voucherService->DefaultVoucher($request, 'debit');
      }
      if (in_array($request->voucher_type, ['INCENTIVE'])) {
        $result = $voucherService->DefaultVoucher($request, 'debit');
      }
      /* if (in_array($request->voucher_type, [13])) {
        $result = $voucherService->DefaultVoucher($request, 2);

      } */

      //$vouchers = Vouchers::create($input);
      return $result;
    } catch (\Exception $e) {
      // Log the error for debugging
      \Log::error('Voucher store error: ' . $e->getMessage(), [
        'request_data' => $request->all(),
        'trace' => $e->getTraceAsString()
      ]);

      // Return user-friendly error message
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Display the specified Vouchers.
   *
   * @param int $id
   *
   * @return Response
   */
  public function show($company_slug, $id)
  {
    /** @var Vouchers $vouchers */
    $voucher = Vouchers::with(['transactions.account'])->find($id);
    $voucherModuleKey = 'vouchers';
    $editDeleteFlags = VoucherType::getEditDeleteFlagsByModule($voucherModuleKey);
    if (empty($voucher)) {
      Flash::error('Vouchers not found');

      return redirect()->back();
    }

    if (request()->ajax() || request()->wantsJson()) {
      return view('vouchers.show_modal', compact('voucher','editDeleteFlags'));
    }

    return view('vouchers.show', compact('voucher'));
  }

  /**
   * Show the form for editing the specified Vouchers.
   *
   * @param int $id
   *
   * @return Response
   */
  public function edit($company_slug, $id)
  {
    /** @var Vouchers $vouchers */
    $vouchers = Vouchers::where('trans_code', $id)->first();

    if (empty($vouchers)) {
      Flash::error('Vouchers not found');

      return redirect(route('vouchers.index'));
    }

    if (VoucherType::isLockedInVouchersModule($vouchers->voucher_type)) {
      abort(403, 'This voucher cannot be edited from the Vouchers module.');
    }

    if ($vouchers->voucher_type == 'JV') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'RFV') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'AL') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'COD') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'PN') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'IL') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'PAY') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'VC') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'INC') {
      $data = Transactions::where('trans_code', $id)->get();
    } else {
      $data = Transactions::where('trans_code', $id)->where('debit', '>', 0)->get();
    }

    $voucherCustomFields = VoucherCustomField::orderBy('display_order')->get();
    return view('vouchers.edit', compact('vouchers', 'data', 'voucherCustomFields'));
  }

  /**
   * Update the specified Vouchers in storage.
   *
   * @param int $id
   * @param UpdateVouchersRequest $request
   *
   * @return Response
   */
  public function update($company_slug, $id, Request $request, VoucherService $voucherService)
  {
    /** @var Vouchers $vouchers */
    $vouchers = Vouchers::find($id);

    $request->billing_month = $request->billing_month . "-01";
    $request->merge(['custom_field_values' => $request->input('voucher_custom_fields', [])]);

    /* if (array_sum($request->dr_amount) != array_sum($request->cr_amount)) {

      return response()->json(['errors' => ['error' => 'Total debit and credit must be equal.']], 422);
    } */

    if (empty($vouchers)) {
      Flash::error('Vouchers not found');

      return redirect(route('vouchers.index'));
    }

    if (VoucherType::isLockedInVouchersModule($vouchers->voucher_type)) {
      return response()->json(['errors' => ['error' => 'This voucher cannot be edited from the Vouchers module.']], 403);
    }

    if ($request->voucher_type == 'JV') {
      if (array_sum($request->dr_amount) != array_sum($request->cr_amount)) {

        return response()->json(['errors' => ['error' => 'Total debit and credit must be equal.']], 422);
      }
      $voucherService->JournalVoucher($request);
    }
    if ($request->voucher_type === 'RFV') {
      if (array_sum($request->dr_amount) != array_sum($request->cr_amount)) {
        return response()->json(['errors' => ['error' => 'Total debit and credit must be equal']], 422);
      }

      $riderId = $request->rider_id ?? $vouchers->rider_id;

      $riderAccountId = \App\Support\CompanyQuery::table('riders')->where('id', $riderId)->value('account_id');
      if (!$riderAccountId) {
        $riderAccountId = $request->account_id[0] ?? null;
        if (!$riderAccountId) {
          return response()->json(['errors' => ['error' => 'No account ID found for this rider']], 422);
        }
      }

      DB::beginTransaction();

      try {
        // Calculate amounts
        $totalDebit = array_sum($request->dr_amount);
        $adminCharges = 0;
        $serviceCharges = 0;

        foreach ($request->narration as $i => $note) {
          if (stripos($note, 'Admin Charges') !== false) {
            $adminCharges += floatval($request->cr_amount[$i]);
          }
          if (stripos($note, 'Service Charges') !== false) {
            $serviceCharges += floatval($request->cr_amount[$i]);
          }
        }

        $actualFineAmount = $totalDebit - ($adminCharges + $serviceCharges);

        // Update voucher
        $vouchers->rider_id = $riderAccountId;
        $vouchers->amount = $totalDebit;
        if ($request->has('reference_number')) {
          $vouchers->reference_number = $request->reference_number;
        }
        $vouchers->custom_field_values = $request->input('custom_field_values', []);
        $vouchers->save();

        // Only update rider_id in rta_fines if this is the FIRST voucher with this ref_id
        $firstVoucherId = \App\Support\CompanyQuery::table('vouchers')
          ->where('ref_id', $vouchers->ref_id)
          ->where('voucher_type', 'RFV')
          ->orderBy('id', 'asc')
          ->value('id');

        $fineUpdate = [
          'amount'       => $actualFineAmount,
          'total_amount' => $totalDebit,
          'detail'       => $request->narration[0] ?? '',
          'updated_at'   => now(),
        ];

        if ($vouchers->id == $firstVoucherId) {
          $fineUpdate['rider_id'] = $riderAccountId;
        }

        \App\Support\CompanyQuery::table('rta_fines')->where('id', $vouchers->ref_id)->update($fineUpdate);

        // Update transactions only for this voucher's trans_code
        $transactions = \App\Support\CompanyQuery::table('transactions')
          ->where('reference_id', $vouchers->ref_id)
          ->where('reference_type', 'RTA')
          ->where('trans_code', $vouchers->trans_code)
          ->orderBy('id')
          ->get();

        foreach ($transactions as $i => $txn) {
          $accountId = $request->account_id[$i] ?? 0;
          $newAccountId = ($accountId == 0 || empty($accountId)) ? $riderAccountId : $accountId;

          \App\Support\CompanyQuery::table('transactions')
            ->where('id', $txn->id)
            ->update([
              'account_id' => $newAccountId,
              'debit'      => floatval($request->dr_amount[$i]),
              'credit'     => floatval($request->cr_amount[$i]),
              'narration'  => $request->narration[$i] ?? '',
              'updated_at' => now()
            ]);
        }

        DB::commit();
        return response()->json(['message' => 'RFV voucher, RTA fine, and transactions updated successfully.']);
      } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['errors' => ['error' => 'Update failed: ' . $e->getMessage()]], 500);
      }
    }





    if (in_array($request->voucher_type, ['LV', 'LE', 'AL', 'COD', 'PN', 'PAY', 'VC', 'INC'])) {
      $result = $voucherService->DefaultVoucher($request, 'debit');
    }
    /*  if (in_array($request->voucher_type, [13])) {
       $result = $voucherService->DefaultVoucher($request, 2);

     } */
    /*   if ($request->voucher_type == 11) {
          $result = $voucherService->FuelVoucher($request);
      }
      if ($request->voucher_type == 10) {
          $result = $voucherService->RentVoucher($request);
      }
      if ($request->voucher_type == 8) {
          $result = $voucherService->RtaVoucher($request);
      } */
    /*  $vouchers->fill($request->all());
     $vouchers->save();

     Flash::success('Vouchers updated successfully.'); */

    return response()->json(['message' => 'Voucher updated successfully.']);
  }

  /**
   * Remove the specified Vouchers from storage.
   *
   * @param int $id
   *
   * @throws \Exception
   *
   * @return Response
   */
  public function destroy($company_slug, $id)
  {
    /** @var Vouchers $vouchers */
    DB::beginTransaction();
    try {
      // Get all vouchers with this trans_code before deletion for cascade tracking
      $vouchers = Vouchers::where('trans_code', $id)->get();

      if ($vouchers->isEmpty()) {
        return response()->json(['errors' => ['error' => 'Voucher not found.']], 404);
      }

      // Use the first voucher for reference data
      $voucher = $vouchers->first();

      if (VoucherType::isLockedInVouchersModule($voucher->voucher_type)) {
        DB::rollBack();

        return response()->json(['errors' => ['error' => 'This voucher cannot be deleted from the Vouchers module.']], 403);
      }
      $billingMonth = $voucher->billing_month;

      // Get all related transactions before deletion for cascade tracking
      $relatedTransactions = Transactions::where('trans_code', $id)->get();

      // Get all accounts involved in this voucher's transactions
      $affectedAccounts = $relatedTransactions->pluck('account_id')->unique();

      // Create voucher identifier for cascade tracking
      $voucherIdentifier = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);

      // Soft delete and track each transaction
      foreach ($relatedTransactions as $transaction) {
        try {
          // Track cascade deletion before deleting
          $this->trackCascadeDeletion(
            Vouchers::class,
            $voucher->id,
            $voucherIdentifier,
            Transactions::class,
            $transaction->id,
            "Transaction #{$transaction->id} - {$transaction->narration} (Trans Code: {$transaction->trans_code})",
            'hasMany',
            'transactions',
            'soft',
            'Cascade deletion from Voucher deletion'
          );

          // Update deleted_by if field exists
          if (in_array('deleted_by', $transaction->getFillable())) {
            $transaction->deleted_by = auth()->id();
            $transaction->save();
          }

          // Soft delete the transaction
          $transaction->delete();
        } catch (\Exception $e) {
          \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
          // Continue with deletion even if tracking fails
          $transaction->delete();
        }
      }

      // Update deleted_by if field exists for all vouchers with this trans_code
      if (in_array('deleted_by', $voucher->getFillable())) {
        Vouchers::where('trans_code', $id)->update(['deleted_by' => auth()->id()]);
      }

      // Track each voucher deletion in cascade table before soft deleting
      $user = auth()->user();
      $userName = $user ? ($user->name ?? 'User #' . $user->id) : 'System';

      foreach ($vouchers as $voucherToDelete) {
        try {
          $voucherIdentifier = $voucherToDelete->voucher_type . '-' . str_pad($voucherToDelete->id, 4, '0', STR_PAD_LEFT);

          $this->trackCascadeDeletion(
            User::class,
            auth()->id(),
            $userName,
            Vouchers::class,
            $voucherToDelete->id,
            $voucherIdentifier,
            'hasMany',
            'vouchers',
            'soft',
            'User-initiated voucher deletion'
          );
          \Log::info("Voucher deletion tracked in cascade table for voucher ID: {$voucherToDelete->id}");
        } catch (\Exception $e) {
          \Log::error("Failed to track voucher deletion in cascade table for voucher ID: {$voucherToDelete->id} - " . $e->getMessage());
          // Continue with deletion even if tracking fails
        }
      }

      // Soft delete vouchers with trans_code filter
      Vouchers::where('trans_code', $id)->delete();

      // ✅ FIX: Recalculate ledger for all affected accounts
      foreach ($affectedAccounts as $accountId) {
        if ($accountId && $billingMonth) {
          $this->recalculateLedgerAfterDeletion($accountId, $billingMonth);
        }
      }

      DB::commit();
      return response()->json(['message' => 'Vouchers deleted successfully.']);
    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error("Error deleting Voucher trans_code: {$id} - " . $e->getMessage());
      return response()->json(['errors' => ['error' => 'Error deleting voucher: ' . $e->getMessage()]], 500);
    }
  }

  /**
   * Recalculate ledger entries after deletion
   * This ensures ledger integrity without deleting all entries
   */
  private function recalculateLedgerAfterDeletion($accountId, $billingMonth)
  {
    // Delete only the ledger entry for this specific billing month
    \App\Support\CompanyQuery::table('ledger_entries')
      ->where('account_id', $accountId)
      ->where('billing_month', $billingMonth)
      ->delete();

    // Get the last ledger entry before this billing month
    $lastLedger = \App\Support\CompanyQuery::table('ledger_entries')
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
      \App\Support\CompanyQuery::insert('ledger_entries', [
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

  public static function GetInvoiceBalance()
  {
    $id = request('id');
    $type = request('type');
    $date = date('Y-m-d');
    $date = date('Y-m-d', strtotime($date . ' +1 day'));
    $invoice_balance = 0;
    $balance = 0;
    $inv_id = 0;
    if ($type == 5) {
      //Rider Invoice Balance
      $item = RiderInvoice::where('RID', $id)->first();
      if ($item) {
        $total = Transaction::where('SID', $item->id)->where('vt', 4)->sum('amount');
        $paid = Transaction::where('SID', $item->id)->where('vt', 2)->sum('amount');
        $balance = ($total) - ($paid);
        if ($balance > 0) {
          $invoice_balance += $balance;
        }
        $inv_id = $item->id;
      }
      $rider = Rider::find($id);
      $balance = Account::ob($date, $rider->account->id);
      $balance = Account::show_bal($balance);
      return ['invoice_balance' => $invoice_balance, 'inv_id' => $inv_id, 'balance' => $balance];
    }
  }

  public function fetch_invoices($company_slug, $id, $vt)
  {
    $date = date('Y-m-d');
    $date = date('Y-m-d', strtotime($date . ' +1 day'));
    if ($vt == 5) {
      $res = RiderInvoice::where('RID', $id)->whereDate('billing_month', '>=', '2024-04-01')->get();

      $htmlData = '';
      $rider_balance = 0;
      foreach ($res as $item) {
        /* $total = Transaction::where('SID', $item->id)->where('vt', 4)->sum('amount');
        $paid = Transaction::where('SID', $item->id)->where('vt', 2)->sum('amount');
        $balance = ($total) - ($paid); */
        $balance = Account::InvoiceBalance($item->id);
        if ($balance > 0) {
          $trans_acc_id = TransactionAccount::where(['PID' => 21, 'Parent_Type' => $item->RID])->value('id');
          $rider_balance = Account::Monthly_ob($date, $trans_acc_id);
          $htmlData .= '
                <div class="row">
                <input type="hidden" name="inv_id[]" value="' . $item->id . '">
                <input type="hidden" name="id[]" value="' . $item->rider->id . '">
                <input type="hidden" name="inv_billing_month[]" value="' . $item->billing_month . '">

                        <div class="form-group col-md-7">
                            <label>Narration</label>
                            <textarea name="narration[]" class="form-control form-control-sm narration" rows="10" placeholder="Narration" style="height: 40px !important;">Payment to Rider against Invoice #' . $item->id . ' - Billing Month: ' . $item->billing_month . '</textarea>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Invoice Balance</label>
                            <input type="number" name="" class="form-control form-control-sm dr_amount" value="' . $balance . '" readonly placeholder="Balance Amount">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Amount</label>
                            <input type="number" name="amount[]" step="any" class="form-control form-control-sm cr_amount" onkeyup="getTotal();" placeholder="Paid Amount">
                        </div>
                    </div>
                    <!--row-->
            ';
        }
      }
      //SELECT SUM(t.amount) FROM rider_invoices rv INNER JOIN transactions AS t ON rv.id=t.SID WHERE vt='4' and rv.VID=1
      return compact('htmlData', 'rider_balance');
    } else {
      $res = RiderInvoice::where('VID', $id)->get();
      $htmlData = '';
      $vendor_balance = 0;
      foreach ($res as $item) {
        /* $total = Transaction::where('SID', $item->id)->where('vt', 4)->sum('amount');
        $paid = Transaction::where('SID', $item->id)->where('vt', 2)->sum('amount');
        $balance = ($total) - ($paid); */
        $balance = Account::InvoiceBalance($item->id);
        if ($balance > 0) {
          $trans_acc_id = TransactionAccount::where(['PID' => 21, 'Parent_Type' => $item->RID])->value('id');
          $rider_balance = Account::Monthly_ob($date, $trans_acc_id);
          $htmlData .= '
                <tr><td>
                <div class="row">
                <input type="hidden" name="inv_id[]" value="' . $item->id . '">
                <input type="hidden" name="inv_billing_month[]" value="' . $item->billing_month . '">
                        <div class="form-group col-md-2">
                            <label for="exampleInputEmail1">Payment To</label>
                            <input type="hidden" name="id[]" value="' . $item->rider->id . '">
                               ' . $item->rider->name . '(' . $item->rider->rider_id . ')

                        </div>
                        <div class="form-group col-md-4">
                            <label>Narration</label>
                            <textarea name="narration[]" class="form-control form-control-sm narration" rows="10" placeholder="Narration" style="height: 40px !important;">Payment to Rider against #' . $item->id . ' through vendor</textarea>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Rider Balance</label>
                            <input type="text" name="" class="form-control form-control-sm" value="' . Account::show_bal($rider_balance) . '" readonly placeholder="Balance Amount">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Invoice Balance</label>
                            <input type="number" step="any" name="" class="form-control form-control-sm" value="' . $balance . '" readonly placeholder="Balance Amount">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Amount</label>
                            <input type="number" step="any" name="amount[]" class="form-control form-control-sm amount" step="any" onkeyup="getTotal();" placeholder="Paid Amount">
                        </div>
                    </div>
                    </td>
                    <td width="100"><input type="button" class="ibtnDel btn btn-md btn-xs btn-danger " style="margin-top:22px;"  value="Delete"></td>
                    </tr>
                    <!--row-->
            ';
        }
        $vendor_balance += $rider_balance;
      }
      //SELECT SUM(t.amount) FROM rider_invoices rv INNER JOIN transactions AS t ON rv.id=t.SID WHERE vt='4' and rv.VID=1
      $vendor_balance = Account::show_bal($vendor_balance);
      return compact('htmlData', 'vendor_balance');
    }
  }

  public function fileUpload(Request $request, $company_slug, $id)
  {
    $voucher = Vouchers::find($id);

    if (!$voucher) {
      if ($request->expectsJson() || $request->ajax()) {
        return response()->json(['message' => 'Voucher not found'], 404);
      }

      abort(404);
    }

    if ($request->isMethod('post')) {
      if (!$request->hasFile('attach_file')) {
        return response()->json(['message' => 'Please select a file to upload.'], 422);
      }

      $photo = $request->file('attach_file');
      $fileName = $photo->getClientOriginalName();
      if (in_array($voucher->voucher_type, ['LV', 'LE'])) {
        $fileName = $photo->store('vouchers', 'public');
      } else {
        PublicStorageDisk::storeUploadedFile($photo, 'vouchers', $fileName);
      }
      $voucher->attach_file = $fileName;
      $voucher->updated_by = auth()->id();
      $voucher->save();

      return response()->json(['message' => 'File uploaded successfully', 'reload' => true], 200);
    }

    return view('vouchers.attach_file', compact('id', 'voucher'));
  }


  public function import(Request $request)
  {
    if ($request->isMethod('post')) {
      $rules = [
        'file' => 'required|max:50000|mimes:xlsx,csv'
      ];
      $message = [
        'file.required' => 'Excel File Required'
      ];

      $this->validate($request, $rules, $message);
      Excel::import(new ImportVoucher(), $request->file('file'));
    }

    return view('vouchers.import');
  }

  public function cloneVoucher($company_slug, $id)
  {
    /** @var Vouchers $vouchers */
    $vouchers = Vouchers::where('trans_code', $id)->first();
    $vouchers->trans_code = null;
    $vouchers->billing_month = Carbon::parse($vouchers->billing_month)->format('Y-m');
    if($vouchers->voucher_type == 'RV') {
      $receipt = Receipt::find($vouchers->ref_id);
        if (empty($receipt)) {
            Flash::error('Receipt not found');
            return redirect()->back();
        }

        $banks = Banks::active()->get();
        $receipt->billing_month = \Carbon\Carbon::parse($receipt->billing_month)->format('Y-m');
        return view('receipts.create', compact('receipt', 'banks'));

    }  elseif($vouchers->voucher_type == 'PV') {
      $payment = Payment::find($vouchers->ref_id);
        if (empty($payment)) {
            Flash::error('Payment not found');
            return redirect()->back();
        }

        $banks = Banks::active()->get();
        $payment->billing_month = \Carbon\Carbon::parse($payment->billing_month)->format('Y-m');
        $payment->amount = $payment->amount - $payment->bank_charges;

        return view('payments.create', compact('payment', 'banks'));

    } elseif ($vouchers->voucher_type == 'JV') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'RFV') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'AL') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'COD') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'PN') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'IL') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'PAY') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'VC') {
      $data = Transactions::where('trans_code', $id)->get();
    } elseif ($vouchers->voucher_type == 'INC') {
      $data = Transactions::where('trans_code', $id)->get();
    } else {
      $data = Transactions::where('trans_code', $id)->where('debit', '>', 0)->get();
    }

    if (empty($vouchers)) {
      Flash::error('Vouchers not found');

      return redirect(route('vouchers.index'));
    }

    $voucherCustomFields = VoucherCustomField::orderBy('display_order')->get();
    return view('vouchers.create', compact('vouchers', 'data', 'voucherCustomFields'));
  }
}
