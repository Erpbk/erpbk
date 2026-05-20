<?php

namespace App\Http\Controllers;

use App\Repositories\ReceiptsRepository;
use App\Models\Receipt;
use App\Models\Banks;
use App\Models\CustomerInvoices;
use App\Models\LeasingCompanies;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Models\Customers;
use App\Models\Accounts;
use App\Models\LeasingCompanyInvoice;
use App\Models\LeasingCompanyBillingInvoice;
use App\Models\BikeRentCompany;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Illuminate\Support\Facades\DB;
use Flash;


class ReceiptController extends Controller
{
    use GlobalPagination;
    private $receiptsRepository;

    public function __construct(ReceiptsRepository $receiptsRepo)
    {
        $this->receiptsRepository = $receiptsRepo;
    }

    public function index(Request $request)
    {
        $fundIn = 0;
        $fundOut = 0;
        $banks = Banks::all();
        foreach ($banks as $bank) {
            $credit = Transactions::where('account_id', $bank->account_id)->sum('credit');
            $debit  = Transactions::where('account_id', $bank->account_id)->sum('debit');
            $balance = $debit - $credit;
            $fundIn += $debit;
            $fundOut += $credit;
            $bank->update(['balance' => $balance]);
        }
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Receipt::query()->with(['payerAccount', 'payeeAccount'])->orderBy('date_of_receipt', 'desc');
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        $fundsIn = 0;
        $fundsOut = 0;
        $banks = Banks::all();
        foreach ($banks as $bank) {
            $credit = Transactions::where('account_id', $bank->account_id)->sum('credit');
            $debit  = Transactions::where('account_id', $bank->account_id)->sum('debit');
            $fundsIn += $debit;
            $fundsOut += $credit;
        }
        return view('receipts.index', compact('data', 'fundsIn', 'fundsOut'));
    }

    public function create()
    {
        $accountId = request()->input('id') ?? null;
        $bikeRentCompanyId = request()->input('bike_rent_company_id') ?? request()->input('leasing_company_id') ?? null;
        $customerId = request()->input('customer_id') ?? null;
        $customerIds = null;
        $accountIds = null;
        $leasingIds = null;
        $invoiceType = null;
        if (request()->input('customer_receipt')) {
            $customerIds = Customers::pluck('id')->toArray();
            $accountIds = Customers::pluck('account_id')->toArray();
        }
        if (request()->input('leasing_receipt')) {
            $leasingIds = BikeRentCompany::pluck('id')->toArray();
            $accountIds = BikeRentCompany::pluck('account_id')->toArray();
        }
        if ($customerId) {
            $invoices = CustomerInvoices::with('customer')
                ->where('customer_id', $customerId)
                ->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere('status', 'partially_paid');
                })
                ->get();
            $accountIds = Customers::where('id', $customerId)->pluck('account_id')->toArray();
        } elseif ($customerIds) {
            $invoices = CustomerInvoices::with('customer')
                ->whereIn('customer_id', $customerIds)
                ->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere('status', 'partially_paid');
                })
                ->get();
        } elseif ($bikeRentCompanyId) {
            $invoices = LeasingCompanyBillingInvoice::with('customer')
                ->where('customer_id', $bikeRentCompanyId)
                ->where(function ($q) {
                    $q->where('status', 0)
                        ->orWhere('status', 3);
                })
                ->get();
            $accountIds = BikeRentCompany::where('id', $bikeRentCompanyId)->pluck('account_id')->toArray();
        } elseif ($leasingIds) {
            $invoices = LeasingCompanyBillingInvoice::with('customer')
                ->whereIn('customer_id', $leasingIds)
                ->where(function ($q) {
                    $q->where('status', 0)
                        ->orWhere('status', 3);
                })
                ->get();
        } else {
            $invoices = null;
        }
        $receipt = null;
        $customerIds = $accountIds;

        if ($accountId) {
            $banks = Banks::active()->get();
            $bank = Banks::find($accountId);
            $receipt = Receipt::where('bank_id', $accountId)->first();
            return view('receipts.create', compact('bank', 'banks', 'receipt'));
        } elseif ($bikeRentCompanyId || $leasingIds) {
            $leasingCompany = BikeRentCompany::find($bikeRentCompanyId ?? 0);
            $banks = Banks::with('account')->active()->get();
            $invoiceType = 'leasingCompany';
            return view('receipts.create', compact('leasingCompany', 'banks', 'receipt', 'invoices', 'customerIds', 'invoiceType', 'customerId'));
        } elseif ($customerId || $customerIds) {
            $banks = Banks::with('account')->active()->get();
            $invoiceType = 'customer';
            return view('receipts.create', compact('banks', 'receipt', 'invoices', 'customerIds', 'customerId', 'invoiceType'));
        } else {
            $banks = Banks::with('account')->active()->get();
            return view('receipts.create', compact('banks', 'receipt', 'invoices'));
        }
    }

    public function store(Request $request)
    {

        $rules = [
            'reference' => 'nullable|string|max:255',
            'amount_type' => 'required|string|in:Cash,Online,Cheque,Credit',
            'bank_id' => 'required|numeric|exists:banks,id',
            'date_of_receipt' => 'required|date',
            'billing_month' => 'required|date',
            'description' => 'required|string|max:500',
            'payer_account_id' => 'required|numeric|exists:accounts,id',
            'amount' => 'required|numeric|min:1',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'numeric|exists:customer_invoices,id',
            'payment_amounts' => 'nullable|array',
            'payment_amounts.*' => 'numeric|min:0.01',
        ];

        $messages = [
            'bank_id.required' => 'Bank Account is Required',
            'date_of_receipt.required' => 'Receipt date is Required',
            'billing_month.required' => 'Billing month is Required',
            'description.required' => 'Narration is Required',
            'payer_account_id.required' => 'Sender Account is Required',
            'amount.required' => 'Amount is Required',
            'amount.min' => 'Amount must be greater than zero',
            'invoice_ids.*.exists' => 'One or more selected invoices are invalid.',
            'payment_amounts.*.min' => 'Payment amounts must be greater than zero.',
        ];

        $this->validate($request, $rules, $messages);

        $bank = Banks::find($request->input('bank_id'));
        if (!$bank) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Selected bank account not found'], 422);
            }
            Flash::error('Selected bank account not found.');
            return redirect()->back()->withInput();
        }

        $input = $request->all();

        $input['created_by'] = auth()->id();
        $input['billing_month'] = $input['billing_month'] . '-01';
        $input['branch_id'] = Accounts::where('id', $input['payer_account_id'])->value('branch_id');
        $input['account_id'] = $bank->account_id;
        try {
            DB::beginTransaction();
            if ($request->has('invoice_ids') && count($input['invoice_ids']) > 0) {
                $paymentAmounts = $request->input('payment_amounts');
                $totalPayment = array_sum($paymentAmounts);
                if ($totalPayment > $input['amount']) {
                    throw new \Exception('Total payment amount for selected invoices cannot exceed the receipt amount.');
                }
            }
            $receipt = Receipt::create($input);

            if ($request->has('invoice_ids') && count($input['invoice_ids']) > 0) {
                $invoiceIds = $request->input('invoice_ids');
                if ($input['invoice_type'] == 'customer') {
                    $invoices = CustomerInvoices::whereIn('id', $invoiceIds)->get();
                    foreach ($invoices as $invoice) {
                        $paymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);
                        $partialAmount = $invoice->partial_paid_amount ?? [];
                        $partialAmount[$receipt->id] = $paymentAmount;
                        if ($paymentAmount > 0) {
                            // Update the invoice status based on the payment
                            if ($paymentAmount == ($invoice->total - ($invoice->paid_amount ?? 0))) {
                                $invoice->update(['status' => 'paid', 'partial_paid_amount' => $partialAmount, 'updated_by' => auth()->id()]);
                            } else {
                                $invoice->update(['status' => 'partially_paid', 'partial_paid_amount' => $partialAmount, 'updated_by' => auth()->id()]);
                            }
                        }
                    }
                } else {
                    $invoices = LeasingCompanyBillingInvoice::whereIn('id', $invoiceIds)->get();
                    foreach ($invoices as $invoice) {
                        $paymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);
                        $partialAmount = $invoice->partial_paid_amount ?? [];
                        $partialAmount[$receipt->id] = $paymentAmount;
                        if ($paymentAmount > 0) {

                            // Update the invoice status based on the payment
                            if ($paymentAmount == ($invoice->total_amount - ($invoice->paid_amount ?? 0))) {
                                $invoice->update(['status' => 1, 'partial_paid_amount' => $partialAmount, 'updated_by' => auth()->id()]);
                            } else {
                                $invoice->update(['status' => 3, 'partial_paid_amount' => $partialAmount, 'updated_by' => auth()->id()]);
                            }
                        }
                    }
                }
            }

            $transCode = \App\Helpers\Account::trans_code();
            $date = $input['date_of_receipt'] ?? now();
            $billingMonth = $input['billing_month'];
            $desc = $input['description'];

            // DEBIT the receiving account (BANK or CASH)
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $receipt->id,
                'reference_type' => 'RV',
                'account_id' => $bank->account_id,
                'credit' => 0,
                'debit' => $receipt->amount,
                'billing_month' => $billingMonth,
                'narration' => $desc,
                'branch_id' => $receipt->branch_id,
            ]);

            // CREDIT payer account

            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $receipt->id,
                'reference_type' => 'RV',
                'account_id' => $receipt->payer_account_id,
                'credit' => $receipt->amount,
                'debit' => 0,
                'billing_month' => $billingMonth,
                'branch_id' => $receipt->branch_id,
                'narration' => $desc,
            ]);



            // voucher
            $voucherData = [
                'trans_date' => $date,
                'trans_code' => $transCode,
                'reference_number' => $receipt->reference,
                'billing_month' => $billingMonth,
                'payment_to' => $bank->account_id,
                'amount' => $receipt->amount,
                'voucher_type' => 'RV',
                'remarks' => 'Receipt Voucher',
                'ref_id' => $receipt->id,
                'Created_By' => auth()->id(),
                'status' => 1,
                'branch_id' => $receipt->branch_id,
                'custom_field_values' => $request->input('voucher_custom_fields', []),
            ];

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/vouchers', $fileName);
                $voucherData['attach_file'] = $fileName;
            }

            $voucher = Vouchers::create($voucherData);

            // Update receipt with voucher info and detailed account data
            $receipt->update([
                'voucher_id' => $voucher->id,
                'attachment' => $voucher->attach_file ?? null,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Receipt creation failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            if ($request->ajax()) {
                return response()->json(['message' => "An Error Occurred: " . $e->getMessage()], 500);
            }

            Flash::error('Error Occurred: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        if ($request->ajax()) {
            return response()->json([
                "message" => "Receipt Added Successfully",
                'reload' => true
            ]);
        }

        Flash::success('Receipt added successfully.');
        return redirect()->bacK();
    }

    public function show($comapny_slug, $id)
    {
        $receipt = $this->receiptsRepository->find($id);
        if (empty($receipt)) {
            Flash::error('Receipt not found');
            return redirect(route('receipts.index'));
        }
        return view('receipts.show')->with('receipt', $receipt);
    }

    public function edit(Request $request, $comapny_slug, $id)
    {
        $receipt = Receipt::find($id);
        $existingInvoices = null;
        $customerIds = null;
        $customerId = null;
        $invoices = null;
        $invoiceType = null;
        if (empty($receipt)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Receipt Not found'], 404);
            }
            Flash::error('Receipt not found');
            return redirect()->back();
        }
        if (str_contains($receipt->reference, 'CI-')) {
            $invoiceType = 'customer';
            $invoice_numbers = explode(' ', $receipt->reference);
            $invoiceIds = [];
            foreach ($invoice_numbers as $invoice_number) {
                $invoiceId = CustomerInvoices::getIdFromInvoiceNumber($invoice_number);
                if ($invoiceId) {
                    $invoiceIds[] = $invoiceId;
                }
            }
            $existingInvoices = CustomerInvoices::with('customer')
                ->whereIn('id', $invoiceIds)
                ->get();
            $invoices = CustomerInvoices::with('customer')
                ->whereIn('customer_id', $existingInvoices->pluck('customer_id'))
                ->where(function ($query) use ($invoiceIds) {
                    $query->whereIn('status', ['pending', 'partially_paid'])
                        ->WhereNotIn('id', $invoiceIds);
                })
                ->get();
            $customerIds = $existingInvoices->pluck('customer.account_id')->toArray();
            $customerId = $existingInvoices->first()->customer_id ?? null;
        }
        if ((str_contains($receipt->reference, 'LBI-'))) {
            $invoiceType = 'leasingCompany';
            $invoice_numbers = explode(' ', $receipt->reference);
            $invoiceIds = [];
            foreach ($invoice_numbers as $invoice_number) {
                $invoiceId = LeasingCompanyBillingInvoice::getIdFromInvoiceNumber($invoice_number);
                if ($invoiceId) {
                    $invoiceIds[] = $invoiceId;
                }
            }
            $existingInvoices = LeasingCompanyBillingInvoice::with('customer')
                ->whereIn('id', $invoiceIds)
                ->get();
            $invoices = LeasingCompanyBillingInvoice::with('customer')
                ->whereIn('customer_id', $existingInvoices->pluck('customer_id'))
                ->where(function ($query) use ($invoiceIds) {
                    $query->whereIn('status', [0, 3])
                        ->WhereNotIn('id', $invoiceIds);
                })
                ->get();
            $customerIds = $existingInvoices->pluck('customer.account_id')->toArray();
            $customerId = $existingInvoices->first()->customer_id ?? null;
        }

        $banks = Banks::active()->get();

        $receipt->billing_month = \Carbon\Carbon::parse($receipt->billing_month)->format('Y-m');

        $payerAccounts = $this->resolvePayerAccountsForForm($receipt, $customerIds);
        $leasingCompany = null;
        if ($invoiceType === 'leasingCompany' && $receipt->payer_account_id) {
            $leasingCompany = BikeRentCompany::where('account_id', $receipt->payer_account_id)->first();
        }

        return view('receipts.edit', compact('receipt', 'banks', 'invoices', 'existingInvoices', 'customerIds', 'customerId', 'invoiceType', 'payerAccounts', 'leasingCompany'));
    }

    /**
     * Accounts for the sending-account dropdown (edit/create).
     * Always includes the receipt's current payer so Select2 can display it.
     */
    private function resolvePayerAccountsForForm(?Receipt $receipt, ?array $filterAccountIds)
    {
        $query = Accounts::query()->where('status', 1)->orderBy('name');

        if (!empty($filterAccountIds)) {
            $ids = collect($filterAccountIds);
            if ($receipt?->payer_account_id) {
                $ids->push($receipt->payer_account_id);
            }
            $query->whereIn('id', $ids->unique()->filter()->values());
        }

        $accounts = $query->get();

        if ($receipt?->payer_account_id && !$accounts->contains('id', (int) $receipt->payer_account_id)) {
            $currentPayer = Accounts::withoutGlobalScope('branch')->find($receipt->payer_account_id);
            if ($currentPayer) {
                $accounts->prepend($currentPayer);
            }
        }

        return $accounts;
    }

    public function update(Request $request, $comapny_slug, $id)
    {
        $receipt = Receipt::find($id);
        if (empty($receipt)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Receipt Not Found'], 500);
            }
            Flash::error('Receipt not found!');
            return redirect()->back();
        }

        $request['billing_month'] = $request['billing_month'] . "-01";

        $rules = [
            'reference' => 'nullable|string|max:255',
            'amount_type' => 'required|string|in:Cash,Online,Cheque,Credit',
            'bank_id' => 'required|numeric|exists:banks,id',
            'date_of_receipt' => 'required|date',
            'billing_month' => 'required|date',
            'description' => 'required|string|max:500',
            'payer_account_id' => 'required|numeric|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'invoice_ids' => 'nullable|array',
            'invoice_ids.*' => 'numeric|exists:customer_invoices,id',
            'payment_amounts' => 'nullable|array',
            'payment_amounts.*' => 'numeric|min:0.01',
        ];

        $messages = [
            'bank_id.required' => 'Bank Account is Required',
            'date_of_receipt.required' => 'Receipt date is Required',
            'billing_month.required' => 'Billing month is Required',
            'description.required' => 'Narration for Transaction is Required',
            'payer_account_id.required' => 'Sender Account is Required',
            'amount.required' => 'Amount is Required',
            'amount.min' => 'Amount must be greater than zero',
            'invoice_ids.*.exists' => 'One or more selected invoices are invalid.',
            'payment_amounts.*.min' => 'Payment amounts must be greater than zero.',
        ];

        $this->validate($request, $rules, $messages);

        // Get bank account
        $bank = Banks::find($request->input('bank_id'));
        if (!$bank) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Selected bank account not found'], 422);
            }
            Flash::error('Selected bank account not found.');
            return redirect()->back()->withInput();
        }

        try {
            DB::beginTransaction();

            // Prepare data for receipt update
            $input = $request->all();
            $input['billing_month'] = $input['billing_month'] . '-01';
            $input['branch_id'] = Accounts::where('id', $input['payer_account_id'])->value('branch_id');
            $input['updated_by'] = auth()->id();
            $pending = null;
            $partial = null;
            $paid = null;
            if ($request->has('invoice_ids') && count($input['invoice_ids']) > 0) {
                $paymentAmounts = $request->input('payment_amounts');
                $totalPayment = array_sum($paymentAmounts);
                if ($totalPayment > $input['amount']) {
                    throw new \Exception('Total payment amount for selected invoices cannot exceed the receipt amount.');
                }
                $invoice_numbers = explode(' ', $receipt->reference);
                $invoiceIds = [];
                if ($input['invoice_type'] == 'customer') {
                    foreach ($invoice_numbers as $invoice_number) {
                        $id = CustomerInvoices::getIdFromInvoiceNumber($invoice_number);
                        if ($id) {
                            $invoiceIds[] = $id;
                        }
                    }
                    $existingInvoices = CustomerInvoices::with('customer')
                        ->whereIn('id', $invoiceIds)
                        ->get();
                    $pending = 'pending';
                    $partial = 'partially_paid';
                } else {
                    foreach ($invoice_numbers as $invoice_number) {
                        $id = LeasingCompanyBillingInvoice::getIdFromInvoiceNumber($invoice_number);
                        if ($id) {
                            $invoiceIds[] = $id;
                        }
                    }
                    $existingInvoices = LeasingCompanyBillingInvoice::with('customer')
                        ->whereIn('id', $invoiceIds)
                        ->get();
                    $pending = 0;
                    $partial = 3;
                }
                foreach ($existingInvoices as $invoice) {
                    $partialAmount = $invoice->partial_paid_amount ?? [];
                    unset($partialAmount[$receipt->id]); // Remove payment for this receipt
                    $invoice->partial_paid_amount = $partialAmount;
                    if (count($partialAmount) < 1) {
                        $invoice->status = $pending; // Revert to pending if no payments left
                    } else {
                        $invoice->status = $partial; // Otherwise, it's still partially paid
                    }
                    $invoice->updated_by = auth()->id();
                    $invoice->save();
                }
            }
            // Fill the model with new data and check for changes
            $receipt->fill($input);
            $receiptHasChanges = $receipt->isDirty();
            $hasNewAttachment = $request->hasFile('attachment');


            // If nothing changed, return early
            if (!$receiptHasChanges && !$hasNewAttachment) {
                DB::commit();
                return response()->json([
                    'message' => 'Nothing New Entered to Update',
                    'reload' => true
                ], 200);
            }
            if ($request->has('invoice_ids') && count($input['invoice_ids']) > 0) {
                $invoiceIds = $request->input('invoice_ids');
                if ($input['invoice_type'] == 'customer') {
                    $invoices = CustomerInvoices::whereIn('id', $invoiceIds)->get();
                    $partial = 'partially_paid';
                    $paid = 'paid';
                } else {
                    $invoices = LeasingCompanyBillingInvoice::whereIn('id', $invoiceIds)->get();
                    $partial = 3;
                    $paid = 1;
                }

                foreach ($invoices as $invoice) {
                    $paymentAmount = floatval($paymentAmounts[$invoice->id] ?? 0);
                    $partialAmount = $invoice->partial_paid_amount ?? [];
                    $partialAmount[$receipt->id] = $paymentAmount;
                    if ($paymentAmount > 0) {

                        // Update the invoice status based on the payment
                        if ($paymentAmount == (($invoice->total ?? $invoice->total_amount) - ($invoice->paid_amount ?? 0))) {
                            $invoice->update(['status' => $paid, 'partial_paid_amount' => $partialAmount, 'updated_by' => auth()->id()]);
                        } else {
                            $invoice->update(['status' => $partial, 'partial_paid_amount' => $partialAmount, 'updated_by' => auth()->id()]);
                        }
                    }
                }
            }

            // Save receipt if it has changes
            if ($receiptHasChanges) {
                $receipt->save();
                if (!$receipt->voucher) {
                    throw new \Exception('Voucher not found for this receipt');
                }

                $transCode = $receipt->voucher->trans_code;
                $date = $request->input('date_of_receipt');
                $billingMonth = $request->input('billing_month');
                $desc = $request->input('description');
                $amount = floatval($request->input('amount'));

                // Delete existing transactions
                Transactions::where('trans_code', $transCode)->delete();

                // Create DEBIT transaction (receiving account - BANK)
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $date,
                    'reference_id' => $receipt->id,
                    'reference_type' => 'RV',
                    'account_id' => $bank->account_id,
                    'debit' => $amount,
                    'credit' => 0,
                    'billing_month' => $billingMonth,
                    'branch_id' => $receipt->branch_id,
                    'narration' => $desc,
                ]);

                // Create CREDIT transaction (sending account)
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $date,
                    'reference_id' => $receipt->id,
                    'reference_type' => 'RV',
                    'account_id' => $request->input('payer_account_id'),
                    'credit' => $amount,
                    'debit' => 0,
                    'billing_month' => $billingMonth,
                    'branch_id' => $receipt->branch_id,
                    'narration' => $desc,
                ]);

                // Update voucher
                $voucherData = [
                    'trans_date' => $date,
                    'billing_month' => $billingMonth,
                    'reference_number' => $receipt->reference,
                    'payment_to' => $bank->account_id,
                    'amount' => $amount,
                    'branch_id' => $receipt->branch_id,
                    'Updated_By' => auth()->id(),
                ];

                $receipt->voucher->fill($voucherData);

                // Save voucher if it has changes
                if ($receipt->voucher->isDirty()) {
                    $receipt->voucher->save();
                }
            }

            // Handle attachment if provided (can be updated independently)
            if ($hasNewAttachment) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/vouchers', $fileName);

                $receipt->update(['attachment' => $fileName]);

                if ($receipt->voucher) {
                    $receipt->voucher->update(['attach_file' => $fileName]);
                }
            }

            DB::commit();

            // Determine appropriate success message
            $message = 'Receipt Updated Successfully';
            if ($hasNewAttachment && !$receiptHasChanges) {
                $message = 'File uploaded Successfully';
            }

            return response()->json([
                'message' => $message,
                'reload' => true
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Receipt update failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }

            Flash::error('Error Occurred: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy(Request $request, $comapny_slug, $id)
    {
        $receipt = Receipt::find($id);
        if (empty($receipt)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Receipt Not Found']);
            }
            Flash::error('Receipt not found!');
            return redirect()->back();
        } else {
            Transactions::where('trans_code', $receipt->voucher->trans_code)->delete();
            Vouchers::where('id', $receipt->voucher_id)->delete();
            if ($receipt->amount_type == 'Cheque') {
                // Also delete associated cheque record if receipt was created by cheque
                $cheque = \App\Models\Cheques::where('voucher_id', $receipt->voucher_id)->first();
                if ($cheque) {
                    $cheque->update([
                        'status' => 'Issued',
                        'cleared_date' => null,
                        'billing_month' => null,
                        'voucher_id' => null,
                    ]);
                }
            }
            if (str_contains($receipt->reference, 'CI-')) {
                $invoice_numbers = explode(' ', $receipt->reference);
                $invoiceIds = [];
                foreach ($invoice_numbers as $invoice_number) {
                    $id = CustomerInvoices::getIdFromInvoiceNumber($invoice_number);
                    if ($id) {
                        $invoiceIds[] = $id;
                    }
                }
                $invoices = CustomerInvoices::whereIn('id', $invoiceIds)->get();
                foreach ($invoices as $invoice) {
                    $partialAmount = $invoice->partial_paid_amount ?? [];
                    unset($partialAmount[$receipt->id]); // Remove payment for this receipt
                    $invoice->partial_paid_amount = $partialAmount;
                    if (count($partialAmount) < 1) {
                        $invoice->status = 'pending'; // Revert to pending if no payments left
                    } else {
                        $invoice->status = 'partially_paid';
                    }
                    $invoice->save();
                }
            }
            if (str_contains($receipt->reference, 'LBI-')) {
                $invoice_numbers = explode(' ', $receipt->reference);
                $invoiceIds = [];
                foreach ($invoice_numbers as $invoice_number) {
                    $id = LeasingCompanyBillingInvoice::getIdFromInvoiceNumber($invoice_number);
                    if ($id) {
                        $invoiceIds[] = $id;
                    }
                }
                $invoices = LeasingCompanyBillingInvoice::whereIn('id', $invoiceIds)->get();
                foreach ($invoices as $invoice) {
                    $partialAmount = $invoice->partial_paid_amount ?? [];
                    unset($partialAmount[$receipt->id]); // Remove payment for this receipt
                    $invoice->partial_paid_amount = $partialAmount;
                    if (count($partialAmount) < 1) {
                        $invoice->status = 0; // Revert to pending if no payments left
                    } else {
                        $invoice->status = 3;
                    }
                    $invoice->save();
                }
            }
            $receipt->delete();
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Receipt Deleted Successfuly', 'reload' => true]);
            }
        }
        Flash::success('Receipt deleted successfully.');
        return redirect()->back();
    }

    public function clone(Request $request, $comapny_slug, $id)
    {
        $receipt = Receipt::find($id);
        if (empty($receipt)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Receipt Not found'], 404);
            }
            Flash::error('Receipt not found');
            return redirect()->back();
        }

        $banks = Banks::active()->get();

        $receipt->billing_month = \Carbon\Carbon::parse($receipt->billing_month)->format('Y-m');

        return view('receipts.create', compact('receipt', 'banks'));
    }
}
