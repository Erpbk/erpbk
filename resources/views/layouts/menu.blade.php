@php $menuLabels = $menuLabels ?? \App\Models\Settings::getMenuLabels(); @endphp
@can('dashboard_view')
<li class="menu-item {{ Request::is('/') ? 'active' : '' }}">
  <a href="{{ route('home') }}" class="menu-link ">
    <i class="menu-icon tf-icons ti ti-layout-dashboard"></i>
    <div>{{ $menuLabels['dashboard'] ?? 'Dashboard' }}</div>
    {{-- <div class="badge bg-white text-dark rounded-pill ms-auto">2</div>  --}}
  </a>
</li>
@endcan
@can('trash_view')
<li class="menu-item {{ Request::is('trash*') ? 'active' : '' }}">
  <a href="{{ route('trash.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-trash text-warning"></i>
    <div>{{ $menuLabels['recycle_bin'] ?? 'Recycle Bin' }}</div>
  </a>
</li>
@endcan
@can('bank_view')
<li class="menu-item {{ Request::is('banks') ? 'active' : '' }} {{ Request::is('bank*') ? 'active' : '' }}">
  <a href="{{ route('banks.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-building-bank"></i>
    <div>{{ $menuLabels['cash_banks'] ?? 'Cash & Banks' }}</div>
  </a>
</li>
@endcan
@can('item_view')
<li class="menu-item {{ Request::is('items*') ? 'open' : '' }} {{ Request::is('garage-items*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-notes"></i>
    <div>{{ $menuLabels['items'] ?? 'Items' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Request::is('items*') && !Request::is('garage-items*') ? 'active' : '' }}">
      <a href="{{ route('items.index') }}" class="menu-link">
        <div>{{ $menuLabels['items_list'] ?? 'Items List' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Request::is('garage-items*') ? 'active' : '' }}">
      <a href="{{ route('garage-items.index') }}" class="menu-link">
        <div>{{ $menuLabels['garage_items'] ?? 'Garage Items' }}</div>
      </a>
    </li>
  </ul>
</li>
@endcan
@can('leads_view')
<li class="menu-item {{ Request::is('riderleads*') ? 'active' : '' }}">
  <a href="{{ route('riderleads.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user-plus"></i>
    <div>{{ $menuLabels['leads'] ?? 'Leads' }}</div>
  </a>
</li>
@endcan
@can('customer_view')
<li class="menu-item {{ Request::is('customers*') ? 'active' : '' }}">
  <a href="{{ route('customers.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user-star"></i>
    <div>{{ $menuLabels['customers'] ?? 'Customers' }}</div>
  </a>
</li>
@endcan
@can('vendor_view')
<li class="menu-item {{ Request::is('vendors*') ? 'active' : '' }}">
  <a href="{{ route('vendors.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user-star"></i>
    <div>{{ $menuLabels['vendors'] ?? 'Vendors' }}</div>
  </a>
</li>
@endcan
@can('recruiter_view')
<li class="menu-item {{ Request::is('recruiters*') ? 'active' : '' }}">
  <a href="{{ route('recruiters.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user-star"></i>
    <div>{{ $menuLabels['recruiters'] ?? 'Recruiters' }}</div>
  </a>
</li>
@endcan

@can('rider_view')
<li class="menu-item {{ Request::is('riders*') ? 'open' : '' }}
 {{ Request::is('riderInvoices*') ? 'open' : '' }}
 {{ Request::is('riderActivities*') ? 'open' : '' }}
  {{ Request::is('reports/rider_report*') ? 'open' : '' }}
  {{ Request::is('reports/rider_monthly_report*') ? 'open' : '' }}  ">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-user-pin"></i>
    <div>{{ $menuLabels['riders'] ?? 'Riders' }}</div>
  </a>
  <ul class="menu-sub">

    <li class="menu-item {{ Request::is('riders*') ? 'active' : '' }}">
      <a href="{{ route('riders.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-user-pin"></i>
        <div>{{ $menuLabels['riders_list'] ?? 'Riders List' }}</div>
      </a>
    </li>
    @can('riderinvoice_view')
    <li class="menu-item {{ Request::is('riderInvoices*') ? 'active' : '' }}">
      <a href="{{ route('riderInvoices.index') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-file"></i>
        <div>{{ $menuLabels['invoices'] ?? 'Invoices' }}</div>
      </a>
    </li>
    @endcan
    <li class="menu-item {{ Request::is('riderActivities*') ? 'active' : '' }}">
      <a href="{{ route('riderActivities.index') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-bike"></i>
        <div>{{ $menuLabels['activities'] ?? 'Activities' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Request::is('liveactivities*') ? 'active' : '' }}">
      <a href="{{ route('rider.liveactivities') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-bike"></i>
        <div>{{ $menuLabels['live_activities'] ?? 'Live Activities' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Request::is('reports*') ? 'active' : '' }}">
      <a href="{{ route('reports.rider_report') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-users-group"></i>
        {{ $menuLabels['rider_report'] ?? 'Rider Report' }}
      </a>
    </li>
  </ul>
</li>
@endcan
@can('bike_view')
<li class="menu-item {{ Request::is('bikes*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-motorbike"></i>
    <div>{{ $menuLabels['bikes'] ?? 'Bikes' }}</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item {{ Request::is('bikes*') ? 'active' : '' }}">
      <a href="{{ route('bikes.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-motorbike"></i>
        <div>{{ $menuLabels['bike_list'] ?? 'Bike List' }}</div>
      </a>
    </li>
    <li class="menu-item {{ Request::is('bikeMaintenance*') ? 'active' : '' }}">
      <a href="{{ route('bikeMaintenance.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-motorbike"></i>
        <div>{{ $menuLabels['maintenance_overview'] ?? 'Maintenance Overview' }}</div>
      </a>
    </li>
  </ul>
</li>
{{-- <li class="menu-item {{ Request::is('bikeHistories*') ? 'active' : '' }}">
<a href="{{ route('bikeHistories.index') }}" class="menu-link">
  <i class="menu-icon tf-icons ti ti-bike-off"></i>
  <div>Bike History</div>
</a>
</li> --}}
@endcan
@can('sim_view')
<li class="menu-item {{ Request::is('sims*') ? 'active' : '' }}">
  <a href="{{ route('sims.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>{{ $menuLabels['sims'] ?? 'Sims' }}</div>
  </a>
</li>
@endcan

@can('fuel_view')
<li class="menu-item {{ Request::is('fuelCards*') ? 'active' : '' }}">
  <a href="{{ route('fuelCards.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-gas-station"></i>
    <div>{{ $menuLabels['fuel_cards'] ?? 'Fuel Cards' }}</div>
  </a>
</li>
@endcan

@can('rtafine_view')
<li class="menu-item {{ Request::is('rtaFines*') ? 'active' : '' }}">
  <a href="{{ route('rtaFines.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-file-alert"></i>
    <div>{{ $menuLabels['rta_fines'] ?? 'RTA Fines' }}</div>
  </a>
</li>
@endcan
@can('salik_view')
<li class="menu-item {{ Request::is('salik*') ? 'active' : '' }}">
  <a href="{{ route('salik.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-cash"></i>
    <div>{{ $menuLabels['rta_saliks'] ?? 'RTA Saliks' }}</div>
  </a>
</li>
@endcan

@can('inventory_view')
<li class="menu-item ">
  <a href="#" class="menu-link">
    <i class="menu-icon tf-icons ti ti-package"></i>
    <div>{{ $menuLabels['inventory'] ?? 'Inventory' }}</div>
  </a>
</li>
@endcan
@can('visaexpense_view')
<li class="menu-item {{ Request::is('VisaExpense*') ? 'active' : '' }}">
  <a href="{{ route('VisaExpense.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-credit-card"></i>
    <div>{{ $menuLabels['visa_expense'] ?? 'Visa Expense' }}</div>
  </a>
</li>
@endcan
@can('expense_view')
<li class="menu-item ">
  <a href="#" class="menu-link">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>{{ $menuLabels['expenses'] ?? 'Expenses' }}</div>
  </a>
</li>
@endcan
<li class="menu-item {{ Request::is('leasingCompanies*') ? 'open' : '' }} {{ Request::is('leasingCompanyInvoices*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-building"></i>
    <div>{{ $menuLabels['leasing_companies'] ?? 'Leasing Companies' }}</div>
  </a>

  <ul class="menu-sub">

    @can('leasing_view')
    <li class="menu-item {{ Request::is('leasingCompanies*') && !Request::is('leasingCompanyInvoices*') ? 'active' : '' }}">
      <a href="{{ route('leasingCompanies.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-building"></i>
        <div>{{ $menuLabels['leasing_companies_list'] ?? 'Leasing Companies List' }}</div>
      </a>
    </li>
    @endcan
    @can('leasing_company_invoice_view')
    <li class="menu-item {{ Request::is('leasingCompanyInvoices*') ? 'active' : '' }}">
      <a href="{{ route('leasingCompanyInvoices.index') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-file-invoice"></i>
        <div>{{ $menuLabels['leasing_invoices'] ?? 'Invoices' }}</div>
      </a>
    </li>
    @endcan
  </ul>
</li>
@can('garage_view')
<li class="menu-item {{ Request::is('garages*') ? 'active' : '' }}">
  <a href="{{ route('garages.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-parking"></i>
    <div>{{ $menuLabels['garages'] ?? 'Garages' }}</div>
  </a>
</li>
@endcan
@canany(['supplier_view'])
<li class="menu-item {{ Request::is('suppliers*') ? 'open' : '' }}">

  <a href="javascript:void(0); " class="menu-link menu-toggle">
    <i class="menu-icon tf-icons ti ti-truck"></i>
    <div>{{ $menuLabels['supplier'] ?? 'Supplier' }}</div>
  </a>
  <ul class="menu-sub">

    <li class="menu-item {{ Request::is('suppliers*') ? 'active' : '' }}">
      <a href="{{ route('suppliers.index') }}" class="menu-link">
        <div>{{ $menuLabels['suppliers'] ?? 'Suppliers' }}</div>
      </a>
    </li>

    <li class="menu-item {{ Request::is('supplier-invoices*') ? 'active' : '' }}">
      <a href="{{ route('supplier_invoices.index') }}" class="menu-link">
        <div>{{ $menuLabels['supplier_invoices'] ?? 'Supplier Invoices' }}</div>
      </a>
    </li>

  </ul>
</li>
@endcanany
@can('asset_view')
<li class="menu-item ">
  <a href="#" class="menu-link">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>{{ $menuLabels['assets'] ?? 'Assets' }}</div>
  </a>
</li>
@endcan
@can('company_documents_view')
<li class="menu-item {{ Request::is('upload_files*') ? 'active' : '' }}">
  <a href="{{ route('upload_files.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-upload"></i>
    <div>{{ $menuLabels['documents'] ?? 'Documents' }}</div>
  </a>
</li>
@endcan
@can('voucher_view')
<li class="menu-item {{ Request::is('vouchers*') ? 'active' : '' }}">
  <a href="{{ route('vouchers.index') }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-ticket"></i>
    <div>{{ $menuLabels['vouchers'] ?? 'Vouchers' }}</div>
  </a>
</li>
@endcan




@canany(['account_view','gn_ledger'])
<li class="menu-item {{ Request::is('accounts*') ? 'open' : '' }} ">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-graph"></i>
    <div>{{ $menuLabels['accounts'] ?? 'Accounts' }}</div>
  </a>
  <ul class="menu-sub">

    @can('account_view')
    <li class="menu-item {{ Request::is('accounts/tree') ? 'active' : '' }}">
      <a href="{{ route('accounts.tree') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-settings"></i>
        <div>{{ $menuLabels['chart_of_accounts'] ?? 'Chart Of Accounts' }}</div>
      </a>
    </li>
    @endcan

    @can('gn_ledger')

    <li class="menu-item {{ Request::is('accounts/ledger') ? 'active' : '' }}">
      <a href="{{ route('accounts.ledger') }}" class="menu-link">
        <i class="menu-icon tf-icons ti ti-settings"></i>
        <div>{{ $menuLabels['ledger'] ?? 'Ledger' }}</div>
      </a>
    </li>
    @endcan


  </ul>
</li>
@endcan
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

@can('user_view')
<li class="menu-item {{ Request::is('users*') ? 'open' : '' }} {{ Request::is('roles*') ? 'open' : '' }}">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-users-group"></i>
    <div>{{ $menuLabels['user_management'] ?? 'User Management' }}</div>
  </a>
  <ul class="menu-sub">

    <li class="menu-item {{ Request::is('users*') ? 'active' : '' }}">
      <a href="{{ route('users.index') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-users-group"></i>
        {{ $menuLabels['users'] ?? 'Users' }}
      </a>
    </li>


    @can('role_view')
    <li class="menu-item {{ Request::is('roles*') ? 'active' : '' }}">
      <a href="{{ route('roles.index') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-user-check"></i>
        {{ $menuLabels['roles'] ?? 'Roles' }}
      </a>
    </li>


    <li class="menu-item {{ Request::is('permissions*') ? 'active' : '' }}">
      <a href="{{ route('permissions.index') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-user-check"></i>
        {{ $menuLabels['permissions'] ?? 'Permissions' }}
      </a>
    </li>
    @endcan

    @can('activity_logs_view')
    <li class="menu-item {{ Request::is('activity-logs*') ? 'active' : '' }}">
      <a href="{{ route('activity-logs.index') }}" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-history"></i>
        {{ $menuLabels['activity_logs'] ?? 'Activity Logs' }}
      </a>
    </li>
    @endcan
  </ul>
</li>
@endcan

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