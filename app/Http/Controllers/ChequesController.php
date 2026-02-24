<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cheques;
use App\Models\Banks;
use App\Models\Accounts;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Transactions;
use App\Models\Vouchers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChequesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Cheques::query()->latest('issue_date');
        $data = $query->get();
        return view('cheques.index', compact('data'));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accountId = request()->input('id') ?? null;
        if($accountId){
            $bank = Banks::find($accountId);
            return view('cheques.create',compact('bank'));
        }
        else
            return view('cheques.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'type' => 'required|in:payable,receiveable',
            'is_security' => 'sometimes|boolean',
            'cheque_number' => 'required|string|unique:cheques,cheque_number',
            'amount' => 'required|numeric|min:0.01',
            'issue_date' => 'required|date',
            'cheque_date' => 'nullable|date',
            'billing_month' => 'nullable|date_format:Y-m',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // 2MB
        ]);

        // Additional conditional validation based on type
        if ($request->type === 'payable') {
            $request->validate([
                'payee_account' => 'required|exists:accounts,id',
                'payee_name' => 'nullable|string|max:255',
                'bank_id' => 'required|exists:banks,id',
            ]);
        } else {
            $request->validate([
                'payer_account' => 'required|exists:accounts,id',
                'payer_name' => 'nullable|string|max:255',
                'bank_id' => 'nullable|exists:banks,id',
            ]);
        }
        DB::beginTransaction();
        try {
            
            $chequeData = [
                'bank_id' => $validated['bank_id'] ?? null,
                'type' => $validated['type'],
                'is_security' => $request->has('is_security'),
                'cheque_number' => $validated['cheque_number'],
                'amount' => $validated['amount'],
                'issue_date' => $validated['issue_date'],
                'cheque_date' => $validated['cheque_date'],
                'billing_month' => $validated['billing_month'],
                'reference' => $validated['reference'],
                'description' => $validated['description'],
                'status' => 'Issued',
                'created_by' => Auth::id(),
            ];

            // type-specific fields
            if ($request->type === 'payable') {
                
                $payeeAccount = Accounts::find($request->payee_account);
                
                $chequeData['payee_account'] = $request->payee_account;
                $chequeData['payee_name'] = $request->payee_name ?? $payeeAccount->name;
                
                $bank = Banks::find($request->bank_id);
                $chequeData['payer_account'] = $bank->account_id ;
                $chequeData['payer_name'] = $bank->name;
                
            } else {
                
                $payerAccount = Accounts::find($request->payer_account);
                
                $chequeData['payer_account'] = $request->payer_account;
                $chequeData['payer_name'] = $request->payer_name ?? $payerAccount->name;
                
                // Payee is the bank account
                if($request->bank_id){
                    $bank = Banks::find($request->bank_id);
                    $chequeData['payee_account'] = $bank->account_id;
                    $chequeData['payee_name'] = $bank->name;
                }
            }

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/vouchers', $filename);
                $chequeData['attachment'] = $filename;
            }

            if($request['billing_month']){
                $chequeData['billing_month'] = $validated['billing_month'].'-01';
            }

            // Create cheque
            $cheque = Cheques::create($chequeData);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cheque created successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cheque creation failed: ' . $e);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create cheque. Please try again. Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cheque = Cheques::findOrFail($id);
        return view('cheques.show', compact('cheque'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $cheque = Cheques::findOrFail($id);
        return view('cheques.edit', compact('cheque'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cheques $cheque)
    {
        $rules = [
            'cheque_number' => 'required|string|unique:cheques,cheque_number,' . $cheque->id,
            'amount' => 'required|numeric|min:0.01',
            'issue_date' => 'required|date',
            'is_security' => 'nullable|boolean',
            'billing_month' => 'nullable|date_format:Y-m',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'cheque_date' => 'nullable|date',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];

        // Add type-specific validation
        if ($cheque->type === 'payable') {
            $rules['payee_account'] = 'required|exists:accounts,id';
            $rules['payee_name'] = 'nullable|string|max:255';
            $rules['bank_id'] = 'required|exists:banks,id';
        } else {
            $rules['payer_account'] = 'required|exists:accounts,id';
            $rules['payer_name'] = 'nullable|string|max:255';
            $rules['bank_id'] = 'nullable|exists:banks,id';
        }

        // Validate the request
        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            
            $updateData = [
                'bank_id' => $validated['bank_id'] ?? null,
                'cheque_number' => $validated['cheque_number'],
                'amount' => $validated['amount'],
                'issue_date' => $validated['issue_date'],
                'cheque_date' => $validated['cheque_date'],
                'is_security' => $request->has('is_security'),
                'billing_month' => $validated['billing_month'],
                'reference' => $validated['reference'],
                'description' => $validated['description'],
                'updated_by' => Auth::id(),
            ];

            // Handle account and name fields based on type
            if ($cheque->type === 'payable') {
                $payeeAccount = Accounts::find($request->payee_account);
                $updateData['payee_account'] = $request->payee_account;
                $updateData['payee_name'] = $request->payee_name ?? $payeeAccount->name;
                
                // Update payer from bank
                $bank = Banks::find($cheque->bank_id);
                $updateData['payer_account'] = $bank->account_id;
                $updateData['payer_name'] = $bank->name;
            } else {
                $payerAccount = Accounts::find($request->payer_account);
                $updateData['payer_account'] = $request->payer_account;
                $updateData['payer_name'] = $request->payer_name ?? $payerAccount->name;
                
                // Update payee from bank
                if($validated['bank_id']){
                    $bank = Banks::find($validated['bank_id']);
                    $updateData['payee_account'] = $bank->account_id;
                    $updateData['payee_name'] = $bank->name;
                }
            }

            if ($request->hasFile('attachment')) {
                // Delete old file
                if ($cheque->attachment) {
                    Storage::delete('public/vouchers/' . $cheque->attachment);
                }
                
                // Store new file
                $file = $request->file('attachment');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/vouchers', $filename);
                $updateData['attachment'] = $filename;
            }

            if($request['billing_month']){
                $updateData['billing_month'] = $validated['billing_month'].'-01';
            }

            // Update the cheque
            $cheque->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cheque updated successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cheque update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cheque. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cheque = Cheques::find($id);
        $path = 'public/vouchers/'.$cheque->attachment;
        if ($cheque) {
            
            DB::beginTransaction();
            try {
                if($cheque->voucher_id) {
                    Transactions::where('trans_code', $cheque->voucher->trans_code)->delete();
                    $cheque->voucher->delete();
                    if($cheque->type === 'payable') {
                        Payment::where('voucher_id', $cheque->voucher_id)->delete();
                    } else {
                        Receipt::where('voucher_id', $cheque->voucher_id)->delete();
                    }
                }
                $cheque->delete();
                DB::commit();
                // Delete attachment file if exists
                Storage::delete($path);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Cheque deleted successfully.'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete cheque: ' . $e->getMessage()
                ], 500);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cheque not found.'
            ], 404);
        }
    }
    /**
     * Update the status of a cheque.
     */
    public function statusForm(Request $request, $id)
    {
        $cheque = Cheques::findOrFail($id);
        return view('cheques.changeStatus', compact('cheque'));
    }
    
    public function updateStatus(Request $request, $id)
    {
        $cheque = Cheques::findOrFail($id);
        if (!$cheque) {
            return response()->json([
                'success' => false,
                'message' => 'Cheque not found.'
            ], 404);
        }   

        // Validate the request
        $rules = [
            'status' => 'required|in:Issued,Cleared,Returned,Stop Payment,Lost',
        ];
        
        // Add conditional validation
        if ($request->status === 'Cleared') {
            $rules['cleared_date'] = 'required|date';
            $rules['billing_month'] = 'required|date_format:Y-m';
            $rules['bank_id'] = 'required|exists:banks,id';
        } elseif ($request->status === 'Returned') {
            $rules['returned_date'] = 'required|date';
            $rules['return_reason'] = 'required|string|max:255';
        } elseif ($request->status === 'Stop Payment') {
            $rules['stop_payment_date'] = 'required|date';
            $rules['stop_payment_reason'] = 'required|string|max:255';
        }
        
        $validated = $request->validate($rules);
        
        DB::beginTransaction();
        try {
            
            $updateData = [
                'status' => $validated['status'],
                'updated_by' => Auth::id(),
            ];

            if($validated['status']==='Returned'){
                $updateData['returned_date'] = $validated['returned_date'];
                $updateData['return_reason'] = $validated['return_reason'];
            }elseif($validated['status']==='Stop Payment'){
                $updateData['stop_payment_date'] = $validated['stop_payment_date'];
                $updateData['stop_payment_reason'] = $validated['stop_payment_reason'];
            }elseif($validated['status']==='Cleared'){
                $updateData['cleared_date'] = $validated['cleared_date'];
                $updateData['billing_month'] = $validated['billing_month'].'-01';
                $updateData['bank_id'] = $validated['bank_id'];
                $bank = Banks::findOrFail($updateData['bank_id']);
                if($cheque->type==='payable'){
                    $paymentData = [
                        'payee_account_id' => (array)$cheque->payee_account,
                        'amount' => $cheque->amount,
                        'date_of_payment' => $validated['cleared_date'],
                        'reference' => 'Cheque Cleared: '.$cheque->cheque_number,
                        'bank_id' => $bank->id,
                        'amount_type' => 'Cheque',
                        'billing_month' => $validated['billing_month'].'-01',
                        'description' => $cheque->description,
                        'status' => '1',
                        'attachment'=> $cheque->attachment,
                        'created_by' => Auth::id(),
                    ];
                    $payment = Payment::create($paymentData);
                    $transCode = \App\Helpers\Account::trans_code();
                    
                    // Credit the BANK account
                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $payment->date_of_payment,
                        'reference_id' => $payment->id,
                        'reference_type' => 'PV',
                        'account_id' => $bank->account_id,
                        'credit' => $cheque->amount,
                        'debit' => 0,
                        'billing_month' => $payment->billing_month,
                        'narration' => $cheque->description,
                    ]);
                    
                    // Debit payee account 
                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $payment->date_of_payment,
                        'reference_id' => $payment->id,
                        'reference_type' => 'PV',
                        'account_id' => $cheque->payee_account,
                        'credit' => 0,
                        'debit' => $cheque->amount,
                        'billing_month' => $payment->billing_month,
                        'narration' => $cheque->description,
                    ]);

                    // voucher
                    $voucherData = [
                        'trans_date' => $payment->date_of_payment,
                        'trans_code' => $transCode,
                        'billing_month' => $payment->billing_month,
                        'payment_from' => $bank->account_id,
                        'payment_to' => $cheque->payee_account,
                        'amount' => $cheque->amount,
                        'voucher_type' => 'PV',
                        'remarks' => 'Payment Voucher',
                        'ref_id' => $payment->id,
                        'Created_By' => Auth::id(),
                        'status' => 1,
                        'attach_file'=> $cheque->attachment,
                        'custom_field_values' => [],
                    ];

                    $voucher = Vouchers::create($voucherData);
                    
                    // Update payment with voucher info and detailed account data
                    $payment->update([
                        'voucher_id' => $voucher->id,
                    ]);
                    $updateData['voucher_id'] = $voucher->id;
                }else{
                    $receiptData = [
                        'payer_account_id' => (array)$cheque->payer_account,
                        'amount' => $cheque->amount,
                        'date_of_receipt' => $validated['cleared_date'],
                        'reference' => 'Cheque Cleared: '.$cheque->cheque_number,
                        'account_id' => $bank->account_id,
                        'bank_id'=> $bank->id,
                        'amount_type' => 'Cheque',
                        'billing_month' => $validated['billing_month'].'-01',
                        'description' => $cheque->description,
                        'status' => '1',
                        'attachment'=> $cheque->attachment,
                        'created_by' => Auth::id(),
                    ];
                    $receipt = Receipt::create($receiptData);
                    $transCode = \App\Helpers\Account::trans_code();
                    // Debit the BANK account
                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $receipt->date_of_receipt,
                        'reference_id' => $receipt->id,
                        'reference_type' => 'RV',
                        'account_id' => $bank->account_id,
                        'credit' => 0,
                        'debit' => $cheque->amount,
                        'billing_month' => $receipt->billing_month,
                        'narration' => $cheque->description,
                    ]);

                    // Credit payer account 
                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $receipt->date_of_receipt,
                        'reference_id' => $receipt->id,
                        'reference_type' => 'RV',
                        'account_id' => $cheque->payer_account,
                        'credit' => $cheque->amount,
                        'debit' => 0,
                        'billing_month' => $receipt->billing_month,
                        'narration' => $cheque->description,
                    ]);

                    // voucher
                    $voucherData = [
                        'trans_date' => $receipt->date_of_receipt,
                        'trans_code' => $transCode,
                        'billing_month' => $receipt->billing_month,
                        'payment_from' => $cheque->payer_account,
                        'payment_to' => $bank->account_id,
                        'amount' => $cheque->amount,
                        'voucher_type' => 'RV',
                        'remarks' => 'Receipt Voucher',
                        'ref_id' => $receipt->id,
                        'Created_By' => Auth::id(),
                        'status' => 1,
                        'attach_file'=> $cheque->attachment,
                        'custom_field_values' => [],
                    ];
                    $voucher = Vouchers::create($voucherData);
                    $receipt->update([
                        'voucher_id' => $voucher->id,
                    ]);
                    $updateData['voucher_id'] = $voucher->id;
                }
            }
            // Update cheque
            $cheque->update($updateData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Cheque status updated successfully!',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cheque status update failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cheque status. Please try again.'.$e->getMessage(),
            ], 500);
        }
    }
}
