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
@can('dashboard_view')
<li class="menu-item {{ Route::is('home') || Route::is('/') ? 'active' : '' }}">
  <a href="{{ $companySlug ? route('home', ['company_slug' => $companySlug]) : 'javascript:void(0);' }}" class="menu-link ">
    <i class="menu-icon tf-icons ti ti-layout-dashboard"></i>
    <div>{{ $menuLabels['dashboard'] ?? 'Dashboard' }}</div>
    {{-- <div class="badge bg-white text-dark rounded-pill ms-auto">2</div>  --}}
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('cash_banks'))
@can('bank_view')
<li class="menu-item {{ Route::is('banks.*') ? 'open' : '' }}  {{ Route::is('bank.*') ? 'open' : '' }} {{ Route::is('cheques.*') ? 'open' : '' }} {{ Route::is('payments.*') ? 'open' : '' }} {{ Route::is('receipts.*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    <i class="menu-icon tf-icons ti ti-building-bank"></i>
    <div>{{ $menuLabels['cash_banks'] ?? 'Cash & Banks' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('banks.*') ? 'active' : '' }} {{ Route::is('bank.*') ? 'active' : '' }} ">
      <a href="{{ route('banks.index') }}" class="menu-link">
        <div>{{ $menuLabels['cash_banks'] ?? 'Cash & Banks' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('cheques.*') ? 'active' : '' }}">
      <a href="{{ route('cheques.index') }}" class="menu-link">
        <div>{{ $menuLabels['cheques'] ?? 'Cheques' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('payments.*') ? 'active' : '' }}">
      <a href="{{ route('payments.index') }}" class="menu-link">
        <div>{{ $menuLabels['payments'] ?? 'Payments (Cash-Out)' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('receipts.*') ? 'active' : '' }}">
      <a href="{{ route('receipts.index') }}" class="menu-link">
        <div>{{ $menuLabels['receipts'] ?? 'Receipts (Cash-In)' }}</div>
      </a>
    </li>
  </ul>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('employees'))
@can('employees_view')
@if(\App\Support\CompanyModuleVisibility::enabled('attendance'))
@can('attendance_view')
<li class="menu-item {{ Route::is('employees.*') ? 'open' : '' }} {{ (Route::is('attendance.*') && request('ref_type') === 'employee') || (Route::is('attendance.summary') && request('user_type', 'employee') === 'employee') ? 'open' : '' }} {{ Route::is('employeeInvoices.*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    <i class="menu-icon tf-icons ti ti-user"></i>
    <div>{{ $menuLabels['employees'] ?? 'Employees' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('employees.*') ? 'active' : '' }}">
      <a href="{{ route('employees.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-user"></i>
        <div>{{ $menuLabels['employees'] ?? 'Employees' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('attendance.index') && request('ref_type') === 'employee' ? 'active' : '' }}">
      <a href="{{ route('attendance.index', ['ref_type' => 'employee']) }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-calendar-check"></i>
        {{ $menuLabels['attendance_records'] ?? 'Attendance Records' }}
      </a>
    </li>
    <li class="menu-item {{ Route::is('attendance.summary') && request('user_type', 'employee') === 'employee' ? 'active' : '' }}">
      <a href="{{ route('attendance.summary', ['user_type' => 'employee']) }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-calendar-check"></i>
        {{ $menuLabels['attendance_summary'] ?? 'Attendance Summary' }}
      </a>
    </li>
    @can('employeeinvoice_view')
    <li class="menu-item {{ Route::is('employeeInvoices.*') ? 'active' : '' }}">
      <a href="{{ route('employeeInvoices.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-file"></i>
        <div>Employee Invoices</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@else
<li class="menu-item {{ Route::is('employees.*') ? 'active' : '' }}">
  <a href="{{ route('employees.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user"></i>
    <div>{{ $menuLabels['employees'] ?? 'Employees' }}</div>
  </a>
</li>
@endcan
@else
<li class="menu-item {{ Route::is('employees.*') ? 'active' : '' }}">
  <a href="{{ route('employees.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user"></i>
    <div>{{ $menuLabels['employees'] ?? 'Employees' }}</div>
  </a>
</li>
@endif
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('items'))
@can('item_view')
<li class="menu-item {{ Route::is('items.*') ? 'open' : '' }} {{ Route::is('garage-items.*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-notes"></i>
    <div>{{ $menuLabels['items'] ?? 'Items' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('items.*') && !Route::is('garage-items.*') ? 'active' : '' }}">
      <a href="{{ route('items.index') }}" class="menu-link">
        <div>{{ $menuLabels['items_list'] ?? 'Items List' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('garage-items.*') ? 'active' : '' }}">
      <a href="{{ route('garage-items.index') }}" class="menu-link">
        <div>{{ $menuLabels['garage_items'] ?? 'Garage Items' }}</div>
      </a>
    </li>
  </ul>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('leads'))
@can('leads_view')
<li class="menu-item {{ Route::is('riderleads.*') ? 'active' : '' }}">
  <a href="{{ route('riderleads.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user-plus"></i>
    <div>{{ $menuLabels['leads'] ?? 'Leads' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('customers'))
@can('customer_view')
<li class="menu-item {{ (Route::is('customer*') || Route::is('customers*') || Route::is('customer_invoice*') || Route::is('customer_invoices*')) ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    <i class="menu-icon tf-icons ti ti-user-star"></i>
    <div>{{ $menuLabels['customers'] ?? 'Customers' }}</div>
  </a>
  <ul class="menu-sub">
    {{-- Customer List --}}
    <li class="menu-item {{ Route::is('customers.index') ? 'active' : '' }}">
      <a href="{{ route('customers.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-users"></i>
        <div>{{ $menuLabels['customer_list'] ?? 'Customer List' }}</div>
      </a>
    </li>
    @can('customer_invoice_view')
    {{-- Invoices --}}
    <li class="menu-item {{ Route::is('customer-invoices*') || Route::is('customer_invoices*') ? 'active' : '' }}">
      <a href="{{ route('customer_invoices.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-receipt"></i>
        <div>{{ $menuLabels['customer_invoices'] ?? 'Invoices' }}</div>
      </a>
    </li>
    @endcan
    {{-- Payments Receieved --}}
    <li class="menu-item {{ Route::is('customer.receipts') ? 'active' : '' }}">
      <a href="{{ route('customer.receipts') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-receipt"></i>
        <div>{{ $menuLabels['customer_receipts'] ?? 'Payments Received' }}</div>
      </a>
    </li>
  </ul>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('vendors'))
@can('vendor_view')
<li class="menu-item {{ Route::is('vendors*') ? 'active' : '' }}">
  <a href="{{ route('vendors.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user-star"></i>
    <div>{{ $menuLabels['vendors'] ?? 'Vendors' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('recruiters'))
@can('recruiter_view')
<li class="menu-item {{ Route::is('recruiters*') ? 'active' : '' }}">
  <a href="{{ route('recruiters.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user-star"></i>
    <div>{{ $menuLabels['recruiters'] ?? 'Recruiters' }}</div>
  </a>
</li>
@endcan
@endif

@if(\App\Support\CompanyModuleVisibility::enabled('riders'))
@can('rider_view')
<li class="menu-item {{ Route::is('riders*') ? 'open' : '' }}
 {{ Route::is('riderInvoices*') ? 'open' : '' }}
 {{ Route::is('riderActivities*') ? 'open' : '' }}
  {{ Route::is('rider.*') ? 'open' : '' }}
 {{ Route::is('reports.rider_report*') ? 'open' : '' }}
 {{ Route::is('reports.rider_monthly_report*') ? 'open' : '' }}
 {{ (Route::is('attendance*') && request('ref_type') === 'rider') || (Route::is('attendance.summary') && request('user_type') === 'rider') ? 'open' : '' }}  ">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-user-pin"></i>
    <div>{{ $menuLabels['riders'] ?? 'Riders' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('riders*') ? 'active' : '' }}">
      <a href="{{ route('riders.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-user-pin"></i>
        <div>{{ $menuLabels['riders_list'] ?? 'Riders List' }}</div>
      </a>
    </li>
    @if(\App\Support\CompanyModuleVisibility::enabled('attendance'))
    @can('attendance_view')
    <li class="menu-item {{ Route::is('attendance.index') && request('ref_type') === 'rider' ? 'active' : '' }}">
      <a href="{{ route('attendance.index', ['ref_type' => 'rider']) }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-calendar-check"></i>
        {{ $menuLabels['attendance_records'] ?? 'Attendance Records' }}
      </a>
    </li>
    <li class="menu-item {{ Route::is('attendance.summary') && request('user_type') === 'rider' ? 'active' : '' }}">
      <a href="{{ route('attendance.summary', ['user_type' => 'rider']) }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-calendar-check"></i>
        {{ $menuLabels['attendance_summary'] ?? 'Attendance Summary' }}
      </a>
    </li>
    @endcan
    @endif
    @can('riderinvoice_view')
    <li class="menu-item {{ Route::is('riderInvoices*') ? 'active' : '' }}">
      <a href="{{ route('riderInvoices.index') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-file"></i>
        <div>{{ $menuLabels['invoices'] ?? 'Invoices' }}</div>
      </a>
    </li>
    @endcan
    <li class="menu-item {{ Route::is('riderActivities*') ? 'active' : '' }}">
      <a href="{{ route('riderActivities.index') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-bike"></i>
        <div>{{ $menuLabels['activities'] ?? 'Activities' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('rider.liveactivities*') ? 'active' : '' }}">
      <a href="{{ route('rider.liveactivities') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-bike"></i>
        <div>{{ $menuLabels['live_activities'] ?? 'Live Activities' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('reports*') ? 'active' : '' }}">
      <a href="{{ route('reports.rider_report') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-users-group"></i>
        {{ $menuLabels['rider_report'] ?? 'Rider Report' }}
      </a>
    </li>
  </ul>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('bikes'))
@can('bike_view')
<li class="menu-item {{ Route::is('bikes*') || Route::is('bikeMaintenance*')? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-motorbike"></i>
    <div>{{ $menuLabels['bikes'] ?? 'Bikes' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('bikes*') ? 'active' : '' }}">
      <a href="{{ route('bikes.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-motorbike"></i>
        <div>{{ $menuLabels['bike_list'] ?? 'Bike List' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('bikeMaintenance*') ? 'active' : '' }}">
      <a href="{{ route('bikeMaintenance.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-motorbike"></i>
        <div>{{ $menuLabels['maintenance_overview'] ?? 'Maintenance' }}</div>
      </a>
    </li>
  </ul>
</li>
{{-- <li class="menu-item {{ Route::is('bikeHistories*') ? 'active' : '' }}">
<a href="{{ route('bikeHistories.index') }}" class="menu-link">
  <i class="menu-icon tf-icons ti ti-bike-off"></i>
  <div>Bike History</div>
</a>
</li> --}}
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('bike_on_rent'))
@can('bike_view')
<li class="menu-item {{ Route::is('bikeRentCompanies*') || Route::is('leasingCompanyBillingInvoices*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-motorbike"></i>
    <div>{{ $menuLabels['bike_on_rent'] ?? 'Bike on rent' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('bikeRentCompanies*') ? 'active' : '' }}">
      <a href="{{ route('bikeRentCompanies.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-users"></i>
        <div>{{ $menuLabels['bike_rent_customers'] ?? 'Customers' }}</div>
      </a>
    </li>
    @can('billing_invoice_view')
    <li class="menu-item {{ Route::is('leasingCompanyBillingInvoices*') ? 'active' : '' }}">
      <a href="{{ route('leasingCompanyBillingInvoices.index') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-file-plus"></i>
        <div>{{ $menuLabels['leasing_billing_invoice'] ?? 'Billing Invoice' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('sims'))
@can('sim_view')
<li class="menu-item {{ Route::is('sims*') || Route::is('simInvoices*') || Route::is('simCompanies*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>{{ $menuLabels['sims'] ?? 'Sims' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('sims*') ? 'active' : '' }}">
      <a href="{{ route('sims.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-device-sim"></i>
        <div>{{ $menuLabels['sims'] ?? 'Sims' }}</div>
      </a>
    </li>
    @can('sim_invoice_view')
    <li class="menu-item {{ Route::is('simInvoices*') ? 'active' : '' }}">
      <a href="{{ route('simInvoices.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-file-invoice"></i>
        <div>SIM Invoices</div>
      </a>
    </li>
    @endcan
    <li class="menu-item {{ Route::is('simCompanies*') ? 'active' : '' }}">
      <a href="{{ route('simCompanies.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-building"></i>
        <div>{{ $menuLabels['sim_companies'] ?? 'SIM Companies' }}</div>
      </a>
    </li>
  </ul>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('fuel_cards'))
@can('fuel_view')
<li class="menu-item {{ Route::is('fuelCards*') ? 'open' : '' }} {{ Route::is('fuel_data*') ? 'open' : '' }} {{ Route::is('fuelCompanies*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    <i class="menu-icon tf-icons ti ti-gas-station"></i>
    <div>{{ $menuLabels['fuel_cards'] ?? 'Fuel Cards' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('fuelCards*') ? 'active' : '' }}">
      <a href="{{ route('fuelCards.index') }}" class="menu-link">
        <div>{{ $menuLabels['fuel_card_list'] ?? 'Card List' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('fuel_data*') ? 'active' : '' }}">
      <a href="{{ route('fuel_data.index') }}" class="menu-link">
        <div>{{ $menuLabels['fuel_data'] ?? 'Fuel Transactions' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Route::is('fuelCompanies*') ? 'active' : '' }}">
      <a href="{{ route('fuelCompanies.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-building"></i>
        <div>{{ $menuLabels['fuel_companies'] ?? 'Fuel Companies' }}</div>
      </a>
    </li>
  </ul>
</li>
@endcan
@endif

@if(\App\Support\CompanyModuleVisibility::enabled('rta_fines'))
@canany(['rtafine_view', 'rtafine_paid_view'])
<li class="menu-item {{ Route::is('rtaFines*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle">
    <i class="menu-icon tf-icons ti ti-file-alert"></i>
    <div>{{ $menuLabels['rta_fines'] ?? 'RTA Fines' }}</div>
  </a>
  <ul class="menu-sub">
    @can('rtafine_view')
    <li class="menu-item {{ Route::is('rtaFines.tickets') ? 'active' : '' }}">
      <a href="{{ route('rtaFines.tickets') }}" class="menu-link">
        <div>Unpaid Fines</div>
      </a>
    </li>
    @endcan
    @can('rtafine_paid_view')
    <li class="menu-item {{ Route::is('rtaFines.paid') ? 'active' : '' }}">
      <a href="{{ route('rtaFines.paid') }}" class="menu-link">
        <div>Paid Fines</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcanany
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('rta_saliks'))
@can('salik_view')
<li class="menu-item {{ Route::is('salik*') ? 'active' : '' }}">
  <a href="{{ route('salik.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-cash"></i>
    <div>{{ $menuLabels['rta_saliks'] ?? 'RTA Saliks' }}</div>
  </a>
</li>
@endcan
@endif

@if(\App\Support\CompanyModuleVisibility::enabled('inventory'))
@can('inventory_view')
<li class="menu-item ">
  <a href="#" class="menu-link">
    <i class="menu-icon tf-icons ti ti-package"></i>
    <div>{{ $menuLabels['inventory'] ?? 'Inventory' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('visa_expense'))
@can('visaexpense_view')
<li class="menu-item {{ Route::is('VisaExpense*') ? 'active' : '' }}">
  <a href="{{ route('VisaExpense.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-credit-card"></i>
    <div>{{ $menuLabels['visa_expense'] ?? 'Visa Expense' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('expenses'))
@can('expenses_view')
<li class="menu-item {{ Route::is('expenses*') ? 'active' : '' }}">
  <a href="{{ route('expenses.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-cash"></i>
    <div>{{ $menuLabels['expenses'] ?? 'Expenses' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('vat'))
@can('vat_view')
<li class="menu-item {{ Route::is('vat.*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-receipt-tax"></i>
    <div>{{ $menuLabels['vat'] ?? 'VAT' }}</div>
  </a>
  <ul class="menu-sub">

    @can('vat_view')
    <li class="menu-item {{ Route::is('vat.index') ? 'active' : '' }}">
      <a href="{{ route('vat.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-receipt-tax"></i>
        <div>{{ $menuLabels['vat_ledger'] ?? 'VAT' }}</div>
      </a>
    </li>
    @endcan
    @can('vat_return_view')
    <li class="menu-item {{ Route::is('vat.returns.index') ? 'active' : '' }}">
      <a href="{{ route('vat.returns.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-file-export"></i>
        <div>{{ $menuLabels['vat_return_file'] ?? 'Return File' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('leasing_companies'))
@can('leasing_view')
<li class="menu-item {{ Route::is('leasingCompan*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-building"></i>
    <div>{{ $menuLabels['leasing_companies'] ?? 'Leasing Companies' }}</div>
  </a>

  <ul class="menu-sub">
    <li class="menu-item {{ Route::is('leasingCompanies.index*') ? 'active' : '' }}">
      <a href="{{ route('leasingCompanies.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-building"></i>
        <div>{{ $menuLabels['leasing_companies_list'] ?? 'Leasing Companies List' }}</div>
      </a>
    </li>
    @can('leasing_company_invoice_view')
    <li class="menu-item {{ Route::is('leasingCompanyInvoices*') ? 'active' : '' }}">
      <a href="{{ route('leasingCompanyInvoices.index') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-file-invoice"></i>
        <div>{{ $menuLabels['leasing_invoices'] ?? 'Invoices' }}</div>
      </a>
    </li>
    @endcan
    @can('billing_invoice_view')
    <li class="menu-item {{ Route::is('leasingCompanies.receipt') ? 'active' : '' }}">
      <a href="{{ route('leasingCompanies.receipt') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-file-plus"></i>
        <div>{{ $menuLabels['leasing_receipt'] ?? 'Payments Received' }}</div>
      </a>
    </li>
    @endcan
    @can('leasing_company_invoice_view')
    <li class="menu-item {{ Route::is('leasingCompanies.payment') ? 'active' : '' }}">
      <a href="{{ route('leasingCompanies.payment') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-file-plus"></i>
        <div>{{ $menuLabels['leasing_payment'] ?? 'Payments sent' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('garages'))
@can('garage_view')
<li class="menu-item {{ Route::is('garages*') ? 'active' : '' }}">
  <a href="{{ route('garages.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-parking"></i>
    <div>{{ $menuLabels['garages'] ?? 'Garages' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('supplier'))
@canany(['supplier_view'])
<li class="menu-item {{ Route::is('supplier*') ? 'open' : '' }}">

  <a href="javascript:void(0); " class="menu-link menu-toggle">
    <i class="menu-icon tf-icons ti ti-truck"></i>
    <div>{{ $menuLabels['supplier'] ?? 'Supplier' }}</div>
  </a>
  <ul class="menu-sub">

    <li class="menu-item {{ Route::is('suppliers.index') || Route::is('suppliers.show') || Route::is('suppliers.ledger') ? 'active' : '' }}">
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
@can('asset_view')
<li class="menu-item ">
  <a href="#" class="menu-link">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>{{ $menuLabels['assets'] ?? 'Assets' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('documents'))
@can('company_documents_view')
<li class="menu-item {{ Route::is('upload_files*') ? 'active' : '' }}">
  <a href="{{ route('upload_files.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-upload"></i>
    <div>{{ $menuLabels['documents'] ?? 'Documents' }}</div>
  </a>
</li>
@endcan
@endif
@if(\App\Support\CompanyModuleVisibility::enabled('vouchers'))
@can('voucher_view')
<li class="menu-item {{ Route::is('vouchers*') ? 'active' : '' }}">
  <a href="{{ route('vouchers.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-ticket"></i>
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
<li class="menu-item {{ Route::is('admin.permissions*') ? 'active' : '' }}">
  <a href="{{ route('admin.permissions.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-lock"></i>
    <div>{{ __('Permissions') }}</div>
  </a>
</li>
@endif

@if($isAdminLogin && $canAccessSuperAdminPanel)
<li class="menu-item {{ Route::is('admin.accounts.fixed*') ? 'active' : '' }}">
  <a href="{{ route('admin.accounts.fixed.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-pinned"></i>
    <div>{{ __('Account Fixing') }}</div>
  </a>
</li>
@endif

@if(!$isAdminLogin)
@canany(['account_view','gn_ledger'])
<li class="menu-item {{ Route::is('accounts*') ? 'open' : '' }} ">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-graph"></i>
    <div>{{ $menuLabels['accounts'] ?? 'Accounts' }}</div>
  </a>
  <ul class="menu-sub">

    @can('account_view')
    <li class="menu-item {{ Route::is('accounts.tree') ? 'active' : '' }}">
      <a href="{{ route('accounts.tree') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-settings"></i>
        <div>{{ $menuLabels['chart_of_accounts'] ?? 'Chart Of Accounts' }}</div>
      </a>
    </li>
    @endcan

    @can('gn_ledger')

    <li class="menu-item {{ Route::is('accounts.ledger') ? 'active' : '' }}">
      <a href="{{ route('accounts.ledger') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-settings"></i>
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