<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Voucher-Capable Modules
    |--------------------------------------------------------------------------
    | All modules are assignable to voucher types except: Documents, Accounts,
    | Ledger, Items, Leads, Activities, Live Activities, and Rider Report.
    | Keys must match menu_labels and stay stable (stored in DB).
    */
    'modules' => [
        'cash_banks'           => 'Cash & Banks',
        'employees'            => 'Employees',
        'attendance'           => 'Attendance',
        'garage_items'         => 'Garage Items',
        'customers'            => 'Customers',
        'vendors'              => 'Vendors',
        'recruiters'           => 'Recruiters',
        'riders'               => 'Riders',
        'riders_list'          => 'Riders List',
        'invoices'             => 'Invoices',
        'bikes'                => 'Bikes',
        'bike_list'            => 'Bike List',
        'bike_on_rent'         => 'Bike on rent',
        'bike_rent_customers'  => 'Bike on rent — Customers',
        'maintenance'          => 'Maintenance',
        'cheques'              => 'Cheques',
        'sims'                 => 'Sims',
        'fuel_cards'           => 'Fuel Cards',
        'rta_fines'            => 'RTA Fines',
        'rta_saliks'           => 'RTA Saliks',
        'inventory'            => 'Inventory',
        'visa_expense'         => 'Visa Expense',
        'visa_status_types'    => 'Visa Status Types',
        'expenses'             => 'Expenses',
        'leasing_companies'    => 'Leasing Companies',
        'leasing_companies_list' => 'Leasing Companies List',
        'leasing_invoices'     => 'Invoices',
        'garages'              => 'Garages',
        'supplier'             => 'Supplier',
        'suppliers'            => 'Suppliers',
        'supplier_invoices'    => 'Supplier Invoices',
        'assets'              => 'Assets',
        'vouchers'             => 'Vouchers',
        'vat'                  => 'VAT',
    ],
];
