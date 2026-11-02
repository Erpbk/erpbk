<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $adminUser = Auth::guard('admin')->user();

        // if (!$adminUser || !$adminUser->hasPermission($permission)) {
        //     abort(403, 'You do not have permission to access this section.');
        // }

        return $next($request);
    }
}

