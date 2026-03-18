<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantConnection
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * Resolve company from route and switch default DB to tenant.
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $companyId = $request->route('company_id');
        if ($companyId === null) {
            abort(404, 'Company not found.');
        }

        $company = Company::query()->find($companyId);
        if (!$company) {
            abort(404, 'Company not found.');
        }

        if (!$company->isApproved()) {
            if ($company->isPending()) {
                return redirect()->route('company.register.pending')
                    ->with('message', __('Your company is pending approval.'));
            }
            abort(403, 'Company access is not approved.');
        }

        if (empty($company->database_name)) {
            abort(503, 'Company database is not ready. Please contact support.');
        }

        $this->tenantService->setTenant($company);
        $request->attributes->set('company', $company);
        view()->share('currentCompany', $company);

        $response = $next($request);
        $this->tenantService->clearTenant();

        return $response;
    }
}
