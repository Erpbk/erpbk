<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Mail\CompanyOtpMail;
use App\Mail\CompanyRegisteredAdminMail;
use App\Models\Company;
use App\Models\CompanyOtpVerification;
use App\Models\Countries;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class CompanyRegistrationController extends Controller
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * Step 1: Show registration form.
     */
    public function showRegistrationForm()
    {
        $countries = Countries::query()->orderBy('name')->pluck('name', 'id');
        return view('company.register.step1', compact('countries'));
    }

    /**
     * Step 1: Submit - validate, send OTP, return JSON for OTP modal or redirect.
     */
    public function submitStep1(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    if (Company::on('mysql_central')->where('email', $value)->exists()) {
                        $fail(__('The email has already been taken.'));
                    }
                },
            ],
            'country_id' => 'required|exists:countries,id',
            'phone' => 'required|string|max:50',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], [
            'name' => __('Company Name'),
            'email' => __('Email'),
            'country_id' => __('Country'),
            'phone' => __('Phone'),
        ]);

        $countryName = Countries::query()->where('id', $validated['country_id'])->value('name');

        $otpRecord = CompanyOtpVerification::createForEmail($validated['email']);

        Mail::to($validated['email'])->send(new CompanyOtpMail($otpRecord->otp, $validated['name']));

        session([
            'company_register_step1' => [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'country' => $countryName,
                'phone' => $validated['phone'],
                'password' => $validated['password'],
            ],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => __('OTP sent to your email.')]);
        }
        return redirect()->route('company.register.otp')->with('message', __('OTP sent to your email.'));
    }

    /**
     * Step 2: Show OTP verification page (or modal is on step1, then redirect here after submit).
     */
    public function showOtpForm()
    {
        if (!session('company_register_step1')) {
            return redirect()->route('company.register')->with('error', __('Session expired. Please start again.'));
        }
        return view('company.register.otp');
    }

    /**
     * Step 2: Verify OTP.
     */
    public function verifyOtp(Request $request)
    {
        $step1 = session('company_register_step1');
        if (!$step1) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('Session expired.')], 400);
            }
            return redirect()->route('company.register')->with('error', __('Session expired.'));
        }

        $request->validate(['otp' => 'required|string|size:6']);

        $record = CompanyOtpVerification::query()
            ->where('email', $step1['email'])
            ->where('otp', $request->input('otp'))
            ->first();

        if (!$record || !$record->isValid()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => __('Invalid or expired OTP.')], 422);
            }
            return back()->withErrors(['otp' => __('Invalid or expired OTP.')]);
        }

        $record->update(['verified' => true]);
        session(['company_register_otp_verified' => true]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'redirect' => route('company.register.details')]);
        }
        return redirect()->route('company.register.details');
    }

    /**
     * Step 3: Company details form (country pre-filled, city, address, taxpayer).
     */
    public function showDetailsForm()
    {
        $step1 = session('company_register_step1');
        if (!$step1 || !session('company_register_otp_verified')) {
            return redirect()->route('company.register')->with('error', __('Please complete the previous steps.'));
        }
        return view('company.register.details', ['step1' => $step1]);
    }

    /**
     * Step 3: Save company and create tenant DB.
     */
    public function submitDetails(Request $request)
    {
        $step1 = session('company_register_step1');
        if (!$step1 || !session('company_register_otp_verified')) {
            return redirect()->route('company.register')->with('error', __('Session expired.'));
        }

        $rules = [
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string',
        ];
        if ($request->boolean('is_taxpayer')) {
            $rules['ntn_number'] = 'required|string|max:50';
            $rules['tax_registration_date'] = 'required|date';
        }

        $validated = $request->validate($rules, [], [
            'city' => __('City'),
            'address' => __('Full Address'),
            'ntn_number' => __('NTN Number'),
            'tax_registration_date' => __('Tax Registration Date'),
        ]);

        $validated['is_taxpayer'] = $request->boolean('is_taxpayer');
        $validated['ntn_number'] = $validated['ntn_number'] ?? null;
        $validated['tax_registration_date'] = $validated['tax_registration_date'] ?? null;

        DB::connection('mysql_central')->transaction(function () use ($step1, $validated) {
            $company = Company::query()->create([
                'name' => $step1['name'],
                'email' => $step1['email'],
                'country' => $step1['country'],
                'phone' => $step1['phone'],
                'password' => $step1['password'],
                'status' => Company::STATUS_PENDING,
                'database_name' => null, // Set when admin approves
                'city' => $validated['city'],
                'address' => $validated['address'],
                'is_taxpayer' => $validated['is_taxpayer'],
                'ntn_number' => $validated['ntn_number'],
                'tax_registration_date' => $validated['tax_registration_date'],
            ]);

            $this->notifyAdminNewCompany($company);
        });

        session()->forget(['company_register_step1', 'company_register_otp_verified']);

        return redirect()->route('company.register.pending')
            ->with('success', __('Registration complete. Your company is pending admin approval.'));
    }

    protected function notifyAdminNewCompany(Company $company): void
    {
        DB::connection('mysql_central')->table('admin_notifications')->insert([
            'type' => 'company_registered',
            'data' => json_encode([
                'company_id' => $company->id,
                'company_name' => $company->name,
                'email' => $company->email,
                'registered_at' => $company->created_at->toIso8601String(),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminEmail = config('mail.admin_notification_email');
        if (!empty($adminEmail)) {
            Mail::to($adminEmail)->send(new CompanyRegisteredAdminMail($company));
        }
    }

    /**
     * Pending approval page.
     */
    public function pending()
    {
        return view('company.register.pending');
    }
}
