<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetAdminGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('admin');
        return $next($request);
    }
}

