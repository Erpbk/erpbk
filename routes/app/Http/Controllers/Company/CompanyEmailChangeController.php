<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Mail\CompanyOtpMail;
use App\Models\Company;
use App\Models\CompanyOtpVerification;
use App\Rules\AvailableCompanyContactEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CompanyEmailChangeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Send a verification code to the new company email address.
     */
    public function sendOtp(Request $request)
    {
        $company = $request->attributes->get('company');
        if (!$company instanceof Company) {
            return response()->json(['success' => false, 'message' => __('Company not found.')], 404);
        }

        try {
            $validated = $request->validate([
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    new AvailableCompanyContactEmail($company->id),
                ],
            ], [], [
                'email' => __('Email'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first('email'),
                'errors' => $e->errors(),
            ], 422);
        }

        $newEmail = strtolower(trim($validated['email']));
        $currentEmail = strtolower(trim((string) ($company->email ?? '')));

        if ($newEmail === $currentEmail) {
            return response()->json([
                'success' => false,
                'message' => __('This is already your current email address.'),
            ], 422);
        }

        $otpRecord = CompanyOtpVerification::createForEmail($newEmail);

        Mail::to($newEmail)->send(new CompanyOtpMail(
            $otpRecord->otp,
            $company->name ?? config('app.name'),
            'email_change'
        ));

        session([
            'company_email_change_pending' => [
                'new_email' => $newEmail,
                'company_id' => $company->id,
            ],
        ]);
        session()->forget('company_email_change_verified');

        return response()->json([
            'success' => true,
            'message' => __('Verification code sent to :email.', ['email' => $newEmail]),
        ]);
    }

    /**
     * Verify the OTP and allow the settings form to save the new email.
     */
    public function verifyOtp(Request $request)
    {
        $company = $request->attributes->get('company');
        if (!$company instanceof Company) {
            return response()->json(['success' => false, 'message' => __('Company not found.')], 404);
        }

        $pending = session('company_email_change_pending');
        if (
            !is_array($pending)
            || (int) ($pending['company_id'] ?? 0) !== (int) $company->id
            || empty($pending['new_email'])
        ) {
            return response()->json([
                'success' => false,
                'message' => __('Session expired. Please request a new verification code.'),
            ], 400);
        }

        $request->validate(['otp' => 'required|string|size:6']);

        $newEmail = strtolower(trim($pending['new_email']));

        $availability = validator(
            ['email' => $newEmail],
            ['email' => ['required', 'email', new AvailableCompanyContactEmail($company->id)]]
        );
        if ($availability->fails()) {
            return response()->json([
                'success' => false,
                'message' => $availability->errors()->first('email'),
            ], 422);
        }

        $record = CompanyOtpVerification::query()
            ->where('email', $newEmail)
            ->where('otp', $request->input('otp'))
            ->first();

        if (!$record || !$record->isValid()) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid or expired verification code.'),
            ], 422);
        }

        $record->update(['verified' => true]);
        session(['company_email_change_verified' => $newEmail]);

        return response()->json([
            'success' => true,
            'message' => __('Email verified. Saving your settings…'),
        ]);
    }
}
