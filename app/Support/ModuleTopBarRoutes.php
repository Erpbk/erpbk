<?php

namespace App\Support;

class ModuleTopBarRoutes
{
    public static function resolve(string $moduleKey): array
    {
        $moduleKey = ErpModuleRegistry::normalizeKey($moduleKey);

        if (in_array($moduleKey, ['riders', 'rider_settings'], true)) {
            return self::riderRoutes();
        }

        if (in_array($moduleKey, ['cheques', 'cheques_settings'], true)) {
            return self::chequeRoutes();
        }

        if (in_array($moduleKey, ['bike_list', 'bike_settings'], true)) {
            return self::bikeRoutes();
        }

        if (in_array($moduleKey, ['employees', 'employee_settings'], true)) {
            return self::employeeRoutes();
        }

        return self::genericRoutes($moduleKey);
    }

    public static function columnFieldForModule(string $moduleKey): string
    {
        $moduleKey = ErpModuleRegistry::normalizeKey($moduleKey);
        $config = ErpModuleRegistry::topBarConfig($moduleKey);

        if ($config && ($config['storage'] ?? '') === 'dedicated') {
            return (string) ($config['column_attribute'] ?? 'db_column');
        }

        return 'db_column';
    }

    public static function columnLabelForModule(string $moduleKey): string
    {
        $moduleKey = ErpModuleRegistry::normalizeKey($moduleKey);

        return match ($moduleKey) {
            'riders', 'rider_settings' => 'Rider Dropdown Column',
            'cheques', 'cheques_settings' => 'Cheque Column',
            'bike_list', 'bike_settings' => 'Vehicle Column',
            'employees', 'employee_settings' => 'Employee Column',
            default => 'Database Column',
        };
    }

    protected static function companySlug(): string
    {
        return (string) (request()->route('company_slug') ?? session('company_slug') ?? '');
    }

    protected static function riderRoutes(): array
    {
        $slug = self::companySlug();

        return [
            'accordion' => route('settings-panel.rider-settings.rider-top-accordion-body', ['company_slug' => $slug]),
            'store_category' => route('settings-panel.rider-settings.store-rider-top-category', ['company_slug' => $slug]),
            'field_values' => route('settings-panel.rider-settings.rider-top-category-field-values', ['company_slug' => $slug, 'id' => '__ID__']),
            'update_category' => route('settings-panel.rider-settings.update-rider-top-category', ['company_slug' => $slug, 'id' => '__ID__']),
            'destroy_category' => route('settings-panel.rider-settings.destroy-rider-top-category', ['company_slug' => $slug, 'id' => '__ID__']),
            'update_visibility' => route('settings-panel.rider-settings.update-rider-top-category-visibility', ['company_slug' => $slug, 'id' => '__ID__']),
            'store_option' => route('settings-panel.rider-settings.store-rider-top-option', ['company_slug' => $slug]),
            'update_option' => route('settings-panel.rider-settings.update-rider-top-option', ['company_slug' => $slug, 'id' => '__ID__']),
            'destroy_option' => route('settings-panel.rider-settings.destroy-rider-top-option', ['company_slug' => $slug, 'id' => '__ID__']),
        ];
    }

    protected static function chequeRoutes(): array
    {
        $slug = self::companySlug();

        return [
            'accordion' => route('settings-panel.cheques-settings.cheque-top-accordion-body', ['company_slug' => $slug]),
            'store_category' => route('settings-panel.cheques-settings.store-cheque-top-category', ['company_slug' => $slug]),
            'field_values' => route('settings-panel.cheques-settings.cheque-top-category-field-values', ['company_slug' => $slug, 'id' => '__ID__']),
            'update_category' => route('settings-panel.cheques-settings.update-cheque-top-category', ['company_slug' => $slug, 'id' => '__ID__']),
            'destroy_category' => route('settings-panel.cheques-settings.destroy-cheque-top-category', ['company_slug' => $slug, 'id' => '__ID__']),
            'update_visibility' => route('settings-panel.cheques-settings.update-cheque-top-category-visibility', ['company_slug' => $slug, 'id' => '__ID__']),
            'store_option' => route('settings-panel.cheques-settings.store-cheque-top-option', ['company_slug' => $slug]),
            'update_option' => route('settings-panel.cheques-settings.update-cheque-top-option', ['company_slug' => $slug, 'id' => '__ID__']),
            'destroy_option' => route('settings-panel.cheques-settings.destroy-cheque-top-option', ['company_slug' => $slug, 'id' => '__ID__']),
        ];
    }

    protected static function bikeRoutes(): array
    {
        $slug = self::companySlug();

        return [
            'accordion' => route('settings-panel.bike-settings.bike-top-accordion-body', ['company_slug' => $slug]),
            'store_category' => route('settings-panel.bike-settings.store-bike-top-category', ['company_slug' => $slug]),
            'field_values' => route('settings-panel.bike-settings.bike-top-category-field-values', ['company_slug' => $slug, 'id' => '__ID__']),
            'update_category' => route('settings-panel.bike-settings.update-bike-top-category', ['company_slug' => $slug, 'id' => '__ID__']),
            'destroy_category' => route('settings-panel.bike-settings.destroy-bike-top-category', ['company_slug' => $slug, 'id' => '__ID__']),
            'update_visibility' => route('settings-panel.bike-settings.update-bike-top-category-visibility', ['company_slug' => $slug, 'id' => '__ID__']),
            'store_option' => route('settings-panel.bike-settings.store-bike-top-option', ['company_slug' => $slug]),
            'update_option' => route('settings-panel.bike-settings.update-bike-top-option', ['company_slug' => $slug, 'id' => '__ID__']),
            'destroy_option' => route('settings-panel.bike-settings.destroy-bike-top-option', ['company_slug' => $slug, 'id' => '__ID__']),
        ];
    }

    protected static function employeeRoutes(): array
    {
        $slug = self::companySlug();

        return [
            'accordion' => route('settings-panel.employee-settings.employee-top-accordion-body', ['company_slug' => $slug]),
            'store_category' => route('settings-panel.employee-settings.store-employee-top-category', ['company_slug' => $slug]),
            'field_values' => route('settings-panel.employee-settings.employee-top-category-field-values', ['company_slug' => $slug, 'id' => '__ID__']),
            'update_category' => route('settings-panel.employee-settings.update-employee-top-category', ['company_slug' => $slug, 'id' => '__ID__']),
            'destroy_category' => route('settings-panel.employee-settings.destroy-employee-top-category', ['company_slug' => $slug, 'id' => '__ID__']),
            'update_visibility' => route('settings-panel.employee-settings.update-employee-top-category-visibility', ['company_slug' => $slug, 'id' => '__ID__']),
            'store_option' => route('settings-panel.employee-settings.store-employee-top-option', ['company_slug' => $slug]),
            'update_option' => route('settings-panel.employee-settings.update-employee-top-option', ['company_slug' => $slug, 'id' => '__ID__']),
            'destroy_option' => route('settings-panel.employee-settings.destroy-employee-top-option', ['company_slug' => $slug, 'id' => '__ID__']),
        ];
    }

    protected static function genericRoutes(string $moduleKey): array
    {
        $slug = self::companySlug();
        $params = ['company_slug' => $slug, 'module' => $moduleKey];

        return [
            'accordion' => route('settings-panel.module-top-bar.accordion', $params),
            'store_category' => route('settings-panel.module-top-bar.store-category', $params),
            'field_values' => route('settings-panel.module-top-bar.field-values', array_merge($params, ['id' => '__ID__'])),
            'update_category' => route('settings-panel.module-top-bar.update-category', array_merge($params, ['id' => '__ID__'])),
            'destroy_category' => route('settings-panel.module-top-bar.destroy-category', array_merge($params, ['id' => '__ID__'])),
            'update_visibility' => route('settings-panel.module-top-bar.update-visibility', array_merge($params, ['id' => '__ID__'])),
            'store_option' => route('settings-panel.module-top-bar.store-option', $params),
            'update_option' => route('settings-panel.module-top-bar.update-option', array_merge($params, ['id' => '__ID__'])),
            'destroy_option' => route('settings-panel.module-top-bar.destroy-option', array_merge($params, ['id' => '__ID__'])),
        ];
    }
}
