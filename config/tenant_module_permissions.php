<?php

/**
 * Default ERP module permissions for each tenant database (Spatie).
 * Parent `name` matches the Permissions UI; child names are `{slug}_{view|create|edit|delete}`.
 * `extras` are additional permission names attached to the same parent (not covered by slug CRUD).
 *
 * `additional_permissions`: optional groups for stray permission names (same parent row as specified).
 */
return [
    'assign_roles' => ['Super Admin', 'Administrator'],

    'modules' => [
        ['parent' => 'Dashboard', 'slug' => 'dashboard', 'extras' => []],

        ['parent' => 'Bank', 'slug' => 'bank', 'extras' => [
            'bank_view_deleted',
            'bank_restore',
            'bank_force_delete',
            'bank_view_delete',
        ]],

        ['parent' => 'Loans', 'slug' => 'loan', 'extras' => [
            'loan_disburse',
            'loan_repay',
            'loan_installment_view',
        ]],

        ['parent' => 'Employees', 'slug' => 'employees', 'extras' => [
            'employeeinvoice_view',
            'employeeinvoice_create',
            'employeeinvoice_edit',
            'employeeinvoice_delete',
            'employee_document',
            'employee_salary',
            'employee_attendance',
            'employee_leave',
            'employee_timeline',
            'employees_documents',
        ]],

        ['parent' => 'Attendance', 'slug' => 'attendance', 'extras' => []],
        ['parent' => 'Item', 'slug' => 'item', 'extras' => []],
        ['parent' => 'Leads', 'slug' => 'leads', 'extras' => []],

        ['parent' => 'Customer', 'slug' => 'customer', 'extras' => [
            'customer_invoice_view',
            'customer_payments',
            'customer_invoice_create',
        ]],

        ['parent' => 'Vendor', 'slug' => 'vendor', 'extras' => []],
        ['parent' => 'Recruiter', 'slug' => 'recruiter', 'extras' => []],

        ['parent' => 'Rider', 'slug' => 'rider', 'extras' => [
            'riderinvoice_view',
            'rider_document',
            'riders_document',
            'rider_vendor_edit',
            'rider_customer_edit',
            'rider_designation_edit',
            'rider_visa_status_edit',
            'rider_insurance_edit',
            'rider_salary_model_edit',
            'rider_emirate_hub_edit',
            'rider_passport_handover_edit',
            'rider_wps_edit',
            'rider_c3_card_edit',
            'rider_assign_price_edit',
            'timeline_view',
            'timeline_create',
            'email_view',
            'email_create',
            'activity_view',
            'incentives_create',
            'invoices_view',
            'advanceloan_create',
            'penality_create',
            'vendorcharges_create',
        ]],

        ['parent' => 'Bike', 'slug' => 'bike', 'extras' => [
            'bike_assign_view',
            'bike_assign_edit',
            'bike_document',
            'files_view',
            'bikes_view',
        ]],

        ['parent' => 'Bike Registration', 'slug' => 'bike_registration', 'extras' => []],

        ['parent' => 'Bike on Rent', 'slug' => 'bike_rent', 'extras' => [
            'bike_rent_edit',
        ]],

        ['parent' => 'Sim', 'slug' => 'sim', 'extras' => [
            'sim_invoice_view',
            'sim_invoice_create',
            'sim_invoice_edit',
            'sim_invoice_delete',
            'sim_invoice_payment_voucher',
        ]],

        ['parent' => 'Fuel', 'slug' => 'fuel', 'extras' => [
            'fuel_assign',
        ]],

        ['parent' => 'RTA Fines', 'slug' => 'rtafine', 'extras' => [
            'rtafine_paid_view',
        ]],

        ['parent' => 'Salik', 'slug' => 'salik', 'extras' => []],
        ['parent' => 'Inventory', 'slug' => 'inventory', 'extras' => []],

        ['parent' => 'Visa Expense', 'slug' => 'visaexpense', 'extras' => [
            'visaexpense_show_in_menu',
        ]],

        ['parent' => 'Installments', 'slug' => 'installment', 'extras' => []],

        ['parent' => 'License Expense', 'slug' => 'licenseexpense', 'extras' => []],

        ['parent' => 'Legal Case', 'slug' => 'legalcase', 'extras' => []],

        ['parent' => 'Passport Handover', 'slug' => 'passport_handover', 'extras' => [
            'passport_handover_issue',
            'passport_handover_return',
            'passport_handover_print',
        ]],

        ['parent' => 'Rider Inventory', 'slug' => 'riderinventory', 'extras' => [
            'riderinventory_contract_print',
        ]],

        ['parent' => 'Expenses', 'slug' => 'expenses', 'extras' => [
            'expense_voucher_create',
            'voucher_document',
        ]],

        ['parent' => 'VAT', 'slug' => 'vat', 'extras' => [
            'vat_return_view',
        ]],

        ['parent' => 'Leasing', 'slug' => 'leasing', 'extras' => [
            'leasing_company_invoice_view',
            'leasing_company_invoice_edit',
            'leasing_company_invoice_create',
            'leasing_company_invoice_delete',
            'billing_invoice_view',
            'billing_invoice_create',
        ]],

        ['parent' => 'Garage', 'slug' => 'garage', 'extras' => []],
        ['parent' => 'Supplier', 'slug' => 'supplier', 'extras' => []],
        ['parent' => 'Dropdown', 'slug' => 'dropdown', 'extras' => []],
        ['parent' => 'Asset', 'slug' => 'asset', 'extras' => []],
        ['parent' => 'Documents', 'slug' => 'company_documents', 'extras' => []],

        ['parent' => 'Voucher', 'slug' => 'voucher', 'extras' => []],

        ['parent' => 'Accounts', 'slug' => 'account', 'extras' => [
            'gn_ledger',
        ]],

        ['parent' => 'Roles', 'slug' => 'role', 'extras' => [
            'permissions_view',
        ]],

        ['parent' => 'Users', 'slug' => 'user', 'extras' => []],
        ['parent' => 'Departments', 'slug' => 'department', 'extras' => []],
        ['parent' => 'Branches', 'slug' => 'branches', 'extras' => []],
        ['parent' => 'Receipts', 'slug' => 'receipt', 'extras' => []],
        ['parent' => 'Cheques', 'slug' => 'cheques', 'extras' => []],

        ['parent' => 'Payments', 'slug' => 'payments', 'extras' => [
            'payment_create',
        ]],

        ['parent' => 'Maintenance', 'slug' => 'maintenance', 'extras' => []],
        ['parent' => 'COD', 'slug' => 'cod', 'extras' => []],

        ['parent' => 'Penalties', 'slug' => 'penalty', 'extras' => [
            'penality_view',
            'penality_create',
        ]],

        ['parent' => 'Activity Logs', 'slug' => 'activity_logs', 'extras' => [
            'activity_logs_export',
            'activity_logs_delete',
        ]],

        ['parent' => 'System', 'slug' => 'trash', 'extras' => [
            'trash_restore',
            'trash_force_delete',
            'gn_settings',
        ]],

        ['parent' => 'Agreements', 'slug' => 'agreement', 'extras' => [
            'agreement_generate',
            'agreement_manage_templates',
        ]],
    ],

    'additional_permissions' => [],
];
