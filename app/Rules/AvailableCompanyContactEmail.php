<?php

namespace App\Rules;

use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ensures a company contact email is not already used by another company or user account.
 */
class AvailableCompanyContactEmail implements ValidationRule
{
    public function __construct(
        private readonly ?int $ignoreCompanyId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));
        if ($email === '') {
            return;
        }
        \Log::info('companyId: ' . $this->ignoreCompanyId);
        $companyQuery = Company::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email]);
        if ($this->ignoreCompanyId !== null) {
            $companyQuery->where('id', '!=', $this->ignoreCompanyId);
        }
        if ($companyQuery->exists()) {
            $fail(__('This email is already registered to another company.'));

            return;
        }

        $userQuery = User::withoutGlobalScope('company')
            ->whereNotNull('email')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email]);

        if ($this->ignoreCompanyId !== null) {
            $userQuery->where(function ($query) {
                $query->where('company_id', '!=', $this->ignoreCompanyId)
                    ->orWhereNull('company_id');
            });
        }

        if ($userQuery->exists()) {
            $fail(__('This email is already used by a user in another company.'));

            return;
        }

        if ($this->ignoreCompanyId === null) {
            return;
        }

        $sameCompanyUserExists = User::withoutGlobalScope('company')
            ->where('company_id', $this->ignoreCompanyId)
            ->whereNotNull('email')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->exists();

        if ($sameCompanyUserExists) {
            $fail(__('This email is already used by another user in your company.'));
        }
    }
}
