<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CompanyAuthController extends Controller
{
    /**
     * Step 1: ask for company name, then redirect to /app/{slug}/login.
     */
    public function showFindLoginForm()
    {
        if (Auth::check() && session()->get('company_slug')) {
            return redirect()->route('home', ['company_slug' => session('company_slug')]);
        }

        return view('company.find-login');
    }

    /**
     * Resolve central company(ies) by name and send the user to the correct login URL.
     */
    public function findLogin(Request $request)
    {
        if (Auth::check() && session()->get('company_slug')) {
            return redirect()->route($this->companyHomeRouteName(), ['company_slug' => session('company_slug')]);
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
            $exact->each(fn(Company $c) => $this->ensureSlug($c));

            return view('company.find-login-choose', ['companies' => $exact]);
        }

        $escaped = addcslashes($term, '%_\\');
        $partial = Company::query()
            ->where('name', 'like', '%' . $escaped . '%')
            ->orderBy('name')
            ->limit(25)
            ->get();

        if ($partial->isEmpty()) {
            return back()->withErrors(['company_name' => __('No company found with that name.')])->withInput();
        }

        if ($partial->count() === 1) {
            return $this->redirectToCompanyLogin($partial->first());
        }

        $partial->each(fn(Company $c) => $this->ensureSlug($c));

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
        $this->ensureSlug($company);

        return view('company.login', [
            'company' => $company,
            'companySlug' => $company->slug,
        ]);
    }

    /**
     * Handle company login in single ERPBK database using company_id isolation.
     */
    public function login(Request $request, string $company_slug)
    {
        $company = Company::query()->where('slug', $company_slug)->first();
        if (!$company && is_numeric($company_slug)) {
            $company = Company::query()->find((int) $company_slug);
        }
        if (!$company || !$company->isApproved()) {
            return back()->withErrors(['email' => __('Invalid company or not approved.')]);
        }

        $this->ensureSlug($company);

        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ], [], ['email' => __('Email or Username'), 'password' => __('Password')]);

        $remember = $request->boolean('remember');

        $login = $credentials['email'];
        $password = $credentials['password'];

        $user = User::query()
            ->where('company_id', $company->id)
            ->where(function ($q) use ($login) {
                $q->where('email', $login)->orWhere('username', $login);
            })
            ->first();

        $statusValue = $user ? strtolower((string) ($user->status ?? '')) : '';
        $isActive = in_array($statusValue, ['1', 'true', 'active'], true);

        $authenticated = $user && $isActive && Hash::check($password, (string) $user->password);

        if ($authenticated) {
            Auth::login($user, $remember);
            $request->session()->regenerate();
            $request->session()->put('company_slug', $company->slug);
            return redirect()->route($this->companyHomeRouteName(), ['company_slug' => $company->slug]);
        }

        if ($user && ! $isActive) {
            return back()
                ->withErrors(['email' => __('Your account has been deactivated. Please contact the administrator.')])
                ->onlyInput('email');
        }

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
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('company.login-form', ['company_slug' => $company?->slug ?? $company_slug]);
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
