<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ExpenseAccount;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Services\DeleteRequestService;
use App\Support\GlobalAccounts;
use Flash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use DB;

trait ManagesExpenseEntryDeletion
{
    /**
     * Queue or apply a visa/license expense delete.
     * Paid rows stay; payment vouchers/transactions are hard-deleted and status becomes unpaid.
     * Unpaid rows soft-delete (recycle bin).
     */
    protected function destroyExpenseEntry(
        ?Model $expense,
        string $referenceType,
        string $voucherType,
        string $notFoundMessage,
        string $deletedMessage,
        string $unpaidMessage
    ) {
        if (empty($expense)) {
            return delete_error_response($notFoundMessage, null, 404);
        }

        $isPaid = DeleteRequestService::isPaidExpense($expense);

        DB::beginTransaction();
        try {
            $relatedTransactions = $this->expenseRelatedTransactions($expense, $referenceType);
            $relatedVouchers = $this->expenseRelatedVouchers($expense, $voucherType);

            $expense->deleted_by = auth()->id();
            $expense->save();
            $expense->delete();

            $pendingQueued = (bool) request()->attributes->get('delete_approval_created');

            // Queue related financials on the delete request (approval path). Paid bypass
            // permanently purges them below so trash restore cannot conflict with a re-pay.
            if ($pendingQueued || ! $isPaid) {
                $this->softDeleteRelatedFinancials($relatedVouchers, $relatedTransactions);
            }

            $successMessage = null;
            if (! $pendingQueued) {
                if ($isPaid) {
                    if (method_exists($expense, 'trashed') && $expense->trashed()) {
                        $expense->restore();
                    }
                    DeleteRequestService::purgePaidExpensePayment(
                        $expense,
                        $referenceType,
                        $relatedVouchers,
                        $relatedTransactions
                    );
                    $successMessage = $unpaidMessage;
                } else {
                    $this->recalculateExpenseLedgers($expense, $referenceType);
                    DeleteRequestService::deleteOrphanExpenseAccount($expense);
                    $successMessage = $deletedMessage;
                }
            }

            DB::commit();

            if ($pendingQueued) {
                if (wants_delete_json()) {
                    return delete_json_response('Expense');
                }

                return redirect()->back();
            }

            if (wants_delete_json()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'queued' => false,
                    'reload' => true,
                ]);
            }

            Flash::success($successMessage);

            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting expense ID: ' . $expense->id . ' - ' . $e->getMessage());

            return delete_error_response('Error deleting expense: ' . $e->getMessage());
        }
    }

    /**
     * Delete an expense account only when it has unpaid rows and no paid rows.
     * Multiple unpaid statuses are queued as one delete request and removed on approval.
     *
     * @param  (\Closure(ExpenseAccount): \Illuminate\Database\Eloquent\Builder)|null  $expenseQueryResolver
     *         Optional scoped query (e.g. category + legacy head-account rows). When omitted,
     *         expenses are matched by expense_account_id only.
     */
    protected function destroyExpenseAccount(
        ExpenseAccount $account,
        string $expenseModelClass,
        string $paidBlockMessage,
        ?string $extraBlockMessage = null,
        ?\Closure $expenseQueryResolver = null
    ) {
        $baseQuery = $expenseQueryResolver
            ? $expenseQueryResolver($account)
            : $expenseModelClass::query()->where('expense_account_id', $account->id);

        // Any paid ticket linked to this account blocks deletion entirely — no delete request.
        $paidExists = (clone $baseQuery)
            ->whereRaw('LOWER(COALESCE(payment_status, "")) = ?', ['paid'])
            ->exists();

        if ($paidExists) {
            return delete_error_response($paidBlockMessage);
        }

        if ($extraBlockMessage) {
            return delete_error_response($extraBlockMessage);
        }

        // Only unpaid rows may be queued/removed; never touch paid tickets.
        $unpaid = (clone $baseQuery)
            ->where(function ($q) {
                $q->whereRaw('LOWER(COALESCE(payment_status, "")) = ?', ['unpaid'])
                    ->orWhereNull('payment_status')
                    ->orWhere('payment_status', '');
            })
            ->orderBy('id')
            ->get();

        DB::beginTransaction();
        try {
            foreach ($unpaid as $expense) {
                if (DeleteRequestService::isPaidExpense($expense)) {
                    DB::rollBack();

                    return delete_error_response($paidBlockMessage);
                }

                if (Schema::hasColumn($expense->getTable(), 'deleted_by')) {
                    $expense->deleted_by = auth()->id();
                    $expense->save();
                }
                $expense->delete();
            }

            $pendingQueued = (bool) request()->attributes->get('delete_approval_created');

            if (! $pendingQueued) {
                $account->delete();
            }

            DB::commit();

            if ($pendingQueued) {
                if (wants_delete_json()) {
                    return delete_json_response('Expense account');
                }

                return redirect()->back();
            }

            if (wants_delete_json()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Account deleted successfully.',
                    'queued' => false,
                    'reload' => true,
                ]);
            }

            Flash::success('Account deleted successfully.');

            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting expense account ID: ' . $account->id . ' - ' . $e->getMessage());

            return delete_error_response('Error deleting account: ' . $e->getMessage());
        }
    }

    protected function expenseRelatedTransactions(Model $expense, string $referenceType): Collection
    {
        return Transactions::where(function ($q) use ($expense, $referenceType) {
            $q->where(function ($inner) use ($expense, $referenceType) {
                $inner->where('reference_id', $expense->id)
                    ->where('reference_type', $referenceType);
            });
            if (! empty($expense->trans_code)) {
                $q->orWhere(function ($inner) use ($expense, $referenceType) {
                    $inner->where('trans_code', $expense->trans_code)
                        ->where('reference_type', $referenceType);
                });
            }
        })->get()->unique('id')->values();
    }

    protected function expenseRelatedVouchers(Model $expense, string $voucherType): Collection
    {
        $query = Vouchers::where('ref_id', $expense->id)
            ->where('voucher_type', $voucherType);
        if ($voucherType === 'LE') {
            $query->where(function ($q) {
                $q->whereNull('reason')->orWhere('reason', '!=', 'license_installment');
            });
        }

        return $query->get();
    }

    protected function softDeleteRelatedFinancials(Collection $vouchers, Collection $transactions): void
    {
        foreach ($vouchers as $voucher) {
            if (Schema::hasColumn($voucher->getTable(), 'deleted_by')) {
                $voucher->deleted_by = auth()->id();
                $voucher->save();
            }
            $voucher->delete();
        }

        foreach ($transactions as $transaction) {
            if (Schema::hasColumn($transaction->getTable(), 'deleted_by')) {
                $transaction->deleted_by = auth()->id();
                $transaction->save();
            }
            $transaction->delete();
        }
    }

    protected function recalculateExpenseLedgers(Model $expense, string $referenceType): void
    {
        DeleteRequestService::recalculateExpenseFinancialLedgers($expense, $referenceType);
    }

    /**
     * Keep LV/LE payment vouchers + transactions aligned with a paid expense row.
     * Preserves existing VAT debit lines; credit + voucher amount = base + VAT.
     *
     * @param  mixed  $previousBillingMonth  Billing month before the edit (Y-m-d / Y-m), for ledger rebuild.
     */
    protected function syncPaidExpenseFinancials(
        Model $expense,
        string $referenceType,
        string $voucherType,
        $previousBillingMonth = null
    ): void {
        if (strtolower((string) ($expense->payment_status ?? '')) !== 'paid') {
            return;
        }

        $vouchers = $this->expenseRelatedVouchers($expense, $voucherType);
        $transactions = $this->expenseRelatedTransactions($expense, $referenceType);
        if ($vouchers->isEmpty() && $transactions->isEmpty()) {
            return;
        }

        $baseAmount = round((float) $expense->amount, 2);
        $vatAccountId = (int) (GlobalAccounts::idOrNull('VAT_PURCHASE_ACCOUNT') ?? 0);
        $vatAmount = 0.0;

        foreach ($transactions as $transaction) {
            if ((float) $transaction->debit > 0
                && $vatAccountId > 0
                && (int) $transaction->account_id === $vatAccountId
            ) {
                $vatAmount += (float) $transaction->debit;
            }
        }
        $vatAmount = round($vatAmount, 2);
        $grandTotal = round($baseAmount + $vatAmount, 2);

        $billingMonth = $expense->billing_month;
        $transDate = $expense->date ?? $expense->trans_date ?? null;
        $accountIds = collect();

        foreach ($transactions as $transaction) {
            $accountIds->push($transaction->account_id);

            if ((float) $transaction->debit > 0) {
                $isVat = $vatAccountId > 0 && (int) $transaction->account_id === $vatAccountId;
                if (! $isVat) {
                    $transaction->debit = $baseAmount;
                }
            }
            if ((float) $transaction->credit > 0) {
                $transaction->credit = $grandTotal;
            }
            if ($billingMonth) {
                $transaction->billing_month = $billingMonth;
            }
            if ($transDate && Schema::hasColumn($transaction->getTable(), 'trans_date')) {
                $transaction->trans_date = $transDate;
            }
            $transaction->save();
        }

        foreach ($vouchers as $voucher) {
            $voucher->amount = $grandTotal;
            if ($expense->getAttribute('reference_number') !== null
                && Schema::hasColumn($voucher->getTable(), 'reference_number')
            ) {
                $voucher->reference_number = $expense->reference_number;
            }
            if ($billingMonth && Schema::hasColumn($voucher->getTable(), 'billing_month')) {
                $voucher->billing_month = $billingMonth;
            }
            if ($transDate && Schema::hasColumn($voucher->getTable(), 'trans_date')) {
                $voucher->trans_date = $transDate;
            }
            $voucher->save();
        }

        $months = collect([$billingMonth, $previousBillingMonth])
            ->filter()
            ->map(function ($month) {
                $month = (string) $month;

                return strlen($month) <= 7 ? $month . '-01' : $month;
            })
            ->unique()
            ->values();

        foreach ($accountIds->filter()->unique() as $accountId) {
            foreach ($months as $month) {
                DeleteRequestService::recalculateLedgerAfterVoucherDeletion((int) $accountId, $month);
            }
        }

        DeleteRequestService::recalculateExpenseRiderLedger($expense);
    }
}
