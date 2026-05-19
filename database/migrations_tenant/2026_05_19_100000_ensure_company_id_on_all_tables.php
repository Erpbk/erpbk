<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $excludedTables = [
        'companies',
        'company_otp_verifications',
        'subscription_plans',
        'plan_features',
        'company_subscriptions',
        'admin_notifications',
        'countries',
        'migrations',
        'failed_jobs',
        'password_reset_tokens',
        'password_resets',
        'personal_access_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'sessions',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        $database = DB::connection()->getDatabaseName();
        $fallbackCompanyId = $this->resolveFallbackCompanyId($database);

        foreach ($this->tenantTables($database) as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! $this->columnExists($database, $table, 'company_id')) {
                $positionSql = $this->columnExists($database, $table, 'id') ? ' AFTER `id`' : '';
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `company_id` BIGINT UNSIGNED NULL{$positionSql}");
            }

            if (! $this->hasIndex($database, $table, 'company_id')) {
                $indexName = $this->makeName("idx_{$table}_company_id");
                DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`company_id`)");
            }

            $this->normalizeZeroDates($database, $table);

            DB::table($table)->whereNull('company_id')->update(['company_id' => $fallbackCompanyId]);
            $this->normalizeOrphanedCompanyIds($table, $fallbackCompanyId);

            if (! $this->hasCompanyForeignKey($database, $table)) {
                $fkName = $this->makeName("fk_{$table}_company_id_companies_id");
                DB::statement(
                    "ALTER TABLE `{$table}` ADD CONSTRAINT `{$fkName}` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE"
                );
            }
        }
    }

    public function down(): void
    {
        $database = DB::connection()->getDatabaseName();

        foreach ($this->tenantTables($database) as $table) {
            if (! Schema::hasTable($table) || ! $this->columnExists($database, $table, 'company_id')) {
                continue;
            }

            foreach ($this->foreignKeysForCompanyId($database, $table) as $fkName) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
            }

            foreach ($this->indexesForCompanyId($database, $table) as $indexName) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
            }

            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `company_id`");
        }
    }

    /**
     * @return list<string>
     */
    private function tenantTables(string $database): array
    {
        $rows = DB::select(
            "SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = ?
               AND table_type = 'BASE TABLE'
             ORDER BY table_name",
            [$database]
        );

        return collect($rows)
            ->pluck('table_name')
            ->map(fn ($name) => (string) $name)
            ->reject(fn ($name) => in_array($name, $this->excludedTables, true))
            ->values()
            ->all();
    }

    private function columnExists(string $database, string $table, string $column): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS c
             FROM information_schema.columns
             WHERE table_schema = ?
               AND table_name = ?
               AND column_name = ?",
            [$database, $table, $column]
        );

        return ((int) ($row->c ?? 0)) > 0;
    }

    private function hasIndex(string $database, string $table, string $column): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS c
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND column_name = ?",
            [$database, $table, $column]
        );

        return ((int) ($row->c ?? 0)) > 0;
    }

    private function hasCompanyForeignKey(string $database, string $table): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS c
             FROM information_schema.key_column_usage
             WHERE table_schema = ?
               AND table_name = ?
               AND column_name = 'company_id'
               AND referenced_table_name = 'companies'
               AND referenced_column_name = 'id'",
            [$database, $table]
        );

        return ((int) ($row->c ?? 0)) > 0;
    }

    /**
     * @return list<string>
     */
    private function foreignKeysForCompanyId(string $database, string $table): array
    {
        $rows = DB::select(
            "SELECT constraint_name
             FROM information_schema.key_column_usage
             WHERE table_schema = ?
               AND table_name = ?
               AND column_name = 'company_id'
               AND referenced_table_name IS NOT NULL",
            [$database, $table]
        );

        return collect($rows)->pluck('constraint_name')->map(fn ($v) => (string) $v)->values()->all();
    }

    /**
     * @return list<string>
     */
    private function indexesForCompanyId(string $database, string $table): array
    {
        $rows = DB::select(
            "SELECT DISTINCT index_name
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND column_name = 'company_id'
               AND index_name <> 'PRIMARY'",
            [$database, $table]
        );

        return collect($rows)->pluck('index_name')->map(fn ($v) => (string) $v)->values()->all();
    }

    private function makeName(string $base): string
    {
        if (strlen($base) <= 64) {
            return $base;
        }

        return substr($base, 0, 47) . '_' . substr(md5($base), 0, 16);
    }

    private function normalizeOrphanedCompanyIds(string $table, int $fallbackCompanyId): void
    {
        $validCompanyIds = DB::table('companies')->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($validCompanyIds === []) {
            return;
        }

        DB::table($table)
            ->whereNotNull('company_id')
            ->whereNotIn('company_id', $validCompanyIds)
            ->update(['company_id' => $fallbackCompanyId]);
    }

    private function normalizeZeroDates(string $database, string $table): void
    {
        $columns = DB::select(
            "SELECT column_name, data_type, is_nullable
             FROM information_schema.columns
             WHERE table_schema = ?
               AND table_name = ?
               AND data_type IN ('date', 'datetime', 'timestamp')",
            [$database, $table]
        );

        foreach ($columns as $column) {
            $columnName = (string) $column->column_name;
            $dataType = strtolower((string) $column->data_type);
            $isNullable = strtoupper((string) $column->is_nullable) === 'YES';

            if (! $isNullable) {
                continue;
            }

            if ($dataType === 'date') {
                DB::statement(
                    "UPDATE `{$table}` SET `{$columnName}` = NULL WHERE `{$columnName}` = '0000-00-00'"
                );

                continue;
            }

            DB::statement(
                "UPDATE `{$table}` SET `{$columnName}` = NULL WHERE `{$columnName}` IN ('0000-00-00 00:00:00', '0000-00-00')"
            );
        }
    }

    private function resolveFallbackCompanyId(string $database): int
    {
        $existingId = DB::table('companies')->orderBy('id')->value('id');
        if ($existingId !== null) {
            return (int) $existingId;
        }

        $email = 'dummy-company@local.test';
        if (DB::table('companies')->where('email', $email)->exists()) {
            $email = 'dummy-company+' . time() . '@local.test';
        }

        $insert = [
            'name' => 'Dummy Company',
            'email' => $email,
            'country' => 'N/A',
            'phone' => '0000000000',
            'password' => password_hash('dummy123', PASSWORD_BCRYPT),
            'status' => 'approved',
            'database_name' => null,
        ];

        if ($this->columnExists($database, 'companies', 'slug')) {
            $insert['slug'] = 'dummy-company';
        }
        if ($this->columnExists($database, 'companies', 'city')) {
            $insert['city'] = 'N/A';
        }
        if ($this->columnExists($database, 'companies', 'address')) {
            $insert['address'] = 'N/A';
        }
        if ($this->columnExists($database, 'companies', 'is_taxpayer')) {
            $insert['is_taxpayer'] = 0;
        }
        if ($this->columnExists($database, 'companies', 'approved_at')) {
            $insert['approved_at'] = now();
        }
        if ($this->columnExists($database, 'companies', 'created_at')) {
            $insert['created_at'] = now();
        }
        if ($this->columnExists($database, 'companies', 'updated_at')) {
            $insert['updated_at'] = now();
        }

        DB::table('companies')->insert($insert);

        return (int) DB::table('companies')->where('email', $email)->value('id');
    }
};
