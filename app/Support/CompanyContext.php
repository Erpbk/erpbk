<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;

final class CompanyContext
{
    public static function shouldApplyScope(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        if (Auth::guard('admin')->check()) {
            return false;
        }

        $request = request();
        if (!$request) {
            return false;
        }

        if ($request->is('admin/*')) {
            return false;
        }

        return true;
    }

    public static function id(): ?int
    {
        $request = request();

        $company = $request?->attributes->get('company');
        if ($company && isset($company->id)) {
            return (int) $company->id;
        }

        // Resolve from route/session slug BEFORE Auth::user().
        // User uses BelongsToCompany; during StartSession auth retrieval the company
        // global scope calls id(), and Auth::user() would re-enter retrieveById
        // forever (OOM). Prefer slug, and only read Auth when already resolved.
        $companySlug = $request?->route('company_slug');
        if (empty($companySlug) && $request?->hasSession()) {
            $companySlug = $request->session()->get('company_slug');
        }
        if (! empty($companySlug)) {
            $resolvedCompany = Company::query()->where('slug', (string) $companySlug)->first();
            if (! $resolvedCompany && is_numeric($companySlug)) {
                $resolvedCompany = Company::query()->find((int) $companySlug);
            }

            if ($resolvedCompany) {
                $request?->attributes->set('company', $resolvedCompany);

                return (int) $resolvedCompany->id;
            }
        }

        if (Auth::hasUser()) {
            $authUser = Auth::user();
            if ($authUser && ! empty($authUser->company_id)) {
                return (int) $authUser->company_id;
            }
        }

        return null;
    }
}
