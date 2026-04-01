<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $companySlug = $request->route('company_slug') ?? $request->session()->get('company_slug');
        if ($companySlug === null || $companySlug === '') {
            // Allow non-company routes to continue when no company context exists.
            return $next($request);
        }

        $company = Company::query()->where('slug', $companySlug)->first();
        if (!$company && is_numeric($companySlug)) {
            // Backward compatibility for old links using numeric ID.
            $company = Company::query()->find((int) $companySlug);
        }
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
        // Clear any SessionGuard user resolved earlier in the stack against the wrong connection.
        Auth::guard('web')->forgetUser();
        $request->attributes->set('company', $company);
        view()->share('currentCompany', $company);

        $response = $next($request);
        $this->tenantService->clearTenant();

        return $response;
    }
}
