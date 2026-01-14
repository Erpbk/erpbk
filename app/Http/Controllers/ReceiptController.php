<?php

namespace App\Http\Controllers;

use App\Repositories\ReceiptsRepository;
use App\Models\Receipt;
use App\Models\Banks;
use App\Models\Transactions;
use App\Models\Vouchers;
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
        // Use global pagination trait
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = Receipt::query()->orderBy('id', 'asc');
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        return view('receipts.index', ['data' => $data]);
    }

    public function create()
    {
        $accountId = request()->input('id') ?? null;
        if($accountId){
            $bank = Banks::find($accountId);
            return view('receipts.create',compact('bank'));
        }
        else
            return view('receipts.create');
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
        'account_id' => 'required|array',
        'account_id.*' => 'required|numeric|exists:accounts,id',
        'narration' => 'required|array',
        'narration.*' => 'required|string|max:500',
        'cr_amount' => 'required|array|min:1',
        'cr_amount.*' => 'required|numeric|min:0.01',
        'status' => 'nullable|boolean',
    ];

    $messages = [
        'bank_id.required' => 'Receiving Bank Account is Required',
        'date_of_receipt.required' => 'Receipt date is Required',
        'billing_month.required' => 'Billing month is Required',
        'description.required' => 'Narration for Bank debit is Required',
        'account_id.required' => 'At least one Payer Account is Required',
        'account_id.*.required' => 'Each row must have an account selected',
        'narration.*.required'=> 'All Narration Fields are required',
        'cr_amount.required' => 'Credit amount is required',
        'cr_amount.*.required' => 'All Credit amounts are required',
        'cr_amount.*.min' => 'Credit Amount must be greater than 0',
    ];

    $this->validate($request, $rules, $messages);
    
    //Check if total debit equals total credit
    $totalDebit = floatval($request->input('amount', 0)); 
    $totalCredit = 0;
    
    $crAmounts = $request->input('cr_amount', []);
    foreach ($crAmounts as $index => $amount) {
        $totalCredit += floatval($amount);
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
            return response()->json(['message' => 'Selected bank account not found'], 422);
        }
        Flash::error('Selected bank account not found.');
        return redirect()->back()->withInput();
    }

    // Get the arrays from the form
    $accountIds = $request->input('account_id', []);
    $crAmounts = $request->input('cr_amount', []);
    $narrations = $request->input('narration', []);

    $input['created_by'] = auth()->id();
    $input['billing_month'] = $input['billing_month'] . '-01';
    $input['account_id'] = $bank->account_id;
    $input['amount'] = $totalDebit; 
    $input['payer_account_id'] = $accountIds[0];

    try {
        DB::beginTransaction();

        $receipt = Receipt::create($input);
        $transCode = \App\Helpers\Account::trans_code();
        
        $date = $input['date_of_receipt'] ?? now();
        $billingMonth = $input['billing_month'];
        $desc = $input['description']; 
        
        // DEBIT the BANK account
        if ($totalDebit > 0) {
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $receipt->id,
                'reference_type' => 'RV',
                'account_id' => $bank->account_id,
                'credit' => 0,
                'debit' => $totalDebit,
                'billing_month' => $billingMonth,
                'narration' => $desc,
            ]);
        }
        
        // CREDIT each payer account
        foreach ($accountIds as $index => $accountId) {

            $creditAmount = floatval($crAmounts[$index] ?? 0);
            $narration = $narrations[$index] ?? ($desc . ' - Payment from Account ID: ' . $accountId);
            
            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $receipt->id,
                'reference_type' => 'RV',
                'account_id' => $accountId,
                'credit' => $creditAmount,
                'debit' => 0,
                'billing_month' => $billingMonth,
                'narration' => $narration,
            ]);
        }

        // voucher
        $voucherData = [
            'trans_date' => $date,
            'trans_code' => $transCode,
            'billing_month' => $billingMonth,
            'payment_to' => $bank->account_id,
            'amount' => $totalDebit,
            'voucher_type' => 'RV',
            'remarks' => 'Receipt Voucher',
            'ref_id' => $receipt->id,
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
            "message" => "Receipt Added Successfully"
        ]);
    }

    Flash::success('Receipt added successfully.');
    return redirect()->bacK();
    }

    public function show($id)
    {
        $receipt = $this->receiptsRepository->find($id);
        if (empty($receipt)) {
            Flash::error('Receipt not found');
            return redirect(route('receipts.index'));
        }
        return view('receipts.show')->with('receipt', $receipt);
    }

    public function edit(Request $request, $id)
    {
        $receipt = Receipt::find($id);
        $bank = Banks::find($receipt->bank_id);
        if (empty($receipt)) {
            if($request->ajax()){
                return response()->json(['message'=> 'Receipt Not found'],404);
            }
            Flash::error('Receipt not found');
            return redirect()->back();
        }
        $receipt->billing_month = \Carbon\Carbon::parse($receipt->billing_month)->format('Y-m');
        $transactions = Transactions::where('trans_code', $receipt->voucher->trans_code)->get();

        return view('receipts.edit', compact('receipt','bank','transactions'));
    }

    public function update(Request $request, $id)
    {
        $receipt = Receipt::find($id);
        if (empty($receipt)) {
            if($request->ajax()){
                return response()->json(['message'=> 'Receipt Not Found'],500);
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
            'account_id' => 'required|array|min:1',
            'account_id.*' => 'required|numeric|exists:accounts,id',
            'narration' => 'required|array|min:1',
            'narration.*' => 'required|string|max:500',
            'cr_amount' => 'required|array|min:1',
            'cr_amount.*' => 'required|numeric|min:0.01',
            'status' => 'nullable|boolean',
        ];

        $messages = [
            'amount_type.required' => 'Amount Type is Required',
            'amount_type.in' => 'Amount Type must be one of: Cash, Online, Cheque, Credit',
            'bank_id.required' => 'Receiving Bank Account is Required',
            'bank_id.exists' => 'Selected bank account does not exist',
            'date_of_receipt.required' => 'Receipt date is Required',
            'billing_month.required' => 'Billing month is Required',
            'description.required' => 'Narration for Bank debit is Required',
            'account_id.required' => 'At least one Payer Account is Required',
            'account_id.*.required' => 'Each row must have an account selected',
            'account_id.*.exists' => 'One or more selected accounts do not exist',
            'narration.*.required' => 'All Narration Fields are required',
            'cr_amount.required' => 'Credit amount is required',
            'cr_amount.*.required' => 'All Credit amounts are required',
            'cr_amount.*.min' => 'Credit Amount must be greater than 0',
        ];

        $this->validate($request, $rules, $messages);
        
        $totalDebit = floatval($request->input('amount', 0)); 
        $totalCredit = 0;
        
        $crAmounts = $request->input('cr_amount', []);
        $narrations = $request->input('narration', []);
        foreach ($crAmounts as $amount) {
            $totalCredit += floatval($amount);
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
                return response()->json(['message' => 'Selected bank account not found'], 422);
            }
            Flash::error('Selected bank account not found.');
            return redirect()->back()->withInput();
        }

        try {
            DB::beginTransaction();
            
            $input = $request->all();
            $input['updated_by'] = auth()->id();
            $input['account_id'] = $bank->account_id; 
            $input['amount'] = $totalDebit;
            
            // Get the first payer account for the receipt record
            $accountIds = $request->input('account_id', []);
            $input['payer_account_id'] = array_unique($accountIds ) ?? null;
            
            // Check if anything changed before updating
            $hasChanges = false;
            $fieldsToCheck = ['reference', 'amount_type', 'date_of_receipt', 'billing_month', 'description', 'payer_account_id', 'amount'];
            
            foreach ($fieldsToCheck as $field) {
                if (isset($input[$field]) && $receipt->$field != $input[$field]) {
                    $hasChanges = true;
                    break;
                }
            }
            
            if (!$hasChanges) {
                // Also check if transaction details changed
                $existingTransactions = Transactions::where('trans_code', $receipt->voucher->trans_code)
                    ->orderBy('id')
                    ->get();
                    
                if (count($existingTransactions) - 1 !== count($accountIds)) {
                    $hasChanges = true;
                } else {
                    for ($i = 0; $i < count($accountIds); $i++) {
                        $existingIndex = $i + 1; // +1 to skip the first debit transaction
                        if (isset($existingTransactions[$existingIndex])) {
                            if ($existingTransactions[$existingIndex]->account_id != $accountIds[$i] ||
                                floatval($existingTransactions[$existingIndex]->credit) != floatval($crAmounts[$i]) ||
                                $existingTransactions[$existingIndex]->narration != ($narrations[$i] ?? '')) {
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
                    $receipt->update(['attachment' => $fileName]);
                    $receipt->voucher->update(['attach_file' => $fileName]);
                    DB::commit();

                    return response()->json(['message'=>'File uploaded Successfully']);
                }

                return response()->json(['message' => 'Nothing New Entered to Update'], 200);
            }
            
            // Update receipt
            $receipt->update($input);
            
            $transCode = $receipt->voucher->trans_code;
            $date = $input['date_of_receipt'];
            $billingMonth = $input['billing_month'];
            $desc = $input['description'];
            
            // Get arrays from request
            $accountIds = $request->input('account_id', []);
            $crAmounts = $request->input('cr_amount', []);
            $narrations = $request->input('narration', []);
            
            // Delete existing transactions except the first one (debit)
            $existingTransactions = Transactions::where('trans_code', $transCode)->get();
            
            // Keep track of which transactions we're keeping
            foreach ($existingTransactions as $index => $transaction) {
                if ($index == 0) {
                    // Update the DEBIT transaction (bank account)
                    $transaction->update([
                        'trans_date' => $date,
                        'account_id' => $bank->account_id,
                        'debit' => $totalDebit,
                        'credit' => 0,
                        'billing_month' => $billingMonth,
                        'narration' => $desc,
                    ]);
                } else {
                    // Delete old credit transactions
                    $transaction->delete();
                }
            }
            
            // Create new CREDIT transactions
            foreach ($accountIds as $index => $accountId) {
                $creditAmount = floatval($crAmounts[$index] ?? 0);
                $narration = $narrations[$index] ?? ($desc . ' - Payment from Account ID: ' . $accountId);
                
                Transactions::create([
                    'trans_code' => $transCode,
                    'trans_date' => $date,
                    'reference_id' => $receipt->id,
                    'reference_type' => 'RV',
                    'account_id' => $accountId,
                    'credit' => $creditAmount,
                    'debit' => 0,
                    'billing_month' => $billingMonth,
                    'narration' => $narration,
                ]);
            }
            
            // Update voucher
            Vouchers::where('id', $receipt->voucher_id)->update([
                'trans_date' => $date,
                'billing_month' => $billingMonth,
                'payment_to' => $bank->account_id,
                'amount' => $totalDebit,
                'Updated_By' => auth()->id(),
            ]);
            
            // Handle attachment if provided
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/vouchers', $fileName);
                
                // Update both receipt and voucher with attachment
                $receipt->update(['attachment' => $fileName]);
                Vouchers::where('id', $receipt->voucher_id)->update(['attach_file' => $fileName]);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Receipt update failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            return response()->json([
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
        
        return response()->json([
            'message' => 'Receipt Updated Successfully'
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $receipt = Receipt::find($id);
        if (empty($receipt)) {
            if($request->ajax()) {
                return response()->json(['success'=> false,'message'=> 'Receipt Not Found']);
            }
            Flash::error('Receipt not found!');
            return redirect()->back();
        } else {
            Transactions::where('trans_code', $receipt->voucher->trans_code)->delete();
            Vouchers::where('id', $receipt->voucher_id)->delete();
            $receipt->delete();
            if($request->ajax()){
                return response()->json(['success'=> true,'message'=> 'Receipt Deleted Successfuly']);
            }
        }
        Flash::success('Receipt deleted successfully.');
        return redirect()->back();
    }

    /**
     * Get head accounts by account type (AJAX)
     */
    public function byparent($id)
    {
        $accounts = \App\Models\Accounts::where('parent_id', $id)->get();
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
        $accounts = \App\Models\Accounts::where('parent_id', null)->where('account_type', $id)->get();
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
