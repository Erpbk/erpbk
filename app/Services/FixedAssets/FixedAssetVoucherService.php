<?php

namespace App\Services\FixedAssets;

use App\Helpers\Account;
use App\Models\AssetDepreciationSchedule;
use App\Models\FixedAsset;
use App\Models\Transactions;
use App\Models\Vouchers;

class FixedAssetVoucherService
{
    public function __construct(
        private readonly OpeningBalanceAccountService $openingBalanceAccountService
    ) {
    }

    /**
     * @param  array{trans_date: string, billing_month: string, reference_number: string, credit_account_id: int}  $data
     */
    public function createAcquisitionVoucher(FixedAsset $asset, array $data): Vouchers
    {
        if (!$asset->asset_account_id) {
            throw new \RuntimeException('Asset account is not configured for this fixed asset.');
        }

        $amount = round((float) $asset->acquisition_cost, 2);
        if ($amount <= 0) {
            throw new \RuntimeException('Acquisition cost must be greater than zero to post an acquisition voucher.');
        }

        $transCode = Account::trans_code();
        $transDate = $data['trans_date'];
        $billingMonth = str_ends_with($data['billing_month'], '-01')
            ? $data['billing_month']
            : $data['billing_month'] . '-01';
        $narration = 'Fixed asset acquisition: ' . $asset->name . ' (' . ($asset->asset_code ?? $asset->id) . ')';
        $userId = auth()->id();

        Transactions::create([
            'account_id' => $asset->asset_account_id,
            'reference_id' => $asset->id,
            'reference_type' => 'FAV',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $narration,
            'debit' => $amount,
            'credit' => 0,
            'billing_month' => $billingMonth,
            'branch_id' => $asset->branch_id,
            'Created_By' => $userId,
        ]);

        Transactions::create([
            'account_id' => (int) $data['credit_account_id'],
            'reference_id' => $asset->id,
            'reference_type' => 'FAV',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $narration,
            'debit' => 0,
            'credit' => $amount,
            'billing_month' => $billingMonth,
            'branch_id' => $asset->branch_id,
            'Created_By' => $userId,
        ]);

        return Vouchers::create([
            'branch_id' => $asset->branch_id,
            'trans_date' => $transDate,
            'trans_code' => $transCode,
            'billing_month' => $billingMonth,
            'payment_type' => 1,
            'voucher_type' => 'FAV',
            'reference_number' => $data['reference_number'],
            'reason' => 'Fixed Asset Acquisition',
            'remarks' => $narration,
            'amount' => $amount,
            'payment_from' => (int) $data['credit_account_id'],
            'payment_to' => $asset->asset_account_id,
            'ref_id' => $asset->id,
            'Created_By' => $userId,
            'status' => 1,
        ]);
    }

    public function createOpeningBalanceAcquisitionVoucher(FixedAsset $asset): Vouchers
    {
        if (!$asset->asset_account_id || !$asset->accumulated_depreciation_account_id) {
            throw new \RuntimeException('Asset accounts are not configured for this fixed asset.');
        }

        $cost = round((float) $asset->acquisition_cost, 2);
        if ($cost <= 0) {
            throw new \RuntimeException('Acquisition cost must be greater than zero to post an opening balance voucher.');
        }

        $accumulatedDepreciation = round((float) $asset->opening_accumulated_depreciation, 2);
        $equityAmount = round($cost - $accumulatedDepreciation, 2);

        if ($accumulatedDepreciation > $cost) {
            throw new \RuntimeException('Opening accumulated depreciation cannot exceed acquisition cost.');
        }

        $equityAccount = \App\Support\GlobalAccounts::id('OPENING_BALANCE_EQUITY');
        if (! $equityAccount) {
            throw new \RuntimeException('Equity account is not configured. Contact ERP Team to Configure it.');
        }

        $transCode = Account::trans_code();
        $transDate = $asset->acquisition_date->toDateString();
        $billingMonth = $asset->acquisition_date->copy()->startOfMonth()->toDateString();
        $referenceNumber = 'OB-' . ($asset->asset_code ?? $asset->id);
        $narration = 'Opening balance fixed asset: ' . $asset->name . ' (' . ($asset->asset_code ?? $asset->id) . ')';
        $userId = auth()->id();

        Transactions::create([
            'account_id' => $asset->asset_account_id,
            'reference_id' => $asset->id,
            'reference_type' => 'FAV',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $narration,
            'debit' => $cost,
            'credit' => 0,
            'billing_month' => $billingMonth,
            'branch_id' => $asset->branch_id,
            'Created_By' => $userId,
        ]);

        if ($accumulatedDepreciation > 0) {
            Transactions::create([
                'account_id' => $asset->accumulated_depreciation_account_id,
                'reference_id' => $asset->id,
                'reference_type' => 'FAV',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => $narration,
                'debit' => 0,
                'credit' => $accumulatedDepreciation,
                'billing_month' => $billingMonth,
                'branch_id' => $asset->branch_id,
                'Created_By' => $userId,
            ]);
        }

        if ($equityAmount > 0) {
            Transactions::create([
                'account_id' => $equityAccount,
                'reference_id' => $asset->id,
                'reference_type' => 'FAV',
                'trans_code' => $transCode,
                'trans_date' => $transDate,
                'narration' => $narration,
                'debit' => 0,
                'credit' => $equityAmount,
                'billing_month' => $billingMonth,
                'branch_id' => $asset->branch_id,
                'Created_By' => $userId,
            ]);
        }

        return Vouchers::create([
            'branch_id' => $asset->branch_id,
            'trans_date' => $transDate,
            'trans_code' => $transCode,
            'billing_month' => $billingMonth,
            'payment_type' => 1,
            'voucher_type' => 'FAV',
            'reference_number' => $referenceNumber,
            'reason' => 'Fixed Asset Opening Balance',
            'remarks' => $narration,
            'amount' => $cost,
            'payment_from' => $equityAccount,
            'payment_to' => $asset->asset_account_id,
            'ref_id' => $asset->id,
            'Created_By' => $userId,
            'status' => 1,
        ]);
    }

    public function createDepreciationVoucher(FixedAsset $asset, AssetDepreciationSchedule $schedule): Vouchers
    {
        if (!$asset->depreciation_expense_account_id || !$asset->accumulated_depreciation_account_id) {
            throw new \RuntimeException('Depreciation accounts are not configured for this fixed asset.');
        }

        $amount = round((float) $schedule->depreciation_amount, 2);
        if ($amount <= 0) {
            throw new \RuntimeException('Depreciation amount must be greater than zero.');
        }

        $transCode = Account::trans_code();
        $transDate = $schedule->period_date->toDateString();
        $billingMonth = $schedule->period_date->copy()->startOfMonth()->toDateString();
        $narration = sprintf(
            'Depreciation: %s (%s) — period %d',
            $asset->name,
            $asset->asset_code ?? $asset->id,
            $schedule->period_number
        );

        Transactions::create([
            'account_id' => $asset->depreciation_expense_account_id,
            'reference_id' => $schedule->id,
            'reference_type' => 'FDV',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $narration,
            'debit' => $amount,
            'credit' => 0,
            'billing_month' => $billingMonth,
            'branch_id' => $asset->branch_id,
        ]);

        Transactions::create([
            'account_id' => $asset->accumulated_depreciation_account_id,
            'reference_id' => $schedule->id,
            'reference_type' => 'FDV',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $narration,
            'debit' => 0,
            'credit' => $amount,
            'billing_month' => $billingMonth,
            'branch_id' => $asset->branch_id,
        ]);

        return Vouchers::create([
            'branch_id' => $asset->branch_id,
            'trans_date' => $transDate,
            'trans_code' => $transCode,
            'billing_month' => $billingMonth,
            'payment_type' => 1,
            'voucher_type' => 'FDV',
            'reason' => 'Fixed Asset Depreciation',
            'remarks' => $narration,
            'amount' => $amount,
            'payment_from' => $asset->depreciation_expense_account_id,
            'payment_to' => $asset->accumulated_depreciation_account_id,
            'ref_id' => $schedule->id,
            'status' => 1,
        ]);
    }
}
