<?php

namespace App\Http\Controllers;

use App\Repositories\PaymentsRepository;
use App\Models\Payment;
use App\Models\Accounts;
use App\Models\Banks;
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
        $query = Payment::query()->orderBy('id', 'asc');
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        return view('payments.index', ['data' => $data]);
    }

    public function create()
    {
        $accountId = request()->input('id') ?? null;
        if($accountId){
            $bank = Banks::find($accountId);
            return view('payments.create',compact('bank'));
        }
        else
            return view('payments.create');
    }

    public function store(Request $request)
    {
    
        $rules = [
            'reference' => 'nullable|string|max:255',
            'amount_type' => 'required|string|in:Cash,Online,Cheque,Credit',
            'bank_id' => 'required|numeric|exists:banks,id',
            'date_of_payment' => 'required|date',
            'date_of_invoice'=> 'nullable|date',
            'billing_month' => 'required|date',
            'description' => 'required|string|max:500',
            'account_id' => 'required|array',
            'account_id.*' => 'required|numeric|exists:accounts,id',
            'narration' => 'required|array',
            'narration.*' => 'required|string|max:500',
            'dr_amount' => 'required|array|min:1',
            'dr_amount.*' => 'required|numeric|min:0.01',
            'status' => 'nullable|boolean',
        ];

        $messages = [
            'bank_id.required' => 'Paying Bank Account is Required',
            'date_of_payment.required' => 'Payment date is Required',
            'billing_month.required' => 'Billing month is Required',
            'description.required' => 'Narration for Bank credit is Required',
            'account_id.required' => 'At least one Payer Account is Required',
            'account_id.*.required' => 'Each row must have an account selected',
            'narration.*.required'=> 'All Narration Fields are required',
            'dr_amount.required' => 'Debit amount is required',
            'dr_amount.*.required' => 'All Debit amounts are required',
            'dr_amount.*.min' => 'Debit Amount must be greater than 0',
        ];

        $this->validate($request, $rules, $messages);
        
        //Check if total debit equals total credit
        $totalCredit = floatval($request->input('amount', 0)); 
        $totalDebit = 0;
        
        $drAmounts = $request->input('dr_amount', []);
        foreach ($drAmounts as $index => $amount) {
            $totalDebit += floatval($amount);
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            if($request->ajax()){
                return response()->json(['message' => 'Total debit ('.$totalDebit.') and credit ('.$totalCredit.') amounts must be equal.'],422);
            }
            Flash::error('Total debit ('.$totalDebit.') and credit ('.$totalCredit.') amounts must be equal.');
            return redirect()->back()->withInput();
        }
        
        $bank = Banks::find($request->input('bank_id'));
        $input = $request->all();
        
        if (!$bank) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Selected bank not found'], 422);
            }
            Flash::error('Selected bank  not found.');
            return redirect()->back()->withInput();
        }

        // Get the arrays from the form
        $accountIds = $request->input('account_id', []);
        $drAmounts = $request->input('dr_amount', []);
        $narrations = $request->input('narration', []);

        $input['created_by'] = auth()->id();
        $input['billing_month'] = $input['billing_month'] . '-01';
        $input['amount'] = $totalCredit; 
        $input['payee_account_id'] = array_unique($accountIds);

        try {
            DB::beginTransaction();

            $payment = Payment::create($input);
            $transCode = \App\Helpers\Account::trans_code();
            
            $date = $input['date_of_payment'];
            $billingMonth = $input['billing_month'];
            $desc = $input['description']; 
            
            // Credit the BANK account
            if ($totalDebit > 0) {
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $date,
                    'reference_id' => $payment->id,
                    'reference_type' => 'PV',
                    'account_id' => $bank->account_id,
                    'credit' => $totalDebit,
                    'debit' => 0,
                    'billing_month' => $billingMonth,
                    'narration' => $desc,
                ]);
            }
            
            // Debit each payee account
            foreach ($accountIds as $index => $accountId) {

                $debitAmount = floatval($drAmounts[$index] ?? 0);
                $narration = $narrations[$index] ?? ($desc . ' - Payment from Account ID: ' . $accountId);
                
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $date,
                    'reference_id' => $payment->id,
                    'reference_type' => 'PV',
                    'account_id' => $accountId,
                    'credit' => 0,
                    'debit' => $debitAmount,
                    'billing_month' => $billingMonth,
                    'narration' => $narration,
                ]);
            }

            // voucher
            $voucherData = [
                'trans_date' => $date,
                'trans_code' => $transCode,
                'billing_month' => $billingMonth,
                'payment_from' => $bank->account_id,
                'amount' => $totalCredit,
                'voucher_type' => 'PV',
                'remarks' => 'Payment Voucher',
                'ref_id' => $payment->id,
                'Created_By' => auth()->id(),
                'status' => 1,
            ];
            
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/vouchers', $fileName);
                $voucherData['attach_file'] = $fileName;
            }

            $voucher = Vouchers::create($voucherData);
            
            // Update payment with voucher info and detailed account data
            $payment->update([
                'voucher_id' => $voucher->id,
                'attachment' => $voucher->attach_file ?? null,
            ]);
            
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment creation failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            if ($request->ajax()) {
                return response()->json(['message' => "An Error Occurred: " . $e->getMessage()], 500);
            }
            
            Flash::error('Error Occurred: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }

        if ($request->ajax()) {
            return response()->json([
                "message" => "Payment Added Successfully"
            ]);
        }

        Flash::success('Payment added successfully.');
        return redirect()->bacK();
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
        $bank = Banks::find($payment->bank_id);
        if (empty($payment)) {
            if($request->ajax()){
                return response()->json(['message'=> 'Payment Not found'],404);
            }
            Flash::error('Payment not found');
            return redirect()->back();
        }
        $payment->billing_month = \Carbon\Carbon::parse($payment->billing_month)->format('Y-m');
        $transactions = $payment->voucher->transactions;

        return view('payments.edit', compact('payment','bank','transactions'));
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (empty($payment)) {
            if($request->ajax()){
                return response()->json(['message'=> 'Payment Not Found'],500);
            }
            Flash::error('Payment not found!');
            return redirect()->back();
        }

        $request['billing_month'] = $request['billing_month'] . "-01";

        $rules = [
            'reference' => 'nullable|string|max:255',
            'amount_type' => 'required|string|in:Cash,Online,Cheque,Credit',
            'bank_id' => 'required|numeric|exists:banks,id',
            'date_of_payment' => 'required|date',
            'billing_month' => 'required|date',
            'description' => 'required|string|max:500',
            'account_id' => 'required|array|min:1',
            'account_id.*' => 'required|numeric|exists:accounts,id',
            'narration' => 'required|array|min:1',
            'narration.*' => 'required|string|max:500',
            'dr_amount' => 'required|array|min:1',
            'dr_amount.*' => 'required|numeric|min:0.01',
            'status' => 'nullable|boolean',
        ];

        $messages = [
            'amount_type.required' => 'Amount Type is Required',
            'amount_type.in' => 'Amount Type must be one of: Cash, Online, Cheque, Credit',
            'bank_id.required' => 'Paying Bank Account is Required',
            'bank_id.exists' => 'Selected bank account does not exist',
            'date_of_payment.required' => 'Payment date is Required',
            'billing_month.required' => 'Billing month is Required',
            'description.required' => 'Narration for Bank Credit is Required',
            'account_id.required' => 'At least one Payee Account is Required',
            'account_id.*.required' => 'Each row must have an account selected',
            'account_id.*.exists' => 'One or more selected accounts do not exist',
            'narration.*.required' => 'All Narration Fields are required',
            'dr_amount.required' => 'Debit amount is required',
            'dr_amount.*.required' => 'All Debit amounts are required',
            'dr_amount.*.min' => 'Debit Amount must be greater than 0',
        ];

        $this->validate($request, $rules, $messages);
        
        $totalCredit = floatval($request->input('amount', 0)); 
        $totalDebit = 0;
        
        $drAmounts = $request->input('dr_amount', []);
        $narrations = $request->input('narration', []);
        foreach ($drAmounts as $amount) {
            $totalDebit += floatval($amount);
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            if($request->ajax()){
                return response()->json([
                    'message' => 'Total debit (' . number_format($totalDebit, 2) . ') and credit (' . number_format($totalCredit, 2) . ') amounts must be equal.'
                ], 422);
            }
            Flash::error('Total debit (' . number_format($totalDebit, 2) . ') and credit (' . number_format($totalCredit, 2) . ') amounts must be equal.');
            return redirect()->back()->withInput();
        }
        
        $bank = Banks::find($request->input('bank_id'));
        if (!$bank) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Selected bank not found'], 422);
            }
            Flash::error('Selected bank not found.');
            return redirect()->back()->withInput();
        }

        try {
            DB::beginTransaction();
            
            $input = $request->all();
            $input['updated_by'] = auth()->id();
            $input['amount'] = $totalCredit;
            
            $accountIds = $request->input('account_id', []);
            $input['payee_account_id'] = array_unique($accountIds );
            
            // Check if anything changed before updating
            $hasChanges = false;
            $fieldsToCheck = ['reference', 'amount_type', 'date_of_payment', 'date_of_invoice' ,'billing_month', 'description', 'payee_account_id', 'amount'];
            
            foreach ($fieldsToCheck as $field) {
                if (isset($input[$field]) && $payment->$field != $input[$field]) {
                    $hasChanges = true;
                    break;
                }
            }
            
            if (!$hasChanges) {
                // Also check if transaction details changed
                $existingTransactions = $payment->voucher->transactions;
                    
                if (count($existingTransactions) - 1 !== count($accountIds)) {
                    $hasChanges = true;
                } else {
                    for ($i = 0; $i < count($accountIds); $i++) {
                        $Index = $i + 1; // +1 to skip the first credit transaction
                        if (isset($existingTransactions[$Index])) {
                            if ($existingTransactions[$Index]->account_id != $accountIds[$i] ||
                                floatval($existingTransactions[$Index]->debit) != floatval($drAmounts[$i]) ||
                                $existingTransactions[$Index]->narration != ($narrations[$i] ?? '')) {
                                $hasChanges = true;
                                break;
                            }
                        }
                    }
                }
            }
            
            if (!$hasChanges) {
                if($request->hasFile('attachment')){
                    $file = $request->file('attachment');
                    $fileName = time().'_'.$file->getClientOriginalName();
                    $file->storeAs('public/vouchers', $fileName);
                    $payment->update(['attachment' => $fileName]);
                    $payment->voucher->update(['attach_file' => $fileName]);
                    DB::commit();

                    return response()->json(['message'=>'File uploaded Successfully']);
                }

                return response()->json(['message' => 'Nothing New Entered to Update'], 200);
            }
            
            // Update payment
            $payment->update($input);
            
            $transCode = $payment->voucher->trans_code;
            $date = $input['date_of_payment'];
            $billingMonth = $input['billing_month'];
            $desc = $input['description'];
            
            // Get arrays from request
            $accountIds = $request->input('account_id', []);
            $drAmounts = $request->input('dr_amount', []);
            $narrations = $request->input('narration', []);
            
            // Delete existing transactions except the first one (debit)
            $existingTransactions = Transactions::where('trans_code', $transCode)->get();
            
            // Keep track of which transactions we're keeping
            foreach ($existingTransactions as $index => $transaction) {
                if ($index == 0) {
                    // Update the Credit transaction (bank account)
                    $transaction->update([
                        'trans_date' => $date,
                        'account_id' => $bank->account_id,
                        'debit' => $totalCredit,
                        'credit' => 0,
                        'billing_month' => $billingMonth,
                        'narration' => $desc,
                    ]);
                } else {
                    // Delete old debit transactions
                    $transaction->delete();
                }
            }
            
            // Create new DEbit transactions
            foreach ($accountIds as $index => $accountId) {
                $debitAmount = floatval($drAmounts[$index] ?? 0);
                $narration = $narrations[$index] ?? ($desc . ' - Payment from Account ID: ' . $accountId);
                
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $date,
                    'reference_id' => $payment->id,
                    'reference_type' => 'PV',
                    'account_id' => $accountId,
                    'credit' => 0,
                    'debit' => $debitAmount,
                    'billing_month' => $billingMonth,
                    'narration' => $narration,
                ]);
            }
            
            // Update voucher
            $payment->voucher->update([
                'trans_date' => $date,
                'billing_month' => $billingMonth,
                'payment_from' => $bank->account_id,
                'amount' => $totalCredit,
                'Updated_By' => auth()->id(),
            ]);
            
            // Handle attachment if provided
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/vouchers', $fileName);
                $payment->update(['attachment' => $fileName]);
                $payment->voucher->update(['attach_file' => $fileName]);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment update failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
        
        return response()->json([
            'message' => 'Payment Updated Successfully'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (empty($payment)) {
            if($request->ajax()){
                return response()->json(['message' => 'Payment Not Found!'],500);
            }
            Flash::error('Payment not found!');
            return redirect()->back();
        } else {
            Transactions::where('trans_code', $payment->voucher->trans_code)->delete();
            Vouchers::where('id', $payment->voucher_id)->delete();
            $payment->delete();
            if($request->ajax()){
                return response()->json(['message' => 'Payment Deleted successfully']);
            }
            Flash::success('Payment deleted successfully.');
            return redirect()->back();
        }
    }

    /**
     * Get head accounts by account type (AJAX)
     */
    function byparent($id)
    {
        $accounts = Accounts::where('parent_id', $id)->get();
        if ($accounts->isEmpty()) {
            echo '<option value="">There is no account against this parent</option>';
        } else {
            echo '<option value="">Select Account</option>';
            foreach ($accounts as $account) {
                echo '<option value="' . $account->id . '">' . $account->name . '</option>';
            }
        }
    }
    public function headbytype($id)
    {
        $accounts = Accounts::where('account_type', $id)->get();
        if ($accounts->isEmpty()) {
            echo '<option value="">There is no account against this type</option>';
        } else {
            echo '<option value="">Select Account</option>';
            foreach ($accounts as $account) {
                echo '<option value="' . $account->id . '">' . $account->name . '</option>';
            }
        }
    }
}
