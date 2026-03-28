<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CompanyAuthController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * Step 1: ask for company name, then redirect to /app/{slug}/login.
     */
    public function showFindLoginForm()
    {
        if (Auth::check() && session()->get('company_slug')) {
            return redirect()->route('company.home', ['company_slug' => session('company_slug')]);
        }

        return view('company.find-login');
    }

    /**
     * Resolve central company(ies) by name and send the user to the correct login URL.
     */
    public function findLogin(Request $request)
    {
        if (Auth::check() && session()->get('company_slug')) {
            return redirect()->route('company.home', ['company_slug' => session('company_slug')]);
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
        ]);

        $term = trim($validated['company_name']);
        if ($term === '') {
            return back()->withErrors(['company_name' => __('Please enter your company name.')])->withInput();
        }

        $normalized = Str::lower($term);

        $exact = Company::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
            ->orderBy('id')
            ->get();

        if ($exact->count() === 1) {
            return $this->redirectToCompanyLogin($exact->first());
        }

        if ($exact->count() > 1) {
            $exact->each(fn (Company $c) => $this->ensureSlug($c));

            return view('company.find-login-choose', ['companies' => $exact]);
        }

        $escaped = addcslashes($term, '%_\\');
        $partial = Company::query()
            ->where('name', 'like', '%'.$escaped.'%')
            ->orderBy('name')
            ->limit(25)
            ->get();

        if ($partial->isEmpty()) {
            return back()->withErrors(['company_name' => __('No company found with that name.')])->withInput();
        }

        if ($partial->count() === 1) {
            return $this->redirectToCompanyLogin($partial->first());
        }

        $partial->each(fn (Company $c) => $this->ensureSlug($c));

        return view('company.find-login-choose', ['companies' => $partial]);
    }

    protected function redirectToCompanyLogin(Company $company)
    {
        $this->ensureSlug($company);

        return redirect()->route('company.login-form', ['company_slug' => $company->slug]);
    }

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
