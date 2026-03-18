<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ensures account 1129 (Leasing Expense) exists so leasing company invoice
     * debit transactions can be recorded. If your chart of accounts uses different
     * IDs, create this account manually and set HeadAccount::LEASING_EXPENSE_ACCOUNT
     * in app/Helpers/HeadAccount.php to match.
     */
    public function up(): void
    {
        $id = 1129;
        $exists = DB::table('accounts')->where('id', $id)->exists();
        if ($exists) {
            return;
        }

        DB::table('accounts')->insert([
            'id' => $id,
            'account_code' => '1129',
            'name' => 'Leasing Expense',
            'account_type' => 'Expense',
            'parent_id' => null,
            'ref_name' => null,
            'ref_id' => null,
            'status' => 1,
            'notes' => 'Debit account for leasing company invoice expenses.',
            'opening_balance' => 0,
            'is_locked' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('accounts')->where('id', 1129)->delete();
    }
};
