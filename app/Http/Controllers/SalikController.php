<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use App\Helpers\Account;
use App\Helpers\Common;
use App\Http\Controllers\AppBaseController;
use App\Models\Bikes;
use App\Models\Riders;
use App\Models\RtaFines;
use App\Models\Accounts;
use App\Models\Vouchers;
use App\Models\LedgerEntry;
use App\Models\Transactions;
use App\Models\salik;
use App\Models\BikeHistory;
use App\Models\FailedSalikImport;
use App\Repositories\SalikRepository;
use App\Services\TransactionService;
use Carbon\Carbon;
use Flash;
use DB;
use Auth;
use App\Imports\SalikImport;
use Maatwebsite\Excel\Facades\Excel;

class SalikController extends AppBaseController
{
    use GlobalPagination, TracksCascadingDeletions;
    /** @var SalikRepository $salikRepository*/
    private $salikRepository;

    public function __construct(SalikRepository $salikRepo)
    {
        $this->salikRepository = $salikRepo;
    }

    /**
     * Display a listing of the RtaFines.
     */
    public function accountcreate(Request $request)
    {
        $exists = Accounts::where('name', $request->name)->exists();
        if ($exists) {
            Flash::success('Account with this name already exists.');
        }

        // Get the parent account
        $parent = Accounts::where('id', 1237)->first();
        if (!$parent) {
            Flash::success('Parent account "Salik" not found.');
        }
        // Create new account
        $newdata = new Accounts();
        $newdata->name = $request->name;
        $newdata->traffic_code_number = $request->traffic_code_number;
        $newdata->admin_charges = $request->admin_charges;
        $newdata->parent_id = $parent->id;
        $newdata->account_type = 'Liability';

        $newdata->status = 1;
        $newdata->save();
        $newdata->account_code = 'ACCT-' . str_pad($newdata->id, 5, '0', STR_PAD_LEFT);
        $newdata->save();

        Flash::success('Account added successfully.');
        return redirect()->back();
    }

    public function editaccount(Request $request)
    {
        $parent = Accounts::where('id', 1237)->first();
        if (!$parent) {
            Flash::error('Parent account "Salik" not found.');
        }
        $newdata = Accounts::find($request->id);
        $newdata->name = $request->name;
        $newdata->traffic_code_number = $request->traffic_code_number;
        $newdata->admin_charges = $request->admin_charges;
        $newdata->parent_id = $parent->id;
        $newdata->account_type = 'Liability';
        $newdata->status = 1;
        $newdata->save();
        $newdata->account_code = 'ACCT-' . str_pad($newdata->id, 5, '0', STR_PAD_LEFT);
        $newdata->save();

        Flash::success('Account Updated successfully.');
        return redirect()->back();
    }
    public function deleteaccount($id)
    {
        // Check if there are any salik entries related to this account (including soft deleted)
        $hasSalik = salik::withTrashed()->where('salik_account_id', $id)->exists();

        if ($hasSalik) {
            Flash::error('Cannot delete account. There are existing Salik entries linked to this account.');
            return redirect()->back();
        }

        // Check if there are any ledger transactions related to this account
        $hasLedgerEntries = LedgerEntry::where('account_id', $id)->exists();

        if ($hasLedgerEntries) {
            Flash::error('Cannot delete account. There are existing ledger transactions linked to this account.');
            return redirect()->back();
        }

        // Check if there are any transactions related to this account (including soft deleted)
        $hasTransactions = Transactions::withTrashed()->where('account_id', $id)->exists();

        if ($hasTransactions) {
            Flash::error('Cannot delete account. There are existing transactions linked to this account.');
            return redirect()->back();
        }

        // Get account details before deletion for cascade tracking
        $account = Accounts::findOrFail($id);
        $accountName = $account->name ?? "Account #{$id}";

        // Soft delete the account
        $account->delete();

        Flash::success('Account deleted successfully.');
        return redirect()->back();
    }
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('salik_view')) {
            abort(403, 'Unauthorized action.');
        }
        $parent = Accounts::where('id', 1237)->first();
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Accounts::query()
            ->orderBy('id', 'asc')->where('parent_id', $parent->id);
        if ($request->has('account_code') && !empty($request->account_code)) {
            $query->where('account_code', 'like', '%' . $request->account_code . '%');
        }
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        if ($request->ajax()) {
            $tableData = view('salik.account_table', [
                'data' => $data,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }
        return view('salik.account_index', [
            'data' => $data,
        ]);
    }
    public function tickets(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('salik_view')) {
            abort(403, 'Unauthorized action.');
        }
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = salik::query()
            ->orderBy('id', 'asc')
            ->orderBy('billing_month', 'desc')
            ->where('salik_account_id', $id);
        if ($request->filled('transaction_id')) {
            $query->where('transaction_id', 'like', '%' . $request->transaction_id . '%');
        }
        if ($request->filled('billing_month')) {
            // Input comes as "YYYY-MM" from month input field
            $billingMonth = \Carbon\Carbon::parse($request->billing_month);

            // Convert to match stored format "Sep-25" or "Sep-25-01"
            $monthName = $billingMonth->format('M'); // e.g., "Sep"
            $yearShort = $billingMonth->format('y'); // e.g., "25"

            // Match formats: "Sep-25", "Sep-25-01", or "2025-09-01"
            $query->where(function ($q) use ($monthName, $yearShort, $billingMonth) {
                $q->where('billing_month', 'like', "{$monthName}-{$yearShort}%")
                    ->orWhere('billing_month', 'like', "{$billingMonth->format('Y-m')}%");
            });
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }
        if ($request->filled('trip_date')) {
            // Input comes as "YYYY-MM-DD" from date input field
            // Database stores as "30 Sep 2025" format
            $tripDate = \Carbon\Carbon::parse($request->trip_date);
            $tripDateFormatted = $tripDate->format('d M Y'); // e.g., "30 Sep 2025"

            // Match the stored format "30 Sep 2025"
            $query->where('trip_date', $tripDateFormatted);
        }
        if ($request->filled('toll_gate')) {
            $query->where('toll_gate', $request->toll_gate);
        }
        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->rider_id);
        }
        if ($request->filled('tag_number')) {
            $query->where('tag_number', 'like', '%' . $request->tag_number . '%');
        }
        if ($request->filled('plate')) {
            $query->where('plate', 'like', '%' . $request->plate . '%');
        }

        $paidAmount   = $query->clone()->where('status', 'paid')->sum('total_amount');
        $unpaidAmount = $query->clone()->where('status', 'unpaid')->sum('total_amount');
        $paidCount    = $query->clone()->where('status', 'paid')->where('salik_account_id', $id)->count();
        $unpaidCount  = $query->clone()->where('status', 'unpaid')->where('salik_account_id', $id)->count();

        // Paginated data
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        // All matching (filtered) data to calculate totals
        $filteredData = $query->get();
        // Calculate totals

        $account = Accounts::find($id);
        if ($request->ajax()) {
            $tableData = view('salik.table', [
                'data' => $data,
                'account' => $account,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'totals' => [
                    'paidAmount'   => 'AED ' . number_format($paidAmount, 2),
                    'unpaidAmount' => 'AED ' . number_format($unpaidAmount, 2),
                    'paidCount'    => $paidCount,
                    'unpaidCount'  => $unpaidCount,
                ]
            ]);
        }
        return view('salik.index', [
            'data' => $data,
            'account' => $account,
            'paidAmount' => $paidAmount,
            'unpaidAmount' => $unpaidAmount,
            'paidCount' => $paidCount,
            'unpaidCount' => $unpaidCount,
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create($id)
    {
        $data = Accounts::where('id', $id)->first();
        return view('salik.create', compact('data'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Prevent duplicate transaction_id
        $exists = salik::where('transaction_id', $request->transaction_id)->exists();
        if ($exists) {
            return response()->json(['errors' => ['error' => 'This Transaction ID already exists.']], 422);
        }
        \DB::beginTransaction();
        try {
            $input = $request->all();
            $bike = Bikes::findOrFail($input['bike_id']);
            $trans_code = \App\Helpers\Account::trans_code();

            // Set values
            $input['billing_month']   = $input['billing_month'] . "-01";
            $input['rider_id']        = $input['rider_id'];
            $input['trip_date']       = $input['trip_date'];
            $input['trip_time']       = $input['trip_time'];
            $input['transaction_post_date'] = $input['transaction_post_date'];
            $input['toll_gate']       = $input['toll_gate'];
            $input['direction']       = $input['direction'];
            $input['tag_number']      = $input['tag_number'];
            $input['details']         = $input['details'];
            $input['bike_id']         = $bike->id;
            $input['plate']           = $bike->plate;
            $input['trans_date']      = \Carbon\Carbon::today();
            $input['trans_code']      = $trans_code;
            $input['total_amount']    = $request->amount + $request->admin_fee;
            $input['status']          = 'paid';
            $input['salik_account_id'] = $input['salik_account_id'] ?? null;
            $input['admin_charges']    = $input['admin_charges'] ?? ($request->admin_fee ?? 0);
            $input['created_by']      = Auth::user()->id;
            // Defensive: Ensure salik_account_id is present
            if (empty($input['salik_account_id'])) {
                throw new \Exception('Salik Account ID is required.');
            }

            // Create Salik entry
            $salik = $this->salikRepository->create($input);

            // --- Voucher and Accounting Logic ---
            $tripAmount = $salik->amount;
            $adminCharges = $salik->admin_charges ?? 0;
            $rider = \App\Models\Riders::find($salik->rider_id);
            $riderAccountId = $rider && $rider->account_id ? $rider->account_id : null;
            $salikAccountId = $salik->salik_account_id ?? null;
            $adminAccountId = 1003; // Admin Charges (RTA Salik)
            $transCode = $trans_code;
            $transDate = now();
            $billingMonth = date('Y-m-01', strtotime($salik->trip_date ?? $salik->billing_month));

            // Defensive: Ensure both account IDs are present
            if (!$salikAccountId) {
                throw new \Exception('Salik Account ID is missing or invalid.');
            }
            if (!$riderAccountId) {
                throw new \Exception('Rider Account ID is missing or invalid.');
            }

            // 1. Save total amount in Salik record
            $salik->total_amount = $tripAmount + $adminCharges;
            $salik->save();
            $pay_account = $request->pay_account;
            $transactionService = new \App\Services\TransactionService();

            // --- FIRST VOUCHER ---
            // 1. Debit Rider (full amount)
            $transactionService->recordTransaction([
                'account_id'     => $riderAccountId,
                'reference_id'   => $salik->id,
                'reference_type' => 'Salik Voucher',
                'trans_code'     => $transCode,
                'trans_date'     => $transDate,
                'narration'      => 'Salik Trip Debit (including admin fee)',
                'debit'          => $tripAmount + $adminCharges,
                'billing_month'  => $billingMonth,
            ]);
            // 2. Credit Salik Account (trip amount only)
            $transactionService->recordTransaction([
                'account_id'     => $salikAccountId,
                'reference_id'   => $salik->id,
                'reference_type' => 'Salik Voucher',
                'trans_code'     => $transCode,
                'trans_date'     => $transDate,
                'narration'      => 'Salik Trip Credit',
                'credit'         => $tripAmount,
                'billing_month'  => $billingMonth,
            ]);
            // 3. Credit Admin Charges Account (admin fee only)
            if ($adminCharges > 0) {
                $transactionService->recordTransaction([
                    'account_id'     => $adminAccountId,
                    'reference_id'   => $salik->id,
                    'reference_type' => 'Salik Voucher',
                    'trans_code'     => $transCode,
                    'trans_date'     => $transDate,
                    'narration'      => 'Salik Admin Charges Credit',
                    'credit'         => $adminCharges,
                    'billing_month'  => $billingMonth,
                ]);
            }

            // --- MAIN VOUCHER RECORD ONLY ---
            \App\Models\Vouchers::create([
                'trans_date'    => $transDate,
                'trans_code'    => $transCode,
                'payment_type'  => 1,
                'billing_month' => $billingMonth,
                'amount'        => $tripAmount + $adminCharges,
                'voucher_type'  => 'SV',
                'remarks'       => 'Salik Main Voucher - Reference Number: ' . $salik->transaction_id,
                'reference_number' => $salik->transaction_id,
                'ref_id'        => $salik->id,
                'rider_id'      => $salik->rider_id,
                'payment_to'    => $salikAccountId,
                'payment_from'  => $riderAccountId,
                'Created_By'    => auth()->id(),
                'custom_field_values' => [],
            ]);
            \DB::commit();
            return redirect()->route('salik.index')->with('success', 'Salik entry created successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['errors' => ['error' => $e->getMessage()]], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function fileUpload(Request $request, $id)
    {
        $fines = salik::find($id);

        if ($request->hasFile('attachment_path')) {
            $photo = $request->file('attachment_path');

            // Store file in storage/app/public/fines/files
            $docFile = $photo->store('fines/files', 'public');

            // Save original name and stored path
            $fines->attachment = $photo->getClientOriginalName();
            $fines->attachment_path = $docFile;

            $fines->save();
        }

        return view('salik.attach_file', compact('id', 'fines'));
    }

    /**
     * Display the specified RtaFines.
     */
    public function show($id)
    {
        $salik = salik::find($id);
        if (empty($salik)) {
            Flash::error('Salik not found');

            return redirect(route('salik.index'));
        }

        return view('salik.show')->with('salik', $salik);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {

        $salik = salik::find($id);
        $data = Accounts::where('id', $salik->salik_account_id)->first();
        if (empty($salik)) {
            Flash::error('Salik not found');

            return redirect(route('salik.index'));
        }

        return view('salik.edit', compact('data', 'salik'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Debugging: log what we actually receive
        // Remove this after testing
        if (!$id) {
            return response()->json(['error' => 'No ID received from route. Check your route definition.'], 400);
        }

        $validated = $request->validate([
            'transaction_id' => 'nullable|string|max:255',
            'trip_date' => 'required|string|max:255',
            'trip_time' => 'required|string|max:255',
            'transaction_post_date' => 'nullable|string|max:255',
            'toll_gate' => 'nullable|string|max:255',
            'direction' => 'nullable|string|max:255',
            'tag_number' => 'nullable|string|max:255',
            'plate' => 'nullable|string|max:255',
            'amount' => 'required|numeric',
            'status' => 'nullable|string|max:255',
            'details' => 'nullable|string|max:5000',
        ]);
        $validated['updated_by'] = auth()->id();
        $pay_account = $request->pay_account;

        DB::beginTransaction();
        try {
            // ✅ FIX: Use correct Model with capital S
            $salik = Salik::findOrFail($id);
            $oldAmount = $salik->amount;
            $oldAdminCharges = $salik->admin_charges ?? 0;
            $newAmount = $validated['amount'];
            $newAdminCharges = $salik->admin_charges ?? 0;
            $amountDifference = $newAmount - $oldAmount;
            $adminDifference = $newAdminCharges - $oldAdminCharges;
            $billingMonth = date('Y-m-01', strtotime($salik->trip_date ?? $salik->billing_month));

            // Check if this is part of a group voucher (imported Salik)
            $relatedSaliks = Salik::where('rider_id', $salik->rider_id)
                ->where('salik_account_id', $salik->salik_account_id)
                ->where('billing_month', $billingMonth)
                ->where('id', '!=', $id)
                ->get();

            if ($relatedSaliks->count() > 0) {
                $this->adjustGroupVoucherForUpdate($salik, $amountDifference, $adminDifference, $billingMonth);
            } else {
                $this->recreateStandaloneVouchers($salik, $validated, $pay_account);
            }

            // Update the Salik record
            $salik->update($validated);
            $salik->total_amount = $newAmount + $newAdminCharges;
            $salik->save();

            // Update account balances for all affected accounts
            $this->updateAccountBalances($salik->trans_code ?? null, $billingMonth);

            DB::commit();
            return redirect()->route('salik.tickets', $salik->salik_account_id)
                ->with('success', 'Salik entry updated, vouchers verified, and account balances adjusted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => ['error' => $e->getMessage()]], 500);
        }
    }

    private function adjustGroupVoucherForUpdate($salik, $amountDifference, $adminDifference, $billingMonth)
    {

        // Find the main Salik transaction (credit to Salik account)
        $salikTransaction = Transactions::where('reference_id', $salik->id)
            ->where('reference_type', 'Salik Voucher')
            ->where('account_id', $salik->salik_account_id)
            ->where('credit', '>', 0)
            ->first();
        if ($salikTransaction) {
            // Update Salik account transaction
            $salikTransaction->credit += $amountDifference;
            $salikTransaction->save();
            // Find and update rider debit transaction
            $riderAccount = DB::table('accounts')->where('ref_id', $salik->rider_id)->first();
            if ($riderAccount) {
                $riderTransaction = Transactions::where('trans_code', $salikTransaction->trans_code)
                    ->where('account_id', $riderAccount->id)
                    ->where('debit', '>', 0)
                    ->first();
                if ($riderTransaction) {
                    $riderTransaction->debit += ($amountDifference + $adminDifference);
                    $riderTransaction->save();
                }
            }

            // Handle admin charges transaction
            if ($adminDifference != 0) {
                $adminTransaction = Transactions::where('trans_code', $salikTransaction->trans_code)
                    ->where('account_id', 1003)
                    ->where('credit', '>', 0)
                    ->first();

                if ($adminTransaction) {
                    $adminTransaction->credit += $adminDifference;
                    if ($adminTransaction->credit <= 0) {
                        $adminTransaction->delete();
                    } else {
                        // Update narration with current count
                        $relatedSaliks = Salik::where('rider_id', $salik->rider_id)
                            ->where('salik_account_id', $salik->salik_account_id)
                            ->where('billing_month', $billingMonth)
                            ->get();
                        $totalCount = $relatedSaliks->count();
                        $adminChargePerSalik = $adminTransaction->credit / $totalCount;
                        $adminTransaction->narration = "Salik Import - Admin Charges ({$totalCount} × {$adminChargePerSalik})";
                        $adminTransaction->save();
                    }
                } elseif ($adminDifference > 0) {
                    // Create new admin transaction if it doesn't exist
                    $this->createAdminTransaction($salikTransaction->trans_code, $adminDifference, $salik->id, $billingMonth);
                }
            }

            // Update main voucher
            $mainVoucher = Vouchers::where('trans_code', $salikTransaction->trans_code)->where('ref_id', $salik->id)->first();
            if ($mainVoucher) {
                $mainVoucher->amount += ($amountDifference + $adminDifference);
                $mainVoucher->Updated_By = auth()->id();
                $mainVoucher->save();

                // Verify voucher consistency
                $this->verifyVoucherConsistency($mainVoucher, $salikTransaction->trans_code);
            }
        }

        $secondVoucherTransaction = Transactions::where('reference_id', $salik->id)
            ->where('reference_type', 'Salik Voucher')
            ->where('account_id', $salik->salik_account_id)
            ->where('debit', '>', 0)
            ->first();

        if ($secondVoucherTransaction) {
            $secondVoucherTransaction->debit += $amountDifference;
            $secondVoucherTransaction->save();

            $payerCreditTransaction = Transactions::where('trans_code', $secondVoucherTransaction->trans_code)
                ->where('credit', '>', 0)
                ->first();

            if ($payerCreditTransaction) {
                $payerCreditTransaction->credit += $amountDifference;
                $payerCreditTransaction->save();
            }

            $secondVoucher = Vouchers::where('trans_code', $secondVoucherTransaction->trans_code)->first();
            if ($secondVoucher) {
                $secondVoucher->amount += $amountDifference;
                $secondVoucher->save();
            }
        }
    }
    private function recreateStandaloneVouchers($salik, $validated, $pay_account)
    {
        $existingTransCodes = Transactions::where('reference_id', $salik->id)
            ->where('reference_type', 'Salik Voucher')
            ->pluck('trans_code');

        Transactions::where('reference_id', $salik->id)
            ->where('reference_type', 'Salik Voucher')
            ->delete();

        Vouchers::where('ref_id', $salik->id)
            ->whereIn('trans_code', $existingTransCodes)
            ->delete();

        $tripAmount = $validated['amount'];
        $adminCharges = $salik->admin_charges ?? 0;
        $rider = Riders::find($salik->rider_id);
        $riderAccountId = $rider && $rider->account_id ? $rider->account_id : null;
        $salikAccountId = $salik->salik_account_id ?? null;
        $adminAccountId = 1003;
        $transCode = Account::trans_code();
        $transDate = now();
        $billingMonth = date('Y-m-01', strtotime($validated['trip_date']));

        if (!$salikAccountId || !$riderAccountId) {
            throw new \Exception('Account IDs are missing or invalid.');
        }

        $transactionService = new TransactionService();

        $transactionService->recordTransaction([
            'account_id'     => $riderAccountId,
            'reference_id'   => $salik->id,
            'reference_type' => 'Salik Voucher',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => 'Salik Trip Debit (including admin fee) - Reference Number: ' . $salik->transaction_id,
            'debit'          => $tripAmount + $adminCharges,
            'billing_month'  => $billingMonth,
        ]);

        $transactionService->recordTransaction([
            'account_id'     => $salikAccountId,
            'reference_id'   => $salik->id,
            'reference_type' => 'Salik Voucher',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => 'Salik Trip Credit - Reference Number: ' . $salik->transaction_id,
            'credit'         => $tripAmount,
            'billing_month'  => $billingMonth,
        ]);

        if ($adminCharges > 0) {
            $transactionService->recordTransaction([
                'account_id'     => $adminAccountId,
                'reference_id'   => $salik->id,
                'reference_type' => 'Salik Voucher',
                'trans_code'     => $transCode,
                'trans_date'     => $transDate,
                'narration'      => 'Salik Admin Charges Credit - Reference Number: ' . $salik->transaction_id,
                'credit'         => $adminCharges,
                'billing_month'  => $billingMonth,
            ]);
        }
        Vouchers::create([
            'trans_date'    => $transDate,
            'trans_code'    => $transCode,
            'payment_type'  => 1,
            'billing_month' => $billingMonth,
            'amount'        => $tripAmount + $adminCharges,
            'voucher_type'  => 'SV',
            'remarks'       => 'Salik Main Voucher - Reference Number: ' . $salik->transaction_id,
            'reference_number' => $salik->transaction_id,
            'ref_id'        => $salik->id,
            'rider_id'      => $salik->rider_id,
            'payment_to'    => $salikAccountId,
            'payment_from'  => $riderAccountId,
            'Created_By'    => auth()->id(),
            'custom_field_values' => [],
        ]);
    }

    /**
     * Create admin transaction for Salik entries
     */
    private function createAdminTransaction($transCode, $adminAmount, $referenceId, $billingMonth)
    {
        $transactionService = new TransactionService();

        $transactionService->recordTransaction([
            'account_id'     => 1003, // Admin account
            'reference_id'   => $referenceId,
            'reference_type' => 'Salik Voucher',
            'trans_code'     => $transCode,
            'trans_date'     => now(),
            'narration'      => 'Salik Import - Admin Charges (1 × ' . $adminAmount . ')',
            'credit'         => $adminAmount,
            'billing_month'  => $billingMonth,
        ]);
    }

    /**
     * Verify voucher consistency with transactions
     */
    private function verifyVoucherConsistency($voucher, $transCode)
    {
        $transactions = Transactions::where('trans_code', $transCode)->get();
        $totalDebit = $transactions->sum('debit');
        $totalCredit = $transactions->sum('credit');
        if (abs($voucher->amount - $totalDebit) > 0.01) {
            \Log::warning("Voucher amount mismatch for trans_code: {$transCode}. Voucher: {$voucher->amount}, Total Debit: {$totalDebit}");
            $voucher->amount = $totalDebit;
            $voucher->save();
            \Log::info("Voucher amount auto-corrected for trans_code: {$transCode}");
        }
        if (abs($totalDebit - $totalCredit) > 0.01) {
            \Log::error("Transaction imbalance for trans_code: {$transCode}. Debit: {$totalDebit}, Credit: {$totalCredit}");
            throw new \Exception("Transaction imbalance detected for voucher {$transCode}");
        }
    }

    /**
     * Update account balances after voucher changes
     */
    private function updateAccountBalances($transCode, $billingMonth)
    {
        $transactions = Transactions::where('trans_code', $transCode)->get();

        foreach ($transactions as $transaction) {
            $this->updateLedgerEntry($transaction->account_id, $billingMonth);
        }
    }

    /**
     * Update ledger entry for an account
     */
    private function updateLedgerEntry($accountId, $billingMonth)
    {
        // Delete existing ledger entry for this month
        DB::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->delete();

        // Get last ledger entry
        $lastLedger = DB::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', '<', $billingMonth)
            ->orderBy('billing_month', 'desc')
            ->first();

        $openingBalance = $lastLedger ? $lastLedger->closing_balance : 0.00;

        // Calculate totals for this month
        $monthTransactions = Transactions::where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->get();

        $debitTotal = $monthTransactions->sum('debit');
        $creditTotal = $monthTransactions->sum('credit');
        $closingBalance = $openingBalance + $debitTotal - $creditTotal;

        // Insert new ledger entry
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        \DB::beginTransaction();
        try {
            $salik = salik::findOrFail($id);
            $salikAccountId = $salik->salik_account_id;
            $riderId = $salik->rider_id;
            $amount = $salik->amount;
            $adminCharges = $salik->admin_charges ?? 0;
            $transactionId = $salik->transaction_id;
            $billingMonth = date('Y-m-01', strtotime($salik->trip_date ?? $salik->billing_month));
            $salikIdentifier = $salik->transaction_id ?? "Salik #{$id}";

            \Log::info("Starting deletion of Salik entry - ID: {$id}, Transaction ID: {$transactionId}, Amount: {$amount}, Admin Charges: {$adminCharges}");

            // Find related Salik entries in the same group (same rider, account, billing month)
            $relatedSaliks = salik::where('rider_id', $riderId)
                ->where('salik_account_id', $salikAccountId)
                ->where('billing_month', $billingMonth)
                ->where('id', '!=', $id)
                ->get();

            \Log::info("Found {$relatedSaliks->count()} related Salik entries for the same rider/account/month");

            if ($relatedSaliks->count() > 0) {
                // This is part of a group - adjust the group voucher
                $this->adjustGroupVoucherForDeletion($salik, $relatedSaliks, $amount, $adminCharges, $billingMonth, $salikIdentifier);
            } else {
                // This is a standalone entry - delete all related records
                $this->deleteStandaloneEntry($salik, $billingMonth, $salikIdentifier);
            }

            // Finally, soft delete the Salik entry itself
            $salik->delete();

            // Track the primary Salik deletion itself
            try {
                $primaryCascadeRecord = \App\Models\DeletionCascade::create([
                    'primary_model' => salik::class,
                    'primary_id' => $salik->id,
                    'primary_name' => $salikIdentifier,
                    'related_model' => salik::class,
                    'related_id' => $salik->id,
                    'related_name' => $salikIdentifier,
                    'relationship_type' => 'self',
                    'relationship_name' => 'salik',
                    'deletion_type' => 'soft',
                    'deleted_by' => auth()->id(),
                    'deletion_reason' => 'Salik entry deleted',
                    'metadata' => [
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'timestamp' => now()->toIso8601String(),
                        'status' => $salik->status,
                        'amount' => $salik->amount,
                        'total_amount' => $salik->total_amount,
                        'admin_charges' => $adminCharges,
                    ],
                ]);
                \Log::info("Primary Salik deletion tracked, cascade record ID: " . ($primaryCascadeRecord->id ?? 'N/A'));
            } catch (\Exception $e) {
                \Log::error("Failed to track Salik deletion: " . $e->getMessage());
                \Log::error("Stack trace: " . $e->getTraceAsString());
            }

            \Log::info("Successfully deleted Salik entry ID: {$id} and all related vouchers/transactions");

            DB::commit();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Salik entry deleted and vouchers updated successfully.'
                ]);
            }

            return redirect()->route('salik.tickets', $salikAccountId)->with('success', 'Salik entry deleted and vouchers updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error("Error deleting Salik entry ID: {$id} - " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());

            // Return JSON response for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting Salik entry: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('salik.tickets', $salikAccountId ?? 1)->with('error', 'Error deleting Salik entry: ' . $e->getMessage());
        }
    }

    private function adjustGroupVoucherForDeletion($salik, $relatedSaliks, $amount, $adminCharges, $billingMonth, $salikIdentifier = null)
    {
        $transactionService = new TransactionService();
        $riderAccount = DB::table('accounts')->where('ref_id', $salik->rider_id)->first();

        if (!$riderAccount) {
            \Log::error("Rider account not found for rider_id: {$salik->rider_id}");
            return;
        }

        // Find the main voucher transactions for this group - check both reference types
        $mainVoucherTransCode = Transactions::where('reference_id', $salik->id)
            ->whereIn('reference_type', ['Salik Voucher', 'Salik'])
            ->where('account_id', $riderAccount->id)
            ->where('debit', '>', 0)
            ->value('trans_code');

        // If not found by reference_id, try using the salik's trans_code
        if (!$mainVoucherTransCode && $salik->trans_code) {
            $mainVoucherTransCode = $salik->trans_code;
        }

        if ($mainVoucherTransCode) {
            // Get the specific Salik transaction before deletion for cascade tracking
            $salikTransaction = Transactions::where('reference_id', $salik->id)
                ->where('trans_code', $mainVoucherTransCode)
                ->where('account_id', $salik->salik_account_id)
                ->first();

            // Remove the specific Salik transaction from the voucher
            if ($salikTransaction) {
                $salikTransaction->delete(); // Soft delete

                // Track cascade deletion
                try {
                    $cascadeRecord = $this->trackCascadeDeletion(
                        salik::class,
                        $salik->id,
                        $salikIdentifier ?? ($salik->transaction_id ?? "Salik #{$salik->id}"),
                        Transactions::class,
                        $salikTransaction->id,
                        "Transaction #{$salikTransaction->id} (Trans Code: {$salikTransaction->trans_code})",
                        'hasMany',
                        'transactions',
                        'soft',
                        'Cascade deletion from Salik group entry deletion'
                    );
                    \Log::info("Cascade deletion tracked for Salik transaction {$salikTransaction->id}, cascade record ID: " . ($cascadeRecord->id ?? 'N/A'));
                } catch (\Exception $e) {
                    \Log::error("Failed to track cascade deletion for Salik transaction {$salikTransaction->id}: " . $e->getMessage());
                }
            }

            // Update the rider debit transaction (reduce by amount + admin charges)
            $riderTransaction = Transactions::where('trans_code', $mainVoucherTransCode)
                ->where('account_id', $riderAccount->id)
                ->where('debit', '>', 0)
                ->first();

            if ($riderTransaction) {
                $riderTransaction->debit -= ($amount + $adminCharges);
                if ($riderTransaction->debit <= 0) {
                    // Track cascade deletion before deleting
                    try {
                        $cascadeRecord = $this->trackCascadeDeletion(
                            salik::class,
                            $salik->id,
                            $salikIdentifier ?? ($salik->transaction_id ?? "Salik #{$salik->id}"),
                            Transactions::class,
                            $riderTransaction->id,
                            "Transaction #{$riderTransaction->id} (Rider Debit - Trans Code: {$riderTransaction->trans_code})",
                            'hasMany',
                            'transactions',
                            'soft',
                            'Cascade deletion from Salik group entry deletion - rider transaction'
                        );
                        \Log::info("Cascade deletion tracked for rider transaction {$riderTransaction->id}, cascade record ID: " . ($cascadeRecord->id ?? 'N/A'));
                    } catch (\Exception $e) {
                        \Log::error("Failed to track cascade deletion for rider transaction {$riderTransaction->id}: " . $e->getMessage());
                    }
                    $riderTransaction->delete(); // Soft delete
                } else {
                    // Update narration with new count after deletion
                    $remainingCount = $relatedSaliks->count(); // Count after deletion
                    $riderTransaction->narration = "Salik Import - Rider Debit ({$remainingCount} transactions)";
                    $riderTransaction->save();
                }
            }

            // Update the admin charges transaction if it exists
            if ($adminCharges > 0) {
                $adminTransaction = Transactions::where('trans_code', $mainVoucherTransCode)
                    ->where('account_id', 1003)
                    ->where('credit', '>', 0)
                    ->first();

                if ($adminTransaction) {
                    $adminTransaction->credit -= $adminCharges;
                    if ($adminTransaction->credit <= 0) {
                        // Track cascade deletion before deleting
                        try {
                            $cascadeRecord = $this->trackCascadeDeletion(
                                salik::class,
                                $salik->id,
                                $salikIdentifier ?? ($salik->transaction_id ?? "Salik #{$salik->id}"),
                                Transactions::class,
                                $adminTransaction->id,
                                "Transaction #{$adminTransaction->id} (Admin Charges - Trans Code: {$adminTransaction->trans_code})",
                                'hasMany',
                                'transactions',
                                'soft',
                                'Cascade deletion from Salik group entry deletion - admin transaction'
                            );
                            \Log::info("Cascade deletion tracked for admin transaction {$adminTransaction->id}, cascade record ID: " . ($cascadeRecord->id ?? 'N/A'));
                        } catch (\Exception $e) {
                            \Log::error("Failed to track cascade deletion for admin transaction {$adminTransaction->id}: " . $e->getMessage());
                        }
                        $adminTransaction->delete(); // Soft delete
                    } else {
                        // Update narration with remaining count
                        $remainingCount = $relatedSaliks->count(); // Count of remaining Salik entries
                        $adminChargePerSalik = $adminCharges; // Original admin charge per Salik
                        $adminTransaction->narration = "Salik Import - Admin Charges ({$remainingCount} × {$adminChargePerSalik})";
                        $adminTransaction->save();
                    }
                }
            }

            // Update the main voucher amount and remarks
            $mainVoucher = Vouchers::where('trans_code', $mainVoucherTransCode)->first();
            if ($mainVoucher) {
                $mainVoucher->amount -= ($amount + $adminCharges);
                if ($mainVoucher->amount <= 0) {
                    // Track cascade deletion before deleting
                    try {
                        $cascadeRecord = $this->trackCascadeDeletion(
                            salik::class,
                            $salik->id,
                            $salikIdentifier ?? ($salik->transaction_id ?? "Salik #{$salik->id}"),
                            Vouchers::class,
                            $mainVoucher->id,
                            "Voucher #{$mainVoucher->id} (Type: {$mainVoucher->voucher_type})",
                            'hasMany',
                            'vouchers',
                            'soft',
                            'Cascade deletion from Salik group entry deletion - main voucher'
                        );
                        \Log::info("Cascade deletion tracked for main voucher {$mainVoucher->id}, cascade record ID: " . ($cascadeRecord->id ?? 'N/A'));
                    } catch (\Exception $e) {
                        \Log::error("Failed to track cascade deletion for main voucher {$mainVoucher->id}: " . $e->getMessage());
                    }
                    $mainVoucher->delete(); // Soft delete
                } else {
                    // Update voucher remarks with new count
                    $remainingCount = $relatedSaliks->count();
                    $mainVoucher->remarks = "Salik Import Main Voucher (Updated - {$remainingCount} remaining transactions)";
                    $mainVoucher->save();
                }
            }
        }

        // Update ledger entries using TransactionService to reverse this entry's impact
        if ($riderAccount) {
            // Reverse rider debit (credit back to rider)
            $transactionService->updateLedger($riderAccount->id, 0, $amount + $adminCharges, $billingMonth);
        }

        // Reverse Salik account credit (debit from Salik account)
        $transactionService->updateLedger($salik->salik_account_id, $amount, 0, $billingMonth);

        // Reverse admin charges if any
        if ($adminCharges > 0) {
            $transactionService->updateLedger(1003, 0, $adminCharges, $billingMonth);
        }

        // Use helper method to update all narrations consistently
        if ($mainVoucherTransCode) {
            $remainingCount = $relatedSaliks->count();
            $this->updateGroupNarrations($mainVoucherTransCode, $remainingCount, $adminCharges > 0 ? $adminCharges : 0);
        }

        \Log::info("Adjusted group voucher for Salik deletion - ID: {$salik->id}, Amount: {$amount}, Admin: {$adminCharges}");
    }

    private function deleteStandaloneEntry($salik, $billingMonth, $salikIdentifier)
    {
        $transactionService = new TransactionService();

        \Log::info("Deleting standalone Salik entry - ID: {$salik->id}, Transaction ID: {$salik->transaction_id}");

        // Get related transactions before deletion for cascade tracking
        $deletedTransactions = Transactions::where('reference_id', $salik->id)
            ->whereIn('reference_type', ['Salik', 'Salik Voucher'])
            ->get();

        // Get related vouchers before deletion for cascade tracking
        $relatedVouchers = Vouchers::where('ref_id', $salik->id)->get();

        // Also get vouchers by trans_code if they reference this Salik entry
        $transCode = $salik->trans_code;
        if ($transCode) {
            $transCodeVouchers = Vouchers::where('trans_code', $transCode)->get();
            $relatedVouchers = $relatedVouchers->merge($transCodeVouchers)->unique('id');

            $transCodeTransactions = Transactions::where('trans_code', $transCode)->get();
            $deletedTransactions = $deletedTransactions->merge($transCodeTransactions)->unique('id');
        }

        // Soft delete related transactions and track cascade
        foreach ($deletedTransactions as $transaction) {
            $transaction->delete(); // Soft delete

            // Track cascade deletion
            try {
                $cascadeRecord = $this->trackCascadeDeletion(
                    salik::class,
                    $salik->id,
                    $salikIdentifier,
                    Transactions::class,
                    $transaction->id,
                    "Transaction #{$transaction->id} (Trans Code: {$transaction->trans_code})",
                    'hasMany',
                    'transactions',
                    'soft',
                    'Cascade deletion from Salik entry deletion'
                );
                \Log::info("Cascade deletion tracked for transaction {$transaction->id}, cascade record ID: " . ($cascadeRecord->id ?? 'N/A'));
            } catch (\Exception $e) {
                \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
            }
        }

        // Soft delete related vouchers and track cascade
        foreach ($relatedVouchers as $voucher) {
            $voucher->delete(); // Soft delete

            // Track cascade deletion
            try {
                $cascadeRecord = $this->trackCascadeDeletion(
                    salik::class,
                    $salik->id,
                    $salikIdentifier,
                    Vouchers::class,
                    $voucher->id,
                    "Voucher #{$voucher->id} (Type: {$voucher->voucher_type})",
                    'hasMany',
                    'vouchers',
                    'soft',
                    'Cascade deletion from Salik entry deletion'
                );
                \Log::info("Cascade deletion tracked for voucher {$voucher->id}, cascade record ID: " . ($cascadeRecord->id ?? 'N/A'));
            } catch (\Exception $e) {
                \Log::error("Failed to track cascade deletion for voucher {$voucher->id}: " . $e->getMessage());
            }
        }

        // Update ledger entries using TransactionService to reverse the transactions
        $riderAccount = DB::table('accounts')->where('ref_id', $salik->rider_id)->first();
        if ($riderAccount) {
            // Reverse rider debit (credit back to rider)
            $transactionService->updateLedger($riderAccount->id, 0, $salik->amount + ($salik->admin_charges ?? 0), $billingMonth);
            \Log::info("Reversed rider debit for account ID: {$riderAccount->id}, Amount: " . ($salik->amount + ($salik->admin_charges ?? 0)));
        }

        // Reverse Salik account credit (debit from Salik account)
        $transactionService->updateLedger($salik->salik_account_id, $salik->amount, 0, $billingMonth);
        \Log::info("Reversed Salik account credit for account ID: {$salik->salik_account_id}, Amount: {$salik->amount}");

        // Reverse admin charges if any
        if ($salik->admin_charges > 0) {
            $transactionService->updateLedger(1003, 0, $salik->admin_charges, $billingMonth);
            \Log::info("Reversed admin charges: {$salik->admin_charges}");
        }

        \Log::info("Successfully deleted standalone Salik entry and reversed all ledger entries");
    }

    /**
     * Update all narrations for remaining Salik entries in a group after deletion
     */
    private function updateGroupNarrations($transCode, $remainingCount, $adminChargePerSalik = 0)
    {
        // Update rider transaction narration
        $riderTransaction = Transactions::where('trans_code', $transCode)
            ->where('debit', '>', 0)
            ->first();

        if ($riderTransaction) {
            $riderTransaction->narration = "Salik Import - Rider Debit ({$remainingCount} transactions)";
            $riderTransaction->save();
            \Log::info("Updated rider transaction narration for trans_code: {$transCode}");
        }

        // Update admin charges narration if applicable
        if ($adminChargePerSalik > 0) {
            $adminTransaction = Transactions::where('trans_code', $transCode)
                ->where('account_id', 1003)
                ->where('credit', '>', 0)
                ->first();

            if ($adminTransaction) {
                $adminTransaction->narration = "Salik Import - Admin Charges ({$remainingCount} × {$adminChargePerSalik})";
                $adminTransaction->save();
                \Log::info("Updated admin transaction narration for trans_code: {$transCode}");
            }
        }

        // Update main voucher remarks
        $mainVoucher = Vouchers::where('trans_code', $transCode)->first();
        if ($mainVoucher) {
            $mainVoucher->remarks = "Salik Import Main Voucher (Updated - {$remainingCount} remaining transactions)";
            $mainVoucher->save();
            \Log::info("Updated main voucher remarks for trans_code: {$transCode}");
        }
    }
    public function attachFile($id, Request $request)
    {
        $salik = salik::findOrFail($id);
        if ($request->isMethod('post')) {
            $request->validate([
                'attachment_path' => 'required|file',
            ]);
            $file = $request->file('attachment_path');
            $path = $file->store('salik/files', 'public');
            $salik->attachment_path = $path;
            $salik->save();
            return redirect()->back()->with('success', 'File uploaded successfully.');
        }
        return view('salik.attach_file', ['salik' => $salik, 'id' => $id]);
    }
    public function viewvoucher($id)
    {
        $data = salik::findOrFail($id);
        $accounts = Accounts::find($data->account_id);
        return view('salik.viewvoucher', compact('data', 'accounts'));
    }

    /**
     * Get the rider for a bike on a specific date (for AJAX filtering)
     */
    public function getriderbybikedate(Request $request)
    {
        $bike_id = $request->input('bike_id');
        $trip_date = $request->input('trip_date');
        $bike = Bikes::find($bike_id);
        $history = BikeHistory::where('bike_id', $bike->id)
            ->whereDate('note_date', '<=', $trip_date)
            ->where(function ($q) use ($trip_date) {
                $q->whereNull('return_date')
                    ->orWhereDate('return_date', '>=', $trip_date);
            })
            ->orderBy('note_date', 'desc')
            ->first();

        if ($history && $history->rider_id) {
            $rider = DB::table('riders')->where('id', $history->rider_id)->first();
            $account = DB::table('accounts')->where('ref_id', $rider->id)->first();
            return response()->json([
                'options' => [
                    [
                        'value' => $rider ? $rider->id : '',
                        'label' => $rider->rider_id . ' - ' . $rider->name
                    ]
                ]
            ]);
        } else {
            return response()->json(['options' => []]);
        }
    }

    /**
     * Test Excel file reading
     */
    public function testImport(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');
                \Log::info('File details: ' . json_encode([
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => $file->getClientOriginalExtension(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ]));

                $collection = Excel::toCollection(new SalikImport(1, 0), $file);
                \Log::info('Excel collection count: ' . $collection->count());

                if ($collection->count() > 0) {
                    $firstSheet = $collection->first();
                    \Log::info('First sheet rows: ' . $firstSheet->count());
                    \Log::info('First 3 rows: ' . json_encode($firstSheet->take(3)->toArray()));
                }

                return response()->json(['message' => 'Check logs for file details']);
            } catch (\Exception $e) {
                \Log::error('Test import error: ' . $e->getMessage());
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }
        return response()->json(['error' => 'No file uploaded'], 422);
    }

    /**
     * Import Salik records from Excel file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
            'salik_account_id' => 'required|numeric',
            'admin_charge_per_salik' => 'nullable|numeric'
        ]);
        try {
            $salikAccountId = $request->salik_account_id;
            $adminChargePerSalik = $request->admin_charge_per_salik ?? 0;
            \Log::info('Starting Salik import with account ID: ' . $salikAccountId . ', admin charge: ' . $adminChargePerSalik);
            $import = new SalikImport($salikAccountId, $adminChargePerSalik);
            Excel::import($import, $request->file('file'));
            $importedCount = salik::where('salik_account_id', $salikAccountId)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();
            \Log::info('Import completed. Records imported: ' . $importedCount);
            $logContent = file_get_contents(storage_path('logs/laravel.log'));
            $missingDataMatches = preg_match_all('/Missing required fields in row/', $logContent);
            $duplicateExcelMatches = preg_match_all('/Duplicate Transaction ID found in Excel file:/', $logContent);
            $updatedExistingMatches = preg_match_all('/Updated existing Salik record with ID:/', $logContent);
            $noBikeMatches = preg_match_all('/Bike not found for plate:/', $logContent);
            $noRiderMatches = preg_match_all('/No rider found for bike/', $logContent);
            $noAccountMatches = preg_match_all('/No account found for rider:/', $logContent);
            $messages = [];
            if ($missingDataMatches > 0) {
                $messages[] = "{$missingDataMatches} missing data";
            }
            if ($duplicateExcelMatches > 0) {
                $messages[] = "{$duplicateExcelMatches} duplicates (within Excel file)";
            }
            if ($updatedExistingMatches > 0) {
                $messages[] = "{$updatedExistingMatches} existing records updated";
            }
            if ($noBikeMatches > 0) {
                $messages[] = "{$noBikeMatches} unknown bikes";
            }
            if ($noRiderMatches > 0) {
                $messages[] = "{$noRiderMatches} no riders";
            }
            if ($noAccountMatches > 0) {
                $messages[] = "{$noAccountMatches} no accounts";
            }
            $skippedMessage = !empty($messages) ? " (Skipped: " . implode(', ', $messages) . ")" : "";
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Salik records imported successfully with vouchers created.{$skippedMessage}",
                    'imported_count' => $importedCount
                ]);
            }
            Flash::success("Salik records imported successfully with vouchers created. Records imported: {$importedCount}{$skippedMessage}");
            return redirect()->back();
        } catch (\Exception $e) {
            \Log::error('Salik import failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import failed: ' . $e->getMessage()
                ], 422);
            }
            Flash::error('Import failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Show import form
     */
    public function importForm($salikAccountId)
    {
        $account = Accounts::findOrFail($salikAccountId);
        return view('salik.import', compact('account'));
    }

    /**
     * Show missing Salik records that couldn't be imported
     */
    public function showMissingRecords(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('salik_view')) {
            abort(403, 'Unauthorized action.');
        }
        $perPage = $request->get('per_page', 50);
        $failedImports = FailedSalikImport::orderBy('created_at', 'desc')
            ->paginate($perPage);
        $missingRecords = [];
        foreach ($failedImports as $failed) {
            $missingRecords[] = [
                'transaction_id' => $failed->transaction_id,
                'trip_date' => $failed->trip_date,
                'plate_number' => $failed->plate_number,
                'amount' => $failed->amount,
                'reason' => $failed->reason,
                'details' => $failed->details,
                'status' => 'Failed Import',
                'suggested_action' => $this->getSuggestedAction($failed->reason),
                'row_number' => $failed->row_number,
                'import_date' => $failed->created_at,
                'batch_id' => $failed->import_batch_id
            ];
        }
        if ($failedImports->count() == 0) {
            $missingRecords = $this->getMissingSalikRecords();
        }
        $importStats = $this->getImportStatistics();
        $totalAmount = 0;
        foreach ($missingRecords as $record) {
            $totalAmount += $record['amount'] ?? 0;
        }
        return view('salik.missing_records', compact('missingRecords', 'importStats', 'failedImports', 'totalAmount'));
    }

    /**
     * Get import statistics
     */
    private function getImportStatistics()
    {
        return [
            'total_imports' => 0,
            'successful_imports' => 0,
            'failed_imports' => 0,
            'last_import_date' => null,
            'common_issues' => []
        ];
    }

    /**
     * Get missing Salik records with reasons
     */
    private function getMissingSalikRecords()
    {
        $missingRecords = [];
        $failedImports = FailedSalikImport::orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        if ($failedImports->count() > 0) {
            foreach ($failedImports as $failed) {
                $missingRecords[] = [
                    'transaction_id' => $failed->transaction_id,
                    'trip_date' => $failed->trip_date,
                    'plate_number' => $failed->plate_number,
                    'amount' => $failed->amount,
                    'reason' => $failed->reason,
                    'details' => $failed->details,
                    'status' => 'Failed Import',
                    'suggested_action' => $this->getSuggestedAction($failed->reason),
                    'row_number' => $failed->row_number,
                    'import_date' => $failed->created_at,
                    'batch_id' => $failed->import_batch_id
                ];
            }
        }
        if ($failedImports->count() == 0) {
            $this->analyzeMissingBikes($missingRecords, Bikes::with('rider')->get()->keyBy('plate'));
            $this->analyzeImportFailures($missingRecords);
        }
        return $missingRecords;
    }

    /**
     * Analyze bikes that might be missing from imports
     */
    private function analyzeMissingBikes(&$missingRecords, $bikes)
    {
        $salikPlates = salik::select('plate')
            ->whereNotNull('plate')
            ->where('plate', '!=', '')
            ->distinct()
            ->pluck('plate')
            ->toArray();
        foreach ($salikPlates as $plate) {
            if (!$bikes->has($plate)) {
                $sampleSalik = salik::where('plate', $plate)->first();
                $missingRecords[] = [
                    'transaction_id' => $sampleSalik ? $sampleSalik->transaction_id : 'N/A',
                    'trip_date' => $sampleSalik ? $sampleSalik->trip_date : date('Y-m-d'),
                    'plate_number' => $plate,
                    'amount' => $sampleSalik ? $sampleSalik->amount : 0.00,
                    'reason' => 'No bike found with this plate number',
                    'details' => "Plate {$plate} exists in Salik records but not in bikes table",
                    'status' => 'Missing Bike',
                    'suggested_action' => $this->getSuggestedAction('No bike found with this plate number')
                ];
            }
        }
    }

    /**
     * Analyze riders that might be missing from imports
     */
    private function analyzeMissingRiders(&$missingRecords, $bikes, $bikeHistory)
    {
        // Check for bikes without assigned riders
        foreach ($bikes as $plate => $bike) {
            if (!$bike->rider_id) {
                $missingRecords[] = [
                    'transaction_id' => 'N/A',
                    'trip_date' => date('Y-m-d'),
                    'plate_number' => $plate,
                    'amount' => 0.00,
                    'reason' => 'No rider assigned for this trip date',
                    'details' => "Bike {$plate} has no rider assigned",
                    'status' => 'Missing Rider',
                    'suggested_action' => $this->getSuggestedAction('No rider assigned for this trip date')
                ];
            }
        }
    }

    /**
     * Analyze accounts that might be missing from imports
     */
    private function analyzeMissingAccounts(&$missingRecords, $riders)
    {
        // Check for riders without accounts
        foreach ($riders as $rider) {
            if (!$rider->account) {
                $missingRecords[] = [
                    'transaction_id' => 'N/A',
                    'trip_date' => date('Y-m-d'),
                    'plate_number' => 'N/A',
                    'amount' => 0.00,
                    'reason' => 'No account found for rider',
                    'details' => "Rider {$rider->name} has no associated account",
                    'status' => 'Missing Account',
                    'suggested_action' => $this->getSuggestedAction('No account found for rider')
                ];
            }
        }
    }

    /**
     * Analyze import failures from logs or recent imports
     */
    private function analyzeImportFailures(&$missingRecords)
    {
        // Get recent Salik records that might indicate import issues
        $recentSaliks = Salik::where('created_at', '>=', now()->subDays(7))
            ->where(function ($query) {
                $query->whereNull('rider_id')
                    ->orWhere('rider_id', 0)
                    ->orWhereNull('bike_id')
                    ->orWhere('bike_id', 0);
            })
            ->get();

        foreach ($recentSaliks as $salik) {
            if (!$salik->rider_id || $salik->rider_id == 0) {
                $missingRecords[] = [
                    'transaction_id' => $salik->transaction_id,
                    'trip_date' => $salik->trip_date,
                    'plate_number' => $salik->plate,
                    'amount' => $salik->amount,
                    'reason' => 'No rider assigned for this trip date',
                    'details' => "Salik record exists but no rider assigned for plate {$salik->plate}",
                    'status' => 'Missing Rider Assignment',
                    'suggested_action' => $this->getSuggestedAction('No rider assigned for this trip date')
                ];
            }
        }
    }

    /**
     * Parse Excel file to identify potential missing records
     */
    public function analyzeExcelFile(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('salik_view')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        try {
            $file = $request->file('excel_file');
            $data = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Imports\SalikImport(1, 0), $file);

            $potentialIssues = [];
            $bikes = Bikes::pluck('plate')->toArray();
            $existingTransactionIds = Salik::pluck('transaction_id')->toArray();

            foreach ($data[0] as $index => $row) {
                if ($index == 0) continue; // Skip header

                $transactionId = $row[0] ?? null;
                $plateNumber = $row[5] ?? null;
                $tripDate = $row[2] ?? null;
                $amount = $row[8] ?? null;

                // Check for missing bike
                if ($plateNumber && !in_array($plateNumber, $bikes)) {
                    $potentialIssues[] = [
                        'row' => $index + 1,
                        'transaction_id' => $transactionId,
                        'plate_number' => $plateNumber,
                        'trip_date' => $tripDate,
                        'amount' => $amount,
                        'issue' => 'Bike not found in system',
                        'severity' => 'High'
                    ];
                }

                // Check for duplicate transaction ID
                if ($transactionId && in_array($transactionId, $existingTransactionIds)) {
                    $potentialIssues[] = [
                        'row' => $index + 1,
                        'transaction_id' => $transactionId,
                        'plate_number' => $plateNumber,
                        'trip_date' => $tripDate,
                        'amount' => $amount,
                        'issue' => 'Duplicate transaction ID',
                        'severity' => 'Medium'
                    ];
                }

                // Check for missing required fields
                if (empty($transactionId) || empty($plateNumber) || empty($tripDate) || empty($amount)) {
                    $potentialIssues[] = [
                        'row' => $index + 1,
                        'transaction_id' => $transactionId,
                        'plate_number' => $plateNumber,
                        'trip_date' => $tripDate,
                        'amount' => $amount,
                        'issue' => 'Missing required fields',
                        'severity' => 'High'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'total_rows' => count($data[0]) - 1,
                'potential_issues' => $potentialIssues,
                'summary' => [
                    'high_severity' => count(array_filter($potentialIssues, fn($i) => $i['severity'] === 'High')),
                    'medium_severity' => count(array_filter($potentialIssues, fn($i) => $i['severity'] === 'Medium')),
                    'low_severity' => count(array_filter($potentialIssues, fn($i) => $i['severity'] === 'Low'))
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error analyzing Excel file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export missing Salik records to Excel
     */
    public function exportMissingRecords(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('salik_view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Get all failed import records
            $failedImports = FailedSalikImport::orderBy('created_at', 'desc')->get();

            if ($failedImports->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No missing records to export'
                ]);
            }

            // Prepare data for export
            $exportData = [];
            $exportData[] = [
                'Transaction ID',
                'Transaction Post Date',
                'Trip Date',
                'Trip Time',
                'Billing Month',
                'Plate Number',
                'Amount',
                'Salik Account ID',
                'Admin Charge',
                'Details',
                'Reason',
                'Row Number',
                'Import Date'
            ];

            foreach ($failedImports as $failed) {
                $exportData[] = [
                    $failed->transaction_id,
                    '', // Transaction Post Date - not available in failed imports
                    $failed->trip_date ? \Carbon\Carbon::parse($failed->trip_date)->format('Y-m-d') : '',
                    '', // Trip Time - not available in failed imports
                    $failed->trip_date ? \Carbon\Carbon::parse($failed->trip_date)->format('Y-m-01') : '',
                    $failed->plate_number,
                    $failed->amount,
                    '', // Salik Account ID - not available in failed imports
                    '', // Admin Charge - not available in failed imports
                    $failed->details,
                    $failed->reason,
                    $failed->row_number,
                    $failed->created_at ? $failed->created_at->format('Y-m-d H:i:s') : ''
                ];
            }

            // Generate filename with timestamp
            $filename = 'missing_salik_records_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            // Create Excel file
            $excel = \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\MissingSalikExport($exportData),
                $filename
            );

            \Log::info("Exported {$failedImports->count()} missing Salik records to {$filename}");

            return $excel;
        } catch (\Exception $e) {
            \Log::error("Error exporting missing Salik records: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error exporting missing records: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Clear old failed import records
     */
    public function clearFailedImports(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('salik_view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Delete all records
            $deletedCount = FailedSalikImport::query()->delete();

            return response()->json([
                'success' => true,
                'message' => "Cleared {$deletedCount} failed import records",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing failed imports: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Get suggested action based on the reason
     */
    private function getSuggestedAction($reason)
    {
        switch ($reason) {
            case 'No bike found with this plate number':
                return 'Add bike with this plate number to the bikes table';
            case 'No rider assigned for this trip date':
                return 'Assign a rider to this bike or update bike history';
            case 'No account found for rider':
                return 'Create an account for this rider in the accounts table';
            case 'Duplicate transaction ID':
                return 'Skip this record as it already exists';
            case 'Missing required fields':
                return 'Complete the missing data in the Excel file';
            default:
                return 'Review and fix the data issue';
        }
    }
}
