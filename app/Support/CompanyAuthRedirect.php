<?php

namespace App\Support;

use Illuminate\Http\Request;

class CompanyAuthRedirect
{
    /**
     * URL for unauthenticated users: company login when slug is known, else site root.
     */
    public static function url(Request $request): string
    {
        $slug = $request->route('company_slug');
        if ($slug === null || $slug === '') {
            $slug = $request->session()->get('company_slug');
        }
        if ($slug !== null && $slug !== '') {
            return route('company.login-form', ['company_slug' => $slug]);
        }

        return route('company.find-login');
    }
}
