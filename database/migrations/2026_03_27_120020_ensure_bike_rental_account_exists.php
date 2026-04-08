<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fresh environments may not have accounts table in this migration path.
        if (!Schema::hasTable('accounts')) {
            return;
        }

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
        if (!Schema::hasTable('accounts')) {
            return;
        }

        DB::table('accounts')->where('id', 1130)->delete();
    }
};

