<?php

namespace App\Services\VoucherApproval;

use App\Models\Vouchers;
use App\Support\VoucherApprovalRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Holds / restores ledger rows so pending and rejected vouchers do not affect balances.
 *
 * Holds are marked with BOTH deleted_at and approval_held_at. Restores MUST only
 * clear rows with approval_held_at set — never "all soft-deletes with deleted_by NULL",
 * which would revive obsolete ledger lines left by update/rebuild soft-deletes.
 */
class VoucherApprovalLedger
{
    /**
     * Mutate a transaction insert row so pending vouchers stay off the live ledger.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function applyHoldOnInsert(array $row): array
    {
        if (! VoucherApprovalRegistry::deferPosting()) {
            return $row;
        }

        if (! Schema::hasColumn('transactions', 'deleted_at')) {
            return $row;
        }

        $transCode = $row['trans_code'] ?? null;
        if ($transCode === null || $transCode === '') {
            return $row;
        }

        $voucher = Vouchers::query()->where('trans_code', $transCode)->first();
        if ($voucher === null) {
            return $row;
        }

        if ($voucher->isApprovalApproved()) {
            return $row;
        }

        $heldAt = $row['deleted_at'] ?? now();
        $row['deleted_at'] = $heldAt;
        if (Schema::hasColumn('transactions', 'approval_held_at')) {
            $row['approval_held_at'] = $row['approval_held_at'] ?? $heldAt;
        }

        return $row;
    }

    public static function holdForVoucher(Vouchers $voucher): void
    {
        if (! VoucherApprovalRegistry::deferPosting()) {
            return;
        }

        if (! Schema::hasColumn('transactions', 'deleted_at')) {
            return;
        }

        if ($voucher->trans_code === null || $voucher->trans_code === '') {
            return;
        }

        $heldAt = now();
        $payload = [
            'deleted_at' => $heldAt,
            'updated_at' => $heldAt,
        ];
        if (Schema::hasColumn('transactions', 'approval_held_at')) {
            $payload['approval_held_at'] = $heldAt;
        }

        DB::table('transactions')
            ->where('trans_code', $voucher->trans_code)
            ->whereNull('deleted_at')
            ->update($payload);
    }

    public static function restoreForVoucher(Vouchers $voucher): void
    {
        if (! VoucherApprovalRegistry::deferPosting()) {
            return;
        }

        if (! Schema::hasColumn('transactions', 'deleted_at')) {
            return;
        }

        if ($voucher->trans_code === null || $voucher->trans_code === '') {
            return;
        }

        $query = DB::table('transactions')->where('trans_code', $voucher->trans_code);

        // Strict: only undo explicit approval holds. Never revive ordinary soft-deletes
        // (update rebuild leftovers, trash cascades, etc.).
        if (Schema::hasColumn('transactions', 'approval_held_at')) {
            $query->whereNotNull('approval_held_at');
        } else {
            // Legacy fallback before column exists: still require no user deleted_by.
            if (Schema::hasColumn('transactions', 'deleted_by')) {
                $query->whereNull('deleted_by');
            }
        }

        $payload = [
            'deleted_at' => null,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('transactions', 'approval_held_at')) {
            $payload['approval_held_at'] = null;
        }

        $query->whereNotNull('deleted_at')->update($payload);
    }

    /**
     * Whether a voucher's ledger lines should count toward balances.
     */
    public static function countsTowardBalances(Vouchers $voucher): bool
    {
        if (! VoucherApprovalRegistry::enabled()) {
            return true;
        }

        return $voucher->isApprovalApproved();
    }
}
