<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expense_accounts')) {
            return;
        }

        $this->dropUniqueIndexIfExists('expense_accounts', 'expense_accounts_account_id_unique');

        if (Schema::hasColumn('expense_accounts', 'rider_id')
            && Schema::hasColumn('expense_accounts', 'renewal_category_id')
            && !$this->indexExists('expense_accounts', 'expense_accounts_rider_renewal_category_unique')) {
            Schema::table('expense_accounts', function (Blueprint $table) {
                $table->unique(
                    ['rider_id', 'renewal_category_id'],
                    'expense_accounts_rider_renewal_category_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('expense_accounts')) {
            return;
        }

        if ($this->indexExists('expense_accounts', 'expense_accounts_rider_renewal_category_unique')) {
            Schema::table('expense_accounts', function (Blueprint $table) {
                $table->dropUnique('expense_accounts_rider_renewal_category_unique');
            });
        }

        if (!$this->indexExists('expense_accounts', 'expense_accounts_account_id_unique')) {
            Schema::table('expense_accounts', function (Blueprint $table) {
                $table->unique('account_id', 'expense_accounts_account_id_unique');
            });
        }
    }

    private function dropUniqueIndexIfExists(string $table, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName) {
            $table->dropUnique($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $row = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$database, $table, $indexName]
        );

        return $row !== null;
    }
};
