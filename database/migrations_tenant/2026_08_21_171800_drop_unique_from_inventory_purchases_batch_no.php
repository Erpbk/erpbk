<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_purchases')) {
            return;
        }

        $this->dropUniqueIndexIfExists('inventory_purchases', 'inventory_purchases_batch_no_unique');

        if (! $this->indexExists('inventory_purchases', 'inventory_purchases_batch_no_index')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                $table->index('batch_no', 'inventory_purchases_batch_no_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventory_purchases')) {
            return;
        }

        if (! $this->indexExists('inventory_purchases', 'inventory_purchases_batch_no_unique')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                $table->unique('batch_no', 'inventory_purchases_batch_no_unique');
            });
        }
    }

    private function dropUniqueIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropUnique($indexName);
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
