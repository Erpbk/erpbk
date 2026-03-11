<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates expense_accounts table and seeds it with all existing accounts
     * that belong to the Expense tree (root account_type = 'Expense' and their descendants).
     */
    public function up(): void
    {
        if (!Schema::hasTable('expense_accounts')) {
            Schema::create('expense_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id')->unique();
                $table->timestamps();
            });
        }

        $this->seedExpenseAccounts();
    }

    /**
     * Get all account IDs that belong to the Expense tree:
     * roots with account_type = 'Expense' and all their descendants.
     */
    private function seedExpenseAccounts(): void
    {
        $expenseRootIds = DB::table('accounts')
            ->whereNull('parent_id')
            ->where('account_type', 'Expense')
            ->pluck('id')
            ->all();

        if (empty($expenseRootIds)) {
            return;
        }

        $allExpenseIds = [];
        $toProcess = $expenseRootIds;

        while (!empty($toProcess)) {
            $parentIds = $toProcess;
            $allExpenseIds = array_merge($allExpenseIds, $parentIds);
            $toProcess = DB::table('accounts')
                ->whereIn('parent_id', $parentIds)
                ->pluck('id')
                ->all();
        }

        $allExpenseIds = array_unique($allExpenseIds);
        $existing = DB::table('expense_accounts')->pluck('account_id')->flip()->all();
        $toInsert = array_diff($allExpenseIds, array_keys($existing));
        if (empty($toInsert)) {
            return;
        }
        $now = now();
        $rows = array_map(function ($accountId) use ($now) {
            return [
                'account_id' => $accountId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $toInsert);
        DB::table('expense_accounts')->insert($rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_accounts');
    }
};
