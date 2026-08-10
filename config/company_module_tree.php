<?php

/**
 * ERP sidebar module tree for admin company module assignment.
 * Parent keys match CompanyModuleVisibility::enabled() and config/company_modules.php.
 * Child keys match menu_labels.defaults (submenu label overrides).
 */
return [
    ['key' => 'dashboard'],

    ['key' => 'cash_banks', 'children' => ['cash_banks', 'cheques', 'payments', 'receipts']],

    ['key' => 'loans'],

    ['key' => 'employees', 'children' => ['employees']],
    ['key' => 'attendance', 'children' => ['attendance_records', 'attendance_summary']],

    ['key' => 'items', 'children' => ['items_list', 'garage_items', 'inventory']],

    ['key' => 'leads'],

    ['key' => 'customers', 'children' => ['customer_list', 'customer_invoices', 'customer_receipts']],

    ['key' => 'vendors'],
    ['key' => 'recruiters'],

    ['key' => 'riders', 'children' => ['riders_list', 'rider_inventory', 'invoices', 'activities', 'live_activities', 'rider_report']],

    ['key' => 'bikes', 'children' => ['bike_list', 'bike_registration']],
    ['key' => 'bike_on_rent', 'children' => ['bike_rent_customers', 'leasing_billing_invoice']],

    ['key' => 'sims', 'children' => ['sims', 'sim_companies']],

    ['key' => 'fuel_cards'],

    ['key' => 'rta_fines'],
    ['key' => 'rta_saliks'],

    ['key' => 'visa_expense'],
    ['key' => 'installments'],
    ['key' => 'license_expense'],
    ['key' => 'legal_case'],
    ['key' => 'passport_handover'],

    ['key' => 'expenses'],

    ['key' => 'vat', 'children' => ['vat_ledger', 'vat_return_file']],

    ['key' => 'leasing_companies', 'children' => [
        'leasing_companies_list',
        'leasing_invoices',
        'leasing_receipt',
        'leasing_payment',
    ]],

    ['key' => 'garages', 'children' => ['garage_list', 'garages_customers', 'maintenance_overview']],

    ['key' => 'supplier', 'children' => ['suppliers', 'supplier_orders', 'supplier_invoices', 'supplier_payments']],

    ['key' => 'assets'],
    ['key' => 'agreements'],
    ['key' => 'documents'],
    ['key' => 'vouchers'],

    ['key' => 'accounts', 'children' => ['chart_of_accounts', 'ledger']],
];
