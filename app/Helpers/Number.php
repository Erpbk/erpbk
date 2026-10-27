<?php

namespace App\Helpers;

use App\Support\CompanyGlobalPreferences;

class Number
{
    public static function format(float|int|string|null $amount, ?int $decimals = null, ?int $companyId = null): string
    {
        $numericAmount = is_numeric($amount) ? (float) $amount : 0.0;
        $decimals ??= CompanyGlobalPreferences::decimalPlaces($companyId);
        $thousand = CompanyGlobalPreferences::thousandSeparator($companyId);
        $decimal = CompanyGlobalPreferences::decimalSeparator($companyId);

        // number_format requires distinct separators; fall back if misconfigured.
        if ($thousand !== '' && $thousand === $decimal) {
            $thousand = $decimal === '.' ? ',' : '.';
        }

        return number_format($numericAmount, $decimals, $decimal, $thousand);
    }
}
