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
use App\Traits\GlobalPagination;
use App\Support\PublicStorageDisk;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $query = Payment::query()->with(['payeeAccount', 'voucher'])->orderBy('date_of_payment', 'desc');
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

        return view('payments.index', compact('data', 'totals'));
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
            $payeeOptions = $this->buildPayeeOptionsFromEntities(
                Riders::with('account')
                    ->whereNotNull('account_id')
                    ->orderBy('name')
                    ->get()
            );
            $accountIds = $payeeOptions->pluck('account_id')->all();

            if ($riderId) {
                $lockedPayee = $this->makePayeeOption(Riders::with('account')->find($riderId));
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
        $rules = [
            'reference' => 'nullable|string|max:255',
            'amount_type' => 'required|string|in:Cash,Online,Cheque,Credit',
            'bank_id' => 'required|numeric|exists:banks,id',
            'payee_account_id' => 'required|numeric|exists:accounts,id',
            'date_of_payment' => 'required|date',
            'date_of_invoice' => 'nullable|date',
            'billing_month' => 'required|date_format:Y-m',
            'amount' => 'required|numeric|min:0.01',
            'bank_charges' => 'nullable|numeric|min:0',
            'description' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'numeric|required',
            'payment_amounts' => 'nullable|array',
            'payment_amounts.*' => 'numeric|min:0.01',
        ];

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
            'payment_amounts.*.min' => 'Payment amounts must be greater than zero.',
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

        $input = $request->all();
        $input['created_by'] = auth()->id();
        $input['billing_month'] = $input['billing_month'] . '-01';
        $input['branch_id'] = Accounts::where('id', $input['payee_account_id'])->value('branch_id');
        $input['amount'] = $totalAmount;

        try {
            DB::beginTransaction();

            // Validate invoice payments if invoices are selected
            if ($request->has('invoice_ids') && count($input['invoice_ids']) > 0) {
                $paymentAmounts = $request->input('payment_amounts');
                $totalPayment = array_sum($paymentAmounts);

                if ($totalPayment > $paymentAmount) {
                    throw new \Exception('Total payment amount for selected invoices cannot exceed the payment amount.');
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

                        if ($invoicePaymentAmount > 0) {
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

                    foreach ($invoices as $invoice) {
                        $invoicePaymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);

                        if ($invoicePaymentAmount > 0) {
                            // Payment already saved; balance is remaining due after deductions/payments.
                            $invoice->update([
                                'status' => ((float) $invoice->balance) <= 0.01 ? 1 : 3,
                                'updated_by' => auth()->id(),
                            ]);
                        }
                    }
                } elseif ($invoiceType == 'rider') {
                    $invoices = RiderInvoices::with(['rider.account', 'items'])->whereIn('id', $invoiceIds)->get();

                    foreach ($invoices as $invoice) {
                        $invoicePaymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);

                        if ($invoicePaymentAmount > 0) {
                            // Payment already saved; balance is remaining due after deductions/payments.
                            $invoice->update([
                                'status' => ((float) $invoice->balance) <= 0.01 ? 1 : 3,
                                'updated_by' => auth()->id(),
                            ]);
                        }
                    }
                } elseif ($invoiceType == 'sim') {
                    $invoices = SimInvoice::whereIn('id', $invoiceIds)->get();

                    foreach ($invoices as $invoice) {
                        $invoicePaymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);
                        $partialAmount = $invoice->partial_paid_amount ?? [];
                        $partialAmount[$payment->id] = $invoicePaymentAmount;

                        if ($invoicePaymentAmount > 0) {
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

                        if ($invoicePaymentAmount > 0) {
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

            // 1. Credit the paying account (BANK) - CREDIT entry (money going out)
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $payment->id,
                'reference_type' => 'PV',
                'account_id' => $payingAccountId, // Bank account (credit)
                'credit' => $totalAmount,
                'debit' => 0,
                'billing_month' => $billingMonth,
                'narration' => $desc,
                'branch_id' => $payment->branch_id,
            ]);

            // 2. Debit the payee account (receiving account) - DEBIT entry (money coming in)
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $payment->id,
                'reference_type' => 'PV',
                'account_id' => $request->input('payee_account_id'), // Receiving account (debit)
                'credit' => 0,
                'debit' => $paymentAmount,
                'billing_month' => $billingMonth,
                'branch_id' => $payment->branch_id,
                'narration' => $desc,
            ]);

            // 3. Handle bank charges if any
            if ($bankCharges > 0) {
                $bankAccount = GlobalAccounts::account('BANK_CHARGES');
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $date,
                    'reference_id' => $payment->id,
                    'reference_type' => 'PV',
                    'account_id' => $bankAccount->id,
                    'credit' => 0,
                    'debit' => $bankCharges,
                    'billing_month' => $billingMonth,
                    'branch_id' => $payment->branch_id,
                    'narration' => 'Bank charges for ( ' . $payment->description . ' )',
                ]);
            }

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

    public function show($comapny_slug, $id)
    {
        $payment = $this->paymentsRepository->find($id);
        if (empty($payment)) {
            Flash::error('Payment not found');

            return redirect(route('payments.index'));
        }

        return view('payments.show')->with('payment', $payment);
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
        $invoices = null;
        $existingInvoices = null;
        $accountIds = null;
        $invoiceType = null;
        $payeeOptions = collect();
        $lockedPayee = null;
        $preferredPayeeAccountId = null;

        if ((str_contains($payment->reference, 'LCI'))) {
            $invoice_numbers = explode(' ', $payment->reference);
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
        if ((str_contains($payment->reference, 'SUP'))) {
            $invoice_numbers = explode(' ', $payment->reference);
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
        if ((str_contains($payment->reference, 'EMP_INV'))) {
            $invoice_numbers = explode(' ', $payment->reference);
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
        if ((str_contains($payment->reference, 'SIMI'))) {
            $invoice_numbers = explode(' ', $payment->reference);
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
        if ((str_contains($payment->reference, 'RINV'))) {
            $invoice_numbers = explode(' ', $payment->reference);
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
            $payeeOptions = $this->buildPayeeOptionsFromEntities(
                Riders::with('account')
                    ->whereNotNull('account_id')
                    ->orderBy('name')
                    ->get()
            );
            $accountIds = $payeeOptions->pluck('account_id')->all();
            $lockedPayee = null;
            $preferredPayeeAccountId = $payment->payee_account_id;
        }

        // For module-linked payments without a selectable list, surface the saved payee as locked.
        if ($invoiceType && ! $lockedPayee && $payment->payee_account_id && $payeeOptions->isEmpty()) {
            $lockedPayee = $this->makePayeeOptionFromAccountId($payment->payee_account_id);
        }

        $banks = Banks::active()->get();
        $payment->billing_month = Carbon::parse($payment->billing_month)->format('Y-m');

        return view('payments.edit', compact(
            'payment',
            'banks',
            'accountIds',
            'existingInvoices',
            'invoices',
            'invoiceType',
            'payeeOptions',
            'lockedPayee',
            'preferredPayeeAccountId'
        ));
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
            'bank_charges_account' => 'required_if:bank_charges,>0|nullable|numeric|exists:accounts,id',
            'description' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'numeric|required',
            'payment_amounts' => 'nullable|array',
            'payment_amounts.*' => 'numeric|min:0.01',
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
            'bank_charges_account.required_if' => 'Please select a bank charges account when bank charges are entered',
            'invoice_ids.*.exists' => 'One or more selected invoices are invalid.',
            'payment_amounts.*.min' => 'Payment amounts must be greater than zero.',
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

            // Prepare data for payment update
            $input = $request->all();
            $input['branch_id'] = Accounts::where('id', $input['payee_account_id'])->value('branch_id');
            $input['amount'] = $totalAmount;
            $input['updated_by'] = auth()->id();
            $pending = null;
            $partial = null;
            $paid = null;

            // Handle existing invoice payments (remove old ones)
            if ($request->has('invoice_ids') && count($input['invoice_ids']) > 0) {
                $paymentAmounts = $request->input('payment_amounts');
                $totalPayment = array_sum($paymentAmounts);

                if ($totalPayment > $paymentAmount) {
                    throw new \Exception('Total payment amount for selected invoices cannot exceed the payment amount.');
                }

                // Get existing invoice numbers from payment reference
                $invoice_numbers = explode(' ', $payment->reference);
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
                    if (in_array($input['invoice_type'] ?? null, ['rider', 'employee'], true)) {
                        $invoice->status = $pending;
                        $invoice->updated_by = auth()->id();
                        $invoice->save();
                        continue;
                    }

                    $partialAmount = $invoice->partial_paid_amount ?? [];
                    unset($partialAmount[$payment->id]); // Remove payment for this payment record
                    $invoice->partial_paid_amount = $partialAmount;

                    if (count($partialAmount) < 1) {
                        $invoice->status = $pending; // Revert to pending if no payments left
                    } else {
                        $invoice->status = $partial; // Otherwise, it's still partially paid
                    }
                    if (($input['invoice_type'] ?? null) != 'sim') {
                        $invoice->updated_by = auth()->id();
                    }
                    $invoice->save();
                }
            }

            // Fill the model with new data and check for changes
            $payment->fill($input);
            $paymentHasChanges = $payment->isDirty();
            $hasNewAttachment = $request->hasFile('attachment');

            // If nothing changed, return early
            if (! $paymentHasChanges && ! $hasNewAttachment) {
                DB::commit();

                return response()->json([
                    'message' => 'Nothing New Entered to Update',
                    'reload' => true,
                ], 200);
            }

            // Process new invoice payments
            if ($request->has('invoice_ids') && count($input['invoice_ids']) > 0) {
                $invoiceIds = $request->input('invoice_ids');
                $paymentAmounts = $request->input('payment_amounts');
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

                    if ($invoicePaymentAmount <= 0) {
                        continue;
                    }

                    if (in_array($input['invoice_type'] ?? null, ['rider', 'employee'], true)) {
                        // Payment row already updated; balance reflects remaining due after this payment.
                        $invoice->status = ((float) $invoice->balance) <= 0.01 ? $paid : $partial;
                        $invoice->updated_by = auth()->id();
                        $invoice->save();
                        continue;
                    }

                    $partialAmount = $invoice->partial_paid_amount ?? [];
                    $partialAmount[$payment->id] = $invoicePaymentAmount;

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

                // 1. CREDIT transaction (paying account - BANK - money going out)
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $date,
                    'reference_id' => $payment->id,
                    'reference_type' => 'PV',
                    'account_id' => $payingAccountId,
                    'credit' => $totalAmount,
                    'debit' => 0,
                    'billing_month' => $billingMonth,
                    'branch_id' => $payment->branch_id,
                    'narration' => $desc,
                ]);

                // 2. DEBIT transaction (receiving account - money coming in)
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $date,
                    'reference_id' => $payment->id,
                    'reference_type' => 'PV',
                    'account_id' => $request->input('payee_account_id'),
                    'credit' => 0,
                    'debit' => $paymentAmount,
                    'billing_month' => $billingMonth,
                    'branch_id' => $payment->branch_id,
                    'narration' => $desc,
                ]);

                // 3. Bank charges transaction (if any)
                if ($bankCharges > 0) {
                    if (! $request->input('bank_charges_account')) {
                        throw new \Exception('No Account Selected for Bank Charges');
                    }

                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $date,
                        'reference_id' => $payment->id,
                        'reference_type' => 'PV',
                        'account_id' => $request->input('bank_charges_account'),
                        'credit' => 0,
                        'debit' => $bankCharges,
                        'billing_month' => $billingMonth,
                        'branch_id' => $payment->branch_id,
                        'narration' => 'Bank charges for ( ' . $desc . ' )',
                    ]);
                }

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

                $payment->voucher->fill($voucherData);

                // Save voucher if it has changes
                if ($payment->voucher->isDirty()) {
                    $payment->voucher->save();
                }
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
        if (!empty($entity->employee_id)) {
            $codePrefix = (string) $entity->employee_id . ' - ';
        } elseif (!empty($entity->rider_id)) {
            $codePrefix = (string) $entity->rider_id . ' - ';
        }

        $displayName = $codePrefix . ($name ?: '-');
        $accountCode = (string) ($account->account_code ?? '');

        return [
            'account_id' => (int) $entity->account_id,
            'entity_id' => (int) $entity->id,
            'name' => $name ?: '-',
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

    public function clone($comapny_slug, $id)
    {

        $payment = Payment::find($id);
        if (empty($payment)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Payment Not found'], 404);
            }
            Flash::error('Payment not found');

            return redirect()->back();
        }

        $banks = Banks::active()->get();
        $payment->billing_month = Carbon::parse($payment->billing_month)->format('Y-m');
        $payment->amount = $payment->amount - $payment->bank_charges;
        $payeeOptions = collect();
        $lockedPayee = $this->makePayeeOptionFromAccountId($payment->payee_account_id);

        return view('payments.create', compact('payment', 'banks', 'payeeOptions', 'lockedPayee'));
    }
}
