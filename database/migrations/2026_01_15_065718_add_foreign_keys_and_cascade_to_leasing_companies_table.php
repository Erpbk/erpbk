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
        // Add foreign key for leasing_companies.account_id -> accounts.id
        $this->addForeignKeyIfNotExists(
            'leasing_companies',
            'account_id',
            'accounts',
            'id',
            'leasing_companies_account_id_foreign'
        );

        // Add foreign key for bikes.company -> leasing_companies.id (RESTRICT on delete)
        // Note: Application layer prevents deletion if bikes exist, but RESTRICT provides database-level protection
        $this->addForeignKeyIfNotExists(
            'bikes',
            'company',
            'leasing_companies',
            'id',
            'bikes_company_leasing_companies_foreign',
            'RESTRICT'
        );

        // Add foreign key for vouchers.lease_company -> leasing_companies.id (RESTRICT on delete)
        // Note: Application layer prevents deletion if vouchers exist, but RESTRICT provides database-level protection
        $this->addForeignKeyIfNotExists(
            'vouchers',
            'lease_company',
            'leasing_companies',
            'id',
            'vouchers_lease_company_leasing_companies_foreign',
            'RESTRICT'
        );

        // Add indexes for better query performance
        Schema::table('leasing_companies', function (Blueprint $table) {
            if (!$this->hasIndex('leasing_companies', 'leasing_companies_account_id_index')) {
                $table->index('account_id', 'leasing_companies_account_id_index');
            }
            if (!$this->hasIndex('leasing_companies', 'leasing_companies_status_index')) {
                $table->index('status', 'leasing_companies_status_index');
            }
        });

        Schema::table('bikes', function (Blueprint $table) {
            if (!$this->hasIndex('bikes', 'bikes_company_index')) {
                $table->index('company', 'bikes_company_index');
            }
        });

        Schema::table('vouchers', function (Blueprint $table) {
            if (!$this->hasIndex('vouchers', 'vouchers_lease_company_index')) {
                $table->index('lease_company', 'vouchers_lease_company_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        Schema::table('vouchers', function (Blueprint $table) {
            if ($this->hasIndex('vouchers', 'vouchers_lease_company_index')) {
                $table->dropIndex('vouchers_lease_company_index');
            }
        });

        Schema::table('bikes', function (Blueprint $table) {
            if ($this->hasIndex('bikes', 'bikes_company_index')) {
                $table->dropIndex('bikes_company_index');
            }
        });

        Schema::table('leasing_companies', function (Blueprint $table) {
            if ($this->hasIndex('leasing_companies', 'leasing_companies_status_index')) {
                $table->dropIndex('leasing_companies_status_index');
            }
            if ($this->hasIndex('leasing_companies', 'leasing_companies_account_id_index')) {
                $table->dropIndex('leasing_companies_account_id_index');
            }
        });

        // Drop foreign keys
        $this->dropForeignKeyIfExists('vouchers', 'vouchers_lease_company_leasing_companies_foreign');
        $this->dropForeignKeyIfExists('bikes', 'bikes_company_leasing_companies_foreign');
        $this->dropForeignKeyIfExists('leasing_companies', 'leasing_companies_account_id_foreign');
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
    private function addForeignKeyIfNotExists(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $constraintName,
        string $onDelete = 'RESTRICT'
    ): void {
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
                    Schema::table($table, function (Blueprint $table) use ($column, $referencedTable, $referencedColumn, $constraintName, $onDelete) {
                        $foreignKey = $table->foreign($column, $constraintName)
                            ->references($referencedColumn)
                            ->on($referencedTable)
                            ->onUpdate('cascade');

                        // Apply the onDelete action
                        if ($onDelete === 'SET NULL') {
                            $foreignKey->onDelete('set null');
                        } elseif ($onDelete === 'CASCADE') {
                            $foreignKey->onDelete('cascade');
                        } else {
                            $foreignKey->onDelete('restrict');
                        }
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
