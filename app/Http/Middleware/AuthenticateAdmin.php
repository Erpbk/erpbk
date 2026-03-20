<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateAdmin extends Middleware
{
    protected function authenticate($request, array $guards)
    {
        if (!Auth::guard('admin')->check()) {
            $this->unauthenticated($request, ['admin']);
        }
    }

    /**
     * Redirect unauthenticated users to the admin login page.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('admin.login');
    }
}

