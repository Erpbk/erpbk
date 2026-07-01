<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use App\Exceptions\GlobalAccountNotConfiguredException;
use App\Helpers\Account;
use App\Helpers\Common;
use App\Http\Controllers\AppBaseController;
use App\Models\Bikes;
use App\Models\Riders;
use App\Models\RtaFines;
use App\Models\BikeRentCompany;
use App\Models\LeasingCompanies;
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
use App\Support\GlobalAccounts;

class SalikController extends AppBaseController
{
    use GlobalPagination, TracksCascadingDeletions;
    /** @var SalikRepository $salikRepository*/
    private $salikRepository;

    public function __construct(SalikRepository $salikRepo)
    {
        $this->salikRepository = $salikRepo;
    }

    public function index(Request $request)
    {
        return $this->renderSalikListing($request);
    }

    public function tickets(Request $request, $company_slug, $id = null)
    {
        return $this->renderSalikListing($request);
    }

    private function renderSalikListing(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('salik_view')) {
            abort(403, 'Unauthorized action.');
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = salik::query()
            ->with(['branch', 'bike.leasingCompany', 'rider', 'rentalCompany'])
            ->orderBy('billing_month', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('transaction_id')) {
            $query->where('transaction_id', 'like', '%' . $request->transaction_id . '%');
        }
        if ($request->filled('billing_month')) {
            $billingMonth = Carbon::parse($request->billing_month);
            $monthName = $billingMonth->format('M');
            $yearShort = $billingMonth->format('y');
            $query->where(function ($q) use ($monthName, $yearShort, $billingMonth) {
                $q->where('billing_month', 'like', "{$monthName}-{$yearShort}%")
                    ->orWhere('billing_month', 'like', "{$billingMonth->format('Y-m')}%")
                    ->orWhereYear('billing_month', $billingMonth->year)
                    ->whereMonth('billing_month', $billingMonth->month);
            });
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('trip_date')) {
            $tripDate = Carbon::parse($request->trip_date);
            $tripDateFormatted = $tripDate->format('d M Y');
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
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paidAmount   = (clone $query)->where('status', 'paid')->sum('total_amount');
        $unpaidAmount = (clone $query)->where('status', 'unpaid')->sum('total_amount');
        $paidCount    = (clone $query)->where('status', 'paid')->count();
        $unpaidCount  = (clone $query)->where('status', 'unpaid')->count();

        $data = $this->applyPagination($query, $paginationParams);

        if ($request->ajax()) {
            $tableData = view('salik.table', ['data' => $data])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'totals' => [
                    'paidAmount'   => 'AED ' . number_format($paidAmount, 2),
                    'unpaidAmount' => 'AED ' . number_format($unpaidAmount, 2),
                    'paidCount'    => $paidCount,
                    'unpaidCount'  => $unpaidCount,
                ],
            ]);
        }

        return view('salik.index', [
            'data' => $data,
            'paidAmount' => $paidAmount,
            'unpaidAmount' => $unpaidAmount,
            'paidCount' => $paidCount,
            'unpaidCount' => $unpaidCount,
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create($company_slug, $id = null)
    {
        try {
            $this->requireSalikVoucherHeadAccounts(0, 0);
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            Flash::error($e->getMessage());
            return redirect()->route('salik.index');
        }

        $salikPayableAccount = Accounts::find(GlobalAccounts::id('SALIK_PAYABLE_ACCOUNT'));
        $bikes = Bikes::with(['leasingCompany', 'rider'])->get();
        $companies = BikeRentCompany::with(['account'])->where('customer_type', 'bike_rental')->get();
        $salik = null;
        return view('salik.create', compact('bikes', 'companies', 'salikPayableAccount', 'salik'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $exists = salik::where('transaction_id', $request->transaction_id)->exists();
        if ($exists) {
            return response()->json(['errors' => ['error' => 'This Transaction ID already exists.']], 422);
        }

        \DB::beginTransaction();
        try {
            $input = $request->all();
            $bike = Bikes::findOrFail($input['bike_id']);

            if (!empty($input['rider_id']) && !empty($input['rental_company_id'])) {
                throw new \Exception('Either select a Rider or Rental Company. Cannot charge both.');
            }
            if (empty($input['rider_id']) && empty($input['rental_company_id'])) {
                throw new \Exception('Either select a Rider or Rental Company.');
            }

            if (!empty($input['rental_company_id'])) {
                $input['rider_id'] = null;
            } else {
                $input['rental_company_id'] = null;
            }

            $amount = (float) ($request->amount ?? 0);
            $adminCharges = (float) ($request->admin_fee ?? $request->admin_charges ?? 0);
            $salikVatAmount = (float) ($request->salik_vat_amount ?? 0);
            $adminVatAmount = (float) ($request->admin_vat_amount ?? 0);
            $totalVat = $salikVatAmount + $adminVatAmount;
            $totalAmount = $amount + $adminCharges + $totalVat;

            $this->requireSalikVoucherHeadAccounts($totalVat, $adminCharges);

            $input['billing_month'] = $input['billing_month'] . '-01';
            $input['bike_id'] = $bike->id;
            $input['plate'] = $bike->plate;
            $input['trans_date'] = Carbon::today();
            $input['admin_charges'] = $adminCharges;
            $input['salik_vat'] = $request->salik_vat ?? 0;
            $input['salik_vat_amount'] = $salikVatAmount;
            $input['admin_vat'] = $request->admin_vat ?? 0;
            $input['admin_vat_amount'] = $adminVatAmount;
            $input['vat'] = $totalVat;
            $input['total_amount'] = $totalAmount;
            $input['status'] = 'unpaid';
            $input['created_by'] = Auth::user()->id;
            $input['branch_id'] = $bike->branch_id;

            $salik = $this->salikRepository->create($input);

            $this->syncMonthlyInvoiceTransactions(
                $salik->rider_id,
                $salik->billing_month,
                $salik->rental_company_id
            );

            \DB::commit();
            if ($request->ajax()) {
                return response()->json(['message' => 'Salik entry created successfully.', 'reload' => true], 200);
            }
            return redirect()->route('salik.index')->with('success', 'Salik entry created successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['errors' => ['error' => $e->getMessage()]], 500);
        }
    }

    public function monthlySummary(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('salik_view')) {
            abort(403, 'Unauthorized action.');
        }

        $query = salik::query()
            ->select(
                'inv_id',
                DB::raw('MAX(rider_id) as rider_id'),
                DB::raw('MAX(rental_company_id) as rental_company_id'),
                DB::raw('MIN(billing_month) as billing_month'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(admin_charges) as total_admin_charges'),
                DB::raw('SUM(vat) as total_vat'),
                DB::raw('SUM(total_amount) as total_grand')
            )
            ->whereNotNull('inv_id')
            ->where(function ($q) {
                $q->whereNotNull('rider_id')->orWhereNotNull('rental_company_id');
            })
            ->groupBy('inv_id')
            ->orderBy(DB::raw('MIN(billing_month)'), 'desc');

        if ($request->filled('billing_month')) {
            salik::applyBillingMonthFilter(
                $query,
                Carbon::parse($request->billing_month . '-01')->format('Y-m-01')
            );
        }

        $summaries = $query->get();
        $summaries->load(['rider', 'rentalCompany']);

        return view('salik.monthly_summary', compact('summaries'));
    }

    public function showMonthlyInvoice($company_slug, $rider_id, $billing_month)
    {
        $billingMonthNorm = salik::normalizeBillingMonth($billing_month . '-01');
        $salik = salik::where('rider_id', $rider_id)
            ->whereNull('rental_company_id')
            ->whereNotNull('inv_id')
            ->where(function ($query) use ($billingMonthNorm) {
                salik::applyBillingMonthFilter($query, $billingMonthNorm);
            })
            ->first();

        return $this->renderMonthlyInvoice($salik);
    }

    public function showCompanyMonthlyInvoice($company_slug, $rental_company_id, $billing_month)
    {
        $billingMonthNorm = salik::normalizeBillingMonth($billing_month . '-01');
        $salik = salik::where('rental_company_id', $rental_company_id)
            ->whereNull('rider_id')
            ->whereNotNull('inv_id')
            ->where(function ($query) use ($billingMonthNorm) {
                salik::applyBillingMonthFilter($query, $billingMonthNorm);
            })
            ->first();

        return $this->renderMonthlyInvoice($salik);
    }

    private function renderMonthlyInvoice(?salik $salik)
    {
        if (!$salik) {
            Flash::error('Salik invoice not found');
            return redirect(route('salik.summary'));
        }

        $summary = $salik->getMonthlySummary();

        return view('salik.invoice_show', compact('summary'));
    }

    public function syncMonthlyInvoiceTransactions(?int $riderId, $billingMonth, ?int $rentalCompanyId = null): void
    {
        $billingMonthNorm = salik::normalizeBillingMonth($billingMonth);
        if (!$billingMonthNorm || (!$riderId && !$rentalCompanyId)) {
            return;
        }

        $query = salik::query();
        if ($riderId) {
            $query->where('rider_id', $riderId);
        } else {
            $query->where('rental_company_id', $rentalCompanyId)->whereNull('rider_id');
        }
        salik::applyBillingMonthFilter($query, $billingMonthNorm);

        $saliks = $query->get();
        $this->purgeSalikInvoiceLedger($saliks);

        if ($saliks->isEmpty()) {
            $this->purgeSalikInvoiceLedgerByRiderMonth($riderId, $billingMonthNorm, $rentalCompanyId);
            return;
        }

        $invId = $riderId
            ? salik::getOrCreateInvId($riderId, $billingMonthNorm)
            : salik::getOrCreateInvIdForRentalCompany($rentalCompanyId, $billingMonthNorm);

        salik::whereIn('id', $saliks->pluck('id'))->update(['inv_id' => $invId]);

        $firstSalik = $saliks->first()->fresh();
        $totalAmount = $saliks->sum('amount');
        $totalAdmin = $saliks->sum('admin_charges');
        $totalVat = $saliks->sum('vat');
        $grandTotal = $saliks->sum('total_amount');
        $count = $saliks->count();

        $this->requireSalikVoucherHeadAccounts($totalVat, $totalAdmin);

        $debitAccountId = $this->resolveSalikDebitAccountId($firstSalik);
        $payableAccountId = GlobalAccounts::id('SALIK_PAYABLE_ACCOUNT');
        $transCode = Account::trans_code();
        $transDate = now();
        $billingMonthDisplay = Carbon::parse($billingMonthNorm)->format('M Y');
        $transactionService = new TransactionService();

        $transactionService->recordTransaction([
            'account_id'     => $debitAccountId,
            'reference_id'   => $firstSalik->id,
            'reference_type' => 'salik',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => "Salik charges for {$billingMonthDisplay} ({$count} trips) - {$invId}",
            'debit'          => $grandTotal,
            'billing_month'  => $billingMonthNorm,
            'branch_id'      => $firstSalik->branch_id,
        ]);

        $transactionService->recordTransaction([
            'account_id'     => $payableAccountId,
            'reference_id'   => $firstSalik->id,
            'reference_type' => 'salik',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => "Salik payable for {$billingMonthDisplay} ({$count} trips) - {$invId}",
            'credit'         => $totalAmount + $totalAdmin,
            'billing_month'  => $billingMonthNorm,
            'branch_id'      => $firstSalik->branch_id,
        ]);

        if ($totalVat > 0) {
            $transactionService->recordTransaction([
                'account_id'     => GlobalAccounts::id('VAT_ON_SALES'),
                'reference_id'   => $firstSalik->id,
                'reference_type' => 'salik',
                'trans_code'     => $transCode,
                'trans_date'     => $transDate,
                'narration'      => "Salik VAT for {$billingMonthDisplay} ({$count} trips) - {$invId}",
                'credit'         => $totalVat,
                'billing_month'  => $billingMonthNorm,
                'branch_id'      => $firstSalik->branch_id,
            ]);
        }

        salik::whereIn('id', $saliks->pluck('id'))->update(['trans_code' => $transCode]);
        $this->updateAccountBalances($transCode, $billingMonthNorm);
    }

    private function purgeSalikInvoiceLedger($saliks): void
    {
        $salikIds = $saliks->pluck('id')->filter()->values();
        $transCodes = $saliks->pluck('trans_code')->filter()->unique()->values();

        if ($salikIds->isNotEmpty()) {
            Transactions::whereIn('reference_id', $salikIds)
                ->whereIn('reference_type', ['Salik Voucher', 'salik', 'Salik'])
                ->delete();

            Vouchers::where('voucher_type', 'SV')
                ->whereIn('ref_id', $salikIds)
                ->delete();
        }

        if ($transCodes->isNotEmpty()) {
            Transactions::whereIn('trans_code', $transCodes)
                ->whereIn('reference_type', ['Salik Voucher', 'salik', 'Salik'])
                ->delete();

            Vouchers::where('voucher_type', 'SV')
                ->whereIn('trans_code', $transCodes)
                ->delete();
        }
    }

    private function purgeSalikInvoiceLedgerByRiderMonth(?int $riderId, string $billingMonthNorm, ?int $rentalCompanyId = null): void
    {
        $accountId = null;
        if ($riderId) {
            $rider = Riders::find($riderId);
            $accountId = $rider?->account_id;
        } elseif ($rentalCompanyId) {
            $company = BikeRentCompany::find($rentalCompanyId);
            $accountId = $company?->account_id;
        }

        if (!$accountId) {
            return;
        }

        $transCodes = Transactions::where('account_id', $accountId)
            ->where('billing_month', $billingMonthNorm)
            ->whereIn('reference_type', ['salik', 'Salik Voucher', 'Salik'])
            ->pluck('trans_code')
            ->unique()
            ->filter();

        if ($transCodes->isEmpty()) {
            return;
        }

        Transactions::whereIn('trans_code', $transCodes)->delete();
        Vouchers::where('voucher_type', 'SV')->whereIn('trans_code', $transCodes)->delete();
        $this->updateAccountBalances($transCodes->first(), $billingMonthNorm);
    }

    private function resolveSalikDebitAccountId(salik $salik): int
    {
        if ($salik->rider_id) {
            $rider = Riders::find($salik->rider_id);
            if ($rider && $rider->account_id) {
                return (int) $rider->account_id;
            }
            throw new \Exception('Rider account not found.');
        }

        if ($salik->rental_company_id) {
            $company = BikeRentCompany::find($salik->rental_company_id);
            if ($company && $company->account_id) {
                return (int) $company->account_id;
            }
            throw new \Exception('Rental company account not found.');
        }

        throw new \Exception('Debit account not found.');
    }

    /**
     * @param int[] $accountIds
     * @throws \Exception
     */
    private function requireHeadAccountsExist(array $accountIds, array $labels): void
    {
        foreach (array_unique(array_filter($accountIds)) as $accountId) {
            if (! Accounts::where('id', $accountId)->exists()) {
                $name = $labels[$accountId] ?? 'Account';
                throw new GlobalAccountNotConfiguredException($name);
            }
        }
    }

    private function requireSalikVoucherHeadAccounts(float $vatAmount = 0, float $adminAmount = 0): void
    {
        $accountIds = [GlobalAccounts::id('SALIK_PAYABLE_ACCOUNT')];
        if ($vatAmount > 0) {
            $accountIds[] = GlobalAccounts::id('VAT_ON_SALES');
        }
        if ($adminAmount > 0) {
            $accountIds[] = GlobalAccounts::id('SALIK_ADMIN_CHARGES');
        }
        $this->requireHeadAccountsExist($accountIds, GlobalAccounts::salikVoucherAccountLabels());
    }

    private function requireSalikPaymentHeadAccounts(float $vatDebit, float $ownSalikAmount, float $ownAdminAmount): void
    {
        $accountIds = [GlobalAccounts::id('SALIK_PAYABLE_ACCOUNT')];
        if ($vatDebit > 0) {
            $accountIds[] = GlobalAccounts::id('VAT_PURCHASE_ACCOUNT');
        }
        if ($ownSalikAmount > 0) {
            $accountIds[] = GlobalAccounts::id('SALIK_ASSET_ACCOUNT');
        }
        if ($ownAdminAmount > 0) {
            $accountIds[] = GlobalAccounts::id('SALIK_ADMIN_CHARGES');
        }
        $this->requireHeadAccountsExist($accountIds, GlobalAccounts::salikPaymentAccountLabels());
    }

    private function salikPaymentNarration(?string $billingMonth, string $partyLabel): string
    {
        $monthValue = $billingMonth;
        if ($monthValue && strlen($monthValue) === 7) {
            $monthValue .= '-01';
        }

        $monthLabel = $monthValue
            ? Carbon::parse($monthValue)->format('M Y')
            : Carbon::now()->format('M Y');

        return 'Salik Charges for (' . $monthLabel . ') ' . $partyLabel;
    }

    private function resolveSalikPaymentPartyLabel(?string $leasingCompanyFilter, $saliks): string
    {
        if ($leasingCompanyFilter === 'own') {
            return 'own vehicles';
        }

        if ($leasingCompanyFilter && $leasingCompanyFilter !== 'own') {
            $company = LeasingCompanies::find($leasingCompanyFilter);
            if ($company) {
                return $company->name;
            }
        }

        $leasingNames = [];
        $hasLeased = false;
        $hasOwn = false;

        foreach ($saliks as $salik) {
            $leasingCompany = $salik->bike?->leasingCompany;
            if ($leasingCompany && $leasingCompany->account_id) {
                $hasLeased = true;
                $leasingNames[$leasingCompany->name] = true;
            } else {
                $hasOwn = true;
            }
        }

        if ($hasLeased && ! $hasOwn && count($leasingNames) === 1) {
            return array_key_first($leasingNames);
        }

        if ($hasOwn && ! $hasLeased) {
            return 'own vehicles';
        }

        return 'own vehicles';
    }

    private function inferSalikPaymentLeasingFilter($saliks): ?string
    {
        $hasLeased = false;
        $hasOwn = false;
        $leasingId = null;

        foreach ($saliks as $salik) {
            $leasingCompany = $salik->bike?->leasingCompany;
            if ($leasingCompany?->account_id) {
                $hasLeased = true;
                $leasingId = (string) $leasingCompany->id;
            } else {
                $hasOwn = true;
            }
        }

        if ($hasOwn && ! $hasLeased) {
            return 'own';
        }

        if ($hasLeased && ! $hasOwn && $leasingId) {
            return $leasingId;
        }

        return null;
    }

    /**
     * Display the specified resource.
     */
    public function fileUpload(Request $request, $company_slug, $id)
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
    public function show($company_slug, $id)
    {
        $salik = salik::with('branch')->find($id);
        if (empty($salik)) {
            Flash::error('Salik not found');

            return redirect(route('salik.index'));
        }

        return view('salik.show')->with('salik', $salik);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($company_slug, $id)
    {
        $salik = salik::find($id);
        if (empty($salik)) {
            Flash::error('Salik not found');
            return redirect(route('salik.index'));
        }

        try {
            $this->requireSalikVoucherHeadAccounts(0, 0);
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            Flash::error($e->getMessage());
            return redirect()->route('salik.index');
        }

        $salikPayableAccount = Accounts::find(GlobalAccounts::id('SALIK_PAYABLE_ACCOUNT'));
        $bikes = Bikes::with(['leasingCompany', 'rider'])->get();
        $companies = BikeRentCompany::with(['account'])->where('customer_type', 'bike_rental')->get();
        return view('salik.edit', compact('salikPayableAccount', 'salik', 'bikes', 'companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $company_slug, $id)
    {
        if (!$id) {
            return response()->json(['error' => 'No ID received from route. Check your route definition.'], 400);
        }

        $validated = $request->validate([
            'transaction_id' => 'nullable|string|max:255',
            'trip_date' => 'required|string|max:255',
            'trip_time' => 'required|string|max:255',
            'transaction_post_date' => 'nullable|string|max:255',
            'billing_month' => 'required|date_format:Y-m',
            'bike_id' => 'required|exists:bikes,id',
            'rider_id' => 'nullable|exists:riders,id',
            'rental_company_id' => 'nullable|exists:bike_rent_companies,id',
            'toll_gate' => 'nullable|string|max:255',
            'direction' => 'nullable|string|max:255',
            'tag_number' => 'nullable|string|max:255',
            'amount' => 'required|numeric',
            'admin_fee' => 'nullable|numeric|min:0',
            'salik_vat' => 'nullable|numeric|min:0',
            'salik_vat_amount' => 'nullable|numeric|min:0',
            'admin_vat' => 'nullable|numeric|min:0',
            'admin_vat_amount' => 'nullable|numeric|min:0',
            'details' => 'nullable|string|max:5000',
        ]);

        if (!empty($validated['rider_id']) && !empty($validated['rental_company_id'])) {
            return response()->json(['errors' => ['error' => 'Either select a Rider or Rental Company. Cannot charge both.']], 422);
        }
        if (empty($validated['rider_id']) && empty($validated['rental_company_id'])) {
            return response()->json(['errors' => ['error' => 'Either select a Rider or Rental Company.']], 422);
        }

        if (!empty($validated['rental_company_id'])) {
            $validated['rider_id'] = null;
        } else {
            $validated['rental_company_id'] = null;
        }

        DB::beginTransaction();
        try {
            $salik = salik::findOrFail($id);
            $bike = Bikes::findOrFail($validated['bike_id']);

            $amount = (float) $validated['amount'];
            $adminCharges = (float) ($request->admin_fee ?? $request->admin_charges ?? $salik->admin_charges ?? 0);
            $salikVatPercent = (float) ($validated['salik_vat'] ?? 0);
            $adminVatPercent = (float) ($validated['admin_vat'] ?? 0);
            $salikVatAmount = (float) ($validated['salik_vat_amount'] ?? round($amount * $salikVatPercent / 100, 2));
            $adminVatAmount = (float) ($validated['admin_vat_amount'] ?? round($adminCharges * $adminVatPercent / 100, 2));
            $totalVat = $salikVatAmount + $adminVatAmount;
            $totalAmount = $amount + $adminCharges + $totalVat;

            $this->requireSalikVoucherHeadAccounts($totalVat, $adminCharges);

            $oldRiderId = $salik->rider_id;
            $oldRentalCompanyId = $salik->rental_company_id;
            $oldBillingMonth = $salik->billing_month;

            $validated['billing_month'] = $validated['billing_month'] . '-01';
            $validated['bike_id'] = $bike->id;
            $validated['plate'] = $bike->plate;
            $validated['branch_id'] = $bike->branch_id;
            $validated['admin_charges'] = $adminCharges;
            $validated['salik_vat'] = $salikVatPercent;
            $validated['salik_vat_amount'] = $salikVatAmount;
            $validated['admin_vat'] = $adminVatPercent;
            $validated['admin_vat_amount'] = $adminVatAmount;
            $validated['vat'] = $totalVat;
            $validated['total_amount'] = $totalAmount;
            $validated['updated_by'] = auth()->id();
            unset($validated['status']);

            $salik->update($validated);

            $this->syncMonthlyInvoiceTransactions($salik->rider_id, $salik->billing_month, $salik->rental_company_id);

            if ($oldRiderId != $salik->rider_id
                || $oldRentalCompanyId != $salik->rental_company_id
                || salik::normalizeBillingMonth($oldBillingMonth) !== salik::normalizeBillingMonth($salik->billing_month)) {
                $this->syncMonthlyInvoiceTransactions($oldRiderId, $oldBillingMonth, $oldRentalCompanyId);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['message' => 'Salik entry updated successfully.', 'reload' => true], 200);
            }

            return redirect()->route('salik.index')
                ->with('success', 'Salik entry updated and monthly invoice synced successfully.');
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
            $salikTransaction->branch_id = $salik->branch_id;
            $salikTransaction->save();
            // Find and update rider debit transaction
            $riderAccount = \App\Support\CompanyQuery::table('accounts')->where('ref_id', $salik->rider_id)->first();
            if ($riderAccount) {
                $riderTransaction = Transactions::where('trans_code', $salikTransaction->trans_code)
                    ->where('account_id', $riderAccount->id)
                    ->where('debit', '>', 0)
                    ->first();
                if ($riderTransaction) {
                    $riderTransaction->debit += ($amountDifference + $adminDifference);
                    $riderTransaction->branch_id = $salik->branch_id;
                    $riderTransaction->save();
                }
            }

            // Handle admin charges transaction
            if ($adminDifference != 0) {
                $adminTransaction = Transactions::where('trans_code', $salikTransaction->trans_code)
                    ->where('account_id', GlobalAccounts::id('SALIK_ADMIN_CHARGES'))
                    ->where('credit', '>', 0)
                    ->first();

                if ($adminTransaction) {
                    $adminTransaction->credit += $adminDifference;
                    $adminTransaction->branch_id = $salik->branch_id;
                    if ($adminTransaction->credit <= 0) {
                        $adminTransaction->delete();
                    } else {
                        // Update narration with current count
                        $relatedSaliks = salik::where('rider_id', $salik->rider_id)
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
                    $this->createAdminTransaction($salikTransaction->trans_code, $adminDifference, $salik->id, $billingMonth, $salik->branch_id);
                }
            }

            // Update main voucher
            $mainVoucher = Vouchers::where('trans_code', $salikTransaction->trans_code)->where('ref_id', $salik->id)->first();
            if ($mainVoucher) {
                $mainVoucher->amount += ($amountDifference + $adminDifference);
                $mainVoucher->branch_id = $salik->branch_id;
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
            $secondVoucherTransaction->branch_id = $salik->branch_id;
            $secondVoucherTransaction->save();

            $payerCreditTransaction = Transactions::where('trans_code', $secondVoucherTransaction->trans_code)
                ->where('credit', '>', 0)
                ->first();

            if ($payerCreditTransaction) {
                $payerCreditTransaction->credit += $amountDifference;
                $payerCreditTransaction->branch_id = $salik->branch_id;
                $payerCreditTransaction->save();
            }

            $secondVoucher = Vouchers::where('trans_code', $secondVoucherTransaction->trans_code)->first();
            if ($secondVoucher) {
                $secondVoucher->amount += $amountDifference;
                $secondVoucher->branch_id = $salik->branch_id;
                $secondVoucher->save();
            }
        }
    }
    private function recreateStandaloneVouchers($salik, $validated, $pay_account)
    {
        $tripAmount = (float) $validated['amount'];
        $adminCharges = (float) ($salik->admin_charges ?? 0);
        $totalVat = (float) ($salik->vat ?? 0);
        $this->requireSalikVoucherHeadAccounts($totalVat, $adminCharges);

        $existingTransCodes = Transactions::where('reference_id', $salik->id)
            ->where('reference_type', 'Salik Voucher')
            ->pluck('trans_code');

        Transactions::where('reference_id', $salik->id)
            ->where('reference_type', 'Salik Voucher')
            ->delete();

        Vouchers::where('ref_id', $salik->id)
            ->whereIn('trans_code', $existingTransCodes)
            ->delete();

        $totalAmount = $tripAmount + $adminCharges + $totalVat;
        $payableAccountId = GlobalAccounts::id('SALIK_PAYABLE_ACCOUNT');
        $transCode = Account::trans_code();
        $transDate = now();
        $billingMonth = date('Y-m-01', strtotime($validated['trip_date']));

        $debitAccountId = $this->resolveSalikDebitAccountId($salik);
        $transactionService = new TransactionService();

        $transactionService->recordTransaction([
            'account_id'     => $debitAccountId,
            'reference_id'   => $salik->id,
            'reference_type' => 'Salik Voucher',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => 'Salik Trip Debit - Reference Number: ' . $salik->transaction_id,
            'debit'          => $totalAmount,
            'billing_month'  => $billingMonth,
            'branch_id'      => $salik->branch_id,
        ]);

        $transactionService->recordTransaction([
            'account_id'     => $payableAccountId,
            'reference_id'   => $salik->id,
            'reference_type' => 'Salik Voucher',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => 'Salik Payable Credit - Reference Number: ' . $salik->transaction_id,
            'credit'         => $tripAmount + $adminCharges,
            'branch_id'      => $salik->branch_id,
            'billing_month'  => $billingMonth,
        ]);

        if ($totalVat > 0) {
            $transactionService->recordTransaction([
                'account_id'     => GlobalAccounts::id('VAT_ON_SALES'),
                'reference_id'   => $salik->id,
                'reference_type' => 'Salik Voucher',
                'trans_code'     => $transCode,
                'trans_date'     => $transDate,
                'narration'      => 'Salik VAT on Sales - Reference Number: ' . $salik->transaction_id,
                'credit'         => $totalVat,
                'branch_id'      => $salik->branch_id,
                'billing_month'  => $billingMonth,
            ]);
        }

        Vouchers::create([
            'trans_date'    => $transDate,
            'trans_code'    => $transCode,
            'payment_type'  => 1,
            'billing_month' => $billingMonth,
            'amount'        => $totalAmount,
            'voucher_type'  => 'SV',
            'remarks'       => 'Salik Voucher - Reference Number: ' . $salik->transaction_id,
            'reference_number' => $salik->transaction_id,
            'ref_id'        => $salik->id,
            'rider_id'      => $salik->rider_id,
            'payment_to'    => $payableAccountId,
            'payment_from'  => $debitAccountId,
            'Created_By'    => auth()->id(),
            'branch_id'     => $salik->branch_id,
            'custom_field_values' => [],
        ]);
    }

    /**
     * Create admin transaction for Salik entries
     */
    private function createAdminTransaction($transCode, $adminAmount, $referenceId, $billingMonth, $branchId)
    {
        $this->requireSalikVoucherHeadAccounts(0, $adminAmount);
        $transactionService = new TransactionService();

        $transactionService->recordTransaction([
            'account_id'     => GlobalAccounts::id('SALIK_ADMIN_CHARGES'),
            'reference_id'   => $referenceId,
            'reference_type' => 'Salik Voucher',
            'trans_code'     => $transCode,
            'trans_date'     => now(),
            'narration'      => 'Salik Import - Admin Charges (1 × ' . $adminAmount . ')',
            'credit'         => $adminAmount,
            'billing_month'  => $billingMonth,
            'branch_id'      => $branchId
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
        \App\Support\CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->delete();

        // Get last ledger entry
        $lastLedger = \App\Support\CompanyQuery::table('ledger_entries')
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($company_slug, $id)
    {
        \DB::beginTransaction();
        try {
            $salik = salik::findOrFail($id);
            $riderId = $salik->rider_id;
            $rentalCompanyId = $salik->rental_company_id;
            $billingMonth = $salik->billing_month;
            $salikIdentifier = $salik->transaction_id ?? "Salik #{$id}";

            \Log::info("Starting deletion of Salik entry - ID: {$id}, Transaction ID: {$salikIdentifier}");

            $salik->delete();

            $this->syncMonthlyInvoiceTransactions($riderId, $billingMonth, $rentalCompanyId);

            try {
                \App\Models\DeletionCascade::create([
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
                    ],
                ]);
            } catch (\Exception $e) {
                \Log::error("Failed to track Salik deletion: " . $e->getMessage());
            }

            \Log::info("Successfully deleted Salik entry ID: {$id} and synced monthly invoice");

            DB::commit();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Salik entry deleted and monthly invoice updated successfully.'
                ]);
            }

            return redirect()->route('salik.index')->with('success', 'Salik entry deleted and monthly invoice updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error("Error deleting Salik entry ID: {$id} - " . $e->getMessage());

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting Salik entry: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('salik.index')->with('error', 'Error deleting Salik entry: ' . $e->getMessage());
        }
    }

    private function adjustGroupVoucherForDeletion($salik, $relatedSaliks, $amount, $adminCharges, $billingMonth, $salikIdentifier = null)
    {
        $transactionService = new TransactionService();
        $riderAccount = \App\Support\CompanyQuery::table('accounts')->where('ref_id', $salik->rider_id)->first();

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
                    ->where('account_id', GlobalAccounts::id('SALIK_ADMIN_CHARGES'))
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
            $transactionService->updateLedger(GlobalAccounts::id('SALIK_ADMIN_CHARGES'), 0, $adminCharges, $billingMonth);
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
        $riderAccount = \App\Support\CompanyQuery::table('accounts')->where('ref_id', $salik->rider_id)->first();
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
            $transactionService->updateLedger(GlobalAccounts::id('SALIK_ADMIN_CHARGES'), 0, $salik->admin_charges, $billingMonth);
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
    public function attachFile($company_slug, $id, Request $request)
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
    public function viewvoucher($company_slug, $id)
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
        $trip_date = $request->input('trip_date') ?? date('Y-m-d');
        $bike = Bikes::find($bike_id);
        if (!$bike) {
            return response()->json(['riders' => false, 'companies' => false]);
        }

        $history = $bike->history()
            ->where('note_date', '<=', $trip_date)
            ->where(function ($query) use ($trip_date) {
                $query->where('return_date', '>=', $trip_date)
                    ->orWhereNull('return_date');
            })
            ->first();

        $currentRiderId = $bike->rider_id;
        $currentCompanyId = $bike->rental_company_id;
        $riderIds = $bike->history()->whereNotNull('rider_id')->pluck('rider_id')->toArray();
        if ($currentRiderId) {
            $riderIds[] = $currentRiderId;
        }
        $companyIds = $bike->history()->whereNotNull('rental_company_id')->pluck('rental_company_id')->toArray();
        if ($currentCompanyId) {
            $companyIds[] = $currentCompanyId;
        }

        $riders = Riders::whereIn('id', array_unique($riderIds))->get();
        $companies = BikeRentCompany::where('customer_type', 'bike_rental')
            ->whereIn('id', array_unique($companyIds))
            ->get();

        $ridersHtml = $riders->isEmpty() ? false : '<option value="">Select Rider</option>' . $riders->map(function ($r) use ($history, $currentRiderId) {
            $selected = ($r->id == ($history?->rider_id ?? $currentRiderId ?? 0)) ? ' selected' : '';
            return '<option value="' . $r->id . '"' . $selected . '>' . $r->rider_id . ' - ' . $r->name . '</option>';
        })->implode('');

        $companiesHtml = $companies->isEmpty() ? false : '<option value="">Select Company</option>' . $companies->map(function ($c) use ($history, $currentCompanyId) {
            $selected = ($c->id == ($history?->rental_company_id ?? $currentCompanyId ?? 0)) ? ' selected' : '';
            return '<option value="' . $c->id . '"' . $selected . '>' . $c->name . '</option>';
        })->implode('');

        return response()->json([
            'riders' => $ridersHtml,
            'companies' => $companiesHtml,
        ]);
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
            'admin_charge_per_salik' => 'nullable|numeric',
        ]);
        try {
            $adminChargePerSalik = $request->admin_charge_per_salik ?? 0;
            $import = new SalikImport($adminChargePerSalik);
            Excel::import($import, $request->file('file'));
            $importedCount = salik::where('created_at', '>=', now()->subMinutes(5))->count();
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
                    'message' => "Salik records imported successfully. Monthly invoices synced.{$skippedMessage}",
                    'imported_count' => $importedCount
                ]);
            }
            Flash::success("Salik records imported successfully. Monthly invoices synced. Records imported: {$importedCount}{$skippedMessage}");
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
    public function importForm($company_slug, $salikAccountId = null)
    {
        return view('salik.import');
    }

    /**
     * Download CSV import template with all required columns.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Transaction ID',
            'Trip Date',
            'Trip Time',
            'Transaction Post Date',
            'Toll Gate',
            'Direction',
            'Tag Number',
            'Plate Number',
            'Amount',
            'Billing Month',
            'Salik VAT %',
            'Admin Charges',
            'Admin VAT %',
            'Details',
        ];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            fputcsv($file, [
                'TXN001',
                '2025-01-15',
                '08:30:00',
                '2025-01-16',
                'Al Maktoum Bridge',
                'North',
                'TAG001',
                'ABC123',
                '4.00',
                '2025-01-01',
                '5',
                '1.00',
                '5',
                'Sample Salik transaction',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="salik_import_template.csv"',
        ]);
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
        $recentSaliks = salik::where('created_at', '>=', now()->subDays(7))
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
            $existingTransactionIds = salik::pluck('transaction_id')->toArray();

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



    public function paymentForm(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('salik_view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $this->requireSalikPaymentHeadAccounts(0, 0, 0);
        } catch (\Exception $e) {
            Flash::error($e->getMessage());
            return redirect()->route('salik.index');
        }

        $leasingCompanies = LeasingCompanies::orderBy('name')->get();
        $salikPayableAccount = Accounts::find(GlobalAccounts::id('SALIK_PAYABLE_ACCOUNT'));
        $vatPurchaseAccount = Accounts::find(GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'));

        return view('salik.payment', compact('leasingCompanies', 'salikPayableAccount', 'vatPurchaseAccount'));
    }

    public function getPaymentRecords(Request $request)
    {
        $request->validate([
            'billing_month' => 'required|date_format:Y-m',
            'leasing_company_id' => 'required|string',
        ]);

        $billingMonth = Carbon::parse($request->billing_month . '-01');
        $selectedFilter = $request->input('leasing_company_id');

        $query = salik::query()
            ->with(['bike.leasingCompany', 'rider'])
            ->where('status', 'unpaid')
            ->where(function ($q) use ($billingMonth) {
                $monthName = $billingMonth->format('M');
                $yearShort = $billingMonth->format('y');
                $q->where('billing_month', 'like', "{$monthName}-{$yearShort}%")
                    ->orWhere('billing_month', 'like', $billingMonth->format('Y-m') . '%')
                    ->orWhere(function ($sub) use ($billingMonth) {
                        $sub->whereYear('billing_month', $billingMonth->year)
                            ->whereMonth('billing_month', $billingMonth->month);
                    });
            });

        $query->whereHas('bike', function ($bikeQuery) use ($selectedFilter) {
            if ($selectedFilter === 'own') {
                $bikeQuery->where(function ($q) {
                    $q->whereNull('company')->orWhere('company', 0);
                });
            } else {
                $bikeQuery->where('company', $selectedFilter);
            }
        });

        $records = $query->orderBy('trip_date')->get();

        return response()->json([
            'html' => view('salik.payment_table', ['records' => $records])->render(),
            'count' => $records->count(),
        ]);
    }

    public function calculatePaymentVoucher(Request $request)
    {
        $request->validate([
            'salik_ids' => 'required|array|min:1',
            'salik_ids.*' => 'integer|exists:saliks,id',
        ]);

        $saliks = salik::with('bike.leasingCompany')->whereIn('id', $request->salik_ids)
            ->where('status', 'unpaid')
            ->get();

        if ($saliks->isEmpty()) {
            return response()->json(['error' => 'No unpaid salik records found.'], 422);
        }

        $billingMonth = $request->billing_month ?? $saliks->first()->billing_month;
        $debitPartyLabel = $this->resolveSalikPaymentPartyLabel(
            $request->input('leasing_company_id'),
            $saliks
        );
        $payableNarration = $this->salikPaymentNarration($billingMonth, $debitPartyLabel);
        $vatNarration = $payableNarration;

        $payableDebit = $saliks->sum(fn ($s) => (float) $s->amount + (float) ($s->admin_charges ?? 0));
        $vatDebit = $saliks->sum(fn ($s) => (float) ($s->salik_vat_amount ?? 0) + (float) ($s->admin_vat_amount ?? 0));

        $leasedTotal = 0;
        $ownSalikAmount = 0;
        $ownAdminAmount = 0;
        $creditLines = [];

        foreach ($saliks as $salik) {
            $amount = (float) $salik->amount;
            $admin = (float) ($salik->admin_charges ?? 0);
            $salikVat = (float) ($salik->salik_vat_amount ?? 0);
            $adminVat = (float) ($salik->admin_vat_amount ?? 0);
            $lineTotal = $amount + $admin + $salikVat + $adminVat;
            $leasingCompany = $salik->bike?->leasingCompany;

            if ($leasingCompany && $leasingCompany->account_id) {
                $key = 'lease_' . $leasingCompany->id;
                if (!isset($creditLines[$key])) {
                    $creditLines[$key] = [
                        'account_id' => $leasingCompany->account_id,
                        'account_name' => $leasingCompany->name,
                        'amount' => 0,
                        'narration' => $this->salikPaymentNarration($billingMonth, $leasingCompany->name),
                    ];
                }
                $creditLines[$key]['amount'] += $lineTotal;
                $leasedTotal += $lineTotal;
            } else {
                $ownSalikAmount += $amount + $salikVat;
                $ownAdminAmount += $admin + $adminVat;
            }
        }

        if ($ownSalikAmount > 0) {
            $assetAccount = Accounts::find(GlobalAccounts::id('SALIK_ASSET_ACCOUNT'));
            $creditLines['own_salik'] = [
                'account_id' => GlobalAccounts::id('SALIK_ASSET_ACCOUNT'),
                'account_name' => $assetAccount?->name ?? 'Salik Asset',
                'amount' => $ownSalikAmount,
                'narration' => $this->salikPaymentNarration($billingMonth, 'own vehicles'),
            ];
        }
        if ($ownAdminAmount > 0) {
            $adminAccount = Accounts::find(GlobalAccounts::id('SALIK_ADMIN_CHARGES'));
            $creditLines['own_admin'] = [
                'account_id' => GlobalAccounts::id('SALIK_ADMIN_CHARGES'),
                'account_name' => $adminAccount?->name ?? 'Salik Admin Charges',
                'amount' => $ownAdminAmount,
                'narration' => $this->salikPaymentNarration($billingMonth, 'own vehicles'),
            ];
        }

        try {
            $this->requireSalikPaymentHeadAccounts($vatDebit, $ownSalikAmount, $ownAdminAmount);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $payableAccount = Accounts::find(GlobalAccounts::id('SALIK_PAYABLE_ACCOUNT'));
        $vatAccount = Accounts::find(GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'));
        $totalDebit = $payableDebit + $vatDebit;
        $totalCredit = collect($creditLines)->sum('amount');

        return response()->json([
            'payable_debit' => round($payableDebit, 2),
            'payable_account_name' => $payableAccount?->name ?? 'Salik Payable',
            'vat_debit' => round($vatDebit, 2),
            'vat_account_name' => $vatAccount?->name ?? 'VAT on Purchase',
            'payable_narration' => $payableNarration,
            'vat_narration' => $vatNarration,
            'credit_lines' => array_values($creditLines),
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'balanced' => abs($totalDebit - $totalCredit) < 0.02,
        ]);
    }

    public function storePayment(Request $request)
    {
        $request->validate([
            'salik_ids' => 'required|array|min:1',
            'salik_ids.*' => 'integer|exists:saliks,id',
            'billing_month' => 'required|date_format:Y-m',
            'trans_date' => 'required|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $saliks = salik::with('bike.leasingCompany')->whereIn('id', $request->salik_ids)
                ->where('status', 'unpaid')
                ->lockForUpdate()
                ->get();

            if ($saliks->count() !== count($request->salik_ids)) {
                throw new \Exception('One or more selected salik records are already paid or not found.');
            }

            $calcRequest = new Request([
                'salik_ids' => $request->salik_ids,
                'billing_month' => $request->billing_month,
                'leasing_company_id' => $this->inferSalikPaymentLeasingFilter($saliks),
            ]);
            $calcResponse = $this->calculatePaymentVoucher($calcRequest);
            $calc = json_decode($calcResponse->getContent(), true);
            if ($calcResponse->getStatusCode() !== 200 || isset($calc['error'])) {
                throw new \Exception($calc['error'] ?? 'Unable to calculate payment voucher.');
            }
            if (!($calc['balanced'] ?? false)) {
                throw new \Exception('Voucher amounts are not balanced. Please recalculate.');
            }

            $transCode = Account::trans_code();
            $transDate = $request->trans_date;
            $billingMonth = Carbon::parse($request->billing_month . '-01')->format('Y-m-d');
            $transactionService = new TransactionService();
            $firstSalik = $saliks->first();
            $branchId = $firstSalik->branch_id;

            $transactionService->recordTransaction([
                'account_id' => GlobalAccounts::id('SALIK_PAYABLE_ACCOUNT'),
                'reference_id' => $firstSalik->id,
                'reference_type' => 'Salik Voucher',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => $calc['payable_narration'],
                'debit' => $calc['payable_debit'],
                'billing_month' => $billingMonth,
                'branch_id' => $branchId,
            ]);

            if (($calc['vat_debit'] ?? 0) > 0) {
                $transactionService->recordTransaction([
                    'account_id' => GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'),
                    'reference_id' => $firstSalik->id,
                    'reference_type' => 'Salik Voucher',
                    'trans_code' => $transCode,
                    'trans_date' => $transDate,
                    'narration' => $calc['vat_narration'],
                    'debit' => $calc['vat_debit'],
                    'billing_month' => $billingMonth,
                    'branch_id' => $branchId,
                ]);
            }

            foreach ($calc['credit_lines'] as $line) {
                $transactionService->recordTransaction([
                    'account_id' => $line['account_id'],
                    'reference_id' => $firstSalik->id,
                    'reference_type' => 'Salik Voucher',
                    'trans_code' => $transCode,
                    'trans_date' => $transDate,
                    'narration' => $line['narration'],
                    'credit' => $line['amount'],
                    'billing_month' => $billingMonth,
                    'branch_id' => $branchId,
                ]);
            }

            $voucher = Vouchers::create([
                'trans_date' => $transDate,
                'trans_code' => $transCode,
                'billing_month' => $billingMonth,
                'voucher_type' => 'SV',
                'payment_type' => 1,
                'payment_from' => GlobalAccounts::id('SALIK_PAYABLE_ACCOUNT'),
                'payment_to' => $calc['credit_lines'][0]['account_id'] ?? null,
                'amount' => $calc['total_debit'],
                'remarks' => $request->remarks ?? ('Salik payment for ' . $saliks->count() . ' record(s)'),
                'ref_id' => $firstSalik->id,
                'Created_By' => auth()->id(),
                'branch_id' => $branchId,
                'custom_field_values' => [],
            ]);

            salik::whereIn('id', $saliks->pluck('id'))->update([
                'status' => 'paid',
                'payment_voucher_id' => $voucher->id,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['message' => 'Salik payment recorded successfully.', 'reload' => true], 200);
            }

            Flash::success('Salik payment recorded successfully.');
            return redirect()->route('salik.index');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['errors' => ['error' => $e->getMessage()]], 500);
            }
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Normalize payment status and re-sync monthly invoices for rental-company saliks.
     */
    public function repairRentalCompanySalikRecords(): array
    {
        $normalized = 0;

        salik::whereNotNull('rental_company_id')
            ->whereNotNull('rider_id')
            ->update(['rider_id' => null]);

        $rentalSaliks = salik::whereNotNull('rental_company_id')->get();

        foreach ($rentalSaliks as $salik) {
            $newStatus = salik::normalizePaymentStatus(
                $salik->status,
                !empty($salik->payment_voucher_id)
            );

            if ($salik->status !== $newStatus) {
                $salik->update(['status' => $newStatus]);
                $normalized++;
            }
        }

        $groups = salik::whereNotNull('rental_company_id')
            ->whereNull('rider_id')
            ->get()
            ->groupBy(fn ($s) => $s->rental_company_id . '|' . salik::normalizeBillingMonth($s->billing_month));

        foreach ($groups as $group) {
            $first = $group->first();
            $this->syncMonthlyInvoiceTransactions(null, $first->billing_month, (int) $first->rental_company_id);
        }

        return [
            'normalized' => $normalized,
            'synced_groups' => $groups->count(),
        ];
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
