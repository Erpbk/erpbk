<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sub-account under Current Liabilities (same parent as "Sims (Company)") for Bike on Rent customer AP balances.
     */
    public function up(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        $parentId = 1644;
        if (!DB::table('accounts')->where('id', $parentId)->exists()) {
            return;
        }

        $exists = DB::table('accounts')
            ->where('parent_id', $parentId)
            ->where('name', 'Bike on Rent (Company)')
            ->where('account_type', 'Liability')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('accounts')->insert([
            'account_code' => 'BOR-COMP',
            'name' => 'Bike on Rent (Company)',
            'account_type' => 'Liability',
            'parent_id' => $parentId,
            'ref_name' => null,
            'ref_id' => null,
            'status' => 1,
            'notes' => 'Parent head for Bike on Rent customer (vendor) liability accounts.',
            'opening_balance' => 0,
            'is_locked' => 0,
            'company_id' => null,
            'branch_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        DB::table('accounts')
            ->where('parent_id', 1644)
            ->where('name', 'Bike on Rent (Company)')
            ->where('account_code', 'BOR-COMP')
            ->delete();
    }
};
