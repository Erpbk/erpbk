<?php

namespace App\Support;

use App\Models\Company;

class CompanyModuleVisibility
{
    /**
     * Whether the tenant sidebar should show this module (admin can disable per company).
     */
    public static function enabled(string $moduleKey): bool
    {
        $company = view()->shared('currentCompany');
        if (! $company instanceof Company) {
            return true;
        }
        $settings = $company->modules_settings;
        if (! is_array($settings)) {
            return true;
        }
        $disabled = $settings['disabled'] ?? [];

        return ! in_array($moduleKey, $disabled, true);
    }

    public static function bikeOnRentEnabled(): bool
    {
        return self::enabled('bike_on_rent');
    }

    public static function garageCustomersEnabled(): bool
    {
        return self::enabled('garages') && self::enabled('garages_customers');
    }
}
