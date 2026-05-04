<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\AdminCompany;
use App\Models\User;
use App\Models\Countries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class AdminCompaniesController extends Controller
{
    /**
     * List companies (pending, approved, rejected). Uses central DB.
     */
    public function index(Request $request)
    {
        $query = AdminCompany::query()->withTrashed();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $companies = $query->orderByDesc('created_at')->paginate(20);

        $companyTenantDbReady = [];
        foreach ($companies as $c) {
            $companyTenantDbReady[$c->id] = $c->status === Company::STATUS_APPROVED ? true : null;
        }

        return view('admin.companies.index', compact('companies', 'companyTenantDbReady'));
    }

    /**
     * Show admin form to create a company directly.
     */
    public function create()
    {
        $countries = Countries::query()->orderBy('name')->pluck('name', 'id');
        return view('admin.companies.create', compact('countries'));
    }

    /**
     * Admin creates a company directly in the shared ERPBK database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:companies,email',
            'phone' => 'required|string|max:50',
            'country_id' => 'required|exists:countries,id',
            'city' => 'required|string|max:255',
            'address' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_taxpayer' => 'nullable|boolean',
            'ntn_number' => 'required_if:is_taxpayer,1|nullable|string|max:50',
            'tax_registration_date' => 'required_if:is_taxpayer,1|nullable|date',
        ]);

        $countryName = Countries::query()->where('id', $validated['country_id'])->value('name');

        $company = Company::query()->create([
            'name' => $validated['name'],
            'slug' => Company::generateUniqueSlug($validated['name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'country' => $countryName,
            'city' => $validated['city'],
            'address' => $validated['address'],
            'password' => $validated['password'],
            'status' => Company::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => Auth::guard('admin')->id(),
            'is_taxpayer' => (bool) ($validated['is_taxpayer'] ?? false),
            'ntn_number' => $validated['ntn_number'] ?? null,
            'tax_registration_date' => $validated['tax_registration_date'] ?? null,
        ]);

        $this->createFirstUserForCompany($company);

        AdminCompany::syncFromCentralCompany($company);

        return redirect()->route('admin.companies.index')->with('success', __('Company created successfully.'));
    }

    /**
     * Show company details.
     */
    public function show(AdminCompany $company)
    {
        $tenantDbReady = $company->status === Company::STATUS_APPROVED;

        return view('admin.companies.show', compact('company', 'tenantDbReady'));
    }

    /**
     * Assign ERP sidebar modules and optional menu titles for this tenant.
     */
    public function editModules(AdminCompany $company)
    {
        $central = Company::query()->findOrFail($company->id);
        $moduleDefinitions = config('company_modules.modules', []);
        $settings = is_array($central->modules_settings) ? $central->modules_settings : [];
        $disabled = $settings['disabled'] ?? [];
        $labelOverrides = $settings['label_overrides'] ?? [];

        return view('admin.companies.modules', compact('company', 'central', 'moduleDefinitions', 'disabled', 'labelOverrides'));
    }

    /**
     * Persist module visibility and label overrides to the central companies row (and admin mirror).
     */
    public function updateModules(Request $request, AdminCompany $company)
    {
        $parentKeys = array_keys(config('company_modules.modules', []));
        $childKeys = [
            'cheques',
            'attendance_records',
            'attendance_summary',
            'items_list',
            'garage_items',
            'riders_list',
            'invoices',
            'activities',
            'live_activities',
            'rider_report',
            'bike_list',
            'maintenance_overview',
            'vat_ledger',
            'vat_return_file',
            'leasing_companies_list',
            'leasing_invoices',
            'bike_rent_customers',
            'suppliers',
            'supplier_invoices',
            'chart_of_accounts',
            'ledger',
        ];
        $keys = array_values(array_unique(array_merge($parentKeys, $childKeys)));
        $labelKeys = array_keys(config('menu_labels.defaults', []));
        $validated = $request->validate([
            'enabled' => 'nullable|array',
            'enabled.*' => ['string', Rule::in($keys)],
            'labels' => 'nullable|array',
            'labels.*' => 'nullable|string|max:255',
        ]);

        $enabled = array_values(array_intersect($keys, $validated['enabled'] ?? []));
        $disabled = array_values(array_diff($keys, $enabled));

        $labelOverrides = [];
        foreach ($labelKeys as $key) {
            $raw = $validated['labels'][$key] ?? null;
            if (is_string($raw) && trim($raw) !== '') {
                $labelOverrides[$key] = trim($raw);
            }
        }

        $central = Company::query()->findOrFail($company->id);
        $central->modules_settings = [
            'disabled' => $disabled,
            'label_overrides' => $labelOverrides,
        ];
        $central->save();

        AdminCompany::syncFromCentralCompany($central);

        return redirect()
            ->route('admin.companies.modules.edit', $company)
            ->with('success', __('Company modules updated.'));
    }

    /**
     * Approve company in shared ERPBK database.
     */
    public function approve(int|string $company)
    {
        $adminCompany = AdminCompany::withTrashed()->findOrFail($company);

        $centralCompany = Company::query()->find($adminCompany->id);
        $tempPassword = null;
        if (!$centralCompany) {
            $tempPassword = Str::random(12);
            $centralCompany = new Company();
            $centralCompany->id = $adminCompany->id;
            $centralCompany->name = $adminCompany->name;
            $centralCompany->slug = Company::generateUniqueSlug($adminCompany->name, $adminCompany->id);
            $centralCompany->email = $adminCompany->email;
            $centralCompany->country = $adminCompany->country;
            $centralCompany->phone = $adminCompany->phone;
            $centralCompany->city = $adminCompany->city;
            $centralCompany->address = $adminCompany->address;
            $centralCompany->password = $tempPassword;
            $centralCompany->status = Company::STATUS_PENDING;
            $centralCompany->database_name = null;
            $centralCompany->is_taxpayer = (bool) ($adminCompany->is_taxpayer ?? false);
            $centralCompany->ntn_number = $adminCompany->ntn_number;
            $centralCompany->tax_registration_date = $adminCompany->tax_registration_date;
            $centralCompany->logo = $adminCompany->logo;
            $centralCompany->primary_color = $adminCompany->primary_color;
            $centralCompany->secondary_color = $adminCompany->secondary_color;
            $centralCompany->branding_json = $adminCompany->branding_json;
            $centralCompany->modules_settings = $adminCompany->modules_settings;
            $centralCompany->approved_at = null;
            $centralCompany->approved_by = null;
            $centralCompany->rejection_reason = null;
            $centralCompany->save();

            AdminCompany::syncFromCentralCompany($centralCompany);
            $adminCompany->refresh();
        }

        if ($centralCompany->status === Company::STATUS_APPROVED) {
            $adminCompany->update([
                'status' => Company::STATUS_APPROVED,
                'database_name' => null,
                'approved_at' => $centralCompany->approved_at,
                'approved_by' => $centralCompany->approved_by,
                'rejection_reason' => null,
            ]);

            return back()->with('success', __('Company is already approved.'));
        }

        if ($centralCompany->status !== Company::STATUS_PENDING) {
            return back()->with('error', __('This company cannot be approved right now.'));
        }

        try {
            $this->createFirstUserForCompany($centralCompany);

            $centralCompany->status = Company::STATUS_APPROVED;
            $centralCompany->database_name = null;
            $centralCompany->approved_at = now();
            $centralCompany->approved_by = Auth::guard('admin')->id();
            $centralCompany->rejection_reason = null;
            $centralCompany->save();

            AdminCompany::syncFromCentralCompany($centralCompany);
        } catch (\Throwable $e) {
            return back()->with('error', __('Failed to create company owner user: :message', ['message' => $e->getMessage()]));
        }

        $successMessage = __('Company approved. Owner user created.');
        if ($tempPassword !== null) {
            $successMessage .= ' ' . __('Temporary owner password: :password', ['password' => $tempPassword]);
        }

        return back()->with('success', $successMessage);
    }

    /**
     * Create the first user (company owner) in the shared ERPBK database.
     */
    protected function createFirstUserForCompany(Company $company): void
    {
        $passwordHash = $company->getRawOriginal('password');
        $user = User::query()->updateOrCreate(
            ['email' => $company->email, 'company_id' => $company->id],
            [
                'name' => $company->name,
                'first_name' => $company->name,
                'email' => $company->email,
                'company_id' => $company->id,
                'password' => $passwordHash,
                'branch_ids' => [],
                'status' => 1,
            ]
        );
        if (method_exists($user, 'assignRole')) {
            $user->syncRoles(['Super Admin']);
        }
    }

    /**
     * Reject company.
     */
    public function reject(Request $request, AdminCompany $company)
    {
        $centralCompany = Company::query()->findOrFail($company->id);

        if ($centralCompany->status !== Company::STATUS_PENDING) {
            return back()->with('error', __('Company is not pending.'));
        }
        $centralCompany->update([
            'status' => Company::STATUS_REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        $company->update([
            'status' => Company::STATUS_REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return back()->with('success', __('Company rejected.'));
    }
}
