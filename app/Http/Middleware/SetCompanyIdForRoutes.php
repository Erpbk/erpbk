<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyIdForRoutes
{
    /**
     * Set default company_id for route() when in company context so links work.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->route('company_id') ?? session('company_id');
        if ($companyId !== null) {
            URL::defaults(['company_id' => $companyId]);
        }
        return $next($request);
    }
}
