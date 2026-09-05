<?php

/**
 * ERP module registry (settings panel + top bar integration).
 *
 * Keep assignable / visibility parents aligned with config/company_modules.php.
 *
 * @see config/top_bar_filters.php for top bar filter configuration per module.
 * @see App\Support\ErpModuleRegistry
 */
return [

    'modules' => [
        'dashboard' => 'Dashboard',
        'recycle_bin' => 'Recycle Bin',
        'cash_banks' => 'Cash & Banks',
        'loans' => 'Bank Loans',
        'employees' => 'Employees',
        'attendance' => 'Attendance',
        'items' => 'Items',
        'leads' => 'Leads',
        'customers' => 'Customers',
        'customer_invoices' => 'Customer Invoices',
        'vendors' => 'Vendors',
        'recruiters' => 'Recruiters',
        'bikes' => 'Bikes',
        'bike_on_rent' => 'Bike on rent',
        'sims' => 'Sims',
        'fuel_cards' => 'Fuel Cards',
        'rta_fines' => 'RTA Fines',
        'rta_saliks' => 'RTA Saliks',
        'inventory' => 'Inventory',
        'visa_expense' => 'Visa Expense',
        'installments' => 'Installments',
        'license_expense' => 'License Expense',
        'legal_case' => 'Legal Case',
        'passport_handover' => 'Passport Handover',
        'rider_inventory' => 'Rider Inventory',
        'expenses' => 'Expenses',
        'leasing_companies' => 'Leasing Companies',
        'garages' => 'Garages',
        'garages_customers' => 'Garage Customers',
        'supplier' => 'Supplier',
        'assets' => 'Assets',
        'agreements' => 'Agreements',
        'documents' => 'Documents',
        'cheques' => 'Cheques',
        'riders' => 'Riders',
        'accounts' => 'Accounts',
        'vouchers' => 'Vouchers',
        'vat' => 'VAT',
    ],

];
