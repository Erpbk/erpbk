<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeInvoiceExport;
use App\Helpers\Account;
use App\Http\Requests\CreateEmployeeInvoicesRequest;
use App\Http\Requests\UpdateEmployeeInvoicesRequest;
use App\Imports\ImportEmployeeInvoice;
use App\Models\Accounts;
use App\Models\Banks;
use App\Models\Employee;
use App\Models\EmployeeInvoices;
use App\Models\Items;
use App\Models\Payment;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Repositories\EmployeeInvoicesRepository;
use App\Services\Email\CompanyEmailBrandingService;
use App\Services\Email\UserEmailService;
use App\Services\EmployeeInvoice\EmployeeInvoiceViewDataBuilder;
use App\Support\EmployeeVoucherPermissions;
use App\Support\InvoicePdf;
use App\Support\ResourceLinks;
use App\Traits\GlobalPagination;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeInvoicesController extends AppBaseController
{
    use GlobalPagination;

    private $employeeInvoicesRepository;

    public function __construct(EmployeeInvoicesRepository $employeeInvoicesRepo)
    {
        $this->employeeInvoicesRepository = $employeeInvoicesRepo;
        $this->middleware('permission:employees_invoice_view')->only('index', 'show', 'download', 'export', 'sendEmail');
        $this->middleware('permission:employees_invoice_create')->only('create', 'store', 'importForm', 'import');
        $this->middleware('permission:employees_invoice_edit')->only('edit', 'update', 'markAsSettled');
        $this->middleware('permission:employees_invoice_delete')->only('destroy', 'bulkDelete');
        $this->middleware('permission:employees_payments_create')->only('markAsPaid');
    }

    public function index(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = $this->filteredEmployeeInvoicesQuery($request);
        $stats = $this->buildIndexStats($request, $query);

        $data = $this->applyPagination(clone $query, $paginationParams);
        if (method_exists($data, 'withQueryString')) {
            $data->withQueryString();
        }
        $data->load(['employee', 'items']);

        $invoiceBuilder = app(EmployeeInvoiceViewDataBuilder::class);
        $pageRows = method_exists($data, 'items') ? collect($data->items()) : collect($data);
        $invoiceBuilder->preloadOutstandingSummaries($pageRows);

        if ($request->ajax()) {
            $tableHtml = view('employee_invoices.table', ['data' => $data])->render();
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

        return view('employee_invoices.index', [
            'data' => $data,
            'stats' => $stats,
        ]);
    }

    /**
     * Export employee invoices for the active index filters (no pagination).
     */
    public function export(Request $request)
    {
        $invoices = $this->filteredEmployeeInvoicesQuery($request)
            ->with(['employee', 'items'])
            ->get();

        app(EmployeeInvoiceViewDataBuilder::class)->preloadOutstandingSummaries($invoices);

        $monthSuffix = $request->filled('billing_month')
            ? Carbon::parse($request->billing_month)->format('Y_m')
            : 'all';
        $filename = 'employee_invoices_'.$monthSuffix.'_'.now()->format('Y_m_d_His').'.xlsx';

        return Excel::download(new EmployeeInvoiceExport($invoices), $filename);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\EmployeeInvoices>  $query
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

        $zoneLabel = 'All';
        if ($request->filled('zone')) {
            $zoneLabel = (string) $request->input('zone');
        }

        $billingMonthLabel = 'All';
        if ($request->filled('billing_month')) {
            $billingMonthLabel = Carbon::parse($request->billing_month)->format('M Y');
        }

        $moneyRows = (clone $statsQuery)->with(['employee', 'items'])->get();
        app(EmployeeInvoiceViewDataBuilder::class)->preloadOutstandingSummaries($moneyRows);

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
            'project_label' => $zoneLabel,
            'billing_month_label' => $billingMonthLabel,
            'total_payable_salary' => round($payable, 2),
            'paid_amount' => round($paidAmount, 2),
            'balance' => round($balance, 2),
            'money_available' => true,
        ];
    }

    /**
     * Shared listing/export query with the same filters as the index page.
     */
    protected function filteredEmployeeInvoicesQuery(Request $request)
    {
        $query = EmployeeInvoices::query()
            ->orderBy('billing_month', 'desc')
            ->orderBy('status', 'asc');

        if ($request->filled('id')) {
            $rawId = trim((string) $request->input('id'));
            if (preg_match('/^(?:EMP[_-]?INV-?)?0*(\d+)$/i', $rawId, $matches)) {
                $query->where('id', (int) $matches[1]);
            } elseif (preg_match('/(\d+)/', $rawId, $matches)) {
                $query->where('id', (int) $matches[1]);
            } else {
                $query->where('id', 'like', '%'.$rawId.'%');
            }
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
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
                $q->where('employee_invoices.id', 'like', '%'.$search.'%')
                    ->orWhere('employee_invoices.descriptions', 'like', '%'.$search.'%')
                    ->orWhere('employee_invoices.zone', 'like', '%'.$search.'%')
                    ->orWhere('employee_invoices.notes', 'like', '%'.$search.'%')
                    ->orWhere('employee_invoices.performance', 'like', '%'.$search.'%')
                    ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                        $employeeQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('employee_id', 'like', '%'.$search.'%');
                    });

                if (preg_match('/^(?:EMP[_-]?INV-?)?0*(\d+)$/i', $search, $matches)) {
                    $q->orWhere('employee_invoices.id', (int) $matches[1]);
                }
            });
        }

        return $query;
    }

    public function create()
    {
        $employees = Employee::dropdown();
        $items = Items::dropdown('employee');

        return view('employee_invoices.create', compact('employees', 'items'));
    }

    public function store(CreateEmployeeInvoicesRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->employeeInvoicesRepository->record($request);
            DB::commit();
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Employee invoice saved successfully.']);
            }
            Flash::success('Employee invoice saved successfully.');

            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            Flash::error($e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function show($comapny_slug, $id)
    {
        $viewData = $this->invoiceViewData($id);
        if ($viewData instanceof \Illuminate\Http\RedirectResponse) {
            return $viewData;
        }

        return response(view('employee_invoices.show', $viewData)->render());
    }

    public function download($company_slug, $id)
    {
        $viewData = $this->invoiceViewData($id);
        if ($viewData instanceof \Illuminate\Http\RedirectResponse) {
            abort(404, 'Employee Invoice not found');
        }

        $employeeInvoice = $viewData['employeeInvoice'];
        $invoiceNumber = $viewData['invoiceNumber']
            ?? $employeeInvoice->invoice_number
            ?? ('EI-' . str_pad((string) $employeeInvoice->id, 6, '0', STR_PAD_LEFT));

        return InvoicePdf::download(
            'employee_invoices.show',
            $viewData,
            'Employee-Invoice-' . $invoiceNumber . '.pdf'
        );
    }

    /**
     * @return array<string, mixed>|\Illuminate\Http\RedirectResponse
     */
    private function invoiceViewData($id)
    {
        $employeeInvoice = $this->employeeInvoicesRepository->find($id);
        if (empty($employeeInvoice)) {
            session()->flash('error', 'Employee invoice not found');

            return redirect(route('employeeInvoices.index'));
        }

        $employeeInvoice->load([
            'items',
            'employee' => function ($query) {
                $query->withTrashed()->with(['department', 'account']);
            },
        ]);

        if (! $employeeInvoice->employee) {
            session()->flash('error', 'Employee record not found for this invoice');

            return redirect(route('employeeInvoices.index'));
        }

        $builder = app(EmployeeInvoiceViewDataBuilder::class);

        return array_merge(
            $builder->build($employeeInvoice),
            [
                'employeeInvoice' => $employeeInvoice,
                'paymentVouchers' => $builder->paymentVouchersForInvoice($employeeInvoice),
            ]
        );
    }

    public function edit($comapny_slug, $id)
    {
        $invoice = $this->employeeInvoicesRepository->find($id);
        if (empty($invoice)) {
            session()->flash('error', 'Employee invoice not found');

            return redirect(route('employeeInvoices.index'));
        }

        $employees = Employee::dropdown();
        $items = Items::dropdown('employee');

        return view('employee_invoices.edit', compact('invoice', 'employees', 'items'));
    }

    public function update($comapny_slug, $id, UpdateEmployeeInvoicesRequest $request)
    {
        try {
            $invoice = $this->employeeInvoicesRepository->find($id);
            if (empty($invoice)) {
                session()->flash('error', 'Employee invoice not found');

                return redirect(route('employeeInvoices.index'));
            }

            $this->employeeInvoicesRepository->record($request, $id);
            session()->flash('success', 'Employee invoice updated successfully.');

            return redirect(route('employeeInvoices.index'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function destroy($comapny_slug, $id)
    {
        $invoice = $this->employeeInvoicesRepository->find($id);
        if (empty($invoice)) {
            session()->flash('error', 'Employee invoice not found');

            return redirect(route('employeeInvoices.index'));
        }

        $payment = Payment::where('payee_account_id', $invoice->employee->account_id)
            ->where('reference', 'like', '%'.$invoice->invoice_number.'%')
            ->exists();

        if ($payment) {
            return response()->json([
                'message' => 'Cannot Delete Invoice. Payment has already been Made',
            ], 500);
        }

        Transactions::softDeleteMatching(
            fn ($q) => $q->where('reference_type', 'EmployeeInvoice')->where('reference_id', $id),
            Auth::id()
        );
        $invoice->deleted_by = Auth::id();
        $invoice->save();
        $invoice->delete();

        return response()->json([
            'message' => 'Employee invoice deleted successfully.',
            'reload' => true,
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids', []);
        if (empty($invoiceIds)) {
            return response()->json(['success' => false, 'message' => 'No invoices selected.'], 400);
        }

        $deleted = 0;
        foreach ($invoiceIds as $invoiceId) {
            $invoice = $this->employeeInvoicesRepository->find($invoiceId);
            if (! $invoice || $invoice->status == 1) {
                continue;
            }
            Transactions::softDeleteMatching(
                fn ($q) => $q->where('reference_type', 'EmployeeInvoice')->where('reference_id', $invoiceId),
                Auth::id()
            );
            $invoice->deleted_by = Auth::id();
            $invoice->save();
            $invoice->delete();
            $deleted++;
        }

        return response()->json([
            'success' => true,
            'message' => $deleted.' employee invoice(s) deleted successfully.',
        ]);
    }

    public function importForm()
    {
        $items = Items::dropdown('employee')->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => (float) ($item->price ?? 0),
                'vat' => (float) ($item->vat ?? 0),
            ];
        })->values();

        return view('employee_invoices.import', compact('items'));
    }

    /**
     * Mark invoice settled for reporting without recording a cash payment.
     */
    public function markAsSettled(Request $request, $company_slug, $id)
    {
        $invoice = EmployeeInvoices::with(['employee', 'items'])->find($id);
        if (! $invoice) {
            Flash::error('Employee invoice not found.');

            return redirect()->route('employeeInvoices.index');
        }

        if ((int) $invoice->status === 1) {
            Flash::error('Invoice is already marked as paid.');

            return redirect()->back();
        }

        $outstanding = app(EmployeeInvoiceViewDataBuilder::class)->outstandingAmounts($invoice);
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

            return redirect()->route('employeeInvoices.index');
        }

        return view('employee_invoices.mark_as_settled', compact('invoice', 'outstanding', 'needsReason'));
    }

    /**
     * Mark a single invoice as paid via Payment + PV ledger (not RI).
     */
    public function markAsPaid(Request $request, $company_slug, $id)
    {
        if ($request->isMethod('post')) {
            EmployeeVoucherPermissions::authorize('PAY', 'create');

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
                DB::beginTransaction();

                $invoice = EmployeeInvoices::with(['employee.account', 'items'])->find($id);
                if (! $invoice) {
                    Flash::error('Employee invoice not found.');

                    return redirect()->back();
                }

                if ((int) $invoice->status === 1) {
                    Flash::error('Invoice is already marked as paid.');

                    return redirect()->back();
                }

                $employee = $invoice->employee;
                if (! $employee || ! $employee->account_id) {
                    Flash::error('Employee account not found.');

                    return redirect()->back();
                }

                $paymentAmount = (float) $request->input('payment_amount', 0);
                $outstanding = app(EmployeeInvoiceViewDataBuilder::class)->outstandingAmountsForStatus($invoice);
                $dueAmount = max((float) ($outstanding['balance'] ?? 0), 0);
                if ($paymentAmount <= 0 || $paymentAmount > $dueAmount + 0.01) {
                    Flash::error('Payment amount must be greater than 0 and not exceed the remaining invoice due amount.');

                    return redirect()->back()->withInput();
                }

                $bankAccountId = (int) $request->input('bank_account_id');
                $bank = Banks::where('account_id', $bankAccountId)->first();
                if (! $bank) {
                    Flash::error('Selected bank account is not linked to a bank record.');

                    return redirect()->back()->withInput();
                }

                $payeeAccountId = (int) $employee->account_id;
                $billingMonth = date('Y-m-01', strtotime((string) $invoice->billing_month));
                $paymentDate = now()->format('Y-m-d');
                $invoiceNumber = $invoice->invoice_number;
                $description = 'Manual payment for Employee Invoice #'.$invoice->id
                    .($invoice->descriptions ? ' - '.$invoice->descriptions : '');

                $payment = Payment::create([
                    'reference' => $invoiceNumber,
                    'amount_type' => 'Online',
                    'bank_id' => $bank->id,
                    'payee_account_id' => $payeeAccountId,
                    'date_of_payment' => $paymentDate,
                    'date_of_invoice' => $invoice->inv_date
                        ? Carbon::parse($invoice->inv_date)->format('Y-m-d')
                        : $paymentDate,
                    'billing_month' => $billingMonth,
                    'amount' => $paymentAmount,
                    'bank_charges' => 0,
                    'bank_charges_account' => null,
                    'description' => $description,
                    'payment_kind' => 'salary_payment',
                    'branch_id' => Accounts::where('id', $payeeAccountId)->value('branch_id'),
                    'created_by' => Auth::id(),
                ]);

                $partialAmount = $invoice->partial_paid_amount ?? [];
                $partialAmount[$payment->id] = $paymentAmount;
                $balanceBefore = (float) $outstanding['balance'];
                $invoice->update([
                    'status' => $paymentAmount >= ($balanceBefore - 0.01) ? 1 : 3,
                    'partial_paid_amount' => $partialAmount,
                    'updated_by' => Auth::id(),
                ]);
                $this->refreshEmployeeInvoiceStatuses(collect([$invoice]));

                $paymentAmounts = [(int) $invoice->id => $paymentAmount];
                $payment->resource_links = ResourceLinks::build(
                    'employee',
                    [(int) $invoice->id],
                    $paymentAmounts,
                    collect([$invoice]),
                    null,
                    ResourceLinks::SOURCE_PAYMENT_FORM
                );
                $payment->save();

                $transCode = Account::trans_code();
                $this->postManualPaymentLedgerEntries(
                    $transCode,
                    $paymentDate,
                    (int) $payment->id,
                    $bankAccountId,
                    $payeeAccountId,
                    $paymentAmount,
                    $billingMonth,
                    $payment->branch_id ? (int) $payment->branch_id : null,
                    $description
                );

                $voucher = Vouchers::create([
                    'trans_date' => $paymentDate,
                    'trans_code' => $transCode,
                    'reference_number' => $invoiceNumber,
                    'billing_month' => $billingMonth,
                    'payment_from' => $bankAccountId,
                    'amount' => $paymentAmount,
                    'voucher_type' => 'PV',
                    'remarks' => 'Salary Payment',
                    'ref_id' => $payment->id,
                    'Created_By' => Auth::id(),
                    'status' => 1,
                    'branch_id' => $payment->branch_id,
                ]);

                $payment->voucher_id = $voucher->id;
                $payment->save();

                DB::commit();
                Flash::success('Invoice payment recorded successfully.');
            } catch (\Exception $e) {
                DB::rollBack();
                Flash::error('Error recording invoice payment: '.$e->getMessage());
            }

            return redirect()->back();
        }

        $invoice = EmployeeInvoices::with(['employee', 'items'])->find($id);
        if (! $invoice) {
            Flash::error('Employee invoice not found.');

            return redirect()->back();
        }

        if ((int) $invoice->status === 1) {
            Flash::error('Invoice is already marked as paid.');

            return redirect()->back();
        }

        $outstanding = app(EmployeeInvoiceViewDataBuilder::class)->outstandingAmounts($invoice);
        $netBalance = round((float) ($outstanding['balance'] ?? 0), 2);
        if ($netBalance <= 0.01) {
            Flash::warning('Net payable is zero or negative after deductions. Use Mark Settled (no payment) instead of recording a bank payment.');

            return redirect()->route('employeeInvoices.index');
        }

        $bankAccounts = Accounts::bankAccountsDropdown();

        return view('employee_invoices.mark_as_paid', compact('invoice', 'bankAccounts', 'outstanding'));
    }

    public function sendEmail($company_slug, $id, Request $request)
    {
        $invoice = EmployeeInvoices::with(['employee'])->findOrFail($id);

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
                    'message' => 'Employee invoice email address is missing.',
                ], 422);
            }
            $toEmail = trim($toEmail);

            $subject = is_string($request->input('email_subject')) && trim($request->input('email_subject')) !== ''
                ? trim($request->input('email_subject'))
                : 'Employee Invoice';

            $brandingService = app(CompanyEmailBrandingService::class);
            $data = $brandingService->mergeIntoMailData([
                'html' => $request->input('email_message'),
            ]);

            $invoice->load([
                'items',
                'employee' => function ($query) {
                    $query->withTrashed()->with(['department', 'account']);
                },
            ]);

            $builder = app(EmployeeInvoiceViewDataBuilder::class);
            $invoiceNumber = $invoice->invoice_number;
            $pdf = InvoicePdf::make(
                'employee_invoices.show',
                array_merge(
                    $builder->build($invoice),
                    [
                        'employeeInvoice' => $invoice,
                        'paymentVouchers' => $builder->paymentVouchersForInvoice($invoice),
                    ]
                )
            );

            $brandingService->sendBrandedEmail('emails.general', $data, function ($message) use ($toEmail, $pdf, $fromEmail, $fromName, $subject, $invoiceNumber) {
                $message->to([$toEmail]);
                $message->from($fromEmail, $fromName);
                $message->replyTo($fromEmail, $fromName);
                $message->subject($subject);
                $message->attachData($pdf->output(), 'Employee-Invoice-'.$invoiceNumber.'.pdf');
                $message->priority(3);
            });

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully.',
            ]);
        }

        return view('employee_invoices.send_email', compact('invoice'));
    }

    /**
     * @param  \Illuminate\Support\Collection|iterable  $invoices
     */
    private function refreshEmployeeInvoiceStatuses($invoices): void
    {
        $builder = app(EmployeeInvoiceViewDataBuilder::class);
        foreach (collect($invoices) as $fresh) {
            if (! $fresh instanceof EmployeeInvoices) {
                continue;
            }
            $fresh->clearOutstandingSummary();
            $amounts = $builder->outstandingAmountsForStatus($fresh);
            $balance = (float) ($amounts['balance'] ?? 0);
            $paid = (float) ($amounts['paid_amount'] ?? 0);
            if ($balance <= 0.01) {
                $fresh->status = 1;
            } elseif ($paid >= 0.01) {
                $fresh->status = 3;
            } else {
                $fresh->status = 0;
            }
            $fresh->updated_by = Auth::id();
            $fresh->save();
        }
    }

    /**
     * PV ledger lines (mirrors PaymentController::postPaymentLedgerEntries).
     */
    private function postManualPaymentLedgerEntries(
        int $transCode,
        string $date,
        int $paymentId,
        int $payingAccountId,
        int $payeeAccountId,
        float $paymentAmount,
        string $billingMonth,
        ?int $branchId,
        string $desc
    ): void {
        $paymentAmount = round($paymentAmount, 2);

        Transactions::create([
            'trans_code' => $transCode,
            'trans_date' => $date,
            'reference_id' => $paymentId,
            'reference_type' => 'PV',
            'account_id' => $payeeAccountId,
            'credit' => 0,
            'debit' => $paymentAmount,
            'billing_month' => $billingMonth,
            'branch_id' => $branchId,
            'narration' => $desc,
        ]);

        Transactions::create([
            'trans_code' => $transCode,
            'trans_date' => $date,
            'reference_id' => $paymentId,
            'reference_type' => 'PV',
            'account_id' => $payingAccountId,
            'credit' => $paymentAmount,
            'debit' => 0,
            'billing_month' => $billingMonth,
            'narration' => $desc,
            'branch_id' => $branchId,
        ]);
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

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
            'col_employee_id' => 'required|integer|min:1',
            'col_inv_date' => 'required|integer|min:1',
            'col_billing_month' => 'required|integer|min:1',
            'col_descriptions' => 'required|integer|min:1',
            'col_notes' => 'nullable|integer|min:1',
            'item_map' => 'required|array|min:1',
            'item_map.*.item_id' => 'required|integer|exists:items,id',
            'item_map.*.col' => 'required|integer|min:1',
            'item_map.*.rate' => 'required|numeric',
            'item_map.*.vat' => 'required|numeric|min:0',
            'item_map.*.discount' => 'required|numeric|min:0',
        ]);

        $columnMap = [
            'employee_id' => (int) $request->col_employee_id,
            'inv_date' => (int) $request->col_inv_date,
            'billing_month' => (int) $request->col_billing_month,
            'descriptions' => (int) $request->col_descriptions,
            'notes' => $request->filled('col_notes') ? (int) $request->col_notes : null,
        ];

        $itemDefs = [];
        $itemIds = [];
        $itemCols = [];

        foreach ($request->input('item_map', []) as $row) {
            $itemId = (int) $row['item_id'];
            $col = (int) $row['col'];

            if (in_array($itemId, $itemIds, true)) {
                $message = 'Each item can only be mapped once.';
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                Flash::error($message);

                return redirect()->back();
            }

            if (in_array($col, $itemCols, true)) {
                $message = 'Item quantity columns must be unique.';
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                Flash::error($message);

                return redirect()->back();
            }

            $itemIds[] = $itemId;
            $itemCols[] = $col;
            $itemDefs[] = [
                'item_id' => $itemId,
                'col' => $col,
                'rate' => (float) $row['rate'],
                'vat' => (float) $row['vat'],
                'discount' => (float) $row['discount'],
            ];
        }

        $employeeItems = Items::whereIn('id', $itemIds)
            ->where('status', 1)
            ->whereJsonContains('owner', 'employee')
            ->pluck('id')
            ->all();

        if (count($employeeItems) !== count($itemIds)) {
            $message = 'One or more mapped items are invalid or not assigned to the employee module.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        $headerCols = array_filter($columnMap, fn ($v) => $v !== null);
        $allCols = array_merge(array_values($headerCols), $itemCols);
        if (count($allCols) !== count(array_unique($allCols))) {
            $message = 'Column numbers must be unique across header and item mappings.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        try {
            $import = new ImportEmployeeInvoice($columnMap, $itemDefs);
            Excel::import($import, $request->file('file'));
            $importedCount = $import->importedCount;
            $skippedLog = $import->skippedLog;
            $skippedCount = count($skippedLog);

            $message = "Import finished. Imported: {$importedCount}.";
            if ($skippedCount > 0) {
                $message .= " Skipped: {$skippedCount}.";
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
            if ($skippedCount > 0) {
                session()->flash('import_skipped_log', $skippedLog);
            }

            return redirect()->route('employeeInvoices.index');
        } catch (\Exception $e) {
            \Log::error('Employee invoice import failed: '.$e->getMessage());
            $message = 'Import failed: '.$e->getMessage();
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }
    }
}
