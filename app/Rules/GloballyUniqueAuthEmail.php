<?php

namespace App\Rules;

use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Ensures an email is not used by any company or user account (global uniqueness).
 */
class GloballyUniqueAuthEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));
        if ($email === '') {
            return;
        }

        if (Company::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()) {
            $fail('Email is already taken.');

            return;
        }

        if (User::withoutGlobalScope('company')
            ->whereNotNull('email')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->exists()) {
            $fail('Email is already taken.');
        }
    }
}
