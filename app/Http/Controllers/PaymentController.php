<?php

namespace App\Http\Controllers;

use App\Repositories\PaymentsRepository;
use App\Models\Payment;
use App\Models\Accounts;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
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
        return view('payments.create');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $input['created_by'] = auth()->id();
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('payments', 'public');
            $input['attachment'] = $path;
        }
        $payment = $this->paymentsRepository->create($input);

        // Ledger entries: Credit bank, Debit account
        $bank = \App\Models\Banks::find($input['bank_id']);
        $bankAccountId = $bank ? $bank->account_id : null;
        $lastAccountId = $input['account_id'];
        $amount = $input['amount'];
        $billingMonth = $input['billing_month'] ?? null;
        // Fix billing_month to be a valid date
        if (!empty($billingMonth) && strlen($billingMonth) === 7) { // 'YYYY-MM'
            $billingMonth .= '-01';
        }
        $date = $input['date_of_payment'] ?? now();
        $transCode = \App\Helpers\Account::trans_code();

        if ($bankAccountId && $lastAccountId && $amount) {
            // Debit account
            \App\Models\Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $payment->id,
                'reference_type' => 'Voucher',
                'account_id' => $lastAccountId,
                'debit' => $amount,
                'credit' => 0,
                'billing_month' => $billingMonth,
                'narration' => 'Payment: Account Debit',
            ]);
            // Credit bank
            \App\Models\Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $payment->id,
                'reference_type' => 'Voucher',
                'account_id' => $bankAccountId,
                'debit' => 0,
                'credit' => $amount,
                'billing_month' => $billingMonth,
                'narration' => 'Payment: Bank Credit',
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
            'ref_id' => $payment->id,
            'Created_By' => auth()->id(),
            'status' => 1,
        ];
        if ($request->hasFile('attachment')) {
            $voucherData['attach_file'] = $input['attachment'];
        }
        \App\Models\Vouchers::create($voucherData);

        Flash::success('Payment added successfully.');
        return redirect()->back();
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

    public function edit($id)
    {
        $payment = $this->paymentsRepository->find($id);
        if (empty($payment)) {
            Flash::error('Payment not found');
            return redirect(route('payments.index'));
        }
        return view('payments.edit')->with('payment', $payment);
    }

    public function update($id, Request $request)
    {
        $payment = $this->paymentsRepository->find($id);
        if (empty($payment)) {
            Flash::error('Payment not found!');
        }
        $input = $request->all();
        $input['updated_by'] = auth()->id();
        $payment = $this->paymentsRepository->update($input, $id);
        Flash::success('Payment updated successfully.');
        return redirect()->back();
    }

    public function destroy($id)
    {
        $payment = $this->paymentsRepository->find($id);
        if (empty($payment)) {
            Flash::error('Payment not found!');
        } else {
            $this->paymentsRepository->delete($id);
            Flash::success('Payment deleted successfully.');
        }
        return redirect(route('payments.index'));
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
