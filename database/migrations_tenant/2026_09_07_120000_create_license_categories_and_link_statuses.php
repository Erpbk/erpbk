<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('license_categories')) {
            Schema::create('license_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedInteger('display_order')->default(1)->index();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        $defaultId = DB::table('license_categories')->where('is_default', true)->value('id');
        if (! $defaultId) {
            $defaultId = DB::table('license_categories')->insertGetId([
                'name' => 'New License',
                'display_order' => 1,
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('license_statuses')) {
            if (! Schema::hasColumn('license_statuses', 'license_category_id')) {
                Schema::table('license_statuses', function (Blueprint $table) {
                    $table->unsignedBigInteger('license_category_id')->nullable()->after('id');
                    $table->index('license_category_id', 'license_statuses_license_category_id_index');
                });
            }

            DB::table('license_statuses')
                ->whereNull('license_category_id')
                ->update(['license_category_id' => $defaultId]);

            $this->dropUniqueIndexesOnName('license_statuses');
        }

        if (Schema::hasTable('license_expenses') && ! Schema::hasColumn('license_expenses', 'license_category_id')) {
            Schema::table('license_expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('license_category_id')->nullable()->after('expense_account_id');
                $table->index('license_category_id', 'license_expenses_license_category_id_index');
            });

            DB::table('license_expenses')
                ->whereNull('license_category_id')
                ->update(['license_category_id' => $defaultId]);
        }

        if (Schema::hasTable('expense_accounts') && ! Schema::hasColumn('expense_accounts', 'license_category_id')) {
            Schema::table('expense_accounts', function (Blueprint $table) {
                $table->unsignedBigInteger('license_category_id')->nullable()->after('renewal_category_id');
                $table->index('license_category_id', 'expense_accounts_license_category_id_index');
            });

            if (Schema::hasColumn('expense_accounts', 'module')) {
                DB::table('expense_accounts')
                    ->where('module', 'license')
                    ->whereNull('license_category_id')
                    ->update(['license_category_id' => $defaultId]);
            }

            if (Schema::hasColumn('expense_accounts', 'rider_id')
                && ! $this->indexExists('expense_accounts', 'expense_accounts_rider_license_category_unique')) {
                Schema::table('expense_accounts', function (Blueprint $table) {
                    $table->unique(
                        ['rider_id', 'license_category_id'],
                        'expense_accounts_rider_license_category_unique'
                    );
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expense_accounts')) {
            if ($this->indexExists('expense_accounts', 'expense_accounts_rider_license_category_unique')) {
                Schema::table('expense_accounts', function (Blueprint $table) {
                    $table->dropUnique('expense_accounts_rider_license_category_unique');
                });
            }
            if (Schema::hasColumn('expense_accounts', 'license_category_id')) {
                Schema::table('expense_accounts', function (Blueprint $table) {
                    $table->dropIndex('expense_accounts_license_category_id_index');
                    $table->dropColumn('license_category_id');
                });
            }
        }

        if (Schema::hasTable('license_expenses') && Schema::hasColumn('license_expenses', 'license_category_id')) {
            Schema::table('license_expenses', function (Blueprint $table) {
                $table->dropIndex('license_expenses_license_category_id_index');
                $table->dropColumn('license_category_id');
            });
        }

        if (Schema::hasTable('license_statuses') && Schema::hasColumn('license_statuses', 'license_category_id')) {
            Schema::table('license_statuses', function (Blueprint $table) {
                $table->dropIndex('license_statuses_license_category_id_index');
                $table->dropColumn('license_category_id');
            });

            if (! $this->indexExists('license_statuses', 'license_statuses_name_unique')) {
                Schema::table('license_statuses', function (Blueprint $table) {
                    $table->unique('name', 'license_statuses_name_unique');
                });
            }
        }

        Schema::dropIfExists('license_categories');
    }

    private function dropUniqueIndexesOnName(string $table): void
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $rows = DB::select(
                'SELECT DISTINCT INDEX_NAME AS index_name
                 FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = ? AND column_name = ? AND non_unique = 0
                   AND INDEX_NAME <> ?',
                [$database, $table, 'name', 'PRIMARY']
            );
            foreach ($rows as $row) {
                $indexName = (string) $row->index_name;
                if ($indexName === '') {
                    continue;
                }
                Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                    $blueprint->dropUnique($indexName);
                });
            }

            return;
        }

        foreach (['license_statuses_name_unique', 'license_statuses_company_id_name_unique'] as $indexName) {
            if ($this->indexExists($table, $indexName)) {
                Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                    $blueprint->dropUnique($indexName);
                });
            }
        }
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
