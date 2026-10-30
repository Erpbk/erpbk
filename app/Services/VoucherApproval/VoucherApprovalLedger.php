<?php

namespace App\Services\VoucherApproval;

use App\Models\Vouchers;
use App\Support\VoucherApprovalRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Optional ledger hold helpers.
 *
 * Default (defer_posting=false): approval is supervision-only — create posts
 * transactions immediately; these methods do not hide ledger lines.
 */
class VoucherApprovalLedger
{
    /**
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
        if ($voucher === null || $voucher->isApprovalApproved()) {
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

    /**
     * Undo approval soft-holds for a voucher (safe even when defer_posting is off).
     */
    public static function restoreForVoucher(Vouchers $voucher): void
    {
        if (! Schema::hasColumn('transactions', 'deleted_at')) {
            return;
        }

        if ($voucher->trans_code === null || $voucher->trans_code === '') {
            return;
        }

        $query = DB::table('transactions')->where('trans_code', $voucher->trans_code);

        if (Schema::hasColumn('transactions', 'approval_held_at')) {
            $query->whereNotNull('approval_held_at');
        } elseif (Schema::hasColumn('transactions', 'deleted_by')) {
            $query->whereNull('deleted_by');
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
     * Restore every approval soft-hold so edit forms and ledgers show lines again.
     */
    public static function restoreAllApprovalHolds(): int
    {
        if (! Schema::hasColumn('transactions', 'deleted_at')) {
            return 0;
        }

        $query = DB::table('transactions')->whereNotNull('deleted_at');

        if (Schema::hasColumn('transactions', 'approval_held_at')) {
            $query->whereNotNull('approval_held_at');
        } elseif (Schema::hasColumn('transactions', 'deleted_by')) {
            $query->whereNull('deleted_by');
        }

        $payload = [
            'deleted_at' => null,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('transactions', 'approval_held_at')) {
            $payload['approval_held_at'] = null;
        }

        return $query->update($payload);
    }

    public static function countsTowardBalances(Vouchers $voucher): bool
    {
        if (! VoucherApprovalRegistry::enabled()) {
            return true;
        }

        if (! VoucherApprovalRegistry::deferPosting()) {
            return true;
        }

        return $voucher->isApprovalApproved();
    }
}
