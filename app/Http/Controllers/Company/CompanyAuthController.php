<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyAuthController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * Show company login form. Company loaded from central DB; no tenant switch yet.
     */
    public function showLoginForm(string $company_slug)
    {
        $company = Company::query()->where('slug', $company_slug)->first();
        if (!$company && is_numeric($company_slug)) {
            $company = Company::query()->find((int) $company_slug);
        }
        if (!$company) {
            abort(404, 'Company not found.');
        }
        if (!$company->isApproved()) {
            if ($company->isPending()) {
                return redirect()->route('company.register.pending')->with('message', __('Your company is pending approval.'));
            }
            abort(403, 'Company access is not approved.');
        }
        if (empty($company->database_name)) {
            abort(503, 'Company database is not ready. Please contact support.');
        }

        $this->ensureSlug($company);

        return view('company.login', [
            'company' => $company,
            'companySlug' => $company->slug,
        ]);
    }

    /**
     * Handle company login. Switch to tenant DB then attempt auth.
     */
    public function login(Request $request, string $company_slug)
    {
        $company = Company::query()->where('slug', $company_slug)->first();
        if (!$company && is_numeric($company_slug)) {
            $company = Company::query()->find((int) $company_slug);
        }
        if (!$company || !$company->isApproved() || empty($company->database_name)) {
            return back()->withErrors(['email' => __('Invalid company or not approved.')]);
        }

        $this->ensureSlug($company);

        $this->tenantService->setTenant($company);

        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ], [], ['email' => __('Email or Username'), 'password' => __('Password')]);

        $remember = $request->boolean('remember');

        $login = $credentials['email'];
        $password = $credentials['password'];

        $authenticated = Auth::attempt(['email' => $login, 'password' => $password], $remember)
            || Auth::attempt(['username' => $login, 'password' => $password], $remember);

        if ($authenticated) {
            $request->session()->regenerate();
            $request->session()->put('company_slug', $company->slug);
            $this->tenantService->clearTenant();
            return redirect()->route('company.home', ['company_slug' => $company->slug]);
        }

        $this->tenantService->clearTenant();
        return back()->withErrors(['email' => __('These credentials do not match our records.')])->onlyInput('email');
    }

    /**
     * Logout from company app.
     */
    public function logout(Request $request, string $company_slug)
    {
        $company = Company::query()->where('slug', $company_slug)->first();
        if (!$company && is_numeric($company_slug)) {
            $company = Company::query()->find((int) $company_slug);
        }
        if ($company) {
            $this->tenantService->setTenant($company);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($company) {
            $this->tenantService->clearTenant();
        }
        return redirect()->route('company.login-form', ['company_slug' => $company?->slug ?? $company_slug]);
    }

    protected function ensureSlug(Company $company): void
    {
        if (empty($company->slug)) {
            $company->slug = Company::generateUniqueSlug($company->name, $company->id);
            $company->save();
        }
    }
}
