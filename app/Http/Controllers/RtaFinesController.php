<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Helpers\Common;
use App\Http\Requests\CreateRtaFinesRequest;
use App\Http\Requests\UpdateRtaFinesRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Bikes;
use App\Models\Riders;
use App\Models\Banks;
use App\Models\BikeRentCompany;
use App\Models\RtaFines;
use App\Models\Accounts;
use App\Models\Vouchers;
use App\Models\VoucherType;
use App\Models\LedgerEntry;
use App\Models\Transactions;
use App\Repositories\RtaFinesRepository;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\AppliesModuleTopBarFilters;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Flash;
use DB;
use App\Imports\RTAFineImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Support\GlobalAccounts;

class RtaFinesController extends AppBaseController
{
    use AppliesModuleTopBarFilters, GlobalPagination, TracksCascadingDeletions;
    /** @var RtaFinesRepository $rtaFinesRepository*/
    private $rtaFinesRepository;

    public function __construct(RtaFinesRepository $rtaFinesRepo)
    {
        $this->rtaFinesRepository = $rtaFinesRepo;
        $this->middleware('auth');
        $this->middleware('permission:rta_fines_unpaid_view')->only('index', 'tickets', 'show');
        $this->middleware('permission:rta_fines_paid_view')->only('paid', 'show');
        $this->middleware('permission:rta_fines_unpaid_create')->only('create', 'store', 'fileUpload', 'importForm', 'import');
        $this->middleware('permission:rta_fines_paid_create')->only('payfine', 'payForm', 'fileUpload');
        $this->middleware('permission:rta_fines_unpaid_edit')->only('edit', 'update', 'fileUpload');
        $this->middleware('permission:rta_fines_unpaid_delete|rta_fines_paid_delete')->only('destroy');
    }

    /**
     * Display a listing of the RtaFines.
     */


    public function index(Request $request)
    {
        return $this->renderTicketsListing($request, 'unpaid');
    }
    public function tickets(Request $request)
    {
        return $this->renderTicketsListing($request, 'unpaid');
    }

    public function paid(Request $request)
    {
        return $this->renderTicketsListing($request, 'paid');
    }

    private function renderTicketsListing(Request $request, string $status)
    {
        $status = $status === 'paid' ? 'paid' : 'unpaid';
        $ticketsRouteName = $status === 'paid' ? 'rtaFines.paid' : 'rtaFines.tickets';
        $pageTitle = $status === 'paid' ? 'Paid RTA Fines' : 'Unpaid RTA Fines';

        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = RtaFines::query()
            ->select(
                'rta_fines.*',
                'leasing_companies.name as company'
            )
            ->leftJoin('bikes', 'bikes.id', '=', 'rta_fines.bike_id')
            ->leftJoin('leasing_companies', 'leasing_companies.id', '=', 'bikes.company')
            ->with('branch')
            ->orderBy('trip_date', 'desc');

        $query->where('rta_fines.status', $status);

        if ($request->filled('ticket_no')) {
            $query->where('rta_fines.ticket_no', 'like', '%' . $request->ticket_no . '%');
        }
        if ($request->filled('branch_id')) {
            $query->where('rta_fines.branch_id', $request->branch_id);
        }
        if ($request->filled('billing_month')) {
            $billingMonth = \Carbon\Carbon::parse($request->billing_month);
            $query->whereYear('rta_fines.billing_month', $billingMonth->year)
                ->whereMonth('rta_fines.billing_month', $billingMonth->month);
        }
        if ($request->filled('trans_code')) {
            $query->where('rta_fines.trans_code', $request->trans_code);
        }
        if ($request->filled('rider_id')) {
            $query->where('rta_fines.rider_id', $request->rider_id);
        }
        if ($request->filled('bike_id')) {
            $query->where('rta_fines.bike_id', $request->bike_id);
        }
        if ($request->filled('company_id') && $request->company_id != 'own') {
            $query->where('bikes.company', $request->company_id);
        }
        if ($request->filled('company_id') && $request->company_id == 'own') {
            $query->where('bikes.bike_owner', 'Owned');
        }
        $topBarModuleKey = $status === 'paid' ? 'rta_fines_paid' : 'rta_fines_unpaid';
        $this->applyModuleTopBarFilters($query, $request, $topBarModuleKey);
        $leasingCompanies = \App\Models\LeasingCompanies::orderBy('name')->get();
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
        $total_Amount =  $totalAmount + $serviceCharges + $adminFee;
        if ($request->ajax()) {
            $tableData = view('rta_fines.table', [
                'data' => $data,
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
                    'leasingCompanies' => $leasingCompanies,
                ]
            ]);
        }
        return view('rta_fines.index', array_merge([
            'data' => $data,
            'ticketsRouteName' => $ticketsRouteName,
            'listingStatus' => $status,
            'pageTitle' => $pageTitle,
            'paidAmount' => $paidAmount,
            'unpaidAmount' => $unpaidAmount,
            'totalAmount' => $totalAmount,
            'paidCount' => $paidCount,
            'unpaidCount' => $unpaidCount,
            'totaltickets' => $totaltickets,
            'serviceCharges' => $serviceCharges,
            'adminFee' => $adminFee,
            'total_Amount' => $total_Amount,
            'leasingCompanies' => $leasingCompanies,
        ], $this->moduleTopBarListingData($request, $topBarModuleKey)));
    }

    public function payfine(Request $request)
    {
        DB::beginTransaction();
        $path = null;
        $voucher = null;
        try {

            $fine = RtaFines::findOrFail($request->id);
            $creditAccount = Accounts::find($request->pay_account);
            if (!$creditAccount)
                throw new \Exception('Credit Account Not Found');
            if ($fine->status == 'paid') {
                throw new \Exception('Fine is Already Paid.');
            } else {
                $fine->pay_account = $request->pay_account;
                $fine->status = 'paid';
                // Determine payment type flag
                $payment_type_flag = match ($creditAccount->account_type) {
                    'Liability' => 1,
                    'Asset' => 0,
                    default => null,
                };

                // File Upload
                if ($request->file('attachment')) {
                    $photo = $request->file('attachment');
                    $path = $photo->store('fines', 'public');
                    $fine->attachment = $path;
                }
                $trans_code = Account::trans_code();
                $TransactionService = new TransactionService();

                $billingMonth = $fine->billing_month;
                $transDate = $fine->trans_date;
                $credit = 0;
                $profit = 0;
                if ($creditAccount->account_type == 'Liability')
                    $credit = $fine->total_amount;
                else {
                    $credit = $fine->total_amount - $fine->admin_fee - $fine->vat;
                    $profit = $fine->admin_fee;
                }
                // Debit RTA Account
                $TransactionService->recordTransaction([
                    'account_id'     => GlobalAccounts::id('RTA_FINE'),
                    'reference_id'   => $fine->id,
                    'reference_type' => 'RTA',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $transDate,
                    'narration'      => '(Payment) ' . $fine->detail ?? 'RTA Fine Payment',
                    'debit'          => $fine->total_amount - $fine->vat,
                    'billing_month'  => $billingMonth,
                    'branch_id'      => $fine->branch_id
                ]);

                if ($fine->vat > 0 && $creditAccount->account_type == 'Liability') {
                    $TransactionService->recordTransaction([
                        'account_id'     => GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'),
                        'reference_id'   => $fine->id,
                        'reference_type' => 'RTA',
                        'trans_code'     => $trans_code,
                        'trans_date'     => $fine->trans_date,
                        'narration'      => 'Service Charges VAT. ',
                        'debit'          => $fine->vat,
                        'billing_month'  => $billingMonth,
                        'branch_id'      => $fine->branch_id,
                    ]);
                }


                // Credit Selected Payment Account
                $TransactionService->recordTransaction([
                    'account_id'     => $creditAccount->id,
                    'reference_id'   => $fine->id,
                    'reference_type' => 'RTA',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $transDate,
                    'narration'      => '(Payment) ' . $fine->detail ?? 'RTA Fine Payment',
                    'credit'         => $credit,
                    'branch_id'      => $fine->branch_id,
                    'billing_month'  => $billingMonth,
                ]);

                if ($profit > 0) {
                    $adminAcc = Accounts::where('id', GlobalAccounts::id('RTA_ADMIN_CHARGES'))->exists();
                    if (!$adminAcc)
                        throw new \Exception('Admin Charges (RTA Fines) Account not found');
                    $TransactionService->recordTransaction([
                        'account_id'     => GlobalAccounts::id('RTA_ADMIN_CHARGES'),
                        'reference_id'   => $fine->id,
                        'reference_type' => 'RTA',
                        'trans_code'     => $trans_code,
                        'trans_date'     => $transDate,
                        'narration'      => $fine->detail ?? 'RTA Fine Payment',
                        'credit'         => $profit,
                        'branch_id'      => $fine->branch_id,
                        'billing_month'  => $billingMonth,
                    ]);
                }

                // 4. Voucher
                $voucher = Vouchers::create([
                    'rider_id'      => $fine->rider_id,
                    'trans_date'    => $transDate,
                    'trans_code'    => $trans_code,
                    'trip_date'     => $fine->trip_date,
                    'reference_number' => $fine->reference_number ?? '',
                    'billing_month' => $billingMonth,
                    'payment_type'  => $payment_type_flag,
                    'voucher_type'  => 'RFV',
                    'remarks'       => 'RTA Fine Payment Voucher',
                    'amount'        => $fine->total_amount,
                    'Created_By'    => auth()->id(),
                    'attach_file'   => $path,
                    'payment_from'  => GlobalAccounts::id('RTA_FINE'),
                    'payment_to'    => $creditAccount->id,
                    'ref_id'        => $fine->id,
                    'branch_id'     => $fine->branch_id,
                    'custom_field_values' => $request->input('voucher_custom_fields', []),
                ]);
            }
            $fine->paid_voucher_id = $voucher->id;
            $fine->save();
            DB::commit();
            if ($request->ajax()) {
                return response()->json(['message' => 'Fine Paid Successfully', 'reload' => true], 200);
            }
            Flash::success('Fine Paid Successfully');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($path)
                \Storage::delete($path);
            \Log::error('error:', [$e->getMessage(), $e->getTrace()]);
            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
            Flash::error('Error: ' . $e->getMessage());
        }
    }
    public function payForm($company_slug, $id)
    {
        $fine = RtaFines::with(['rider', 'rentalCompany', 'bike.leasingCompany'])->where('id', $id)->first();
        $debitAccount = Accounts::where('id', $fine->rta_account_id)->first();
        $ids = Banks::active()->pluck('account_id');
        $leasingId = $fine->bike->leasingCompany?->account_id ?? null;
        if ($leasingId)
            $ids[] = $leasingId;
        $creditAccounts = Accounts::wherein('id', $ids)->get();
        return view('rta_fines.viewvoucher', compact('fine', 'debitAccount', 'creditAccounts'));
    }

    /**
     * Show the form for creating a new RtaFines.
     */
    public function create()
    {
        $rtaFineAccount = Accounts::where('id', GlobalAccounts::id('RTA_FINE'))->first();
        if (!$rtaFineAccount) {
            return response()->json(['message' => 'Current Liabilities Account => RTA Fines, not found'], 500);
        }
        $bikes = Bikes::with(['leasingCompany', 'rider'])->get();
        $riders = Riders::with(['account'])->get();
        $companies = BikeRentCompany::with(['account'])->where('customer_type', 'bike_rental')->get();
        $rtaFines = null;
        return view('rta_fines.create', compact('bikes', 'riders', 'rtaFineAccount', 'companies', 'rtaFines'));
    }
    /**
     * Store a newly created RtaFines in storage.
     */
    public function store(CreateRtaFinesRequest $request)
    {
        $exists = \App\Support\CompanyQuery::table('rta_fines')->where('ticket_no', $request->ticket_no)->where('deleted_at', null)->exists();

        if ($exists) {
            return response()->json(['errors' => ['error' => 'This Ticket Number already exists.']], 422);
        }
        $path = null;
        DB::beginTransaction();

        try {
            $vat_account = GlobalAccounts::id('VAT_ON_SALES');
            $input = $request->all();
            $bike = Bikes::findOrFail($input['bike_id']);
            $trans_code = Account::trans_code();

            // Upload file
            $path = $request->file('attachment_path')->store('fines', 'public');

            // Set values
            $input['billing_month']   = $input['billing_month'] . "-01";
            $input['attachment_path'] = $path;
            $input['plate_no']        = $bike->plate;
            $input['trans_date']      = Carbon::today();
            $input['trans_code']      = $trans_code;
            $input['status']          = 'unpaid';
            $input['branch_id']       = $bike->branch_id;

            if (!empty($input['rider_id']) && !empty($input['rental_company_id'])) {
                throw new \Exception('Either Select a Rider or Rental Company. Cannot Charge Both');
            }

            if (empty($input['rider_id']) && empty($input['rental_company_id'])) {
                throw new \Exception('Either Select a Rider or Rental Company.');
            }

            // Create RTA Fine
            $rtaFines = RtaFines::create($input);


            $TransactionService = new TransactionService();
            $billingMonth = $rtaFines->billing_month;

            $rider_account = $rtaFines->rider_id ? $rtaFines->rider->account_id : ($rtaFines->rental_company_id ? $rtaFines->rentalCompany->account_id : null);
            $rta_account = GlobalAccounts::id('RTA_FINE');
            if (!$rider_account)
                throw new \Exception('Debit Account Not Found');

            // --- 1. Main Fine (Rider Debit) ---
            $TransactionService->recordTransaction([
                'account_id'     => $rider_account,
                'reference_id'   => $rtaFines->id,
                'reference_type' => 'RTA FINE',
                'trans_code'     => $trans_code,
                'trans_date'     => $rtaFines->trans_date,
                'narration'      => $rtaFines->detail ?? 'RTA Fine for Bike: ' . $rtaFines->plate_no,
                'debit'          => $rtaFines->total_amount,
                'billing_month'  => $billingMonth,
                'branch_id'      => $bike->branch_id,
            ]);

            $TransactionService->recordTransaction([
                'account_id'     => $rta_account,
                'reference_id'   => $rtaFines->id,
                'reference_type' => 'RTA FINE',
                'trans_code'     => $trans_code,
                'trans_date'     => $rtaFines->trans_date,
                'narration'      => $rtaFines->detail ?? 'RTA Fine for Bike: ' . $rtaFines->plate_no,
                'credit'         => $rtaFines->total_amount - $rtaFines->vat,
                'branch_id'      => $bike->branch_id,
                'billing_month'  => $billingMonth,
            ]);

            if ($rtaFines->vat > 0) {
                $TransactionService->recordTransaction([
                    'account_id'     => $vat_account,
                    'reference_id'   => $rtaFines->id,
                    'reference_type' => 'RTA FINE',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $rtaFines->trans_date,
                    'narration'      => 'Service Charges Vat. ',
                    'credit'         => $rtaFines->vat,
                    'billing_month'  => $billingMonth,
                    'branch_id'      => $bike->branch_id,
                ]);
            }

            DB::commit();
            if ($request->ajax()) {
                return response()->json(['message' => 'RTA Fine added successfully', 'reload' => true], 200);
            }
            Flash::success('RTA Fine added successfully with all charges and ledger.');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            if ($path) {
                \Storage::delete($path);
            }
            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back();
        }
    }


    public function fileUpload(Request $request, $company_slug, $id)
    {
        $fine = RtaFines::find($id);
        if (!$fine) {
            return response()->json(['message' => 'Fine Not Found'], 500);
        }
        if ($request->isMethod('POST')) {
            if ($request->hasFile('attachment_path')) {
                $old = $fine->attachment_path;
                $photo = $request->file('attachment_path');
                $path = $photo->store('fines', 'public');
                $fine->attachment_path = $path;
                \Storage::delete($old);
            }
            if ($request->hasFile('attachment')) {
                $old = $fine->attachment;
                $photo = $request->file('attachment');
                $path = $photo->store('fines', 'public');
                $fine->attachment = $path;
                \Storage::delete($old);
            }
            $fine->save();
            return response()->json(['message' => 'File Uploaded Successfully', 'reload' => true], 200);
        } else {
            return view('rta_fines.attach_file', compact('id', 'fine'));
        }
    }

    /**
     * Display the specified RtaFines.
     */
    public function show($company_slug, $id)
    {
        $rtaFine = $this->rtaFinesRepository->find($id);

        if (empty($rtaFine)) {
            Flash::error('Rta Fine not found');

            return redirect()->back();
        }

        $rtaFine->load(['transactions.account']);

        return view('rta_fines.show', compact('rtaFine'));
    }

    /**
     * Show the form for editing the specified RtaFines.
     */
    public function edit(Request $request, $company_slug, $id)
    {

        $rtaFines = $this->rtaFinesRepository->find($id);
        $bikes = Bikes::with(['leasingCompany', 'rider'])->get();
        $riders = Riders::with(['account'])->get();
        $companies = BikeRentCompany::with(['account'])->where('customer_type', 'bike_rental')->get();
        $rtaFineAccount = Accounts::where('id', GlobalAccounts::id('RTA_FINE'))->first();
        if (empty($rtaFines)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'RTA Fine Not Found'], 500);
            }
            Flash::error('Rta Fine not found');

            return redirect()->back();
        }

        return view('rta_fines.edit', compact('bikes', 'rtaFines', 'riders', 'rtaFineAccount', 'companies'));
    }

    /**
     * Update the specified RtaFines in storage.
     */
    public function update(Request $request, $company_slug, $id)
    {
        // Check if same ticket_no exists on any other record
        $exists = \App\Support\CompanyQuery::table('rta_fines')
            ->where('ticket_no', $request->ticket_no)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json(['errors' => ['error' => 'This Ticket No is already used in another fine.']], 422);
        }
        $rtaFines = RtaFines::findOrFail($id);
        $vat_account = GlobalAccounts::id('VAT_ON_SALES');
        $rta_account = GlobalAccounts::id('RTA_FINE');
        $path = $rtaFines->attachment_path;
        $newPath = null;
        DB::beginTransaction();

        try {
            $input = $request->all();
            $bike  = Bikes::findOrFail($input['bike_id']);
            $rider = \App\Support\CompanyQuery::table('riders')->where('id', $rtaFines->rider_id)->first();

            // Upload new file if provided
            if ($request->hasFile('attachment')) {
                $newPath = $request->file('attachment')->store('fines', 'public');
                $input['attachment_path'] = $newPath;
            }

            // Update fields
            $input['billing_month']   = $input['billing_month'] . '-01';
            $input['plate_no']        = $bike->plate;
            $input['trans_date']      = Carbon::today()->format('Y-m-d');
            $input['branch_id']       = $bike->branch_id;

            $rtaFines->update($input);
            $trans_code    = $rtaFines->trans_code;
            $billingMonth  = $rtaFines->billing_month;
            /*
            |--------------------------------------------------------------------------
            | Transactions (update only)
            |--------------------------------------------------------------------------
            */
            $rider_account = $rtaFines->rider_id ? $rtaFines->rider->account_id : ($rtaFines->rental_company_id ? $rtaFines->rentalCompany->account_id : null);
            if (!$rider_account)
                throw new \Exception('Debit Account Not Found');

            Transactions::where('trans_code', $trans_code)->delete();
            $TransactionService = new TransactionService();
            // --- 1. Main Fine (Rider Debit) ---
            $TransactionService->recordTransaction([
                'account_id'     => $rider_account,
                'reference_id'   => $rtaFines->id,
                'reference_type' => 'RTA FINE',
                'trans_code'     => $trans_code,
                'trans_date'     => $rtaFines->trans_date,
                'narration'      => $rtaFines->detail ?? 'RTA Fine for Bike: ' . $rtaFines->plate_no,
                'debit'          => $rtaFines->total_amount,
                'billing_month'  => $billingMonth,
                'branch_id'      => $bike->branch_id,
            ]);

            $TransactionService->recordTransaction([
                'account_id'     => $rta_account,
                'reference_id'   => $rtaFines->id,
                'reference_type' => 'RTA FINE',
                'trans_code'     => $trans_code,
                'trans_date'     => $rtaFines->trans_date,
                'narration'      => $rtaFines->detail ?? 'RTA Fine for Bike: ' . $rtaFines->plate_no,
                'credit'         => $rtaFines->total_amount - $rtaFines->vat,
                'branch_id'      => $bike->branch_id,
                'billing_month'  => $billingMonth,
            ]);

            if ($rtaFines->vat > 0) {
                $TransactionService->recordTransaction([
                    'account_id'     => $vat_account,
                    'reference_id'   => $rtaFines->id,
                    'reference_type' => 'RTA FINE',
                    'trans_code'     => $trans_code,
                    'trans_date'     => $rtaFines->trans_date,
                    'narration'      => 'Service Charges Vat. ',
                    'credit'         => $rtaFines->vat,
                    'billing_month'  => $billingMonth,
                    'branch_id'      => $bike->branch_id,
                ]);
            }

            DB::commit();
            \Storage::delete($path);
            if ($request->ajax()) {
                return response()->json(['message' => 'RTA Fine updated successfully.', 'reload' => true], 200);
            }
            Flash::success('RTA Fine updated successfully.');

            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($newPath) {
                \Storage::delete($newPath);
            }
            report($e);
            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }


    /**
     * Remove the specified RtaFines from storage.
     *
     * @throws \Exception
     */
    public function destroy(Request $request, $company_slug, $id)
    {
        $rtaFines = $this->rtaFinesRepository->find($id);

        if (empty($rtaFines)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Fine Not Found'], 500);
            }
            Flash::error('Rta Fines not found');
            return redirect()->back();
        }
        DB::beginTransaction();
        if ($rtaFines->status == 'paid') {
            try {
                $rtaFines->load('paidVoucher');
                $rtaFines->paidVoucher->transactions()->delete();
                $rtaFines->paidVoucher()->delete();
                $path = $rtaFines->attachment;
                $rtaFines->update([
                    'status' => 'unpaid',
                    'paid_voucher_id' => null,
                    'attachment' => null,
                    'pay_account' => null,
                ]);
                DB::commit();
                \Log::info('fine', $rtaFines->toArray());
                if ($path) {
                    \Storage::delete($path);
                }
                if ($request->ajax()) {
                    return response()->json(['message' => 'Fine Payment Deleted Successfully.', 'reload' => true], 200);
                }
                Flash::success('Fine Payment Deleted Successfully');
                return redirect()->back();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => $e->getMessage()], 500);
            }
        } else {
            try {
                $rtaFines->load(['transactions']);
                $billingMonth = $rtaFines->billing_month;
                $ticketIdentifier = $rtaFines->ticket_no;
                $path = $rtaFines->attachment_path;

                // Get related transactions before deletion for cascade tracking
                $relatedTransactions = $rtaFines->transactions;

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
                    foreach ($relatedTransactions as $transaction) {
                        \App\Models\DeletionCascade::create([
                            'primary_model' => RtaFines::class,
                            'primary_id' => $rtaFines->id,
                            'primary_name' => $ticketIdentifier,
                            'related_model' => Transactions::class,
                            'related_id' => $transaction->id,
                            'related_name' => $transaction->trans_code,
                            'relationship_type' => 'hasMany',
                            'relationship_name' => 'transactions',
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
                        $transaction->delete();
                        \Log::info("Transaction #{$transaction->id} (Trans Code: {$transaction->trans_code}) deleted");
                        \Log::info("Cascade deletion tracked for transaction {$transaction->id}, cascade record ID: " . ($cascadeRecord->id ?? 'N/A'));
                    }
                    // Soft delete the RTA fine record
                    $rtaFines->delete();
                    \Log::info("Primary RTA Fine deletion tracked, cascade record ID: " . ($primaryCascadeRecord->id ?? 'N/A'));
                } catch (\Exception $e) {
                    \Log::error("Failed to track RTA Fine deletion: " . $e->getMessage());
                    \Log::error("Stack trace: " . $e->getTraceAsString());
                }

                DB::commit();
                if ($path)
                    \Storage::delete($path);
                if ($request->ajax()) {
                    return response()->json(['message' => 'Fine Deleted Successfully', 'reload' => true], 200);
                }
                Flash::success('RTA Fine deleted successfully with all related records.');
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Error deleting RTA Fine ID: {$id} - " . $e->getMessage());
                if ($request->ajax()) {
                    return response()->json(['message' => 'Error occured while deleting fine. ' . $e->getMessage()], 500);
                }
                Flash::error('Error deleting RTA Fine: ' . $e->getMessage());
            }
        }

        return redirect()->back();
    }

    public function getrider($company_slug, $id)
    {
        $bike = Bikes::find($id);
        if (!$bike) {
            return response()->json([
                'success' => false,
                'message' => 'Bike not found',
            ]);
        }
        $tripDate = request()->input('trip_date') ?? date('1970-01-01');
        $history = $bike->history()
            ->where('note_date', '<=', $tripDate)
            ->where(function ($query) use ($tripDate) {
                $query->where('return_date', '>=', $tripDate)
                    ->orWhereNull('return_date');
            })
            ->first();
        $currentRiderId = $bike->rider_id;
        $currentCompanyId = $bike->rental_company_id;

        // Get rider IDs from history
        $riderIds = $bike->history()->whereNotNull('rider_id')->pluck('rider_id')->toArray();
        // Get company IDs from history
        $companyIds = $bike->history()->whereNotNull('rental_company_id')->pluck('rental_company_id')->toArray();

        // Fetch riders and companies
        $riders = \App\Support\CompanyQuery::table('riders')->whereIn('id', $riderIds)->get();
        $companies = \App\Support\CompanyQuery::table('bike_rent_companies')
            ->where('customer_type', 'bike_rental')
            ->whereIn('id', $companyIds)
            ->get();

        // Build riders dropdown HTML
        $ridersHtml = '';
        if ($riders->isEmpty()) {
            $ridersHtml = false;
        } else {
            $ridersHtml = '<option value="">Select Rider</option>';
            foreach ($riders as $r) {
                $ridersHtml .= '<option value="' . $r->id . '"'
                    . ($r->id == ($history?->rider_id ?? $currentRiderId ?? 0) ? ' selected' : '')
                    . '>' . $r->rider_id . ' - ' . $r->name . '</option>';
            }
        }

        // Build companies dropdown HTML
        $companiesHtml = '';
        if ($companies->isEmpty()) {
            $companiesHtml = false;
        } else {
            $companiesHtml = '<option value="">Select Company</option>';
            foreach ($companies as $c) {
                $companiesHtml .= '<option value="' . $c->id . '"'
                    . ($c->id == ($history?->rental_company_id ?? $currentCompanyId ?? 0) ? ' selected' : '')
                    . '>' . $c->name . '</option>';
            }
        }

        // Return JSON response with both dropdowns
        return response()->json([
            'success' => true,
            'riders' => $ridersHtml,
            'companies' => $companiesHtml
        ]);
    }

    public function importForm()
    {
        $selectedAccountId = request('rta_account_id')
            ?? session('rta_selected_account_id')
            ?? Accounts::query()->where('parent_id', 1235)->orderBy('id', 'asc')->value('id');
        $account = Accounts::findOrFail($selectedAccountId);
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
