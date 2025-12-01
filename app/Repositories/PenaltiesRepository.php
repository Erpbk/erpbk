<?php

namespace App\Repositories;

use App\Models\penalties;
use App\Models\Riders;
use App\Models\Accounts;
use App\Models\Vouchers;
use App\Models\Transactions;
use App\Services\TransactionService;
use App\Helpers\Account;
use Carbon\Carbon;
use DB;
use Auth;

class PenaltiesRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model(): string
    {
        return penalties::class;
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
     * Create Penalty entry with voucher and accounting logic
     */
    public function createWithAccounting($data)
    {
        DB::beginTransaction();

        try {
            // Set default values
            $data['created_by'] = Auth::user()->id;
            $data['status'] = $data['status'] ?? 'unpaid';
            $data['billing_month'] = $data['billing_month'] ?? date('Y-m-01', strtotime($data['transaction_date']));

            // Create Penalty entry
            $penalty = $this->create($data);

            // Create voucher and accounting entries
            $this->createVoucherAndTransactions($penalty, $data);

            DB::commit();
            return $penalty;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update Penalty entry with voucher adjustments
     */
    public function updateWithAccounting($id, $data)
    {
        DB::beginTransaction();

        try {
            $penalty = $this->find($id);
            $oldAmount = $penalty->amount;
            $newAmount = $data['amount'];
            $amountDifference = $newAmount - $oldAmount;

            // Update Penalty entry
            $data['updated_by'] = Auth::user()->id;
            $penalty = $this->update($data, $id);

            // Adjust vouchers if amount changed
            if ($amountDifference != 0) {
                $this->adjustVoucherForUpdate($penalty, $amountDifference);
            }

            DB::commit();
            return $penalty;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete Penalty entry with voucher cleanup
     */
    public function deleteWithAccounting($id)
    {
        DB::beginTransaction();

        try {
            $penalty = $this->find($id);

            // Delete related transactions and vouchers
            Transactions::where('reference_id', $penalty->id)
                ->where('reference_type', 'Penalty')
                ->delete();

            Vouchers::where('ref_id', $penalty->id)
                ->where('voucher_type', 'PENALTY')
                ->delete();

            // Delete Penalty entry
            $this->delete($id);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get Penalty entries by rider
     */
    public function getByRider($riderId, $perPage = 50)
    {
        return $this->model->where('rider_id', $riderId)
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get Penalty entries by status
     */
    public function getByStatus($status, $perPage = 50)
    {
        return $this->model->where('status', $status)
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get Penalty entries by date range
     */
    public function getByDateRange($startDate, $endDate, $perPage = 50)
    {
        return $this->model->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get total penalty amount by rider
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
     * Mark penalty as paid
     */
    public function markAsPaid($id, $paymentData = [])
    {
        DB::beginTransaction();

        try {
            $penalty = $this->find($id);
            $penalty->status = 'paid';
            $penalty->updated_by = Auth::user()->id;
            $penalty->save();

            // Create payment voucher if payment data provided
            if (!empty($paymentData)) {
                $this->createPaymentVoucher($penalty, $paymentData);
            }

            DB::commit();
            return $penalty;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get penalty statistics
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
     * Import penalties from array data
     */
    public function importPenalties($penaltyData)
    {
        DB::beginTransaction();

        try {
            $imported = [];
            $skipped = [];

            foreach ($penaltyData as $data) {
                try {
                    // Validate required fields
                    if (empty($data['rider_id']) || empty($data['amount']) || empty($data['transaction_date'])) {
                        $skipped[] = $data;
                        continue;
                    }

                    // Check for duplicates if transaction_id exists
                    if (!empty($data['transaction_id'])) {
                        $exists = $this->model->where('transaction_id', $data['transaction_id'])->exists();
                        if ($exists) {
                            $skipped[] = $data;
                            continue;
                        }
                    }

                    $penalty = $this->createWithAccounting($data);
                    $imported[] = $penalty;
                } catch (\Exception $e) {
                    $skipped[] = array_merge($data, ['error' => $e->getMessage()]);
                }
            }

            DB::commit();
            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'imported_count' => count($imported),
                'skipped_count' => count($skipped)
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createVoucherAndTransactions($penalty, $data)
    {
        $rider = Riders::find($penalty->rider_id);
        $riderAccount = Accounts::where('ref_id', $rider->id)->first();

        if (!$riderAccount) {
            throw new \Exception('Rider account not found');
        }

        $transCode = Account::trans_code();
        $transDate = Carbon::parse($penalty->transaction_date);
        $billingMonth = $penalty->billing_month;
        $transactionService = new TransactionService();

        // Debit Rider Account
        $transactionService->recordTransaction([
            'account_id'     => $riderAccount->id,
            'reference_id'   => $penalty->id,
            'reference_type' => 'Penalty',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => 'Penalty - ' . ($penalty->description ?? 'Penalty Charge'),
            'debit'          => $penalty->amount,
            'billing_month'  => $billingMonth,
        ]);

        // Credit Penalty Account
        $penaltyAccountId = 1600; // Define your Penalty account ID
        $transactionService->recordTransaction([
            'account_id'     => $penaltyAccountId,
            'reference_id'   => $penalty->id,
            'reference_type' => 'Penalty',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => 'Penalty - ' . ($penalty->description ?? 'Penalty Charge'),
            'credit'         => $penalty->amount,
            'billing_month'  => $billingMonth,
        ]);

        // Create voucher
        Vouchers::create([
            'trans_date'    => $transDate,
            'trans_code'    => $transCode,
            'payment_type'  => 1,
            'billing_month' => $billingMonth,
            'amount'        => $penalty->amount,
            'voucher_type'  => 'PENALTY',
            'remarks'       => 'Penalty Voucher',
            'ref_id'        => $penalty->id,
            'rider_id'      => $penalty->rider_id,
            'payment_to'    => $penaltyAccountId,
            'payment_from'  => $riderAccount->id,
            'Created_By'    => Auth::user()->id,
        ]);
    }

    private function adjustVoucherForUpdate($penalty, $amountDifference)
    {
        // Find and update related transactions
        $transactions = Transactions::where('reference_id', $penalty->id)
            ->where('reference_type', 'Penalty')
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
        $voucher = Vouchers::where('ref_id', $penalty->id)
            ->where('voucher_type', 'PENALTY')
            ->first();

        if ($voucher) {
            $voucher->amount += $amountDifference;
            $voucher->save();
        }
    }

    private function createPaymentVoucher($penalty, $paymentData)
    {
        $transCode = Account::trans_code();
        $transDate = now();
        $transactionService = new TransactionService();

        // Credit Rider Account (penalty paid)
        $rider = Riders::find($penalty->rider_id);
        $riderAccount = Accounts::where('ref_id', $rider->id)->first();

        if ($riderAccount) {
            $transactionService->recordTransaction([
                'account_id'     => $riderAccount->id,
                'reference_id'   => $penalty->id,
                'reference_type' => 'Penalty',
                'trans_code'     => $transCode,
                'trans_date'     => $transDate,
                'narration'      => 'Penalty Payment',
                'credit'         => $penalty->amount,
                'billing_month'  => $penalty->billing_month,
            ]);
        }

        // Debit Bank/Cash Account
        $paymentAccountId = $paymentData['payment_account_id'] ?? 1001; // Default cash account
        $transactionService->recordTransaction([
            'account_id'     => $paymentAccountId,
            'reference_id'   => $penalty->id,
            'reference_type' => 'Penalty',
            'trans_code'     => $transCode,
            'trans_date'     => $transDate,
            'narration'      => 'Penalty Payment',
            'debit'          => $penalty->amount,
            'billing_month'  => $penalty->billing_month,
        ]);

        // Create payment voucher
        Vouchers::create([
            'trans_date'    => $transDate,
            'trans_code'    => $transCode,
            'payment_type'  => 1,
            'billing_month' => $penalty->billing_month,
            'amount'        => $penalty->amount,
            'voucher_type'  => 'PENALTYPAY',
            'remarks'       => 'Penalty Payment Voucher',
            'ref_id'        => $penalty->id,
            'rider_id'      => $penalty->rider_id,
            'payment_to'    => $paymentAccountId,
            'payment_from'  => $riderAccount->id,
            'Created_By'    => Auth::user()->id,
        ]);
    }
}
