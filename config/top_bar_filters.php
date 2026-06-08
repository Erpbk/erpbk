<?php

/**
 * Centralized ERP top-bar filter registry.
 *
 * Each module declares how categories/options map to database columns and request params.
 * Add a module here to enable Top Bar Settings + dynamic query filtering without hardcoded queries.
 */
return [

    'default_date_mode' => 'exact',

    'filter_types' => [
        'exact_date' => 'Exact date (calendar day)',
        'upcoming_date' => 'Upcoming / on or after date',
        'overdue_date' => 'Overdue / before today',
        'date_range' => 'Date range (request from/to)',
        'exact_match' => 'Exact column value',
        'status' => 'Status / select value',
        'option_fk' => 'Filter by assigned top option (FK on record)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu label aliases – saving one key updates related sidebar keys
    |--------------------------------------------------------------------------
    */
    'menu_label_aliases' => [
        'riders' => ['riders_list'],
        'rider_settings' => ['riders'],
        'bikes' => ['bike_list'],
        'bike_list' => ['bikes', 'bike_settings'],
        'bike_settings' => ['bike_list', 'bikes'],
        'employees' => ['employee_settings'],
        'employee_settings' => ['employees'],
        'accounts' => ['account_fields', 'chart_of_accounts'],
        'account_fields' => ['accounts'],
        'vouchers' => ['voucher_settings'],
        'voucher_settings' => ['vouchers'],
        'vat' => ['vat_settings', 'vat_ledger'],
        'vat_settings' => ['vat'],
        'garages' => ['garage_list'],
        'garage_list' => ['garages'],
        'rta_fines_unpaid' => ['rta_fines_tickets'],
        'rta_fines_tickets' => ['rta_fines_unpaid'],
        'cash_banks' => ['cheques', 'payments', 'receipts'],
        'cheques' => ['cash_banks'],
    ],

    'modules' => [

        'cheques' => [
            'storage' => 'dedicated',
            'source_table' => 'cheques',
            'category_model' => \App\Models\ChequeTopCategory::class,
            'option_model' => \App\Models\ChequeTopOption::class,
            'column_attribute' => 'cheque_column',
            'filter_strategy' => 'column',
            'listing_default_statuses' => ['cleared', 'pending'],
            'request' => [
                'option_id' => 'cheque_top_option_id',
                'status' => 'cheque_top_status',
                'filter_mode' => 'cheque_top_filter_mode',
                'date_from' => 'cheque_top_date_from',
                'date_to' => 'cheque_top_date_to',
            ],
            'status_filters' => [
                'cleared' => ['column' => 'status', 'operator' => '=', 'value' => 'Cleared'],
                'pending' => ['column' => 'status', 'operator' => '!=', 'value' => 'Cleared'],
            ],
            'date_columns' => [
                'issue_date',
                'cheque_date',
                'cleared_date',
                'returned_date',
                'stop_payment_date',
                'due_date',
                'billing_month',
            ],
            'column_modes' => [
                'cheque_date' => 'upcoming_date',
                'due_date' => 'upcoming_date',
            ],
            'settings_routes' => 'settings-panel.cheques-settings',
        ],

        'riders' => [
            'storage' => 'dedicated',
            'source_table' => 'riders',
            'category_model' => \App\Models\RiderTopCategory::class,
            'option_model' => \App\Models\RiderTopOption::class,
            'column_attribute' => 'rider_column',
            'filter_strategy' => 'option_fk',
            'fk_column' => 'rider_top_option_id',
            'listing_default_statuses' => ['active', 'inactive'],
            'request' => [
                'option_id' => 'rider_top_option_id',
                'status' => 'rider_status',
            ],
            'settings_routes' => 'settings-panel.rider-settings',
        ],

        'bike_list' => [
            'storage' => 'dedicated',
            'source_table' => 'bikes',
            'category_model' => \App\Models\BikeTopCategory::class,
            'option_model' => \App\Models\BikeTopOption::class,
            'column_attribute' => 'bike_column',
            'filter_strategy' => 'option_fk',
            'fk_column' => 'bike_top_option_id',
            'listing_default_statuses' => ['active', 'inactive'],
            'request' => [
                'option_id' => 'bike_top_option_id',
                'status' => 'bike_top_wh',
            ],
            'settings_routes' => 'settings-panel.bike-settings',
        ],

        'employees' => [
            'storage' => 'dedicated',
            'source_table' => 'employees',
            'category_model' => \App\Models\EmployeeTopCategory::class,
            'option_model' => \App\Models\EmployeeTopOption::class,
            'column_attribute' => 'employee_column',
            'filter_strategy' => 'column',
            'listing_default_statuses' => ['active', 'inactive', 'on_leave'],
            'request' => [
                'option_id' => 'top_option_id',
                'status' => 'employee_status',
            ],
            'settings_routes' => 'settings-panel.employee-settings',
        ],

        'garages' => [
            'storage' => 'generic',
            'source_table' => 'garages',
            'filter_strategy' => 'column',
            'request' => [
                'option_id' => 'top_option_id',
                'filter_mode' => 'top_filter_mode',
                'date_from' => 'top_date_from',
                'date_to' => 'top_date_to',
            ],
        ],

        'visa_expense' => [
            'storage' => 'generic',
            'filter_strategy' => 'column',
            'request' => [
                'option_id' => 'top_option_id',
                'filter_mode' => 'top_filter_mode',
                'date_from' => 'top_date_from',
                'date_to' => 'top_date_to',
            ],
        ],

        'legal_case' => [
            'storage' => 'generic',
            'filter_strategy' => 'column',
            'request' => [
                'option_id' => 'top_option_id',
                'filter_mode' => 'top_filter_mode',
                'date_from' => 'top_date_from',
                'date_to' => 'top_date_to',
            ],
        ],

        'expenses' => [
            'storage' => 'generic',
            'source_table' => 'expenses',
            'filter_strategy' => 'column',
            'request' => [
                'option_id' => 'top_option_id',
                'filter_mode' => 'top_filter_mode',
                'date_from' => 'top_date_from',
                'date_to' => 'top_date_to',
            ],
        ],

        'customers' => [
            'storage' => 'generic',
            'source_table' => 'customers',
            'filter_strategy' => 'column',
            'request' => [
                'option_id' => 'top_option_id',
                'filter_mode' => 'top_filter_mode',
                'date_from' => 'top_date_from',
                'date_to' => 'top_date_to',
            ],
        ],

        'vendors' => [
            'storage' => 'generic',
            'source_table' => 'vendors',
            'filter_strategy' => 'column',
            'request' => [
                'option_id' => 'top_option_id',
                'filter_mode' => 'top_filter_mode',
                'date_from' => 'top_date_from',
                'date_to' => 'top_date_to',
            ],
        ],

        'sims' => [
            'storage' => 'generic',
            'source_table' => 'sims',
            'filter_strategy' => 'column',
            'request' => [
                'option_id' => 'top_option_id',
                'filter_mode' => 'top_filter_mode',
                'date_from' => 'top_date_from',
                'date_to' => 'top_date_to',
            ],
        ],

        'rta_fines_unpaid' => [
            'storage' => 'generic',
            'source_table' => 'rta_fines',
            'filter_strategy' => 'column',
            'scoped_status' => 'unpaid',
            'listing_default_statuses' => ['unpaid'],
            'status_filters' => [
                'unpaid' => ['column' => 'status', 'operator' => '=', 'value' => 'unpaid'],
            ],
            'listing_stats' => [
                'unpaid' => ['label' => 'Unpaid', 'icon' => 'ti-clock'],
            ],
            'option_labels' => [
                'unpaid' => 'Unpaid Fines',
            ],
            'request' => [
                'option_id' => 'top_option_id',
                'status' => 'rta_top_status',
                'filter_mode' => 'top_filter_mode',
                'date_from' => 'top_date_from',
                'date_to' => 'top_date_to',
            ],
            'preset_categories' => [
                [
                    'name' => 'Unpaid Fines',
                    'db_column' => 'status',
                    'filter_type' => 'exact_match',
                    'options' => [
                        ['name' => 'unpaid'],
                    ],
                ],
            ],
        ],

        'rta_fines_paid' => [
            'storage' => 'generic',
            'source_table' => 'rta_fines',
            'filter_strategy' => 'column',
            'scoped_status' => 'paid',
            'listing_default_statuses' => ['paid'],
            'status_filters' => [
                'paid' => ['column' => 'status', 'operator' => '=', 'value' => 'paid'],
            ],
            'listing_stats' => [
                'paid' => ['label' => 'Paid', 'icon' => 'ti-circle-check'],
            ],
            'option_labels' => [
                'paid' => 'Paid Fines',
            ],
            'request' => [
                'option_id' => 'top_option_id',
                'status' => 'rta_top_status',
                'filter_mode' => 'top_filter_mode',
                'date_from' => 'top_date_from',
                'date_to' => 'top_date_to',
            ],
            'preset_categories' => [
                [
                    'name' => 'Paid Fines',
                    'db_column' => 'status',
                    'filter_type' => 'exact_match',
                    'options' => [
                        ['name' => 'paid'],
                    ],
                ],
            ],
        ],

    ],

];
