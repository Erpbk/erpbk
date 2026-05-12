<?php

/**
 * Default ERP module permissions for each tenant database (Spatie).
 * Parent `name` matches the Permissions UI; child names are `{slug}_{view|create|edit|delete}`.
 * `extras` are full permission names (e.g. riderinvoice_view) attached to the same parent.
 */
return [
    'assign_roles' => ['Super Admin', 'Administrator'],

    'modules' => [
        ['parent' => 'Dashboard', 'slug' => 'dashboard', 'extras' => []],
        ['parent' => 'Bank', 'slug' => 'bank', 'extras' => ['bank_view_deleted', 'bank_restore', 'bank_force_delete']],
        ['parent' => 'Employees', 'slug' => 'employees', 'extras' => ['employeeinvoice_view', 'employeeinvoice_create', 'employeeinvoice_edit', 'employeeinvoice_delete']],
        ['parent' => 'Attendance', 'slug' => 'attendance', 'extras' => []],
        ['parent' => 'Item', 'slug' => 'item', 'extras' => []],
        ['parent' => 'Leads', 'slug' => 'leads', 'extras' => []],
        ['parent' => 'Customer', 'slug' => 'customer', 'extras' => []],
        ['parent' => 'Vendor', 'slug' => 'vendor', 'extras' => []],
        ['parent' => 'Recruiter', 'slug' => 'recruiter', 'extras' => []],
        ['parent' => 'Rider', 'slug' => 'rider', 'extras' => ['riderinvoice_view']],
        ['parent' => 'Bike', 'slug' => 'bike', 'extras' => []],
        ['parent' => 'Bike Registration', 'slug' => 'bike_registration', 'extras' => []],
        ['parent' => 'Bike on Rent', 'slug' => 'bike_rent', 'extras' => []],
        ['parent' => 'Sim', 'slug' => 'sim', 'extras' => ['sim_invoice_view', 'sim_invoice_create', 'sim_invoice_edit', 'sim_invoice_delete', 'sim_invoice_payment_voucher']],
        ['parent' => 'Fuel', 'slug' => 'fuel', 'extras' => []],
        ['parent' => 'RTA Fines', 'slug' => 'rtafine', 'extras' => ['rtafine_paid_view']],
        ['parent' => 'Salik', 'slug' => 'salik', 'extras' => []],
        ['parent' => 'Inventory', 'slug' => 'inventory', 'extras' => []],
        ['parent' => 'Visa Expense', 'slug' => 'visaexpense', 'extras' => []],
        ['parent' => 'Expenses', 'slug' => 'expenses', 'extras' => []],
        ['parent' => 'VAT', 'slug' => 'vat', 'extras' => ['vat_return_view']],
        ['parent' => 'Leasing', 'slug' => 'leasing', 'extras' => ['leasing_company_invoice_view']],
        ['parent' => 'Garage', 'slug' => 'garage', 'extras' => []],
        ['parent' => 'Supplier', 'slug' => 'supplier', 'extras' => []],
        ['parent' => 'Dropdown', 'slug' => 'dropdown', 'extras' => []],
        ['parent' => 'Asset', 'slug' => 'asset', 'extras' => []],
        ['parent' => 'Documents', 'slug' => 'company_documents', 'extras' => []],
        ['parent' => 'Voucher', 'slug' => 'voucher', 'extras' => []],
        ['parent' => 'Accounts', 'slug' => 'account', 'extras' => ['gn_ledger']],
        ['parent' => 'Activity Logs', 'slug' => 'activity_logs', 'extras' => ['activity_logs_export', 'activity_logs_delete']],
        ['parent' => 'System', 'slug' => 'trash', 'extras' => ['trash_restore', 'trash_force_delete']],
    ],
];
