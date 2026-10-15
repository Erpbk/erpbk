<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Exceptions\GlobalAccountNotConfiguredException;
use App\Support\GlobalAccounts;
use App\Models\Accounts;
use App\Models\Banks;
use App\Models\Customers;
use App\Models\Employee;
use App\Models\EmployeeInvoices;
use App\Models\LeasingCompanies;
use App\Models\LeasingCompanyInvoice;
use App\Models\Payment;
use App\Models\RiderInvoices;
use App\Models\Riders;
use App\Models\SimInvoice;
use App\Models\Supplier;
use App\Models\SupplierInvoices;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Repositories\PaymentsRepository;
use App\Services\EmployeeInvoice\EmployeeInvoiceViewDataBuilder;
use App\Services\Payment\RiderPaymentVoucherViewData;
use App\Services\Rider\RiderAdvanceLoanService;
use App\Services\RiderInvoice\RiderInvoiceViewDataBuilder;
use App\Support\InvoicePdf;
use App\Traits\GlobalPagination;
use App\Support\PublicStorageDisk;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    use GlobalPagination;

    private $paymentsRepository;

    public function __construct(PaymentsRepository $paymentsRepo)
    {
        $this->paymentsRepository = $paymentsRepo;
    }

    public function index(Request $request)
    {
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Payment::query()
            ->with(['payeeAccount', 'voucher', 'bank.account'])
            ->orderBy('date_of_payment', 'desc')
            ->applyListFilters($request);
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);

        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();
        $monthQuery = Payment::query()->whereBetween('date_of_payment', [$monthStart, $monthEnd]);

        $totals = [
            'count' => Payment::count(),
            'amount' => (float) Payment::sum('amount'),
            'month_count' => (clone $monthQuery)->count(),
            'month_amount' => (float) (clone $monthQuery)->sum('amount'),
        ];
        $banks = Banks::active()->get();

        if ($request->ajax()) {
            $tableData = view('payments.table', compact('data'))->render();

            return response()->json([
                'tableData' => $tableData,
            ]);
        }

        return view('payments.index', compact('data', 'totals', 'banks'));
    }

    public function create()
    {
        $accountId = request()->input('id') ?? null;
        $leasingCompanyId = request()->input('leasing_company_id') ?? null;
        $customerId = request()->input('customer_id') ?? null;
        $supplierId = request()->input('supplier_id') ?? null;
        $payment = null;
        $accountIds = null;
        $leasingIds = null;
        $supplierIds = null;
        $invoiceType = null;
        $existingInvoices = null;
        $invoices = null;
        $payeeOptions = collect();
        $lockedPayee = null;
        $preferredPayeeAccountId = null;
        $employeePayment = request()->input('employee_payment') || request()->input('invoice_type') == 'employee';
        $riderPayment = request()->input('rider_payment') || request()->input('invoice_type') == 'rider';
        $simPayment = request()->input('sim_payment') || request()->input('invoice_type') == 'sim';
        $riderId = request()->input('rider_id') ?? null;
        if (request()->input('leasing_payment')) {
            $leasingIds = LeasingCompanies::pluck('id')->toArray();
            $accountIds = LeasingCompanies::pluck('account_id')->toArray();
        }
        if (request()->input('supplier_payment')) {
            $supplierIds = Supplier::pluck('id')->toArray();
            $accountIds = Supplier::pluck('account_id')->toArray();
        }
        if ($leasingCompanyId) {
            $invoices = LeasingCompanyInvoice::with('leasingCompany.account')
                ->where('leasing_company_id', $leasingCompanyId)
                ->where(function ($q) {
                    $q->where('status', 0)
                        ->orWhere('status', 3);
                })
                ->get();
            $accountIds = LeasingCompanies::where('id', $leasingCompanyId)->pluck('account_id')->toArray();
        } elseif ($leasingIds) {
            $invoices = LeasingCompanyInvoice::with('leasingCompany.account')
                ->whereIn('leasing_company_id', $leasingIds)
                ->where(function ($q) {
                    $q->where('status', 0)
                        ->orWhere('status', 3);
                })
                ->get();
        } elseif ($supplierId) {
            $invoices = SupplierInvoices::with('supplier.account')
                ->where('is_invoice', true)
                ->where('supplier_id', $supplierId)
                ->where(function ($q) {
                    $q->where('status', 'unpaid')
                        ->orWhere('status', 'partially_paid');
                })
                ->get();
            $accountIds = Supplier::where('id', $supplierId)->pluck('account_id')->toArray();
        } elseif ($supplierIds) {
            $invoices = SupplierInvoices::with('supplier.account')
                ->where('is_invoice', true)
                ->whereIn('supplier_id', $supplierIds)
                ->where(function ($q) {
                    $q->where('status', 'unpaid')
                        ->orWhere('status', 'partially_paid');
                })
                ->get();
        } elseif ($employeePayment) {
            $invoiceType = 'employee';
            $selectedInvoice = null;
            $employeeId = request()->input('employee_id') ?? null;
            if (request()->input('invoice_id')) {
                $selectedInvoice = EmployeeInvoices::with(['employee.account', 'items'])->find(request()->input('invoice_id'));
                if ($selectedInvoice && ! $employeeId) {
                    $employeeId = $selectedInvoice->employee_id;
                }
            }
            $invoiceQuery = EmployeeInvoices::with(['employee.account', 'items'])
                ->payable()
                ->whereHas('employee', fn ($q) => $q->whereNotNull('account_id'));
            // Scope invoices only when opened for a specific employee (locked payee).
            if ($employeeId) {
                $invoiceQuery->where('employee_id', $employeeId);
            }
            $invoices = $invoiceQuery->orderBy('billing_month', 'desc')->get();

            // Full employee list for payee dropdown (same UX as rider payment when choosing a payee).
            $payeeOptions = $this->buildPayeeOptionsFromEntities(
                Employee::with('account')
                    ->whereNotNull('account_id')
                    ->orderBy('name')
                    ->get()
            );
            $accountIds = $payeeOptions->pluck('account_id')->all();

            // Only lock when payment is opened for a specific employee.
            if ($employeeId) {
                $lockedPayee = $this->makePayeeOption(Employee::with('account')->find($employeeId));
            } elseif ($selectedInvoice && $selectedInvoice->employee) {
                $preferredPayeeAccountId = $selectedInvoice->employee->account_id;
            }
        } elseif ($riderPayment) {
            $invoiceType = 'rider';
            $selectedInvoice = null;
            if (request()->input('invoice_id')) {
                $selectedInvoice = RiderInvoices::with(['rider.account', 'items'])->find(request()->input('invoice_id'));
                if ($selectedInvoice && ! $riderId) {
                    $riderId = $selectedInvoice->rider_id;
                }
            }
            $invoiceQuery = RiderInvoices::with(['rider.account', 'items'])
                ->payable()
                ->whereHas('rider', fn ($q) => $q->whereNotNull('account_id'));
            // Scope invoices only when opened for a specific rider (locked payee).
            if ($riderId) {
                $invoiceQuery->where('rider_id', $riderId);
            }
            $invoices = $invoiceQuery->orderBy('billing_month', 'desc')->get();

            // Full rider list for payee dropdown.
            $payeeOptions = $this->attachLedgerBalancesToPayeeOptions(
                $this->buildPayeeOptionsFromEntities(
                    Riders::with('account')
                        ->whereNotNull('account_id')
                        ->orderBy('name')
                        ->get()
                )
            );
            $accountIds = $payeeOptions->pluck('account_id')->all();

            if ($riderId) {
                $lockedPayee = $this->makePayeeOption(Riders::with('account')->find($riderId));
                if ($lockedPayee) {
                    $lockedPayee['ledger_balance'] = $this->ledgerBalanceForAccount((int) $lockedPayee['account_id']);
                }
            } elseif ($selectedInvoice && $selectedInvoice->rider) {
                $preferredPayeeAccountId = $selectedInvoice->rider->account_id;
            }
        } elseif ($simPayment) {
            $invoiceType = 'sim';
            $selectedInvoice = null;
            if (request()->input('invoice_id')) {
                $selectedInvoice = SimInvoice::with('company.account')->find(request()->input('invoice_id'));
            }
            if ($selectedInvoice) {
                $invoices = SimInvoice::with('company.account')
                    ->where('vendor_id', $selectedInvoice->vendor_id)
                    ->where('status', '!=', 1)
                    ->get();
            } else {
                $invoices = SimInvoice::with('company.account')
                    ->where('status', '!=', 1)
                    ->whereHas('company', fn($q) => $q->whereNotNull('account_id'))
                    ->orderBy('billing_month', 'desc')
                    ->get();
            }
            $payeeOptions = $this->buildPayeeOptionsFromInvoices($invoices, 'sim');
            $accountIds = $payeeOptions->pluck('account_id')->all();
            if ($selectedInvoice && $selectedInvoice->company) {
                $lockedPayee = $this->makePayeeOption($selectedInvoice->company);
            } elseif ($payeeOptions->count() === 1) {
                $lockedPayee = $payeeOptions->first();
            }
        }

        $banks = Banks::with('account')->active()->get();

        if ($accountId) {
            $bank = Banks::with('account')->find($accountId);

            return view('payments.create', compact('bank', 'banks', 'payment', 'payeeOptions', 'lockedPayee'));
        }

        if ($leasingCompanyId || $leasingIds) {
            $invoiceType = 'leasingCompany';
            $leasingCompany = LeasingCompanies::with('account')->find($leasingCompanyId);
            $payeeOptions = $this->buildPayeeOptionsFromInvoices($invoices, 'leasingCompany');
            if ($payeeOptions->isEmpty() && $leasingIds) {
                $payeeOptions = $this->buildPayeeOptionsFromEntities(
                    LeasingCompanies::with('account')->whereIn('id', $leasingIds)->get()
                );
            }
            $accountIds = $payeeOptions->pluck('account_id')->all();
            $lockedPayee = $leasingCompany ? $this->makePayeeOption($leasingCompany) : null;
            if (!$lockedPayee && $payeeOptions->count() === 1) {
                $lockedPayee = $payeeOptions->first();
            }

            return view('payments.create', compact(
                'leasingCompany',
                'banks',
                'payment',
                'invoices',
                'accountIds',
                'invoiceType',
                'payeeOptions',
                'lockedPayee'
            ));
        }

        if ($supplierId || $supplierIds) {
            $invoiceType = 'supplier';
            $supplier = Supplier::with('account')->find($supplierId);
            $leasingCompany = $supplier; // backward-compatible with existing view checks
            $payeeOptions = $this->buildPayeeOptionsFromInvoices($invoices, 'supplier');
            if ($payeeOptions->isEmpty() && $supplierIds) {
                $payeeOptions = $this->buildPayeeOptionsFromEntities(
                    Supplier::with('account')->whereIn('id', $supplierIds)->get()
                );
            }
            $accountIds = $payeeOptions->pluck('account_id')->all();
            $lockedPayee = $supplier ? $this->makePayeeOption($supplier) : null;
            if (!$lockedPayee && $payeeOptions->count() === 1) {
                $lockedPayee = $payeeOptions->first();
            }

            return view('payments.create', compact(
                'leasingCompany',
                'banks',
                'payment',
                'invoices',
                'accountIds',
                'invoiceType',
                'payeeOptions',
                'lockedPayee'
            ));
        }

        if ($employeePayment || $riderPayment || $simPayment) {
            $this->applySequentialSalaryInvoiceBalances($invoices, $existingInvoices, $invoiceType);

            // Payable invoices must stay listed even when sequential ledger net is <= 0
            // (deductions/carry). Normalize allocatable balance for payment allocation UI.
            if ($invoiceType === 'rider') {
                $this->normalizeRiderPaymentFormBalances($invoices);
                if (! empty($existingInvoices)) {
                    $this->normalizeRiderPaymentFormBalances($existingInvoices);
                }
            }

            return view('payments.create', compact(
                'banks',
                'payment',
                'invoices',
                'accountIds',
                'invoiceType',
                'existingInvoices',
                'payeeOptions',
                'lockedPayee',
                'preferredPayeeAccountId'
            ));
        }

        if ($customerId) {
            $customer = Customers::with('account')->find($customerId);
            $lockedPayee = $this->makePayeeOption($customer);
            $invoiceType = 'customer';

            return view('payments.create', compact('customer', 'banks', 'payment', 'payeeOptions', 'lockedPayee', 'invoiceType'));
        }

        return view('payments.create', compact('banks', 'payment', 'payeeOptions', 'lockedPayee'));
    }

    public function store(Request $request)
    {
        $this->forgetEmptyAttachmentUpload($request);

        if (
            $request->input('invoice_type') === 'rider'
            && $request->input('create_payment_type') === 'advance_loan'
        ) {
            return $this->storeRiderAdvanceLoanFromPaymentForm($request);
        }

        $isRiderPaymentCreate = $request->input('invoice_type') === 'rider'
            && in_array($request->input('create_payment_type', 'payment'), ['payment', ''], true);

        $rules = [
            'reference' => 'nullable|string|max:255',
            'amount_type' => 'required|string|in:Cash,Online,Cheque,Credit',
            'bank_id' => 'required|numeric|exists:banks,id',
            'payee_account_id' => 'required|numeric|exists:accounts,id',
            'date_of_payment' => 'required|date',
            'date_of_invoice' => 'nullable|date',
            'billing_month' => $isRiderPaymentCreate ? 'nullable|date_format:Y-m' : 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:0.01',
            'bank_charges' => 'nullable|numeric|min:0',
            'description' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'invoice_ids' => $isRiderPaymentCreate ? 'required|array|size:1' : 'nullable|array',
            'invoice_ids.*' => 'numeric|required',
            'payment_amounts' => 'nullable|array',
            'payment_amounts.*' => 'nullable|numeric',
        ];

        if ($isRiderPaymentCreate) {
            $rules['create_payment_type'] = 'nullable|in:payment';
            $rules['internal_notes'] = 'nullable|string|max:500';
            $rules['terms_and_conditions'] = 'nullable|string';
        }

        $messages = [
            'amount_type.required' => 'Payment mode is required',
            'bank_id.required' => 'Sending account is required',
            'payee_account_id.required' => 'Receiving account is required',
            'amount.required' => 'Payment amount is required',
            'amount.min' => 'Payment amount must be greater than 0',
            'date_of_payment.required' => 'Payment date is required',
            'billing_month.required' => 'Billing month is required',
            'description.required' => 'Main narration is required',
            'bank_charges_account.required_if' => 'Please select a bank charges account when bank charges are entered',
            'invoice_ids.required' => 'Please select exactly one invoice for payment',
            'invoice_ids.size' => 'Please select exactly one invoice for payment',
        ];

        $this->validate($request, $rules, $messages);

        // For rider Payment type: derive billing month from the selected invoice.
        if ($isRiderPaymentCreate) {
            $this->applyRiderPaymentInvoiceRules($request);
        }

        // For rider/employee payments, inherit invoice date from the selected invoice when not provided.
        if (
            in_array($request->input('invoice_type'), ['rider', 'employee'], true)
            && blank($request->input('date_of_invoice'))
            && $request->filled('invoice_ids')
        ) {
            $firstInvoiceId = collect($request->input('invoice_ids'))->first();
            $firstInvoice = $request->input('invoice_type') === 'employee'
                ? EmployeeInvoices::find($firstInvoiceId)
                : RiderInvoices::find($firstInvoiceId);
            if ($firstInvoice && $firstInvoice->inv_date) {
                $request->merge([
                    'date_of_invoice' => Carbon::parse($firstInvoice->inv_date)->format('Y-m-d'),
                ]);
            }
        }

        // Calculate total debit (payment amount + bank charges)
        $paymentAmount = floatval($request->input('amount', 0));
        $bankCharges = floatval($request->input('bank_charges', 0));
        $totalAmount = $paymentAmount + $bankCharges;

        // Get the paying account (bank account)
        $bank = Banks::find($request->input('bank_id'));
        if (! $bank) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Selected bank not found'], 422);
            }
            Flash::error('Selected bank not found.');

            return redirect()->back()->withInput();
        }
        $payingAccountId = $bank->account_id;

        $input = $request->all();
        $input['created_by'] = auth()->id();
        $billingMonthRaw = (string) ($input['billing_month'] ?? $request->input('billing_month') ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $billingMonthRaw)) {
            $input['billing_month'] = substr($billingMonthRaw, 0, 10);
        } elseif (preg_match('/^\d{4}-\d{2}$/', $billingMonthRaw)) {
            $input['billing_month'] = $billingMonthRaw . '-01';
        } else {
            $input['billing_month'] = date('Y-m-01');
        }
        $input['branch_id'] = Accounts::where('id', $input['payee_account_id'])->value('branch_id');
        $input['amount'] = $totalAmount;

        try {
            DB::beginTransaction();

            $bankChargesAccountId = null;
            if ($bankCharges > 0) {
                $bankChargesAccountId = GlobalAccounts::account('BANK_CHARGES')->id;
            }
            $input['bank_charges_account'] = $bankChargesAccountId;

            // Validate invoice payments if invoices are selected
            $salaryBalancesBefore = [];
            if ($request->has('invoice_ids') && count($input['invoice_ids']) > 0) {
                // Bank charges are ledger-only — never part of invoice allocation.
                $this->enforceInvoicePaymentsWithinPaymentAmount($request, $paymentAmount, $bankCharges);

                // Snapshot salary due amounts before creating the payment row so legacy
                // billing-month paid totals (and carry-forward) do not distort status.
                $invoiceType = $request->input('invoice_type');
                if (in_array($invoiceType, ['rider', 'employee'], true)) {
                    $previewIds = $request->input('invoice_ids');
                    $previewInvoices = $invoiceType === 'employee'
                        ? EmployeeInvoices::with(['employee.account', 'items'])->whereIn('id', $previewIds)->get()
                        : RiderInvoices::with(['rider.account', 'items'])->whereIn('id', $previewIds)->get();
                    $this->applySequentialSalaryInvoiceBalances($previewInvoices, null, $invoiceType);
                    $salaryBalancesBefore = $this->captureInvoiceBalances($previewInvoices);
                }
            }

            // Create the payment record
            $payment = Payment::create($input);

            // Process invoice payments if invoices are selected
            if ($request->has('invoice_ids') && count($input['invoice_ids']) > 0) {
                $invoiceIds = $request->input('invoice_ids');
                $paymentAmounts = $request->input('payment_amounts');

                $invoiceType = $request->input('invoice_type');
                if ($invoiceType == 'leasingCompany') {
                    $invoices = LeasingCompanyInvoice::whereIn('id', $invoiceIds)->get();

                    foreach ($invoices as $invoice) {
                        $invoicePaymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);
                        $partialAmount = $invoice->partial_paid_amount ?? [];
                        $partialAmount[$payment->id] = $invoicePaymentAmount;

                        if ($this->isNonZeroInvoiceAllocation($invoicePaymentAmount)) {
                            // Update the invoice status based on the payment
                            if ($invoicePaymentAmount >= $invoice->total_amount - ($invoice->paid_amount ?? 0)) {
                                $invoice->update([
                                    'status' => 1, // Paid
                                    'partial_paid_amount' => $partialAmount,
                                    'updated_by' => auth()->id(),
                                ]);
                            } else {
                                $invoice->update([
                                    'status' => 3, // Partially Paid
                                    'partial_paid_amount' => $partialAmount,
                                    'updated_by' => auth()->id(),
                                ]);
                            }
                        }
                    }
                } elseif ($invoiceType == 'employee') {
                    $invoices = EmployeeInvoices::with(['employee.account', 'items'])->whereIn('id', $invoiceIds)->get();
                    $employeeInvoicesToRefresh = collect();

                    foreach ($invoices as $invoice) {
                        $invoicePaymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);
                        if (! $this->isNonZeroInvoiceAllocation($invoicePaymentAmount)
                            && count($invoiceIds) === 1) {
                            // Single-invoice payments may omit per-row amounts (exclude bank charges)
                            $invoicePaymentAmount = $paymentAmount;
                        }
                        if (! $this->isNonZeroInvoiceAllocation($invoicePaymentAmount)) {
                            continue;
                        }

                        $partialAmount = $invoice->partial_paid_amount ?? [];
                        $partialAmount[$payment->id] = $invoicePaymentAmount;
                        // Status uses this invoice's own net (no prior-month carry-forward).
                        $balanceBefore = (float) app(EmployeeInvoiceViewDataBuilder::class)
                            ->outstandingAmountsForStatus($invoice)['balance'];

                        $invoice->update([
                            'status' => $invoicePaymentAmount >= ($balanceBefore - 0.01) ? 1 : 3,
                            'partial_paid_amount' => $partialAmount,
                            'updated_by' => auth()->id(),
                        ]);
                        $employeeInvoicesToRefresh->push($invoice);
                    }

                    if ($employeeInvoicesToRefresh->isNotEmpty()) {
                        $this->refreshSelectedEmployeeInvoiceStatuses($employeeInvoicesToRefresh);
                    }
                } elseif ($invoiceType == 'rider') {
                    $invoices = RiderInvoices::with(['rider.account', 'items'])->whereIn('id', $invoiceIds)->get();

                    $invoiceRefs = [];
                    foreach ($invoices as $invoice) {
                        $invoicePaymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);
                        if (! $this->isNonZeroInvoiceAllocation($invoicePaymentAmount)
                            && count($invoiceIds) === 1) {
                            // Single-invoice payments may omit per-row amounts (exclude bank charges)
                            $invoicePaymentAmount = $paymentAmount;
                        }
                        if (! $this->isNonZeroInvoiceAllocation($invoicePaymentAmount)) {
                            continue;
                        }

                        $partialAmount = $invoice->partial_paid_amount ?? [];
                        $partialAmount[$payment->id] = $invoicePaymentAmount;
                        $balanceBefore = $salaryBalancesBefore[$invoice->id] ?? (float) $invoice->balance;

                        $invoiceUpdate = [
                            'status' => $invoicePaymentAmount >= ($balanceBefore - 0.01) ? 1 : 3,
                            'partial_paid_amount' => $partialAmount,
                            'updated_by' => auth()->id(),
                        ];
                        // Persist editable notes/terms from the payment form onto the invoice.
                        if ($request->has('internal_notes')) {
                            $invoiceUpdate['notes'] = $request->input('internal_notes');
                        }
                        if ($request->has('terms_and_conditions')) {
                            $invoiceUpdate['terms_and_conditions'] = $request->input('terms_and_conditions');
                        }
                        $invoice->update($invoiceUpdate);
                        $invoiceRefs[] = 'Rider Invoice #'.$invoice->id;
                    }

                    if ($invoiceRefs !== []) {
                        $tag = implode(', ', $invoiceRefs);
                        $desc = trim((string) $payment->description);
                        if ($desc === '') {
                            $payment->description = 'Payment against '.$tag;
                            $payment->save();
                        } elseif (! str_contains($desc, 'Rider Invoice #')) {
                            $payment->description = $desc.' | '.$tag;
                            $payment->save();
                        }
                    }
                } elseif ($invoiceType == 'sim') {
                    $invoices = SimInvoice::whereIn('id', $invoiceIds)->get();

                    foreach ($invoices as $invoice) {
                        $invoicePaymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);
                        $partialAmount = $invoice->partial_paid_amount ?? [];
                        $partialAmount[$payment->id] = $invoicePaymentAmount;

                        if ($this->isNonZeroInvoiceAllocation($invoicePaymentAmount)) {
                            if ($invoicePaymentAmount >= ($invoice->balance ?? 0)) {
                                $invoice->status = 1;
                            } else {
                                $invoice->status = 3;
                            }
                            $invoice->partial_paid_amount = $partialAmount;
                            $invoice->save();
                        }
                    }
                } else {
                    $invoices = SupplierInvoices::whereIn('id', $invoiceIds)->get();

                    foreach ($invoices as $invoice) {
                        $invoicePaymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);
                        $partialAmount = $invoice->partial_paid_amount ?? [];
                        $partialAmount[$payment->id] = $invoicePaymentAmount;

                        if ($this->isNonZeroInvoiceAllocation($invoicePaymentAmount)) {
                            // Update the invoice status based on the payment
                            if ($invoicePaymentAmount >= $invoice->total_amount - ($invoice->paid_amount ?? 0)) {
                                $invoice->update([
                                    'status' => 'paid', // Paid
                                    'partial_paid_amount' => $partialAmount,
                                    'updated_by' => auth()->id(),
                                ]);
                            } else {
                                $invoice->update([
                                    'status' => 'partially_paid', // Partially Paid
                                    'partial_paid_amount' => $partialAmount,
                                    'updated_by' => auth()->id(),
                                ]);
                            }
                        }
                    }
                }
            }

            $transCode = Account::trans_code();
            $date = $input['date_of_payment'];
            $billingMonth = $input['billing_month'];
            $desc = $input['description'];

            $this->postPaymentLedgerEntries(
                $transCode,
                $date,
                (int) $payment->id,
                (int) $payingAccountId,
                (int) $request->input('payee_account_id'),
                $paymentAmount,
                $bankCharges,
                $bankChargesAccountId,
                $billingMonth,
                $payment->branch_id ? (int) $payment->branch_id : null,
                (string) $desc
            );

            // Create voucher
            $voucherData = [
                'trans_date' => $date,
                'trans_code' => $transCode,
                'reference_number' => $payment->reference,
                'billing_month' => $billingMonth,
                'payment_from' => $payingAccountId,
                'amount' => $totalAmount,
                'voucher_type' => 'PV',
                'remarks' => 'Payment Voucher',
                'ref_id' => $payment->id,
                'Created_By' => auth()->id(),
                'status' => 1,
                'branch_id' => $payment->branch_id,
                'custom_field_values' => $request->input('voucher_custom_fields', []),
            ];

            // Link PV to related rider when paying against rider invoice(s)
            if ($request->input('invoice_type') === 'rider') {
                $riderId = null;
                $invoiceIds = collect($request->input('invoice_ids', []))->filter()->values();
                if ($invoiceIds->isNotEmpty()) {
                    $riderId = RiderInvoices::whereIn('id', $invoiceIds)->value('rider_id');
                }
                if (! $riderId) {
                    $riderId = Accounts::where('id', $request->input('payee_account_id'))
                        ->where('ref_name', 'Rider')
                        ->value('ref_id');
                }
                if ($riderId) {
                    $voucherData['rider_id'] = (int) $riderId;
                }
            }

            // Handle attachment
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                PublicStorageDisk::storeUploadedFile($file, 'vouchers', $fileName);
                $voucherData['attach_file'] = $fileName;
            }

            $voucher = Vouchers::create($voucherData);

            // Update payment with voucher info
            $payment->update([
                'voucher_id' => $voucher->id,
                'attachment' => $voucher->attach_file ?? null,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Payment added successfully',
                    'reload' => true,
                ]);
            }

            Flash::success('Payment added successfully.');

            return redirect()->back();
        } catch (GlobalAccountNotConfiguredException $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            Flash::error($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment creation failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            if ($request->ajax()) {
                return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
            }

            Flash::error('Error occurred: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function show(Request $request, $comapny_slug, $id)
    {
        $payment = Payment::with(['payeeAccount', 'bank.account', 'voucher'])->find($id);
        if (empty($payment)) {
            if ($request->ajax()) {
                return response('Payment not found', 404);
            }
            Flash::error('Payment not found');

            return redirect(route('payments.index'));
        }

        $viewData = app(RiderPaymentVoucherViewData::class);
        if (! $viewData->isRiderPayment($payment)) {
            // Non-rider payments keep the generic voucher modal when a voucher exists.
            if ($payment->voucher_id) {
                return redirect()->route('vouchers.show', array_filter([
                    'voucher' => $payment->voucher_id,
                    'company_slug' => $comapny_slug,
                    'print' => $request->boolean('print') ? 1 : null,
                ]));
            }

            return view('payments.show_fields')->with('payment', $payment);
        }

        return view('payments.show', $viewData->build($payment));
    }

    public function pdf($comapny_slug, $id)
    {
        $payment = Payment::with(['payeeAccount', 'bank.account', 'voucher'])->find($id);
        if (empty($payment)) {
            Flash::error('Payment not found');

            return redirect(route('payments.index'));
        }

        $viewData = app(RiderPaymentVoucherViewData::class);
        abort_unless($viewData->isRiderPayment($payment), 404);

        $data = $viewData->build($payment);
        $filename = 'Payment-Voucher-'.preg_replace('/[^A-Za-z0-9\-_]/', '', (string) ($data['referenceNo'] ?? $payment->id)).'.pdf';

        return InvoicePdf::download('payments.pdf', $data, $filename);
    }

    public function edit(Request $request, $comapny_slug, $id)
    {
        $payment = Payment::find($id);
        if (empty($payment)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Payment Not found'], 404);
            }
            Flash::error('Payment not found');

            return redirect()->back();
        }

        return view('payments.edit', $this->buildLinkedPaymentFormData($payment));
    }

    public function update(Request $request, $comapny_slug, $id)
    {
        $payment = Payment::find($id);

        if (empty($payment)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Payment Not Found'], 500);
            }
            Flash::error('Payment not found!');

            return redirect()->back();
        }

        $request['billing_month'] = $request['billing_month'] . '-01';
        $this->forgetEmptyAttachmentUpload($request);

        $rules = [
            'reference' => 'nullable|string|max:255',
            'amount_type' => 'required|string|in:Cash,Online,Cheque,Credit',
            'bank_id' => 'required|numeric|exists:banks,id',
            'payee_account_id' => 'required|numeric|exists:accounts,id',
            'date_of_payment' => 'required|date',
            'date_of_invoice' => 'nullable|date',
            'billing_month' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'bank_charges' => 'nullable|numeric|min:0',
            'description' => 'required|string|max:500',
            'internal_notes' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'numeric|required',
            'payment_amounts' => 'nullable|array',
            'payment_amounts.*' => 'nullable|numeric',
        ];

        $messages = [
            'amount_type.required' => 'Payment mode is required',
            'bank_id.required' => 'Sending account is required',
            'payee_account_id.required' => 'Receiving account is required',
            'amount.required' => 'Payment amount is required',
            'amount.min' => 'Payment amount must be greater than zero',
            'date_of_payment.required' => 'Payment date is required',
            'billing_month.required' => 'Billing month is required',
            'description.required' => 'Narration for Transaction is Required',
            'invoice_ids.*.exists' => 'One or more selected invoices are invalid.',
        ];

        $this->validate($request, $rules, $messages);

        // For rider/employee payments, inherit invoice date from the selected invoice when not provided.
        if (
            in_array($request->input('invoice_type'), ['rider', 'employee'], true)
            && blank($request->input('date_of_invoice'))
            && $request->filled('invoice_ids')
        ) {
            $firstInvoiceId = collect($request->input('invoice_ids'))->first();
            $firstInvoice = $request->input('invoice_type') === 'employee'
                ? EmployeeInvoices::find($firstInvoiceId)
                : RiderInvoices::find($firstInvoiceId);
            if ($firstInvoice && $firstInvoice->inv_date) {
                $request->merge([
                    'date_of_invoice' => Carbon::parse($firstInvoice->inv_date)->format('Y-m-d'),
                ]);
            }
        }

        // Calculate total debit (payment amount + bank charges)
        $paymentAmount = floatval($request->input('amount', 0));
        $bankCharges = floatval($request->input('bank_charges', 0));
        $totalAmount = $paymentAmount + $bankCharges;

        // Get the paying account (bank account)
        $bank = Banks::find($request->input('bank_id'));
        if (! $bank) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Selected bank not found'], 422);
            }
            Flash::error('Selected bank not found.');

            return redirect()->back()->withInput();
        }
        $payingAccountId = $bank->account_id;

        try {
            DB::beginTransaction();

            $bankChargesAccountId = null;
            if ($bankCharges > 0) {
                $bankChargesAccountId = GlobalAccounts::account('BANK_CHARGES')->id;
            }

            // Prepare data for payment update (keep existing attachment unless a new file is uploaded)
            $input = $request->except(['attachment']);
            $input['branch_id'] = Accounts::where('id', $input['payee_account_id'])->value('branch_id');
            $input['amount'] = $totalAmount;
            $input['updated_by'] = auth()->id();
            $input['bank_charges_account'] = $bankChargesAccountId;
            $pending = null;
            $partial = null;
            $paid = null;
            $employeeInvoicesToRefresh = collect();
            $originalReference = (string) ($payment->reference ?? '');
            $salaryBalancesBefore = [];

            $payment->fill($input);
            // updated_by is always stamped; ignore it so attachment-only edits do not
            // look like payment/invoice changes and wipe invoice statuses.
            $meaningfulDirty = array_values(array_diff(
                array_keys($payment->getDirty()),
                ['updated_by', 'updated_at']
            ));
            $paymentHasChanges = $meaningfulDirty !== [];
            $hasNewAttachment = $request->hasFile('attachment');
            $hasInvoiceSelection = $request->has('invoice_ids') && count((array) ($input['invoice_ids'] ?? [])) > 0;
            $invoiceAllocationsChanged = $hasInvoiceSelection
                && $this->invoiceAllocationsChanged(
                    $payment,
                    $originalReference,
                    $input['invoice_type'] ?? null,
                    (array) $request->input('invoice_ids', []),
                    (array) $request->input('payment_amounts', []),
                    $paymentAmount
                );
            $amountFieldsDirty = array_intersect(
                $meaningfulDirty,
                ['amount', 'bank_charges', 'bank_charges_account']
            ) !== [];
            // Skip revert/reapply when only the attachment (or non-amount fields) changed.
            $shouldSyncInvoiceStatuses = $hasInvoiceSelection
                && ($invoiceAllocationsChanged || $amountFieldsDirty);

            // Leave linked invoices untouched when the form has nothing to save.
            if (! $paymentHasChanges && ! $hasNewAttachment && ! $invoiceAllocationsChanged) {
                DB::commit();

                return response()->json([
                    'message' => 'Nothing New Entered to Update',
                    'reload' => true,
                ], 200);
            }

            // Handle existing invoice payments (remove old ones)
            if ($shouldSyncInvoiceStatuses) {
                // Bank charges are ledger-only — never part of invoice allocation.
                $this->enforceInvoicePaymentsWithinPaymentAmount($request, $paymentAmount, $bankCharges);

                // Snapshot salary balances before reverting this payment's allocations.
                // Use the same sequential period slices as the payment form.
                if (in_array($input['invoice_type'] ?? null, ['rider', 'employee'], true)) {
                    $previewIds = $request->input('invoice_ids');
                    $previewInvoices = ($input['invoice_type'] === 'employee')
                        ? EmployeeInvoices::with(['employee.account', 'items'])->whereIn('id', $previewIds)->get()
                        : RiderInvoices::with(['rider.account', 'items'])->whereIn('id', $previewIds)->get();
                    $this->applySequentialSalaryInvoiceBalances(
                        $previewInvoices,
                        null,
                        $input['invoice_type']
                    );
                    $salaryBalancesBefore = $this->captureInvoiceBalances($previewInvoices, $payment);
                }

                // Use the reference from before fill(), so invoices removed on this edit are reverted.
                $invoice_numbers = $originalReference === '' ? [] : explode(' ', $originalReference);
                $invoiceIds = [];
                if ($input['invoice_type'] == 'leasingCompany') {
                    foreach ($invoice_numbers as $invoice_number) {
                        $id = LeasingCompanyInvoice::getIdFromInvoiceNumber($invoice_number);
                        if ($id) {
                            $invoiceIds[] = $id;
                        }
                    }

                    // Get existing invoices and revert their status
                    $existingInvoices = LeasingCompanyInvoice::whereIn('id', $invoiceIds)->get();
                    $partial = 3;
                    $pending = 0;
                } elseif ($input['invoice_type'] == 'employee') {
                    foreach ($invoice_numbers as $invoice_number) {
                        $id = EmployeeInvoices::getIdFromInvoiceNumber($invoice_number);
                        if ($id) {
                            $invoiceIds[] = $id;
                        }
                    }

                    // Get existing invoices and revert their status
                    $existingInvoices = EmployeeInvoices::with(['employee.account', 'items'])->whereIn('id', $invoiceIds)->get();
                    $partial = 3;
                    $pending = 0;
                } elseif ($input['invoice_type'] == 'sim') {
                    foreach ($invoice_numbers as $invoice_number) {
                        $id = SimInvoice::getIdFromInvoiceNumber($invoice_number);
                        if ($id) {
                            $invoiceIds[] = $id;
                        }
                    }

                    $existingInvoices = SimInvoice::whereIn('id', $invoiceIds)->get();
                    $partial = 3;
                    $pending = 0;
                } elseif ($input['invoice_type'] == 'rider') {
                    foreach ($invoice_numbers as $invoice_number) {
                        if (preg_match('/RINV-?0*(\d+)/i', $invoice_number, $matches)) {
                            $invoiceIds[] = (int) $matches[1];
                        }
                    }

                    $existingInvoices = RiderInvoices::whereIn('id', $invoiceIds)->get();
                    $partial = 3;
                    $pending = 0;
                } else {
                    foreach ($invoice_numbers as $invoice_number) {
                        $id = SupplierInvoices::getIdFromInvoiceNumber($invoice_number);
                        if ($id) {
                            $invoiceIds[] = $id;
                        }
                    }

                    // Get existing invoices and revert their status
                    $existingInvoices = SupplierInvoices::whereIn('id', $invoiceIds)->get();
                    $partial = 'partially_paid';
                    $pending = 'unpaid';
                }

                foreach ($existingInvoices as $invoice) {
                    $partialAmount = $invoice->partial_paid_amount ?? [];
                    unset($partialAmount[$payment->id], $partialAmount[(string) $payment->id]);
                    $invoice->partial_paid_amount = $partialAmount;

                    if (count($partialAmount) < 1) {
                        $invoice->status = $pending; // Revert to pending if no payments left
                    } else {
                        $invoice->status = $partial; // Otherwise, it's still partially paid
                    }
                    if (($input['invoice_type'] ?? null) != 'sim') {
                        $invoice->updated_by = auth()->id();
                    }
                    if (method_exists($invoice, 'clearOutstandingSummary')) {
                        $invoice->clearOutstandingSummary();
                    }
                    $invoice->save();
                }
            }

            // Process new invoice payments
            if ($shouldSyncInvoiceStatuses) {
                $invoiceIds = $request->input('invoice_ids');
                $paymentAmounts = (array) $request->input('payment_amounts', []);
                if ($input['invoice_type'] == 'leasingCompany') {
                    $invoices = LeasingCompanyInvoice::whereIn('id', $invoiceIds)->get();
                    $partial = 3;
                    $paid = 1;
                } elseif ($input['invoice_type'] == 'employee') {
                    $invoices = EmployeeInvoices::with(['employee.account', 'items'])->whereIn('id', $invoiceIds)->get();
                    $partial = 3;
                    $paid = 1;
                } elseif ($input['invoice_type'] == 'sim') {
                    $invoices = SimInvoice::whereIn('id', $invoiceIds)->get();
                    $partial = 3;
                    $paid = 1;
                } elseif ($input['invoice_type'] == 'rider') {
                    $invoices = RiderInvoices::with(['rider.account', 'items'])->whereIn('id', $invoiceIds)->get();
                    $partial = 3;
                    $paid = 1;
                } else {
                    $invoices = SupplierInvoices::whereIn('id', $invoiceIds)->get();
                    $partial = 'partially_paid';
                    $paid = 'paid';
                }

                foreach ($invoices as $invoice) {
                    $invoicePaymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);
                    if (! $this->isNonZeroInvoiceAllocation($invoicePaymentAmount)
                        && count($invoiceIds) === 1) {
                        // Single-invoice payments may omit per-row amounts (exclude bank charges)
                        $invoicePaymentAmount = $paymentAmount;
                    }

                    if (! $this->isNonZeroInvoiceAllocation($invoicePaymentAmount)) {
                        continue;
                    }

                    $partialAmount = $invoice->partial_paid_amount ?? [];
                    $partialAmount[$payment->id] = $invoicePaymentAmount;

                    if (in_array($input['invoice_type'] ?? null, ['rider', 'employee'], true)) {
                        if (($input['invoice_type'] ?? null) === 'employee') {
                            $balanceBefore = (float) app(EmployeeInvoiceViewDataBuilder::class)
                                ->outstandingAmountsForStatus($invoice)['balance'];
                        } else {
                            $balanceBefore = $salaryBalancesBefore[$invoice->id] ?? (float) $invoice->balance;
                        }
                        $invoice->status = $invoicePaymentAmount >= ($balanceBefore - 0.01) ? $paid : $partial;
                        $invoice->partial_paid_amount = $partialAmount;
                        $invoice->updated_by = auth()->id();
                        $invoice->clearOutstandingSummary();
                        $invoice->save();
                        if (($input['invoice_type'] ?? null) === 'employee') {
                            $employeeInvoicesToRefresh->push($invoice);
                        }
                        continue;
                    }

                    if ($input['invoice_type'] == 'sim') {
                        if ($invoicePaymentAmount >= ($invoice->balance ?? 0)) {
                            $invoice->status = $paid;
                        } else {
                            $invoice->status = $partial;
                        }
                    } elseif ($invoicePaymentAmount >= ($invoice->total_amount - ($invoice->paid_amount ?? 0))) {
                        $invoice->status = $paid;
                    } else {
                        $invoice->status = $partial;
                    }
                    $invoice->partial_paid_amount = $partialAmount;
                    if (($input['invoice_type'] ?? null) != 'sim') {
                        $invoice->updated_by = auth()->id();
                    }
                    $invoice->save();
                }
            }

            // Save payment if it has changes
            if ($paymentHasChanges) {
                $payment->save();

                if (! $payment->voucher) {
                    throw new \Exception('Voucher not found for this payment');
                }

                $transCode = $payment->voucher->trans_code;
                $date = $request->input('date_of_payment');
                $billingMonth = $request->input('billing_month');
                $desc = $request->input('description');

                // Delete existing transactions
                Transactions::where('trans_code', $transCode)->delete();

                $this->postPaymentLedgerEntries(
                    (int) $transCode,
                    $date,
                    (int) $payment->id,
                    (int) $payingAccountId,
                    (int) $request->input('payee_account_id'),
                    $paymentAmount,
                    $bankCharges,
                    $bankChargesAccountId,
                    $billingMonth,
                    $payment->branch_id ? (int) $payment->branch_id : null,
                    (string) $desc
                );

                // Update voucher
                $voucherData = [
                    'trans_date' => $date,
                    'billing_month' => $billingMonth,
                    'reference_number' => $payment->reference,
                    'payment_from' => $payingAccountId,
                    'amount' => $totalAmount,
                    'branch_id' => $payment->branch_id,
                    'Updated_By' => auth()->id(),
                ];

                if ($request->input('invoice_type') === 'rider') {
                    $riderId = null;
                    $invoiceIds = collect($request->input('invoice_ids', []))->filter()->values();
                    if ($invoiceIds->isNotEmpty()) {
                        $riderId = RiderInvoices::whereIn('id', $invoiceIds)->value('rider_id');
                    }
                    if (! $riderId) {
                        $riderId = Accounts::where('id', $request->input('payee_account_id'))
                            ->where('ref_name', 'Rider')
                            ->value('ref_id');
                    }
                    if ($riderId) {
                        $voucherData['rider_id'] = (int) $riderId;
                    }
                }

                $payment->voucher->fill($voucherData);

                // Save voucher if it has changes
                if ($payment->voucher->isDirty()) {
                    $payment->voucher->save();
                }
            }

            if ($employeeInvoicesToRefresh->isNotEmpty()) {
                $this->refreshSelectedEmployeeInvoiceStatuses($employeeInvoicesToRefresh);
            } elseif (
                $hasInvoiceSelection
                && ($input['invoice_type'] ?? null) === 'employee'
            ) {
                // Attachment-only / no allocation churn: still correct Paid vs Partial
                // using this invoice's own net (without prior-month carry).
                $this->refreshSelectedEmployeeInvoiceStatuses(
                    EmployeeInvoices::whereIn('id', (array) $request->input('invoice_ids', []))->get()
                );
            }

            // Handle attachment if provided (can be updated independently)
            if ($hasNewAttachment) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                PublicStorageDisk::storeUploadedFile($file, 'vouchers', $fileName);

                $oldPaymentFile = $payment->attachment ? basename((string) $payment->attachment) : null;
                $oldVoucherFile = $payment->voucher?->attach_file
                    ? basename((string) $payment->voucher->attach_file)
                    : null;

                $payment->update(['attachment' => $fileName]);

                if ($payment->voucher) {
                    $payment->voucher->update(['attach_file' => $fileName]);
                }

                foreach (array_unique(array_filter([$oldPaymentFile, $oldVoucherFile])) as $oldFile) {
                    if ($oldFile !== $fileName) {
                        PublicStorageDisk::delete('vouchers/' . $oldFile);
                    }
                }
            }

            DB::commit();

            // Determine appropriate success message
            $message = 'Payment Updated Successfully';
            if ($hasNewAttachment && ! $paymentHasChanges) {
                $message = 'File uploaded Successfully';
            }

            return response()->json([
                'message' => $message,
                'reload' => true,
            ], 200);
        } catch (GlobalAccountNotConfiguredException $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            Flash::error($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment update failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error: ' . $e->getMessage(),
                ], 500);
            }

            Flash::error('Error Occurred: ' . $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function destroy(Request $request, $comapny_slug, $id)
    {
        $payment = Payment::with('voucher')->find($id);
        if (empty($payment)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Payment Not Found!'], 500);
            }
            Flash::error('Payment not found!');

            return redirect()->back();
        }

        // Delete-approval: queue request (voucher + transactions + payment) until admin approves.
        if (
            \App\Services\DeleteRequestService::enabled()
            && ! \App\Services\DeleteRequestService::shouldBypassApproval()
            && $payment->voucher
        ) {
            try {
                $deleteRequest = \App\Services\PaymentDeletionService::queueDeleteRequest($payment);
            } catch (\RuntimeException $e) {
                if ($request->ajax()) {
                    return response()->json(['message' => $e->getMessage()], 422);
                }
                Flash::warning($e->getMessage());

                return redirect()->back();
            }

            if ($request->ajax()) {
                return response()->json([
                    'message' => \App\Services\DeleteRequestService::pendingMessage($deleteRequest),
                    'delete_request_id' => $deleteRequest->id,
                    'pending_deletion' => true,
                    'reload' => true,
                ]);
            }

            Flash::warning(\App\Services\DeleteRequestService::pendingMessage($deleteRequest));

            return redirect()->back();
        }

        \App\Services\PaymentDeletionService::executeImmediate($payment);

        if ($request->ajax()) {
            return response()->json(['message' => 'Payment Deleted successfully', 'reload' => true]);
        }
        Flash::success('Payment deleted successfully.');

        return redirect()->back();
    }

    private function isNonZeroInvoiceAllocation($amount): bool
    {
        return abs((float) $amount) >= 0.01;
    }

    /**
     * Parse linked invoice IDs from a payment reference string.
     *
     * @return list<int>
     */
    private function invoiceIdsFromPaymentReference(string $reference, ?string $invoiceType): array
    {
        if ($reference === '') {
            return [];
        }

        $invoiceNumbers = explode(' ', $reference);
        $invoiceIds = [];

        if ($invoiceType === 'leasingCompany') {
            foreach ($invoiceNumbers as $invoiceNumber) {
                $id = LeasingCompanyInvoice::getIdFromInvoiceNumber($invoiceNumber);
                if ($id) {
                    $invoiceIds[] = (int) $id;
                }
            }
        } elseif ($invoiceType === 'employee') {
            foreach ($invoiceNumbers as $invoiceNumber) {
                $id = EmployeeInvoices::getIdFromInvoiceNumber($invoiceNumber);
                if ($id) {
                    $invoiceIds[] = (int) $id;
                }
            }
        } elseif ($invoiceType === 'sim') {
            foreach ($invoiceNumbers as $invoiceNumber) {
                $id = SimInvoice::getIdFromInvoiceNumber($invoiceNumber);
                if ($id) {
                    $invoiceIds[] = (int) $id;
                }
            }
        } elseif ($invoiceType === 'rider') {
            foreach ($invoiceNumbers as $invoiceNumber) {
                if (preg_match('/RINV-?0*(\d+)/i', $invoiceNumber, $matches)) {
                    $invoiceIds[] = (int) $matches[1];
                }
            }
        } else {
            foreach ($invoiceNumbers as $invoiceNumber) {
                $id = SupplierInvoices::getIdFromInvoiceNumber($invoiceNumber);
                if ($id) {
                    $invoiceIds[] = (int) $id;
                }
            }
        }

        return array_values(array_unique($invoiceIds));
    }

    /**
     * Whether submitted invoice IDs / line amounts differ from what is already stored
     * on this payment. Omitted or zero line amounts (common on attachment-only saves)
     * are not treated as a change unless a single-invoice fallback amount differs.
     */
    private function invoiceAllocationsChanged(
        Payment $payment,
        string $originalReference,
        ?string $invoiceType,
        array $submittedInvoiceIds,
        array $paymentAmounts,
        float $paymentAmount
    ): bool {
        $originalIds = collect($this->invoiceIdsFromPaymentReference($originalReference, $invoiceType))
            ->sort()
            ->values()
            ->all();
        $submittedIds = collect($submittedInvoiceIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($submittedIds !== $originalIds) {
            return true;
        }

        if ($submittedIds === []) {
            return false;
        }

        $invoices = match ($invoiceType) {
            'leasingCompany' => LeasingCompanyInvoice::whereIn('id', $submittedIds)->get(),
            'employee' => EmployeeInvoices::whereIn('id', $submittedIds)->get(),
            'sim' => SimInvoice::whereIn('id', $submittedIds)->get(),
            'rider' => RiderInvoices::whereIn('id', $submittedIds)->get(),
            default => SupplierInvoices::whereIn('id', $submittedIds)->get(),
        };

        if ($invoices->count() !== count($submittedIds)) {
            return true;
        }

        foreach ($invoices as $invoice) {
            $partial = $invoice->partial_paid_amount ?? [];
            $existing = floatval($partial[$payment->id] ?? $partial[(string) $payment->id] ?? 0);
            $submittedAmt = floatval(
                $paymentAmounts[$invoice->id] ?? $paymentAmounts[(string) $invoice->id] ?? 0
            );

            if (! $this->isNonZeroInvoiceAllocation($submittedAmt)) {
                if (count($submittedIds) === 1) {
                    $submittedAmt = $paymentAmount;
                } else {
                    // Multi-invoice forms often omit line amounts on attachment-only saves.
                    continue;
                }
            }

            if (abs($submittedAmt - $existing) >= 0.01) {
                return true;
            }
        }

        return false;
    }

    /**
     * Drop empty / invalid attachment uploads so optional file fields stay optional
     * (especially on update when the original file is already stored).
     */
    private function forgetEmptyAttachmentUpload(Request $request): void
    {
        if (! $request->hasFile('attachment')) {
            $request->files->remove('attachment');
            $request->request->remove('attachment');

            return;
        }

        $file = $request->file('attachment');
        if (! $file || ! $file->isValid() || $file->getSize() === 0) {
            $request->files->remove('attachment');
            $request->request->remove('attachment');
        }
    }

    /**
     * Snapshot balance due per invoice before payment allocations are written.
     * When editing, add back this payment's existing allocation so status compares
     * against the true remaining due.
     *
     * @param  \Illuminate\Support\Collection|iterable  $invoices
     * @return array<int, float>
     */
    private function captureInvoiceBalances($invoices, ?Payment $payment = null): array
    {
        $balances = [];
        foreach (collect($invoices) as $invoice) {
            if (! $invoice || ! isset($invoice->id)) {
                continue;
            }
            $balance = (float) ($invoice->balance ?? 0);
            if ($payment) {
                $partials = $invoice->partial_paid_amount ?? [];
                if (is_array($partials) && array_key_exists($payment->id, $partials)) {
                    $balance += (float) $partials[$payment->id];
                } elseif (is_array($partials) && array_key_exists((string) $payment->id, $partials)) {
                    $balance += (float) $partials[(string) $payment->id];
                }
            }
            $balances[(int) $invoice->id] = $balance;
        }

        return $balances;
    }

    /**
     * Recalculate employee invoice status after the payment row has been saved.
     * Uses each invoice's own month net (excludes prior-month carry-forward) so a
     * full payment of the invoice amount marks it Paid.
     */
    private function refreshSelectedEmployeeInvoiceStatuses($invoices): void
    {
        $ids = collect($invoices)->pluck('id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        $freshInvoices = EmployeeInvoices::with(['employee.account', 'items'])
            ->whereIn('id', $ids)
            ->get();
        $builder = app(EmployeeInvoiceViewDataBuilder::class);

        foreach ($freshInvoices as $fresh) {
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
            $fresh->updated_by = auth()->id();
            $fresh->save();
        }
    }

    /**
     * When sequential ledger net is not positive, fall back to remaining invoice
     * line total (items + VAT − paid) so unpaid invoices stay payable in the form.
     *
     * @param  iterable<int, RiderInvoices>|null  $invoices
     */
    private function normalizeRiderPaymentFormBalances($invoices): void
    {
        foreach (collect($invoices ?? []) as $invoice) {
            if (! $invoice) {
                continue;
            }

            $sequentialBalance = (float) ($invoice->balance ?? 0);
            if ($sequentialBalance > 0.01) {
                continue;
            }

            $itemsTotal = round(
                (float) ($invoice->items?->sum('amount') ?? 0) + (float) ($invoice->vat ?? 0),
                2
            );
            $paid = (float) ($invoice->paid_amount ?? 0);
            $remaining = round(max(0, $itemsTotal - $paid), 2);
            if ($remaining <= 0.01) {
                continue;
            }

            $invoice->setOutstandingSummary([
                'final_amount' => $itemsTotal,
                'paid_amount' => $paid,
                'balance' => $remaining,
            ]);
        }
    }

    /**
     * Rider Payment type: require one invoice, derive billing month, cap amount to invoice total.
     */
    private function applyRiderPaymentInvoiceRules(Request $request): void
    {
        $invoiceId = (int) collect($request->input('invoice_ids', []))->filter()->first();
        if ($invoiceId <= 0) {
            throw ValidationException::withMessages([
                'invoice_ids' => 'Please select exactly one invoice for payment',
            ]);
        }

        $invoice = RiderInvoices::with(['rider.account', 'items'])->find($invoiceId);
        if (! $invoice) {
            throw ValidationException::withMessages([
                'invoice_ids' => 'Selected invoice was not found',
            ]);
        }

        $collection = collect([$invoice]);
        $this->applySequentialSalaryInvoiceBalances($collection, null, 'rider');
        $this->normalizeRiderPaymentFormBalances($collection);
        $balanceDue = (float) ($invoice->balance ?? 0);
        // Cap to TOTAL AMOUNT (invoice total), not remaining balance due.
        $invoiceTotal = round((float) ($invoice->total ?? $invoice->total_amount ?? 0), 2);
        if ($invoiceTotal <= 0.01) {
            $invoiceTotal = round($balanceDue + (float) ($invoice->paid_amount ?? 0), 2);
        }

        if ($balanceDue <= 0.01) {
            throw ValidationException::withMessages([
                'invoice_ids' => 'Selected invoice has no outstanding balance',
            ]);
        }

        $paymentAmount = round((float) $request->input('amount', 0), 2);
        $bankCharges = round((float) $request->input('bank_charges', 0), 2);
        $lineAmount = round((float) ($request->input('payment_amounts.'.$invoiceId) ?? $paymentAmount), 2);
        if ($lineAmount < 0.01) {
            $lineAmount = $paymentAmount;
        }

        // If the line mistakenly includes bank charges, strip them before invoice checks.
        if ($bankCharges > 0.009 && $lineAmount > $paymentAmount + 0.009
            && $lineAmount <= $paymentAmount + $bankCharges + 0.01) {
            $lineAmount = $paymentAmount;
        }

        // Invoice allocation uses payment amount only (bank charges excluded).
        if ($lineAmount > $paymentAmount + 0.01) {
            $lineAmount = $paymentAmount;
        }

        if ($paymentAmount > $invoiceTotal + 0.01 || $lineAmount > $invoiceTotal + 0.01) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount cannot exceed the selected invoice amount ('.number_format($invoiceTotal, 2).')',
            ]);
        }

        $billingMonth = $invoice->billing_month
            ? Carbon::parse($invoice->billing_month)->format('Y-m')
            : date('Y-m');

        $request->merge([
            'billing_month' => $billingMonth,
            'payment_amounts' => array_merge(
                (array) $request->input('payment_amounts', []),
                [(string) $invoiceId => $lineAmount]
            ),
        ]);
    }

    /**
     * Ensure selected invoice allocations do not exceed the payment amount.
     * Bank charges must never inflate or block invoice allocation.
     */
    private function enforceInvoicePaymentsWithinPaymentAmount(
        Request $request,
        float $paymentAmount,
        float $bankCharges = 0.0
    ): void {
        $invoiceIds = collect($request->input('invoice_ids', []))
            ->filter(static fn ($id) => $id !== null && $id !== '')
            ->map(static fn ($id) => (string) $id)
            ->values();

        if ($invoiceIds->isEmpty()) {
            return;
        }

        $rawAmounts = (array) $request->input('payment_amounts', []);
        $normalized = [];
        $totalPayment = 0.0;

        foreach ($invoiceIds as $id) {
            $value = round((float) ($rawAmounts[$id] ?? $rawAmounts[(int) $id] ?? 0), 2);
            $normalized[$id] = $value;
            $totalPayment += $value;
        }

        $totalPayment = round($totalPayment, 2);
        $paymentAmount = round($paymentAmount, 2);
        $bankCharges = round($bankCharges, 2);

        if ($totalPayment <= $paymentAmount + 0.01) {
            // Keep request in sync with the selected-invoice-only map.
            $request->merge(['payment_amounts' => array_merge($rawAmounts, $normalized)]);

            return;
        }

        // Lines that still include bank charges (legacy / UI bleed): cap to payment amount.
        $excess = round($totalPayment - $paymentAmount, 2);
        if ($bankCharges > 0.009 && $excess <= $bankCharges + 0.01) {
            $normalized = $this->capInvoicePaymentAmounts($normalized, $paymentAmount);
            $request->merge(['payment_amounts' => array_merge($rawAmounts, $normalized)]);

            return;
        }

        throw new \Exception('Total payment amount for selected invoices cannot exceed the payment amount.');
    }

    /**
     * Reduce selected invoice line amounts so they sum to at most $maxAmount.
     *
     * @param  array<string, float>  $amounts
     * @return array<string, float>
     */
    private function capInvoicePaymentAmounts(array $amounts, float $maxAmount): array
    {
        $maxAmount = round(max(0, $maxAmount), 2);
        $total = round(array_sum($amounts), 2);

        if ($total <= $maxAmount + 0.01 || $total < 0.01) {
            return $amounts;
        }

        // Prefer trimming the last non-zero line (typical single-invoice rider payment).
        $keys = array_reverse(array_keys($amounts));
        $remainingExcess = round($total - $maxAmount, 2);

        foreach ($keys as $key) {
            if ($remainingExcess <= 0.009) {
                break;
            }
            $current = round((float) $amounts[$key], 2);
            if ($current <= 0) {
                continue;
            }
            $reduction = min($current, $remainingExcess);
            $amounts[$key] = round($current - $reduction, 2);
            $remainingExcess = round($remainingExcess - $reduction, 2);
        }

        return $amounts;
    }

    /**
     * Post PV ledger lines:
     * 1) Debit rider for payment + bank charges (single clubbed entry)
     * 2) Credit bank for payment amount
     * 3) Credit bank charges account (when charges apply)
     */
    private function postPaymentLedgerEntries(
        int $transCode,
        string $date,
        int $paymentId,
        int $payingAccountId,
        int $payeeAccountId,
        float $paymentAmount,
        float $bankCharges,
        ?int $bankChargesAccountId,
        string $billingMonth,
        ?int $branchId,
        string $desc
    ): void {
        $paymentAmount = round($paymentAmount, 2);
        $bankCharges = round(max(0, $bankCharges), 2);
        $riderDebit = round($paymentAmount + $bankCharges, 2);

        $riderNarration = $desc;
        if ($bankCharges > 0.009) {
            $riderNarration = trim($desc.' | Bank charges '.number_format($bankCharges, 2));
        }

        // 1. Debit payee (rider) — payment + bank charges in one entry
        Transactions::create([
            'trans_code' => $transCode,
            'trans_date' => $date,
            'reference_id' => $paymentId,
            'reference_type' => 'PV',
            'account_id' => $payeeAccountId,
            'credit' => 0,
            'debit' => $riderDebit,
            'billing_month' => $billingMonth,
            'branch_id' => $branchId,
            'narration' => $riderNarration,
        ]);

        // 2. Credit bank for the payment amount
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

        // 3. Credit bank charges account
        if ($bankCharges > 0.009 && $bankChargesAccountId) {
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $paymentId,
                'reference_type' => 'PV',
                'account_id' => $bankChargesAccountId,
                'credit' => $bankCharges,
                'debit' => 0,
                'billing_month' => $billingMonth,
                'branch_id' => $branchId,
                'narration' => 'Bank charges for ( '.$desc.' )',
            ]);
        }
    }

    /**
     * Ledger closing balance (debit − credit), same as account detail panel.
     */
    private function ledgerBalanceForAccount(?int $accountId): float
    {
        if (! $accountId) {
            return 0.0;
        }

        return round((float) Transactions::where('account_id', $accountId)
            ->sum(DB::raw('debit - credit')), 2);
    }

    /**
     * @param  array<int, int|string>  $accountIds
     * @return array<int, float> account_id => balance
     */
    private function ledgerBalancesForAccountIds(array $accountIds): array
    {
        $accountIds = array_values(array_unique(array_filter(array_map('intval', $accountIds))));
        if ($accountIds === []) {
            return [];
        }

        return Transactions::query()
            ->whereIn('account_id', $accountIds)
            ->groupBy('account_id')
            ->selectRaw('account_id, COALESCE(SUM(debit - credit), 0) as balance')
            ->pluck('balance', 'account_id')
            ->map(static fn ($balance) => round((float) $balance, 2))
            ->all();
    }

    /**
     * Attach ledger_balance onto payee option arrays.
     *
     * @param  \Illuminate\Support\Collection|array  $options
     * @return \Illuminate\Support\Collection
     */
    private function attachLedgerBalancesToPayeeOptions($options)
    {
        $options = collect($options)->values();
        $balances = $this->ledgerBalancesForAccountIds($options->pluck('account_id')->all());

        return $options->map(function ($option) use ($balances) {
            $accountId = (int) ($option['account_id'] ?? 0);
            $option['ledger_balance'] = round((float) ($balances[$accountId] ?? 0), 2);

            return $option;
        });
    }

    /**
     * Create an AL voucher from the Record Rider Payment form (Advance Loan type).
     * Does not allocate against invoice paid balances.
     */
    private function storeRiderAdvanceLoanFromPaymentForm(Request $request)
    {
        $invoiceId = (int) collect($request->input('invoice_ids', []))->filter()->first();
        $invoice = $invoiceId > 0 ? RiderInvoices::find($invoiceId) : null;
        if ($invoiceId > 0 && ! $invoice) {
            throw ValidationException::withMessages([
                'invoice_ids' => 'Selected invoice was not found',
            ]);
        }

        // Normalize / fill billing month before validation (type=month must be Y-m).
        $billingMonthInput = $request->input('billing_month');
        if (blank($billingMonthInput) && $invoice && $invoice->billing_month) {
            $billingMonthInput = Carbon::parse($invoice->billing_month)->format('Y-m');
        }
        if (! blank($billingMonthInput) && preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $billingMonthInput)) {
            $billingMonthInput = Carbon::parse($billingMonthInput)->format('Y-m');
        }
        if (! blank($billingMonthInput)) {
            $request->merge(['billing_month' => $billingMonthInput]);
        }

        $this->validate($request, [
            'create_payment_type' => 'required|in:advance_loan',
            'reference' => 'nullable|string|max:255',
            'amount_type' => 'required|string|in:Cash,Online,Cheque,Credit',
            'bank_id' => 'required|numeric|exists:banks,id',
            'payee_account_id' => 'required|numeric|exists:accounts,id',
            'date_of_payment' => 'required|date',
            'billing_month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:0.01',
            'bank_charges' => 'nullable|numeric|min:0',
            'description' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'invoice_ids' => 'nullable|array|max:1',
            'invoice_ids.*' => 'numeric|exists:rider_invoices,id',
            'internal_notes' => 'nullable|string|max:500',
            'terms_and_conditions' => 'nullable|string',
        ], [
            'invoice_ids.max' => 'Please select at most one invoice for the advance loan',
            'billing_month.required' => 'Billing month is required for Advance Loan',
        ]);

        $billingMonthInput = $request->input('billing_month');

        $bank = Banks::find($request->input('bank_id'));
        if (! $bank || ! $bank->account_id) {
            throw ValidationException::withMessages([
                'bank_id' => 'Selected paying account was not found',
            ]);
        }

        $attachFile = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = time().'_'.$file->getClientOriginalName();
            PublicStorageDisk::storeUploadedFile($file, 'vouchers', $fileName);
            $attachFile = $fileName;
        }

        $branchId = Accounts::where('id', $request->input('payee_account_id'))->value('branch_id');

        // Persist editable notes/terms only when an invoice is linked.
        if ($invoice) {
            $invoice->update([
                'notes' => $request->input('internal_notes'),
                'terms_and_conditions' => $request->input('terms_and_conditions'),
                'updated_by' => auth()->id(),
            ]);
        }

        try {
            $result = app(RiderAdvanceLoanService::class)->create([
                'rider_account_id' => (int) $request->input('payee_account_id'),
                'bank_account_id' => (int) $bank->account_id,
                'amount' => (float) $request->input('amount'),
                'bank_charges' => (float) $request->input('bank_charges', 0),
                'trans_date' => $request->input('date_of_payment'),
                'billing_month' => $billingMonthInput,
                'reference_number' => $request->input('reference'),
                'payment_type' => RiderAdvanceLoanService::paymentTypeFromAmountType($request->input('amount_type')),
                'branch_id' => $branchId ? (int) $branchId : null,
                'invoice_id' => $invoice ? $invoiceId : null,
                'debit_narration' => $request->input('description') ?: 'Advance Loan Received',
                'credit_narration' => $request->input('description')
                    ? ('Advance Loan: '.$request->input('description'))
                    : null,
                'remarks' => 'Advance Loan to Rider',
                'custom_field_values' => $request->input('voucher_custom_fields', []),
                'attach_file' => $attachFile,
            ]);
        } catch (GlobalAccountNotConfiguredException $e) {
            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            Flash::error($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\InvalidArgumentException $e) {
            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            Flash::error($e->getMessage());

            return redirect()->back()->withInput();
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Advance loan recorded successfully',
                'voucher_id' => $result['voucher']->id,
                'trans_code' => $result['trans_code'],
                'reload' => true,
            ]);
        }

        Flash::success('Advance loan recorded successfully.');

        return redirect()->back();
    }

    /**
     * Give each rider/employee invoice a unique period slice so payment-form
     * balances do not re-include earlier unpaid months.
     */
    private function applySequentialSalaryInvoiceBalances($invoices, $existingInvoices, ?string $invoiceType): void
    {
        if (! in_array($invoiceType, ['rider', 'employee'], true)) {
            return;
        }

        $all = collect($invoices ?? []);
        if ($existingInvoices) {
            $all = $all->concat(collect($existingInvoices));
        }
        $all = $all->unique('id')->values();
        if ($all->isEmpty()) {
            return;
        }

        if ($invoiceType === 'rider') {
            app(RiderInvoiceViewDataBuilder::class)->applySequentialOutstanding($all);

            return;
        }

        app(EmployeeInvoiceViewDataBuilder::class)->applySequentialOutstanding($all);
    }

    /**
     * Build unique payee options from invoice-linked module entities.
     */
    private function buildPayeeOptionsFromInvoices($invoices, string $invoiceType)
    {
        $entities = collect();
        foreach (collect($invoices) as $invoice) {
            $entity = match ($invoiceType) {
                'leasingCompany' => $invoice->leasingCompany ?? null,
                'supplier' => $invoice->supplier ?? null,
                'employee' => $invoice->employee ?? null,
                'rider' => $invoice->rider ?? null,
                'sim' => $invoice->company ?? null,
                'customer' => $invoice->customer ?? null,
                default => null,
            };
            if ($entity) {
                $entities->push($entity);
            }
        }

        return $this->buildPayeeOptionsFromEntities($entities);
    }

    /**
     * Build unique payee options from a collection of entities (rider/employee/etc).
     */
    private function buildPayeeOptionsFromEntities($entities)
    {
        $options = collect();
        foreach (collect($entities) as $entity) {
            $option = $this->makePayeeOption($entity);
            if ($option) {
                $options->put((int) $option['account_id'], $option);
            }
        }

        return $options->values()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /**
     * Normalize a module entity into a payee option array.
     */
    private function makePayeeOption($entity): ?array
    {
        if (!$entity || empty($entity->account_id)) {
            return null;
        }

        $account = null;
        if (method_exists($entity, 'relationLoaded') && $entity->relationLoaded('account')) {
            $account = $entity->getRelation('account');
        }
        if (!$account) {
            $account = Accounts::withoutGlobalScope('branch')->find($entity->account_id);
        }

        // Orphan account_id (employee/rider points at a deleted/missing account).
        if (!$account) {
            return null;
        }

        $name = $entity->name ?: ($account->name ?? null);
        if (!$name && $account->ref_name && $account->ref_id) {
            $ref = \App\Helpers\Accounts::getRef([
                'ref_name' => $account->ref_name,
                'ref_id' => $account->ref_id,
            ]);
            $name = $ref->name ?? null;
        }

        $codePrefix = '';
        $entityCode = null;
        if (!empty($entity->employee_id)) {
            $entityCode = (string) $entity->employee_id;
            $codePrefix = $entityCode . ' - ';
        } elseif (!empty($entity->rider_id)) {
            $entityCode = (string) $entity->rider_id;
            $codePrefix = $entityCode . ' - ';
        }

        $displayName = $codePrefix . ($name ?: '-');
        $accountCode = (string) ($account->account_code ?? '');

        return [
            'account_id' => (int) $entity->account_id,
            'entity_id' => (int) $entity->id,
            'name' => $name ?: '-',
            'entity_code' => $entityCode,
            'account_code' => $accountCode,
            'label' => trim(($accountCode !== '' ? $accountCode . ' - ' : '') . $displayName),
        ];
    }

    /**
     * Resolve payee display from a chart-of-accounts id (fallback).
     */
    private function makePayeeOptionFromAccountId($accountId): ?array
    {
        $account = Accounts::withoutGlobalScope('branch')->find($accountId);
        if (!$account) {
            return null;
        }

        $name = $account->name;
        if ((!$name || is_numeric($name)) && $account->ref_name && $account->ref_id) {
            $ref = \App\Helpers\Accounts::getRef([
                'ref_name' => $account->ref_name,
                'ref_id' => $account->ref_id,
            ]);
            $name = $ref->name ?? $name;
        }

        return [
            'account_id' => (int) $account->id,
            'entity_id' => (int) ($account->ref_id ?? 0),
            'name' => $name ?: '-',
            'account_code' => $account->account_code ?? '',
            'label' => trim(($account->account_code ? $account->account_code . ' - ' : '') . ($name ?: '-')),
        ];
    }

    public function clone(Request $request, $comapny_slug, $id)
    {
        $payment = Payment::find($id);
        if (empty($payment)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Payment Not found'], 404);
            }
            Flash::error('Payment not found');

            return redirect()->back();
        }

        // Same linked-invoice UI as edit/create (rider form, invoice table, etc.).
        return view('payments.create', $this->buildLinkedPaymentFormData($payment));
    }

    /**
     * Shared create/edit/clone form payload for payments linked to module invoices.
     *
     * @return array<string, mixed>
     */
    private function buildLinkedPaymentFormData(Payment $payment): array
    {
        $invoices = null;
        $existingInvoices = null;
        $accountIds = null;
        $invoiceType = null;
        $payeeOptions = collect();
        $lockedPayee = null;
        $preferredPayeeAccountId = null;

        if ((str_contains((string) $payment->reference, 'LCI'))) {
            $invoice_numbers = explode(' ', (string) $payment->reference);
            $invoiceIds = [];
            foreach ($invoice_numbers as $invoice_number) {
                $invoiceId = LeasingCompanyInvoice::getIdFromInvoiceNumber($invoice_number);
                if ($invoiceId) {
                    $invoiceIds[] = $invoiceId;
                }
            }
            $existingInvoices = LeasingCompanyInvoice::with('leasingCompany.account')
                ->whereIn('id', $invoiceIds)
                ->get();
            $invoices = LeasingCompanyInvoice::with('leasingCompany.account')
                ->whereIn('leasing_company_id', $existingInvoices->pluck('leasing_company_id'))
                ->where(function ($query) use ($invoiceIds) {
                    $query->whereIn('status', [0, 3])
                        ->WhereNotIn('id', $invoiceIds);
                })
                ->get();
            $invoiceType = 'leasingCompany';
            $payeeOptions = $this->buildPayeeOptionsFromInvoices(
                $existingInvoices->concat($invoices),
                'leasingCompany'
            );
            $accountIds = $payeeOptions->pluck('account_id')->all();
            $lockedPayee = $payeeOptions->firstWhere('account_id', (int) $payment->payee_account_id)
                ?: $payeeOptions->first();
        }
        if ((str_contains((string) $payment->reference, 'SUP'))) {
            $invoice_numbers = explode(' ', (string) $payment->reference);
            $invoiceIds = [];
            foreach ($invoice_numbers as $invoice_number) {
                $invoiceId = SupplierInvoices::getIdFromInvoiceNumber($invoice_number);
                if ($invoiceId) {
                    $invoiceIds[] = $invoiceId;
                }
            }
            $existingInvoices = SupplierInvoices::with('supplier.account')
                ->where('is_invoice', true)
                ->whereIn('id', $invoiceIds)
                ->get();
            $invoices = SupplierInvoices::with('supplier.account')
                ->where('is_invoice', true)
                ->whereIn('supplier_id', $existingInvoices->pluck('supplier_id'))
                ->where(function ($query) use ($invoiceIds) {
                    $query->whereIn('status', ['unpaid', 'partially_paid'])
                        ->WhereNotIn('id', $invoiceIds);
                })
                ->get();
            $invoiceType = 'supplier';
            $payeeOptions = $this->buildPayeeOptionsFromInvoices(
                $existingInvoices->concat($invoices),
                'supplier'
            );
            $accountIds = $payeeOptions->pluck('account_id')->all();
            $lockedPayee = $payeeOptions->firstWhere('account_id', (int) $payment->payee_account_id)
                ?: $payeeOptions->first();
        }
        if ((str_contains((string) $payment->reference, 'EMP_INV'))) {
            $invoice_numbers = explode(' ', (string) $payment->reference);
            $invoiceIds = [];
            foreach ($invoice_numbers as $invoice_number) {
                $invoiceId = EmployeeInvoices::getIdFromInvoiceNumber($invoice_number);
                if ($invoiceId) {
                    $invoiceIds[] = $invoiceId;
                }
            }
            $existingInvoices = EmployeeInvoices::with(['employee.account', 'items'])
                ->whereIn('id', $invoiceIds)
                ->get();
            $invoices = EmployeeInvoices::with(['employee.account', 'items'])
                ->payable()
                ->whereHas('employee', fn ($q) => $q->whereNotNull('account_id'))
                ->whereNotIn('id', $invoiceIds)
                ->orderBy('billing_month', 'desc')
                ->get();
            $invoiceType = 'employee';
            $payeeOptions = $this->buildPayeeOptionsFromEntities(
                Employee::with('account')
                    ->whereNotNull('account_id')
                    ->orderBy('name')
                    ->get()
            );
            $accountIds = $payeeOptions->pluck('account_id')->all();
            $lockedPayee = null;
            $preferredPayeeAccountId = $payment->payee_account_id;
        }
        if ((str_contains((string) $payment->reference, 'SIMI'))) {
            $invoice_numbers = explode(' ', (string) $payment->reference);
            $invoiceIds = [];
            foreach ($invoice_numbers as $invoice_number) {
                $invoiceId = SimInvoice::getIdFromInvoiceNumber($invoice_number);
                if ($invoiceId) {
                    $invoiceIds[] = $invoiceId;
                }
            }
            $existingInvoices = SimInvoice::with('company.account')
                ->whereIn('id', $invoiceIds)
                ->get();
            $invoices = SimInvoice::with('company.account')
                ->whereIn('vendor_id', $existingInvoices->pluck('vendor_id'))
                ->where('status', '!=', 1)
                ->whereNotIn('id', $invoiceIds)
                ->get();
            $invoiceType = 'sim';
            $payeeOptions = $this->buildPayeeOptionsFromInvoices(
                $existingInvoices->concat($invoices),
                'sim'
            );
            $accountIds = $payeeOptions->pluck('account_id')->all();
            $lockedPayee = $payeeOptions->firstWhere('account_id', (int) $payment->payee_account_id)
                ?: $payeeOptions->first();
        }
        if ((str_contains((string) $payment->reference, 'RINV'))) {
            $invoice_numbers = explode(' ', (string) $payment->reference);
            $invoiceIds = [];
            foreach ($invoice_numbers as $invoice_number) {
                if (preg_match('/RINV-?0*(\d+)/i', $invoice_number, $matches)) {
                    $invoiceIds[] = (int) $matches[1];
                }
            }
            $existingInvoices = RiderInvoices::with(['rider.account', 'items'])
                ->whereIn('id', $invoiceIds)
                ->get();
            $invoices = RiderInvoices::with(['rider.account', 'items'])
                ->payable()
                ->whereHas('rider', fn ($q) => $q->whereNotNull('account_id'))
                ->whereNotIn('id', $invoiceIds)
                ->orderBy('billing_month', 'desc')
                ->get();
            $invoiceType = 'rider';
            $payeeOptions = $this->attachLedgerBalancesToPayeeOptions(
                $this->buildPayeeOptionsFromEntities(
                    Riders::with('account')
                        ->whereNotNull('account_id')
                        ->orderBy('name')
                        ->get()
                )
            );
            $accountIds = $payeeOptions->pluck('account_id')->all();
            $lockedPayee = null;
            $preferredPayeeAccountId = $payment->payee_account_id;
        }

        // For module-linked payments without a selectable list, surface the saved payee as locked.
        if ($invoiceType && ! $lockedPayee && $payment->payee_account_id && $payeeOptions->isEmpty()) {
            $lockedPayee = $this->makePayeeOptionFromAccountId($payment->payee_account_id);
        }

        if ($invoiceType === 'rider' && $lockedPayee && ! isset($lockedPayee['ledger_balance'])) {
            $lockedPayee['ledger_balance'] = $this->ledgerBalanceForAccount((int) ($lockedPayee['account_id'] ?? 0));
        }

        $this->applySequentialSalaryInvoiceBalances($invoices, $existingInvoices, $invoiceType);

        if ($invoiceType === 'rider') {
            $this->normalizeRiderPaymentFormBalances($invoices);
            if (! empty($existingInvoices)) {
                $this->normalizeRiderPaymentFormBalances($existingInvoices);
            }
        }

        $banks = Banks::active()->get();
        if (! empty($payment->billing_month)) {
            $payment->billing_month = Carbon::parse($payment->billing_month)->format('Y-m');
        }

        return compact(
            'payment',
            'banks',
            'accountIds',
            'existingInvoices',
            'invoices',
            'invoiceType',
            'payeeOptions',
            'lockedPayee',
            'preferredPayeeAccountId'
        );
    }
}
