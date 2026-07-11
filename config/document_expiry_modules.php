<?php

/**
 * Maps `files.type` values to ERP modules, visibility keys, and view permissions.
 *
 * @see App\Support\DocumentExpiryDashboard
 */
return [
    'documents' => [
        'label' => 'Documents',
        'visibility' => 'documents',
        'permissions' => ['documents_view', 'documents_create', 'documents_edit', 'documents_delete'],
        'route' => 'files.index',
    ],
    'rider' => [
        'label' => 'Riders',
        'visibility' => 'riders',
        'permissions' => ['riders_rider_view', 'riders_documents_view'],
        'route' => 'rider.document',
        'route_param' => 'id',
    ],
    'employee' => [
        'label' => 'Employees',
        'visibility' => 'employees',
        'permissions' => ['employees_employee_view', 'employees_document_view'],
        'route' => 'employees.show',
        'route_param' => 'employee',
    ],
    'bike' => [
        'label' => 'Bikes',
        'visibility' => 'bikes',
        'permissions' => ['bikes_bike_view', 'bikes_documents_view'],
        'route' => 'files.index',
        'route_query' => ['type' => 'bike'],
    ],
    'customer' => [
        'label' => 'Customers',
        'visibility' => 'customers',
        'permissions' => ['customers_customer_view', 'customers_documents_view'],
        'route' => 'customers.index',
    ],
    'bank' => [
        'label' => 'Banks',
        'visibility' => 'cash_banks',
        'permissions' => ['cash_&_banks_banks_view'],
        'route' => 'banks.index',
    ],
    'supplier' => [
        'label' => 'Suppliers',
        'visibility' => 'supplier',
        'permissions' => ['suppliers_supplier_view', 'suppliers_documents_view'],
        'route' => 'suppliers.document',
        'route_param' => 'id',
    ],
    '3' => [
        'label' => 'Suppliers',
        'visibility' => 'supplier',
        'permissions' => ['suppliers_supplier_view', 'suppliers_documents_view'],
        'route' => 'suppliers.document',
        'route_param' => 'id',
    ],
    'rentCompany' => [
        'label' => 'Bike rent companies',
        'visibility' => 'bike_on_rent',
        'permissions' => ['bike_on_rent_customers_view', 'bike_on_rent_documents_view'],
        'route' => 'bike_rent_companies.index',
    ],
    'leasing_company' => [
        'label' => 'Leasing companies',
        'visibility' => 'leasing_companies',
        'permissions' => ['leasing_companies_company_view', 'leasing_companies_documents_view'],
        'route' => 'leasingCompanies.index',
    ],
    'vendor' => [
        'label' => 'Vendors',
        'visibility' => 'vendors',
        'permissions' => ['vendors_view'],
        'route' => 'vendors.index',
    ],
    'cheque' => [
        'label' => 'Cheques',
        'visibility' => 'cheques',
        'permissions' => ['cash_&_banks_cheques_view'],
        'route' => 'cheques.index',
    ],
    'recruiter' => [
        'label' => 'Recruiters',
        'visibility' => 'recruiters',
        'permissions' => ['recruiters_view'],
        'route' => 'recruiters.index',
    ],
    'garage' => [
        'label' => 'Garages',
        'visibility' => 'garages',
        'permissions' => ['garages_garage_view', 'garages_documents_view'],
        'route' => 'garages.index',
    ],

    'bike_registration' => [
        'label' => 'Bike registration',
        'visibility' => 'bike_registration',
        'permissions' => ['bikes_registration_view'],
        'source' => 'bike_registrations',
        'route' => 'BikeRegistration.edit',
        'route_param' => 'id',
    ],
];
