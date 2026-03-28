<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
     * Whether a MySQL schema exists on the same server as mysql_central.
     */
    public function tenantDatabaseExists(string $databaseName): bool
    {
        if ($databaseName === '') {
            return false;
        }

        $result = DB::connection('mysql_central')->selectOne(
            'SELECT COUNT(1) AS c FROM information_schema.schemata WHERE schema_name = ?',
            [$databaseName]
        );

        return ((int) ($result->c ?? 0)) > 0;
    }

    /**
     * Create the tenant database, clone schema from central, run tenant migrations,
     * and apply the initial data policy. Used when an admin approves a company.
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

        // Enforce tenant "initial state":
        // - Keep only specific permanent accounts.
        // - Copy countries data from central.
        // - Clear all other tenant data so the tenant starts fresh.
        $this->enforceTenantInitialDataPolicy($company);

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

    /**
     * Reset tenant DB data so only the required seed/reference remains.
     *
     * Requirements:
     * - In `accounts`, keep only a fixed set of account codes.
     * - In `countries`, copy central rows + data.
     * - Clear all other tenant data (fresh/empty).
     */
    private function enforceTenantInitialDataPolicy(Company $company): void
    {
        $allowedAccounts = [
            // Assets
            '1031' => ['name' => 'Enoc Fuel Wallet', 'account_type' => 'Asset', 'status' => 1],
            '1097' => ['name' => '1097', 'account_type' => 'Asset', 'status' => 0],
            '1247' => ['name' => 'Non-Current Assets', 'account_type' => 'Asset', 'status' => 1],
            '1639' => ['name' => 'Current Assets', 'account_type' => 'Asset', 'status' => 1],
            '1648' => ['name' => 'Intangible Assets', 'account_type' => 'Asset', 'status' => 1],
            '2452' => ['name' => 'Cash & Bank', 'account_type' => 'Asset', 'status' => 1],

            // Expenses
            '0998' => ['name' => 'Operating Expenses', 'account_type' => 'Expense', 'status' => 1],
            '1007' => ['name' => 'Admin Expenses', 'account_type' => 'Expense', 'status' => 1],
            '1046' => ['name' => 'Bike Renewal Charges', 'account_type' => 'Expense', 'status' => 0],
            '1154' => ['name' => 'Service Charges', 'account_type' => 'Expense', 'status' => 1],
            '1213' => ['name' => 'Bike Maintenance Expenses', 'account_type' => 'Expense', 'status' => 1],
            '1374' => ['name' => 'Visa Expense', 'account_type' => 'Expense', 'status' => 1],
            '1652' => ['name' => 'Cost of Revenue', 'account_type' => 'Expense', 'status' => 1],
            '2148' => ['name' => 'Finance Cost', 'account_type' => 'Expense', 'status' => 1],

            // Liabilities
            '0001' => ['name' => 'Riders', 'account_type' => 'Liability', 'status' => 1],
            '0996' => ['name' => 'Leasing Companies', 'account_type' => 'Liability', 'status' => 1],
            '1235' => ['name' => 'RTA Fines', 'account_type' => 'Liability', 'status' => 1],
            '1237' => ['name' => 'RTA Salik', 'account_type' => 'Liability', 'status' => 1],
            '1256' => ['name' => 'Vendors', 'account_type' => 'Liability', 'status' => 1],
            '1287' => ['name' => 'Supplier', 'account_type' => 'Liability', 'status' => 1],
            '1644' => ['name' => 'Current Liabilities', 'account_type' => 'Liability', 'status' => 1],
            '1645' => ['name' => 'Non-Current Liabilities', 'account_type' => 'Liability', 'status' => 1],
            '1664' => ['name' => 'Garages', 'account_type' => 'Liability', 'status' => 1],
            '2176' => ['name' => 'Recruiter', 'account_type' => 'Liability', 'status' => 1],
            '2454' => ['name' => 'VAT %', 'account_type' => 'Liability', 'status' => 1],

            // Revenue
            '1002' => ['name' => 'Incomes', 'account_type' => 'Revenue', 'status' => 1],
            '1638' => ['name' => 'Garage Revenue', 'account_type' => 'Revenue', 'status' => 1],
            '1641' => ['name' => 'Other Incomes', 'account_type' => 'Revenue', 'status' => 1],
            '2150' => ['name' => 'Interest On RAK Bank', 'account_type' => 'Revenue', 'status' => 1],
        ];

        $tenantDb = DB::connection('tenant')->getDatabaseName();
        $allowedAccountCodes = array_keys($allowedAccounts);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1) Keep only allowed rows in `accounts`.
        if (Schema::hasTable('accounts')) {
            DB::table('accounts')
                ->whereNotIn('account_code', $allowedAccountCodes)
                ->delete();

            $existingAllowedCodes = DB::table('accounts')
                ->whereIn('account_code', $allowedAccountCodes)
                ->pluck('account_code')
                ->map(fn ($v) => (string) $v)
                ->all();

            foreach ($allowedAccounts as $code => $meta) {
                if (in_array($code, $existingAllowedCodes, true)) {
                    // Preserve structure like `parent_id` if the chart already exists.
                    DB::table('accounts')
                        ->where('account_code', $code)
                        ->update([
                            'name' => $meta['name'],
                            'account_type' => $meta['account_type'],
                            'status' => $meta['status'],
                        ]);
                } else {
                    // If missing, insert a minimal row so the required accounts exist.
                    DB::table('accounts')->insert([
                        'account_code' => $code,
                        'name' => $meta['name'],
                        'account_type' => $meta['account_type'],
                        'parent_id' => null,
                        'ref_name' => null,
                        'ref_id' => null,
                        'status' => $meta['status'],
                        'notes' => null,
                        'opening_balance' => 0,
                        'is_locked' => 0,
                        'custom_field_values' => null,
                    ]);
                }
            }
        }

        // 2) Overwrite `countries` with central data.
        if (Schema::hasTable('countries')) {
            if (Schema::connection('mysql_central')->hasTable('countries')) {
                // Insert as associative arrays to avoid Laravel interpreting objects as numeric-key rows.
                $centralCountries = DB::connection('mysql_central')
                    ->table('countries')
                    ->get(['name', 'code', 'created_at', 'updated_at']);

                DB::table('countries')->delete();
                if ($centralCountries->isNotEmpty()) {
                    $rows = $centralCountries->map(static function ($c) {
                        return [
                            'name' => $c->name,
                            'code' => $c->code,
                            'created_at' => $c->created_at,
                            'updated_at' => $c->updated_at,
                        ];
                    })->toArray();

                    DB::table('countries')->insert($rows);
                }
            }
        }

        // 3) Clear every other tenant table’s data except required reference tables.
        $tables = DB::select(
            'SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = ?
             AND table_type = \'BASE TABLE\'',
            [$tenantDb]
        );

        $preserveTables = [
            // Allowed per your requirement
            'accounts',
            'countries',

            // Voucher reference data seeded by migrations (used by voucher UI/filters)
            'voucher_types',
            'voucher_type_module_assignments',

            // Keep Laravel migration tracking so future migrations behave.
            'migrations',
        ];

        foreach ($tables as $t) {
            $table = (string) $t->table_name;
            if (in_array($table, $preserveTables, true)) {
                continue;
            }
            if (!Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->delete();
        }

        // Rebuild derived expense_accounts based on the filtered accounts chart.
        if (Schema::hasTable('expense_accounts') && Schema::hasTable('accounts')) {
            DB::table('expense_accounts')->delete();

            $expenseRootIds = DB::table('accounts')
                ->whereNull('parent_id')
                ->where('account_type', 'Expense')
                ->pluck('id')
                ->all();

            if (!empty($expenseRootIds)) {
                $allExpenseIds = [];
                $toProcess = $expenseRootIds;
                while (!empty($toProcess)) {
                    $parentIds = $toProcess;
                    $allExpenseIds = array_merge($allExpenseIds, $parentIds);
                    $toProcess = DB::table('accounts')
                        ->whereIn('parent_id', $parentIds)
                        ->pluck('id')
                        ->all();
                }

                $allExpenseIds = array_unique($allExpenseIds);
                $existing = DB::table('expense_accounts')->pluck('account_id')->flip()->all();
                $toInsert = array_diff($allExpenseIds, array_keys($existing));

                if (!empty($toInsert)) {
                    $now = now();
                    $rows = array_map(function ($accountId) use ($now) {
                        return [
                            'account_id' => $accountId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }, $toInsert);

                    DB::table('expense_accounts')->insert($rows);
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
