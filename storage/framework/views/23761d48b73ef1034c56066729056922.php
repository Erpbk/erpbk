<?php $__env->startSection('title','Rider Activities'); ?>

<?php $__env->startPush('third_party_stylesheets'); ?>
<link rel="stylesheet" href="<?php echo e(asset('css/riders-styles.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h3>Rider Activities</h3>
      </div>
      <div class="col-sm-6">
        
      </div>
      <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
        <div class="filter-header">
          <h5>Filter Rider Activities</h5>
          <button type="button" class="btn-close" id="closeSidebar"></button>
        </div>
        <div class="filter-body" id="searchTopbody">
          <form id="filterForm" action="<?php echo e(route('riderActivities.index')); ?>" method="GET">
            <div class="row">
              <div class="form-group col-md-12">
                <label for="rider_id">Filter by Rider ID</label>
                <select class="form-control" id="id" name="id">
                  <option value="" selected>Select</option>
                  <?php $__currentLoopData = $riders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($rider->rider_id); ?>" <?php echo e(request('rider_id') == $rider->rider_id ? 'selected' : ''); ?>>
                    <?php echo e($rider->rider_id); ?>

                  </option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </div>

              <div class="form-group col-md-12">
                <label for="rider_id">Filter by Rider</label>
                <select class="form-control" id="rider_id" name="rider_id">
                  <option value="" selected>Select</option>
                  <?php $__currentLoopData = $riders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($rider->id); ?>" <?php echo e(request('rider_id') == $rider->rider_id ? 'selected' : ''); ?>>
                    <?php echo e($rider->name); ?>

                  </option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </div>
              <div class="form-group col-md-12">
                <label for="from_date_range">From Date</label>
                <select class="form-control" id="from_date_range" name="from_date_range">
                  <option value="" selected>Select</option>
                  <option value="Today" <?php echo e(request('from_date_range') == 'Today' ? 'selected' : ''); ?>>Today</option>
                  <option value="Yesterday" <?php echo e(request('from_date_range') == 'Yesterday' ? 'selected' : ''); ?>>Yesterday</option>
                  <option value="Last 7 Days" <?php echo e(request('from_date_range') == 'Last 7 Days' ? 'selected' : ''); ?>>Last 7 Days</option>
                  <option value="Last 30 Days" <?php echo e(request('from_date_range') == 'Last 30 Days' ? 'selected' : ''); ?>>Last 30 Days</option>
                  <option value="Last 90 Days" <?php echo e(request('from_date_range') == 'Last 90 Days' ? 'selected' : ''); ?>>Last 90 Days</option>
                </select>
              </div>
              
              <div class="form-group col-md-12">
                <label for="from_date">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo e(request('from_date')); ?>">
              </div>

              <div class="form-group col-md-12">
                <label for="to_date">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo e(request('to_date')); ?>">
              </div>

              
              <div class="form-group col-md-12">
                <label for="billing_month">Billing Month</label>
                <input type="month" name="billing_month" class="form-control" value="<?php echo e(request('billing_month') ?? date('Y-m')); ?>">
              </div>

              <div class="form-group col-md-12">
                <label for="valid_day">Filter by Valid Day</label>
                <select class="form-control" id="valid_day" name="valid_day">
                  <option value="" selected>All</option>
                  <option value="Yes" <?php echo e(request('valid_day') == 'Yes' ? 'selected' : ''); ?>>Valid</option>
                  <option value="No" <?php echo e(request('valid_day') == 'No' ? 'selected' : ''); ?>>Invalid</option>
                  <option value="Off" <?php echo e(request('valid_day') == 'Off' ? 'selected' : ''); ?>>Off</option>
                </select>
              </div>

              <div class="form-group col-md-12">
                <label for="fleet_supervisor">Filter by Fleet Supervisor</label>
                <select class="form-control" id="fleet_supervisor" name="fleet_supervisor">
                  <option value="" selected>Select</option>
                  <?php $__currentLoopData = $fleetSupervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($supervisor); ?>" <?php echo e(request('fleet_supervisor') == $supervisor ? 'selected' : ''); ?>>
                    <?php echo e($supervisor); ?>

                  </option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </div>

              <div class="form-group col-md-12">
                <label for="payout_type">Filter by Payout Type</label>
                <select class="form-control" id="payout_type" name="payout_type">
                  <option value="" selected>Select</option>
                  <?php $__currentLoopData = $payoutTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($type); ?>" <?php echo e(request('payout_type') == $type ? 'selected' : ''); ?>><?php echo e($type); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
              </div>

              <div class="col-md-12 form-group text-center">
                <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
              </div>
            </div>
          </form>
        </div>
      </div>
      <!-- Filter Overlay -->
      <div id="filterOverlay" class="filter-overlay"></div>
    </div>
    <div class="row mb-3">
      <?php
      $activity = new App\Models\RiderActivities();
      $result = $activity->select('*');
      if(request('month')){
      $result->where(\DB::raw('DATE_FORMAT(date, "%Y-%m")'), '=', request('month') ?? date('Y-m'));
      }
      if(request('rider_id')){
      $result->where('rider_id',request('rider_id'));
      }

      //$activity->get();
      ?>
      <div class="col-12 col-md-12">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between">
            <h5 class="card-title mb-0">Statistics</h5>
            <small class="text-body-secondary"><a class="btn btn-primary openFilterSidebar" href="javascript:void(0);"> <i class="fa fa-search"></i></a></small>
          </div>
          <div class="card-body">
            <div id="totalsBar" class="mb-2">
              <div class="totals-cards">

                <div class="total-card total-valid-days">
                  <div class="label"><i class="fa fa-calendar-check"></i>Total Orders</div>
                  <div class="value" id="total_orders"><?php echo e(number_format($totals['total_orders'] ?? 0)); ?></div>
                </div>
                <div class="total-card total-ontime">
                  <div class="label"><i class="fa fa-calendar-check"></i>OnTime%</div>
                  <div class="value" id="avg_ontime"><?php echo e(number_format($totals['avg_ontime'] ?? 0, 2)); ?>%</div>
                </div>
                <div class="total-card total-rejected">
                  <div class="label"><i class="fa fa-calendar-check"></i>Rejection</div>
                  <div class="value" id="total_rejected"><?php echo e(number_format($totals['total_rejected'] ?? 0)); ?></div>
                </div>
                <div class="total-card total-hours">
                  <div class="label"><i class="fa fa-calendar-check"></i>Total Hours</div>
                  <div class="value" id="total_hours"><?php echo e(number_format($totals['total_hours'] ?? 0, 2)); ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="content px-3">
  <?php echo $__env->make('flash::message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
  <div class="clearfix"></div>

  <div class="card">
    <div class="card-body table-responsive px-2 py-0" id="table-data">
      <?php echo $__env->make('rider_activities.table', ['data' => $data, 'totals' => $totals ?? []], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
  </div>
</div>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-script'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
  function confirmDelete(url) {
    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = url;
      }
    })
  }
  $(document).ready(function() {
    $('#fleet_supervisor').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Fleet SuperVisor",
      allowClear: true, // ✅ cross icon enable
    });
    $('#rider_id').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Rider",
      allowClear: true, // ✅ cross icon enable
    });
    $('#from_date_range').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By From Date Range",
      allowClear: true, // ✅ cross icon enable
    });
    $('#id').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Rider ID",
      allowClear: true, // ✅ cross icon enable
    });
    $('#payout_type').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Payout Type",
      allowClear: true, // ✅ cross icon enable
    });
    $('#valid_day').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Valid Day",
      allowClear: true, // ✅ cross icon enable
    });
    $('#from_date_range').on('change', function() {
      const selectedValue = $(this).val();
      if (selectedValue === 'Today') {
        $('#from_date').val(new Date().toISOString().split('T')[0]);
      } else if (selectedValue === 'Yesterday') {
        $('#from_date').val(new Date(new Date().setDate(new Date().getDate() - 1)).toISOString().split('T')[0]);
      } else if (selectedValue === 'Last 7 Days') {
        $('#from_date').val(new Date(new Date().setDate(new Date().getDate() - 7)).toISOString().split('T')[0]);
      } else if (selectedValue === 'Last 30 Days') {
        $('#from_date').val(new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0]);
      } else if (selectedValue === 'Last 90 Days') {
        $('#from_date').val(new Date(new Date().setDate(new Date().getDate() - 90)).toISOString().split('T')[0]);
      }
    });
  });
</script>

<script type="text/javascript">
  $(document).ready(function() {
    // Filter sidebar functionality - open on hover
    $(document).on('mouseenter', '#openFilterSidebar, .openFilterSidebar', function(e) {
      e.preventDefault();
      console.log('Filter button hovered!');
      $('#filterSidebar').addClass('open');
      $('#filterOverlay').addClass('show');
      return false;
    });

    // Keep the original click handler for mobile devices
    $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
      e.preventDefault();
      console.log('Filter button clicked!');
      $('#filterSidebar').addClass('open');
      $('#filterOverlay').addClass('show');
      return false;
    });

    $('#closeSidebar, #filterOverlay').on('click', function() {
      $('#filterSidebar').removeClass('open');
      $('#filterOverlay').removeClass('show');
    });

    $('#filterForm').on('submit', function(e) {
      e.preventDefault();

      $('#loading-overlay').show();
      $('#filterSidebar').removeClass('open');
      $('#filterOverlay').removeClass('show');

      const loaderStartTime = Date.now();

      // Exclude _token and empty fields
      let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
      let formData = $.param(filteredFields);

      $.ajax({
        url: "<?php echo e(route('riderActivities.index')); ?>",
        type: "GET",
        data: formData,
        success: function(data) {
          $('#table-data').html(data.tableData);

          // Update totals cards if totals are provided
          if (data.totals) {
            $('#total_orders').text(parseInt(data.totals.total_orders || 0).toLocaleString());
            $('#avg_ontime').text(parseFloat(data.totals.avg_ontime || 0).toFixed(2) + '%');
            $('#total_rejected').text(parseInt(data.totals.total_rejected || 0).toLocaleString());
            $('#total_hours').text(parseFloat(data.totals.total_hours || 0).toFixed(2));
          }

          // Update URL
          let newUrl = "<?php echo e(route('riderActivities.index')); ?>" + (formData ? '?' + formData : '');
          history.pushState(null, '', newUrl);


          // Ensure loader is visible at least 3s
          const elapsed = Date.now() - loaderStartTime;
          const remaining = 1000 - elapsed;
          setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
        },
        error: function(xhr, status, error) {
          console.error(error);

          const elapsed = Date.now() - loaderStartTime;
          const remaining = 1000 - elapsed;
          setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
        }
      });
    });
  });
</script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('#dataTableBuilder');
    const headers = table.querySelectorAll('th.sorting');
    const tbody = table.querySelector('tbody');

    headers.forEach((header, colIndex) => {
      header.addEventListener('click', () => {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAsc = header.classList.contains('sorted-asc');

        // Clear previous sort classes
        headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));

        // Add new sort direction
        header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');

        // Sort logic
        rows.sort((a, b) => {
          let aText = a.children[colIndex]?.textContent.trim().toLowerCase();
          let bText = b.children[colIndex]?.textContent.trim().toLowerCase();

          const aVal = isNaN(aText) ? aText : parseFloat(aText);
          const bVal = isNaN(bText) ? bText : parseFloat(bText);

          if (aVal < bVal) return isAsc ? 1 : -1;
          if (aVal > bVal) return isAsc ? -1 : 1;
          return 0;
        });

        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
      });
    });
  });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rider_activities/index.blade.php ENDPATH**/ ?>