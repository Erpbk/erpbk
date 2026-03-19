<?php

namespace App\Http\Controllers;
use App\Repositories\ReceiptsRepository;
use App\Models\Receipt;
use App\Models\Banks;
use App\Models\LeasingCompanies;
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
        $query = Receipt::query()->with(['payerAccount','payeeAccount'])->orderBy('date_of_receipt', 'desc');
        // Apply pagination using the trait
        $data = $this->applyPagination($query, $paginationParams);
        return view('receipts.index', ['data' => $data]);
    }

    public function create()
    {
        $accountId = request()->input('id') ?? null;
        $leasingCompanyId = request()->input('leasing_company_id') ?? null;

        if ($accountId) {
            $bank = Banks::find($accountId);
            return view('receipts.create', compact('bank'));
        } elseif ($leasingCompanyId) {
            $leasingCompany = LeasingCompanies::find($leasingCompanyId);
            $banks = Banks::with('account')->active()->get();
            return view('receipts.create', compact('leasingCompany','banks'));
        } else {
            $banks = Banks::with('account')->active()->get();
            return view('receipts.create', compact('banks'));
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
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ];

        $messages = [
            'bank_id.required' => 'Bank Account is Required',
            'date_of_receipt.required' => 'Receipt date is Required',
            'billing_month.required' => 'Billing month is Required',
            'description.required' => 'Narration for Transaction is Required',
            'payer_account_id.required' => 'Sender Account is Required',
            'amount.required' => 'Amount is Required',
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
        $input['account_id'] = $bank->account_id;

        try {
            DB::beginTransaction();

            $receipt = Receipt::create($input);
            $transCode = \App\Helpers\Account::trans_code();

            $date = $input['date_of_receipt'] ?? now();
            $billingMonth = $input['billing_month'];
            $desc = $input['description'];

            // DEBIT the receiving account (BANK or LEASING COMPANY)
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
        if (empty($receipt)) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Receipt Not found'], 404);
            }
            Flash::error('Receipt not found');
            return redirect()->back();
        }

        $banks = Banks::active()->get();

        $receipt->billing_month = \Carbon\Carbon::parse($receipt->billing_month)->format('Y-m');

        return view('receipts.edit', compact('receipt', 'banks'));
    }

    public function update(Request $request, $id)
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
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ];

        $messages = [
            'bank_id.required' => 'Bank Account is Required',
            'date_of_receipt.required' => 'Receipt date is Required',
            'billing_month.required' => 'Billing month is Required',
            'description.required' => 'Narration for Transaction is Required',
            'payer_account_id.required' => 'Sender Account is Required',
            'amount.required' => 'Amount is Required',
            'amount.min' => 'Amount must be greater than zero',
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
            $input['updated_by'] = auth()->id();

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
                    'narration' => $desc,
                ]);

                // Update voucher
                $voucherData = [
                    'trans_date' => $date,
                    'billing_month' => $billingMonth,
                    'reference_number' => $receipt->reference,
                    'payment_to' => $bank->account_id,
                    'amount' => $amount,
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

    public function destroy(Request $request, $id)
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
            $receipt->delete();
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Receipt Deleted Successfuly', 'reload' => true]);
            }
        }
        Flash::success('Receipt deleted successfully.');
        return redirect()->back();
    }

    public function clone(Request $request, $id)
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
