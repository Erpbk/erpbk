<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminCompaniesController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * List companies (pending, approved, rejected). Uses central DB.
     */
    public function index(Request $request)
    {
        $query = Company::query()->withTrashed();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $companies = $query->orderByDesc('created_at')->paginate(20);
        return view('admin.companies.index', compact('companies'));
    }

    /**
     * Show company details.
     */
    public function show(Company $company)
    {
        return view('admin.companies.show', compact('company'));
    }

    /**
     * Approve company: create tenant DB, run migrations, create first user.
     */
    public function approve(Company $company)
    {
        if ($company->status !== Company::STATUS_PENDING) {
            return back()->with('error', __('Company is not pending.'));
        }

        $databaseName = Company::generateDatabaseName($company->id);
        $company->database_name = $databaseName;
        $company->status = Company::STATUS_APPROVED;
        $company->approved_at = now();
        $company->approved_by = Auth::id(); // if admin uses same users table on central
        $company->save();

        try {
            $this->tenantService->createDatabaseForCompany($company);
            $this->createFirstUserForCompany($company);
        } catch (\Throwable $e) {
            $company->update(['status' => Company::STATUS_PENDING, 'database_name' => null]);
            return back()->with('error', __('Failed to create company database: :message', ['message' => $e->getMessage()]));
        }

        return back()->with('success', __('Company approved. Database and owner user created.'));
    }

    /**
     * Create the first user (company owner) in the tenant database.
     */
    protected function createFirstUserForCompany(Company $company): void
    {
        $this->tenantService->setTenant($company);
        $userClass = config('auth.providers.users.model', \App\Models\User::class);
        $userClass::query()->firstOrCreate(
            ['email' => $company->email],
            [
                'name' => $company->name,
                'first_name' => $company->name,
                'email' => $company->email,
                'password' => $company->password,
                'branch_ids' => [], // Tenant may need a default branch; create in migration or here if required
                'status' => 'active',
            ]
        );
        $this->tenantService->clearTenant();
    }

    /**
     * Reject company.
     */
    public function reject(Request $request, Company $company)
    {
        if ($company->status !== Company::STATUS_PENDING) {
            return back()->with('error', __('Company is not pending.'));
        }
        $company->update([
            'status' => Company::STATUS_REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);
        return back()->with('success', __('Company rejected.'));
    }
}
