<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class TenantService
{
    /**
     * Central (global) migration filenames - these must NOT run on tenant DBs.
     */
    protected static array $centralMigrations = [
        '2026_03_18_100000_create_companies_table.php',
        '2026_03_18_100001_create_company_otp_verifications_table.php',
        '2026_03_18_100002_create_subscription_tables_design.php',
        '2026_03_18_100003_create_admin_notifications_table.php',
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

        $config = config('database.connections.mysql');
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
        Config::set('database.default', 'mysql');
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

        $driver = config('database.default');
        $host = config("database.connections.{$driver}.host");
        $username = config("database.connections.{$driver}.username");
        $password = config("database.connections.{$driver}.password");
        $charset = config("database.connections.{$driver}.charset", 'utf8mb4');
        $collation = config("database.connections.{$driver}.collation", 'utf8mb4_unicode_ci');

        $pdo = new \PDO(
            "mysql:host={$host}",
            $username,
            $password,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET {$charset} COLLATE {$collation}");

        $this->runTenantMigrations($databaseName);
    }

    /**
     * Run only tenant migrations on the given database.
     * Uses database/migrations_tenant (run tenant:stub-migrations once to populate).
     */
    protected function runTenantMigrations(string $databaseName): void
    {
        $config = config('database.connections.mysql');
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

        $this->clearTenant();
    }

    /**
     * Get current company from request (e.g. from route parameter).
     */
    public function getCompanyFromId(int|string $companyId): ?Company
    {
        return Company::query()->find($companyId);
    }
}
