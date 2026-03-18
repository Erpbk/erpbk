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
    public function showLoginForm(int $company_id)
    {
        $company = Company::query()->find($company_id);
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

        return view('company.login', [
            'company' => $company,
            'companyId' => $company_id,
        ]);
    }

    /**
     * Handle company login. Switch to tenant DB then attempt auth.
     */
    public function login(Request $request, int $company_id)
    {
        $company = Company::query()->find($company_id);
        if (!$company || !$company->isApproved() || empty($company->database_name)) {
            return back()->withErrors(['email' => __('Invalid company or not approved.')]);
        }

        $this->tenantService->setTenant($company);

        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ], [], ['email' => __('Email or Username'), 'password' => __('Password')]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $request->session()->put('company_id', $company_id);
            $this->tenantService->clearTenant();
            return redirect()->intended(route('company.home', ['company_id' => $company_id]));
        }

        $this->tenantService->clearTenant();
        return back()->withErrors(['email' => __('These credentials do not match our records.')])->onlyInput('email');
    }

    /**
     * Logout from company app.
     */
    public function logout(Request $request, int $company_id)
    {
        $company = Company::query()->find($company_id);
        if ($company) {
            $this->tenantService->setTenant($company);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($company) {
            $this->tenantService->clearTenant();
        }
        return redirect()->route('company.login-form', ['company_id' => $company_id]);
    }
}
