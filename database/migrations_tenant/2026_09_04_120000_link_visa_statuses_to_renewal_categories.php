<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('visa_statuses')) {
            return;
        }

        if (! Schema::hasTable('visa_renewal_categories')) {
            Schema::create('visa_renewal_categories', function (Blueprint $table) {
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

        $defaultId = DB::table('visa_renewal_categories')->where('is_default', true)->value('id');
        if (! $defaultId) {
            $insert = [
                'name' => 'New Visa',
                'display_order' => 1,
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('visa_renewal_categories', 'company_id')) {
                $companyId = DB::table('visa_renewal_categories')->value('company_id')
                    ?? DB::table('companies')->value('id');
                if ($companyId) {
                    $insert['company_id'] = $companyId;
                }
            }
            $defaultId = DB::table('visa_renewal_categories')->insertGetId($insert);
        }

        if (! Schema::hasColumn('visa_statuses', 'visa_renewal_category_id')) {
            Schema::table('visa_statuses', function (Blueprint $table) {
                $table->unsignedBigInteger('visa_renewal_category_id')->nullable()->after('id');
                $table->index('visa_renewal_category_id', 'visa_statuses_renewal_category_id_index');
            });
        }

        DB::table('visa_statuses')
            ->whereNull('visa_renewal_category_id')
            ->update(['visa_renewal_category_id' => $defaultId]);

        $this->dropUniqueIndexesOnName();
    }

    public function down(): void
    {
        if (! Schema::hasTable('visa_statuses')) {
            return;
        }

        if (Schema::hasColumn('visa_statuses', 'visa_renewal_category_id')) {
            Schema::table('visa_statuses', function (Blueprint $table) {
                $table->dropIndex('visa_statuses_renewal_category_id_index');
                $table->dropColumn('visa_renewal_category_id');
            });
        }

        if (! $this->indexExists('visa_statuses', 'visa_statuses_name_unique')) {
            Schema::table('visa_statuses', function (Blueprint $table) {
                $table->unique('name', 'visa_statuses_name_unique');
            });
        }
    }

    private function dropUniqueIndexesOnName(): void
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
                [$database, 'visa_statuses', 'name', 'PRIMARY']
            );
            foreach ($rows as $row) {
                $indexName = (string) $row->index_name;
                if ($indexName === '') {
                    continue;
                }
                Schema::table('visa_statuses', function (Blueprint $table) use ($indexName) {
                    $table->dropUnique($indexName);
                });
            }

            return;
        }

        $this->dropUniqueIndexIfExists('visa_statuses_name_unique');
        $this->dropUniqueIndexIfExists('visa_statuses_company_id_name_unique');
    }

    private function dropUniqueIndexIfExists(string $indexName): void
    {
        if (! $this->indexExists('visa_statuses', $indexName)) {
            return;
        }

        Schema::table('visa_statuses', function (Blueprint $table) use ($indexName) {
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
