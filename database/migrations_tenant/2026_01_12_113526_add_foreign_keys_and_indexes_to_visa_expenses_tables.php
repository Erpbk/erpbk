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
        // Add indexes and foreign keys to visa_expenses table
        Schema::table('visa_expenses', function (Blueprint $table) {
            // Add indexes for commonly queried columns
            if (!$this->hasIndex('visa_expenses', 'visa_expenses_rider_id_index')) {
                $table->index('rider_id', 'visa_expenses_rider_id_index');
            }
            if (!$this->hasIndex('visa_expenses', 'visa_expenses_trans_code_index')) {
                $table->index('trans_code', 'visa_expenses_trans_code_index');
            }
            if (!$this->hasIndex('visa_expenses', 'visa_expenses_visa_status_index')) {
                $table->index('visa_status', 'visa_expenses_visa_status_index');
            }
            if (!$this->hasIndex('visa_expenses', 'visa_expenses_payment_status_index')) {
                $table->index('payment_status', 'visa_expenses_payment_status_index');
            }
            if (!$this->hasIndex('visa_expenses', 'visa_expenses_billing_month_index')) {
                $table->index('billing_month', 'visa_expenses_billing_month_index');
            }
            if (!$this->hasIndex('visa_expenses', 'visa_expenses_trans_date_index')) {
                $table->index('trans_date', 'visa_expenses_trans_date_index');
            }
        });

        // Add foreign keys to visa_expenses table (only if they don't exist)
        $this->addForeignKeyIfNotExists('visa_expenses', 'rider_id', 'accounts', 'id', 'visa_expenses_rider_id_foreign');

        // Add indexes and foreign keys to visa_installment_plans table
        Schema::table('visa_installment_plans', function (Blueprint $table) {
            // Add indexes for commonly queried columns
            if (!$this->hasIndex('visa_installment_plans', 'visa_installment_plans_rider_id_index')) {
                $table->index('rider_id', 'visa_installment_plans_rider_id_index');
            }
            if (!$this->hasIndex('visa_installment_plans', 'visa_installment_plans_billing_month_index')) {
                $table->index('billing_month', 'visa_installment_plans_billing_month_index');
            }
            if (!$this->hasIndex('visa_installment_plans', 'visa_installment_plans_status_index')) {
                $table->index('status', 'visa_installment_plans_status_index');
            }
            if (!$this->hasIndex('visa_installment_plans', 'visa_installment_plans_date_index')) {
                $table->index('date', 'visa_installment_plans_date_index');
            }
        });

        // Add foreign keys to visa_installment_plans table
        $this->addForeignKeyIfNotExists('visa_installment_plans', 'rider_id', 'accounts', 'id', 'visa_installment_plans_rider_id_foreign');

        // Ensure indexes exist on transactions table for visa expense queries
        // (These may already exist from RTA Fines migration, but we'll check)
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
            // Composite index for visa expense/installment queries
            if (!$this->hasIndex('transactions', 'transactions_reference_type_id_index')) {
                $table->index(['reference_type', 'reference_id'], 'transactions_reference_type_id_index');
            }
        });

        // Ensure indexes exist on vouchers table for visa expense queries
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
            // Composite index for visa expense/installment voucher queries
            if (!$this->hasIndex('vouchers', 'vouchers_ref_id_type_index')) {
                $table->index(['ref_id', 'voucher_type'], 'vouchers_ref_id_type_index');
            }
        });

        // Ensure indexes exist on ledger_entries table
        Schema::table('ledger_entries', function (Blueprint $table) {
            if (!$this->hasIndex('ledger_entries', 'ledger_entries_account_id_index')) {
                $table->index('account_id', 'ledger_entries_account_id_index');
            }
            if (!$this->hasIndex('ledger_entries', 'ledger_entries_billing_month_index')) {
                $table->index('billing_month', 'ledger_entries_billing_month_index');
            }
            // Composite index for account and billing month queries
            if (!$this->hasIndex('ledger_entries', 'ledger_entries_account_billing_index')) {
                $table->index(['account_id', 'billing_month'], 'ledger_entries_account_billing_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys
        $this->dropForeignKeyIfExists('visa_installment_plans', 'visa_installment_plans_rider_id_foreign');
        $this->dropForeignKeyIfExists('visa_expenses', 'visa_expenses_rider_id_foreign');

        // Drop composite indexes from ledger_entries
        Schema::table('ledger_entries', function (Blueprint $table) {
            if ($this->hasIndex('ledger_entries', 'ledger_entries_account_billing_index')) {
                $table->dropIndex('ledger_entries_account_billing_index');
            }
        });

        // Drop composite indexes from vouchers
        Schema::table('vouchers', function (Blueprint $table) {
            if ($this->hasIndex('vouchers', 'vouchers_ref_id_type_index')) {
                $table->dropIndex('vouchers_ref_id_type_index');
            }
        });

        // Drop composite indexes from transactions
        Schema::table('transactions', function (Blueprint $table) {
            if ($this->hasIndex('transactions', 'transactions_reference_type_id_index')) {
                $table->dropIndex('transactions_reference_type_id_index');
            }
        });

        // Drop indexes from visa_installment_plans
        Schema::table('visa_installment_plans', function (Blueprint $table) {
            if ($this->hasIndex('visa_installment_plans', 'visa_installment_plans_date_index')) {
                $table->dropIndex('visa_installment_plans_date_index');
            }
            if ($this->hasIndex('visa_installment_plans', 'visa_installment_plans_status_index')) {
                $table->dropIndex('visa_installment_plans_status_index');
            }
            if ($this->hasIndex('visa_installment_plans', 'visa_installment_plans_billing_month_index')) {
                $table->dropIndex('visa_installment_plans_billing_month_index');
            }
            if ($this->hasIndex('visa_installment_plans', 'visa_installment_plans_rider_id_index')) {
                $table->dropIndex('visa_installment_plans_rider_id_index');
            }
        });

        // Drop indexes from visa_expenses
        Schema::table('visa_expenses', function (Blueprint $table) {
            if ($this->hasIndex('visa_expenses', 'visa_expenses_trans_date_index')) {
                $table->dropIndex('visa_expenses_trans_date_index');
            }
            if ($this->hasIndex('visa_expenses', 'visa_expenses_billing_month_index')) {
                $table->dropIndex('visa_expenses_billing_month_index');
            }
            if ($this->hasIndex('visa_expenses', 'visa_expenses_payment_status_index')) {
                $table->dropIndex('visa_expenses_payment_status_index');
            }
            if ($this->hasIndex('visa_expenses', 'visa_expenses_visa_status_index')) {
                $table->dropIndex('visa_expenses_visa_status_index');
            }
            if ($this->hasIndex('visa_expenses', 'visa_expenses_trans_code_index')) {
                $table->dropIndex('visa_expenses_trans_code_index');
            }
            if ($this->hasIndex('visa_expenses', 'visa_expenses_rider_id_index')) {
                $table->dropIndex('visa_expenses_rider_id_index');
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
