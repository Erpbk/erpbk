<?php

namespace App\Repositories;

use App\Models\cod;
use App\Models\Riders;
use App\Models\Accounts;
use App\Models\Vouchers;
use App\Models\Transactions;
use App\Services\TransactionService;
use App\Helpers\Account;
use Carbon\Carbon;
use DB;
use Auth;

class CodRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model(): string
    {
        return cod::class;
    }

    /**
     * @return array
     */
    public function getFieldsSearchable(): array
    {
        return [
            'rider_id',
            'transaction_date',
            'amount',
            'status',
            'description'
        ];
    }

    /**
     * Create COD entry with voucher and accounting logic
     */
    public function createWithAccounting($data)
    {
        DB::beginTransaction();

        try {
            // Set default values
            $data['created_by'] = Auth::user()->id;
            $data['status'] = $data['status'] ?? 'pending';
            $data['billing_month'] = $data['billing_month'] ?? date('Y-m-01', strtotime($data['transaction_date']));

            // Create COD entry
            $cod = $this->create($data);

            // Create voucher and accounting entries
            $this->createVoucherAndTransactions($cod, $data);

            DB::commit();
            return $cod;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update COD entry with voucher adjustments
     */
    public function updateWithAccounting($id, $data)
    {
        DB::beginTransaction();

        try {
            $cod = $this->find($id);
            $oldAmount = $cod->amount;
            $newAmount = $data['amount'];
            $amountDifference = $newAmount - $oldAmount;

            // Update COD entry
            $data['updated_by'] = Auth::user()->id;
            $cod = $this->update($data, $id);

            // Adjust vouchers if amount changed
            if ($amountDifference != 0) {
                $this->adjustVoucherForUpdate($cod, $amountDifference);
            }

            DB::commit();
            return $cod;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete COD entry with voucher cleanup
     */
    public function deleteWithAccounting($id)
    {
        DB::beginTransaction();

        try {
            $cod = $this->find($id);

            // Delete related transactions and vouchers
            Transactions::where('reference_id', $cod->id)
                ->where('reference_type', 'COD')
                ->delete();

            Vouchers::where('ref_id', $cod->id)
                ->where('voucher_type', 'COD')
                ->delete();

            // Delete COD entry
            $this->delete($id);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get COD entries by rider
     */
    public function getByRider($riderId, $perPage = 50)
    {
        return $this->model->where('rider_id', $riderId)
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get COD entries by status
     */
    public function getByStatus($status, $perPage = 50)
    {
        return $this->model->where('status', $status)
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get COD entries by date range
     */
    public function getByDateRange($startDate, $endDate, $perPage = 50)
    {
        return $this->model->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get total COD amount by rider
     */
    public function getTotalByRider($riderId, $status = null)
    {
        $query = $this->model->where('rider_id', $riderId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->sum('amount');
    }

    /**
     * Get COD statistics
     */
    public function getStatistics($riderId = null)
    {
        $query = $this->model;

        if ($riderId) {
            $query = $query->where('rider_id', $riderId);
        }

        return [
            'total_count' => $query->count(),
            'paid_count' => $query->where('status', 'paid')->count(),
            'unpaid_count' => $query->where('status', 'unpaid')->count(),
            'pending_count' => $query->where('status', 'pending')->count(),
            'total_amount' => $query->sum('amount'),
            'paid_amount' => $query->where('status', 'paid')->sum('amount'),
            'unpaid_amount' => $query->where('status', 'unpaid')->sum('amount'),
        ];
    }

    /**
     * Mark COD as paid
     */
    public function markAsPaid($id, $paymentData = [])
    {
        DB::beginTransaction();

        try {
            $cod = $this->find($id);
            $cod->status = 'paid';
            $cod->updated_by = Auth::user()->id;
            $cod->save();

            // Create payment voucher if payment data provided
            if (!empty($paymentData)) {
                $this->createPaymentVoucher($cod, $paymentData);
            }

            DB::commit();
            return $cod;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createVoucherAndTransactions($cod, $data)
    {
        $rider = Riders::find($cod->rider_id);
        $riderAccount = Accounts::where('ref_id', $rider->id)->first();

        if (!$riderAccount) {
            throw new \Exception('Rider account not found');
        }

        $transCode = Account::trans_code();
        $transDate = Carbon::parse($cod->transaction_date);
        $billingMonth = $cod->billing_month;
        $transactionService = new TransactionService();

        // Debit Rider Account
        $transactionService->recordTransaction([
            'account_id'     => $riderAccount->id,
            'reference_id'   => $cod->id,
            'reference_type' => 'COD',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => 'COD Transaction - ' . ($cod->description ?? 'COD Payment'),
            'debit'          => $cod->amount,
            'billing_month'  => $billingMonth,
        ]);

        // Credit COD Account (you may need to define this account ID)
        $codAccountId = 1500; // Define your COD account ID
        $transactionService->recordTransaction([
            'account_id'     => $codAccountId,
            'reference_id'   => $cod->id,
            'reference_type' => 'COD',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => 'COD Transaction - ' . ($cod->description ?? 'COD Payment'),
            'credit'         => $cod->amount,
            'billing_month'  => $billingMonth,
        ]);

        // Create voucher
        Vouchers::create([
            'trans_date'    => $transDate,
            'trans_code'    => $transCode,
            'payment_type'  => 1,
            'billing_month' => $billingMonth,
            'amount'        => $cod->amount,
            'voucher_type'  => 'COD',
            'remarks'       => 'COD Voucher',
            'ref_id'        => $cod->id,
            'rider_id'      => $cod->rider_id,
            'payment_to'    => $codAccountId,
            'payment_from'  => $riderAccount->id,
            'Created_By'    => Auth::user()->id,
        ]);
    }

    private function adjustVoucherForUpdate($cod, $amountDifference)
    {
        // Find and update related transactions
        $transactions = Transactions::where('reference_id', $cod->id)
            ->where('reference_type', 'COD')
            ->get();

        foreach ($transactions as $transaction) {
            if ($transaction->debit > 0) {
                $transaction->debit += $amountDifference;
            } else {
                $transaction->credit += $amountDifference;
            }
            $transaction->save();
        }

        // Update voucher amount
        $voucher = Vouchers::where('ref_id', $cod->id)
            ->where('voucher_type', 'COD')
            ->first();

        if ($voucher) {
            $voucher->amount += $amountDifference;
            $voucher->save();
        }
    }

    private function createPaymentVoucher($cod, $paymentData)
    {
        $transCode = Account::trans_code();
        $transDate = now();
        $transactionService = new TransactionService();

        // Credit Rider Account (payment received)
        $rider = Riders::find($cod->rider_id);
        $riderAccount = Accounts::where('ref_id', $rider->id)->first();

        if ($riderAccount) {
            $transactionService->recordTransaction([
                'account_id'     => $riderAccount->id,
                'reference_id'   => $cod->id,
                'reference_type' => 'COD',
                'trans_code'     => $transCode,
                'trans_date'     => $transDate,
                'narration'      => 'COD Payment Received',
                'credit'         => $cod->amount,
                'billing_month'  => $cod->billing_month,
            ]);
        }

        // Debit Bank/Cash Account
        $paymentAccountId = $paymentData['payment_account_id'] ?? 1001; // Default cash account
        $transactionService->recordTransaction([
            'account_id'     => $paymentAccountId,
            'reference_id'   => $cod->id,
            'reference_type' => 'COD',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => 'COD Payment Received',
            'debit'          => $cod->amount,
            'billing_month'  => $cod->billing_month,
        ]);

        // Create payment voucher
        Vouchers::create([
            'trans_date'    => $transDate,
            'trans_code'    => $transCode,
            'payment_type'  => 1,
            'billing_month' => $cod->billing_month,
            'amount'        => $cod->amount,
            'voucher_type'  => 'CODPAY',
            'remarks'       => 'COD Payment Voucher',
            'ref_id'        => $cod->id,
            'rider_id'      => $cod->rider_id,
            'payment_to'    => $paymentAccountId,
            'payment_from'  => $riderAccount->id,
            'Created_By'    => Auth::user()->id,
        ]);
    }
}
