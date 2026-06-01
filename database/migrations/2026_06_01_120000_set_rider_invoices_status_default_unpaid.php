<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_invoices') || ! Schema::hasColumn('rider_invoices', 'status')) {
            return;
        }

        // Legacy rows: NULL/empty meant unpaid in the UI but were excluded from SQL `status != 1`.
        DB::table('rider_invoices')
            ->whereNull('status')
            ->update(['status' => 0]);

        DB::table('rider_invoices')
            ->where('status', '')
            ->update(['status' => 0]);

        DB::statement(<<<'SQL'
ALTER TABLE `rider_invoices`
  MODIFY `status` TINYINT UNSIGNED NOT NULL DEFAULT 0
  COMMENT '0=Unpaid, 1=Paid, 3=Partially Paid'
SQL);
    }

    public function down(): void
    {
        if (! Schema::hasTable('rider_invoices') || ! Schema::hasColumn('rider_invoices', 'status')) {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE `rider_invoices`
  MODIFY `status` VARCHAR(255) NULL DEFAULT NULL
SQL);
    }
};
