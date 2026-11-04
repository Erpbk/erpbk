<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Distinguish voucher-approval ledger holds from ordinary soft-deletes.
 * restoreForVoucher must ONLY clear rows with approval_held_at set.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        if (! Schema::hasColumn('transactions', 'approval_held_at')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->timestamp('approval_held_at')->nullable()->after('deleted_by')->index();
            });
        }

        // Backfill: pending/rejected voucher lines that are soft-deleted without deleted_by
        // are treated as approval holds (legacy behaviour before this column existed).
        if (Schema::hasTable('vouchers') && Schema::hasColumn('vouchers', 'approval_status')) {
            DB::statement("
                UPDATE transactions t
                INNER JOIN vouchers v ON v.trans_code = t.trans_code
                SET t.approval_held_at = COALESCE(t.approval_held_at, t.deleted_at)
                WHERE t.deleted_at IS NOT NULL
                  AND t.approval_held_at IS NULL
                  AND (t.deleted_by IS NULL)
                  AND v.approval_status IN ('pending_approval', 'rejected')
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'approval_held_at')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('approval_held_at');
            });
        }
    }
};
