<?php

namespace App\Http\Controllers;

use App\Exports\RiderInvoiceExport;
use App\Helpers\Account;
use App\Http\Requests\CreateRiderInvoicesRequest;
use App\Http\Requests\UpdateRiderInvoicesRequest;
use App\Imports\ImportPaidRiderInvoice;
use App\Imports\ImportRiderInvoice;
use App\Models\Accounts;
use App\Models\Customers;
use App\Models\Items;
use App\Models\Payment;
use App\Models\RiderInvoiceTemplate;
use App\Models\RiderInvoices;
use App\Models\Riders;
use App\Models\Transactions;
use App\Models\VoucherType;
use App\Repositories\RiderInvoicesRepository;
use App\Services\Email\CompanyEmailBrandingService;
use App\Services\Email\UserEmailService;
use App\Services\RiderInvoice\RiderInvoiceTemplateResolver;
use App\Services\RiderInvoice\RiderInvoiceViewDataBuilder;
use App\Services\TransactionService;
use App\Support\CompanyQuery;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use App\Support\InvoicePdf;
use Carbon\Carbon;
use Flash;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class RiderInvoicesController extends AppBaseController
{
    use GlobalPagination, TracksCascadingDeletions;

    /** @var RiderInvoicesRepository */
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
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

        $query = $this->filteredRiderInvoicesQuery($request);
        $stats = $this->buildIndexStats($request, $query);

        $data = $this->applyPagination(clone $query, $paginationParams);
        if (method_exists($data, 'withQueryString')) {
            $data->withQueryString();
        }
        $data->load(['rider.customer', 'customer', 'items']);

        $invoiceBuilder = app(RiderInvoiceViewDataBuilder::class);
        // Paginator has items(); "all" used to return a bare Collection — support both.
        $pageRows = method_exists($data, 'items') ? collect($data->items()) : collect($data);
        $invoiceBuilder->preloadOutstandingSummaries($pageRows);

        if ($request->ajax()) {
            $tableHtml = view('rider_invoices.table', ['data' => $data])->render();
            $paginationHtml = view('components.riders-demo-pagination', [
                'paginator' => $data,
                'entityLabel' => 'invoices',
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $tableHtml,
                'pagination' => $paginationHtml,
                'stats' => $stats,
                'tableData' => $tableHtml,
                'paginationLinks' => $paginationHtml,
            ]);
        }

        return view('rider_invoices.index', [
            'data' => $data,
            'stats' => $stats,
            'invoiceTemplates' => app(RiderInvoiceTemplateResolver::class)->activeTemplates(),
        ]);
    }

    /**
     * Export rider invoices for the selected billing month / active filters.
     * Uses the same filters as the index listing (no pagination).
     */
    public function export(Request $request)
    {
        $invoices = $this->filteredRiderInvoicesQuery($request)
            ->with(['rider.customer', 'customer', 'items'])
            ->get();

        app(RiderInvoiceViewDataBuilder::class)->preloadOutstandingSummaries($invoices);

        $monthSuffix = $request->filled('billing_month')
            ? Carbon::parse($request->billing_month)->format('Y_m')
            : 'all';
        $filename = 'rider_invoices_'.$monthSuffix.'_'.now()->format('Y_m_d_His').'.xlsx';

        return Excel::download(new RiderInvoiceExport($invoices), $filename);
    }

    /**
     * Filter-scoped listing stats for summary cards.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\RiderInvoices>  $query
     * @return array<string, mixed>
     */
    protected function buildIndexStats(Request $request, $query): array
    {
        $statsQuery = clone $query;

        $invoiceCount = (int) (clone $statsQuery)->count();
        $paidCount = (int) (clone $statsQuery)->where('status', 1)->count();
        $partialCount = (int) (clone $statsQuery)->where('status', 3)->count();
        $unpaidCount = (int) (clone $statsQuery)->where(function ($q) {
            $q->whereNull('status')->orWhereNotIn('status', [1, 3]);
        })->count();
        $totalInvoiceAmount = round((float) (clone $statsQuery)->sum('total_amount'), 2);

        $projectLabel = 'All';
        if ($request->filled('customer_id')) {
            $projectLabel = (string) (company_table('customers')
                ->where('id', $request->customer_id)
                ->value('name') ?: 'Project');
        }

        $billingMonthLabel = 'All';
        if ($request->filled('billing_month')) {
            $billingMonthLabel = Carbon::parse($request->billing_month)->format('M Y');
        }

        $moneyRows = (clone $statsQuery)->with(['rider', 'items'])->get();
        app(RiderInvoiceViewDataBuilder::class)->preloadOutstandingSummaries($moneyRows);

        $payable = 0.0;
        $paidAmount = 0.0;
        $balance = 0.0;
        foreach ($moneyRows as $invoice) {
            $payable += (float) $invoice->final_amount;
            $paidAmount += (float) $invoice->paid_amount;
            $balance += (float) $invoice->balance;
        }

        return [
            'invoice_count' => $invoiceCount,
            'paid_count' => $paidCount,
            'partial_count' => $partialCount,
            'unpaid_count' => $unpaidCount,
            'total_invoice_amount' => $totalInvoiceAmount,
            'project_label' => $projectLabel,
            'billing_month_label' => $billingMonthLabel,
            'total_payable_salary' => round($payable, 2),
            'paid_amount' => round($paidAmount, 2),
            'balance' => round($balance, 2),
            'money_available' => true,
        ];
    }

    /**
     * AJAX Select2 search for the rider invoices filter (match rider_id or name).
     */
    public function searchRiders(Request $request)
    {
        $term = trim((string) $request->input('q', $request->input('term', '')));
        if ($term === '') {
            return response()->json(['results' => []]);
        }

        $like = '%'.$term.'%';
        $riders = Riders::query()
            ->select('id', 'rider_id', 'name')
            ->where(function ($q) use ($like) {
                $q->where('rider_id', 'like', $like)
                    ->orWhere('name', 'like', $like);
            })
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn (Riders $rider) => [
                'id' => $rider->id,
                'text' => trim(($rider->rider_id ?? '').'-'.($rider->name ?? ''), '-'),
            ])
            ->values()
            ->all();

        return response()->json(['results' => $riders]);
    }

    /**
     * Shared listing/export query with the same filters as the index page.
     */
    protected function filteredRiderInvoicesQuery(Request $request)
    {
        $query = RiderInvoices::query()
            ->orderBy('billing_month', 'desc')
            ->orderBy('status', 'asc')
            ->whereHas('rider');

        if ($request->filled('id')) {
            $rawId = trim((string) $request->input('id'));
            // Accept "9515", "RINV-9515", "RINV9515" → match numeric invoice id
            if (preg_match('/^(?:RINV-?)?0*(\d+)$/i', $rawId, $matches)) {
                $query->where('id', (int) $matches[1]);
            } elseif (preg_match('/(\d+)/', $rawId, $matches)) {
                $query->where('id', (int) $matches[1]);
            } else {
                $query->where('id', 'like', '%'.$rawId.'%');
            }
        }
        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->rider_id);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('billing_month')) {
            $billingMonth = Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)
                ->whereMonth('billing_month', $billingMonth->month);
        }
        if ($request->filled('zone')) {
            $query->where('zone', $request->zone);
        }
        if ($request->filled('performance')) {
            $query->where('performance', $request->performance);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('quick_search')) {
            $search = trim((string) $request->input('quick_search'));
            $query->where(function ($q) use ($search) {
                $q->where('rider_invoices.id', 'like', '%'.$search.'%')
                    ->orWhere('rider_invoices.descriptions', 'like', '%'.$search.'%')
                    ->orWhere('rider_invoices.zone', 'like', '%'.$search.'%')
                    ->orWhere('rider_invoices.notes', 'like', '%'.$search.'%')
                    ->orWhere('rider_invoices.performance', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('rider', function ($riderQuery) use ($search) {
                        $riderQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('rider_id', 'like', '%'.$search.'%')
                            ->orWhere('person_code', 'like', '%'.$search.'%')
                            ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                $customerQuery->where('name', 'like', '%'.$search.'%');
                            });
                    });

                if (preg_match('/^(?:RINV-?)?0*(\d+)$/i', $search, $matches)) {
                    $q->orWhere('rider_invoices.id', (int) $matches[1]);
                }
            });
        }

        return $query;
    }

    /**
     * Show the form for creating a new RiderInvoices.
     */
    public function create()
    {
        $riders = Riders::dropdown();
        $customers = Customers::dropdown();
        $riderCustomerMap = Riders::query()->pluck('customer_id', 'id');
        $items = Items::dropdown('rider');
        $invoiceTemplates = app(RiderInvoiceTemplateResolver::class)->activeTemplates();
        $defaultTemplate = app(RiderInvoiceTemplateResolver::class)->defaultTemplate();

        return view('rider_invoices.create', compact(
            'riders',
            'customers',
            'riderCustomerMap',
            'items',
            'invoiceTemplates',
            'defaultTemplate'
        ));
    }

    /**
     * Store a newly created RiderInvoices in storage.
     */
    public function store(CreateRiderInvoicesRequest $request)
    {
        try {
            $input = $request->all();

            $this->riderInvoicesRepository->record($request);
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Rider Invoice created successfully.',
                    'reload' => true,
                ]);
            }
            Flash::success('Rider Invoices saved successfully.');

            return redirect()->back();
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            Flash::error($e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    /**
     * HTML fragment for right-side modal loaders (jQuery .load treats non-2xx as error).
     */
    private function modalLoadError(string $message, int $status = 500)
    {
        return response()->view('partials.modal_load_error', [
            'message' => $message,
            'status' => $status,
        ], $status);
    }

    /**
     * Display the specified RiderInvoices.
     */
    public function show($company_slug, $id)
    {
        try {
            $riderInvoice = $this->riderInvoicesRepository->find($id);

            if (empty($riderInvoice)) {
                return $this->modalLoadError('Rider invoice not found.', 404);
            }

            $riderInvoice->load([
                'items',
                'customer',
                'rider' => function ($query) {
                    $query->withTrashed()->with(['sim', 'vendor', 'branch', 'bikes', 'account', 'customer']);
                },
            ]);

            if (RiderInvoiceTemplate::isSchemaReady()) {
                $riderInvoice->load('template');
            }

            if (! $riderInvoice->rider) {
                return $this->modalLoadError('Rider record not found for this invoice.', 404);
            }

            $resolver = app(RiderInvoiceTemplateResolver::class);
            $templateView = $resolver->resolveViewForInvoice($riderInvoice);

            if (! View::exists($templateView)) {
                $templateView = RiderInvoiceTemplate::FALLBACK_VIEW;
            }

            $builder = app(RiderInvoiceViewDataBuilder::class);
            $viewData = array_merge(
                $builder->build($riderInvoice),
                [
                    'riderInvoice' => $riderInvoice,
                    'activeTemplate' => $resolver->resolveForInvoice($riderInvoice),
                    'templateView' => $templateView,
                    'templates' => $resolver->activeTemplates(),
                    'paymentVouchers' => $builder->paymentVouchersForInvoice($riderInvoice),
                ]
            );

            return response(view('rider_invoices.show', $viewData)->render());
        } catch (\Throwable $e) {
            Log::error('Rider invoice show failed', [
                'invoice_id' => $id,
                'company_slug' => $company_slug,
                'message' => $e->getMessage(),
            ]);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'Unable to load rider invoice. Please deploy the latest code and run tenant migrations.';

            return $this->modalLoadError($message, 500);
        }
    }

    public function download($company_slug, $id)
    {
        $riderInvoice = $this->riderInvoicesRepository->find($id);

        if (empty($riderInvoice)) {
            abort(404, 'Rider Invoice not found');
        }

        $riderInvoice->load([
            'items',
            'customer',
            'rider' => function ($query) {
                $query->withTrashed()->with(['sim', 'vendor', 'branch', 'bikes', 'account', 'customer']);
            },
        ]);

        if (RiderInvoiceTemplate::isSchemaReady()) {
            $riderInvoice->load('template');
        }

        $resolver = app(RiderInvoiceTemplateResolver::class);
        $activeTemplate = $resolver->resolveForInvoice($riderInvoice);
        $invoiceNumber = \App\Helpers\General::inv_sch($riderInvoice->id, $riderInvoice->created_at);
        $templateView = $resolver->resolveViewForInvoice($riderInvoice);

        if (! View::exists($templateView)) {
            $templateView = RiderInvoiceTemplate::FALLBACK_VIEW;
        }

        $builder = app(RiderInvoiceViewDataBuilder::class);

        return InvoicePdf::download(
            'rider_invoices.show',
            array_merge(
                $builder->build($riderInvoice),
                [
                    'riderInvoice' => $riderInvoice,
                    'activeTemplate' => $activeTemplate,
                    'templateView' => $templateView,
                    'paymentVouchers' => $builder->paymentVouchersForInvoice($riderInvoice),
                ]
            ),
            'Rider-Invoice-' . $invoiceNumber . '.pdf'
        );
    }

    public function updateTemplate(Request $request, $company_slug, $id)
    {
        if (! RiderInvoiceTemplate::isSchemaReady()) {
            return response()->json(['message' => 'Invoice templates are not available yet.'], 422);
        }
        $riderInvoice = $this->riderInvoicesRepository->find($id);

        if (empty($riderInvoice)) {
            return response()->json(['message' => 'Rider Invoice not found'], 404);
        }

        $validated = $request->validate([
            'template_id' => ['required', 'integer', 'exists:rider_invoice_templates,id'],
        ]);

        $riderInvoice->template_id = $validated['template_id'];
        $riderInvoice->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'reload' => true]);
        }

        return redirect()->route('riderInvoices.show', ['company_slug' => $company_slug, 'riderInvoice' => $id]);
    }

    /**
     * Show the form for editing the specified RiderInvoices.
     */
    public function edit($company_slug, $id)
    {
        $invoice = $this->riderInvoicesRepository->find($id);

        if (empty($invoice)) {
            return response()->json([
                'message' => 'Rider Invoices not found',
            ], 404);
        }
        $riders = Riders::dropdown();
        $customers = Customers::dropdown();
        $riderCustomerMap = Riders::query()->pluck('customer_id', 'id');
        $items = Items::dropdown('rider');
        $invoiceTemplates = app(RiderInvoiceTemplateResolver::class)->activeTemplates();
        $defaultTemplate = app(RiderInvoiceTemplateResolver::class)->defaultTemplate();

        return view('rider_invoices.edit', compact(
            'riders',
            'customers',
            'riderCustomerMap',
            'items',
            'invoice',
            'invoiceTemplates',
            'defaultTemplate'
        ));
    }

    /**
     * Update the specified RiderInvoices in storage.
     */
    public function update($company_slug, $id, UpdateRiderInvoicesRequest $request)
    {
        try {
            $riderInvoices = $this->riderInvoicesRepository->find($id);

            if (empty($riderInvoices)) {
                return response()->json([
                    'message' => 'Rider Invoices not found',
                ], 404);
            }

            $riderInvoices = $this->riderInvoicesRepository->record($request, $id);

            return response()->json([
                'message' => 'Rider Invoices updated successfully.',
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);

            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified RiderInvoices from storage.
     *
     * @throws \Exception
     */
    public function destroy($company_slug, $id)
    {
        $riderInvoices = $this->riderInvoicesRepository->find($id);

        if (empty($riderInvoices)) {
            return response()->json([
                'message' => 'Rider Invoices not found',
            ], 404);
        }

        if ($this->invoiceHasPayment($riderInvoices)) {
            return response()->json([
                'message' => 'Cannot Delete Invoice. Payment has already been Made',
            ], 500);
        }

        DB::beginTransaction();
        try {
            // Get all transactions for this specific invoice (both 'Invoice' and 'RiderInvoice' reference types)
            $invoiceTransactions = Transactions::where(function ($query) {
                $query->where('reference_type', 'Invoice')
                    ->orWhere('reference_type', 'RiderInvoice');
            })
                ->where('reference_id', $id)
                ->get();

            if ($invoiceTransactions->count() > 0) {
                // Get unique account and billing month combinations for ledger recalculation
                $affectedAccountsData = $invoiceTransactions
                    ->unique(function ($transaction) {
                        return $transaction->account_id . '-' . $transaction->billing_month;
                    })
                    ->map(function ($transaction) {
                        return [
                            'account_id' => $transaction->account_id,
                            'billing_month' => $transaction->billing_month,
                        ];
                    });

                // Store invoice name for cascade logging
                $invoiceName = "Rider Invoice #{$id} - " . ($riderInvoices->rider->name ?? 'Unknown Rider');

                // Delete only transactions for this specific invoice and log cascade
                foreach ($invoiceTransactions as $transaction) {
                    // Log cascade deletion for each transaction
                    $this->trackCascadeDeletion(
                        RiderInvoices::class,
                        $id,
                        $invoiceName,
                        Transactions::class,
                        $transaction->id,
                        "Transaction #{$transaction->id} - {$transaction->narration}",
                        'hasMany',
                        'transactions',
                        'hard' // Transactions are hard deleted
                    );

                    // Hard delete the transaction
                    $transaction->forceDelete();
                }

                // ✅ Recalculate ledger for all affected accounts
                foreach ($affectedAccountsData as $transData) {
                    if ($transData['account_id'] && $transData['billing_month']) {
                        $this->recalculateLedgerAfterDeletion($transData['account_id'], $transData['billing_month']);
                    }
                }
            }

            // Delete related rider invoice items
            CompanyQuery::table('rider_invoice_items')->where('inv_id', $id)->delete();

            // Set deleted_by and soft delete the invoice
            $riderInvoices->deleted_by = Auth::id();
            $riderInvoices->save();
            $riderInvoices->delete();

            DB::commit();
            Flash::success(delete_outcome_message(
                'Rider invoice',
                route('settings-panel.trash.index') . '?module=rider_invoices'
            ));
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error deleting Rider Invoice ID: {$id} - " . $e->getMessage());
            Flash::error('Error deleting Rider Invoice: ' . $e->getMessage());
        }

        return redirect(route('riderInvoices.index'));
    }

    /**
     * Whether the invoice has a payment recorded and must not be deleted.
     */
    private function invoiceHasPayment(RiderInvoices $invoice): bool
    {
        if ($invoice->isPaid()) {
            return true;
        }

        // Manual mark-as-paid / paid-import GL (reference_type RiderInvoice)
        if (Transactions::where('reference_type', 'RiderInvoice')
            ->where('reference_id', $invoice->id)
            ->exists()) {
            return true;
        }

        $invoiceNumber = $invoice->invoice_number; // RINV-0001
        $accountId = $invoice->rider?->account_id;
        $invoiceRefPatterns = array_values(array_filter([
            $invoiceNumber,
            'Rider Invoice #'.$invoice->id,
            'Invoice #'.$invoice->id,
            'RINV-'.str_pad((string) $invoice->id, 4, '0', STR_PAD_LEFT),
        ]));

        // Payment module records (payments table) linked to this invoice
        if ($accountId && ! empty($invoiceRefPatterns)) {
            $hasPaymentRecord = Payment::where('payee_account_id', $accountId)
                ->where(function ($q) use ($invoiceRefPatterns) {
                    foreach ($invoiceRefPatterns as $pattern) {
                        $q->orWhere('reference', 'like', '%'.$pattern.'%')
                            ->orWhere('description', 'like', '%'.$pattern.'%');
                    }
                })
                ->exists();

            if ($hasPaymentRecord) {
                return true;
            }
        }

        // Payment vouchers: RI (mark-as-paid) and PV (Payment module)
        return CompanyQuery::table('vouchers')
            ->whereIn('voucher_type', ['RI', 'PV'])
            ->where(function ($q) use ($invoice, $invoiceRefPatterns) {
                $q->where('remarks', 'like', '%Rider Invoice #'.$invoice->id.'%')
                    ->orWhere('remarks', 'like', '%Invoice #'.$invoice->id.'%');

                foreach ($invoiceRefPatterns as $pattern) {
                    $q->orWhere('reference_number', 'like', '%'.$pattern.'%')
                        ->orWhere('remarks', 'like', '%'.$pattern.'%');
                }
            })
            ->exists();
    }

    /**
     * Recalculate ledger entries after deletion
     * This ensures ledger integrity without deleting all entries
     */
    private function recalculateLedgerAfterDeletion($accountId, $billingMonth)
    {
        // Delete only the ledger entry for this specific billing month
        CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->delete();

        // Get the last ledger entry before this billing month
        $lastLedger = CompanyQuery::table('ledger_entries')
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
            CompanyQuery::insert('ledger_entries', [
                'account_id' => $accountId,
                'billing_month' => $billingMonth,
                'opening_balance' => $openingBalance,
                'debit_balance' => $debitTotal,
                'credit_balance' => $creditTotal,
                'closing_balance' => $closingBalance,
                'created_at' => now(),
                'updated_at' => now(),
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
        if (! user_can('riderinvoice_delete')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete invoices.',
            ], 403);
        }

        try {
            $invoiceIds = $request->input('invoice_ids', []);

            if (empty($invoiceIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No invoices selected for deletion.',
                ], 400);
            }

            $deletedCount = 0;
            $skippedCount = 0;
            $errors = [];
            $skippedInvoices = [];

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

                        // ✅ Skip invoices that have payment (paid / partial / payment GL / voucher)
                        if ($this->invoiceHasPayment($riderInvoice)) {
                            $skippedCount++;
                            $skippedInvoices[] = "Invoice ID {$invoiceId} (payment exists)";

                            continue;
                        }

                        // Get all transactions for this specific invoice (both 'Invoice' and 'RiderInvoice' reference types)
                        $invoiceTransactions = Transactions::where(function ($query) {
                            $query->where('reference_type', 'Invoice')
                                ->orWhere('reference_type', 'RiderInvoice');
                        })
                            ->where('reference_id', $invoiceId)
                            ->get();

                        if ($invoiceTransactions->count() > 0) {
                            // Get unique account and billing month combinations for ledger recalculation
                            $affectedAccountsData = $invoiceTransactions
                                ->unique(function ($transaction) {
                                    return $transaction->account_id . '-' . $transaction->billing_month;
                                })
                                ->map(function ($transaction) {
                                    return [
                                        'account_id' => $transaction->account_id,
                                        'billing_month' => $transaction->billing_month,
                                    ];
                                });

                            // Store invoice name for cascade logging
                            $invoiceName = "Rider Invoice #{$invoiceId} - " . ($riderInvoice->rider->name ?? 'Unknown Rider');

                            // Delete only transactions for this specific invoice and log cascade
                            foreach ($invoiceTransactions as $transaction) {
                                // Log cascade deletion for each transaction
                                $this->trackCascadeDeletion(
                                    RiderInvoices::class,
                                    $invoiceId,
                                    $invoiceName,
                                    Transactions::class,
                                    $transaction->id,
                                    "Transaction #{$transaction->id} - {$transaction->narration}",
                                    'hasMany',
                                    'transactions',
                                    'hard' // Transactions are hard deleted
                                );

                                // Hard delete the transaction
                                $transaction->forceDelete();
                            }

                            // ✅ Recalculate ledger for all affected accounts
                            foreach ($affectedAccountsData as $transData) {
                                if ($transData['account_id'] && $transData['billing_month']) {
                                    $this->recalculateLedgerAfterDeletion($transData['account_id'], $transData['billing_month']);
                                }
                            }
                        }

                        // Delete related rider invoice items
                        CompanyQuery::table('rider_invoice_items')->where('inv_id', $invoiceId)->delete();

                        // Set deleted_by and soft delete the invoice
                        $riderInvoice->deleted_by = Auth::id();
                        $riderInvoice->save();
                        $riderInvoice->delete();

                        $deletedCount++;
                    } catch (\Exception $e) {
                        $errors[] = "Failed to delete invoice ID {$invoiceId}: " . $e->getMessage();
                    }
                }

                // Commit transaction if all deletions were successful
                DB::commit();

                // Build response message
                $messageParts = [];
                if ($deletedCount > 0) {
                    $messageParts[] = "Successfully deleted {$deletedCount} invoice(s).";
                }
                if ($skippedCount > 0) {
                    $messageParts[] = "Skipped {$skippedCount} invoice(s) with payment (cannot be deleted).";
                }
                if (! empty($errors)) {
                    $messageParts[] = 'Errors: ' . implode(', ', $errors);
                }

                $message = implode(' ', $messageParts);

                if ($deletedCount > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'deleted_count' => $deletedCount,
                        'skipped_count' => $skippedCount,
                        'skipped_invoices' => $skippedInvoices,
                        'errors' => $errors,
                    ]);
                } else {
                    $statusCode = ($skippedCount > 0 || ! empty($errors)) ? 400 : 400;

                    return response()->json([
                        'success' => false,
                        'message' => $message ?: 'No invoices were deleted.',
                        'deleted_count' => 0,
                        'skipped_count' => $skippedCount,
                        'skipped_invoices' => $skippedInvoices,
                        'errors' => $errors,
                    ], $statusCode);
                }
            } catch (\Exception $e) {
                // Rollback transaction on any error
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during bulk deletion: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request)
    {
        if ($request->isMethod('post')) {
            $rules = [
                'file' => 'required|max:50000|mimes:xlsx',
                'customer_id' => 'required',
            ];
            $message = [
                'file.required' => 'Excel File Required',
                'customer_id.required' => 'Project is required',
            ];
            $this->validate($request, $rules, $message);
            try {

                Excel::import(new ImportRiderInvoice((int) $request->customer_id), $request->file('file'));

                return response()->json([
                    'success' => true,
                    'message' => 'Invoices imported successfully.',
                    'reload' => true,
                ]);
            } catch (ValidationException $e) {

                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error: ' . $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            } catch (QueryException $e) {

                return response()->json([
                    'success' => false,
                    'message' => 'Database Error: ' . $e->getMessage(),
                ], 500);
            } catch (\Exception $e) {

                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred: ' . $e->getMessage(),
                ], 500);
            }
        }

        $customers = Customers::dropdown();

        return view('rider_invoices.import', compact('customers'));
    }

    /**
     * Import paid rider invoices from Excel
     */
    public function importPaid(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'file' => 'required|max:50000|mimes:xlsx,csv,xls',
                'col_invoice_number' => 'required|integer|min:1',
                'col_rider_id' => 'nullable|integer|min:1',
                'col_billing_month' => 'nullable|integer|min:1',
                'col_account_code' => 'required|integer|min:1',
                'col_amount' => 'required|integer|min:1',
                'col_payment_date' => 'required|integer|min:1',
                'col_description' => 'required|integer|min:1',
            ], [
                'file.required' => 'Excel File Required',
                'col_invoice_number.required' => 'Map the Invoice Number column.',
                'col_account_code.required' => 'Map the paying account code column.',
                'col_amount.required' => 'Map the Amount column.',
                'col_payment_date.required' => 'Map the Payment Date column.',
                'col_description.required' => 'Map the Description / Narration column.',
            ]);

            $columnMap = [
                'invoice_number' => (int) $request->col_invoice_number,
                'account_code' => (int) $request->col_account_code,
                'amount' => (int) $request->col_amount,
                'payment_date' => (int) $request->col_payment_date,
                'description' => (int) $request->col_description,
            ];
            if ($request->filled('col_rider_id')) {
                $columnMap['rider_id'] = (int) $request->col_rider_id;
            }
            if ($request->filled('col_billing_month')) {
                $columnMap['billing_month'] = (int) $request->col_billing_month;
            }

            $provided = array_filter($columnMap, fn ($v) => $v !== null && $v !== '');
            if (count($provided) !== count(array_unique($provided))) {
                $message = 'Column numbers must be unique.';
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                Flash::error($message);

                return redirect()->back();
            }

            try {
                $import = new ImportPaidRiderInvoice($columnMap);
                Excel::import($import, $request->file('file'));
                $importedCount = $import->importedCount;
                $skippedLog = $import->skippedLog;
                $skippedCount = count($skippedLog);

                $message = "Import finished. Successfully imported: {$importedCount}.";
                if ($skippedCount > 0) {
                    $message .= " Failed: {$skippedCount}.";
                }

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'imported_count' => $importedCount,
                        'skipped_count' => $skippedCount,
                        'skipped_log' => array_values($skippedLog),
                    ]);
                }

                Flash::success($message);

                return redirect()->route('riderInvoices.index');
            } catch (ValidationException $e) {
                throw $e;
            } catch (\Exception $e) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error importing paid invoices: '.$e->getMessage(),
                    ], 500);
                }
                Flash::error('Error importing paid invoices: '.$e->getMessage());

                return redirect()->back();
            }
        }

        return view('rider_invoices.import_paid');
    }

    /**
     * Mark invoice settled for reporting without recording a cash payment.
     * Use when advances/penalties already absorb the payable (or policy says do not pay).
     */
    public function markAsSettled(Request $request, $company_slug, $id)
    {
        $invoice = RiderInvoices::with(['rider', 'items'])->find($id);
        if (! $invoice) {
            Flash::error('Invoice not found.');

            return redirect()->route('riderInvoices.index');
        }

        if ((int) $invoice->status === 1) {
            Flash::error('Invoice is already marked as paid.');

            return redirect()->back();
        }

        $outstanding = app(RiderInvoiceViewDataBuilder::class)->outstandingAmounts($invoice);
        $balance = round((float) ($outstanding['balance'] ?? 0), 2);
        $needsReason = $balance > 0.01;

        if ($request->isMethod('post')) {
            $this->validate($request, [
                'reason' => ($needsReason ? 'required' : 'nullable').'|string|max:255',
            ], [
                'reason.required' => 'A reason is required when settling an invoice that still has a positive payable balance.',
            ]);

            $invoice->status = 1;
            $invoice->notes = $this->appendSettlementAuditNote(
                (string) ($invoice->notes ?? ''),
                $balance,
                $request->input('reason')
            );
            $invoice->save();

            $message = 'Invoice marked as settled. No payment or bank voucher was recorded.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            Flash::success($message);

            return redirect()->route('riderInvoices.index');
        }

        return view('rider_invoices.mark_as_settled', compact('invoice', 'outstanding', 'needsReason'));
    }

    /**
     * Mark a single invoice as paid manually (cash settlement via bank voucher).
     */
    public function markAsPaid(Request $request, $company_slug, $id)
    {
        if ($request->isMethod('post')) {
            $rules = [
                'bank_account_id' => 'required|exists:accounts,id',
                'payment_amount' => 'required|numeric|min:0.01',
            ];
            $message = [
                'bank_account_id.required' => 'Bank account is required',
                'bank_account_id.exists' => 'Selected bank account does not exist',
                'payment_amount.required' => 'Payment amount is required',
                'payment_amount.numeric' => 'Payment amount must be a valid number',
                'payment_amount.min' => 'Payment amount must be at least 0.01',
            ];

            $this->validate($request, $rules, $message);

            try {
                \DB::beginTransaction();

                // Find the invoice
                $invoice = RiderInvoices::find($id);
                if (! $invoice) {
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
                if (! $rider) {
                    Flash::error('Rider not found.');

                    return redirect()->back();
                }

                $paymentAmount = (float) $request->input('payment_amount', 0);
                $dueAmount = max($invoice->total_amount - $invoice->paid_amount, 0);
                if ($paymentAmount <= 0 || $paymentAmount > $dueAmount) {
                    Flash::error('Payment amount must be greater than 0 and not exceed the remaining invoice due amount.');

                    return redirect()->back()->withInput();
                }

                $invoice->status = 1;
                $invoice->save();

                // Create voucher entries for the payment amount
                $this->createManualPaymentVoucher($invoice, $rider, $request->bank_account_id, $paymentAmount);

                \DB::commit();
                Flash::success('Invoice marked as paid successfully.');
            } catch (\Exception $e) {
                \DB::rollBack();
                Flash::error('Error marking invoice as paid: ' . $e->getMessage());
            }

            return redirect()->back();
        }

        // GET request - show payment form
        $invoice = RiderInvoices::with(['rider', 'items'])->find($id);
        if (! $invoice) {
            Flash::error('Invoice not found.');

            return redirect()->back();
        }

        if ($invoice->status == 1) {
            Flash::error('Invoice is already marked as paid.');

            return redirect()->back();
        }

        $outstanding = app(RiderInvoiceViewDataBuilder::class)->outstandingAmounts($invoice);
        $netBalance = round((float) ($outstanding['balance'] ?? 0), 2);
        if ($netBalance <= 0.01) {
            Flash::warning('Net payable is zero or negative after deductions. Use Mark Settled (no payment) from the invoice list instead of recording a bank payment.');

            return redirect()->route('riderInvoices.index');
        }

        // Get bank accounts for dropdown
        $bankAccounts = Accounts::bankAccountsDropdown();

        return view('rider_invoices.mark_as_paid', compact('invoice', 'bankAccounts'));
    }

    /**
     * Append a short settlement audit line to invoice notes (max 500 chars).
     */
    private function appendSettlementAuditNote(string $existingNotes, float $balance, ?string $reason): string
    {
        $user = Auth::user()->name ?? (string) Auth::id();
        $ts = now()->format('Y-m-d H:i');
        $line = sprintf(
            '[Settled %s by %s; balance %s%s]',
            $ts,
            $user,
            number_format($balance, 2, '.', ''),
            $reason !== null && $reason !== '' ? '; reason: '.$reason : ''
        );

        $combined = trim($existingNotes === '' ? $line : $existingNotes."\n".$line);
        if (strlen($combined) > 500) {
            $combined = substr($combined, -500);
        }

        return $combined;
    }

    /**
     * Create voucher entries for manual payment
     */
    private function createManualPaymentVoucher($invoice, $rider, $bankAccountId, $paymentAmount)
    {
        $transactionService = new TransactionService;
        $trans_code = Account::trans_code();
        $totalAmount = $paymentAmount;
        $invoiceDate = now()->format('Y-m-d');
        $billingMonth = $invoice->billing_month;

        // Debit rider account
        $transactionDataDebit = [
            'account_id' => $rider->account_id,
            'reference_id' => $invoice->id,
            'reference_type' => 'RiderInvoice',
            'trans_code' => $trans_code,
            'trans_date' => $invoiceDate,
            'narration' => 'Manual payment for Rider Invoice #' . $invoice->id . ' - ' . ($invoice->descriptions ?? 'Manual Payment'),
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
            'narration' => 'Manual payment received for Rider Invoice #' . $invoice->id . ' - ' . ($invoice->descriptions ?? 'Manual Payment'),
            'debit' => 0,
            'credit' => $totalAmount,
            'billing_month' => $billingMonth,
        ];
        $transactionService->recordTransaction($transactionDataCredit);

        if (! VoucherType::isCodeAllowedForModule('RI', 'riders_list')) {
            throw new \Exception('Rider Invoice voucher type (RI) is not assigned to the Riders List module. Please assign it in Voucher Settings.');
        }

        // Create voucher record — link to related rider so it appears under the rider
        $voucherData = [
            'trans_date' => $invoiceDate,
            'voucher_type' => 'RI', // Rider Invoice Payment Voucher
            'payment_type' => 1,
            'payment_from' => $bankAccountId,
            'billing_month' => $billingMonth,
            'amount' => $totalAmount,
            'trans_code' => $trans_code,
            'Created_By' => \Auth::user()->id,
            'remarks' => 'Manual payment for Rider Invoice #' . $invoice->id,
            'reference_number' => $invoice->invoice_number
                ?? ('RINV-'.str_pad((string) $invoice->id, 4, '0', STR_PAD_LEFT)),
            'ref_id' => $rider->id,
            'rider_id' => $rider->id,
            'status' => 1,
            'branch_id' => $rider->branch_id ?? $invoice->branch_id ?? null,
            'company_id' => $invoice->company_id ?? $rider->company_id ?? null,
        ];

        CompanyQuery::insert('vouchers', $voucherData);
    }

    public function sendEmail($company_slug, $id, Request $request)
    {
        $invoice = RiderInvoices::with(['rider'])->findOrFail($id);

        if ($request->isMethod('post')) {
            $user = Auth::user();
            $emailService = app(UserEmailService::class);
            $smtpPrep = $emailService->prepareCompanySmtp($user);
            if (! $smtpPrep['ready']) {
                return response()->json([
                    'success' => false,
                    'message' => $smtpPrep['message'],
                ], $smtpPrep['status'] ?? 422);
            }
            $fromEmail = $smtpPrep['from_email'];
            $fromName = $smtpPrep['from_name'];

            $toEmail = $request->input('email_to');
            if (! is_string($toEmail) || trim($toEmail) === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Rider invoice email address is missing.',
                ], 422);
            }
            $toEmail = trim($toEmail);

            $subject = is_string($request->input('email_subject')) && trim($request->input('email_subject')) !== ''
                ? trim($request->input('email_subject'))
                : 'Rider Invoice';

            $brandingService = app(CompanyEmailBrandingService::class);
            $data = $brandingService->mergeIntoMailData([
                'html' => $request->input('email_message'),
            ]);

            $invoice->load([
                'items',
                'customer',
                'rider' => function ($query) {
                    $query->withTrashed()->with(['sim', 'vendor', 'branch', 'bikes', 'account', 'customer']);
                },
            ]);

            if (RiderInvoiceTemplate::isSchemaReady()) {
                $invoice->load('template');
            }

            $resolver = app(RiderInvoiceTemplateResolver::class);
            $activeTemplate = $resolver->resolveForInvoice($invoice);
            $invoiceNumber = \App\Helpers\General::inv_sch($invoice->id, $invoice->created_at);
            $templateView = $resolver->resolveViewForInvoice($invoice);

            if (! View::exists($templateView)) {
                $templateView = RiderInvoiceTemplate::FALLBACK_VIEW;
            }

            $builder = app(RiderInvoiceViewDataBuilder::class);
            $pdf = InvoicePdf::make(
                'rider_invoices.show',
                array_merge(
                    $builder->build($invoice),
                    [
                        'riderInvoice' => $invoice,
                        'activeTemplate' => $activeTemplate,
                        'templateView' => $templateView,
                        'paymentVouchers' => $builder->paymentVouchersForInvoice($invoice),
                    ]
                )
            );

            $brandingService->sendBrandedEmail('emails.general', $data, function ($message) use ($toEmail, $pdf, $fromEmail, $fromName, $subject, $invoiceNumber) {
                $message->to([$toEmail]);
                $message->from($fromEmail, $fromName);
                $message->replyTo($fromEmail, $fromName);
                $message->subject($subject);
                $message->attachData($pdf->output(), 'Rider-Invoice-' . $invoiceNumber . '.pdf');
                $message->priority(3);
            });

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully.',
            ]);
        }

        return view('rider_invoices.send_email', compact('invoice'));
    }
}
