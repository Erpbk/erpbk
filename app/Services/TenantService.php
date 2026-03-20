<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TenantService
{
    /**
     * Default connection name before switching to tenant (restored in clearTenant).
     */
    protected ?string $connectionBeforeTenant = null;

    /**
     * PDO DSN for MySQL, aligned with Laravel's mysql config (TCP host:port OR unix_socket).
     * Raw "mysql:host=..." breaks on many servers that use DB_SOCKET or a custom DB_PORT.
     */
    protected function mysqlPdoDsn(array $config, ?string $database = null): string
    {
        $charset = $config['charset'] ?? 'utf8mb4';
        $socket = trim((string) ($config['unix_socket'] ?? ''));
        $host = $config['host'] ?? '127.0.0.1';
        $port = (string) ($config['port'] ?? '3306');

        $segments = [];
        if ($socket !== '') {
            $segments[] = 'unix_socket=' . $socket;
        } else {
            $segments[] = 'host=' . $host;
            $segments[] = 'port=' . $port;
        }
        if ($database !== null && $database !== '') {
            $segments[] = 'dbname=' . $database;
        }
        $segments[] = 'charset=' . $charset;

        return 'mysql:' . implode(';', $segments);
    }

    /**
     * Central (global) migration filenames - these must NOT run on tenant DBs.
     */
    protected static array $centralMigrations = [
        '2026_03_18_100000_create_companies_table.php',
        '2026_03_18_100001_create_company_otp_verifications_table.php',
        '2026_03_18_100002_create_subscription_tables_design.php',
        '2026_03_18_100003_create_admin_notifications_table.php',
        '2026_03_20_000000_add_slug_to_companies_table_if_missing.php',
        '2026_03_21_000002_add_title_to_admin_notifications_table.php',
    ];

    /**
     * Switch default database connection to the given company's tenant database.
     */
    public function setTenant(Company $company): void
    {
        $databaseName = $company->database_name;
        if (empty($databaseName)) {
            throw new \RuntimeException('Company has no database assigned.');
        }

        if ($this->connectionBeforeTenant === null) {
            $this->connectionBeforeTenant = config('database.default');
        }

        $config = config('database.connections.mysql_central');
        Config::set('database.connections.tenant', array_merge($config, [
            'database' => $databaseName,
        ]));
        Config::set('database.default', 'tenant');
        DB::purge('tenant');
    }

    /**
     * Clear tenant and reset to central default.
     */
    public function clearTenant(): void
    {
        if ($this->connectionBeforeTenant !== null) {
            Config::set('database.default', $this->connectionBeforeTenant);
            $this->connectionBeforeTenant = null;
        } else {
            Config::set('database.default', 'mysql');
        }
        DB::purge('tenant');
    }

    /**
     * Create the database for a company and run tenant migrations.
     */
    public function createDatabaseForCompany(Company $company): void
    {
        $databaseName = $company->database_name;
        if (empty($databaseName)) {
            $databaseName = Company::generateDatabaseName($company->id);
            $company->update(['database_name' => $databaseName]);
        }

        // Create tenant database and clone schema from the central DB.
        // This avoids fragile "alter table" tenant migrations when some base tables
        // are not present at the time a migration runs.
        $central = config('database.connections.mysql_central');
        $username = $central['username'];
        $password = $central['password'];
        $charset = $central['charset'] ?? 'utf8mb4';
        $collation = $central['collation'] ?? 'utf8mb4_unicode_ci';
        $centralDb = $central['database'];

        Log::info('Tenant DB: creating database', [
            'tenant_database' => $databaseName,
            'central_database' => $centralDb,
            'uses_socket' => !empty(trim((string) ($central['unix_socket'] ?? ''))),
        ]);

        // Server-level connection (no dbname) — must use same DSN rules as Laravel.
        $pdo = new \PDO(
            $this->mysqlPdoDsn($central, null),
            $username,
            $password,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        // If a previous attempt created a partially-migrated DB, drop it to guarantee
        // a clean schema clone.
        $pdo->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
        $pdo->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET {$charset} COLLATE {$collation}");

        $this->cloneCentralSchema($central, $centralDb, $databaseName);

        $tenantPath = database_path('migrations_tenant');
        if (File::isDirectory($tenantPath) && count(File::files($tenantPath)) > 0) {
            try {
                $this->migrateTenantDatabase($databaseName);
            } catch (\Throwable $e) {
                Log::error('Tenant migrations failed after schema clone', [
                    'tenant_database' => $databaseName,
                    'message' => $e->getMessage(),
                ]);
                throw $e;
            }
        } else {
            Log::warning('Tenant migrations skipped: database/migrations_tenant is empty. Run: php artisan tenant:stub-migrations');
        }

        $this->setTenant($company);
        try {
            TenantModulePermissionsSync::sync();
        } finally {
            $this->clearTenant();
        }
    }

    /**
     * Clone the central schema (structure only) into the given tenant database.
     *
     * This uses `SHOW CREATE TABLE` per base table.
     * It ensures the tenant DB starts with the same table structure as central.
     */
    protected function cloneCentralSchema(
        array $centralConfig,
        string $centralDb,
        string $tenantDb
    ): void {
        $username = $centralConfig['username'];
        $password = $centralConfig['password'];

        $centralDbEsc = str_replace('`', '``', $centralDb);
        $tenantDbEsc = str_replace('`', '``', $tenantDb);

        $pdoCentral = new \PDO(
            $this->mysqlPdoDsn($centralConfig, $centralDb),
            $username,
            $password,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $pdoTenant = new \PDO(
            $this->mysqlPdoDsn($centralConfig, $tenantDb),
            $username,
            $password,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $stmt = $pdoCentral->prepare(
            "SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = ? AND table_type = 'BASE TABLE'"
        );
        $stmt->execute([$centralDb]);
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $pdoTenant->exec('SET FOREIGN_KEY_CHECKS=0;');

        // Preload CREATE TABLE statements.
        $createStatements = [];
        foreach ($tables as $table) {
            // `SHOW CREATE TABLE` returns 2 columns on MySQL: Table and Create Table.
            $tableEsc = str_replace('`', '``', $table);
            $row = $pdoCentral->query("SHOW CREATE TABLE `{$centralDbEsc}`.`{$tableEsc}`")
                ->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                continue;
            }

            $createSql = $row['Create Table'] ?? array_values($row)[1] ?? null;
            if (!$createSql) {
                continue;
            }

            $createStatements[$table] = $createSql;
        }

        // Create tables. Retry failures to cope with FK dependency ordering.
        $pending = array_keys($createStatements);
        $lastException = null;
        $maxRounds = count($pending) + 1;
        for ($round = 0; $round < $maxRounds && !empty($pending); $round++) {
            $progress = false;
            $nextPending = [];

            foreach ($pending as $table) {
                try {
                    $pdoTenant->exec($createStatements[$table]);
                    $progress = true;
                } catch (\PDOException $e) {
                    $lastException = $e;
                    $nextPending[] = $table;
                }
            }

            $pending = $nextPending;
            if (!$progress) {
                break;
            }
        }

        if (!empty($pending) && $lastException) {
            throw $lastException;
        }

        $pdoTenant->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Run only tenant migrations on the given database.
     * Uses database/migrations_tenant (run tenant:stub-migrations to sync files from database/migrations).
     */
    public function migrateTenantDatabase(string $databaseName): void
    {
        $config = config('database.connections.mysql_central');
        Config::set('database.connections.tenant', array_merge($config, [
            'database' => $databaseName,
        ]));
        DB::purge('tenant');

        $tenantPath = database_path('migrations_tenant');
        if (!File::isDirectory($tenantPath) || count(File::files($tenantPath)) === 0) {
            throw new \RuntimeException(
                'Tenant migrations not found. Run: php artisan tenant:stub-migrations'
            );
        }

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations_tenant',
            '--force' => true,
        ]);
    }

    /**
     * Get current company from request (e.g. from route parameter).
     */
    public function getCompanyFromId(int|string $companyId): ?Company
    {
        return Company::query()->find($companyId);
    }
}
