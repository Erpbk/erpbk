<?php

namespace App\Http\Middleware;

use App\Support\CompanyRouteContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyIdForRoutes
{
    /**
     * Set default company_slug for route() when in company context so links work.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $companySlug = CompanyRouteContext::slug($request);
        if ($companySlug !== null && $companySlug !== '') {
            URL::defaults(['company_slug' => $companySlug]);
        }
        return $next($request);
    }
}
