<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\AdminCompany;
use App\Models\Countries;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

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
        $query = AdminCompany::query()->withTrashed();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $companies = $query->orderByDesc('created_at')->paginate(20);
        return view('admin.companies.index', compact('companies'));
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
     * Admin creates a company and tenant DB immediately.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:mysql_central.companies,email',
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

        try {
            $company->update(['database_name' => Company::generateDatabaseName($company->id)]);
            $this->tenantService->createDatabaseForCompany($company);
            $this->createFirstUserForCompany($company);
        } catch (\Throwable $e) {
            $company->update(['status' => Company::STATUS_PENDING, 'database_name' => null]);
            return back()->withInput()->with('error', __('Failed to create tenant database: :message', ['message' => $e->getMessage()]));
        }

        AdminCompany::syncFromCentralCompany($company);

        return redirect()->route('admin.companies.index')->with('success', __('Company created and tenant database provisioned successfully.'));
    }

    /**
     * Show company details.
     */
    public function show(AdminCompany $company)
    {
        return view('admin.companies.show', compact('company'));
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
        $keys = array_keys(config('company_modules.modules', []));
        $validated = $request->validate([
            'enabled' => 'nullable|array',
            'enabled.*' => ['string', Rule::in($keys)],
            'labels' => 'nullable|array',
            'labels.*' => 'nullable|string|max:255',
        ]);

        $enabled = array_values(array_intersect($keys, $validated['enabled'] ?? []));
        $disabled = array_values(array_diff($keys, $enabled));

        $labelOverrides = [];
        foreach ($keys as $key) {
            $raw = $validated['labels'][$key] ?? null;
            if (is_string($raw) && trim($raw) !== '') {
                $meta = config("company_modules.modules.$key");
                $labelKey = $meta['primary_label_key'] ?? $key;
                $labelOverrides[$labelKey] = trim($raw);
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
     * Approve company: create tenant DB, run migrations, create first user.
     */
    public function approve(int|string $company)
    {
        $adminCompany = AdminCompany::withTrashed()->findOrFail($company);

        $centralCompany = Company::query()->find($adminCompany->id);
        $tempPassword = null;
        if (!$centralCompany) {
            // Some admin rows may exist without a matching central company record.
            // Create a central company entry so tenant provisioning can continue.
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
            $centralCompany->password = Hash::make($tempPassword);
            $centralCompany->status = Company::STATUS_PENDING;
            $centralCompany->database_name = $adminCompany->database_name ?: Company::generateDatabaseName($adminCompany->id);
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
        }

        if ($centralCompany->status !== Company::STATUS_PENDING) {
            return back()->with('error', __('Company is not pending.'));
        }

        $databaseName = Company::generateDatabaseName($centralCompany->id);
        $centralCompany->database_name = $databaseName;
        $centralCompany->status = Company::STATUS_APPROVED;
        $centralCompany->approved_at = now();
        $centralCompany->approved_by = Auth::guard('admin')->id();
        $centralCompany->save();

        try {
            $this->tenantService->createDatabaseForCompany($centralCompany);
            $this->createFirstUserForCompany($centralCompany);

            // Sync admin DB company record
            $adminCompany->update([
                'status' => Company::STATUS_APPROVED,
                'database_name' => $databaseName,
                'approved_at' => $centralCompany->approved_at,
                'approved_by' => $centralCompany->approved_by,
                'rejection_reason' => null,
            ]);
        } catch (\Throwable $e) {
            $centralCompany->update(['status' => Company::STATUS_PENDING, 'database_name' => null]);
            $adminCompany->update([
                'status' => Company::STATUS_PENDING,
                'database_name' => null,
                'approved_at' => null,
                'approved_by' => null,
            ]);
            return back()->with('error', __('Failed to create company database: :message', ['message' => $e->getMessage()]));
        }

        $successMessage = __('Company approved. Database and owner user created.');
        if ($tempPassword !== null) {
            $successMessage .= ' ' . __('Temporary owner password: :password', ['password' => $tempPassword]);
        }

        return back()->with('success', $successMessage);
    }

    /**
     * Create the first user (company owner) in the tenant database.
     */
    protected function createFirstUserForCompany(Company $company): void
    {
        $this->tenantService->setTenant($company);
        $userClass = config('auth.providers.users.model', \App\Models\User::class);
        // Use raw DB hash so the User model's "hashed" cast does not re-hash an already-hashed value incorrectly.
        $passwordHash = $company->getRawOriginal('password');
        $user = $userClass::query()->updateOrCreate(
            ['email' => $company->email],
            [
                'name' => $company->name,
                'first_name' => $company->name,
                'email' => $company->email,
                'password' => $passwordHash,
                'branch_ids' => [],
                'status' => 1,
            ]
        );
        if (method_exists($user, 'assignRole')) {
            $user->syncRoles(['Super Admin']);
        }
        $this->tenantService->clearTenant();
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
