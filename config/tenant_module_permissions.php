<?php

/**
 * Default ERP module permissions for each tenant database (Spatie).
 *
 * Parent `name` is the root permission row (Permissions UI module).
 * - With `submodules`: leaves are `{slug}_{submodule_slug}_{view|create|edit|delete}`
 *   (see App\Support\PermissionTreeBuilder).
 * - Submodule entries may be strings or arrays:
 *   ['name' => 'COD Voucher', 'extra_actions' => ['upload']]
 *   which also creates `{slug}_{submodule_slug}_upload`.
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
                [
                    'name' => 'Payments',
                    'extra_actions' => ['upload', 'approve', 'reject'],
                ],
                'Activities',
                'Live Activities',
                'Report',
                'Documents',
                'Timeline',
                'History',
                [
                    'name' => 'COD Voucher',
                    'extra_actions' => ['upload', 'approve', 'reject'],
                ],
                [
                    'name' => 'Penalty Voucher',
                    'extra_actions' => ['upload', 'approve', 'reject'],
                ],
                [
                    'name' => 'Incentive Voucher',
                    'extra_actions' => ['upload', 'approve', 'reject'],
                ],
                [
                    'name' => 'Vendor Charges Voucher',
                    'extra_actions' => ['upload', 'approve', 'reject'],
                ],
                [
                    'name' => 'Advance Loan Voucher',
                    'extra_actions' => ['upload', 'approve', 'reject'],
                ],
                [
                    'name' => 'Import Vouchers',
                    'extra_actions' => ['upload'],
                ],
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

        [
            'parent' => 'Visa Expense',
            'slug' => 'visa_expense',
            'submodules' => [
                'Account Creation',
                'Entries',
                'Installments',
            ],
        ],
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
        [
            'parent' => 'Vouchers',
            'slug' => 'vouchers',
            'submodules' => [],
            // Journal / funds-transfer document attach (Role Permissions → Upload column).
            'extras' => ['upload'],
        ],
        [
            'parent' => 'Voucher Signature Assignment',
            'slug' => 'voucher_signature_assignment',
            'submodules' => [],
        ],

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
                'Delete Requests',
                'Voucher Approvals',
                'Email',
            ],
        ],

        ['parent' => 'Dropdown', 'slug' => 'dropdown', 'submodules' => []],

        [
            'parent' => 'Voucher Approval',
            'slug' => 'voucher_approval',
            'submodules' => [],
            'extras' => ['force_approve', 'reopen', 'revise'],
        ],
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
        [
            'parent' => 'Voucher Approval',
            // Typed approve/reject for non-rider voucher codes (riders use submodule extra_actions).
            'permissions' => [
                'voucher_approval_force_approve',
                'voucher_approval_reopen',
                'voucher_approval_revise',
                'voucher_approval_jv_approve',
                'voucher_approval_jv_reject',
                'voucher_approval_trf_approve',
                'voucher_approval_trf_reject',
                'voucher_approval_pv_approve',
                'voucher_approval_pv_reject',
                'voucher_approval_rv_approve',
                'voucher_approval_rv_reject',
                'voucher_approval_exp_approve',
                'voucher_approval_exp_reject',
                'voucher_approval_lv_approve',
                'voucher_approval_lv_reject',
                'voucher_approval_vl_approve',
                'voucher_approval_vl_reject',
                'voucher_approval_le_approve',
                'voucher_approval_le_reject',
                'voucher_approval_vr_approve',
                'voucher_approval_vr_reject',
                'voucher_approval_rfv_approve',
                'voucher_approval_rfv_reject',
                'voucher_approval_sv_approve',
                'voucher_approval_sv_reject',
                'voucher_approval_gv_approve',
                'voucher_approval_gv_reject',
                'voucher_approval_ri_approve',
                'voucher_approval_ri_reject',
                'voucher_approval_vp_approve',
                'voucher_approval_vp_reject',
                'voucher_approval_vv_approve',
                'voucher_approval_vv_reject',
                'voucher_approval_bl_approve',
                'voucher_approval_bl_reject',
                'voucher_approval_fav_approve',
                'voucher_approval_fav_reject',
                'voucher_approval_fdv_approve',
                'voucher_approval_fdv_reject',
                'voucher_approval_il_approve',
                'voucher_approval_il_reject',
            ],
        ],
    ],
];
