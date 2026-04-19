<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetTenantConnection
{
    /**
     * Resolve company context for shared ERPBK database.
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

        $request->attributes->set('company', $company);
        view()->share('currentCompany', $company);

        // Extra guardrail: authenticated users may only access their own company routes.
        if (Auth::check() && (int) Auth::user()->company_id !== (int) $company->id) {
            abort(403, 'You are not allowed to access this company data.');
        }

        return $next($request);
    }
}
