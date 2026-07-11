<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Http\Requests\CreateRiderInvoicesRequest;
use App\Http\Requests\UpdateRiderInvoicesRequest;
use App\Imports\ImportPaidRiderInvoice;
use App\Imports\ImportRiderInvoice;
use App\Models\Accounts;
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
        $this->middleware('auth');
        $this->middleware('permission:riders_invoices_view')->only('index', 'show', 'download');
        $this->middleware('permission:riders_invoices_create')->only('create', 'store', 'import', 'importPaid');
        $this->middleware('permission:riders_invoices_edit')->only('edit', 'update', 'import', 'importPaid');
        $this->middleware('permission:riders_invoices_delete')->only('destroy', 'bulkDelete');
        $this->middleware('permission:email_create')->only('sendEmail');
        $this->middleware('permission:riders_payments_create')->only('markAsPaid');
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
        $query->whereHas('rider');
        // Filters
        if ($request->has('id') && ! empty($request->id)) {
            $query->where('id', 'like', '%' . $request->id . '%');
        }
        if ($request->has('rider_id') && ! empty($request->rider_id)) {
            $query->where('rider_id', $request->rider_id);
        }
        if ($request->has('billing_month') && ! empty($request->billing_month)) {
            $billingMonth = Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)
                ->whereMonth('billing_month', $billingMonth->month);
        }
        if ($request->has('zone') && ! empty($request->zone)) {
            $query->where('zone', $request->zone);
        }
        if ($request->has('performance') && ! empty($request->performance)) {
            $query->where('performance', $request->performance);
        }
        if ($request->has('status') && ! empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);

        // ✅ Billing month ka check for total calculation
        $billingMonth = $request->has('billing_month') && ! empty($request->billing_month)
            ? Carbon::parse($request->billing_month)
            : now();

        $currentMonthTotal = RiderInvoices::whereYear('billing_month', $billingMonth->year)
            ->whereMonth('billing_month', $billingMonth->month)
            ->sum('total_amount');

        // ✅ AJAX Response
        if ($request->ajax()) {
            $tableData = view('rider_invoices.table', [
                'data' => $data,
                'currentMonthTotal' => $currentMonthTotal,
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
            'invoiceTemplates' => app(RiderInvoiceTemplateResolver::class)->activeTemplates(),
        ]);
    }

    /**
     * Show the form for creating a new RiderInvoices.
     */
    public function create()
    {
        $riders = Riders::dropdown();
        $items = Items::dropdown('rider');
        $invoiceTemplates = app(RiderInvoiceTemplateResolver::class)->activeTemplates();
        $defaultTemplate = app(RiderInvoiceTemplateResolver::class)->defaultTemplate();

        return view('rider_invoices.create', compact('riders', 'items', 'invoiceTemplates', 'defaultTemplate'));
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
                'rider' => function ($query) {
                    $query->withTrashed()->with(['sim', 'vendor']);
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

            $viewData = array_merge(
                app(RiderInvoiceViewDataBuilder::class)->build($riderInvoice),
                [
                    'riderInvoice' => $riderInvoice,
                    'activeTemplate' => $resolver->resolveForInvoice($riderInvoice),
                    'templateView' => $templateView,
                    'templates' => $resolver->activeTemplates(),
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
            'rider' => function ($query) {
                $query->withTrashed()->with(['sim', 'vendor']);
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

        $pdf = \PDF::loadView('rider_invoices.pdf', array_merge(
            app(RiderInvoiceViewDataBuilder::class)->build($riderInvoice),
            [
                'riderInvoice' => $riderInvoice,
                'activeTemplate' => $activeTemplate,
                'templateView' => $templateView,
            ]
        ));

        return $pdf->download('Rider-Invoice-' . $invoiceNumber . '.pdf');
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
        $items = Items::dropdown('rider');
        $invoiceTemplates = app(RiderInvoiceTemplateResolver::class)->activeTemplates();
        $defaultTemplate = app(RiderInvoiceTemplateResolver::class)->defaultTemplate();

        return view('rider_invoices.edit', compact('riders', 'items', 'invoice', 'invoiceTemplates', 'defaultTemplate'));
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
        $payment = Payment::where('payee_account_id', $riderInvoices->rider->account_id)
            ->where('reference', 'like', '%' . $riderInvoices->inv_number . '%')
            ->exists();
        if ($payment) {
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
        if (! auth()->user()->can('riders_invoices_delete')) {
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

                        // ✅ Check if invoice is paid - skip paid invoices
                        if ($riderInvoice->status == 1) {
                            $skippedCount++;
                            $skippedInvoices[] = "Invoice ID {$invoiceId} (paid)";

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
                    $messageParts[] = "Skipped {$skippedCount} paid invoice(s) (cannot be deleted).";
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
            ];
            $message = [
                'file.required' => 'Excel File Required',
            ];
            $this->validate($request, $rules, $message);
            try {

                Excel::import(new ImportRiderInvoice, $request->file('file'));

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

        return view('rider_invoices.import');
    }

    /**
     * Import paid rider invoices from Excel
     */
    public function importPaid(Request $request)
    {
        if ($request->isMethod('post')) {
            $rules = [
                'file' => 'required|max:50000|mimes:xlsx',
            ];
            $message = [
                'file.required' => 'Excel File Required',
            ];

            $this->validate($request, $rules, $message);

            try {
                Excel::import(new ImportPaidRiderInvoice, $request->file('file'));
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

                // Update invoice status based on remaining due
                $newTotalPaid = $invoice->paid_amount + $paymentAmount;
                $invoice->status = $newTotalPaid >= $invoice->total_amount ? 1 : 3;
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
        $invoice = RiderInvoices::with('rider')->find($id);
        if (! $invoice) {
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
            'remarks' => 'Manual payment for Rider Invoice #' . $invoice->id,
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
                'rider' => function ($query) {
                    $query->withTrashed()->with(['sim', 'vendor']);
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

            $pdf = \PDF::loadView('rider_invoices.pdf', array_merge(
                app(RiderInvoiceViewDataBuilder::class)->build($invoice),
                [
                    'riderInvoice' => $invoice,
                    'activeTemplate' => $activeTemplate,
                    'templateView' => $templateView,
                ]
            ));

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
