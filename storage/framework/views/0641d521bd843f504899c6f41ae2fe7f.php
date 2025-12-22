<!-- need to remove -->
<li class="menu-item <?php echo e(Request::is('/') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('home')); ?>" class="menu-link ">
    <i class="menu-icon tf-icons ti ti-layout-dashboard"></i>
    <div>Dashboard</div>
    
  </a>
</li>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bank_view')): ?>
<li class="menu-item <?php echo e(Request::is('banks') ? 'active' : ''); ?> <?php echo e(Request::is('bank*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('banks.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-building-bank"></i>
    <div>Cash & Banks</div>
  </a>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('item_view')): ?>
<li class="menu-item <?php echo e(Request::is('items*') ? 'open' : ''); ?> <?php echo e(Request::is('garage-items*') ? 'open' : ''); ?>">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-notes"></i>
    <div data-i18n="Front Pages">Items</div>
  </a>
  <ul class="menu-sub">
    <li class="menu-item <?php echo e(Request::is('items*') && !Request::is('garage-items*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('items.index')); ?>" class="menu-link">
        <div>Items List</div>
      </a>
    </li>
    <li class="menu-item <?php echo e(Request::is('garage-items*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('garage-items.index')); ?>" class="menu-link">
        <div>Garage Items</div>
      </a>
    </li>
  </ul>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leads_view')): ?>
<li class="menu-item <?php echo e(Request::is('riderleads*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('riderleads.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>Leads</div>
  </a>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('customer_view')): ?>
<li class="menu-item <?php echo e(Request::is('customers*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('customers.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user-star"></i>
    <div>Customers</div>
  </a>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vendor_view')): ?>
<li class="menu-item <?php echo e(Request::is('vendors*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('vendors.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user-star"></i>
    <div>Vendors</div>
  </a>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('recruiter_view')): ?>
<li class="menu-item <?php echo e(Request::is('recruiters*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('recruiters.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-user-star"></i>
    <div>Recruiters</div>
  </a>
</li>
<?php endif; ?>

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rider_view')): ?>
<li class="menu-item <?php echo e(Request::is('riders*') ? 'open' : ''); ?>

 <?php echo e(Request::is('riderInvoices*') ? 'open' : ''); ?>

 <?php echo e(Request::is('riderActivities*') ? 'open' : ''); ?>

  <?php echo e(Request::is('reports/rider_report*') ? 'open' : ''); ?>

  <?php echo e(Request::is('reports/rider_monthly_report*') ? 'open' : ''); ?>  ">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-user-pin"></i>
    <div data-i18n="Front Pages">Riders</div>
  </a>
  <ul class="menu-sub">

    <li class="menu-item <?php echo e(Request::is('riders*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('riders.index')); ?>" class="menu-link">
        <i class="menu-icon tf-icons ti ti-user-pin"></i>
        <div>Riders List</div>
      </a>
    </li>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('riderinvoice_view')): ?>
    <li class="menu-item <?php echo e(Request::is('riderInvoices*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('riderInvoices.index')); ?>" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-file"></i>
        <div>Invoices</div>
      </a>
    </li>
    <?php endif; ?>
    <li class="menu-item <?php echo e(Request::is('riderActivities*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('riderActivities.index')); ?>" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-bike"></i>
        <div>Activities</div>
      </a>
    </li>
    <li class="menu-item <?php echo e(Request::is('liveactivities*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('rider.liveactivities')); ?>" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-bike"></i>
        <div>Live Activities</div>
      </a>
    </li>
    <li class="menu-item <?php echo e(Request::is('reports*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('reports.rider_report')); ?>" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-users-group"></i>
        Rider Report
      </a>
    </li>
  </ul>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bike_view')): ?>
<li class="menu-item <?php echo e(Request::is('bikes*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('bikes.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-motorbike"></i>
    <div>Bikes</div>
  </a>
</li>

<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('sim_view')): ?>
<li class="menu-item <?php echo e(Request::is('sims*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('sims.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>Sims</div>
  </a>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rtafine_view')): ?>
<li class="menu-item <?php echo e(Request::is('rtaFines*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('rtaFines.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-file-alert"></i>
    <div>RTA Fines</div>
  </a>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('salik_view')): ?>
<li class="menu-item <?php echo e(Request::is('salik*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('salik.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-cash"></i>
    <div>RTA Saliks</div>
  </a>
</li>
<?php endif; ?>


<li class="menu-item ">
  <a href="#" class="menu-link">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>Inventory</div>
  </a>
</li>

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('visaexpense_view')): ?>
<li class="menu-item <?php echo e(Request::is('VisaExpense*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('VisaExpense.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>Visa Expense</div>
  </a>
</li>
<li class="menu-item <?php echo e(Request::is('visa-statuses*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('visa-statuses.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-list-check"></i>
    <div>Visa Status Types</div>
  </a>
</li>
<?php endif; ?>
<li class="menu-item ">
  <a href="#" class="menu-link">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>Expenses</div>
  </a>
</li>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('leasing_view')): ?>
<li class="menu-item <?php echo e(Request::is('leasingCompanies*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('leasingCompanies.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-building"></i>
    <div>Leasing Companies</div>
  </a>
</li>
<?php endif; ?>

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('garage_view')): ?>
<li class="menu-item <?php echo e(Request::is('garages*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('garages.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-parking"></i>
    <div>Garages</div>
  </a>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['supplier_view'])): ?> <!-- Replace with your actual permission(s) -->
<li class="menu-item <?php echo e(Request::is('suppliers*') ? 'open' : ''); ?>">

  <a href="javascript:void(0); " class="menu-link menu-toggle">
    <i class="menu-icon tf-icons ti ti-truck"></i> <!-- You can choose an icon -->
    <div>Supplier</div>
  </a>
  <ul class="menu-sub">

    <li class="menu-item <?php echo e(Request::is('suppliers*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('suppliers.index')); ?>" class="menu-link">
        <div>Suppliers</div>
      </a>
    </li>

    <li class="menu-item <?php echo e(Request::is('supplier-invoices*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('supplier_invoices.index')); ?>" class="menu-link">
        <div>Supplier Invoices</div>
      </a>
    </li>

    <!-- You can add other Supplier submenu items here -->

  </ul>
</li>
<?php endif; ?>
<li class="menu-item ">
  <a href="#" class="menu-link">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>Assets</div>
  </a>
</li>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('files_view')): ?>
<li class="menu-item <?php echo e(Request::is('upload_files*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('upload_files.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-upload"></i>
    <div>Documents</div>
  </a>
</li>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('voucher_view')): ?>
<li class="menu-item <?php echo e(Request::is('vouchers*') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('vouchers.index')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-device-sim"></i>
    <div>Vouchers</div>
  </a>
</li>
<?php endif; ?>




<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['account_view','gn_ledger'])): ?>
<li class="menu-item <?php echo e(Request::is('accounts*') ? 'open' : ''); ?> ">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-graph"></i>
    <div data-i18n="Front Pages">Accounts</div>
  </a>
  <ul class="menu-sub">

    
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('account_view')): ?>
<li class="menu-item <?php echo e(Request::is('accounts/tree') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('accounts.tree')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-settings"></i>
    <div>Chart Of Accounts</div>
  </a>
</li>
<?php endif; ?>

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('gn_ledger')): ?>

<li class="menu-item <?php echo e(Request::is('accounts/ledger') ? 'active' : ''); ?>">
  <a href="<?php echo e(route('accounts.ledger')); ?>" class="menu-link">
    <i class="menu-icon tf-icons ti ti-settings"></i>
    <div>Ledger</div>
  </a>
</li>
<?php endif; ?>


</ul>
</li>
<?php endif; ?>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user_view')): ?>
<li class="menu-item <?php echo e(Request::is('users*') ? 'open' : ''); ?> <?php echo e(Request::is('roles*') ? 'open' : ''); ?>">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-users-group"></i>
    <div data-i18n="Front Pages">User Management</div>
  </a>
  <ul class="menu-sub">

    <li class="menu-item <?php echo e(Request::is('users*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('users.index')); ?>" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-users-group"></i>
        Users
      </a>
    </li>


    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('role_view')): ?>
    <li class="menu-item <?php echo e(Request::is('roles*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('roles.index')); ?>" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-user-check"></i>
        Roles
      </a>
    </li>


    <li class="menu-item <?php echo e(Request::is('permissions*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('permissions.index')); ?>" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-user-check"></i>
        Permissions
      </a>
    </li>
    <?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_logs_view')): ?>
    <li class="menu-item <?php echo e(Request::is('activity-logs*') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('activity-logs.index')); ?>" class="menu-link ">
        <i class="menu-icon tf-icons ti ti-history"></i>
        Activity Logs
      </a>
    </li>
    <?php endif; ?>
  </ul>
</li>
<?php endif; ?>

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['gn_settings','department_view','dropdown_view'])): ?>
<li class="menu-item <?php echo e(Request::is('settings*') ? 'open' : ''); ?> {">
  <a href="javascript:void(0);" class="menu-link menu-toggle ">
    <i class="menu-icon tf-icons ti ti-settings"></i>
    <div data-i18n="Front Pages">Company Settings</div>
  </a>
  <ul class="menu-sub">
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('gn_settings')): ?>
    <li class="menu-item <?php echo e(Request::is('settings/company') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('settings')); ?>" class="menu-link">
        <i class="menu-icon tf-icons ti ti-settings"></i>
        <div>Settings</div>
      </a>
    </li>
    <?php endif; ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('department_view')): ?>
    <li class="menu-item <?php echo e(Request::is('settings/departments') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('departments.index')); ?>" class="menu-link">
        <i class="menu-icon tf-icons ti ti-settings"></i>
        <div>Departments</div>
      </a>
    </li>
    <?php endif; ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('dropdown_view')): ?>

    <li class="menu-item <?php echo e(Request::is('settings/dropdowns') ? 'active' : ''); ?>">
      <a href="<?php echo e(route('dropdowns.index')); ?>" class="menu-link">
        <i class="menu-icon tf-icons ti ti-list"></i>
        <div>Dropdowns</div>
      </a>
    </li>
    <?php endif; ?>

  </ul>
</li>
<?php endif; ?>








<?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/layouts/menu.blade.php ENDPATH**/ ?>