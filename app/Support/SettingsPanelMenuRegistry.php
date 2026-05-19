<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class SettingsPanelMenuRegistry
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function items(): array
    {
        return config('settings_panel_menu.items', []);
    }

    public static function label(string $labelKey, array $menuLabels, ?string $override = null): string
    {
        if ($override !== null && $override !== '') {
            return $override;
        }

        if ($labelKey === 'riders' || $labelKey === 'riders_list') {
            return $menuLabels['riders'] ?? config('menu_labels.defaults.riders', 'Riders');
        }

        return $menuLabels[$labelKey]
            ?? config('menu_labels.defaults.' . $labelKey, ucwords(str_replace('_', ' ', $labelKey)));
    }

    public static function settingsUrl(string $settingsModuleKey, ?string $companySlug = null): ?string
    {
        $companySlug ??= (string) (request()->route('company_slug') ?? session('company_slug') ?? '');
        if ($companySlug === '') {
            return null;
        }

        $params = ['company_slug' => $companySlug];

        $dedicated = [
            'riders' => 'settings-panel.rider-settings.index',
            'riders_list' => 'settings-panel.rider-settings.index',
            'employees' => 'settings-panel.employee-settings.index',
            'cheques' => 'settings-panel.cheques-settings.index',
            'accounts' => 'settings-panel.account-fields.index',
            'chart_of_accounts' => 'settings-panel.account-fields.index',
            'ledger' => 'settings-panel.account-fields.index',
            'vouchers' => 'settings-panel.voucher-settings.index',
            'vat' => 'settings-panel.vat-settings.index',
            'vat_ledger' => 'settings-panel.vat-settings.index',
            'vat_return_file' => 'settings-panel.vat-settings.index',
            'visa_expense' => 'settings-panel.visa-statuses.index',
            'bike_registration' => 'settings-panel.bike-registration-statuses.index',
            'bike_list' => 'settings-panel.module-settings.index',
            'dashboard' => 'settings-panel.module-settings.index',
            'assets' => null,
            'rta_fines' => 'settings-panel.module-settings.index',
            'rta_fines_unpaid' => 'settings-panel.module-settings.index',
            'rta_fines_paid' => 'settings-panel.module-settings.index',
        ];

        if (array_key_exists($settingsModuleKey, $dedicated)) {
            $routeName = $dedicated[$settingsModuleKey];
            if ($routeName === null) {
                return null;
            }

            if ($routeName === 'settings-panel.module-settings.index') {
                $params['module'] = $settingsModuleKey === 'dashboard'
                    ? 'dashboard'
                    : $settingsModuleKey;
            }

            return route($routeName, $params);
        }

        $params['module'] = $settingsModuleKey;

        return route('settings-panel.module-settings.index', $params);
    }

    public static function isActive(string $settingsModuleKey): bool
    {
        $patterns = [
            'riders' => ['settings-panel/rider-settings*', 'settings-panel/module-settings/riders*'],
            'riders_list' => ['settings-panel/rider-settings*'],
            'employees' => ['settings-panel/employee-settings*', 'settings-panel/module-settings/employees*'],
            'cheques' => ['settings-panel/cheques-settings*', 'settings-panel/module-settings/cheques*'],
            'accounts' => ['settings-panel/account-fields*'],
            'chart_of_accounts' => ['settings-panel/account-fields*'],
            'ledger' => ['settings-panel/account-fields*'],
            'vouchers' => ['settings-panel/voucher-settings*'],
            'vat' => ['settings-panel/vat-settings*'],
            'vat_ledger' => ['settings-panel/vat-settings*'],
            'vat_return_file' => ['settings-panel/vat-settings*'],
            'visa_expense' => ['settings-panel/visa-statuses*', 'settings-panel/module-settings/visa_expense*'],
            'bike_registration' => ['settings-panel/bike-registration-statuses*', 'settings-panel/module-settings/bike_registration*'],
            'bike_list' => ['settings-panel/module-settings/bike_list*'],
            'rta_fines' => ['settings-panel/module-settings/rta_fines*'],
            'rta_fines_unpaid' => ['settings-panel/module-settings/rta_fines_unpaid*'],
            'rta_fines_paid' => ['settings-panel/module-settings/rta_fines_paid*'],
        ];

        foreach ($patterns[$settingsModuleKey] ?? [] as $pattern) {
            if (request()->is($pattern)) {
                return true;
            }
        }

        return request()->is('settings-panel/module-settings/' . $settingsModuleKey . '*');
    }

    public static function branchIsOpen(array $item): bool
    {
        $settingsKey = (string) ($item['settings'] ?? $item['key'] ?? '');
        if ($settingsKey !== '' && self::isActive($settingsKey)) {
            return true;
        }

        if (request()->is('settings-panel/module-settings/' . ($item['key'] ?? '') . '*')) {
            return true;
        }

        foreach ($item['children'] ?? [] as $child) {
            $childSettings = (string) ($child['settings'] ?? $child['key'] ?? '');
            if ($childSettings !== '' && self::isActive($childSettings)) {
                return true;
            }
        }

        return false;
    }

    public static function isVisible(array $item): bool
    {
        $visibilityKey = (string) ($item['visibility'] ?? $item['key'] ?? '');

        return $visibilityKey === '' || CompanyModuleVisibility::enabled($visibilityKey);
    }

    public static function hasPermission(array $item): bool
    {
        $permission = $item['permission'] ?? null;
        if ($permission === null) {
            return true;
        }

        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if (is_array($permission)) {
            foreach ($permission as $ability) {
                if ($user->can($ability)) {
                    return true;
                }
            }

            return false;
        }

        return $user->can($permission);
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return list<array<string, mixed>>
     */
    public static function visibleChildren(array $children): array
    {
        $visible = [];
        foreach ($children as $child) {
            if (!self::isVisible($child) || !self::hasPermission($child)) {
                continue;
            }
            $visible[] = $child;
        }

        return $visible;
    }

    public static function icon(string $key, ?string $fromItem = null): string
    {
        if ($fromItem !== null && $fromItem !== '') {
            return $fromItem;
        }

        $icons = [
            'dashboard' => 'ti-layout-dashboard',
            'cash_banks' => 'ti-building-bank',
            'cheques' => 'ti-file',
            'payments' => 'ti-cash',
            'receipts' => 'ti-receipt',
            'employees' => 'ti-user',
            'employee_invoices' => 'ti-file',
            'attendance_records' => 'ti-calendar-check',
            'attendance_summary' => 'ti-calendar-stats',
            'items' => 'ti-notes',
            'items_list' => 'ti-list-details',
            'inventory' => 'ti-package',
            'leads' => 'ti-user-plus',
            'customers' => 'ti-user-star',
            'customer_list' => 'ti-users',
            'customer_invoices' => 'ti-receipt',
            'customer_receipts' => 'ti-receipt',
            'vendors' => 'ti-user-star',
            'recruiters' => 'ti-user-star',
            'riders' => 'ti-user-pin',
            'riders_list' => 'ti-users',
            'invoices' => 'ti-file',
            'activities' => 'ti-bike',
            'live_activities' => 'ti-activity',
            'rider_report' => 'ti-chart-bar',
            'bikes' => 'ti-motorbike',
            'bike_list' => 'ti-motorbike',
            'bike_registration' => 'ti-id',
            'bike_on_rent' => 'ti-motorbike',
            'bike_rent_customers' => 'ti-users',
            'leasing_billing_invoice' => 'ti-file-plus',
            'sims' => 'ti-device-sim',
            'sim_invoices' => 'ti-file-invoice',
            'sim_companies' => 'ti-building',
            'fuel_cards' => 'ti-gas-station',
            'fuel_card_list' => 'ti-gas-station',
            'fuel_data' => 'ti-gas-station',
            'fuel_companies' => 'ti-building',
            'rta_fines' => 'ti-file-alert',
            'rta_saliks' => 'ti-cash',
            'visa_expense' => 'ti-credit-card',
            'expenses' => 'ti-cash',
            'vat' => 'ti-receipt-tax',
            'vat_ledger' => 'ti-receipt-tax',
            'vat_return_file' => 'ti-file-export',
            'leasing_companies' => 'ti-building',
            'leasing_companies_list' => 'ti-building',
            'leasing_invoices' => 'ti-file-invoice',
            'leasing_receipt' => 'ti-file-plus',
            'leasing_payment' => 'ti-file-plus',
            'garages' => 'ti-parking',
            'garage_list' => 'ti-parking',
            'garage_customers' => 'ti-users',
            'maintenance_overview' => 'ti-motorbike',
            'supplier' => 'ti-truck',
            'suppliers' => 'ti-truck',
            'supplier_orders' => 'ti-truck',
            'supplier_invoices' => 'ti-truck',
            'supplier_payments' => 'ti-truck',
            'assets' => 'ti-box',
            'documents' => 'ti-upload',
            'vouchers' => 'ti-ticket',
            'accounts' => 'ti-graph',
            'chart_of_accounts' => 'ti-settings',
            'ledger' => 'ti-settings',
        ];

        return $icons[$key] ?? 'ti-adjustments-alt';
    }
}
