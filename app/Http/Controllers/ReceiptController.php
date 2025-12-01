<?php

namespace App\Http\Controllers;

use App\Repositories\ReceiptsRepository;
use App\Models\Receipt;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
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
        return view('receipts.create');
    }

    public function store(Request $request)
    {
        $rules = \App\Models\Receipt::$rules;
        $request->validate($rules);
        $input = $request->all();
        $input['created_by'] = auth()->id();
        $receipt = $this->receiptsRepository->create($input);

        // Ledger entries: Debit bank, Credit last account
        $bank = \App\Models\Banks::find($input['bank_id']);
        $bankAccountId = $bank ? $bank->account_id : null;
        $lastAccountId = $input['account_id'];
        $amount = $input['amount'];
        $billingMonth = $input['billing_month'] ?? null;
        // Fix billing_month to be a valid date
        if (!empty($billingMonth) && strlen($billingMonth) === 7) { // 'YYYY-MM'
            $billingMonth .= '-01';
        }
        $date = $input['date_of_receipt'] ?? now();
        $transCode = \App\Helpers\Account::trans_code();

        if ($bankAccountId && $lastAccountId && $amount) {
            // Debit bank
            \App\Models\Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $receipt->id,
                'reference_type' => 'receipt',
                'account_id' => $bankAccountId,
                'debit' => $amount,
                'credit' => 0,
                'billing_month' => $billingMonth,
                'narration' => 'Receipt: Bank Debit',
            ]);
            // Credit last account
            \App\Models\Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $receipt->id,
                'reference_type' => 'receipt',
                'account_id' => $lastAccountId,
                'debit' => 0,
                'credit' => $amount,
                'billing_month' => $billingMonth,
                'narration' => 'Receipt: Account Credit',
            ]);
        }

        // Create voucher and save attachment if present
        $voucherData = [
            'trans_date' => $date,
            'trans_code' => $transCode,
            'posting_date' => $date,
            'billing_month' => $billingMonth,
            'payment_to' => $lastAccountId,
            'payment_from' => $bankAccountId,
            'amount' => $amount,
            'voucher_type' => 'JV',
            'remarks' => 'Journal Voucher',
            'ref_id' => $receipt->id,
            'Created_By' => auth()->id(),
            'status' => 1,
        ];
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('/', 'public');
            $voucherData['attach_file'] = $path;
        }
        \App\Models\Vouchers::create($voucherData);

        Flash::success('Receipt added successfully.');
        return redirect()->back();
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

    public function edit($id)
    {
        $receipt = $this->receiptsRepository->find($id);
        if (empty($receipt)) {
            Flash::error('Receipt not found');
            return redirect(route('receipts.index'));
        }
        return view('receipts.edit')->with('receipt', $receipt);
    }

    public function update($id, Request $request)
    {
        $rules = \App\Models\Receipt::$rules;
        $rules['transaction_number'] .= ',' . $id;
        $request->validate($rules);
        $receipt = $this->receiptsRepository->find($id);
        if (empty($receipt)) {
            Flash::error('Receipt not found!');
        }
        $input = $request->all();
        $input['updated_by'] = auth()->id();
        $receipt = $this->receiptsRepository->update($input, $id);
        Flash::success('Receipt updated successfully.');
        return redirect()->back();
    }

    public function destroy($id)
    {
        $receipt = $this->receiptsRepository->find($id);
        if (empty($receipt)) {
            Flash::error('Receipt not found!');
        } else {
            $this->receiptsRepository->delete($id);
            Flash::success('Receipt deleted successfully.');
        }
        return redirect(route('receipts.index'));
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
