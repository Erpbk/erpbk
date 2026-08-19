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

    public static function ridersEnabled(): bool
    {
        return self::enabled('riders');
    }

    public static function bikeOnRentEnabled(): bool
    {
        return self::enabled('bike_on_rent');
    }

    public static function garageCustomersEnabled(): bool
    {
        return self::enabled('garages') && self::enabled('garages_customers');
    }

    /**
     * Bike assign targets currently enabled: rider, rental, garage.
     *
     * @return list<string>
     */
    public static function bikeAssignTargets(): array
    {
        $targets = [];
        if (self::ridersEnabled()) {
            $targets[] = 'rider';
        }
        if (self::bikeOnRentEnabled()) {
            $targets[] = 'rental';
        }
        if (self::garageCustomersEnabled()) {
            $targets[] = 'garage';
        }

        return $targets;
    }

    /**
     * @return array<string, string>
     */
    public static function bikeAssignTypeLabels(): array
    {
        $riderCustom = self::customizedMenuLabel('riders');
        $garageCustom = self::customizedMenuLabel('garages');

        return [
            'rider' => $riderCustom ?? 'Rider',
            'rental' => 'Rental customer',
            'garage' => $garageCustom !== null ? $garageCustom . ' customers' : 'Garage customer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function bikeAssignTypeOptions(): array
    {
        $labels = self::bikeAssignTypeLabels();
        $options = ['' => 'Select Type'];
        foreach (self::bikeAssignTargets() as $key) {
            $options[$key] = $labels[$key];
        }

        return $options;
    }

    /**
     * Menu title from Settings when it differs from the default, otherwise null.
     */
    public static function customizedMenuLabel(string $key): ?string
    {
        $default = trim((string) (config('menu_labels.defaults.' . $key) ?? ''));
        $labels = \App\Models\Settings::getMenuLabels();
        $value = trim((string) ($labels[$key] ?? ''));
        if ($value === '' || strcasecmp($value, $default) === 0) {
            return null;
        }

        return $value;
    }
}
