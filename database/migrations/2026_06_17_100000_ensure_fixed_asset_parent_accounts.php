<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        $now = now();

        $this->ensureHeadAccount('Non-Current Assets', 'Asset', '1500');
        $this->ensureHeadAccount('Operating Expenses', 'Expense', '5000');
    }

    public function down(): void
    {
        // Intentionally no-op: shared head accounts may be in use.
    }

    private function ensureHeadAccount(string $name, string $accountType, string $accountCode): void
    {
        $exists = DB::table('accounts')
            ->where('name', $name)
            ->where('account_type', $accountType)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('accounts')->insert([
            'company_id' => null,
            'branch_id' => null,
            'account_code' => $accountCode,
            'name' => $name,
            'account_type' => $accountType,
            'parent_id' => null,
            'ref_name' => null,
            'ref_id' => null,
            'status' => 1,
            'notes' => 'Auto-created for fixed assets module.',
            'opening_balance' => 0,
            'is_locked' => 1,
            'custom_field_values' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
