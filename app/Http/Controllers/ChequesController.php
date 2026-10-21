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
use App\Models\ChequeTopCategory;
use App\Models\ChequeTopOption;
use App\Http\Controllers\Concerns\AppliesModuleTopBarFilters;
use App\Services\Cheques\ChequeTopDateFilterService;
use App\Services\Permissions\TopBarOptionPermissionSync;
use App\Services\Permissions\TopBarPermissionSync;
use App\Support\PublicStorageDisk;
use App\Support\ResourceLinks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Traits\GlobalPagination;

class ChequesController extends Controller
{
    use AppliesModuleTopBarFilters;
    use GlobalPagination;

    public function __construct(
        protected ChequeTopDateFilterService $chequeTopDateFilter
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (! \App\Support\RoleFieldAccess::canAccessModule('cheques')) {
            abort(403, 'Unauthorized action.');
        }
        $query = Cheques::query()->latest('issue_date');

        if (Schema::hasTable('cheque_top_categories')) {
            $this->chequeTopDateFilter->applyTopBarFilters($query, $request);
        }

        $totals = [
            'count' => (clone $query)->count(),
            'security_count' => (clone $query)->where('is_security', true)->count(),
            'payable_amount' => (float) (clone $query)->where('type', 'payable')->sum('amount'),
            'receivable_amount' => (float) (clone $query)->where('type', 'receiveable')->sum('amount'),
        ];

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        Cheques::attachClearVouchersTo($data);

        return view('cheques.index', array_merge(
            compact('data', 'totals'),
            $this->moduleTopBarListingData($request, 'cheques')
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accountId = request()->input('id') ?? null;
        if ($accountId) {
            $bank = Banks::find($accountId);
            return view('cheques.create', compact('bank'));
        } else
            return view('cheques.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
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
                'bank_id' => $request['bank_id'] ?? null,
                'type' => $request['type'],
                'is_security' => $request->has('is_security'),
                'cheque_number' => $request['cheque_number'],
                'amount' => $request['amount'],
                'issue_date' => $request['issue_date'],
                'cheque_date' => $request['cheque_date'],
                'billing_month' => $request['billing_month'],
                'reference' => $request['reference'],
                'description' => $request['description'],
                'status' => 'Issued',
                'created_by' => Auth::id(),
            ];
            // type-specific fields
            if ($request->type === 'payable') {

                $payeeAccount = Accounts::find($request->payee_account);

                $chequeData['payee_account'] = $request->payee_account;
                $chequeData['payee_name'] = $request->payee_name ?? $payeeAccount->name;
                $chequeData['branch_id'] = $payeeAccount->branch_id;

                $bank = Banks::find($request->bank_id);
                $chequeData['payer_account'] = $bank->account_id;
                $chequeData['payer_name'] = $bank->name;
            } else {

                $payerAccount = Accounts::find($request->payer_account);

                $chequeData['payer_account'] = $request->payer_account;
                $chequeData['payer_name'] = $request->payer_name ?? $payerAccount->name;
                $chequeData['branch_id'] = $payerAccount->branch_id;

                // Payee is the bank account
                if ($request->bank_id) {
                    $bank = Banks::find($request->bank_id);
                    $chequeData['payee_account'] = $bank->account_id;
                    $chequeData['payee_name'] = $bank->name;
                }
            }

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . $file->getClientOriginalName();
                PublicStorageDisk::storeUploadedFile($file, 'vouchers', $filename);
                $chequeData['attachment'] = $filename;
            }

            if ($request['billing_month']) {
                $chequeData['billing_month'] = $request['billing_month'] . '-01';
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
    public function show($comapny_slug, $id)
    {
        $cheque = Cheques::findOrFail($id);
        return view('cheques.show', compact('cheque'));
    }

    public function setChequeTopOption(Request $request, $company_slug, $id)
    {
        $cheque = Cheques::findOrFail($id);
        $validated = $request->validate([
            'option_id' => 'nullable|integer|exists:cheque_top_options,id',
        ]);

        $optionId = $validated['option_id'] ?? null;
        $option = null;
        if (!empty($optionId)) {
            $option = ChequeTopOption::query()
                ->where('id', $optionId)
                ->whereHas('category', fn($q) => $q->where('show_in_view_cards', 1))
                ->with('category')
                ->first();
            if (!$option) {
                return response()->json(['success' => false, 'message' => 'Invalid Cheque Top option for view cards.'], 422);
            }
            if ($option->category && ! TopBarPermissionSync::canAccessCategory('cheques', $option->category)) {
                return response()->json(['success' => false, 'message' => 'You do not have permission for this Top Bar.'], 403);
            }
            if (! TopBarOptionPermissionSync::canAccessOption('cheques', $option)) {
                return response()->json(['success' => false, 'message' => 'You do not have permission for this Top Bar value.'], 403);
            }
        }

        $cheque->cheque_top_option_id = $option?->id;
        $column = $option?->category ? trim((string) ($option->category->cheque_column ?? '')) : '';
        if ($column !== '' && Schema::hasColumn('cheques', $column)) {
            $cheque->{$column} = $option ? (string) $option->name : null;
        }
        $cheque->save();

        return response()->json([
            'success' => true,
            'message' => $option ? 'Cheque Top option assigned.' : 'Cheque Top option cleared.',
            'option_id' => $cheque->cheque_top_option_id,
            'option_name' => $option?->name,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($comapny_slug, $id)
    {
        $cheque = Cheques::findOrFail($id);
        return view('cheques.edit', compact('cheque'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $comapny_slug, Cheques $cheque)
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
                $updateData['branch_id'] = $payeeAccount->branch_id;

                // Update payer from bank
                $bank = Banks::find($cheque->bank_id);
                $updateData['payer_account'] = $bank->account_id;
                $updateData['payer_name'] = $bank->name;
            } else {
                $payerAccount = Accounts::find($request->payer_account);
                $updateData['payer_account'] = $request->payer_account;
                $updateData['payer_name'] = $request->payer_name ?? $payerAccount->name;
                $updateData['branch_id'] = $payerAccount->branch_id;

                // Update payee from bank
                if ($validated['bank_id']) {
                    $bank = Banks::find($validated['bank_id']);
                    $updateData['payee_account'] = $bank->account_id;
                    $updateData['payee_name'] = $bank->name;
                }
            }

            if ($request->hasFile('attachment')) {
                // Delete old file
                if ($cheque->attachment) {
                    PublicStorageDisk::delete('vouchers/' . $cheque->attachment);
                }

                // Store new file
                $file = $request->file('attachment');
                $filename = time() . '_' . $file->getClientOriginalName();
                PublicStorageDisk::storeUploadedFile($file, 'vouchers', $filename);
                $updateData['attachment'] = $filename;
            }

            if ($request['billing_month']) {
                $updateData['billing_month'] = $validated['billing_month'] . '-01';
            }

            // Update the cheque
            $cheque->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cheque updated successfully!',
                'reload' => true,
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
    public function destroy($comapny_slug, $id)
    {
        $cheque = Cheques::find($id);
        if ($cheque) {
            if (in_array($cheque->status, ['Cleared', 'Partially Cleared'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cleared or partially cleared cheques cannot be deleted. Delete the related payment/receipt vouchers first.',
                ], 422);
            }

            DB::beginTransaction();
            try {
                $docs = $cheque->linkedClearDocumentsQuery()->get();
                foreach ($docs as $doc) {
                    if ($doc->voucher_id) {
                        $voucher = Vouchers::find($doc->voucher_id);
                        if ($voucher) {
                            Transactions::where('trans_code', $voucher->trans_code)->delete();
                            $voucher->delete();
                        }
                    }
                    $doc->delete();
                }

                // Legacy single voucher_id with no resource_links matches
                if ($cheque->voucher_id && $docs->where('voucher_id', $cheque->voucher_id)->isEmpty()) {
                    $voucher = Vouchers::find($cheque->voucher_id);
                    if ($voucher) {
                        Transactions::where('trans_code', $voucher->trans_code)->delete();
                        if ($cheque->type === 'payable') {
                            Payment::where('voucher_id', $cheque->voucher_id)->delete();
                        } else {
                            Receipt::where('voucher_id', $cheque->voucher_id)->delete();
                        }
                        $voucher->delete();
                    }
                }

                $cheque->delete();
                DB::commit();
                if ($cheque->attachment) {
                    PublicStorageDisk::delete('vouchers/' . $cheque->attachment);
                }

                return delete_json_response(
                    'Cheque',
                    route('settings-panel.trash.index') . '?module=cheques'
                );
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
    public function statusForm(Request $request, $comapny_slug, $id)
    {
        $cheque = Cheques::findOrFail($id);
        $changeable = ['Issued', 'Partially Cleared', 'Returned', 'Stop Payment', 'Lost'];
        if (! in_array($cheque->status, $changeable, true)) {
            abort(403, 'Status cannot be changed for fully cleared cheques.');
        }

        return view('cheques.changeStatus', compact('cheque'));
    }

    public function updateStatus(Request $request, $comapny_slug, $id)
    {
        $cheque = Cheques::findOrFail($id);

        $changeable = ['Issued', 'Partially Cleared', 'Returned', 'Stop Payment', 'Lost'];
        if (! in_array($cheque->status, $changeable, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Fully cleared cheques cannot change status.',
            ], 422);
        }

        $remaining = $cheque->remainingAmount();
        $hasClears = $cheque->status === 'Partially Cleared'
            || round((float) $cheque->cleared_amount, 2) > 0.00001;

        // Partially cleared (or any cheque with clear docs): only further clear allowed.
        $allowedStatuses = $hasClears
            ? ['Cleared']
            : ['Cleared', 'Returned', 'Stop Payment', 'Lost'];

        if ($hasClears && in_array($request->status, ['Returned', 'Stop Payment', 'Lost'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Returned, Stop Payment, and Lost are not allowed while the cheque has clear payments. Delete related vouchers first.',
            ], 422);
        }

        $rules = [
            'status' => 'required|in:'.implode(',', $allowedStatuses),
        ];

        if ($request->status === 'Cleared') {
            $rules['cleared_date'] = 'required|date';
            $rules['billing_month'] = 'required|date_format:Y-m';
            $rules['bank_id'] = 'required|exists:banks,id';
            $rules['clear_amount'] = 'required|numeric|gt:0|lte:'.$remaining;
            $rules['description'] = 'nullable|string|max:255';
            $rules['attachment'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        } elseif ($request->status === 'Returned') {
            $rules['returned_date'] = 'required|date';
            $rules['return_reason'] = 'required|string|max:255';
        } elseif ($request->status === 'Stop Payment') {
            $rules['stop_payment_date'] = 'required|date';
            $rules['stop_payment_reason'] = 'required|string|max:255';
        }

        $validated = $request->validate($rules);

        if ($hasClears && $validated['status'] !== 'Cleared') {
            return response()->json([
                'success' => false,
                'message' => 'Delete existing clear payments/receipts before changing to another status.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $updateData = [
                'updated_by' => Auth::id(),
            ];

            if ($validated['status'] === 'Returned') {
                $updateData['status'] = 'Returned';
                $updateData['returned_date'] = $validated['returned_date'];
                $updateData['return_reason'] = $validated['return_reason'];
            } elseif ($validated['status'] === 'Stop Payment') {
                $updateData['status'] = 'Stop Payment';
                $updateData['stop_payment_date'] = $validated['stop_payment_date'];
                $updateData['stop_payment_reason'] = $validated['stop_payment_reason'];
            } elseif ($validated['status'] === 'Lost') {
                $updateData['status'] = 'Lost';
            } elseif ($validated['status'] === 'Cleared') {
                $clearAmount = round((float) $validated['clear_amount'], 2);
                if ($clearAmount > $remaining + 0.00001) {
                    throw new \InvalidArgumentException('Clear amount exceeds remaining cheque balance.');
                }

                $description = $validated['description'] ?? $cheque->description;
                $updateData['cleared_date'] = $validated['cleared_date'];
                $updateData['billing_month'] = $validated['billing_month'].'-01';
                $updateData['bank_id'] = $validated['bank_id'];
                if ($description !== null) {
                    $updateData['description'] = $description;
                }

                $voucherAttachment = $cheque->attachment;
                if ($request->hasFile('attachment')) {
                    $file = $request->file('attachment');
                    $voucherAttachment = time().'_'.$file->getClientOriginalName();
                    PublicStorageDisk::storeUploadedFile($file, 'vouchers', $voucherAttachment);
                }

                $bank = Banks::findOrFail($updateData['bank_id']);
                $newCleared = round((float) $cheque->cleared_amount + $clearAmount, 2);
                $chequeAmount = round((float) $cheque->amount, 2);
                $updateData['cleared_amount'] = min($newCleared, $chequeAmount);
                $updateData['status'] = ($newCleared + 0.00001 >= $chequeAmount)
                    ? 'Cleared'
                    : 'Partially Cleared';

                if ($cheque->type === 'payable') {
                    $payment = Payment::create([
                        'payee_account_id' => $cheque->payee_account,
                        'amount' => $clearAmount,
                        'date_of_payment' => $validated['cleared_date'],
                        'reference' => 'Cheque Cleared: '.$cheque->cheque_number,
                        'resource_links' => ResourceLinks::build(
                            null,
                            [],
                            [],
                            null,
                            (int) $cheque->id,
                            ResourceLinks::SOURCE_CHEQUE_CLEAR
                        ),
                        'bank_id' => $bank->id,
                        'amount_type' => 'Cheque',
                        'billing_month' => $validated['billing_month'].'-01',
                        'description' => $description,
                        'bank_charges' => 0,
                        'status' => '1',
                        'attachment' => $voucherAttachment,
                        'branch_id' => $cheque->branch_id,
                        'created_by' => Auth::id(),
                    ]);
                    $transCode = \App\Helpers\Account::trans_code();

                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $payment->date_of_payment,
                        'reference_id' => $payment->id,
                        'reference_type' => 'PV',
                        'account_id' => $bank->account_id,
                        'credit' => $clearAmount,
                        'debit' => 0,
                        'billing_month' => $payment->billing_month,
                        'narration' => $description,
                        'branch_id' => $cheque->branch_id,
                    ]);

                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $payment->date_of_payment,
                        'reference_id' => $payment->id,
                        'reference_type' => 'PV',
                        'account_id' => $cheque->payee_account,
                        'credit' => 0,
                        'debit' => $clearAmount,
                        'billing_month' => $payment->billing_month,
                        'branch_id' => $cheque->branch_id,
                        'narration' => $description,
                    ]);

                    $voucher = Vouchers::create([
                        'trans_date' => $payment->date_of_payment,
                        'trans_code' => $transCode,
                        'billing_month' => $payment->billing_month,
                        'payment_from' => $bank->account_id,
                        'payment_to' => $cheque->payee_account,
                        'amount' => $clearAmount,
                        'voucher_type' => 'PV',
                        'remarks' => 'Payment Voucher',
                        'ref_id' => $payment->id,
                        'Created_By' => Auth::id(),
                        'status' => 1,
                        'attach_file' => $voucherAttachment,
                        'branch_id' => $cheque->branch_id,
                        'custom_field_values' => [],
                    ]);

                    $payment->update(['voucher_id' => $voucher->id]);
                    $updateData['voucher_id'] = $voucher->id;
                } else {
                    $receipt = Receipt::create([
                        'payer_account_id' => $cheque->payer_account,
                        'amount' => $clearAmount,
                        'date_of_receipt' => $validated['cleared_date'],
                        'reference' => 'Cheque Cleared: '.$cheque->cheque_number,
                        'resource_links' => ResourceLinks::build(
                            null,
                            [],
                            [],
                            null,
                            (int) $cheque->id,
                            ResourceLinks::SOURCE_CHEQUE_CLEAR
                        ),
                        'account_id' => $bank->account_id,
                        'bank_id' => $bank->id,
                        'amount_type' => 'Cheque',
                        'billing_month' => $validated['billing_month'].'-01',
                        'description' => $description,
                        'status' => '1',
                        'attachment' => $voucherAttachment,
                        'branch_id' => $cheque->branch_id,
                        'created_by' => Auth::id(),
                    ]);
                    $transCode = \App\Helpers\Account::trans_code();

                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $receipt->date_of_receipt,
                        'reference_id' => $receipt->id,
                        'reference_type' => 'RV',
                        'account_id' => $bank->account_id,
                        'credit' => 0,
                        'debit' => $clearAmount,
                        'billing_month' => $receipt->billing_month,
                        'branch_id' => $cheque->branch_id,
                        'narration' => $description,
                    ]);

                    Transactions::create([
                        'trans_code' => $transCode,
                        'trans_date' => $receipt->date_of_receipt,
                        'reference_id' => $receipt->id,
                        'reference_type' => 'RV',
                        'account_id' => $cheque->payer_account,
                        'credit' => $clearAmount,
                        'debit' => 0,
                        'billing_month' => $receipt->billing_month,
                        'branch_id' => $cheque->branch_id,
                        'narration' => $description,
                    ]);

                    $voucher = Vouchers::create([
                        'trans_date' => $receipt->date_of_receipt,
                        'trans_code' => $transCode,
                        'billing_month' => $receipt->billing_month,
                        'payment_from' => $cheque->payer_account,
                        'payment_to' => $bank->account_id,
                        'amount' => $clearAmount,
                        'voucher_type' => 'RV',
                        'remarks' => 'Receipt Voucher',
                        'ref_id' => $receipt->id,
                        'Created_By' => Auth::id(),
                        'status' => 1,
                        'attach_file' => $voucherAttachment,
                        'branch_id' => $cheque->branch_id,
                        'custom_field_values' => [],
                    ]);
                    $receipt->update(['voucher_id' => $voucher->id]);
                    $updateData['voucher_id'] = $voucher->id;
                }
            }

            $cheque->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cheque status updated successfully!',
                'reload' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Cheque status update failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update cheque status. Please try again.'.$e->getMessage(),
            ], 500);
        }
    }
}
