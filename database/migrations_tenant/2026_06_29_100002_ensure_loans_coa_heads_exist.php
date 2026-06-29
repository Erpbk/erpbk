<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        $now = now();

        foreach ([
            ['name' => 'Loans Payable', 'account_type' => 'Liability', 'account_code' => '2010'],
            ['name' => 'Loan Interest Expense', 'account_type' => 'Expense', 'account_code' => '1140'],
        ] as $head) {
            $exists = DB::table('accounts')
                ->whereNull('parent_id')
                ->where('name', $head['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('accounts')->insert([
                'company_id' => null,
                'branch_id' => null,
                'account_code' => $head['account_code'],
                'name' => $head['name'],
                'account_type' => $head['account_type'],
                'parent_id' => null,
                'ref_name' => null,
                'ref_id' => null,
                'status' => 1,
                'notes' => null,
                'opening_balance' => 0,
                'is_locked' => 1,
                'custom_field_values' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        DB::table('accounts')
            ->whereNull('parent_id')
            ->whereIn('name', ['Loans Payable', 'Loan Interest Expense'])
            ->delete();
    }
};
