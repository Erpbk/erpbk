<?php

namespace App\Services\VoucherApproval;

use App\Models\Vouchers;
use App\Support\VoucherApprovalRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Holds / restores ledger rows so pending and rejected vouchers do not affect balances.
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

        $row['deleted_at'] = $row['deleted_at'] ?? now();

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

        DB::table('transactions')
            ->where('trans_code', $voucher->trans_code)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
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

        if (Schema::hasColumn('transactions', 'deleted_by')) {
            // Only restore system holds (no user delete), keep user soft-deletes.
            $query->whereNull('deleted_by');
        }

        $query->whereNotNull('deleted_at')->update([
            'deleted_at' => null,
            'updated_at' => now(),
        ]);
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
