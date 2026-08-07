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
use App\Http\Requests\StoreSalikTopUpRequest;
use App\Models\Banks;
use App\Repositories\SalikRepository;
use App\Services\SalikTopUpService;
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
        if (!user_can('salik_view')) {
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
        if ($request->has('company') && ! empty($request->company)) {
            if ($request->company === 'own') {
                $query->whereHas('bike', function ($query) {
                    $query->where('bike_owner', 'Owned');
                });
            } else {
                $query->whereHas('bike', function ($query) use ($request) {
                    $query->where('company', $request->company);
                });
            }
        }
        if ($request->filled('status')) {
            if ($request->status === 'paid') {
                $query->paid();
            } elseif ($request->status === 'unpaid') {
                $query->unpaid();
            } else {
                $query->where('status', $request->status);
            }
        }

        $paidAmount   = (clone $query)->paid()->sum('total_amount');
        $unpaidAmount = (clone $query)->unpaid()->sum('total_amount');
        $paidCount    = (clone $query)->paid()->count();
        $unpaidCount  = (clone $query)->unpaid()->count();

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
        $request->validate($this->salikFormRules());

        $exists = salik::where('transaction_id', $request->transaction_id)->exists();
        if ($exists) {
            return response()->json(['errors' => ['error' => 'This Transaction ID already exists.']], 422);
        }

        \DB::beginTransaction();
        try {
            $bike = Bikes::findOrFail($request->bike_id);
            $payload = $this->buildSalikPayloadFromRequest($request, $bike);

            $this->requireSalikVoucherHeadAccounts($payload['vat'], $payload['admin_charges']);

            $payload['status'] = 'unpaid';
            $payload['created_by'] = Auth::user()->id;
            $payload['trans_date'] = Carbon::today();

            $salik = $this->salikRepository->create($payload);

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
            if ($request->ajax()) {
                return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function monthlySummary(Request $request)
    {
        if (!user_can('salik_view')) {
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

        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->rider_id);
        }

        $summaries = $query->get();
        $summaries->load(['rider', 'rentalCompany']);

        $totalInvoices = $summaries->count();
        $totalSaliks = (int) $summaries->sum('transaction_count');
        $salikAmount = (float) $summaries->sum('total_amount');
        $adminCharges = (float) $summaries->sum('total_admin_charges');
        $vat = (float) $summaries->sum('total_vat');
        $totalAmount = (float) $summaries->sum('total_grand');

        return view('salik.monthly_summary', compact(
            'summaries',
            'totalInvoices',
            'totalSaliks',
            'salikAmount',
            'adminCharges',
            'vat',
            'totalAmount'
        ));
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
            'narration'      => "Salik for {$billingMonthDisplay} ({$count} trips) - {$invId}",
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
            // Invoice ledger only — never purge payment vouchers (reference_type = Salik Voucher)
            Transactions::whereIn('reference_id', $salikIds)
                ->whereIn('reference_type', ['salik', 'Salik'])
                ->delete();
            // Do not delete vouchers by ref_id — payment SV vouchers also store salik ref_id
        }

        if ($transCodes->isNotEmpty()) {
            Transactions::whereIn('trans_code', $transCodes)
                ->whereIn('reference_type', ['salik', 'Salik'])
                ->delete();

            // Only invoice/legacy vouchers on the salik invoice trans_code (not payment codes)
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
            ->whereIn('reference_type', ['salik', 'Salik'])
            ->pluck('trans_code')
            ->unique()
            ->filter();

        if ($transCodes->isEmpty()) {
            return;
        }

        Transactions::whereIn('trans_code', $transCodes)
            ->whereIn('reference_type', ['salik', 'Salik'])
            ->delete();
        // Do not delete SV payment vouchers here
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

    private function parseSalikTripDate(?string $tripDate): ?Carbon
    {
        if ($tripDate === null || trim($tripDate) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d M Y', trim($tripDate))->startOfDay();
        } catch (\Exception $e) {
            try {
                return Carbon::parse($tripDate)->startOfDay();
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    private function resolveSalikTripDateRangeLabel($saliks): string
    {
        $dates = collect($saliks)
            ->map(fn ($salik) => $this->parseSalikTripDate($salik->trip_date ?? null))
            ->filter();

        if ($dates->isEmpty()) {
            return Carbon::now()->format('d M Y');
        }

        $min = $dates->min();
        $max = $dates->max();

        if ($min->equalTo($max)) {
            return $min->format('d M Y');
        }

        return $min->format('d M Y') . ' - ' . $max->format('d M Y');
    }

    private function salikPaymentNarration(string $dateRangeLabel, string $partyLabel): string
    {
        return 'Salik Charges for (' . $dateRangeLabel . ') ' . $partyLabel;
    }

    private function salikVatNarration(string $baseNarration): string
    {
        return '( Vat ) ' . ltrim(preg_replace('/^\(\s*Vat\s*\)\s*/i', '', $baseNarration) ?? $baseNarration);
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

        if ($salik->isPaid()) {
            $message = 'Paid Salik records cannot be edited.';
            if (request()->ajax()) {
                return response()->json(['message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->route('salik.index');
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

        $request->validate($this->salikFormRules());

        DB::beginTransaction();
        try {
            $salik = salik::findOrFail($id);
            if ($salik->isPaid()) {
                throw new \Exception('Paid Salik records cannot be updated.');
            }

            $bike = Bikes::findOrFail($request->bike_id);
            $payload = $this->buildSalikPayloadFromRequest($request, $bike);
            $payload['updated_by'] = auth()->id();

            $this->requireSalikVoucherHeadAccounts($payload['vat'], $payload['admin_charges']);

            $oldRiderId = $salik->rider_id;
            $oldRentalCompanyId = $salik->rental_company_id;
            $oldBillingMonth = $salik->billing_month;

            $salik->update($payload);

            $this->syncMonthlyInvoiceTransactions($salik->rider_id, $salik->billing_month, $salik->rental_company_id);

            if (
                (int) $oldRiderId !== (int) $salik->rider_id
                || (int) ($oldRentalCompanyId ?? 0) !== (int) ($salik->rental_company_id ?? 0)
                || salik::normalizeBillingMonth($oldBillingMonth) !== salik::normalizeBillingMonth($salik->billing_month)
            ) {
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
            return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
        }
    }

    /**
     * Shared validation rules for create/update.
     */
    private function salikFormRules(): array
    {
        return [
            'transaction_id' => 'required|string|max:255',
            'trip_date' => 'required|date',
            'trip_time' => 'required|string|max:255',
            'transaction_post_date' => 'nullable|date',
            'billing_month' => 'required|date_format:Y-m',
            'bike_id' => 'required|exists:bikes,id',
            'rider_id' => 'nullable|exists:riders,id',
            'rental_company_id' => 'nullable|exists:bike_rent_companies,id',
            'toll_gate' => 'nullable|string|max:255',
            'direction' => 'nullable|string|max:255',
            'tag_number' => 'nullable|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'admin_fee' => 'nullable|numeric|min:0',
            'salik_vat' => 'nullable|numeric|min:0',
            'admin_vat' => 'nullable|numeric|min:0',
            'details' => 'nullable|string|max:5000',
        ];
    }

    /**
     * Build store/update payload aligned with Salik import financials and rider rules.
     */
    private function buildSalikPayloadFromRequest(Request $request, Bikes $bike): array
    {
        $wantsCompany = $request->filled('rental_company_id');
        $wantsRider = $request->filled('rider_id');

        if ($wantsCompany && $wantsRider) {
            throw new \Exception('Either select a Rider or Rental Company. Cannot charge both.');
        }

        $tripDate = Carbon::parse($request->trip_date)->startOfDay();
        $tripDateForStorage = $tripDate->format('d M Y');

        if (!$wantsCompany && !$wantsRider) {
            throw new \Exception('Please select a Rider or Rental Company.');
        }

        $riderId = null;
        $rentalCompanyId = null;

        if ($wantsCompany) {
            $rentalCompanyId = (int) $request->rental_company_id;
            if (!$this->partyAssignedToBike($bike, null, $rentalCompanyId)) {
                throw new \Exception('Selected rental company was never assigned to this bike.');
            }

            $company = BikeRentCompany::find($rentalCompanyId);
            if (!$company || empty($company->account_id)) {
                throw new \Exception('Rental company account not found.');
            }
        } else {
            $riderId = (int) $request->rider_id;
            if (!$this->partyAssignedToBike($bike, $riderId, null)) {
                throw new \Exception('Selected rider was never assigned to this bike.');
            }

            $rider = Riders::find($riderId);
            if (!$rider) {
                throw new \Exception('Selected rider not found.');
            }

            if (empty($rider->account_id)) {
                throw new \Exception("No account found for rider {$rider->name}.");
            }
        }

        $amount = (float) ($request->amount ?? 0);
        $adminCharges = (float) ($request->admin_fee ?? $request->admin_charges ?? 0);
        $salikVatPercent = (float) ($request->salik_vat ?? 0);
        $adminVatPercent = (float) ($request->admin_vat ?? 0);

        // VAT amounts always derived from % (same as import)
        $salikVatAmount = round($amount * $salikVatPercent / 100, 2);
        $adminVatAmount = round($adminCharges * $adminVatPercent / 100, 2);
        $totalVat = $salikVatAmount + $adminVatAmount;
        $totalAmount = $amount + $adminCharges + $totalVat;

        $tripTime = $this->normalizeSalikTripTime($request->trip_time);
        $postDateRaw = $request->transaction_post_date;
        $transactionPostDate = !empty($postDateRaw)
            ? Carbon::parse($postDateRaw)->format('d M Y')
            : $tripDateForStorage;

        $billingMonth = Carbon::parse($request->billing_month . '-01')->startOfMonth()->toDateString();
        $details = trim((string) ($request->details ?? ''));
        if ($details === '') {
            $details = 'Salik Charges - ' . $tripDate->format('M-Y');
        }

        return [
            'transaction_id' => $request->transaction_id,
            'trip_date' => $tripDateForStorage,
            'trip_time' => $tripTime,
            'transaction_post_date' => $transactionPostDate,
            'toll_gate' => $request->toll_gate,
            'direction' => $request->direction,
            'tag_number' => $request->tag_number,
            'plate' => $bike->plate,
            'bike_id' => $bike->id,
            'amount' => $amount,
            'salik_vat' => $salikVatPercent,
            'salik_vat_amount' => $salikVatAmount,
            'rider_id' => $riderId,
            'rental_company_id' => $rentalCompanyId,
            'admin_charges' => $adminCharges,
            'admin_vat' => $adminVatPercent,
            'admin_vat_amount' => $adminVatAmount,
            'vat' => $totalVat,
            'total_amount' => $totalAmount,
            'details' => $details,
            'branch_id' => $bike->branch_id,
            'billing_month' => $billingMonth,
        ];
    }

    /**
     * Charge party for trip date from bike history (used by import-aligned helpers / AJAX defaults).
     * Prefer rider when present; otherwise rental company.
     *
     * @return array{rider_id: ?int, rental_company_id: ?int}|null
     */
    private function findChargePartyForTripDate(int $bikeId, string $tripDate): ?array
    {
        $trip = Carbon::parse($tripDate)->startOfDay();

        $histories = BikeHistory::where('bike_id', $bikeId)
            ->where(function ($q) {
                $q->whereNotNull('rider_id')
                    ->orWhereNotNull('rental_company_id');
            })
            ->whereDate('note_date', '<=', $trip)
            ->where(function ($q) use ($trip) {
                $q->whereNull('return_date')
                    ->orWhereDate('return_date', '>=', $trip);
            })
            ->orderByDesc('note_date')
            ->orderByDesc('id')
            ->get();

        // Prefer rider among any overlapping history rows for that date
        $riderHistory = $histories->first(fn ($h) => !empty($h->rider_id));
        if ($riderHistory) {
            return [
                'rider_id' => (int) $riderHistory->rider_id,
                'rental_company_id' => null,
            ];
        }

        $companyHistory = $histories->first(fn ($h) => !empty($h->rental_company_id));
        if ($companyHistory) {
            return [
                'rider_id' => null,
                'rental_company_id' => (int) $companyHistory->rental_company_id,
            ];
        }

        // Fall back to current bike assignment
        $bike = Bikes::find($bikeId);
        if ($bike?->rider_id) {
            return [
                'rider_id' => (int) $bike->rider_id,
                'rental_company_id' => null,
            ];
        }
        if ($bike?->rental_company_id) {
            return [
                'rider_id' => null,
                'rental_company_id' => (int) $bike->rental_company_id,
            ];
        }

        return null;
    }

    /**
     * Whether a rider or rental company appears in this bike's history (or current assignment).
     * Used by create/edit so the user can charge any party from bike history.
     */
    private function partyAssignedToBike(Bikes $bike, ?int $riderId = null, ?int $rentalCompanyId = null): bool
    {
        if ($riderId) {
            if ((int) $bike->rider_id === $riderId) {
                return true;
            }

            return BikeHistory::where('bike_id', $bike->id)
                ->where('rider_id', $riderId)
                ->exists();
        }

        if ($rentalCompanyId) {
            if ((int) $bike->rental_company_id === $rentalCompanyId) {
                return true;
            }

            return BikeHistory::where('bike_id', $bike->id)
                ->where('rental_company_id', $rentalCompanyId)
                ->exists();
        }

        return false;
    }

    private function normalizeSalikTripTime($tripTime): ?string
    {
        if ($tripTime === null || $tripTime === '') {
            return null;
        }

        try {
            return Carbon::parse($tripTime)->format('h:i:s A');
        } catch (\Exception $e) {
            return is_string($tripTime) ? $tripTime : null;
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
            if ($salik->isPaid()) {
                throw new \Exception('Paid Salik records cannot be deleted.');
            }
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
                $status = str_contains($e->getMessage(), 'Paid Salik') ? 422 : 500;
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting Salik entry: ' . $e->getMessage()
                ], $status);
            }

            return redirect()->route('salik.index')->with('error', 'Error deleting Salik entry: ' . $e->getMessage());
        }
    }

    /**
     * Show form to delete unpaid salik records for a billing month.
     */
    public function deleteMonthlyForm()
    {
        if (! user_can('rta_saliks_salik_delete') && ! user_can('salik_delete')) {
            abort(403, 'Unauthorized action.');
        }

        return view('salik.delete_monthly');
    }

    /**
     * Soft-delete unpaid salik records for a billing month, then re-sync monthly invoices.
     * Paid records for the same month are left untouched.
     */
    public function deleteMonthly(Request $request)
    {
        if (! user_can('rta_saliks_salik_delete') && ! user_can('salik_delete')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete salik records.',
            ], 403);
        }

        $request->validate([
            'billing_month' => 'required|date_format:Y-m',
        ], [
            'billing_month.required' => 'Billing month is required.',
            'billing_month.date_format' => 'Billing month must be a valid month.',
        ]);

        $billingMonth = salik::normalizeBillingMonth($request->billing_month);
        if (! $billingMonth) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid billing month.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $query = salik::query()->unpaid();
            salik::applyBillingMonthFilter($query, $billingMonth);

            $toDelete = (clone $query)->get(['id', 'rider_id', 'rental_company_id']);
            $deletedCount = $toDelete->count();

            if ($deletedCount === 0) {
                $paidQuery = salik::query()->paid();
                salik::applyBillingMonthFilter($paidQuery, $billingMonth);
                $paidCount = $paidQuery->count();

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => $paidCount > 0
                        ? "No unpaid salik records found for the selected month. {$paidCount} paid record(s) were left untouched."
                        : 'No unpaid salik records found for the selected month.',
                ], 404);
            }

            $syncTargets = [];
            foreach ($toDelete as $row) {
                if ($row->rider_id) {
                    $syncTargets['r:' . $row->rider_id] = [
                        'rider_id' => (int) $row->rider_id,
                        'rental_company_id' => null,
                    ];
                } elseif ($row->rental_company_id) {
                    $syncTargets['c:' . $row->rental_company_id] = [
                        'rider_id' => null,
                        'rental_company_id' => (int) $row->rental_company_id,
                    ];
                }
            }

            (clone $query)->delete();

            foreach ($syncTargets as $target) {
                $this->syncMonthlyInvoiceTransactions(
                    $target['rider_id'],
                    $billingMonth,
                    $target['rental_company_id']
                );
            }

            DB::commit();

            $monthLabel = Carbon::parse($billingMonth)->format('F Y');

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deletedCount} unpaid salik record(s) for {$monthLabel}. Paid records were not deleted.",
                'reload' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete monthly salik records: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
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
     * Return all riders/companies from bike history for create/edit selection.
     * Optionally pre-selects an explicit party, else the party active on trip_date.
     */
    public function getriderbybikedate(Request $request)
    {
        $bike_id = $request->input('bike_id');
        $bike = Bikes::find($bike_id);
        if (!$bike) {
            return response()->json(['riders' => false, 'companies' => false]);
        }

        $histories = BikeHistory::where('bike_id', $bike_id)
            ->where(function ($q) {
                $q->whereNotNull('rider_id')->orWhereNotNull('rental_company_id');
            })
            ->orderByDesc('note_date')
            ->orderByDesc('id')
            ->get();

        $riderIds = $histories->pluck('rider_id')->filter()->unique()->values();
        $companyIds = $histories->pluck('rental_company_id')->filter()->unique()->values();

        if ($bike->rider_id) {
            $riderIds = $riderIds->push((int) $bike->rider_id)->unique()->values();
        }
        if ($bike->rental_company_id) {
            $companyIds = $companyIds->push((int) $bike->rental_company_id)->unique()->values();
        }

        $selectedRiderId = $request->filled('selected_rider_id')
            ? (int) $request->input('selected_rider_id')
            : null;
        $selectedCompanyId = $request->filled('selected_rental_company_id')
            ? (int) $request->input('selected_rental_company_id')
            : null;

        // Default to party active on trip date when no sticky/edit selection
        if (!$selectedRiderId && !$selectedCompanyId && $request->filled('trip_date')) {
            $party = $this->findChargePartyForTripDate((int) $bike_id, $request->input('trip_date'));
            if ($party) {
                $selectedRiderId = $party['rider_id'];
                $selectedCompanyId = $party['rental_company_id'];
            }
        }

        // Prefer one party only
        if ($selectedRiderId && $selectedCompanyId) {
            $selectedCompanyId = null;
        }

        $ridersHtml = false;
        if ($riderIds->isNotEmpty()) {
            $riders = Riders::whereIn('id', $riderIds)->orderBy('name')->get();
            $options = '<option value="">Select Rider</option>';
            foreach ($riders as $rider) {
                $selected = ((int) $rider->id === (int) $selectedRiderId) ? ' selected' : '';
                $options .= '<option value="' . $rider->id . '"' . $selected . '>'
                    . e($rider->rider_id . ' - ' . $rider->name)
                    . '</option>';
            }
            $ridersHtml = $options;
        }

        $companiesHtml = false;
        if ($companyIds->isNotEmpty()) {
            $companies = BikeRentCompany::where('customer_type', 'bike_rental')
                ->whereIn('id', $companyIds)
                ->orderBy('name')
                ->get();
            $options = '<option value="">Select Company</option>';
            foreach ($companies as $company) {
                $selected = ((int) $company->id === (int) $selectedCompanyId) ? ' selected' : '';
                $options .= '<option value="' . $company->id . '"' . $selected . '>'
                    . e($company->name)
                    . '</option>';
            }
            $companiesHtml = $options;
        }

        return response()->json([
            'riders' => $ridersHtml,
            'companies' => $companiesHtml,
            'selected_rider_id' => $selectedRiderId,
            'selected_rental_company_id' => $selectedCompanyId,
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

                $collection = Excel::toCollection(new SalikImport(0), $file);
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
            'file' => 'required|mimes:xlsx,csv,xls',
            'admin_charge_per_salik' => 'nullable|numeric|min:0',
            'salik_vat_percent' => 'nullable|numeric|min:0',
            'admin_vat_percent' => 'nullable|numeric|min:0',
            'col_transaction_id' => 'required|integer|min:1',
            'col_trip_date' => 'required|integer|min:1',
            'col_trip_time' => 'required|integer|min:1',
            'col_toll_gate' => 'required|integer|min:1',
            'col_direction' => 'required|integer|min:1',
            'col_tag_number' => 'required|integer|min:1',
            'col_plate' => 'required|integer|min:1',
            'col_amount' => 'required|integer|min:1',
            'col_transaction_post_date' => 'nullable|integer|min:1',
            'col_billing_month' => 'nullable|integer|min:1',
        ]);

        $columnMap = [
            'transaction_id' => (int) $request->col_transaction_id,
            'trip_date' => (int) $request->col_trip_date,
            'trip_time' => (int) $request->col_trip_time,
            'toll_gate' => (int) $request->col_toll_gate,
            'direction' => (int) $request->col_direction,
            'tag_number' => (int) $request->col_tag_number,
            'plate' => (int) $request->col_plate,
            'amount' => (int) $request->col_amount,
            'transaction_post_date' => $request->filled('col_transaction_post_date') ? (int) $request->col_transaction_post_date : null,
            'billing_month' => $request->filled('col_billing_month') ? (int) $request->col_billing_month : null,
        ];

        $uniqueCheck = $columnMap;
        // Trip date and trip time may share the same column when both are in one cell
        unset($uniqueCheck['trip_time']);
        $provided = array_filter($uniqueCheck, fn ($v) => $v !== null);
        if (count($provided) !== count(array_unique($provided))) {
            $message = 'Column numbers must be unique, except Trip Date and Trip Time which may share the same column.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->back();
        }
        if (
            $columnMap['trip_time'] !== $columnMap['trip_date']
            && in_array($columnMap['trip_time'], $provided, true)
        ) {
            $message = 'Trip Time column number conflicts with another mapped column.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);
            return redirect()->back();
        }

        try {
            $adminChargePerSalik = $request->admin_charge_per_salik ?? 0;
            $salikVatPercent = $request->salik_vat_percent ?? 0;
            $adminVatPercent = $request->admin_vat_percent ?? 0;
            $import = new SalikImport($adminChargePerSalik, $columnMap, $salikVatPercent, $adminVatPercent);
            Excel::import($import, $request->file('file'));
            $importedCount = $import->importedCount;
            \Log::info('Import completed. Records imported: ' . $importedCount);

            $messages = [];
            if ($import->missingDataCount > 0) {
                $messages[] = "{$import->missingDataCount} missing data";
            }
            if ($import->duplicateCount > 0) {
                $messages[] = "{$import->duplicateCount} duplicates";
            }
            if ($import->updatedCount > 0) {
                $messages[] = "{$import->updatedCount} existing records updated";
            }
            if ($import->noBikeCount > 0) {
                $messages[] = "{$import->noBikeCount} unknown bikes";
            }
            if ($import->noRiderCount > 0) {
                $messages[] = "{$import->noRiderCount} no user assigned";
            }
            if ($import->noAccountCount > 0) {
                $messages[] = "{$import->noAccountCount} no accounts";
            }
            if ($import->notSalikCount > 0) {
                $messages[] = "{$import->notSalikCount} not salik";
            }
            $skippedLog = array_values($import->skippedLog);
            $skippedCount = count($skippedLog);
            $skippedMessage = !empty($messages) ? ' Skipped: '.implode(', ', $messages).'.' : '';
            $message = "Import finished. Imported: {$importedCount}.{$skippedMessage}";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'imported_count' => $importedCount,
                    'skipped_count' => $skippedCount,
                    'skipped_log' => $skippedLog,
                ]);
            }
            Flash::success($message);
            if ($skippedCount > 0) {
                session()->flash('import_skipped_log', $skippedLog);
            }
            return redirect()->back();
        } catch (\Exception $e) {
            \Log::error('Salik import failed: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
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
        if (!user_can('salik_view')) {
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
        if (!user_can('salik_view')) {
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
        if (!user_can('salik_view')) {
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
        if (!user_can('salik_view')) {
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



    public function createTopUp()
    {
        if (! $this->canTopUp()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $salikAssetAccount = GlobalAccounts::account('SALIK_ASSET_ACCOUNT');
        } catch (\Exception $e) {
            Flash::error($e->getMessage());

            return redirect()->route('salik.index');
        }

        $banks = Banks::active()->with('account')->orderBy('name')->get();
        $salikAssetLabel = trim(
            ($salikAssetAccount->account_code ? $salikAssetAccount->account_code.' — ' : '')
            .($salikAssetAccount->name ?: 'Salik Asset')
        );

        return view('salik.top_up', [
            'banks' => $banks,
            'salikAssetAccount' => $salikAssetAccount,
            'salikAssetLabel' => $salikAssetLabel,
        ]);
    }

    public function storeTopUp(StoreSalikTopUpRequest $request, SalikTopUpService $topUpService)
    {
        try {
            $topUpService->create($request->validated() + [
                'attachment' => $request->file('attachment'),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Salik top-up payment voucher created successfully.',
                    'reload' => true,
                ]);
            }

            Flash::success('Salik top-up payment voucher created successfully.');

            return redirect(route('salik.index'));
        } catch (\InvalidArgumentException $e) {
            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            Flash::error($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Exception $e) {
            \Log::error('Salik top-up failed: '.$e->getMessage()."\n".$e->getTraceAsString());

            if ($request->ajax()) {
                return response()->json(['message' => 'An error occurred: '.$e->getMessage()], 500);
            }

            Flash::error('Error occurred: '.$e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    protected function canTopUp(): bool
    {
        $canManageSalik = user_can('rta_saliks_salik_create') || user_can('rta_saliks_salik_edit');
        $canManagePayment = user_can('rta_saliks_payment_create') || user_can('rta_saliks_payment_edit');

        return $canManageSalik || $canManagePayment;
    }

    public function paymentForm(Request $request)
    {
        if (!user_can('salik_view')) {
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
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'leasing_company_id' => 'required|string',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable',
        ]);

        $paginationParams = $this->getPaginationParams($request, 50);
        $query = $this->buildPaymentRecordsQuery($request);

        $records = $this->applyPagination(
            $query->orderByRaw("COALESCE(STR_TO_DATE(trip_date, '%d %b %Y'), STR_TO_DATE(trip_date, '%Y-%m-%d'), DATE(trip_date))"),
            $paginationParams
        );
        if (method_exists($records, 'appends')) {
            $records->appends($request->except('page'));
        }

        return response()->json([
            'html' => view('salik.payment_table', ['records' => $records])->render(),
            'count' => method_exists($records, 'total') ? $records->total() : $records->count(),
            'page' => method_exists($records, 'currentPage') ? $records->currentPage() : 1,
        ]);
    }

    public function getPaymentRecordIds(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'leasing_company_id' => 'required|string',
            'search' => 'nullable|string|max:255',
        ]);

        $ids = $this->buildPaymentRecordsQuery($request)->pluck('id')->map(fn ($id) => (string) $id)->values();

        return response()->json([
            'ids' => $ids,
            'count' => $ids->count(),
        ]);
    }

    private function buildPaymentRecordsQuery(Request $request)
    {
        $selectedFilter = $request->input('leasing_company_id');
        $dateFrom = Carbon::parse($request->date_from)->toDateString();
        $dateTo = Carbon::parse($request->date_to)->toDateString();

        $query = salik::query()
            ->with(['bike.leasingCompany', 'rider'])
            ->unpaid()
            // trip_date is stored as "d M Y" (e.g. 09 Apr 2026), not a native DATE
            ->whereRaw(
                "COALESCE(STR_TO_DATE(trip_date, '%d %b %Y'), STR_TO_DATE(trip_date, '%Y-%m-%d'), DATE(trip_date)) BETWEEN ? AND ?",
                [$dateFrom, $dateTo]
            );

        $query->whereHas('bike', function ($bikeQuery) use ($selectedFilter) {
            if ($selectedFilter === 'own') {
                $bikeQuery->where(function ($q) {
                    $q->where('bike_owner', 'Owned')
                        ->orWhereNull('company')
                        ->orWhere('company', 0);
                });
            } else {
                $bikeQuery->where('company', $selectedFilter);
            }
        });

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', '%' . $search . '%')
                    ->orWhere('plate', 'like', '%' . $search . '%')
                    ->orWhereHas('rider', function ($rq) use ($search) {
                        $rq->where('rider_id', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query;
    }

    public function calculatePaymentVoucher(Request $request)
    {
        $request->validate([
            'salik_ids' => 'required|array|min:1',
            'salik_ids.*' => 'integer|exists:saliks,id',
        ]);

        $saliks = salik::with('bike.leasingCompany')->whereIn('id', $request->salik_ids)
            ->unpaid()
            ->get();

        if ($saliks->isEmpty()) {
            return response()->json(['error' => 'No unpaid salik records found.'], 422);
        }

        $dateRangeLabel = $this->resolveSalikTripDateRangeLabel($saliks);
        $debitPartyLabel = $this->resolveSalikPaymentPartyLabel(
            $request->input('leasing_company_id'),
            $saliks
        );
        $payableNarration = $this->salikPaymentNarration($dateRangeLabel, $debitPartyLabel);
        $vatNarration = $this->salikVatNarration($payableNarration);

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
                        'narration' => $this->salikPaymentNarration($dateRangeLabel, $leasingCompany->name),
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
                'narration' => $this->salikPaymentNarration($dateRangeLabel, 'own vehicles'),
            ];
        }
        if ($ownAdminAmount > 0) {
            $adminAccount = Accounts::find(GlobalAccounts::id('SALIK_ADMIN_CHARGES'));
            $creditLines['own_admin'] = [
                'account_id' => GlobalAccounts::id('SALIK_ADMIN_CHARGES'),
                'account_name' => $adminAccount?->name ?? 'Salik Admin Charges',
                'amount' => $ownAdminAmount,
                'narration' => $this->salikPaymentNarration($dateRangeLabel, 'own vehicles'),
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
                ->unpaid()
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

            if ($request->filled('payable_narration')) {
                $calc['payable_narration'] = trim((string) $request->payable_narration);
            }
            if ($request->filled('vat_narration')) {
                $calc['vat_narration'] = trim((string) $request->vat_narration);
            } elseif ($request->filled('payable_narration')) {
                $calc['vat_narration'] = $this->salikVatNarration($calc['payable_narration']);
            }

            $creditNarrations = $request->input('credit_narrations', []);
            if (is_array($creditNarrations)) {
                foreach ($calc['credit_lines'] as $index => &$creditLine) {
                    if (array_key_exists($index, $creditNarrations) && $creditNarrations[$index] !== null && $creditNarrations[$index] !== '') {
                        $creditLine['narration'] = trim((string) $creditNarrations[$index]);
                    } elseif ($request->filled('payable_narration')) {
                        $creditLine['narration'] = $calc['payable_narration'];
                    }
                }
                unset($creditLine);
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

    public function paymentRecords(Request $request)
    {
        if (!user_can('rta_saliks_payment_view')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Vouchers::where('voucher_type', 'SV')
            ->withCount('salikPayments as salik_count');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('billing_month')) {
            $bm = Carbon::parse($request->billing_month . '-01')->format('Y-m-d');
            $query->where('billing_month', $bm);
        }

        if ($request->filled('trans_date_from')) {
            $query->where('trans_date', '>=', $request->trans_date_from);
        }

        if ($request->filled('trans_date_to')) {
            $query->where('trans_date', '<=', $request->trans_date_to);
        }

        $paginationParams = $this->getPaginationParams($request);

        $totalCount = (clone $query)->count();
        $totalAmount = (clone $query)->sum('amount');
        $totalSaliks = (int) (clone $query)->sum(
            DB::raw('(SELECT COUNT(*) FROM saliks WHERE saliks.payment_voucher_id = vouchers.id)')
        );

        $data = $this->applyPagination($query->orderByDesc('trans_date'), $paginationParams);

        if ($request->ajax()) {
            $tableHtml = view('salik.payments_table', compact('data'))->render();
            $paginationLinks = method_exists($data, 'links') ? $data->links('components.global-pagination')->render() : '';

            return response()->json([
                'tableData' => $tableHtml,
                'paginationLinks' => $paginationLinks,
                'total' => method_exists($data, 'total') ? $data->total() : $data->count(),
            ]);
        }

        return view('salik.payments', compact('data', 'totalCount', 'totalAmount', 'totalSaliks'));
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
