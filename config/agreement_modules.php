<?php

use App\Models\Banks;
use App\Models\BikeRentCompany;
use App\Models\Bikes;
use App\Models\Cheques;
use App\Models\Countries;
use App\Models\Customers;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\FixedAsset;
use App\Models\FuelCards;
use App\Models\FuelCompany;
use App\Models\Garages;
use App\Models\Items;
use App\Models\LeasingCompanies;
use App\Models\legal_cases;
use App\Models\Recruiters;
use App\Models\Riders;
use App\Models\RtaFines;
use App\Models\salik;
use App\Models\SimCompany;
use App\Models\Sims;
use App\Models\Supplier;
use App\Models\Vendors;
use App\Models\visa_expenses;

/**
 * ERP modules that support agreement contracts.
 * Keys must match agreement_categories.assigned_modules and menu module keys.
 *
 * Module assignment checkboxes in Agreement settings use all keys from erp_modules.modules
 * except excluded_from_assignment (see AgreementModuleService::assignableModuleKeys).
 */
return [
    'excluded_from_assignment' => [
        'dashboard',
        'recycle_bin',
        'agreements',
        'documents',
        'accounts',
        'vouchers',
        'vat',
        // Modules that never need agreement templates
        'cash_banks',
        'loans',
        'attendance',
        'items',
        'leads',
        'customer_invoices',
        'rta_fines',
        'rta_saliks',
        'inventory',
        'visa_expense',
        'installments',
        'license_expense',
        'legal_case',
        'expenses',
        'assets',
        'cheques',
        'passport_handover',
        'rider_inventory',
    ],

    'modules' => [
        'riders' => [
            'model' => Riders::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'rider_view'],
            'label_field' => 'name',
            'code_field' => 'rider_id',
            'email_field' => 'email',
        ],
        'employees' => [
            'model' => Employee::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'employees_employee_view'],
            'label_field' => 'name',
            'code_field' => 'employee_id',
            'email_field' => 'company_email',
        ],
        'customers' => [
            'model' => Customers::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'customer_view'],
            'label_field' => 'name',
            'code_field' => 'id',
            'email_field' => 'company_email',
        ],
        'vendors' => [
            'model' => Vendors::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'vendor_view'],
            'label_field' => 'name',
            'code_field' => 'id',
            'email_field' => 'email',
        ],
        'supplier' => [
            'model' => Supplier::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'supplier_view'],
            'label_field' => 'name',
            'code_field' => 'id',
            'email_field' => 'email',
        ],
        'bikes' => [
            'model' => Bikes::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'bikes_view', 'bike_view', 'bikes_bike_view', 'bikes_bike_edit'],
            'label_field' => 'plate',
            'code_field' => 'bike_code',
            'email_field' => 'email',
        ],
        'bike_on_rent' => [
            'model' => BikeRentCompany::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'bike_on_rent_customers_view'],
            'label_field' => 'name',
            'code_field' => 'id',
            'email_field' => 'email',
        ],
        'garages_customers' => [
            'model' => BikeRentCompany::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'garages_customers_view'],
            'label_field' => 'name',
            'code_field' => 'id',
            'email_field' => 'email',
        ],
        'visa_expense' => [
            'model' => visa_expenses::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'visaexpense_view'],
            'label_field' => 'detail',
            'code_field' => 'trans_code',
            'email_field' => 'email',
        ],
        'recruiters' => [
            'model' => Recruiters::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'recruiter_view'],
            'label_field' => 'name',
            'code_field' => 'id',
            'email_field' => 'email',
        ],
        'sims' => [
            'model' => Sims::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'sim_view'],
            'label_field' => 'number',
            'code_field' => 'id',
            'email_field' => 'email',
        ],
        'fuel_cards' => [
            'model' => FuelCards::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'fuel_view'],
            'label_field' => 'card_number',
            'code_field' => 'id',
            'email_field' => 'email',
        ],
        'leasing_companies' => [
            'model' => LeasingCompanies::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'leasing_view'],
            'label_field' => 'name',
            'code_field' => 'id',
            'email_field' => 'email',
        ],
        'garages' => [
            'model' => Garages::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'garage_view'],
            'label_field' => 'name',
            'code_field' => 'id',
            'email_field' => 'email',
        ],
        'items' => [
            'model' => Items::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'item_view'],
            'label_field' => 'name',
            'code_field' => 'code',
            'email_field' => 'email',
        ],
        'cheques' => [
            'model' => Cheques::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'cheques_view'],
            'label_field' => 'payee_name',
            'code_field' => 'cheque_number',
            'email_field' => 'email',
        ],
        'legal_case' => [
            'model' => legal_cases::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'legalcase_view'],
            'label_field' => 'detail',
            'code_field' => 'reference_number',
            'email_field' => 'email',
        ],
        'assets' => [
            'model' => FixedAsset::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'asset_view'],
            'label_field' => 'name',
            'code_field' => 'asset_code',
            'email_field' => 'email',
        ],
        'rta_fines' => [
            'model' => RtaFines::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'rtafine_view'],
            'label_field' => 'detail',
            'code_field' => 'ticket_no',
            'email_field' => 'email',
        ],
        'rta_saliks' => [
            'model' => salik::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'salik_view'],
            'label_field' => 'details',
            'code_field' => 'trans_code',
            'email_field' => 'email',
        ],
        'cash_banks' => [
            'model' => Banks::class,
            'permissions' => ['agreements_view', 'agreements_generate', 'agreements_edit', 'bank_view'],
            'label_field' => 'name',
            'code_field' => 'id',
            'email_field' => 'email',
        ],
    ],

    /**
     * Predefined group labels for admin placeholder CRUD.
     *
     * @var list<string>
     */
    'placeholder_groups' => [
        'Personal Information',
        'Address Information',
        'Employment Information',
        'Vehicle Information',
        'System Information',
        'Related',
        'General',
    ],

    /**
     * FK columns that expand into Related: {Label} source options (never offered as raw IDs).
     * Use `by_module` when the same column name maps to different relations per module.
     *
     * @var array<string, array{relation?: string, label?: string, module?: string|null, table?: string|null, model?: class-string<\Illuminate\Database\Eloquent\Model>|null, by_module?: array<string, array{relation: string, label: string, module?: string|null, table?: string|null, model?: class-string<\Illuminate\Database\Eloquent\Model>|null}>}>
     */
    'foreign_key_sources' => [
        'branch_id' => [
            'relation' => 'branch',
            'label' => 'Branch',
            'module' => null,
            'table' => 'branches',
            'model' => \App\Models\Branch::class,
        ],
        'customer_id' => [
            'relation' => 'customer',
            'label' => 'Customer',
            'module' => 'customers',
            'table' => 'customers',
            'model' => Customers::class,
        ],
        'bike_id' => [
            'relation' => 'bike',
            'label' => 'Bike',
            'module' => 'bikes',
            'table' => 'bikes',
            'model' => Bikes::class,
        ],
        'vendor_id' => [
            'relation' => 'vendor',
            'label' => 'Vendor',
            'module' => 'vendors',
            'table' => 'vendors',
            'model' => Vendors::class,
        ],
        'supplier_id' => [
            'relation' => 'supplier',
            'label' => 'Supplier',
            'module' => 'supplier',
            'table' => 'suppliers',
            'model' => Supplier::class,
        ],
        'garage_id' => [
            'relation' => 'garage',
            'label' => 'Garage',
            'module' => 'garages',
            'table' => 'garages',
            'model' => Garages::class,
        ],
        'recruiter_id' => [
            'relation' => 'recruiter',
            'label' => 'Recruiter',
            'module' => 'recruiters',
            'table' => 'recruiters',
            'model' => Recruiters::class,
        ],
        'leasing_company_id' => [
            'relation' => 'leasingCompany',
            'label' => 'Leasing Company',
            'module' => 'leasing_companies',
            'table' => 'leasing_companies',
            'model' => LeasingCompanies::class,
        ],
        'employee_id' => [
            'relation' => 'employee',
            'label' => 'Employee',
            'module' => 'employees',
            'table' => 'employees',
            'model' => Employee::class,
        ],
        'rider_id' => [
            'relation' => 'rider',
            'label' => 'Rider',
            'module' => 'riders',
            'table' => 'riders',
            'model' => Riders::class,
        ],
        'nationality_id' => [
            'relation' => 'nationality',
            'label' => 'Nationality',
            'module' => null,
            'table' => 'countries',
            'model' => Countries::class,
        ],
        'department_id' => [
            'relation' => 'department',
            'label' => 'Department',
            'module' => null,
            'table' => 'departments',
            'model' => Departments::class,
        ],
        'rental_company_id' => [
            'relation' => 'rentalCompany',
            'label' => 'Rental Company',
            'module' => 'bike_on_rent',
            'table' => 'bike_rent_companies',
            'model' => BikeRentCompany::class,
        ],
        'fuel_company_id' => [
            'relation' => 'fuelCompany',
            'label' => 'Fuel Company',
            'module' => null,
            'table' => 'fuel_companies',
            'model' => FuelCompany::class,
        ],
        'lost_rider_id' => [
            'relation' => 'lostRider',
            'label' => 'Lost Rider',
            'module' => 'riders',
            'table' => 'riders',
            'model' => Riders::class,
        ],
        'assign_to' => [
            'relation' => 'assignee',
            'label' => 'Assignee',
            'module' => 'riders',
            'table' => 'riders',
            'model' => Riders::class,
        ],
        'vendor' => [
            'relation' => 'vendors',
            'label' => 'SIM Vendor',
            'module' => 'customers',
            'table' => 'customers',
            'model' => Customers::class,
        ],
        // Same column name, different relations per module
        'company' => [
            'by_module' => [
                'bikes' => [
                    'relation' => 'LeasingCompany',
                    'label' => 'Leasing Company',
                    'module' => 'leasing_companies',
                    'table' => 'leasing_companies',
                    'model' => LeasingCompanies::class,
                ],
                'sims' => [
                    'relation' => 'telecomCompany',
                    'label' => 'Telecom Company',
                    'module' => null,
                    'table' => 'sim_companies',
                    'model' => SimCompany::class,
                ],
            ],
        ],
        // Riders store nationality as FK id; bike rent customers store a plain string.
        'nationality' => [
            'by_module' => [
                'riders' => [
                    'relation' => 'country',
                    'label' => 'Nationality',
                    'module' => null,
                    'table' => 'countries',
                    'model' => Countries::class,
                ],
            ],
        ],
    ],

    /**
     * Columns never treated as expandable FKs / never shown as Related sources.
     *
     * @var list<string>
     */
    'foreign_key_exclude' => [
        'company_id',
        'account_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'parent_branch_id',
        'category_id',
        'status_id',
    ],
];
