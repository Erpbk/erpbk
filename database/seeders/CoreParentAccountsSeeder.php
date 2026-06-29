<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CoreParentAccountsSeeder extends Seeder
{
    /**
     * Seed shared parent (main) accounts required across the software.
     */
    public function run(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        $now = now();

        $fixedIdHeads = [
            1 => ['account_code' => '0001', 'name' => 'Riders', 'account_type' => 'Liability'],
            994 => ['account_code' => '0994', 'name' => 'Bank', 'account_type' => 'Asset'],
            1005 => ['account_code' => '1005', 'name' => 'Vendor Charges', 'account_type' => 'Expense'],
            1009 => ['account_code' => '1009', 'name' => 'Incentive', 'account_type' => 'Expense'],
            1017 => ['account_code' => '1017', 'name' => 'Penalty', 'account_type' => 'Expense'],
            1023 => ['account_code' => '1023', 'name' => 'Tax Account', 'account_type' => 'Liability'],
            1025 => ['account_code' => '1025', 'name' => 'VAT On Sales', 'account_type' => 'Liability'],
            1103 => ['account_code' => '1103', 'name' => 'Salary Account', 'account_type' => 'Expense'],
            1129 => ['account_code' => '1129', 'name' => 'Leasing Expense', 'account_type' => 'Expense'],
            1135 => ['account_code' => '1135', 'name' => 'Advance Loan', 'account_type' => 'Asset'],
            1200 => ['account_code' => '1200', 'name' => 'Salaries Payable', 'account_type' => 'Liability'],
            1219 => ['account_code' => '1219', 'name' => 'COD Account', 'account_type' => 'Liability'],
            1235 => ['account_code' => '1235', 'name' => 'RTA Fine', 'account_type' => 'Expense'],
            1643 => ['account_code' => '1643', 'name' => 'Cash', 'account_type' => 'Asset'],
            1664 => ['account_code' => '1664', 'name' => 'Garage', 'account_type' => 'Liability'],
        ];

        foreach ($fixedIdHeads as $id => $head) {
            if (DB::table('accounts')->where('id', $id)->exists()) {
                continue;
            }

            DB::table('accounts')->insert([
                'id' => $id,
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

        // Additional shared parent heads used by modules that look up by name.
        foreach ([
            ['name' => 'Supplier', 'account_type' => 'Liability', 'account_code' => '2001'],
            ['name' => 'Recruiter', 'account_type' => 'Liability', 'account_code' => '2002'],
            ['name' => 'Customer', 'account_type' => 'Asset', 'account_code' => '2003'],
            ['name' => 'LeasingCompany', 'account_type' => 'Liability', 'account_code' => '2004'],
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
}

