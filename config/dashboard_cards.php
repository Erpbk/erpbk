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
];
