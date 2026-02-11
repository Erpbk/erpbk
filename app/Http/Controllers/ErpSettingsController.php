<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ErpSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the central ERP settings page (general settings + sidebar menu labels).
     */
    public function index(Request $request)
    {
        // Default sidebar/menu labels (keys match menu item identifiers for future persistence)
        $menuLabels = [
            'dashboard'           => 'Dashboard',
            'recycle_bin'         => 'Recycle Bin',
            'cash_banks'          => 'Cash & Banks',
            'items'               => 'Items',
            'items_list'          => 'Items List',
            'garage_items'        => 'Garage Items',
            'leads'               => 'Leads',
            'customers'           => 'Customers',
            'vendors'             => 'Vendors',
            'recruiters'          => 'Recruiters',
            'riders'              => 'Riders',
            'riders_list'         => 'Riders List',
            'invoices'            => 'Invoices',
            'activities'          => 'Activities',
            'live_activities'     => 'Live Activities',
            'rider_report'        => 'Rider Report',
            'bikes'               => 'Bikes',
            'bike_list'           => 'Bike List',
            'maintenance_overview'=> 'Maintenance Overview',
            'sims'                => 'Sims',
            'fuel_cards'          => 'Fuel Cards',
            'rta_fines'           => 'RTA Fines',
            'rta_saliks'          => 'RTA Saliks',
            'inventory'           => 'Inventory',
            'visa_expense'        => 'Visa Expense',
            'visa_status_types'   => 'Visa Status Types',
            'expenses'            => 'Expenses',
            'leasing_companies'   => 'Leasing Companies',
            'leasing_companies_list' => 'Leasing Companies List',
            'leasing_invoices'    => 'Invoices',
            'garages'             => 'Garages',
            'supplier'            => 'Supplier',
            'suppliers'           => 'Suppliers',
            'supplier_invoices'   => 'Supplier Invoices',
            'assets'              => 'Assets',
            'documents'           => 'Documents',
            'vouchers'            => 'Vouchers',
            'accounts'            => 'Accounts',
            'chart_of_accounts'   => 'Chart Of Accounts',
            'ledger'              => 'Ledger',
            'user_management'     => 'User Management',
            'users'               => 'Users',
            'roles'               => 'Roles',
            'permissions'         => 'Permissions',
            'activity_logs'       => 'Activity Logs',
            'company_settings'    => 'Company Settings',
            'settings'            => 'Settings',
            'departments'         => 'Departments',
            'dropdowns'           => 'Dropdowns',
            'erp_settings'        => 'ERP Settings',
        ];

        return view('settings.erp_settings', compact('menuLabels'));
    }
}
