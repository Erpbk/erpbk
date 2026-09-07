<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ExpenseAccount;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Services\DeleteRequestService;
use Flash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use DB;

trait ManagesExpenseEntryDeletion
{
    /**
     * Queue or apply a visa/license expense delete.
     * Paid rows stay and reverse payment after approval; unpaid rows are removed.
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

            $this->softDeleteRelatedFinancials($relatedVouchers, $relatedTransactions);

            $successMessage = null;
            if (! $pendingQueued) {
                if ($isPaid) {
                    if (method_exists($expense, 'trashed') && $expense->trashed()) {
                        $expense->restore();
                    }
                    DeleteRequestService::unpayExpenseInPlace($expense, $referenceType);
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
     */
    protected function destroyExpenseAccount(
        ExpenseAccount $account,
        string $expenseModelClass,
        string $paidBlockMessage,
        ?string $extraBlockMessage = null
    ) {
        $paidExists = $expenseModelClass::where('expense_account_id', $account->id)
            ->where('payment_status', 'paid')
            ->exists();

        if ($paidExists) {
            return delete_error_response($paidBlockMessage);
        }

        if ($extraBlockMessage) {
            return delete_error_response($extraBlockMessage);
        }

        $unpaid = $expenseModelClass::where('expense_account_id', $account->id)
            ->orderBy('id')
            ->get();

        DB::beginTransaction();
        try {
            foreach ($unpaid as $expense) {
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
}
