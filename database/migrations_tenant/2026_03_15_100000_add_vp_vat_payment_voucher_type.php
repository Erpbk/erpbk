<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('voucher_types')) {
            return;
        }

        DB::table('voucher_types')->updateOrInsert(
            ['code' => 'VP'],
            [
                'label' => 'VP VAT Payment',
                'display_order' => (int) (DB::table('voucher_types')->max('display_order') ?? 0) + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('voucher_types')->where('code', 'VP')->delete();
    }
};
