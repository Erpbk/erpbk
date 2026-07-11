@php
// Labels are editable in Settings > ERP Module Settings > [Module] > General; same source as ModuleSettingsController
$menuLabels = $menuLabels ?? \App\Models\Settings::getMenuLabels();
$companySlug = request()->route('company_slug') ?? session('company_slug');
$isAdminRoute = request()->routeIs('admin.*');
$isAdminLogin = auth('admin')->check() && $isAdminRoute;
$homeLink = $isAdminLogin
? route('admin.dashboard')
: ($companySlug ? route('home', ['company_slug' => $companySlug]) : url('/'));
@endphp
@if(!$isAdminLogin)
@if(\App\Support\CompanyModuleVisibility::enabled('dashboard'))
<li class="menu-item {{ Route::is('home') || Route::is('/') ? 'active' : '' }}">
  <a href="{{ $companySlug ? route('home', ['company_slug' => $companySlug]) : 'javascript:void(0);' }}" class="menu-link ">
    @include('layouts.partials.module_menu_icon', ['key' => 'dashboard'])
    <div>{{ $menuLabels['dashboard'] ?? 'Dashboard' }}</div>
    {{-- <div class="badge bg-white text-dark rounded-pill ms-auto">2</div>  --}}
  </a>
</li>
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('cash_banks'))
<li class="menu-item {{ Route::is('banks.*') ? 'open' : '' }}  {{ Route::is('bank.*') ? 'open' : '' }} {{ Route::is('cheques.*') ? 'open' : '' }} {{ Route::is('payments.*') ? 'open' : '' }} {{ Route::is('receipts.*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    @include('layouts.partials.module_menu_icon', ['key' => 'cash_banks'])
    <div>{{ $menuLabels['cash_banks'] ?? 'Cash & Banks' }}</div>
  </a>
  <ul class="menu-sub">
    @can('cash_&_banks_banks_view')
    <li class="menu-item {{ Route::is('banks.*') ? 'active' : '' }} {{ Route::is('bank.*') ? 'active' : '' }} ">
      <a href="{{ route('banks.index') }}" class="menu-link">
        <div>{{ $menuLabels['cash_banks'] ?? 'Cash & Banks' }}</div>
      </a>
    </li>
    @endcan
    @can('cash_&_banks_cheques_view')
    <li class="menu-item {{ Route::is('cheques.*') ? 'active' : '' }}">
      <a href="{{ route('cheques.index') }}" class="menu-link">
        <div>{{ $menuLabels['cheques'] ?? 'Cheques' }}</div>
      </a>
    </li>
    @endcan
    @can('cash_&_banks_payments_view')
    <li class="menu-item {{ Route::is('payments.*') ? 'active' : '' }}">
      <a href="{{ route('payments.index') }}" class="menu-link">
        <div>{{ $menuLabels['payments'] ?? 'Payments (Cash-Out)' }}</div>
      </a>
    </li>
    @endcan
    @can('cash_&_banks_receipts_view')
    <li class="menu-item {{ Route::is('receipts.*') ? 'active' : '' }}">
      <a href="{{ route('receipts.index') }}" class="menu-link">
        <div>{{ $menuLabels['receipts'] ?? 'Receipts (Cash-In)' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('loans'))
@can('loans_view')
<li class="menu-item {{ Route::is('loans.*') || Route::is('loan.*') || Route::is('loanInstallments.*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    @include('layouts.partials.module_menu_icon', ['key' => 'loans'])
    <div>{{ $menuLabels['loans'] ?? 'Bank Loans' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('loans.index') || Route::is('loans.show') || Route::is('loans.edit') ? 'active' : '' }}">
      <a href="{{ route('loans.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'loans_list'])
        <div>{{ $menuLabels['loans_list'] ?? 'Loans' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('loans.upcomingInstallments') ? 'active' : '' }}">
      <a href="{{ route('loans.upcomingInstallments') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'loans_upcoming_installments'])
        <div>{{ $menuLabels['loans_upcoming_installments'] ?? 'Upcoming Installments' }}</div>
      </a>
    </li>
  </ul>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('employees'))
@canany(['employees_employee_view', 'employees_attendance_view'])
<li class="menu-item {{ Route::is('employees.*') ? 'open' : '' }} {{ (Route::is('attendance.*') && request('ref_type') === 'employee') || (Route::is('attendance.summary') && request('user_type', 'employee') === 'employee') ? 'open' : '' }} {{ Route::is('employeeInvoices.*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    @include('layouts.partials.module_menu_icon', ['key' => 'employees'])
    <div>{{ $menuLabels['employees'] ?? 'Employees' }}</div>
  </a>
  <ul class="menu-sub">
    @can('employees_employee_view')
    <li class="menu-item {{ Route::is('employees.*') ? 'active' : '' }}">
      <a href="{{ route('employees.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'employees'])
        <div>{{ $menuLabels['employees'] ?? 'Employees' }}</div>
      </a>
    </li>
    @endcan
    @if(\App\Support\CompanyModuleVisibility::enabled('attendance'))
    @can('employees_attendance_view')
    <li class="menu-item {{ (Route::is('attendance.summary') && request('user_type', 'employee') === 'employee') || (Route::is('attendance.index') && request('ref_type') === 'employee') ? 'active' : '' }}">
      <a href="{{ route('attendance.summary', ['user_type' => 'employee']) }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'attendance_summary'])
        {{ $menuLabels['attendance_summary'] ?? 'Attendance' }}
      </a>
    </li>
    @endcan
    @endif
    @can('employees_invoice_view')
    <li class="menu-item {{ Route::is('employeeInvoices.*') ? 'active' : '' }}">
      <a href="{{ route('employeeInvoices.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'invoices'])
        <div>Employee Invoices</div>
      </a>
    </li>
    @endcan
    @can('employees_payment_view')
    <li class="menu-item {{ Route::is('employee.payment') ? 'active' : '' }}">
      <a href="{{ route('employee.payment') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'payments'])
        <div>{{ $menuLabels['payments_sent'] ?? 'Payments Sent' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcanany
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('items'))
@canany(['items_item_view', 'items_inventory_view'])
<li class="menu-item {{ Route::is('items.*') ? 'open' : '' }} {{ Route::is('inventory.*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    @include('layouts.partials.module_menu_icon', ['key' => 'items'])
    <div>{{ $menuLabels['items'] ?? 'Items' }}</div>
  </a>
  <ul class="menu-sub">
    @can('items_item_view')
    <li class="menu-item {{ Route::is('items.*') && !Route::is('garage-items.*') ? 'active' : '' }}">
      <a href="{{ route('items.index') }}" class="menu-link">
        <div>{{ $menuLabels['items_list'] ?? 'Items List' }}</div>
      </a>
    </li>
    @endcan
    @can('items_inventory_view')
    <li class="menu-item {{ Route::is('inventory*') ? 'active' : '' }}">
      <a href="{{ route('inventory.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'inventory'])
        <div>{{ $menuLabels['inventory'] ?? 'Inventory' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcanany
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('leads'))
@can('leads_view')
<li class="menu-item {{ Route::is('riderleads.*') ? 'active' : '' }}">
  <a href="{{ route('riderleads.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'leads'])
    <div>{{ $menuLabels['leads'] ?? 'Leads' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('customers'))
@canany(['customers_customer_view', 'customers_invoices_view', 'customers_payments_view'])
<li class="menu-item {{ (Route::is('customer*') || Route::is('customers*') || Route::is('customer_invoice*') || Route::is('customer_invoices*')) ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    @include('layouts.partials.module_menu_icon', ['key' => 'customers'])
    <div>{{ $menuLabels['customers'] ?? 'Customers' }}</div>
  </a>
  <ul class="menu-sub">
    {{-- Customer List --}}
    @can('customers_customer_view')
    <li class="menu-item {{ Route::is('customers.index') ? 'active' : '' }}">
      <a href="{{ route('customers.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'customer_list'])
        <div>{{ $menuLabels['customer_list'] ?? 'Customer List' }}</div>
      </a>
    </li>
    @endcan
    @can('customers_invoices_view')
    {{-- Invoices --}}
    <li class="menu-item {{ Route::is('customer-invoices*') || Route::is('customer_invoices*') ? 'active' : '' }}">
      <a href="{{ route('customer_invoices.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'customer_invoices'])
        <div>{{ $menuLabels['customer_invoices'] ?? 'Invoices' }}</div>
      </a>
    </li>
    @endcan
    @can('customers_payments_view')
    {{-- Payments Receieved --}}
    <li class="menu-item {{ Route::is('customer.receipts') ? 'active' : '' }}">
      <a href="{{ route('customer.receipts') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'customer_receipts'])
        <div>{{ $menuLabels['customer_receipts'] ?? 'Payments Received' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcanany
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('vendors'))
@can('vendors_view')
<li class="menu-item {{ Route::is('vendors*') ? 'active' : '' }}">
  <a href="{{ route('vendors.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'vendors'])
    <div>{{ $menuLabels['vendors'] ?? 'Vendors' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('recruiters'))
@can('recruiters_view')
<li class="menu-item {{ Route::is('recruiters*') ? 'active' : '' }}">
  <a href="{{ route('recruiters.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'recruiters'])
    <div>{{ $menuLabels['recruiters'] ?? 'Recruiters' }}</div>
  </a>
</li>
@endcan
@endif

@if(\App\Support\CompanyModuleVisibility::enabled('riders'))
@canany(['riders_rider_view', 'riders_invoices_view', 'riders_attendance_view', 'riders_inventory_view', 'riders_payments_view', 'riders_activities_view', 'riders_live_activities_view', 'riders_report_view'])
<li class="menu-item {{ Route::is('riders*') ? 'open' : '' }}
 {{ Route::is('riderInvoices*') ? 'open' : '' }}
 {{ Route::is('riderActivities*') ? 'open' : '' }}
  {{ Route::is('rider.*') ? 'open' : '' }}
 {{ Route::is('reports.rider_report*') ? 'open' : '' }}
 {{ Route::is('reports.rider_monthly_report*') ? 'open' : '' }}
 {{ Route::is('RiderInventory*') ? 'open' : '' }}
 {{ (Route::is('attendance*') && request('ref_type') === 'rider') || (Route::is('attendance.summary') && request('user_type') === 'rider') ? 'open' : '' }}  ">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    @include('layouts.partials.module_menu_icon', ['key' => 'riders'])
    <div>{{ $menuLabels['riders'] ?? 'Riders' }}</div>
  </a>
  <ul class="menu-sub">
    @can('riders_rider_view')
    <li class="menu-item {{ Route::is('riders*') ? 'active' : '' }}">
      <a href="{{ route('riders.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'riders_list'])
        <div>{{ $menuLabels['riders_list'] ?? 'Riders List' }}</div>
      </a>
    </li>
    @endcan
    @if(\App\Support\CompanyModuleVisibility::enabled('attendance'))
    @can('riders_attendance_view')
    <li class="menu-item {{ (Route::is('attendance.summary') && request('user_type') === 'rider') || (Route::is('attendance.index') && request('ref_type') === 'rider') ? 'active' : '' }}">
      <a href="{{ route('attendance.summary', ['user_type' => 'rider']) }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'attendance_summary'])
        {{ $menuLabels['attendance_summary'] ?? 'Attendance' }}
      </a>
    </li>
    @endcan
    @endif
    @if(\App\Support\CompanyModuleVisibility::enabled('rider_inventory'))
    @can('riders_inventory_view')
    <li class="menu-item {{ Route::is('RiderInventory*') ? 'active' : '' }}">
      <a href="{{ route('RiderInventory.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'rider_inventory'])
        <div>{{ $menuLabels['rider_inventory'] ?? 'Rider Inventory' }}</div>
      </a>
    </li>
    @endcan
    @endif
    @can('riders_invoices_view')
    <li class="menu-item {{ Route::is('riderInvoices*') ? 'active' : '' }}">
      <a href="{{ route('riderInvoices.index') }}" class="menu-link ">
        @include('layouts.partials.module_menu_icon', ['key' => 'invoices'])
        <div>{{ $menuLabels['invoices'] ?? 'Invoices' }}</div>
      </a>
    </li>
    @endcan
    @can('riders_payments_view')
    <li class="menu-item {{ Route::is('rider.payments') ? 'active' : '' }}">
      <a href="{{ route('rider.payments') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-wallet"></i>
        <div>{{ $menuLabels['payments_sent'] ?? 'Payments Sent' }}</div>
      </a>
    </li>
    @endcan
    @can('riders_activities_view')
    <li class="menu-item {{ Route::is('riderActivities*') || Route::is('rider.liveactivities*') ? 'active' : '' }}">
      <a href="{{ route('riderActivities.index') }}" class="menu-link ">
        @include('layouts.partials.module_menu_icon', ['key' => 'activities'])
        <div>{{ $menuLabels['activities'] ?? 'Activities' }}</div>
      </a>
    </li>
    @endcan
    @can('riders_live_activities_view')
    <li class="menu-item {{ Route::is('rider.liveactivities*') ? 'active' : '' }}">
      <a href="{{ route('rider.liveactivities') }}" class="menu-link ">
        @include('layouts.partials.module_menu_icon', ['key' => 'live_activities'])
        <div>{{ $menuLabels['live_activities'] ?? 'Live Activities' }}</div>
      </a>
    </li>
    @endcan
    @can('riders_report_view')
    <li class="menu-item {{ Route::is('reports*') ? 'active' : '' }}">
      <a href="{{ route('reports.rider_report') }}" class="menu-link ">
        @include('layouts.partials.module_menu_icon', ['key' => 'rider_report'])
        {{ $menuLabels['rider_report'] ?? 'Rider Report' }}
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcanany
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('bikes'))
@canany(['bikes_bike_view', 'bikes_registration_view'])
<li class="menu-item {{ Route::is('bikes*') || Route::is('BikeRegistration*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    @include('layouts.partials.module_menu_icon', ['key' => 'bikes'])
    <div>{{ $menuLabels['bikes'] ?? 'Bikes' }}</div>
  </a>
  <ul class="menu-sub">
    @can('bikes_bike_view')
    <li class="menu-item {{ Route::is('bikes*') ? 'active' : '' }}">
      <a href="{{ route('bikes.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'bike_list'])
        <div>{{ $menuLabels['bike_list'] ?? 'Bike List' }}</div>
      </a>
    </li>
    @endcan
    @can('bikes_registration_view')
    <li class="menu-item {{ Route::is('BikeRegistration*') ? 'active' : '' }}">
      <a href="{{ route('BikeRegistration.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'bike_registration'])
        <div>{{ $menuLabels['bike_registration'] ?? 'Bike Registration' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
{{-- <li class="menu-item {{ Route::is('bikeHistories*') ? 'active' : '' }}">
<a href="{{ route('bikeHistories.index') }}" class="menu-link">
  <i class="menu-icon tf-icons ti ti-bike-off"></i>
  <div>Bike History</div>
</a>
</li> --}}
@endcanany
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('bike_on_rent'))
@canany(['bike_on_rent_customers_view', 'bike_on_rent_invoices_view', 'bike_on_rent_payments_view'])
<li class="menu-item {{ Route::is('bikeRentCompanies*') || Route::is('leasingCompanyBillingInvoices*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    @include('layouts.partials.module_menu_icon', ['key' => 'bike_on_rent'])
    <div>{{ $menuLabels['bike_on_rent'] ?? 'Bike on rent' }}</div>
  </a>
  <ul class="menu-sub">
    @can('bike_on_rent_customers_view')
    <li class="menu-item {{ Route::is('bikeRentCompanies.*') && !Route::is('bikeRentCompanies.all_receipts') ? 'active' : '' }}">
      <a href="{{ route('bikeRentCompanies.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'bike_rent_customers'])
        <div>{{ $menuLabels['bike_rent_customers'] ?? 'Customers' }}</div>
      </a>
    </li>
    @endcan
    @can('bike_on_rent_invoices_view')
    <li class="menu-item {{ Route::is('leasingCompanyBillingInvoices*') ? 'active' : '' }}">
      <a href="{{ route('leasingCompanyBillingInvoices.index') }}" class="menu-link ">
        @include('layouts.partials.module_menu_icon', ['key' => 'leasing_billing_invoice'])
        <div>{{ $menuLabels['leasing_billing_invoice'] ?? 'Invoices' }}</div>
      </a>
    </li>
    @endcan
    {{-- Payments Receieved --}}
    @can('bike_on_rent_payments_view')
    <li class="menu-item {{ Route::is('bikeRentCompanies.all_receipts') ? 'active' : '' }}">
      <a href="{{ route('bikeRentCompanies.all_receipts') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'bike_rent_customer_receipts'])
        <div>{{ $menuLabels['bike_rent_customer_receipts'] ?? 'Payments Received' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcanany
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('sims'))
@canany(['sims_sim_view', 'sims_invoices_view', 'sims_companies_view', 'sims_payments_view'])
<li class="menu-item {{ Route::is('sims*') || Route::is('simInvoices*') || Route::is('simCompanies*') || Route::is('sim.payments') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    @include('layouts.partials.module_menu_icon', ['key' => 'sims'])
    <div>{{ $menuLabels['sims'] ?? 'Sims' }}</div>
  </a>
  <ul class="menu-sub">
    @can('sims_sim_view')
    <li class="menu-item {{ Route::is('sims*') ? 'active' : '' }}">
      <a href="{{ route('sims.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'sims'])
        <div>{{ $menuLabels['sims'] ?? 'Sims' }}</div>
      </a>
    </li>
    @endcan
    @can('sims_companies_view')
    <li class="menu-item {{ Route::is('simCompanies*') ? 'active' : '' }}">
      <a href="{{ route('simCompanies.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'sim_companies'])
        <div>{{ $menuLabels['sim_companies'] ?? 'SIM Companies' }}</div>
      </a>
    </li>
    @endcan
    @can('sims_invoices_view')
    <li class="menu-item {{ Route::is('simInvoices*') ? 'active' : '' }}">
      <a href="{{ route('simInvoices.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'sim_invoices'])
        <div>SIM Invoices</div>
      </a>
    </li>
    @endcan
    @can('sims_payments_view')
    <li class="menu-item {{ Route::is('sim.payments') ? 'active' : '' }}">
      <a href="{{ route('sim.payments') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'payments'])
        <div>{{ $menuLabels['sim_payments'] ?? 'Payments Sent' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcanany
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('fuel_cards'))
@canany(['fuel_cards_card_view', 'fuel_cards_transactions_view', 'fuel_cards_companies_view'])
<li class="menu-item {{ Route::is('fuelCards*') ? 'open' : '' }} {{ Route::is('fuel_data*') ? 'open' : '' }} {{ Route::is('fuelCompanies*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    @include('layouts.partials.module_menu_icon', ['key' => 'fuel_cards'])
    <div>{{ $menuLabels['fuel_cards'] ?? 'Fuel Cards' }}</div>
  </a>
  <ul class="menu-sub">
    @can('fuel_cards_card_view')
    <li class="menu-item {{ Route::is('fuelCards*') ? 'active' : '' }}">
      <a href="{{ route('fuelCards.index') }}" class="menu-link">
        <div>{{ $menuLabels['fuel_card_list'] ?? 'Card List' }}</div>
      </a>
    </li>
    @endcan
    @can('fuel_cards_transactions_view')
    <li class="menu-item {{ Route::is('fuel_data*') ? 'active' : '' }}">
      <a href="{{ route('fuel_data.index') }}" class="menu-link">
        <div>{{ $menuLabels['fuel_data'] ?? 'Fuel Transactions' }}</div>
      </a>
    </li>
    @endcan
    @can('fuel_cards_companies_view')
    <li class="menu-item {{ Route::is('fuelCompanies*') ? 'active' : '' }}">
      <a href="{{ route('fuelCompanies.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'fuel_companies'])
        <div>{{ $menuLabels['fuel_companies'] ?? 'Fuel Companies' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcanany
@endif

@if(\App\Support\CompanyModuleVisibility::enabled('rta_fines'))
@canany(['rta_fines_unpaid_view', 'rta_fines_paid_view'])
<li class="menu-item {{ Route::is('rtaFines*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    @include('layouts.partials.module_menu_icon', ['key' => 'rta_fines'])
    <div>{{ $menuLabels['rta_fines'] ?? 'RTA Fine' }}</div>
  </a>
  <ul class="menu-sub">
    @can('rta_fines_unpaid_view')
    <li class="menu-item {{ Route::is('rtaFines.tickets') ? 'active' : '' }}">
      <a href="{{ route('rtaFines.tickets') }}" class="menu-link">
        <div>{{ $menuLabels['rta_fines_unpaid'] ?? 'Unpaid' }}</div>
      </a>
    </li>
    @endcan
    @can('rta_fines_paid_view')
    <li class="menu-item {{ Route::is('rtaFines.paid') ? 'active' : '' }}">
      <a href="{{ route('rtaFines.paid') }}" class="menu-link">
        <div>{{ $menuLabels['rta_fines_paid'] ?? 'Paid' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcanany
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('rta_saliks'))
@can('rta_saliks_salik_view')
<li class="menu-item {{ Route::is('salik*') ? 'active' : '' }}">
  <a href="{{ route('salik.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'rta_saliks'])
    <div>{{ $menuLabels['rta_saliks'] ?? 'RTA Saliks' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('visa_expense'))
@if(\App\Support\VisaExpenseAccess::visibleInSidebar())
<li class="menu-item {{ Route::is('VisaExpense*') ? 'active' : '' }}">
  <a href="{{ route('VisaExpense.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'visa_expense'])
    <div>{{ $menuLabels['visa_expense'] ?? 'Visa Expense' }}</div>
  </a>
</li>
@endif
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('license_expense'))
@can('license_expense_view')
<li class="menu-item {{ Route::is('LicenseExpense*') ? 'active' : '' }}">
  <a href="{{ route('LicenseExpense.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'license_expense'])
    <div>{{ $menuLabels['license_expense'] ?? 'License Expense' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('legal_case'))
@can('legal_case_view')
<li class="menu-item {{ Route::is('LegalCase*') || Route::is('legal-case-statuses*') ? 'active' : '' }}">
  <a href="{{ route('LegalCase.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'legal_case'])
    <div>{{ $menuLabels['legal_case'] ?? 'Legal Case' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('passport_handover'))
@can('passport_handover_view')
<li class="menu-item {{ Route::is('passportHandover*') ? 'active' : '' }}">
  <a href="{{ route('passportHandover.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'passport_handover'])
    <div>{{ $menuLabels['passport_handover'] ?? 'Passport Handover' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('expenses'))
@can('expenses_view')
<li class="menu-item {{ Route::is('expenses*') ? 'active' : '' }}">
  <a href="{{ route('expenses.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'expenses'])
    <div>{{ $menuLabels['expenses'] ?? 'Expenses' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('vat'))
@canany(['vat_create', 'vat_view'])
<li class="menu-item {{ Route::is('vat.*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    @include('layouts.partials.module_menu_icon', ['key' => 'vat'])
    <div>{{ $menuLabels['vat'] ?? 'VAT' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('vat.index') ? 'active' : '' }}">
      <a href="{{ route('vat.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'vat_ledger'])
        <div>{{ $menuLabels['vat_ledger'] ?? 'VAT' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('vat.returns.index') ? 'active' : '' }}">
      <a href="{{ route('vat.returns.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'vat_return_file'])
        <div>{{ $menuLabels['vat_return_file'] ?? 'Return File' }}</div>
      </a>
    </li>
  </ul>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('leasing_companies'))
@canany(['leasing_companies_company_view', 'leasing_companies_invoices_view', 'leasing_companies_payments_view'])
<li class="menu-item {{ Route::is('leasingCompan*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    @include('layouts.partials.module_menu_icon', ['key' => 'leasing_companies'])
    <div>{{ $menuLabels['leasing_companies'] ?? 'Leasing Companies' }}</div>
  </a>

  <ul class="menu-sub">
    @can('leasing_companies_company_view')
    <li class="menu-item {{ (Route::is('leasingCompanies.*') && !Route::is('leasingCompanies.payment')) || (Route::is('leasingCompany.*') && !Route::is('leasingCompany.payment')) ? 'active' : '' }}">
      <a href="{{ route('leasingCompanies.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'leasing_companies_list'])
        <div>{{ $menuLabels['leasing_companies_list'] ?? 'Leasing Companies List' }}</div>
      </a>
    </li>
    @endcan
    @can('leasing_companies_invoices_view')
    <li class="menu-item {{ Route::is('leasingCompanyInvoices.*') ? 'active' : '' }}">
      <a href="{{ route('leasingCompanyInvoices.index') }}" class="menu-link ">
        @include('layouts.partials.module_menu_icon', ['key' => 'leasing_invoices'])
        <div>{{ $menuLabels['leasing_invoices'] ?? 'Invoices' }}</div>
      </a>
    </li>
    @endcan
    @can('leasing_companies_payments_view')
    <li class="menu-item {{ Route::is('leasingCompanies.payment') ? 'active' : '' }}">
      <a href="{{ route('leasingCompanies.payment') }}" class="menu-link ">
        @include('layouts.partials.module_menu_icon', ['key' => 'leasing_payment'])
        <div>{{ $menuLabels['leasing_payment'] ?? 'Payments sent' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcanany
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('garages'))
@can('garages_view')
<li class="menu-item {{ Route::is('garages*') || Route::is('garage_customer.*') || Route::is('bikeMaintenance*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    @include('layouts.partials.module_menu_icon', ['key' => 'garages'])
    <div>{{ $menuLabels['garages'] ?? 'Garages' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('garages*') ? 'active' : '' }}">
      <a href="{{ route('garages.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'garage_list'])
        <div>{{ $menuLabels['garage_list'] ?? 'Garage List' }}</div>
      </a>
    </li>
    @can('garages_maintenance_view')
    <li class="menu-item {{ Route::is('bikeMaintenance*') ? 'active' : '' }}">
      <a href="{{ route('bikeMaintenance.index') }}" class="menu-link">
          @include('layouts.partials.module_menu_icon', ['key' => 'maintenance_overview'])
          <div>{{ $menuLabels['maintenance_overview'] ?? 'Maintenance' }}</div>
        </a>
      </li>
    @endcan
    @if(\App\Support\CompanyModuleVisibility::enabled('garages_customers'))
    @can('garages_customers_view')
    <li class="menu-item {{ Route::is('garage_customer.*') && !Route::is('garage_customer.all_receipts') ? 'active' : '' }}">
      <a href="{{ route('garage_customer.index') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'garage_customers'])
        <div>{{ $menuLabels['garage_customers'] ?? 'Customers' }}</div>
      </a>
    </li>
    @endcan
    @can('garages_payments_view')
    <li class="menu-item {{ Route::is('garage_customer.all_receipts') ? 'active' : '' }}">
      <a href="{{ route('garage_customer.all_receipts') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'bike_rent_customer_receipts'])
        <div>{{ $menuLabels['bike_rent_customer_receipts'] ?? 'Payments Received' }}</div>
      </a>
    </li>
    @endcan
    @endif
  </ul>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('supplier'))
@canany(['supplier_view'])
<li class="menu-item {{ Route::is('supplier*') ? 'open' : '' }}">

  <a href="javascript:void(0); " class="menu-link menu-toggle">
    @include('layouts.partials.module_menu_icon', ['key' => 'supplier'])
    <div>{{ $menuLabels['supplier'] ?? 'Supplier' }}</div>
  </a>
  <ul class="menu-sub">

    <li class="menu-item {{ Route::is('suppliers.index') || Route::is('suppliers.show') || Route::is('suppliers.ledger') || Route::is('suppliers.document') ? 'active' : '' }}">
      <a href="{{ route('suppliers.index') }}" class="menu-link">
        <div>{{ $menuLabels['suppliers'] ?? 'Suppliers List' }}</div>
      </a>
    </li>

    <li class="menu-item {{ Route::is('supplier.purchase_order') ? 'active' : '' }}">
      <a href="{{ route('supplier.purchase_order') }}" class="menu-link">
        <div>{{ $menuLabels['supplier_orders'] ?? 'Purchase Orders' }}</div>
      </a>
    </li>

    <li class="menu-item {{ Route::is('supplier_invoices.index') ? 'active' : '' }}">
      <a href="{{ route('supplier_invoices.index') }}" class="menu-link">
        <div>{{ $menuLabels['supplier_invoices'] ?? 'Supplier Invoices' }}</div>
      </a>
    </li>

    <li class="menu-item {{ Route::is('supplier.payments') ? 'active' : '' }}">
      <a href="{{ route('supplier.payments') }}" class="menu-link">
        <div>{{ $menuLabels['supplier_payments'] ?? 'Payments Sent' }}</div>
      </a>
    </li>


  </ul>
</li>
@endcanany
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('assets'))
@can('assets_view')
<li class="menu-item {{ Route::is('fixed-assets*') || Route::is('asset-categories*') ? 'active' : '' }}">
  <a href="{{ route('fixed-assets.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'assets'])
    <div>{{ $menuLabels['assets'] ?? 'Assets' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('agreements'))
@can('agreements_view')
<li class="menu-item {{ Route::is('agreements.*') ? 'active' : '' }}">
  <a href="{{ route('agreements.index', ['company_slug' => $menuCompanySlug]) }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'agreements'])
    <div>{{ $menuLabels['agreements'] ?? 'Agreements' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('documents'))
@can('documents_view')
<li class="menu-item {{ Route::is('files*') ? 'active' : '' }}">
  <a href="{{ route('files.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'documents'])
    <div>{{ $menuLabels['documents'] ?? 'Documents' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('vouchers'))
@can('vouchers_view')
<li class="menu-item {{ Route::is('vouchers*') ? 'active' : '' }}">
  <a href="{{ route('vouchers.index') }}" class="menu-link">
    @include('layouts.partials.module_menu_icon', ['key' => 'vouchers'])
    <div>{{ $menuLabels['vouchers'] ?? 'Vouchers' }}</div>
  </a>
</li>
@endcan
@endif
@endif




{{-- Admin Panel (global site settings) --}}
@php($adminUser = auth('admin')->user())
@php($canAccessSuperAdminPanel = $adminUser && $adminUser->hasRole('Super Admin'))
@if($isAdminLogin && $canAccessSuperAdminPanel)
<li class="menu-header small text-uppercase mt-3">
  <span class="menu-header-text">{{ __('Admin Panel') }}</span>
</li>
<li class="menu-item {{ Route::is('admin.dashboard') ? 'active' : '' }}">
  <a href="{{ route('admin.dashboard') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-layout-dashboard"></i>
    <div>{{ __('Dashboard') }}</div>
  </a>
</li>
@endif
@if($isAdminLogin && $canAccessSuperAdminPanel && $adminUser->hasPermission('companies_view'))
<li class="menu-item {{ Route::is('admin.companies*') ? 'active' : '' }}">
  <a href="{{ route('admin.companies.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-building-community"></i>
    <div>{{ __('Companies') }}</div>
  </a>
</li>
@endif

@if($isAdminLogin && $canAccessSuperAdminPanel && $adminUser->hasPermission('blogs_view'))
<li class="menu-item {{ Route::is('admin.blogs*') ? 'active' : '' }}">
  <a href="{{ route('admin.blogs.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-pencil"></i>
    <div>{{ __('Blogs') }}</div>
  </a>
</li>
@endif

@if($isAdminLogin && $canAccessSuperAdminPanel && $adminUser->hasPermission('testimonials_view'))
<li class="menu-item {{ Route::is('admin.testimonials*') ? 'active' : '' }}">
  <a href="{{ route('admin.testimonials.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-message-dots"></i>
    <div>{{ __('Testimonials') }}</div>
  </a>
</li>
@endif

@if($isAdminLogin && $canAccessSuperAdminPanel)
<li class="menu-item {{ Route::is('admin.auth-branding*') ? 'active' : '' }}">
  <a href="{{ route('admin.auth-branding.edit') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-photo"></i>
    <div>{{ __('Login & Register Pages') }}</div>
  </a>
</li>
@endif

@if($isAdminLogin && $canAccessSuperAdminPanel && $adminUser->hasPermission('privacy_policy_view'))
<li class="menu-item {{ Route::is('admin.privacy-policy*') ? 'active' : '' }}">
  <a href="{{ route('admin.privacy-policy.edit') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-file-description"></i>
    <div>{{ __('Privacy Policy') }}</div>
  </a>
</li>
@endif

@if($isAdminLogin && $canAccessSuperAdminPanel && $adminUser->hasPermission('terms_conditions_view'))
<li class="menu-item {{ Route::is('admin.terms-conditions*') ? 'active' : '' }}">
  <a href="{{ route('admin.terms-conditions.edit') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-file-description"></i>
    <div>{{ __('Terms & Conditions') }}</div>
  </a>
</li>
@endif

@if($isAdminLogin && $canAccessSuperAdminPanel && $adminUser->hasPermission('users_view'))
<li class="menu-item {{ Route::is('admin.users*') ? 'active' : '' }}">
  <a href="{{ route('admin.users.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-users-group"></i>
    <div>{{ __('Users') }}</div>
  </a>
</li>
@endif

@if($isAdminLogin && $canAccessSuperAdminPanel)
<li class="menu-item {{ Route::is('admin.permissions*') && !Route::is('admin.erp-permissions*') ? 'active' : '' }}">
  <a href="{{ route('admin.permissions.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-lock"></i>
    <div>{{ __('Permissions') }}</div>
  </a>
</li>
@endif

@if($isAdminLogin && $canAccessSuperAdminPanel)
<li class="menu-item {{ Route::is('admin.erp-permissions*') ? 'active' : '' }}">
  <a href="{{ route('admin.erp-permissions.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-shield-lock"></i>
    <div>{{ __('ERP Permissions') }}</div>
  </a>
</li>
@endif

@if($isAdminLogin && $canAccessSuperAdminPanel)
<li class="menu-item {{ Route::is('admin.global-accounts*') ? 'active' : '' }}">
  <a href="{{ route('admin.global-accounts.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-world"></i>
    <div>{{ __('Global Accounts') }}</div>
  </a>
</li>
@endif

@if(!$isAdminLogin)
@canany(['accounts_coa_view','accounts_ledger_view'])
<li class="menu-item {{ Route::is('accounts*') ? 'open' : '' }} ">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    @include('layouts.partials.module_menu_icon', ['key' => 'accounts'])
    <div>{{ $menuLabels['accounts'] ?? 'Accounts' }}</div>
  </a>
  <ul class="menu-sub">

    @can('accounts_coa_view')
    <li class="menu-item {{ Route::is('accounts.tree') ? 'active' : '' }}">
      <a href="{{ route('accounts.tree') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'chart_of_accounts'])
        <div>{{ $menuLabels['chart_of_accounts'] ?? 'Chart Of Accounts' }}</div>
      </a>
    </li>
    @endcan

    @can('accounts_ledger_view')

    <li class="menu-item {{ Route::is('accounts.ledger') ? 'active' : '' }}">
      <a href="{{ route('accounts.ledger') }}" class="menu-link">
        @include('layouts.partials.module_menu_icon', ['key' => 'ledger'])
        <div>{{ $menuLabels['ledger'] ?? 'Ledger' }}</div>
      </a>
    </li>
    @endcan


  </ul>
</li>
@endcanany
@endif

{{-- <li class="menu-item {{ Request::is('reports*') ? 'open' : '' }} ">
<a href="javascript:void(0);" class="menu-link menu-toggle ">
  <i class="menu-icon tf-icons ti ti-chart-area"></i>
  <div data-i18n="Front Pages">Reports</div>
</a>
<ul class="menu-sub">

  <li class="menu-item {{ Request::is('reports*') ? 'active' : '' }}">
    <a href="{{ route('reports.rider_report') }}" class="menu-link ">
      <i class="menu-icon tf-icons ti ti-users-group"></i>
      Rider Report
    </a>
  </li>
</ul>
</li> --}}


{{-- <li class="nav-item">
    <a href="{{ route('riderAttendances.index') }}" class="nav-link {{ Request::is('riderAttendances*') ? 'active' : '' }}">
<i class="nav-icon fas fa-home"></i>
<p>Rider Attendances</p>
</a>
</li> --}}

{{-- <li class="nav-item">
    <a href="{{ route('riderActivities.index') }}" class="nav-link {{ Request::is('riderActivities*') ? 'active' : '' }}">
<i class="nav-icon fas fa-home"></i>
<p>Rider Activities</p>
</a>
</li> --}}

{{-- <li class="nav-item">
    <a href="{{ route('riderEmails.index') }}" class="nav-link {{ Request::is('riderEmails*') ? 'active' : '' }}">
<i class="nav-icon fas fa-home"></i>
<p>Rider Emails</p>
</a>
</li> --}}

{{-- <li class="nav-item">
    <a href="{{ route('files.index') }}" class="nav-link {{ Request::is('files*') ? 'active' : '' }}">
<i class="nav-icon fas fa-home"></i>
<p>Files</p>
</a>
</li> --}}