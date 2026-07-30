<?php

namespace App\Services;

use App\Helpers\Account;
use App\Models\Banks;
use App\Models\Payment;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Support\GlobalAccounts;
use App\Support\PublicStorageDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SalikTopUpService
{
    /**
     * Create a Payment-backed PV that tops up the Salik prepaid wallet.
     *
     * Direction matches FuelCompanyTopUpService / PaymentController::store:
     * - Debit Salik asset account (prepaid wallet)
     * - Credit bank account (cash out)
     *
     * @param  array{
     *     bank_id:int|string,
     *     amount:float|int|string,
     *     amount_type:string,
     *     date_of_payment:string,
     *     billing_month:string,
     *     description:string,
     *     reference?:string|null,
     *     attachment?:UploadedFile|null
     * }  $input
     */
    public function create(array $input): Payment
    {
        $salikAssetAccount = GlobalAccounts::account('SALIK_ASSET_ACCOUNT');
        if (! $salikAssetAccount || ! $salikAssetAccount->id) {
            throw new \InvalidArgumentException('Salik asset account is not configured.');
        }

        $bank = Banks::with('account')->active()->find($input['bank_id']);
        if (! $bank) {
            throw new \InvalidArgumentException('Selected bank was not found or is inactive.');
        }

        if (! $bank->account_id || ! $bank->account) {
            throw new \InvalidArgumentException('Selected bank has no linked chart account.');
        }

        $amount = round((float) $input['amount'], 2);
        if ($amount < 0.01) {
            throw new \InvalidArgumentException('Top-up amount must be greater than zero.');
        }

        $billingMonth = $input['billing_month'];
        if (preg_match('/^\d{4}-\d{2}$/', $billingMonth)) {
            $billingMonth .= '-01';
        }

        $payeeAccountId = (int) $salikAssetAccount->id;
        $payingAccountId = (int) $bank->account_id;
        $date = $input['date_of_payment'];
        $description = $input['description'];
        $branchId = $salikAssetAccount->branch_id ?? $bank->branch_id ?? $bank->account->branch_id ?? null;

        return DB::transaction(function () use (
            $input,
            $salikAssetAccount,
            $bank,
            $amount,
            $billingMonth,
            $payeeAccountId,
            $payingAccountId,
            $date,
            $description,
            $branchId
        ) {
            $payment = Payment::create([
                'branch_id' => $branchId,
                'reference' => $input['reference'] ?? null,
                'bank_id' => $bank->id,
                'amount_type' => $input['amount_type'],
                'payee_account_id' => $payeeAccountId,
                'amount' => $amount,
                'bank_charges' => 0,
                'date_of_payment' => $date,
                'billing_month' => $billingMonth,
                'description' => $description,
                'status' => 1,
                'created_by' => auth()->id(),
            ]);

            $transCode = Account::trans_code();

            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $payment->id,
                'reference_type' => 'PV',
                'account_id' => $payingAccountId,
                'credit' => $amount,
                'debit' => 0,
                'billing_month' => $billingMonth,
                'narration' => $description,
                'branch_id' => $branchId,
            ]);

            Transactions::create([
                'trans_code' => $transCode,
                'trans_date' => $date,
                'reference_id' => $payment->id,
                'reference_type' => 'PV',
                'account_id' => $payeeAccountId,
                'credit' => 0,
                'debit' => $amount,
                'billing_month' => $billingMonth,
                'narration' => $description,
                'branch_id' => $branchId,
            ]);

            $voucherData = [
                'trans_date' => $date,
                'trans_code' => $transCode,
                'reference_number' => $payment->reference,
                'billing_month' => $billingMonth,
                'payment_from' => $payingAccountId,
                'payment_to' => $payeeAccountId,
                'amount' => $amount,
                'voucher_type' => 'PV',
                'remarks' => 'Salik top-up — '.($salikAssetAccount->name ?? 'Salik Asset'),
                'ref_id' => $payment->id,
                'Created_By' => auth()->id(),
                'status' => 1,
                'branch_id' => $branchId,
            ];

            if (! empty($input['attachment']) && $input['attachment'] instanceof UploadedFile) {
                $file = $input['attachment'];
                $fileName = time().'_'.$file->getClientOriginalName();
                PublicStorageDisk::storeUploadedFile($file, 'vouchers', $fileName);
                $voucherData['attach_file'] = $fileName;
            }

            $voucher = Vouchers::create($voucherData);

            $payment->update([
                'voucher_id' => $voucher->id,
                'attachment' => $voucher->attach_file ?? null,
            ]);

            return $payment->fresh(['voucher', 'bank', 'payeeAccount']);
        });
    }
}
