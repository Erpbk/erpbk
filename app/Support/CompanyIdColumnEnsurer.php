<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotently adds company_id (+ index, backfill, optional FK) to every tenant table.
 * Safe to run multiple times (e.g. after failed migrations or new tables).
 */
final class CompanyIdColumnEnsurer
{
    /**
     * Central / system tables that must not be tenant-scoped.
     *
     * @var list<string>
     */
    private const EXCLUDED_TABLES = [
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

    /**
     * @return array{
     *     processed: int,
     *     column_added: list<string>,
     *     backfilled: list<string>,
     *     fk_added: list<string>,
     *     skipped: list<string>,
     *     errors: list<array{table: string, step: string, message: string}>
     * }
     */
    public function run(?string $connection = null, bool $dryRun = false): array
    {
        $connection = $connection ?: config('database.default');
        $schema = Schema::connection($connection);

        if (! $schema->hasTable('companies')) {
            return [
                'processed' => 0,
                'column_added' => [],
                'backfilled' => [],
                'fk_added' => [],
                'skipped' => ['companies table missing'],
                'errors' => [],
            ];
        }

        $database = DB::connection($connection)->getDatabaseName();
        $fallbackCompanyId = $this->resolveFallbackCompanyId($connection, $database, $dryRun);
        $validCompanyIds = $this->validCompanyIds($connection);

        $report = [
            'processed' => 0,
            'column_added' => [],
            'backfilled' => [],
            'fk_added' => [],
            'skipped' => [],
            'errors' => [],
        ];

        foreach ($this->tenantTables($connection, $database) as $table) {
            if (! $schema->hasTable($table)) {
                continue;
            }

            ++$report['processed'];

            try {
                if (! $this->columnExists($connection, $database, $table, 'company_id')) {
                    if (! $dryRun) {
                        $positionSql = $this->columnExists($connection, $database, $table, 'id') ? ' AFTER `id`' : '';
                        DB::connection($connection)->statement(
                            "ALTER TABLE `{$table}` ADD COLUMN `company_id` BIGINT UNSIGNED NULL{$positionSql}"
                        );
                    }
                    $report['column_added'][] = $table;
                }
            } catch (\Throwable $e) {
                $this->recordError($report, $table, 'add_column', $e);
                continue;
            }

            try {
                if (! $dryRun && ! $this->hasIndex($connection, $database, $table, 'company_id')) {
                    $indexName = $this->makeName("idx_{$table}_company_id");
                    DB::connection($connection)->statement(
                        "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`company_id`)"
                    );
                }
            } catch (\Throwable $e) {
                $this->recordError($report, $table, 'add_index', $e);
            }

            try {
                if (! $dryRun) {
                    $this->normalizeZeroDates($connection, $database, $table);
                }
            } catch (\Throwable $e) {
                $this->recordError($report, $table, 'normalize_dates', $e);
            }

            try {
                if (! $dryRun && $fallbackCompanyId > 0) {
                    $updated = DB::connection($connection)
                        ->table($table)
                        ->whereNull('company_id')
                        ->update(['company_id' => $fallbackCompanyId]);
                    if ($updated > 0) {
                        $report['backfilled'][] = $table . " ({$updated})";
                    }

                    $this->normalizeOrphanedCompanyIds($connection, $table, $fallbackCompanyId, $validCompanyIds);
                }
            } catch (\Throwable $e) {
                $this->recordError($report, $table, 'backfill', $e);
            }

            try {
                if (! $dryRun && ! $this->hasCompanyForeignKey($connection, $database, $table)) {
                    $fkName = $this->makeName("fk_{$table}_company_id_companies_id");
                    DB::connection($connection)->statement(
                        "ALTER TABLE `{$table}` ADD CONSTRAINT `{$fkName}` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE"
                    );
                    $report['fk_added'][] = $table;
                }
            } catch (\Throwable $e) {
                $this->recordError($report, $table, 'add_foreign_key', $e);
            }
        }

        return $report;
    }

    /**
     * Tables that exist but still lack company_id.
     *
     * @return list<string>
     */
    public function tablesMissingCompanyId(?string $connection = null): array
    {
        $connection = $connection ?: config('database.default');
        $database = DB::connection($connection)->getDatabaseName();
        $missing = [];

        foreach ($this->tenantTables($connection, $database) as $table) {
            if (! Schema::connection($connection)->hasTable($table)) {
                continue;
            }
            if (! $this->columnExists($connection, $database, $table, 'company_id')) {
                $missing[] = $table;
            }
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    private function tenantTables(string $connection, string $database): array
    {
        $rows = DB::connection($connection)->select(
            "SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = ?
               AND table_type = 'BASE TABLE'
             ORDER BY table_name",
            [$database]
        );

        return Collection::make($rows)
            ->pluck('table_name')
            ->map(fn ($name) => (string) $name)
            ->reject(fn ($name) => in_array($name, self::EXCLUDED_TABLES, true))
            ->values()
            ->all();
    }

    private function columnExists(string $connection, string $database, string $table, string $column): bool
    {
        $row = DB::connection($connection)->selectOne(
            "SELECT COUNT(*) AS c
             FROM information_schema.columns
             WHERE table_schema = ?
               AND table_name = ?
               AND column_name = ?",
            [$database, $table, $column]
        );

        return ((int) ($row->c ?? 0)) > 0;
    }

    private function hasIndex(string $connection, string $database, string $table, string $column): bool
    {
        $row = DB::connection($connection)->selectOne(
            "SELECT COUNT(*) AS c
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND column_name = ?",
            [$database, $table, $column]
        );

        return ((int) ($row->c ?? 0)) > 0;
    }

    private function hasCompanyForeignKey(string $connection, string $database, string $table): bool
    {
        $row = DB::connection($connection)->selectOne(
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

    private function makeName(string $base): string
    {
        if (strlen($base) <= 64) {
            return $base;
        }

        return substr($base, 0, 47) . '_' . substr(md5($base), 0, 16);
    }

    /**
     * @return list<int>
     */
    private function validCompanyIds(string $connection): array
    {
        return DB::connection($connection)
            ->table('companies')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $validCompanyIds
     */
    private function normalizeOrphanedCompanyIds(
        string $connection,
        string $table,
        int $fallbackCompanyId,
        array $validCompanyIds
    ): void {
        if ($validCompanyIds === []) {
            return;
        }

        DB::connection($connection)
            ->table($table)
            ->whereNotNull('company_id')
            ->whereNotIn('company_id', $validCompanyIds)
            ->update(['company_id' => $fallbackCompanyId]);
    }

    private function normalizeZeroDates(string $connection, string $database, string $table): void
    {
        $columns = DB::connection($connection)->select(
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
                DB::connection($connection)->statement(
                    "UPDATE `{$table}` SET `{$columnName}` = NULL WHERE `{$columnName}` = '0000-00-00'"
                );

                continue;
            }

            DB::connection($connection)->statement(
                "UPDATE `{$table}` SET `{$columnName}` = NULL WHERE `{$columnName}` IN ('0000-00-00 00:00:00', '0000-00-00')"
            );
        }
    }

    private function resolveFallbackCompanyId(string $connection, string $database, bool $dryRun): int
    {
        $existingId = DB::connection($connection)->table('companies')->orderBy('id')->value('id');
        if ($existingId !== null) {
            return (int) $existingId;
        }

        if ($dryRun) {
            return 0;
        }

        $email = 'dummy-company@local.test';
        if (DB::connection($connection)->table('companies')->where('email', $email)->exists()) {
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

        if ($this->columnExists($connection, $database, 'companies', 'slug')) {
            $insert['slug'] = 'dummy-company-' . time();
        }
        if ($this->columnExists($connection, $database, 'companies', 'city')) {
            $insert['city'] = 'N/A';
        }
        if ($this->columnExists($connection, $database, 'companies', 'address')) {
            $insert['address'] = 'N/A';
        }
        if ($this->columnExists($connection, $database, 'companies', 'is_taxpayer')) {
            $insert['is_taxpayer'] = 0;
        }
        if ($this->columnExists($connection, $database, 'companies', 'approved_at')) {
            $insert['approved_at'] = now();
        }
        if ($this->columnExists($connection, $database, 'companies', 'created_at')) {
            $insert['created_at'] = now();
        }
        if ($this->columnExists($connection, $database, 'companies', 'updated_at')) {
            $insert['updated_at'] = now();
        }

        DB::connection($connection)->table('companies')->insert($insert);

        return (int) DB::connection($connection)->table('companies')->where('email', $email)->value('id');
    }

    /**
     * @param  array{errors: list<array{table: string, step: string, message: string}>}  $report
     */
    private function recordError(array &$report, string $table, string $step, \Throwable $e): void
    {
        $message = $e->getMessage();
        $report['errors'][] = [
            'table' => $table,
            'step' => $step,
            'message' => $message,
        ];
        Log::warning('CompanyIdColumnEnsurer: skipped step', [
            'table' => $table,
            'step' => $step,
            'error' => $message,
        ]);
    }
}
