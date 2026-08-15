<?php

namespace App\Services;

use App\Models\DeletionCascade;
use App\Models\salik;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Support\CompanyQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Reverse a posted Salik payment (SV) voucher without touching rider/company invoice ledger.
 */
class SalikPaymentReversalService
{
    public static function unpayLinkedSaliks(int $voucherId): int
    {
        $voucher = Vouchers::withTrashed()->find($voucherId);
        if ($voucher) {
            static::rememberLinkedSaliks($voucher);
        }

        $updated = salik::where('payment_voucher_id', $voucherId)->update([
            'status' => 'unpaid',
            'payment_voucher_id' => null,
            'updated_by' => Auth::id(),
        ]);

        Log::info('Unpaid salik records after SV voucher reversal', [
            'voucher_id' => $voucherId,
            'updated' => $updated,
        ]);

        return (int) $updated;
    }

    /**
     * Persist originally linked trip IDs before payment_voucher_id is cleared.
     */
    public static function rememberLinkedSaliks(Vouchers $voucher): array
    {
        $saliks = salik::where('payment_voucher_id', $voucher->id)->get(['id', 'transaction_id']);
        $ids = $saliks->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        $values = is_array($voucher->custom_field_values) ? $voucher->custom_field_values : [];
        if ($ids === [] && ! empty($values['unpaid_salik_ids']) && is_array($values['unpaid_salik_ids'])) {
            return array_values(array_unique(array_map('intval', $values['unpaid_salik_ids'])));
        }

        $values = $voucher->custom_field_values;
        if (! is_array($values)) {
            $values = [];
        }
        $values['unpaid_salik_ids'] = $ids;
        $voucher->custom_field_values = $values;
        $voucher->save();

        $voucherLabel = ($voucher->voucher_type ?? 'SV') . '-' . str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT);
        foreach ($saliks as $row) {
            DeletionCascade::logCascade(
                Vouchers::class,
                $voucher->id,
                $voucherLabel,
                salik::class,
                $row->id,
                $row->transaction_id ?: ('Salik #' . $row->id),
                'hasMany',
                'salikPayments',
                'unpay',
                'Salik payment reversed; trips unpaid for possible restore'
            );
        }

        return $ids;
    }

    public static function originalLinkedSalikIds(Vouchers $voucher): array
    {
        $values = $voucher->custom_field_values;
        if (is_array($values) && array_key_exists('unpaid_salik_ids', $values) && is_array($values['unpaid_salik_ids'])) {
            return array_values(array_unique(array_map('intval', $values['unpaid_salik_ids'])));
        }

        return DeletionCascade::query()
            ->where('primary_model', Vouchers::class)
            ->where('primary_id', $voucher->id)
            ->where('related_model', salik::class)
            ->where('relationship_name', 'salikPayments')
            ->pluck('related_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Restore is allowed only when every originally linked trip is still unpaid and unlinked.
     */
    public static function assertCanRestore(Vouchers $voucher): void
    {
        if (($voucher->voucher_type ?? '') !== 'SV') {
            return;
        }

        $ids = static::originalLinkedSalikIds($voucher);
        if ($ids === []) {
            throw new \RuntimeException(
                'This Salik payment cannot be restored because its original trips were not recorded. Record a new payment instead.'
            );
        }

        $saliks = salik::whereIn('id', $ids)->get();
        if ($saliks->count() !== count($ids)) {
            throw new \RuntimeException(
                'This Salik payment cannot be restored because one or more original trips no longer exist.'
            );
        }

        $blocked = $saliks->filter(function ($row) {
            $voucherId = $row->payment_voucher_id;
            $hasVoucher = $voucherId !== null && $voucherId !== '' && (int) $voucherId !== 0;
            $paidStatus = strtolower((string) $row->status) === 'paid';

            return $paidStatus || $hasVoucher || $row->isPaid();
        });

        if ($blocked->isNotEmpty()) {
            throw new \RuntimeException(
                'This Salik payment cannot be restored because one or more original trips are already paid or linked to another voucher.'
            );
        }
    }

    /**
     * After the SV voucher is restored: bring back payment GL, re-link unpaid trips, rebuild ledgers.
     */
    public static function completeRestore(Vouchers $voucher): int
    {
        static::assertCanRestore($voucher);

        $relatedTransactions = Transactions::withTrashed()
            ->where('trans_code', $voucher->trans_code)
            ->get();

        foreach ($relatedTransactions as $transaction) {
            if ($transaction->trashed()) {
                $transaction->restore();
            }
            if (Schema::hasColumn($transaction->getTable(), 'deleted_by') && $transaction->deleted_by) {
                $transaction->deleted_by = null;
                $transaction->save();
            }
        }

        $ids = static::originalLinkedSalikIds($voucher);
        $relinked = 0;
        if ($ids !== []) {
            $relinked = salik::whereIn('id', $ids)->update([
                'status' => 'paid',
                'payment_voucher_id' => $voucher->id,
                'updated_by' => Auth::id(),
            ]);
        }

        $billingMonth = $voucher->billing_month;
        $affectedAccounts = $relatedTransactions->pluck('account_id')->unique()->filter();
        foreach ($affectedAccounts as $accountId) {
            if ($accountId && $billingMonth) {
                static::recalculateLedger((int) $accountId, $billingMonth);
            }
        }

        static::clearUnpaySnapshot($voucher);

        return (int) $relinked;
    }

    private static function clearUnpaySnapshot(Vouchers $voucher): void
    {
        $values = is_array($voucher->custom_field_values) ? $voucher->custom_field_values : [];
        if (array_key_exists('unpaid_salik_ids', $values)) {
            unset($values['unpaid_salik_ids']);
            $voucher->custom_field_values = $values;
            $voucher->save();
        }

        DeletionCascade::query()
            ->where('primary_model', Vouchers::class)
            ->where('primary_id', $voucher->id)
            ->where('related_model', salik::class)
            ->where('relationship_name', 'salikPayments')
            ->where('deletion_type', 'unpay')
            ->delete();
    }

    /**
     * Soft-delete the SV voucher and its payment GL lines, unpay linked trips, recalculate ledgers.
     * Caller must run this inside a DB transaction. Bypass delete-approval first when the
     * reverse is part of an edit (replace) rather than a queued unpay.
     */
    public static function reversePostedVoucher(Vouchers $voucher): int
    {
        if (($voucher->voucher_type ?? '') !== 'SV') {
            throw new \InvalidArgumentException('Only Salik payment vouchers can be reversed here.');
        }

        $transCode = $voucher->trans_code;
        $billingMonth = $voucher->billing_month;
        $userId = Auth::id();

        $relatedTransactions = Transactions::where('trans_code', $transCode)->get();
        $affectedAccounts = $relatedTransactions->pluck('account_id')->unique()->filter();

        foreach ($relatedTransactions as $transaction) {
            if (in_array('deleted_by', $transaction->getFillable(), true) && $userId) {
                $transaction->deleted_by = $userId;
                $transaction->save();
            }
            if (! $transaction->trashed()) {
                $transaction->delete();
            }
        }

        if (in_array('deleted_by', $voucher->getFillable(), true) && $userId) {
            $voucher->deleted_by = $userId;
            $voucher->save();
        }

        if (! $voucher->trashed()) {
            $voucher->delete();
        }

        $unpaidCount = static::unpayLinkedSaliks((int) $voucher->id);

        foreach ($affectedAccounts as $accountId) {
            if ($accountId && $billingMonth) {
                static::recalculateLedger((int) $accountId, $billingMonth);
            }
        }

        return $unpaidCount;
    }

    public static function recalculateLedger(int $accountId, $billingMonth): void
    {
        CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->delete();

        $lastLedger = CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', '<', $billingMonth)
            ->orderBy('billing_month', 'desc')
            ->first();

        $openingBalance = $lastLedger ? $lastLedger->closing_balance : 0.00;

        $monthTransactions = Transactions::where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->get();

        $debitTotal = $monthTransactions->sum('debit');
        $creditTotal = $monthTransactions->sum('credit');
        $closingBalance = $openingBalance + $debitTotal - $creditTotal;

        if ($monthTransactions->count() > 0) {
            CompanyQuery::insert('ledger_entries', [
                'account_id' => $accountId,
                'billing_month' => $billingMonth,
                'opening_balance' => $openingBalance,
                'debit_balance' => $debitTotal,
                'credit_balance' => $creditTotal,
                'closing_balance' => $closingBalance,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
