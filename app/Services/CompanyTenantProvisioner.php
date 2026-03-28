<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point for creating a tenant MySQL database when a company is approved.
 * Runs synchronously in the HTTP request (no queue).
 */
class CompanyTenantProvisioner
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function tenantDatabaseExists(string $databaseName): bool
    {
        return $this->tenantService->tenantDatabaseExists($databaseName);
    }

    /**
     * Create schema, migrate, seed policy, sync module permissions (inside TenantService).
     */
    public function provisionOnApproval(Company $company): void
    {
        Log::info('Company tenant provision (admin approval): start', [
            'company_id' => $company->id,
            'database_name' => $company->database_name,
        ]);

        try {
            $this->tenantService->createDatabaseForCompany($company);
        } catch (\Throwable $e) {
            Log::error('Company tenant provision (admin approval): failed', [
                'company_id' => $company->id,
                'database_name' => $company->database_name,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        Log::info('Company tenant provision (admin approval): done', [
            'company_id' => $company->id,
            'database_name' => $company->database_name,
        ]);
    }
}
