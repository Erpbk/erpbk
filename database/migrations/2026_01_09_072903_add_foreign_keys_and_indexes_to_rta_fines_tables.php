<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes and foreign keys to rta_fines table
        Schema::table('rta_fines', function (Blueprint $table) {
            // Add indexes for commonly queried columns
            if (!$this->hasIndex('rta_fines', 'rta_fines_rta_account_id_index')) {
                $table->index('rta_account_id', 'rta_fines_rta_account_id_index');
            }
            if (!$this->hasIndex('rta_fines', 'rta_fines_rider_id_index')) {
                $table->index('rider_id', 'rta_fines_rider_id_index');
            }
            if (!$this->hasIndex('rta_fines', 'rta_fines_bike_id_index')) {
                $table->index('bike_id', 'rta_fines_bike_id_index');
            }
            if (!$this->hasIndex('rta_fines', 'rta_fines_status_index')) {
                $table->index('status', 'rta_fines_status_index');
            }
            if (!$this->hasIndex('rta_fines', 'rta_fines_trans_code_index')) {
                $table->index('trans_code', 'rta_fines_trans_code_index');
            }
            if (!$this->hasIndex('rta_fines', 'rta_fines_ticket_no_index')) {
                $table->index('ticket_no', 'rta_fines_ticket_no_index');
            }
        });

        // Add foreign keys to rta_fines table (only if they don't exist)
        // Note: Foreign keys are added separately to avoid issues with existing data
        $this->addForeignKeyIfNotExists('rta_fines', 'rta_account_id', 'accounts', 'id', 'rta_fines_rta_account_id_foreign');
        $this->addForeignKeyIfNotExists('rta_fines', 'rider_id', 'riders', 'id', 'rta_fines_rider_id_foreign');
        $this->addForeignKeyIfNotExists('rta_fines', 'bike_id', 'bikes', 'id', 'rta_fines_bike_id_foreign');

        // Add indexes to transactions table for RTA fines queries
        Schema::table('transactions', function (Blueprint $table) {
            if (!$this->hasIndex('transactions', 'transactions_reference_id_index')) {
                $table->index('reference_id', 'transactions_reference_id_index');
            }
            if (!$this->hasIndex('transactions', 'transactions_reference_type_index')) {
                $table->index('reference_type', 'transactions_reference_type_index');
            }
            if (!$this->hasIndex('transactions', 'transactions_account_id_index')) {
                $table->index('account_id', 'transactions_account_id_index');
            }
            if (!$this->hasIndex('transactions', 'transactions_trans_code_index')) {
                $table->index('trans_code', 'transactions_trans_code_index');
            }
        });

        // Add foreign key to transactions table for account_id
        $this->addForeignKeyIfNotExists('transactions', 'account_id', 'accounts', 'id', 'transactions_account_id_foreign');

        // Add indexes to vouchers table for RTA fines queries
        Schema::table('vouchers', function (Blueprint $table) {
            if (!$this->hasIndex('vouchers', 'vouchers_ref_id_index')) {
                $table->index('ref_id', 'vouchers_ref_id_index');
            }
            if (!$this->hasIndex('vouchers', 'vouchers_voucher_type_index')) {
                $table->index('voucher_type', 'vouchers_voucher_type_index');
            }
            if (!$this->hasIndex('vouchers', 'vouchers_trans_code_index')) {
                $table->index('trans_code', 'vouchers_trans_code_index');
            }
        });

        // Add indexes to ledger_entries table
        Schema::table('ledger_entries', function (Blueprint $table) {
            if (!$this->hasIndex('ledger_entries', 'ledger_entries_account_id_index')) {
                $table->index('account_id', 'ledger_entries_account_id_index');
            }
            if (!$this->hasIndex('ledger_entries', 'ledger_entries_billing_month_index')) {
                $table->index('billing_month', 'ledger_entries_billing_month_index');
            }
        });

        // Add foreign key to ledger_entries table for account_id
        $this->addForeignKeyIfNotExists('ledger_entries', 'account_id', 'accounts', 'id', 'ledger_entries_account_id_foreign');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys
        $this->dropForeignKeyIfExists('ledger_entries', 'ledger_entries_account_id_foreign');
        $this->dropForeignKeyIfExists('transactions', 'transactions_account_id_foreign');
        $this->dropForeignKeyIfExists('rta_fines', 'rta_fines_bike_id_foreign');
        $this->dropForeignKeyIfExists('rta_fines', 'rta_fines_rider_id_foreign');
        $this->dropForeignKeyIfExists('rta_fines', 'rta_fines_rta_account_id_foreign');

        // Drop indexes from ledger_entries
        Schema::table('ledger_entries', function (Blueprint $table) {
            if ($this->hasIndex('ledger_entries', 'ledger_entries_billing_month_index')) {
                $table->dropIndex('ledger_entries_billing_month_index');
            }
            if ($this->hasIndex('ledger_entries', 'ledger_entries_account_id_index')) {
                $table->dropIndex('ledger_entries_account_id_index');
            }
        });

        // Drop indexes from vouchers
        Schema::table('vouchers', function (Blueprint $table) {
            if ($this->hasIndex('vouchers', 'vouchers_trans_code_index')) {
                $table->dropIndex('vouchers_trans_code_index');
            }
            if ($this->hasIndex('vouchers', 'vouchers_voucher_type_index')) {
                $table->dropIndex('vouchers_voucher_type_index');
            }
            if ($this->hasIndex('vouchers', 'vouchers_ref_id_index')) {
                $table->dropIndex('vouchers_ref_id_index');
            }
        });

        // Drop indexes from transactions
        Schema::table('transactions', function (Blueprint $table) {
            if ($this->hasIndex('transactions', 'transactions_trans_code_index')) {
                $table->dropIndex('transactions_trans_code_index');
            }
            if ($this->hasIndex('transactions', 'transactions_account_id_index')) {
                $table->dropIndex('transactions_account_id_index');
            }
            if ($this->hasIndex('transactions', 'transactions_reference_type_index')) {
                $table->dropIndex('transactions_reference_type_index');
            }
            if ($this->hasIndex('transactions', 'transactions_reference_id_index')) {
                $table->dropIndex('transactions_reference_id_index');
            }
        });

        // Drop indexes from rta_fines
        Schema::table('rta_fines', function (Blueprint $table) {
            if ($this->hasIndex('rta_fines', 'rta_fines_ticket_no_index')) {
                $table->dropIndex('rta_fines_ticket_no_index');
            }
            if ($this->hasIndex('rta_fines', 'rta_fines_trans_code_index')) {
                $table->dropIndex('rta_fines_trans_code_index');
            }
            if ($this->hasIndex('rta_fines', 'rta_fines_status_index')) {
                $table->dropIndex('rta_fines_status_index');
            }
            if ($this->hasIndex('rta_fines', 'rta_fines_bike_id_index')) {
                $table->dropIndex('rta_fines_bike_id_index');
            }
            if ($this->hasIndex('rta_fines', 'rta_fines_rider_id_index')) {
                $table->dropIndex('rta_fines_rider_id_index');
            }
            if ($this->hasIndex('rta_fines', 'rta_fines_rta_account_id_index')) {
                $table->dropIndex('rta_fines_rta_account_id_index');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$database, $table, $indexName]
        );

        return $result[0]->count > 0;
    }

    /**
     * Add foreign key constraint if it doesn't exist
     */
    private function addForeignKeyIfNotExists(string $table, string $column, string $referencedTable, string $referencedColumn, string $constraintName): void
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        // Check if foreign key already exists
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.table_constraints 
             WHERE constraint_schema = ? 
             AND table_name = ? 
             AND constraint_name = ? 
             AND constraint_type = 'FOREIGN KEY'",
            [$database, $table, $constraintName]
        );

        if ($result[0]->count == 0) {
            // Check if the column is nullable
            $columnInfo = DB::select(
                "SELECT IS_NULLABLE, COLUMN_TYPE, COLUMN_NAME
                 FROM information_schema.COLUMNS 
                 WHERE TABLE_SCHEMA = ? 
                 AND TABLE_NAME = ? 
                 AND COLUMN_NAME = ?",
                [$database, $table, $column]
            );

            // Check if referenced table and column exist
            $refTableInfo = DB::select(
                "SELECT COUNT(*) as count 
                 FROM information_schema.TABLES 
                 WHERE TABLE_SCHEMA = ? 
                 AND TABLE_NAME = ?",
                [$database, $referencedTable]
            );

            $refColumnInfo = DB::select(
                "SELECT COLUMN_TYPE, COLUMN_NAME
                 FROM information_schema.COLUMNS 
                 WHERE TABLE_SCHEMA = ? 
                 AND TABLE_NAME = ? 
                 AND COLUMN_NAME = ?",
                [$database, $referencedTable, $referencedColumn]
            );

            // Only add foreign key if both table and column exist
            if ($refTableInfo[0]->count > 0 && count($refColumnInfo) > 0) {
                // Check for orphaned records (non-null values that don't exist in referenced table)
                if (!empty($columnInfo) && $columnInfo[0]->IS_NULLABLE === 'YES') {
                    // For nullable columns, check for orphaned non-null values
                    $orphanedCount = DB::table($table)
                        ->leftJoin($referencedTable, "{$table}.{$column}", '=', "{$referencedTable}.{$referencedColumn}")
                        ->whereNotNull("{$table}.{$column}")
                        ->whereNull("{$referencedTable}.{$referencedColumn}")
                        ->count();

                    if ($orphanedCount > 0) {
                        \Log::warning("Skipping foreign key {$constraintName}: Found {$orphanedCount} orphaned records in {$table}.{$column}");
                        return;
                    }
                } else {
                    // For non-nullable columns, check for any orphaned records
                    $orphanedCount = DB::table($table)
                        ->leftJoin($referencedTable, "{$table}.{$column}", '=', "{$referencedTable}.{$referencedColumn}")
                        ->whereNull("{$referencedTable}.{$referencedColumn}")
                        ->count();

                    if ($orphanedCount > 0) {
                        \Log::warning("Skipping foreign key {$constraintName}: Found {$orphanedCount} orphaned records in {$table}.{$column}");
                        return;
                    }
                }

                try {
                    Schema::table($table, function (Blueprint $table) use ($column, $referencedTable, $referencedColumn, $constraintName) {
                        $table->foreign($column, $constraintName)
                            ->references($referencedColumn)
                            ->on($referencedTable)
                            ->onDelete('restrict')
                            ->onUpdate('cascade');
                    });
                } catch (\Exception $e) {
                    \Log::warning("Failed to add foreign key {$constraintName}: " . $e->getMessage());
                    // Continue with migration even if foreign key fails
                }
            }
        }
    }

    /**
     * Drop foreign key constraint if it exists
     */
    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.table_constraints 
             WHERE constraint_schema = ? 
             AND table_name = ? 
             AND constraint_name = ? 
             AND constraint_type = 'FOREIGN KEY'",
            [$database, $table, $constraintName]
        );

        if ($result[0]->count > 0) {
            Schema::table($table, function (Blueprint $table) use ($constraintName) {
                $table->dropForeign($constraintName);
            });
        }
    }
};
