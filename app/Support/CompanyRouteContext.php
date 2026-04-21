<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Http\Request;

final class CompanyRouteContext
{
    public static function slug(?Request $request = null): ?string
    {
        $request ??= request();
        if (!$request) {
            return null;
        }

        $fromRoute = $request->route('company_slug');
        if ($fromRoute !== null && $fromRoute !== '') {
            return (string) $fromRoute;
        }

        $fromSession = $request->session()?->get('company_slug');
        if ($fromSession !== null && $fromSession !== '') {
            return (string) $fromSession;
        }

        $company = $request->attributes->get('company');
        if ($company && isset($company->slug) && $company->slug !== '') {
            return (string) $company->slug;
        }

        return null;
    }

    public static function companyId(?Request $request = null): ?int
    {
        $request ??= request();
        if (!$request) {
            return null;
        }

        $company = $request->attributes->get('company');
        if ($company && isset($company->id)) {
            return (int) $company->id;
        }

        $slug = self::slug($request);
        if ($slug === null || $slug === '') {
            $authId = auth()->user()?->company_id;
            return !empty($authId) ? (int) $authId : null;
        }

        $resolved = Company::query()->where('slug', $slug)->first();
        if (!$resolved && is_numeric($slug)) {
            $resolved = Company::query()->find((int) $slug);
        }

        return $resolved?->id ? (int) $resolved->id : null;
    }
}
