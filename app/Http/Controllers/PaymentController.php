<?php

namespace App\Http\Controllers;

use App\Repositories\PaymentsRepository;
use App\Models\Payment;
use App\Models\Accounts;
use App\Models\Banks;
use App\Models\LeasingCompanies;
use App\Models\Transactions;
use App\Models\Vouchers;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Illuminate\Support\Facades\DB;
use Flash;

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
        $query = Payment::query()->with('payeeAccount')->orderBy('date_of_payment', 'desc');
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        return view('payments.index', ['data' => $data]);
    }

    public function create()
    {
        $accountId = request()->input('id') ?? null;
        $leasingCompanyId = request()->input('leasing_company_id') ?? null;
        $payment = null;

        if ($accountId) {
            $bank = Banks::find($accountId);
            return view('payments.create', compact('bank','payment'));
        } elseif ($leasingCompanyId) {
            $leasingCompany = LeasingCompanies::find($leasingCompanyId);
            $banks = Banks::with('account')->active()->get();
            return view('payments.create', compact('leasingCompany','banks','payment'));
        } else{
            $banks = Banks::with('account')->active()->get();
            return view('payments.create', compact('banks','payment'));
        }
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
            'bank_charges_account' => 'required_if:bank_charges,>0|nullable|numeric|exists:accounts,id',
            'description' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
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
        ];

        $this->validate($request, $rules, $messages);

        // Calculate total debit (payment amount + bank charges)
        $paymentAmount = floatval($request->input('amount', 0));
        $bankCharges = floatval($request->input('bank_charges', 0));
        $totalAmount = $paymentAmount + $bankCharges;

        // Get the paying account (bank account)
        $bank = null;
        $payingAccountId = null;

        if ($request->input('bank_id')) {
            $bank = Banks::find($request->input('bank_id'));
            if (!$bank) {
                if ($request->ajax()) {
                    return response()->json(['message' => 'Selected bank not found'], 422);
                }
                Flash::error('Selected bank not found.');
                return redirect()->back()->withInput();
            }
            $payingAccountId = $bank->account_id;
        }

        $input = $request->all();
        $input['created_by'] = auth()->id();
        $input['billing_month'] = $input['billing_month'] . '-01';
        $input['amount'] = $totalAmount;

        try {
            DB::beginTransaction();

            // Create the payment record
            $payment = Payment::create($input);
            $transCode = \App\Helpers\Account::trans_code();

            $date = $input['date_of_payment'];
            $billingMonth = $input['billing_month'];
            $desc = $input['description'];

            // 1. Credit the paying account (BANK) - CREDIT entry
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $payment->id,
                'reference_type' => 'PV',
                'account_id' => $payingAccountId, // Bank account (credit)
                'credit' => $totalAmount, // Money going out of bank
                'debit' => 0,
                'billing_month' => $billingMonth,
                'narration' => $desc,
            ]);

            // 2. Debit the payee account (receiving account) - DEBIT entry
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $payment->id,
                'reference_type' => 'PV',
                'account_id' => $request->input('payee_account_id'), // Receiving account (debit)
                'credit' => 0,
                'debit' => $paymentAmount, // Money coming to this account
                'billing_month' => $billingMonth,
                'narration' => $desc,
            ]);

            // 3. Handle bank charges if any
            if ($bankCharges > 0 ) {
                // Debit the bank charges expense account
                if($request->input('bank_charges_account')) {
                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $date,
                        'reference_id' => $payment->id,
                        'reference_type' => 'PV',
                        'account_id' => $request->input('bank_charges_account'), // Expense account (debit)
                        'credit' => 0,
                        'debit' => $bankCharges,
                        'billing_month' => $billingMonth,
                        'narration' => 'Bank charges for ( ' . $payment->description .' )',
                    ]);
                }else {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'No Account Selected for Bank Charges',
                    ],500);
                }
            }

            if (!\App\Models\VoucherType::isCodeAllowedForModule('PV', 'cash_banks')) {
                DB::rollBack();
                if ($request->ajax()) {
                    return response()->json(['message' => 'Payment voucher type (PV) is not assigned to the Cash & Banks module. Please assign it in Voucher Settings.'], 422);
                }
                Flash::error('Payment voucher type (PV) is not assigned to the Cash & Banks module. Please assign it in Voucher Settings.');
                return redirect()->back()->withInput();
            }

            // voucher
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
                'Created_by' => auth()->id(),
                'status' => 1,
                'custom_field_values' => $request->input('voucher_custom_fields', []),
            ];

            // Handle attachment
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/vouchers', $fileName);
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
                    "message" => "Payment added successfully",
                    'reload' => true
                ]);
            }

            Flash::success('Payment added successfully.');
            return redirect()->back();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment creation failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            if ($request->ajax()) {
                return response()->json(['message' => "An error occurred: " . $e->getMessage()], 500);
            }

            Flash::error('Error occurred: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function show($id)
    {
        $payment = $this->paymentsRepository->find($id);
        if (empty($payment)) {
            Flash::error('Payment not found');
            return redirect(route('payments.index'));
        }
        return view('payments.show')->with('payment', $payment);
    }

    public function edit(Request $request, $id)
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
        $payment->billing_month = \Carbon\Carbon::parse($payment->billing_month)->format('Y-m');

        return view('payments.edit', compact('payment', 'banks'));
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

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
            'bank_charges_account' => 'required_if:bank_charges,>0|nullable|numeric|exists:accounts,id',
            'description' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];

        $this->validate($request, $rules);

        $paymentAmount = floatval($request->input('amount', 0));
        $bankCharges   = floatval($request->input('bank_charges', 0));
        $totalAmount   = $paymentAmount + $bankCharges;

        // Get bank account
        $bank = Banks::find($request->bank_id);
        if (!$bank) {
            return response()->json(['message' => 'Selected bank not found'], 422);
        }

        $payingAccountId = $bank->account_id;

        $input = $request->all();
        $input['updated_by'] = auth()->id();
        $input['billing_month'] = $input['billing_month'] . '-01';
        $input['amount'] = $totalAmount;

        try {
            DB::beginTransaction();

            // Update payment
            $payment->update($input);

            $transCode = $payment->voucher->trans_code;

            $date = $input['date_of_payment'];
            $billingMonth = $input['billing_month'];
            $desc = $input['description'];

            // Delete ALL old transactions
            Transactions::where('trans_code', $transCode)->delete();

            // 1. CREDIT bank (money going out)
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $payment->id,
                'reference_type' => 'PV',
                'account_id' => $payingAccountId,
                'credit' => $totalAmount,
                'debit' => 0,
                'billing_month' => $billingMonth,
                'narration' => $desc,
            ]);

            // 2. DEBIT payee
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $payment->id,
                'reference_type' => 'PV',
                'account_id' => $request->payee_account_id,
                'credit' => 0,
                'debit' => $paymentAmount,
                'billing_month' => $billingMonth,
                'narration' => $desc,
            ]);

            // 3. Bank charges (if any)
            if ($bankCharges > 0) {
                if (!$request->bank_charges_account) {
                    DB::rollBack();
                    return response()->json(['message' => 'No Account Selected for Bank Charges'], 500);
                }

                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $date,
                    'reference_id' => $payment->id,
                    'reference_type' => 'PV',
                    'account_id' => $request->bank_charges_account,
                    'credit' => 0,
                    'debit' => $bankCharges,
                    'billing_month' => $billingMonth,
                    'narration' => 'Bank charges for ( ' . $desc . ' )',
                ]);
            }

            // Update voucher
            $payment->voucher->update([
                'trans_date' => $date,
                'billing_month' => $billingMonth,
                'payment_from' => $payingAccountId,
                'reference_number' => $payment->reference,
                'amount' => $totalAmount,
                'Updated_By' => auth()->id(),
            ]);

            // Handle attachment
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/vouchers', $fileName);

                $payment->update(['attachment' => $fileName]);
                $payment->voucher->update(['attach_file' => $fileName]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment updated successfully',
                'reload' => true
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (empty($payment)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Payment Not Found!'], 500);
            }
            Flash::error('Payment not found!');
            return redirect()->back();
        } else {
            Transactions::where('trans_code', $payment->voucher->trans_code)->delete();
            Vouchers::where('id', $payment->voucher_id)->delete();
            if ($payment->amount_type == 'Cheque') {
                // Also delete associated cheque record if payment was by cheque
                $cheque = \App\Models\Cheques::where('voucher_id', $payment->voucher_id)->first();
                if ($cheque) {
                    $cheque->update([
                        'status' => 'Issued',
                        'cleared_date' => null,
                        'billing_month' => null,
                        'voucher_id' => null,
                    ]);
                }
            }
            $payment->delete();
            if ($request->ajax()) {
                return response()->json(['message' => 'Payment Deleted successfully', 'reload' => true]);
            }
            Flash::success('Payment deleted successfully.');
            return redirect()->back();
        }
    }

    public function clone($id){

        $payment = Payment::find($id);
        if (empty($payment)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Payment Not found'], 404);
            }
            Flash::error('Payment not found');
            return redirect()->back();
        }

        $banks = Banks::active()->get();
        $payment->billing_month = \Carbon\Carbon::parse($payment->billing_month)->format('Y-m');
        $payment->amount = $payment->amount - $payment->bank_charges;

        return view('payments.create', compact('payment', 'banks'));

    }
}
