<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyAdministratorForSettings
{
    /**
     * Company "Settings" panel: only Administrator / Super Admin (tenant) may access
     * configuration, users, roles, and module settings. All users may open their profile.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if ($this->isPublicForAllCompanyUsers($request, $user)) {
            return $next($request);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, __('Administrator access required.'));
        }

        $slug = $request->route('company_slug') ?? session('company_slug');
        if ($slug) {
            return redirect()
                ->route('settings-panel.profile', ['company_slug' => $slug])
                ->with('error', __('You do not have access to that area. Only company administrators can open Settings.'));
        }

        abort(403, __('Administrator access required.'));
    }

    private function isPublicForAllCompanyUsers(Request $request, User $user): bool
    {
        $name = $request->route()?->getName();

        if ($name === 'settings-panel.profile') {
            return true;
        }

        if ($name === 'users.password') {
            $id = $request->route('id');
            return (int) $id === (int) $user->id;
        }

        return false;
    }
}
