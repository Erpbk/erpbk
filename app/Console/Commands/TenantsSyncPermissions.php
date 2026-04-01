<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\TenantModulePermissionsSync;
use App\Services\TenantService;
use Illuminate\Console\Command;

class TenantsSyncPermissions extends Command
{
    protected $signature = 'tenants:sync-permissions {--company= : Central company ID to sync only}';

    protected $description = 'Ensure default Spatie module permissions exist in each approved tenant database.';

    public function handle(TenantService $tenantService): int
    {
        $query = Company::query()
            ->where('status', Company::STATUS_APPROVED)
            ->whereNotNull('database_name');

        if ($this->option('company')) {
            $query->where('id', (int) $this->option('company'));
        }

        $companies = $query->get();
        if ($companies->isEmpty()) {
            $this->warn('No approved companies with a database found.');
            return self::SUCCESS;
        }

        foreach ($companies as $company) {
            $this->line("Syncing permissions for company #{$company->id} ({$company->database_name})…");
            try {
                $tenantService->setTenant($company);
                TenantModulePermissionsSync::sync();
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
            } finally {
                $tenantService->clearTenant();
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
