<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\TenantModulePermissionsSync;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TenantsMigrate extends Command
{
    protected $signature = 'tenants:migrate
                            {--tenant= : Only migrate this tenant database name (exact match)}
                            {--company= : Only migrate the company with this ID}';

    protected $description = 'Run database/migrations_tenant against all company databases (or one tenant/company).';

    public function __construct(
        protected TenantService $tenantService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = Company::query()
            ->whereNotNull('database_name')
            ->where('database_name', '!=', '');

        if ($this->option('company')) {
            $query->where('id', (int) $this->option('company'));
        }
        if ($this->option('tenant')) {
            $query->where('database_name', $this->option('tenant'));
        }

        $companies = $query->orderBy('id')->get();

        if ($companies->isEmpty()) {
            $this->warn('No companies with a database_name matched.');
            return 0;
        }

        $this->info('Running tenant migrations for '.$companies->count().' database(s).');
        $exit = 0;

        foreach ($companies as $company) {
            $name = $company->database_name;
            $this->line(' — '.$name.' (company #'.$company->id.')');

            try {
                $this->tenantService->migrateTenantDatabase($name);
                $this->tenantService->setTenant($company);
                TenantModulePermissionsSync::sync();
                $this->line(Artisan::output());
            } catch (\Throwable $e) {
                $this->error('   Failed: '.$e->getMessage());
                $exit = 1;
            } finally {
                $this->tenantService->clearTenant();
            }
        }

        return $exit;
    }
}
