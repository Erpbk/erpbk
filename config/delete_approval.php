<?php

/**
 * Centralized delete-approval workflow configuration.
 *
 * Soft-delete modules listed here are eligible for pending-deletion
 * interception. Keys align with the Recycle Bin module keys.
 */
return [
    'enabled' => env('DELETE_APPROVAL_ENABLED', true),

    /**
     * Administrator / Super Admin soft-delete immediately (no approval queue).
     * Default false: every delete creates a Delete Request for admin approval.
     */
    'admins_bypass' => env('DELETE_APPROVAL_ADMINS_BYPASS', false),

    /**
     * Optional reason field name accepted on delete requests (POST/JSON).
     */
    'reason_input' => 'delete_reason',

    /**
     * Module registry: key => [model, name, display_columns, show_route]
     * show_route is used to open the record exactly as in its module.
     */
    'modules' => [
        'banks' => [
            'model' => \App\Models\Banks::class,
            'name' => 'Banks',
            'display_columns' => ['name', 'account_no', 'branch'],
            'show_route' => 'banks.show',
        ],
        'accounts' => [
            'model' => \App\Models\Accounts::class,
            'name' => 'Accounts',
            'display_columns' => ['account_code', 'name', 'account_type'],
            'show_route' => 'accounts.show',
        ],
        'customers' => [
            'model' => \App\Models\Customers::class,
            'name' => 'Customers',
            'display_columns' => ['name', 'company_name', 'contact_number'],
            'show_route' => 'customers.show',
        ],
        'vendors' => [
            'model' => \App\Models\Vendors::class,
            'name' => 'Vendors',
            'display_columns' => ['name', 'email', 'contact_number'],
            'show_route' => 'vendors.show',
        ],
        'suppliers' => [
            'model' => \App\Models\Supplier::class,
            'name' => 'Suppliers',
            'display_columns' => ['name', 'email', 'contact_number'],
            'show_route' => 'suppliers.show',
        ],
        'leasing_companies' => [
            'model' => \App\Models\LeasingCompanies::class,
            'name' => 'Leasing Companies',
            'display_columns' => ['name', 'contact_person', 'contact_number'],
            'show_route' => 'leasingCompanies.show',
        ],
        'garages' => [
            'model' => \App\Models\Garages::class,
            'name' => 'Garages',
            'display_columns' => ['name', 'contact_person', 'contact_number'],
            'show_route' => 'garages.show',
        ],
        'recruiters' => [
            'model' => \App\Models\Recruiters::class,
            'name' => 'Recruiters',
            'display_columns' => ['name', 'email', 'contact_number'],
            'show_route' => 'recruiters.show',
        ],
        'riders' => [
            'model' => \App\Models\Riders::class,
            'name' => 'Riders',
            'display_columns' => ['rider_id', 'name', 'personal_contact'],
            'show_route' => 'riders.show',
        ],
        'bikes' => [
            'model' => \App\Models\Bikes::class,
            'name' => 'Bikes',
            'display_columns' => ['plate', 'model', 'chassis_number'],
            'show_route' => 'bikes.show',
        ],
        'sims' => [
            'model' => \App\Models\Sims::class,
            'name' => 'SIM Cards',
            'display_columns' => ['number', 'company', 'status'],
            'show_route' => 'sims.show',
        ],
        'sim_companies' => [
            'model' => \App\Models\SimCompany::class,
            'name' => 'SIM Companies',
            'display_columns' => ['name', 'email', 'company_contact'],
            'show_route' => 'simCompanies.show',
        ],
        'bike_rent_companies' => [
            'model' => \App\Models\BikeRentCompany::class,
            'name' => 'Bike on rent — Customers',
            'display_columns' => ['name', 'email', 'company_contact'],
            'show_route' => 'bikeRentCompanies.show',
        ],
        'fuel_companies' => [
            'model' => \App\Models\FuelCompany::class,
            'name' => 'Fuel Companies',
            'display_columns' => ['name', 'email', 'company_contact'],
            'show_route' => 'fuelCompanies.show',
        ],
        'items' => [
            'model' => \App\Models\Items::class,
            'name' => 'Items',
            'display_columns' => ['name', 'price', 'cost'],
            'show_route' => 'items.show',
        ],
        'rider_inventory_assignments' => [
            'model' => \App\Models\RiderInventoryAssignment::class,
            'name' => 'Rider Inventory Assignments',
            'display_columns' => ['id', 'rider_id', 'status', 'amount'],
            'show_route' => null,
        ],
        'rider_invoices' => [
            'model' => \App\Models\RiderInvoices::class,
            'name' => 'Rider Invoices',
            'display_columns' => ['id', 'rider_id', 'billing_month', 'total_amount', 'status'],
            'show_route' => 'riderInvoices.show',
        ],
        'rta_fines' => [
            'model' => \App\Models\RtaFines::class,
            'name' => 'RTA Fines',
            'display_columns' => ['id', 'rider_id', 'billing_month', 'ticket_no', 'amount', 'status'],
            'show_route' => 'rtaFines.show',
        ],
        'salik' => [
            'model' => \App\Models\salik::class,
            'name' => 'Salik',
            'display_columns' => ['id', 'rider_id', 'billing_month', 'ticket_no', 'amount', 'status'],
            'show_route' => 'salik.show',
        ],
        'vouchers' => [
            'model' => \App\Models\Vouchers::class,
            'name' => 'Vouchers',
            'display_columns' => ['id', 'trans_code', 'trans_date', 'billing_month', 'amount', 'status'],
            'show_route' => 'vouchers.show',
        ],
        'leasing_company_invoices' => [
            'model' => \App\Models\LeasingCompanyInvoice::class,
            'name' => 'Leasing Company Invoices',
            'display_columns' => ['id', 'invoice_number', 'billing_month', 'total_amount', 'status'],
            'show_route' => 'leasingCompanyInvoices.show',
        ],
        'sim_invoices' => [
            'model' => \App\Models\SimInvoice::class,
            'name' => 'SIM Invoices',
            'display_columns' => ['invoice_number', 'reference_number', 'billing_month', 'total_amount', 'status'],
            'show_route' => 'simInvoices.show',
        ],
        'supplier_invoices' => [
            'model' => \App\Models\SupplierInvoices::class,
            'name' => 'Supplier Invoices',
            'display_columns' => ['inv_id', 'billing_month', 'total_amount', 'status'],
            'show_route' => 'supplierInvoices.show',
        ],
        'loans' => [
            'model' => \App\Models\Loan::class,
            'name' => 'Bank Loans',
            'display_columns' => ['loan_number', 'bank_name', 'agreement_ref', 'status'],
            'show_route' => 'loans.show',
        ],
        'employees' => [
            'model' => \App\Models\Employee::class,
            'name' => 'Employees',
            'display_columns' => ['employee_id', 'name', 'email'],
            'show_route' => 'employees.show',
        ],
        'fixed_assets' => [
            'model' => \App\Models\FixedAsset::class,
            'name' => 'Fixed Assets',
            'display_columns' => ['asset_code', 'name', 'status'],
            'show_route' => 'fixed-assets.show',
        ],
        'cheques' => [
            'model' => \App\Models\Cheques::class,
            'name' => 'Cheques',
            'display_columns' => ['cheque_number', 'amount', 'status'],
            'show_route' => 'cheques.show',
        ],
        'visa_expenses' => [
            'model' => \App\Models\visa_expenses::class,
            'name' => 'Visa Expenses',
            'display_columns' => ['id', 'visa_status', 'billing_month', 'amount', 'payment_status'],
            'show_route' => 'VisaExpense.viewvoucher',
        ],
        'license_expenses' => [
            'model' => \App\Models\license_expenses::class,
            'name' => 'License Expenses',
            'display_columns' => ['id', 'license_status', 'billing_month', 'amount', 'payment_status'],
            'show_route' => 'LicenseExpense.viewvoucher',
        ],
        'visa_installment_plans' => [
            'model' => \App\Models\visa_installment_plan::class,
            'name' => 'Visa Installment Plans',
            'display_columns' => ['id', 'billing_month', 'amount', 'status', 'reference_number'],
            'show_route' => null,
        ],
        'license_installment_plans' => [
            'model' => \App\Models\license_installment_plan::class,
            'name' => 'License Installment Plans',
            'display_columns' => ['id', 'billing_month', 'amount', 'status', 'reference_number'],
            'show_route' => null,
        ],
    ],
];
