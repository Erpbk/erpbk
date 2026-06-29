<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voucher_types')) {
            return;
        }

        DB::table('voucher_types')->updateOrInsert(
            ['code' => 'BL'],
            [
                'label' => 'Bank Loan',
                'display_order' => 16,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('voucher_types')) {
            return;
        }

        DB::table('voucher_types')->where('code', 'BL')->delete();
    }
};
