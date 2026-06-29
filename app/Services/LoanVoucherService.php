<?php

namespace App\Services;

use App\Helpers\Account;
use App\Models\Accounts;
use App\Models\Banks;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Vouchers;
use App\Support\CompanyQuery;
use Carbon\Carbon;
use App\Helpers\HeadAccount;

class LoanVoucherService
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function disburse(Loan $loan, ?Carbon $disbursementDate = null): Vouchers
    {
        if ($loan->status !== Loan::STATUS_DRAFT) {
            throw new \RuntimeException('Only draft loans can be disbursed.');
        }

        $this->ensureLoanPayableAccount($loan);
        $loan->refresh();

        $receivingBank = Banks::findOrFail($loan->receiving_bank_id);
        if (! $receivingBank->account_id) {
            throw new \RuntimeException('Receiving bank has no linked GL account.');
        }

        $disbursementDate = $disbursementDate ?? Carbon::today();
        $principal = (float) $loan->principal_amount;
        $processingFee = (float) ($loan->processing_fee ?? 0);
        $disbursedAmount = round($principal - $processingFee, 2);

        $transCode = Account::trans_code();
        $billingMonth = $disbursementDate->copy()->startOfMonth()->format('Y-m-d');
        $interestParent = $this->resolveInterestExpenseAccount();

        $voucher = Vouchers::create([
            'trans_date' => $disbursementDate->format('Y-m-d'),
            'trans_code' => $transCode,
            'billing_month' => $billingMonth,
            'payment_type' => 1,
            'voucher_type' => 'BL',
            'remarks' => 'Bank loan disbursement — '.$loan->loan_number,
            'amount' => $principal,
            'reference_number' => $loan->loan_number,
            'Created_By' => auth()->id(),
            'ref_id' => $loan->id,
            'branch_id' => $loan->branch_id,
        ]);

        $narration = 'Loan disbursement '.$loan->loan_number;

        $this->transactionService->recordTransaction([
            'account_id' => $receivingBank->account_id,
            'reference_id' => $loan->id,
            'reference_type' => 'BL',
            'trans_code' => $transCode,
            'trans_date' => $disbursementDate->format('Y-m-d'),
            'narration' => $narration,
            'debit' => $disbursedAmount,
            'branch_id' => $loan->branch_id,
            'billing_month' => $billingMonth,
        ], true);

        $this->transactionService->recordTransaction([
            'account_id' => $loan->account_id,
            'reference_id' => $loan->id,
            'reference_type' => 'BL',
            'trans_code' => $transCode,
            'trans_date' => $disbursementDate->format('Y-m-d'),
            'narration' => $narration,
            'credit' => $principal,
            'branch_id' => $loan->branch_id,
            'billing_month' => $billingMonth,
        ], true);

        if ($processingFee > 0) {
            $this->transactionService->recordTransaction([
                'account_id' => $interestParent->id,
                'reference_id' => $loan->id,
                'reference_type' => 'BL',
                'trans_code' => $transCode,
                'trans_date' => $disbursementDate->format('Y-m-d'),
                'narration' => 'Loan processing fee — '.$loan->loan_number,
                'debit' => $processingFee,
                'branch_id' => $loan->branch_id,
                'billing_month' => $billingMonth,
            ], true);
        }

        $this->updateLedgerEntry($loan->account_id, $billingMonth, 0, $principal, $loan->branch_id);

        $loan->disbursed_amount = $disbursedAmount;
        $loan->disbursement_date = $disbursementDate->format('Y-m-d');
        $loan->outstanding_principal = $principal;
        $loan->status = Loan::STATUS_ACTIVE;
        $loan->save();

        return $voucher;
    }

    /**
     * Create the loan payable COA sub-account on first disbursement.
     */
    public function ensureLoanPayableAccount(Loan $loan): Accounts
    {
        if ($loan->account_id) {
            $existing = Accounts::find($loan->account_id);
            if ($existing) {
                return $existing;
            }
        }

        $parentAccount = Accounts::find(HeadAccount::LOANS_PAYABLE_PARENT_NAME)
            ?? Accounts::where('name', 'Loans Payable')
                ->where('account_type', 'Liability')
                ->whereNull('parent_id')
                ->first();

        if (! $parentAccount) {
            throw new \RuntimeException('Parent account "Loans Payable" not found.');
        }

        $bank = Banks::find($loan->bank_id);
        $account = new Accounts;
        $account->account_code = 'LN'.str_pad($loan->id, 4, '0', STR_PAD_LEFT);
        $account->account_type = 'Liability';
        $account->name = ($bank?->name ?? 'Bank').' — '.$loan->loan_number;
        $account->parent_id = $parentAccount->id;
        $account->ref_name = 'Loan';
        $account->ref_id = $loan->id;
        $account->status = 1;
        $account->branch_id = $loan->branch_id;
        $account->save();

        $loan->account_id = $account->id;
        $loan->save();

        return $account;
    }

    public function repayInstallment(
        LoanInstallment $installment,
        ?int $payingBankId = null,
        ?Carbon $paymentDate = null,
        ?string $narration = null,
        ?float $principal = null,
        ?float $interest = null,
        ?float $total = null,
        ?string $loanPayableNarration = null,
        ?string $interestNarration = null,
        ?string $bankNarration = null
    ): Vouchers {
        $loan = $installment->loan;
        if (! $loan || $loan->status !== Loan::STATUS_ACTIVE) {
            throw new \RuntimeException('Loan is not active.');
        }

        if ($installment->status === LoanInstallment::STATUS_PAID) {
            throw new \RuntimeException('Installment is already paid.');
        }

        $payingBankId = $payingBankId ?? $loan->paying_bank_id ?? $loan->receiving_bank_id;
        $payingBank = Banks::findOrFail($payingBankId);
        if (! $payingBank->account_id) {
            throw new \RuntimeException('Paying bank has no linked GL account.');
        }

        $paymentDate = $paymentDate ?? Carbon::today();
        $principal = round($principal ?? (float) $installment->principal_amount, 2);
        $interest = round($interest ?? (float) $installment->interest_amount, 2);
        $total = round($total ?? (float) $installment->total_amount, 2);

        if (abs($total - ($principal + $interest)) > 0.01) {
            throw new \RuntimeException('Total amount must equal principal plus interest.');
        }

        $defaultNarration = $narration ?? ('Loan EMI #'.$installment->installment_no.' — '.$loan->loan_number);
        $loanPayableNarration = $loanPayableNarration ?: $defaultNarration;
        $interestNarration = $interestNarration ?: $defaultNarration;
        $bankNarration = $bankNarration ?: $defaultNarration;
        $interestParent = $this->resolveInterestExpenseAccount();

        $transCode = Account::trans_code();
        $billingMonth = $paymentDate->copy()->startOfMonth()->format('Y-m-d');

        $voucher = Vouchers::create([
            'trans_date' => $paymentDate->format('Y-m-d'),
            'trans_code' => $transCode,
            'billing_month' => $billingMonth,
            'payment_type' => 1,
            'voucher_type' => 'BL',
            'remarks' => $defaultNarration,
            'amount' => $total,
            'reference_number' => $loan->loan_number,
            'Created_By' => auth()->id(),
            'ref_id' => $installment->id,
            'branch_id' => $loan->branch_id,
        ]);

        $narration = $defaultNarration;

        $this->transactionService->recordTransaction([
            'account_id' => $loan->account_id,
            'reference_id' => $installment->id,
            'reference_type' => 'BL',
            'trans_code' => $transCode,
            'trans_date' => $paymentDate->format('Y-m-d'),
            'narration' => $loanPayableNarration,
            'debit' => $principal,
            'branch_id' => $loan->branch_id,
            'billing_month' => $billingMonth,
        ], true);

        if ($interest > 0) {
            $this->transactionService->recordTransaction([
                'account_id' => $interestParent->id,
                'reference_id' => $installment->id,
                'reference_type' => 'BL',
                'trans_code' => $transCode,
                'trans_date' => $paymentDate->format('Y-m-d'),
                'narration' => $interestNarration,
                'debit' => $interest,
                'branch_id' => $loan->branch_id,
                'billing_month' => $billingMonth,
            ], true);
        }

        $this->transactionService->recordTransaction([
            'account_id' => $payingBank->account_id,
            'reference_id' => $installment->id,
            'reference_type' => 'BL',
            'trans_code' => $transCode,
            'trans_date' => $paymentDate->format('Y-m-d'),
            'narration' => $bankNarration,
            'credit' => $total,
            'branch_id' => $loan->branch_id,
            'billing_month' => $billingMonth,
        ], true);

        $this->updateLedgerEntry($loan->account_id, $billingMonth, $principal, 0, $loan->branch_id);

        $installment->principal_amount = $principal;
        $installment->interest_amount = $interest;
        $installment->total_amount = $total;
        $installment->status = LoanInstallment::STATUS_PAID;
        $installment->paid_amount = $total;
        $installment->paid_date = $paymentDate->format('Y-m-d');
        $installment->voucher_id = $voucher->id;
        $installment->save();

        $loan->outstanding_principal = max(0, round((float) $loan->outstanding_principal - $principal, 2));
        if ($loan->outstanding_principal <= 0 && ! $loan->pendingInstallments()->exists()) {
            $loan->status = Loan::STATUS_CLOSED;
        }
        $loan->save();

        return $voucher;
    }

    protected function resolveInterestExpenseAccount(): Accounts
    {
        $account = Accounts::find(HeadAccount::LOAN_INTEREST_EXPENSE);

        if (! $account) {
            throw new \RuntimeException('Parent account "Loan Interest Expense" not found.');
        }

        return $account;
    }

    protected function updateLedgerEntry(
        int $accountId,
        string $billingMonth,
        float $debit,
        float $credit,
        ?int $branchId
    ): void {
        $lastLedger = CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->orderByDesc('billing_month')
            ->first();

        $opening = $lastLedger ? (float) $lastLedger->closing_balance : 0.0;
        $closing = $opening + $debit - $credit;

        CompanyQuery::insert('ledger_entries', [
            'account_id' => $accountId,
            'billing_month' => $billingMonth,
            'opening_balance' => $opening,
            'debit_balance' => $debit,
            'credit_balance' => $credit,
            'closing_balance' => $closing,
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
