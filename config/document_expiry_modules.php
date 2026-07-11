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
        'permissions' => ['rider_view', 'rider_document', 'riders_document'],
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
        'permissions' => ['bike_view', 'bike_document', 'files_view', 'bikes_view'],
        'route' => 'files.index',
        'route_query' => ['type' => 'bike'],
    ],
    'customer' => [
        'label' => 'Customers',
        'visibility' => 'customers',
        'permissions' => ['customer_view'],
        'route' => 'customers.index',
    ],
    'bank' => [
        'label' => 'Banks',
        'visibility' => 'cash_banks',
        'permissions' => ['bank_view'],
        'route' => 'banks.index',
    ],
    'supplier' => [
        'label' => 'Suppliers',
        'visibility' => 'supplier',
        'permissions' => ['supplier_view'],
        'route' => 'suppliers.document',
        'route_param' => 'id',
    ],
    '3' => [
        'label' => 'Suppliers',
        'visibility' => 'supplier',
        'permissions' => ['supplier_view'],
        'route' => 'suppliers.document',
        'route_param' => 'id',
    ],
    'rentCompany' => [
        'label' => 'Bike rent companies',
        'visibility' => 'bike_on_rent',
        'permissions' => ['bike_view', 'bike_rent_edit'],
        'route' => 'bike_rent_companies.index',
    ],
    'leasing_company' => [
        'label' => 'Leasing companies',
        'visibility' => 'leasing_companies',
        'permissions' => ['leasing_view'],
        'route' => 'leasingCompanies.index',
    ],
    'vendor' => [
        'label' => 'Vendors',
        'visibility' => 'vendors',
        'permissions' => ['vendor_view'],
        'route' => 'vendors.index',
    ],
    'cheque' => [
        'label' => 'Cheques',
        'visibility' => 'cheques',
        'permissions' => ['cheques_view'],
        'route' => 'cheques.index',
    ],
    'recruiter' => [
        'label' => 'Recruiters',
        'visibility' => 'recruiters',
        'permissions' => ['recruiter_view'],
        'route' => 'recruiters.index',
    ],
    'garage' => [
        'label' => 'Garages',
        'visibility' => 'garages',
        'permissions' => ['garage_view'],
        'route' => 'garages.index',
    ],

    'bike_registration' => [
        'label' => 'Bike registration',
        'visibility' => 'bike_registration',
        'permissions' => ['bike_registration_view'],
        'source' => 'bike_registrations',
        'route' => 'BikeRegistration.edit',
        'route_param' => 'id',
    ],
];
