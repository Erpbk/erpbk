<?php

namespace App\Http\Controllers;

use App\DataTables\RtaFinesDataTable;
use App\Helpers\Account;
use App\Helpers\Common;
use App\Http\Requests\CreateRtaFinesRequest;
use App\Http\Requests\UpdateRtaFinesRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Bikes;
use App\Models\Riders;
use App\Models\RtaFines;
use App\Models\Accounts;
use App\Models\Vouchers;
use App\Models\LedgerEntry;
use App\Models\Transactions;
use App\Repositories\RtaFinesRepository;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Flash;
use DB;
use App\Imports\RTAFineImport;
use Maatwebsite\Excel\Facades\Excel;

class RtaFinesController extends AppBaseController
{
    use GlobalPagination, TracksCascadingDeletions;
    /** @var RtaFinesRepository $rtaFinesRepository*/
    private $rtaFinesRepository;

    public function __construct(RtaFinesRepository $rtaFinesRepo)
    {
        $this->rtaFinesRepository = $rtaFinesRepo;
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
        $parent = Accounts::where('id', 1235)->first();
        if (!$parent) {
            Flash::success('Parent account "RTA Fines" not found.');
        }
        // Create new account
        $newdata = new Accounts();
        $newdata->name = $request->name;
        $newdata->traffic_code_number = $request->traffic_code_number;
        $newdata->account_tax = $request->account_tax;
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
        $parent = Accounts::where('id', 1235)->first();
        if (!$parent) {
            Flash::error('Parent account "RTA Fines" not found.');
        }
        $newdata = Accounts::find($request->id);
        $newdata->name = $request->name;
        $newdata->traffic_code_number = $request->traffic_code_number;
        $newdata->account_tax = $request->account_tax;
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
        // Check if there are any rtaFines (tickets) related to this account (including soft deleted)
        $hasFines = RtaFines::withTrashed()->where('rta_account_id', $id)->exists();

        if ($hasFines) {
            Flash::error('Cannot delete account. There are existing RTA fines (tickets) linked to this account.');
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
        if (!auth()->user()->hasPermissionTo('rtafine_view')) {
            abort(403, 'Unauthorized action.');
        }
        $parent = Accounts::where('id', 1235)->first();
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
            $tableData = view('rta_fines.account_table', [
                'data' => $data,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }
        return view('rta_fines.account_index', [
            'data' => $data,
        ]);
    }
    public function tickets(Request $request, $id)
    {
        if (!auth()->user()->hasPermissionTo('rtafine_view')) {
            abort(403, 'Unauthorized action.');
        }

        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = RtaFines::query()
            ->orderBy('id', 'asc')
            ->where('rta_account_id', $id);

        if ($request->filled('ticket_no')) {
            $query->where('ticket_no', 'like', '%' . $request->ticket_no . '%');
        }
        if ($request->filled('billing_month')) {
            $billingMonth = \Carbon\Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)
                ->whereMonth('billing_month', $billingMonth->month);
        }
        if ($request->filled('trans_code')) {
            $query->where('trans_code', $request->trans_code);
        }
        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->rider_id);
        }
        if ($request->filled('bike_id')) {
            $query->where('bike_id', $request->bike_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Paginated data
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);

        // All matching (filtered) data to calculate totals
        $filteredData = $query->get();

        // Calculate totals
        $paidAmount   = $filteredData->where('status', 'paid')->sum('amount');
        $unpaidAmount = $filteredData->where('status', 'unpaid')->sum('amount');
        $totaltickets = $filteredData->count();
        $paidCount    = $filteredData->where('status', 'paid')->count();
        $unpaidCount  = $filteredData->where('status', 'unpaid')->count();
        $totalAmount = $filteredData->sum('amount');
        $serviceCharges = $filteredData->sum('service_charges');
        $adminFee = $filteredData->sum('admin_fee');
        $account = Accounts::find($id);
        $total_Amount =  $totalAmount + $serviceCharges + $adminFee;
        if ($request->ajax()) {
            $tableData = view('rta_fines.table', [
                'data' => $data,
                'account' => $account,
            ])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'totals' => [
                    'paidAmount'   => number_format($paidAmount, 2),
                    'unpaidAmount' => number_format($unpaidAmount, 2),
                    'totalAmount' => $totalAmount,
                    'totaltickets'    => $totaltickets,
                    'paidCount'    => $paidCount,
                    'unpaidCount'  => $unpaidCount,
                    'serviceCharges' => $serviceCharges,
                    'adminFee' => $adminFee,
                    'total_Amount' => $total_Amount,
                ]
            ]);
        }
        return view('rta_fines.index', [
            'data' => $data,
            'account' => $account,
            'paidAmount' => $paidAmount,
            'unpaidAmount' => $unpaidAmount,
            'totalAmount' => $totalAmount,
            'paidCount' => $paidCount,
            'unpaidCount' => $unpaidCount,
            'totaltickets' => $totaltickets,
            'serviceCharges' => $serviceCharges,
            'adminFee' => $adminFee,
            'total_Amount' => $total_Amount,
        ]);
    }

    public function payfine(Request $request)
    {
        DB::beginTransaction();

        try {

            $service_accounts = DB::table('accounts')->where('name', 'Service Charges (RTA Fine)')->where('account_type', 'Expense')->first();
            $fine = RtaFines::findOrFail($request->id);
            $fine->pay_account = $request->account;

            if ($fine->status == 'paid') {
                $fine->status = 'unpaid';
            } else {
                $fine->status = 'paid';

                // Determine payment type flag
                $payment_type_flag = match ($request->payment_type) {
                    'Liability' => 1,
                    'Asset' => 0,
                    default => null,
                };

                // File Upload
                $photo = $request->file('attach_file');
                $docFile = $photo->store('fines/files', 'public');

                // Narration
                $remarks = $request->voucher_type === 'RFV' ? 'RTA Fine Voucher' : 'Journal Voucher';

                $trans_code = Account::trans_code();
                $TransactionService = new TransactionService();

                $billingMonth = $fine->billing_month ?? date('Y-m-01');
                $transDate = $fine->trans_date;

                // Predefined Accounts (pull from config or DB)
                $admin_fee_account_id = config('accounts.admin_fee_account_id', 302);
                $service_charge_account_id = config('accounts.service_charge_account_id', 303);
                // 1. Fine Amount
                if ($fine->amount > 0) {
                    // Debit RTA Account
                    $TransactionService->recordTransaction([
                        'account_id'     => $fine->rta_account_id,
                        'reference_id'   => $fine->id,
                        'reference_type' => 'RTA',
                        'trans_code'     => $trans_code,
                        'trans_date'     => $transDate,
                        'narration'      => $fine->detail ?? 'RTA Fine Payment',
                        'debit'          => $fine->amount,
                        'billing_month'  => $billingMonth,
                    ]);
                }

                // 2. Service Charges
                if ($fine->service_charges > 0) {
                    $TransactionService->recordTransaction([
                        'account_id'     => $service_accounts->id,
                        'reference_id'   => $fine->id,
                        'reference_type' => 'RTA',
                        'trans_code'     => $trans_code,
                        'trans_date'     => $transDate,
                        'narration'      => $service_accounts->name . 'RTA Fine',
                        'debit'          => $fine->service_charges,
                        'billing_month'  => $billingMonth,
                    ]);
                }

                if ($fine->amount > 0) {

                    // Credit Selected Payment Account
                    $TransactionService->recordTransaction([
                        'account_id'     => $request->account,
                        'reference_id'   => $fine->id,
                        'reference_type' => 'RTA',
                        'trans_code'     => $trans_code,
                        'trans_date'     => $transDate,
                        'narration'      => $fine->detail ?? 'RTA Fine Payment',
                        'credit'         => $fine->amount + $fine->service_charges,
                        'billing_month'  => $billingMonth,
                    ]);
                }

                // 4. Voucher
                Vouchers::create([
                    'rider_id'      => $request->rider_id,
                    'trans_date'    => $transDate,
                    'trans_code'    => $trans_code,
                    'trip_date'     => $request->trip_date,
                    'billing_month' => $billingMonth,
                    'payment_type'  => $payment_type_flag,
                    'voucher_type'  => $request->voucher_type,
                    'remarks'       => $remarks,
                    'amount'        => $fine->total_amount,
                    'Created_By'    => $request->Created_By,
                    'attach_file'   => $docFile,
                    'pay_account'   => $request->account,
                    'ref_id'        => $fine->id,
                ]);

                // 5. Ledger Entry (Against Payment Account)
                $total_amount = floatval($fine->total_amount);
                $lastLedger = DB::table('ledger_entries')
                    ->where('account_id', $request->account)
                    ->orderBy('billing_month', 'desc')
                    ->first();

                $opening_balance = $lastLedger ? $lastLedger->closing_balance : 0.00;
                $debit_balance = $credit_balance = 0.00;

                if ($payment_type_flag === 1) { // Liability
                    $debit_balance = $total_amount;
                    $closing_balance = $opening_balance + $total_amount;
                } elseif ($payment_type_flag === 0) { // Asset
                    $credit_balance = $total_amount;
                    $closing_balance = $opening_balance - $total_amount;
                } else {
                    $closing_balance = $opening_balance;
                }

                DB::table('ledger_entries')->insert([
                    'account_id'      => $request->account,
                    'billing_month'   => $billingMonth,
                    'opening_balance' => $opening_balance,
                    'debit_balance'   => $debit_balance,
                    'credit_balance'  => $credit_balance,
                    'closing_balance' => $closing_balance,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            $fine->save();
            DB::commit();

            Flash::success('Fine Paid Successfully with Transaction and Ledger Entries.');
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());
        }

        return redirect(route('rtaFines.tickets', $fine->rta_account_id));
    }



    public function viewvoucher($id)
    {
        $data = RtaFines::where('id', $id)->first();
        $accounts = Accounts::where('id', $data->rta_account_id)->first();
        return view('rta_fines.viewvoucher', compact('data', 'accounts'));
    }

    /**
     * Show the form for creating a new RtaFines.
     */
    public function create($id)
    {
        $data = Accounts::where('id', $id)->first();
        return view('rta_fines.create', compact('data'));
    }
    /**
     * Store a newly created RtaFines in storage.
     */
    public function store(CreateRtaFinesRequest $request)
    {
        $exists = DB::table('rta_fines')->where('ticket_no', $request->ticket_no)->exists();

        if ($exists) {
            return response()->json(['errors' => ['error' => 'This Ticket Number already exists.']], 422);
        }
        DB::beginTransaction();

        try {
            $admin_accounts = DB::Table('accounts')->where('id', 1004)->first();
            $service_accounts = DB::table('accounts')->where('id', 1368)->first();
            $input = $request->all();
            $bike = Bikes::findOrFail($input['bike_id']);
            $trans_code = Account::trans_code();

            // Upload file
            $path = $request->file('attachment')->store('fines/files', 'public');

            // Set values
            $input['billing_month']   = $input['billing_month'] . "-01";
            $input['rider_id']        = $input['debit_account'];
            $input['attachment']      = $request->file('attachment')->getClientOriginalName();
            $input['attachment_path'] = $path;
            $input['plate_no']        = $bike->plate;
            $input['trans_date']      = Carbon::today();
            $input['trans_code']      = $trans_code;
            $input['total_amount']    = $request->amount + $request->service_charges + $request->admin_fee;
            $input['status']          = 'unpaid';
            $input['reference_number']        = $input['reference_number'];

            // Create RTA Fine
            $rtaFines = $this->rtaFinesRepository->create($input);

            // Account IDs (replace with config or DB if needed)
            $admin_fee_account_id = config('accounts.admin_fee_account_id', 302);
            $service_charge_account_id = config('accounts.service_charge_account_id', 303);

            $TransactionService = new TransactionService();
            $billingMonth = $rtaFines->billing_month;

            $rider_account = DB::table('accounts')->where('ref_id', $rtaFines->rider_id)->first();
            // --- 1. Main Fine ---
            $TransactionService->recordTransaction([
                'account_id'     => $rider_account->id,
                'reference_id'   => $rtaFines->id,
                'reference_type' => 'RTA',
                'trans_code'     => $trans_code,
                'trans_date'     => $rtaFines->trans_date,
                'narration'      => $rtaFines->detail ?? 'RTA Fine',
                'debit'          => $rtaFines->total_amount,
                'billing_month'  => $billingMonth,
            ]);




            // --- 3. Admin Charges ---
            if ($request->admin_fee > 0) {
                $TransactionService->recordTransaction([
                    'account_id'     => $admin_accounts->id,
                    'reference_id'   => $rtaFines->id,
                    'reference_type' => 'RTA',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $rtaFines->trans_date,
                    'narration'      => $admin_accounts->name,
                    'credit'          => $request->admin_fee,
                    'billing_month'  => $billingMonth,
                ]);
            }
            // --- 2. Service Charges ---
            if ($request->service_charges > 0) {
                $TransactionService->recordTransaction([
                    'account_id'     => $service_accounts->id,
                    'reference_id'   => $rtaFines->id,
                    'reference_type' => 'RTA',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $rtaFines->trans_date,
                    'narration'      => $service_accounts->name . 'RTA Fine',
                    'credit'          => $request->service_charges,
                    'billing_month'  => $billingMonth,
                ]);
            }
            $TransactionService->recordTransaction([
                'account_id'     => $rtaFines->rta_account_id,
                'reference_id'   => $rtaFines->id,
                'reference_type' => 'RTA',
                'trans_code'     => $trans_code,
                'trans_date'     => $rtaFines->trans_date,
                'narration'      => $rtaFines->detail ?? 'RTA Fine Received',
                'credit'         => $request->amount,
                'billing_month'  => $billingMonth,
            ]);
            // --- Voucher ---
            Vouchers::create([
                'rider_id'      => $rtaFines->rider_id,
                'trans_date'    => $rtaFines->trans_date,
                'trans_code'    => $rtaFines->trans_code,
                'trip_date'     => $rtaFines->trip_date,
                'billing_month' => $billingMonth,
                'payment_type'  => 1,
                'voucher_type'  => 'RFV',
                'remarks'       => 'RTA Fine Voucher',
                'amount'        => $rtaFines->total_amount,
                'Created_By'    => auth()->id(),
                'attach_file'   => $path,
                'pay_account'   => $rider_account->id,
                'ref_id'        => $rtaFines->id,
            ]);

            // --- Ledger Entry ---
            $lastLedger = DB::table('ledger_entries')
                ->where('account_id', $rider_account->id)
                ->orderBy('billing_month', 'desc')
                ->first();

            $opening_balance = $lastLedger ? $lastLedger->closing_balance : 0.00;
            $debit_amount = $rtaFines->total_amount;
            $closing_balance = $opening_balance + $debit_amount;

            DB::table('ledger_entries')->insert([
                'account_id'      => $rider_account->id,
                'billing_month'   => $billingMonth,
                'opening_balance' => $opening_balance,
                'debit_balance'   => $debit_amount,
                'credit_balance'  => 0.00,
                'closing_balance' => $closing_balance,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::commit();
            Flash::success('RTA Fine added successfully with all charges and ledger.');

            return redirect(route('rtaFines.tickets', $rtaFines->rta_account_id));
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }


    public function fileUpload(Request $request, $id)
    {
        $fines = RtaFines::find($id);

        if ($request->hasFile('attachment_path')) {
            $photo = $request->file('attachment_path');

            // Store file in storage/app/public/fines/files
            $docFile = $photo->store('fines/files', 'public');

            // Save original name and stored path
            $fines->attachment = $photo->getClientOriginalName();
            $fines->attachment_path = $docFile;

            $fines->save();
        }

        return view('rta_fines.attach_file', compact('id', 'fines'));
    }

    /**
     * Display the specified RtaFines.
     */
    public function show($id)
    {
        $rtaFines = $this->rtaFinesRepository->find($id);

        if (empty($rtaFines)) {
            Flash::error('Rta Fines not found');

            return redirect(route('rtaFines.index'));
        }

        return view('rta_fines.show')->with('rtaFines', $rtaFines);
    }

    /**
     * Show the form for editing the specified RtaFines.
     */
    public function edit($id)
    {

        $rtaFines = $this->rtaFinesRepository->find($id);
        $data = Accounts::where('id', $rtaFines->rta_account_id)->first();

        if (empty($rtaFines)) {
            Flash::error('Rta Fines not found');

            return redirect(route('rtaFines.index'));
        }

        return view('rta_fines.edit', compact('data', 'rtaFines'));
    }

    /**
     * Update the specified RtaFines in storage.
     */
    public function update(Request $request)
    {
        // Check if same ticket_no exists on any other record
        $exists = DB::table('rta_fines')
            ->where('ticket_no', $request->ticket_no)
            ->where('id', '!=', $request->id)
            ->exists();

        if ($exists) {
            return response()->json(['errors' => ['error' => 'This Ticket No is already used in another fine.']], 422);
        }

        DB::beginTransaction();

        try {
            $id = $request->id;
            $admin_accounts   = DB::table('accounts')->where('id', 1004)->first();
            $service_accounts = DB::table('accounts')->where('id', 1368)->first();

            $input = $request->all();
            $bike  = Bikes::findOrFail($input['bike_id']);
            $rta_account = DB::table('rta_fines')->where('id', $request->id)->first();
            $rtaFines = RtaFines::findOrFail($id);
            $rider = DB::table('riders')->where('id', $rtaFines->rider_id)->first();

            // Upload new file if provided
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('fines/files', 'public');
                $input['attachment'] = $request->file('attachment')->getClientOriginalName();
                $input['attachment_path'] = $path;
            } else {
                $path = $rtaFines->attachment_path;
            }

            // Update fields
            $input['billing_month']   = Carbon::createFromFormat('Y-m', $input['billing_month'])->startOfMonth()->format('Y-m-d');
            $input['rider_id']        = $input['debit_account'];
            $input['plate_no']        = $bike->plate;
            $input['trans_date']      = Carbon::today()->format('Y-m-d');
            $input['total_amount']    = $request->amount + $request->service_charges + $request->admin_fee;
            $input['rta_account_id']  = $request->rta_account_id;

            $rtaFines->update($input);
            $trans_code    = $rtaFines->trans_code;
            $billingMonth  = $rtaFines->billing_month;
            /*
        |--------------------------------------------------------------------------
        | Transactions (update only)
        |--------------------------------------------------------------------------
        */
            $rider_account = DB::table('accounts')->where('ref_id', $rtaFines->rider_id)->first();
            $this->upsertTransaction(
                [
                    'account_id'     => $rider_account->id,
                    'reference_id'   => $rtaFines->id,
                    'reference_type' => 'RTA',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $rtaFines->trans_date,
                    'narration'      => $rtaFines->detail ?? 'RTA Fine',
                    'debit'          => $rtaFines->total_amount,
                    'credit'         => 0,
                    'billing_month'  => $billingMonth,
                ],
                $rtaFines->rider_id
            );

            if ($request->admin_fee > 0) {
                $this->upsertTransaction2([
                    'account_id'     => $admin_accounts->id,
                    'reference_id'   => $rtaFines->id,
                    'reference_type' => 'RTA',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $rtaFines->trans_date,
                    'narration'      => $admin_accounts->name,
                    'debit'          => 0,
                    'credit'         => $request->admin_fee,
                    'billing_month'  => $billingMonth,
                ]);
            }

            if ($request->service_charges > 0) {
                $this->upsertTransaction2([
                    'account_id'     => $service_accounts->id,
                    'reference_id'   => $rtaFines->id,
                    'reference_type' => 'RTA',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $rtaFines->trans_date,
                    'narration'      => $service_accounts->name . ' RTA Fine',
                    'debit'          => 0,
                    'credit'         => $request->service_charges,
                    'billing_month'  => $billingMonth,
                ]);
            }
            $this->upsertTransaction3(
                [
                    'account_id'     => $rtaFines->rta_account_id,
                    'reference_id'   => $request->id,
                    'reference_type' => 'RTA',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $rtaFines->trans_date,
                    'narration'      => $request->detail ?? 'RTA Fine Received',
                    'debit'          => 0,
                    'credit'         => $rtaFines->amount,
                    'billing_month'  => $billingMonth,
                ],
                $rta_account->rta_account_id
            );

            /*
        |--------------------------------------------------------------------------
        | Voucher (update instead of insert)
        |--------------------------------------------------------------------------
        */
            $voucher = Vouchers::where('ref_id', $rtaFines->id)
                ->where('voucher_type', 'RFV')
                ->first();

            if ($voucher) {
                $voucher->update([
                    'rider_id'      => $request->rider_id,
                    'trans_date'    => $rtaFines->trans_date,
                    'trans_code'    => $trans_code,
                    'trip_date'     => $request->trip_date,
                    'billing_month' => $billingMonth,
                    'payment_type'  => 1,
                    'remarks'       => 'RTA Fine Voucher',
                    'amount'        => $rtaFines->total_amount,
                    'attach_file'   => $path,
                    'pay_account'   => $rider_account->id,
                    'Updated_By'    => auth()->id(),
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | Ledger Update
        |--------------------------------------------------------------------------
        */
            DB::table('ledger_entries')
                ->where('account_id', $rider_account->id)
                ->where('billing_month', $billingMonth)
                ->delete();

            $lastLedger = DB::table('ledger_entries')
                ->where('account_id', $rider_account->id)
                ->where('billing_month', '<', $billingMonth)
                ->orderBy('billing_month', 'desc')
                ->first();

            $opening_balance = $lastLedger ? $lastLedger->closing_balance : 0.00;
            $debit_amount    = $rtaFines->total_amount;
            $closing_balance = $opening_balance + $debit_amount;

            DB::table('ledger_entries')->insert([
                'account_id'      => $rider_account->id,
                'billing_month'   => $billingMonth,
                'opening_balance' => $opening_balance,
                'debit_balance'   => $debit_amount,
                'credit_balance'  => 0.00,
                'closing_balance' => $closing_balance,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::commit();
            Flash::success('RTA Fine updated successfully.');

            return redirect(route('rtaFines.tickets', $rtaFines->rta_account_id));
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Rider based transaction update (NO insert)
     */
    private function upsertTransaction(array $data, $rider_id = null)
    {
        $trans_account = DB::table('accounts')->where('ref_id', $rider_id)->first();

        if (!$trans_account) {
            throw new \Exception("No account found for rider_id: {$rider_id}");
        }

        // Find existing transaction by reference + type + code (NOT account_id)
        $existing = DB::table('transactions')
            ->where('reference_type', $data['reference_type'])
            ->where('reference_id', $data['reference_id'])
            ->where('trans_code', $data['trans_code'])
            ->first();

        if ($existing) {
            // Always update with new account_id (rider change bhi handle ho jayega)
            DB::table('transactions')
                ->where('id', $existing->id)
                ->update(array_merge($data, [
                    'account_id' => $trans_account->id,   // 👈 new rider ka account
                    'updated_at' => now(),
                ]));
        }
    }


    /**
     * Non-rider transaction update (Admin/Service/RTA) - NO insert
     */
    private function upsertTransaction2(array $data)
    {
        $existing = DB::table('transactions')
            ->where('reference_type', $data['reference_type'])
            ->where('reference_id', $data['reference_id'])
            ->where('account_id', $data['account_id'])
            ->first();
        if ($existing) {
            DB::table('transactions')
                ->where('id', $existing->id)
                ->update(array_merge($data, [
                    'updated_at' => now(),
                ]));
        }
    }
    private function upsertTransaction3(array $data, $rta_account_id = null)
    {
        // Find existing transaction by reference + type + code (NOT account_id)
        $existing = DB::table('transactions')
            ->where('reference_type', $data['reference_type'])
            ->where('reference_id', $data['reference_id'])
            ->where('account_id', $rta_account_id)
            ->first();
        if ($existing) {
            // Update with new account_id (RTA account change)
            DB::table('transactions')
                ->where('id', $existing->id)
                ->update(array_merge($data, [
                    'updated_at' => now(),
                ]));
        }
    }


    /**
     * Remove the specified RtaFines from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $rtaFines = $this->rtaFinesRepository->find($id);

        if (empty($rtaFines)) {
            Flash::error('Rta Fines not found');
            return redirect()->back();
        }

        // Prevent deletion of paid tickets
        if ($rtaFines->status === 'paid') {
            Flash::error('Cannot delete paid tickets. Only unpaid tickets can be deleted.');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $billingMonth = $rtaFines->billing_month;
            $riderAccountId = DB::table('accounts')->where('ref_id', $rtaFines->rider_id)->value('id');
            $ticketIdentifier = $rtaFines->ticket_no ?? "Ticket #{$id}";

            // Get related transactions before deletion for cascade tracking
            $relatedTransactions = Transactions::where('reference_id', $rtaFines->id)
                ->where('reference_type', 'RTA')
                ->where('trans_code', $rtaFines->trans_code)
                ->get();

            // Get related vouchers before deletion for cascade tracking
            $relatedVouchers = Vouchers::where('ref_id', $rtaFines->id)
                ->where('voucher_type', 'RFV')
                ->where('trans_code', $rtaFines->trans_code)
                ->get();

            // Soft delete related transactions and track cascade
            foreach ($relatedTransactions as $transaction) {
                $transaction->delete(); // Soft delete

                // Track cascade deletion
                try {
                    $cascadeRecord = $this->trackCascadeDeletion(
                        RtaFines::class,
                        $rtaFines->id,
                        $ticketIdentifier,
                        Transactions::class,
                        $transaction->id,
                        "Transaction #{$transaction->id} (Trans Code: {$transaction->trans_code})",
                        'hasMany',
                        'transactions',
                        'soft',
                        'Cascade deletion from RTA Fine ticket deletion'
                    );
                    \Log::info("Cascade deletion tracked for transaction {$transaction->id}, cascade record ID: " . ($cascadeRecord->id ?? 'N/A'));
                } catch (\Exception $e) {
                    \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
                    \Log::error("Stack trace: " . $e->getTraceAsString());
                }
            }

            // Soft delete related vouchers and track cascade
            foreach ($relatedVouchers as $voucher) {
                $voucher->delete(); // Soft delete

                // Track cascade deletion
                try {
                    $cascadeRecord = $this->trackCascadeDeletion(
                        RtaFines::class,
                        $rtaFines->id,
                        $ticketIdentifier,
                        Vouchers::class,
                        $voucher->id,
                        "Voucher #{$voucher->id} (Type: {$voucher->voucher_type})",
                        'hasMany',
                        'vouchers',
                        'soft',
                        'Cascade deletion from RTA Fine ticket deletion'
                    );
                    \Log::info("Cascade deletion tracked for voucher {$voucher->id}, cascade record ID: " . ($cascadeRecord->id ?? 'N/A'));
                } catch (\Exception $e) {
                    \Log::error("Failed to track cascade deletion for voucher {$voucher->id}: " . $e->getMessage());
                    \Log::error("Stack trace: " . $e->getTraceAsString());
                }
            }

            // Recalculate ledger entries after deletion
            if ($riderAccountId) {
                $this->recalculateLedgerAfterDeletion($riderAccountId, $billingMonth);
            }

            // Soft delete the RTA fine record
            $rtaFines->delete();

            // Track the primary RTA Fine deletion itself
            try {
                $primaryCascadeRecord = \App\Models\DeletionCascade::create([
                    'primary_model' => RtaFines::class,
                    'primary_id' => $rtaFines->id,
                    'primary_name' => $ticketIdentifier,
                    'related_model' => RtaFines::class,
                    'related_id' => $rtaFines->id,
                    'related_name' => $ticketIdentifier,
                    'relationship_type' => 'self',
                    'relationship_name' => 'rta_fine',
                    'deletion_type' => 'soft',
                    'deleted_by' => auth()->id(),
                    'deletion_reason' => 'RTA Fine ticket deleted',
                    'metadata' => [
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'timestamp' => now()->toIso8601String(),
                        'status' => $rtaFines->status,
                        'amount' => $rtaFines->amount,
                        'total_amount' => $rtaFines->total_amount,
                    ],
                ]);
                \Log::info("Primary RTA Fine deletion tracked, cascade record ID: " . ($primaryCascadeRecord->id ?? 'N/A'));
            } catch (\Exception $e) {
                \Log::error("Failed to track RTA Fine deletion: " . $e->getMessage());
                \Log::error("Stack trace: " . $e->getTraceAsString());
            }

            DB::commit();
            Flash::success('RTA Fine deleted successfully with all related records.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error deleting RTA Fine ID: {$id} - " . $e->getMessage());
            Flash::error('Error deleting RTA Fine: ' . $e->getMessage());
        }

        return redirect()->back();
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

    public function getrider($id)
    {
        $bike = Bikes::find($id);
        if (!$bike) {
            echo '<option value="">There is no rider against this bike</option>';
            return;
        }
        $currentRiderId = $bike->rider_id;
        $riders = DB::table('riders')->get();
        if ($riders->isEmpty()) {
            echo '<option value="">There is no rider</option>';
        } else {
            echo '<option value="">Select Rider</option>';
            foreach ($riders as $r) {
                echo '<option value="' . $r->id . '"'
                    . ($r->id == $currentRiderId ? ' selected' : '')
                    . '>'
                    . $r->rider_id . ' - ' . $r->name
                    . '</option>';
            }
        }
    }

    public function importForm($AccountId)
    {
        $account = Accounts::findOrFail($AccountId);
        return view('rta_fines.import', compact('account'));
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
            'account_id' => 'required|numeric',
            'admin_charge_per_rtafine' => 'nullable|numeric'
        ]);
        try {
            $AccountId = $request->account_id;
            $import = new RTAFineImport($AccountId);
            Excel::import($import, $request->file('file'));
            $importResult = $import->getResults();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Rta Fines imported successfully with vouchers created.",
                    'results' => $importResult
                ]);
            }
            Flash::success("Rta Fines imported successfully with vouchers created. Records imported: {$importResult['stats']['imported']}.");
            return redirect()->back();
        } catch (\Exception $e) {
            \Log::error('Rta Fines import failed: ' . $e->getMessage());
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
}
