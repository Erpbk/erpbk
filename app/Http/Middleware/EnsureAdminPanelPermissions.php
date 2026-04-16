<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\AdminModulePermissions;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPanelPermissions
{
    /**
     * Ensure Spatie permissions exist for admin-module routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        AdminModulePermissions::ensureForAdminPanel();
        return $next($request);
    }
}

