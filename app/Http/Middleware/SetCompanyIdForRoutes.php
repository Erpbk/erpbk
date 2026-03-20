<?php

namespace App\Http\Middleware;

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
        $companySlug = $request->route('company_slug') ?? session('company_slug');
        if ($companySlug !== null) {
            URL::defaults(['company_slug' => $companySlug]);
        }
        return $next($request);
    }
}
