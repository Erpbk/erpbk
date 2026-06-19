<?php

/**
 * Maps ERP main module keys (config/erp_modules.php) to their primary listing route names.
 *
 * Keys listed here appear in Dashboard Settings; cards on the home dashboard require a resolvable DB table.
 *
 * @see App\Support\DashboardCardRegistry
 */
return [
    'max_visible_cards' => 8,

    'exclude_from_settings' => [
        'dashboard',
        'recycle_bin',
        'assets',
        'attendance',
    ],

    'index_routes' => [
        'cash_banks' => 'banks.index',
        'employees' => 'employees.index',
        'attendance' => 'attendance.index',
        'items' => 'items.index',
        'leads' => 'riderleads.index',
        'customers' => 'customers.index',
        'vendors' => 'vendors.index',
        'recruiters' => 'recruiters.index',
        'bikes' => 'bikes.index',
        'sims' => 'sims.index',
        'fuel_cards' => 'fuelCards.index',
        'rta_fines' => 'rtaFines.index',
        'rta_saliks' => 'salik.index',
        'inventory' => 'inventory.index',
        'visa_expense' => 'VisaExpense.index',
        'license_expense' => 'LicenseExpense.index',
        'legal_case' => 'LegalCase.index',
        'passport_handover' => 'passportHandover.index',
        'rider_inventory' => 'RiderInventory.index',
        'expenses' => 'expenses.index',
        'leasing_companies' => 'leasingCompanies.index',
        'garages' => 'garages.index',
        'supplier' => 'suppliers.index',
        'documents' => 'upload_files.index',
        'cheques' => 'cheques.index',
        'riders' => 'riders.index',
        'accounts' => 'accounts.index',
        'vouchers' => 'vouchers.index',
        'vat' => 'vat.index',
    ],

    /**
     * Optional default icons when not set in config/dashboard_cards.php
     */
    'default_icons' => [
        'cash_banks' => 'ti-building-bank',
        'employees' => 'ti-users',
        'attendance' => 'ti-clock',
        'items' => 'ti-box',
        'leads' => 'ti-flag',
        'customers' => 'ti-users',
        'vendors' => 'ti-building-store',
        'recruiters' => 'ti-user-search',
        'bikes' => 'ti-motorbike',
        'sims' => 'ti-device-sim',
        'fuel_cards' => 'ti-gas-station',
        'rta_fines' => 'ti-traffic-cone',
        'rta_saliks' => 'ti-road',
        'inventory' => 'ti-packages',
        'visa_expense' => 'ti-credit-card',
        'license_expense' => 'ti-steering-wheel',
        'legal_case' => 'ti-scale',
        'passport_handover' => 'ti-e-passport',
        'rider_inventory' => 'ti-package',
        'expenses' => 'ti-receipt',
        'leasing_companies' => 'ti-building',
        'garages' => 'ti-tool',
        'supplier' => 'ti-truck',
        'documents' => 'ti-files',
        'cheques' => 'ti-check',
        'riders' => 'ti-user-star',
        'accounts' => 'ti-calculator',
        'vouchers' => 'ti-file-invoice',
        'vat' => 'ti-percentage',
    ],
];
