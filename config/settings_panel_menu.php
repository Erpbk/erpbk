<?php

/**
 * Settings panel ERP module nav — mirrors resources/views/layouts/menu.blade.php structure.
 * Each item links to module settings (not main app routes).
 *
 * Keys match menu_labels.defaults. Optional:
 * - visibility: CompanyModuleVisibility module key (defaults to key)
 * - permission: gate name (null = always show for admins in settings panel)
 * - settings: module key for route resolution (defaults to key)
 * - route: override route name (settings-panel.*)
 */
return [
    'items' => [
        [
            'key' => 'dashboard',
            'visibility' => 'dashboard',
            'permission' => 'dashboard_view',
            'icon' => 'ti-layout-dashboard',
        ],
        [
            'key' => 'cash_banks',
            'visibility' => 'cash_banks',
            'permission' => 'bank_view',
            'icon' => 'ti-building-bank',
            'children' => [
                ['key' => 'cash_banks', 'settings' => 'cash_banks'],
                ['key' => 'cheques', 'settings' => 'cheques'],
                ['key' => 'payments', 'settings' => 'payments'],
                ['key' => 'receipts', 'settings' => 'receipts'],
            ],
        ],
        [
            'key' => 'employees',
            'visibility' => 'employees',
            'permission' => 'employees_view',
            'icon' => 'ti-user',
            'children' => [
                ['key' => 'employees', 'settings' => 'employees'],
                ['key' => 'attendance_records', 'visibility' => 'attendance', 'permission' => 'attendance_view', 'settings' => 'attendance', 'settings_query' => ['ref_type' => 'employee']],
                ['key' => 'attendance_summary', 'visibility' => 'attendance', 'permission' => 'attendance_view', 'settings' => 'attendance', 'settings_query' => ['ref_type' => 'employee']],
                ['key' => 'employee_invoices', 'permission' => 'employeeinvoice_view', 'settings' => 'employee_invoices', 'label' => 'Employee Invoices'],
            ],
        ],
        [
            'key' => 'items',
            'visibility' => 'items',
            'permission' => 'item_view',
            'icon' => 'ti-notes',
            'children' => [
                ['key' => 'items_list', 'settings' => 'items'],
                ['key' => 'inventory', 'settings' => 'inventory'],
            ],
        ],
        [
            'key' => 'leads',
            'visibility' => 'leads',
            'permission' => 'leads_view',
            'icon' => 'ti-user-plus',
        ],
        [
            'key' => 'customers',
            'visibility' => 'customers',
            'permission' => 'customer_view',
            'icon' => 'ti-user-star',
            'children' => [
                ['key' => 'customer_list', 'settings' => 'customers'],
                ['key' => 'customer_invoices', 'permission' => 'customer_invoice_view', 'settings' => 'customer_invoices'],
                ['key' => 'customer_receipts', 'settings' => 'customers'],
            ],
        ],
        [
            'key' => 'vendors',
            'visibility' => 'vendors',
            'permission' => 'vendor_view',
            'icon' => 'ti-user-star',
        ],
        [
            'key' => 'recruiters',
            'visibility' => 'recruiters',
            'permission' => 'recruiter_view',
            'icon' => 'ti-user-star',
        ],
        [
            'key' => 'riders',
            'visibility' => 'riders',
            'permission' => 'rider_view',
            'icon' => 'ti-user-pin',
            'children' => [
                ['key' => 'riders_list', 'settings' => 'riders'],
                ['key' => 'rider_inventory_items', 'visibility' => 'rider_inventory', 'permission' => 'riderinventory_view', 'settings' => 'rider_inventory_items', 'label' => 'Rider Inventory Items'],
                ['key' => 'attendance_records', 'visibility' => 'attendance', 'permission' => 'attendance_view', 'settings' => 'attendance', 'settings_query' => ['ref_type' => 'rider']],
                ['key' => 'attendance_summary', 'visibility' => 'attendance', 'permission' => 'attendance_view', 'settings' => 'attendance', 'settings_query' => ['ref_type' => 'rider']],
                ['key' => 'invoices', 'permission' => 'riderinvoice_view', 'settings' => 'invoices'],
                ['key' => 'activities', 'settings' => 'activities'],
                ['key' => 'live_activities', 'settings' => 'live_activities'],
                ['key' => 'rider_report', 'settings' => 'rider_report'],
            ],
        ],
        [
            'key' => 'bikes',
            'visibility' => 'bikes',
            'permission' => 'bike_view',
            'icon' => 'ti-motorbike',
            'children' => [
                ['key' => 'bike_list', 'settings' => 'bike_list'],
                ['key' => 'bike_registration', 'settings' => 'bike_registration', 'permission' => 'bike_registration_view'],
            ],
        ],
        [
            'key' => 'bike_on_rent',
            'visibility' => 'bike_on_rent',
            'permission' => 'bike_view',
            'icon' => 'ti-motorbike',
            'children' => [
                ['key' => 'bike_rent_customers', 'settings' => 'bike_on_rent'],
                ['key' => 'leasing_billing_invoice', 'permission' => 'billing_invoice_view', 'settings' => 'bike_on_rent'],
                ['key' => 'bike_rent_customer_receipts', 'settings' => 'bike_on_rent'],
            ],
        ],
        [
            'key' => 'sims',
            'visibility' => 'sims',
            'permission' => 'sim_view',
            'icon' => 'ti-device-sim',
            'children' => [
                ['key' => 'sims', 'settings' => 'sims'],
                ['key' => 'sim_invoices', 'permission' => 'sim_invoice_view', 'settings' => 'sim_invoices', 'label' => 'SIM Invoices'],
                ['key' => 'sim_companies', 'settings' => 'sims'],
            ],
        ],
        [
            'key' => 'fuel_cards',
            'visibility' => 'fuel_cards',
            'permission' => 'fuel_view',
            'icon' => 'ti-gas-station',
            'children' => [
                ['key' => 'fuel_card_list', 'settings' => 'fuel_cards'],
                ['key' => 'fuel_data', 'settings' => 'fuel_cards'],
                ['key' => 'fuel_companies', 'settings' => 'fuel_cards'],
            ],
        ],
        [
            'key' => 'rta_fines',
            'visibility' => 'rta_fines',
            'permission' => ['rtafine_view', 'rtafine_paid_view'],
            'icon' => 'ti-file-alert',
            'children' => [
                ['key' => 'rta_fines_unpaid', 'permission' => 'rtafine_view', 'settings' => 'rta_fines_unpaid', 'label' => 'Unpaid Fines'],
                ['key' => 'rta_fines_paid', 'permission' => 'rtafine_paid_view', 'settings' => 'rta_fines_paid', 'label' => 'Paid Fines'],
            ],
        ],
        [
            'key' => 'rta_saliks',
            'visibility' => 'rta_saliks',
            'permission' => 'salik_view',
            'icon' => 'ti-cash',
        ],
        [
            'key' => 'visa_expense',
            'visibility' => 'visa_expense',
            'permission' => 'visaexpense_view',
            'icon' => 'ti-credit-card',
        ],
        [
            'key' => 'license_expense',
            'visibility' => 'license_expense',
            'permission' => 'licenseexpense_view',
            'icon' => 'ti-steering-wheel',
        ],
        [
            'key' => 'legal_case',
            'visibility' => 'legal_case',
            'permission' => 'legalcase_view',
            'icon' => 'ti-scale',
        ],
        [
            'key' => 'passport_handover',
            'visibility' => 'passport_handover',
            'permission' => 'passport_handover_view',
            'icon' => 'ti-e-passport',
        ],
        [
            'key' => 'expenses',
            'visibility' => 'expenses',
            'permission' => 'expenses_view',
            'icon' => 'ti-cash',
        ],
        [
            'key' => 'vat',
            'visibility' => 'vat',
            'permission' => 'vat_view',
            'icon' => 'ti-receipt-tax',
            'children' => [
                ['key' => 'vat_ledger', 'settings' => 'vat'],
                ['key' => 'vat_return_file', 'permission' => 'vat_return_view', 'settings' => 'vat'],
            ],
        ],
        [
            'key' => 'leasing_companies',
            'visibility' => 'leasing_companies',
            'permission' => 'leasing_view',
            'icon' => 'ti-building',
            'children' => [
                ['key' => 'leasing_companies_list', 'settings' => 'leasing_companies'],
                ['key' => 'leasing_invoices', 'permission' => 'leasing_company_invoice_view', 'settings' => 'leasing_companies'],
                ['key' => 'leasing_receipt', 'permission' => 'billing_invoice_view', 'settings' => 'leasing_companies'],
                ['key' => 'leasing_payment', 'permission' => 'leasing_company_invoice_view', 'settings' => 'leasing_companies'],
            ],
        ],
        [
            'key' => 'garages',
            'visibility' => 'garages',
            'permission' => 'bike_view',
            'icon' => 'ti-parking',
            'children' => [
                ['key' => 'garage_list', 'settings' => 'garages'],
                ['key' => 'garage_customers', 'settings' => 'garages'],
                ['key' => 'maintenance_overview', 'settings' => 'garages'],
                ['key' => 'bike_rent_customer_receipts', 'settings' => 'garages'],
            ],
        ],
        [
            'key' => 'supplier',
            'visibility' => 'supplier',
            'permission' => 'supplier_view',
            'icon' => 'ti-truck',
            'children' => [
                ['key' => 'suppliers', 'settings' => 'supplier'],
                ['key' => 'supplier_orders', 'settings' => 'supplier'],
                ['key' => 'supplier_invoices', 'settings' => 'supplier'],
                ['key' => 'supplier_payments', 'settings' => 'supplier'],
            ],
        ],
        [
            'key' => 'assets',
            'visibility' => 'assets',
            'permission' => 'asset_view',
            'icon' => 'ti-box',
            'settings' => null,
        ],
        [
            'key' => 'agreements',
            'visibility' => 'agreements',
            'permission' => ['agreement_view', 'agreement_manage_templates', 'gn_settings'],
            'icon' => 'ti-file-certificate',
            'settings' => null,
        ],
        [
            'key' => 'documents',
            'visibility' => 'documents',
            'permission' => 'company_documents_view',
            'icon' => 'ti-upload',
        ],
        [
            'key' => 'vouchers',
            'visibility' => 'vouchers',
            'permission' => 'voucher_view',
            'icon' => 'ti-ticket',
        ],
        [
            'key' => 'accounts',
            'visibility' => 'accounts',
            'permission' => ['account_view', 'gn_ledger'],
            'icon' => 'ti-graph',
            'children' => [
                ['key' => 'chart_of_accounts', 'permission' => 'account_view', 'settings' => 'accounts'],
                ['key' => 'ledger', 'permission' => 'gn_ledger', 'settings' => 'accounts'],
            ],
        ],
    ],
];
