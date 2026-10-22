<?php

namespace App\Services;

use App\Helpers\Account;
use App\Models\Accounts;
use App\Models\Vouchers;
use App\Support\GlobalAccounts;
use Illuminate\Support\Facades\Auth;

class AccountOpeningBalanceJvService
{
    public const GLOBAL_EQUITY_CODE = 'OPENING_BALANCE_EQUITY';

    public function __construct(
        private readonly TransactionService $transactionService
    ) {}

    /**
     * Post a JV for a newly created account's opening balance.
     * Skips zero amounts and accounts that are fixed / linked to a global account.
     */
    public function postForNewAccount(Accounts $account, float|int|string|null $amount = null, ?int $branchId = null): ?Vouchers
    {
        if ($account->is_fixed || $account->isLinkedToGlobalAccount()) {
            return null;
        }

        $opening = round((float) ($amount ?? $account->opening_balance ?? 0), 2);
        if (abs($opening) < 0.01) {
            return null;
        }

        $equityAccountId = GlobalAccounts::id(self::GLOBAL_EQUITY_CODE);
        $absolute = abs($opening);
        $isPositive = $opening > 0;

        $branchId = $branchId
            ?? ($account->branch_id ? (int) $account->branch_id : null)
            ?? (Auth::user()?->branch_id ? (int) Auth::user()->branch_id : null);

        $transCode = Account::trans_code();
        $transDate = now()->toDateString();
        $billingMonth = now()->startOfMonth()->toDateString();
        $userId = Auth::id();
        $accountLabel = trim(($account->account_code ? $account->account_code . ' — ' : '') . (string) $account->name);
        $narration = 'Opening balance for account: ' . $accountLabel;
        $referenceNumber = 'OB-ACC-' . $account->id;

        // Positive: debit new account, credit opening equity.
        // Negative: credit new account, debit opening equity.
        $debitAccountId = $isPositive ? (int) $account->id : $equityAccountId;
        $creditAccountId = $isPositive ? $equityAccountId : (int) $account->id;

        $voucher = Vouchers::create([
            'branch_id' => $branchId,
            'trans_date' => $transDate,
            'trans_code' => $transCode,
            'billing_month' => $billingMonth,
            'payment_type' => 1,
            'voucher_type' => 'JV',
            'reference_number' => $referenceNumber,
            'reason' => 'Account Opening Balance',
            'remarks' => $narration,
            'amount' => $absolute,
            'payment_from' => $creditAccountId,
            'payment_to' => $debitAccountId,
            'ref_id' => $account->id,
            'Created_By' => $userId,
            'status' => 1,
        ]);

        $this->transactionService->recordTransaction([
            'account_id' => $debitAccountId,
            'reference_id' => $voucher->id,
            'reference_type' => 'Voucher',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $narration,
            'debit' => $absolute,
            'credit' => 0,
            'billing_month' => $billingMonth,
            'branch_id' => $branchId,
        ], true);

        $this->transactionService->recordTransaction([
            'account_id' => $creditAccountId,
            'reference_id' => $voucher->id,
            'reference_type' => 'Voucher',
            'trans_code' => $transCode,
            'trans_date' => $transDate,
            'narration' => $narration,
            'debit' => 0,
            'credit' => $absolute,
            'billing_month' => $billingMonth,
            'branch_id' => $branchId,
        ], true);

        return $voucher;
    }
}
