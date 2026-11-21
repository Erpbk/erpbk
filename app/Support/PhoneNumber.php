<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalize a phone number to Meta WhatsApp "to" digits (E.164 without +).
     * Defaults bare local UAE numbers (starting with 05) to country code 971.
     */
    public static function toWhatsAppDigits(?string $raw, string $defaultCountryCode = '971'): ?string
    {
        if ($raw === null) {
            return null;
        }

        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^\d+]/', '', $value) ?? '';
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '+')) {
            $value = substr($value, 1);
        }

        $value = preg_replace('/\D+/', '', $value) ?? '';
        if ($value === '') {
            return null;
        }

        // Local UAE mobile: 05xxxxxxxx → 9715xxxxxxxx
        if (str_starts_with($value, '0') && strlen($value) >= 9 && strlen($value) <= 11) {
            $value = $defaultCountryCode.ltrim($value, '0');
        }

        if (strlen($value) < 8 || strlen($value) > 15) {
            return null;
        }

        return $value;
    }

    public static function isValidWhatsAppDigits(?string $digits): bool
    {
        return is_string($digits) && preg_match('/^\d{8,15}$/', $digits) === 1;
    }
}
