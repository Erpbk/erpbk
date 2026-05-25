<?php

namespace App\Support;

use Illuminate\Http\Request;

class CompanyAuthRedirect
{
    /**
     * URL for unauthenticated users: unified company login.
     */
    public static function url(Request $request): string
    {
        return route('company.login');
    }
}
