<?php

/**
 * Default ERP module permissions for each tenant database (Spatie).
 *
 * Parent `name` is the root permission row (Permissions UI module).
 * - With `submodules`: leaves are `{slug}_{submodule_slug}_{view|create|edit|delete}`
 *   (see App\Support\PermissionTreeBuilder).
 * - Without `submodules`: leaves are `{slug}_{view|create|edit|delete}`.
 * - `extras`: additional leaf names under the same parent (flat modules only;
 *   ignored when `submodules` is non-empty).
 * - `slug` defaults to PermissionTreeBuilder::slugify(parent) when omitted.
 *
 * `additional_permissions`: stray leaf names attached to an existing parent.
 */
return [
    'assign_roles' => ['Super Admin', 'Administrator'],

    'modules' => [
        [
            'parent' => 'Cash & Banks',
            'slug' => 'cash_&_banks',
            'submodules' => ['Banks', 'Cheques', 'Payments', 'Receipts'],
        ],

        ['parent' => 'Loans', 'slug' => 'loans', 'submodules' => []],

        [
            'parent' => 'Employees',
            'slug' => 'employees',
            'submodules' => [
                'Employee',
                'Attendance',
                'Invoice',
                'Payments',
                'Document',
                'Ledger',
                'Voucher',
            ],
        ],

        ['parent' => 'Email', 'slug' => 'email', 'submodules' => []],

        [
            'parent' => 'Items',
            'slug' => 'items',
            'submodules' => ['Item', 'Inventory'],
        ],

        ['parent' => 'Leads', 'slug' => 'leads', 'submodules' => []],

        [
            'parent' => 'Customers',
            'slug' => 'customers',
            'submodules' => [
                'Customer',
                'Invoices',
                'Payments',
                'Documents',
                'Inventory',
            ],
        ],

        ['parent' => 'Vendors', 'slug' => 'vendors', 'submodules' => []],
        ['parent' => 'Recruiters', 'slug' => 'recruiters', 'submodules' => []],

        [
            'parent' => 'Riders',
            'slug' => 'riders',
            'submodules' => [
                'Rider',
                'Attendance',
                'Inventory',
                'Invoices',
                'Payments',
                'Activities',
                'Live Activities',
                'Report',
                'Documents',
                'Timeline',
                'History',
                'Voucher',
                'Ledger',
                'Export Data',
            ],
        ],

        [
            'parent' => 'Bikes',
            'slug' => 'bikes',
            'submodules' => [
                'Bike',
                'Registration',
                'Assign',
                'Documents',
                'Maintenance',
                'Export Data',
            ],
        ],

        [
            'parent' => 'Bike On Rent',
            'slug' => 'bike_on_rent',
            'submodules' => [
                'Customers',
                'Invoices',
                'Payments',
                'Documents',
                'Maintenance',
                'Ledger',
            ],
        ],

        [
            'parent' => 'Sims',
            'slug' => 'sims',
            'submodules' => [
                'Sim',
                'Companies',
                'Invoices',
                'Payments',
                'Assign',
                'Export Data',
            ],
        ],

        [
            'parent' => 'Fuel Cards',
            'slug' => 'fuel_cards',
            'submodules' => [
                'Card',
                'Transactions',
                'Companies',
                'Assign',
                'Export Data',
            ],
        ],

        [
            'parent' => 'RTA Fines',
            'slug' => 'rta_fines',
            'submodules' => ['Unpaid', 'Paid'],
        ],

        [
            'parent' => 'RTA Saliks',
            'slug' => 'rta_saliks',
            'submodules' => ['Salik', 'Payment'],
        ],

        ['parent' => 'Visa Expense', 'slug' => 'visa_expense', 'submodules' => []],
        ['parent' => 'License Expense', 'slug' => 'license_expense', 'submodules' => []],
        ['parent' => 'Legal Case', 'slug' => 'legal_case', 'submodules' => []],
        ['parent' => 'Passport Handover', 'slug' => 'passport_handover', 'submodules' => []],
        ['parent' => 'Expenses', 'slug' => 'expenses', 'submodules' => []],
        ['parent' => 'Vat', 'slug' => 'vat', 'submodules' => []],

        [
            'parent' => 'Leasing Companies',
            'slug' => 'leasing_companies',
            'submodules' => [
                'Company',
                'Invoices',
                'Payments',
                'Documents',
                'Ledger',
            ],
        ],

        [
            'parent' => 'Garages',
            'slug' => 'garages',
            'submodules' => [
                'Garage',
                'Maintenance',
                'Customers',
                'Payments',
                'Documents',
                'Ledger',
            ],
        ],

        [
            'parent' => 'Suppliers',
            'slug' => 'suppliers',
            'submodules' => [
                'Supplier',
                'Purchase Order',
                'Invoices',
                'Payments',
                'Documents',
                'Ledger',
            ],
        ],

        ['parent' => 'Assets', 'slug' => 'assets', 'submodules' => []],

        [
            'parent' => 'Agreements',
            'slug' => 'agreements',
            'submodules' => [],
        ],

        ['parent' => 'Documents', 'slug' => 'documents', 'submodules' => []],
        ['parent' => 'Vouchers', 'slug' => 'vouchers', 'submodules' => []],

        [
            'parent' => 'Accounts',
            'slug' => 'accounts',
            'submodules' => ['COA', 'Ledger'],
        ],

        [
            'parent' => 'Settings',
            'slug' => 'settings',
            'submodules' => [
                'Company Setting',
                'Departments',
                'Branches',
                'Users',
                'Roles',
                'Activity Logs',
                'Recycle Bin',
                'Email',
            ],
        ],

        ['parent' => 'Dropdown', 'slug' => 'dropdown', 'submodules' => []],
    ],

    'additional_permissions' => [
        [
            'parent' => 'Settings',
            'permissions' => [
                'gn_settings',
                'trash_view',
                'trash_restore',
                'trash_force_delete',
            ],
        ],
    ],
];
