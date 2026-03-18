<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TenantStubMigrations extends Command
{
    protected $signature = 'tenant:stub-migrations';
    protected $description = 'Copy non-central migrations to database/migrations_tenant for running on new company databases.';

    protected static array $centralMigrations = [
        '2026_03_18_100000_create_companies_table.php',
        '2026_03_18_100001_create_company_otp_verifications_table.php',
        '2026_03_18_100002_create_subscription_tables_design.php',
        '2026_03_18_100003_create_admin_notifications_table.php',
    ];

    public function handle(): int
    {
        $from = database_path('migrations');
        $to = database_path('migrations_tenant');
        if (!File::isDirectory($from)) {
            $this->error('Migrations directory not found.');
            return 1;
        }
        File::ensureDirectoryExists($to);
        $files = File::files($from);
        $copied = 0;
        foreach ($files as $file) {
            $basename = $file->getFilename();
            if (in_array($basename, self::$centralMigrations, true)) {
                continue;
            }
            $dest = $to . DIRECTORY_SEPARATOR . $basename;
            if (!File::exists($dest) || File::lastModified($file->getPathname()) > File::lastModified($dest)) {
                File::copy($file->getPathname(), $dest);
                $copied++;
            }
        }
        $this->info("Tenant migrations ready in database/migrations_tenant ({$copied} files synced).");
        return 0;
    }
}
