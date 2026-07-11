<?php

/**
 * Dashboard statistic cards: counts use company_table() (tenant-scoped).
 *
 * filter_query: query string params applied when linking to Active / Inactive listing views.
 */
return [
    'vendors' => [
        'label' => 'Vendors',
        'icon' => 'ti-building-store',
        'table' => 'vendors',
        'route' => 'vendors.index',
        'count_strategy' => 'numeric_status',
        'filter_query' => [
            'active' => ['list_status' => 'active'],
            'inactive' => ['list_status' => 'inactive'],
        ],
    ],
    'customers' => [
        'label' => 'Customers',
        'icon' => 'ti-users',
        'table' => 'customers',
        'route' => 'customers.index',
        'count_strategy' => 'numeric_status',
        'filter_query' => [
            'active' => ['list_status' => 'active'],
            'inactive' => ['list_status' => 'inactive'],
        ],
    ],
    'riders' => [
        'label' => 'Riders',
        'icon' => 'ti-user-star',
        'table' => 'riders',
        'route' => 'riders.index',
        'count_strategy' => 'numeric_status',
        'filter_query' => [
            'active' => ['rider_status' => 'active'],
            'inactive' => ['rider_status' => 'inactive'],
        ],
    ],
    'bikes' => [
        'label' => 'Bikes',
        'icon' => 'ti-motorbike',
        'table' => 'bikes',
        'route' => 'bikes.index',
        'count_strategy' => 'numeric_status',
        'filter_query' => [
            'active' => ['bike_top_wh' => 'active'],
            'inactive' => ['bike_top_wh' => 'inactive'],
        ],
    ],
    'sims' => [
        'label' => 'Sims',
        'icon' => 'ti-device-sim',
        'table' => 'sims',
        'route' => 'sims.index',
        'count_strategy' => 'sim_char_status',
        'filter_query' => [
            'active' => ['list_status' => 'active'],
            'inactive' => ['list_status' => 'inactive'],
        ],
    ],
    'attendance_employees' => [
        'label' => 'Employee Attendance',
        'icon' => 'ti-users',
        'table' => 'attendance',
        'route' => 'attendance.index',
        'count_strategy' => 'attendance_today_present_absent',
        'attendance_ref_type' => 'employee',
        'stat_labels' => [
            'active' => 'Present today',
            'inactive' => 'Absent today',
        ],
    ],
    'attendance_riders' => [
        'label' => 'Rider Attendance',
        'icon' => 'ti-user-star',
        'table' => 'attendance',
        'route' => 'attendance.index',
        'count_strategy' => 'attendance_today_present_absent',
        'attendance_ref_type' => 'rider',
        'stat_labels' => [
            'active' => 'Present today',
            'inactive' => 'Absent today',
        ],
    ],
    'documents' => [
        'label' => 'Documents',
        'icon' => 'ti-files',
        'table' => 'files',
        'route' => 'files.index',
        'count_strategy' => 'documents_expiry_stats',
        'document_expiry_days' => 10,
        'stat_labels' => [
            'active' => 'Expiring within 10 days',
            'inactive' => 'Expired',
        ],
    ],
    'loans' => [
        'label' => 'Bank Loans',
        'icon' => 'ti-currency-dollar',
        'table' => 'loans',
        'route' => 'loans.index',
        'count_strategy' => 'loan_active_closed',
        'filter_query' => [
            'active' => ['status' => 'active'],
            'inactive' => ['status' => 'closed'],
        ],
        'stat_labels' => [
            'active' => 'Active Loans',
            'inactive' => 'Closed Loans',
        ],
        'stat_routes' => [
            'active' => 'loans.index',
            'inactive' => 'loans.index',
        ],
    ],
];
