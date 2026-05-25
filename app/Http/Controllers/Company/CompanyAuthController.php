<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\AuthBranding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class CompanyAuthController extends Controller
{
    /**
     * Unified company login (email + password only).
     */
    public function showLogin()
    {
        if (Auth::check() && session()->get('company_slug')) {
            return redirect()->route('home', ['company_slug' => session('company_slug')]);
        }

        return view('company.login', [
            'branding' => AuthBranding::forPage('login'),
        ]);
    }

    /**
     * Legacy slug URL — redirect to unified login.
     */
    public function showLoginForm(string $company_slug)
    {
        return redirect()->route('company.login');
    }

    /**
     * Authenticate by globally unique email; resolve company from the user record.
     */
    public function login(Request $request, ?string $company_slug = null)
    {
        if (Auth::check() && session()->get('company_slug')) {
            return redirect()->route($this->companyHomeRouteName(), ['company_slug' => session('company_slug')]);
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [], [
            'email' => __('Email'),
            'password' => __('Password'),
        ]);

        $remember = $request->boolean('remember');
        $email = strtolower(trim($credentials['email']));
        $password = $credentials['password'];

        $user = User::withoutGlobalScope('company')
            ->whereNotNull('email')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        $statusValue = $user ? strtolower((string) ($user->status ?? '')) : '';
        $isActive = in_array($statusValue, ['1', 'true', 'active'], true);

        if ($user && $isActive && Hash::check($password, (string) $user->password)) {
            $company = Company::query()->find($user->company_id);
            if (!$company) {
                return back()
                    ->withErrors(['email' => __('Your account is not linked to a company. Please contact support.')])
                    ->onlyInput('email');
            }

            if (!$company->isApproved()) {
                if ($company->isPending()) {
                    return redirect()
                        ->route('company.register.pending')
                        ->with('message', __('Your company is pending approval.'));
                }

                return back()
                    ->withErrors(['email' => __('Company access is not approved.')])
                    ->onlyInput('email');
            }

            $this->ensureSlug($company);

            Auth::login($user, $remember);
            $request->session()->regenerate();
            $request->session()->put('company_slug', $company->slug);

            return redirect()->route($this->companyHomeRouteName(), ['company_slug' => $company->slug]);
        }

        if ($user && !$isActive) {
            return back()
                ->withErrors(['email' => __('Your account has been deactivated. Please contact the administrator.')])
                ->onlyInput('email');
        }

        return back()
            ->withErrors(['email' => __('These credentials do not match our records.')])
            ->onlyInput('email');
    }

    /**
     * Logout from company app.
     */
    public function logout(Request $request, string $company_slug)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('company.login');
    }

    protected function ensureSlug(Company $company): void
    {
        if (empty($company->slug)) {
            $company->slug = Company::generateUniqueSlug($company->name, $company->id);
            $company->save();
        }
    }

    protected function companyHomeRouteName(): string
    {
        if (Route::has('home')) {
            return 'home';
        }

        if (Route::has('home-dashboard')) {
            return 'home-dashboard';
        }

        if (Route::has('company.home')) {
            return 'company.home';
        }

        if (Route::has('company.home-dashboard')) {
            return 'company.home-dashboard';
        }

        return 'home';
    }
}
