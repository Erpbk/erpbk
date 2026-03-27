<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $id = 1130;
        $exists = DB::table('accounts')->where('id', $id)->exists();
        if ($exists) {
            return;
        }

        DB::table('accounts')->insert([
            'id' => $id,
            'account_code' => '1130',
            'name' => 'Bike Rental',
            'account_type' => 'Expense',
            'parent_id' => null,
            'ref_name' => null,
            'ref_id' => null,
            'status' => 1,
            'notes' => 'Debit account for leasing company billing invoices.',
            'opening_balance' => 0,
            'is_locked' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('accounts')->where('id', 1130)->delete();
    }
};

