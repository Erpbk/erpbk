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

        DB::table('voucher_types')->where('code', 'PAY')->update([
            'label' => 'Salary Payment',
            'updated_at' => now(),
        ]);

        DB::table('voucher_types')->where('code', 'AL')->update([
            'label' => 'Advance payment',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('voucher_types')) {
            return;
        }

        DB::table('voucher_types')->where('code', 'PAY')->update([
            'label' => 'Payment Voucher',
            'updated_at' => now(),
        ]);

        DB::table('voucher_types')->where('code', 'AL')->update([
            'label' => 'Advance Loan Voucher',
            'updated_at' => now(),
        ]);
    }
};
