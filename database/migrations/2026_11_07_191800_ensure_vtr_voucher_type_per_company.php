<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensure each company has an active VTR (VAT Return) voucher type.
 * Insert-only — does not rename VV rows or backfill existing vouchers.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voucher_types') || ! Schema::hasTable('companies')) {
            return;
        }
        if (! Schema::hasColumn('voucher_types', 'company_id')) {
            return;
        }

        $now = now();
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            $exists = DB::table('voucher_types')
                ->where('code', 'VTR')
                ->where('company_id', $companyId)
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('voucher_types')->insert([
                'code' => 'VTR',
                'label' => 'VAT Return',
                'display_order' => 99,
                'is_active' => 1,
                'company_id' => $companyId,
                'branch_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally empty — do not remove VTR types.
    }
};
