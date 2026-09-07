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
            'icon' => 'ti-layout-dashboard',
        ],
        [
            'key' => 'cash_banks',
            'visibility' => 'cash_banks',
            'permission' => 'cash_&_banks_banks_view',
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
            'permission' => 'employees_employee_view',
            'icon' => 'ti-user',
            'children' => [
                ['key' => 'employees', 'settings' => 'employees'],
                ['key' => 'attendance_records', 'visibility' => 'attendance', 'permission' => 'employees_attendance_view', 'settings' => 'attendance', 'settings_query' => ['ref_type' => 'employee']],
                ['key' => 'attendance_summary', 'visibility' => 'attendance', 'permission' => 'employees_attendance_view', 'settings' => 'attendance', 'settings_query' => ['ref_type' => 'employee']],
                ['key' => 'employee_invoices', 'permission' => 'employees_invoice_view', 'settings' => 'employee_invoices', 'label' => 'Employee Invoices'],
            ],
        ],
        [
            'key' => 'items',
            'visibility' => 'items',
            'permission' => ['items_item_view', 'items_inventory_view'],
            'icon' => 'ti-notes',
            'children' => [
                ['key' => 'items_list', 'settings' => 'items', 'permission' => 'items_item_view'],
                ['key' => 'inventory', 'settings' => 'inventory', 'permission' => 'items_inventory_view'],
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
            'permission' => ['customers_customer_view', 'customers_invoices_view', 'customers_payments_view'],
            'icon' => 'ti-user-star',
            'children' => [
                ['key' => 'customer_list', 'settings' => 'customers', 'permission' => 'customers_customer_view'],
                ['key' => 'customer_invoices', 'permission' => 'customers_invoices_view', 'settings' => 'customer_invoices', 'permission' => 'customers_invoices_view'],
                ['key' => 'customer_receipts', 'settings' => 'customers', 'permission' => 'customers_payments_view'],
            ],
        ],
        [
            'key' => 'vendors',
            'visibility' => 'vendors',
            'permission' => 'vendors_view',
            'icon' => 'ti-user-star',
        ],
        [
            'key' => 'recruiters',
            'visibility' => 'recruiters',
            'permission' => 'recruiters_view',
            'icon' => 'ti-user-star',
        ],
        [
            'key' => 'riders',
            'visibility' => 'riders',
            'permission' => ['riders_rider_view', 'riders_invoices_view', 'riders_attendance_view', 'riders_inventory_view', 'riders_payments_view', 'riders_activities_view', 'riders_live_activities_view', 'riders_report_view'],
            'icon' => 'ti-user-pin',
            'children' => [
                ['key' => 'riders_list', 'settings' => 'riders', 'permission' => 'riders_rider_view'],
                ['key' => 'rider_inventory_items', 'visibility' => 'rider_inventory', 'permission' => 'riders_inventory_view', 'settings' => 'rider_inventory_items', 'label' => 'Rider Inventory Items'],
                ['key' => 'attendance_records', 'visibility' => 'attendance', 'permission' => 'riders_attendance_view', 'settings' => 'attendance', 'settings_query' => ['ref_type' => 'rider']],
                ['key' => 'attendance_summary', 'visibility' => 'attendance', 'permission' => 'riders_attendance_view', 'settings' => 'attendance', 'settings_query' => ['ref_type' => 'rider']],
                ['key' => 'invoices', 'permission' => 'riders_invoices_view', 'settings' => 'invoices'],
                ['key' => 'rider_invoice_templates', 'permission' => 'riders_invoices_edit', 'settings' => 'rider_invoice_templates', 'label' => 'Rider Invoice Templates'],
                ['key' => 'activities', 'settings' => 'activities', 'permission' => 'riders_activities_view'],
                ['key' => 'live_activities', 'settings' => 'live_activities', 'permission' => 'riders_live_activities_view'],
                ['key' => 'rider_report', 'settings' => 'rider_report', 'permission' => 'riders_report_view'],
            ],
        ],
        [
            'key' => 'bikes',
            'visibility' => 'bikes',
            'permission' => ['bikes_bike_view', 'bikes_registration_view'],
            'icon' => 'ti-motorbike',
            'children' => [
                ['key' => 'bike_list', 'settings' => 'bike_list', 'permission' => 'bikes_bike_view'],
                ['key' => 'bike_registration', 'settings' => 'bike_registration', 'permission' => 'bikes_registration_view'],
            ],
        ],
        [
            'key' => 'bike_on_rent',
            'visibility' => 'bike_on_rent',
            'permission' => 'bike_on_rent_view',
            'icon' => 'ti-motorbike',
            'children' => [
                ['key' => 'bike_rent_companies', 'settings' => 'bike_on_rent'],
                ['key' => 'bike_rent_individuals', 'settings' => 'bike_on_rent'],
                ['key' => 'leasing_billing_invoice', 'permission' => 'billing_invoice_view', 'settings' => 'bike_on_rent'],
                ['key' => 'bike_rent_customer_receipts', 'settings' => 'bike_on_rent'],
            ],
        ],
        [
            'key' => 'sims',
            'visibility' => 'sims',
            'permission' => ['sims_sim_view', 'sims_invoices_view'],
            'icon' => 'ti-device-sim',
            'children' => [
                ['key' => 'sims', 'permission' => 'sims_sim_view', 'settings' => 'sims'],
                ['key' => 'sim_invoices', 'permission' => 'sims_invoices_view', 'settings' => 'sim_invoices', 'label' => 'SIM Invoices'],
                ['key' => 'sim_companies', 'permission' => 'sims_companies_view', 'settings' => 'sims'],
            ],
        ],
        [
            'key' => 'fuel_cards',
            'visibility' => 'fuel_cards',
            'permission' => ['fuel_cards_card_view', 'fuel_cards_transactions_view', 'fuel_cards_companies_view'],
            'icon' => 'ti-gas-station',
        ],
        [
            'key' => 'rta_fines',
            'visibility' => 'rta_fines',
            'permission' => ['rta_fines_unpaid_view', 'rta_fines_paid_view'],
            'icon' => 'ti-file-alert',
        ],
        [
            'key' => 'rta_saliks',
            'visibility' => 'rta_saliks',
            'permission' => 'rta_saliks_salik_view',
            'icon' => 'ti-cash',
        ],
        [
            'key' => 'visa_expense',
            'visibility' => 'visa expense',
            'permission' => 'visa_expense_view',
            'icon' => 'ti-credit-card',
        ],
        [
            'key' => 'installments',
            'visibility' => 'installments',
            'permission' => 'visa_expense_view',
            'icon' => 'ti-calendar-dollar',
        ],
        [
            'key' => 'license_expense',
            'visibility' => 'license_expense',
            'permission' => 'license_expense_view',
            'icon' => 'ti-steering-wheel',
        ],
        [
            'key' => 'legal_case',
            'visibility' => 'legal_case',
            'permission' => 'legal_case_view',
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
                ['key' => 'vat_ledger', 'permission' => 'vat_view', 'settings' => 'vat'],
                ['key' => 'vat_return_file', 'permission' => 'vat_view', 'settings' => 'vat'],
            ],
        ],
        [
            'key' => 'leasing_companies',
            'visibility' => 'leasing_companies',
            'permission' => ['leasing_companies_company_view', 'leasing_companies_invoices_view', 'leasing_companies_payments_view'],
            'icon' => 'ti-building',
            'children' => [
                ['key' => 'leasing_companies_list', 'settings' => 'leasing_companies'],
                ['key' => 'leasing_invoices', 'permission' => 'leasing_companies_invoices_view', 'settings' => 'leasing_companies'],
                ['key' => 'leasing_receipt', 'permission' => 'leasing_companies_payments_view', 'settings' => 'leasing_companies'],
                ['key' => 'leasing_payment', 'permission' => 'leasing_companies_payments_view', 'settings' => 'leasing_companies'],
            ],
        ],
        [
            'key' => 'garages',
            'visibility' => 'garages',
            'permission' => 'garages_garage_view',
            'icon' => 'ti-parking',
            'children' => [
                ['key' => 'garage_list', 'settings' => 'garages'],
                ['key' => 'garages_customers', 'settings' => 'garages'],
            ],
        ],
        [
            'key' => 'supplier',
            'visibility' => 'supplier',
            'permission' => ['suppliers_supplier_view', 'suppliers_purchase_order_view', 'suppliers_invoices_view', 'suppliers_payments_view'],
            'icon' => 'ti-truck',
            'children' => [
                ['key' => 'suppliers', 'settings' => 'supplier', 'permission' => 'suppliers_supplier_view'],
                ['key' => 'supplier_orders', 'settings' => 'supplier', 'permission' => 'suppliers_purchase_order_view'],
                ['key' => 'supplier_invoices', 'settings' => 'supplier', 'permission' => 'suppliers_invoices_view'],
                ['key' => 'supplier_payments', 'settings' => 'supplier', 'permission' => 'suppliers_payments_view'],
            ],
        ],
        [
            'key' => 'assets',
            'visibility' => 'assets',
            'permission' => 'assets_view',
            'icon' => 'ti-box',
            'settings' => null,
        ],
        [
            'key' => 'agreements',
            'visibility' => 'agreements',
            'permission' => ['agreements_view', 'agreements_edit', 'gn_settings'],
            'icon' => 'ti-file-certificate',
            'settings' => 'agreements',
        ],
        [
            'key' => 'documents',
            'visibility' => 'documents',
            'permission' => 'documents_view',
            'icon' => 'ti-upload',
        ],
        [
            'key' => 'vouchers',
            'visibility' => 'vouchers',
            'permission' => 'vouchers_view',
            'icon' => 'ti-ticket',
        ],
        [
            'key' => 'accounts',
            'visibility' => 'accounts',
            'permission' => ['accounts_coa_view', 'accounts_ledger_view'],
            'icon' => 'ti-graph',
            'children' => [
                ['key' => 'chart_of_accounts', 'permission' => 'accounts_coa_view', 'settings' => 'accounts'],
                ['key' => 'ledger', 'permission' => 'accounts_ledger_view', 'settings' => 'accounts'],
            ],
        ],
    ],
];
