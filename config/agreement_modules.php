<?php

use App\Models\Banks;
use App\Models\Bikes;
use App\Models\Cheques;
use App\Models\Customers;
use App\Models\Employee;
use App\Models\FixedAsset;
use App\Models\FuelCards;
use App\Models\Garages;
use App\Models\Items;
use App\Models\LeasingCompanies;
use App\Models\legal_cases;
use App\Models\Recruiters;
use App\Models\Riders;
use App\Models\RtaFines;
use App\Models\salik;
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
];
