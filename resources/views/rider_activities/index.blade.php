@extends('layouts.app')

@section('title','Rider Activities')

@php
$isAllTab = $isAllTab ?? false;
$isConsolidated = $isConsolidated ?? false;
$projects = $projects ?? collect();
@endphp

@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
@endpush

@section('content')
@can('riders_activities_view')
<div class="row mb-2">
  <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
      <h5>{{ $isAllTab ? 'Filter Rider Summary' : 'Filter Rider Activities' }}</h5>
      <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
      @if($isAllTab)
      <form id="allFilterForm" action="{{ route('riderActivities.index') }}" method="GET">
        <input type="hidden" name="tab" value="all">
        @if(request()->filled('top_option_id'))
          <input type="hidden" name="top_option_id" value="{{ request('top_option_id') }}">
        @endif
        <div class="row">
          <div class="form-group col-md-12">
            <label for="all_from_date">From Date</label>
            <input type="date" name="from_date" id="all_from_date" class="form-control" value="{{ request('from_date') }}">
          </div>
          <div class="form-group col-md-12">
            <label for="all_to_date">To Date</label>
            <input type="date" name="to_date" id="all_to_date" class="form-control" value="{{ request('to_date') }}">
          </div>
          <div class="form-group col-md-12">
            <label for="all_billing_month">Billing Month</label>
            <input type="month" name="billing_month" id="all_billing_month" class="form-control" value="{{ request('billing_month') }}">
          </div>
          <div class="form-group col-md-12">
            <label for="all_customer_id">Project</label>
            <select class="form-control" id="all_customer_id" name="customer_id">
              <option value="">Select</option>
              @foreach($projects as $project)
              <option value="{{ $project->id }}" {{ (string) request('customer_id') === (string) $project->id ? 'selected' : '' }}>
                {{ $project->name }}
              </option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-12">
            <label for="all_fleet_supervisor">Fleet Supervisor</label>
            <select class="form-control" id="all_fleet_supervisor" name="fleet_supervisor">
              <option value="">Select</option>
              @foreach($fleetSupervisors as $supervisor)
              <option value="{{ $supervisor }}" {{ request('fleet_supervisor') == $supervisor ? 'selected' : '' }}>
                {{ $supervisor }}
              </option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-12">
            <label for="all_rider_status">Rider Status</label>
            <select class="form-control" id="all_rider_status" name="rider_status">
              <option value="">All</option>
              <option value="active" {{ request('rider_status') == 'active' ? 'selected' : '' }}>Active</option>
              <option value="inactive" {{ request('rider_status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
              <option value="vacation" {{ request('rider_status') == 'vacation' ? 'selected' : '' }}>Vacation</option>
              <option value="absconded" {{ request('rider_status') == 'absconded' ? 'selected' : '' }}>Absconded</option>
            </select>
          </div>
          <div class="form-group col-md-12">
            <label for="all_rider_id">Rider</label>
            <select class="form-control" id="all_rider_id" name="rider_id">
              <option value="">Select</option>
              @foreach($riders as $rider)
              @php
              $riderStatusLabel = \App\Models\Riders::employmentStatusDisplay($rider->status ?? null)['label'] ?? 'Active';
              $isInactiveRider = (int) ($rider->status ?? 1) !== 1;
              @endphp
              <option value="{{ $rider->id }}" {{ (string) request('rider_id') === (string) $rider->id ? 'selected' : '' }}>
                {{ $rider->name }}@if($isInactiveRider) ({{ $riderStatusLabel }})@endif
              </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-12 form-group text-center">
            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
            <a href="{{ route('riderActivities.index', ['tab' => 'all']) }}" class="btn btn-outline-secondary pull-right mt-3 mx-2">Reset</a>
          </div>
        </div>
      </form>
      @else
      <form id="filterForm" action="{{ route('riderActivities.index') }}" method="GET">
        @if(request()->filled('top_option_id'))
          <input type="hidden" name="top_option_id" value="{{ request('top_option_id') }}">
        @endif
        <div class="row">
          <div class="form-group col-md-12">
            <label for="rider_id">Filter by Rider ID</label>
            <select class="form-control" id="id" name="id">
              <option value="" selected>Select</option>
              @foreach($riders as $rider)
              <option value="{{ $rider->rider_id }}" {{ request('id') == $rider->rider_id ? 'selected' : '' }}>
                {{ $rider->rider_id }}
              </option>
              @endforeach
            </select>
          </div>

          <div class="form-group col-md-12">
            <label for="rider_id">Filter by Rider</label>
            <select class="form-control" id="rider_id" name="rider_id">
              <option value="" selected>Select</option>
              @foreach($riders as $rider)
              @php
              $riderStatusLabel = \App\Models\Riders::employmentStatusDisplay($rider->status ?? null)['label'] ?? 'Active';
              $isInactiveRider = (int) ($rider->status ?? 1) !== 1;
              @endphp
              <option value="{{ $rider->id }}" {{ request('rider_id') == $rider->id ? 'selected' : '' }}>
                {{ $rider->name }}@if($isInactiveRider) ({{ $riderStatusLabel }})@endif
              </option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-12">
            <label for="from_date_range">From Date</label>
            <select class="form-control" id="from_date_range" name="from_date_range">
              <option value="" selected>Select</option>
              <option value="Today" {{ request('from_date_range') == 'Today' ? 'selected' : '' }}>Today</option>
              <option value="Yesterday" {{ request('from_date_range') == 'Yesterday' ? 'selected' : '' }}>Yesterday</option>
              <option value="Last 7 Days" {{ request('from_date_range') == 'Last 7 Days' ? 'selected' : '' }}>Last 7 Days</option>
              <option value="Last 30 Days" {{ request('from_date_range') == 'Last 30 Days' ? 'selected' : '' }}>Last 30 Days</option>
              <option value="Last 90 Days" {{ request('from_date_range') == 'Last 90 Days' ? 'selected' : '' }}>Last 90 Days</option>
            </select>
          </div>
          <div class="form-group col-md-12">
            <label for="from_date">From Date</label>
            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
          </div>

          <div class="form-group col-md-12">
            <label for="to_date">To Date</label>
            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
          </div>

          <div class="form-group col-md-12">
            <label for="billing_month">Billing Month</label>
            <input type="month" name="billing_month" class="form-control" value="{{ request('billing_month') ?? date('Y-m') }}">
          </div>

          <div class="form-group col-md-12">
            <label for="valid_day">Filter by Valid Day</label>
            <select class="form-control" id="valid_day" name="valid_day">
              <option value="" selected>All</option>
              <option value="Yes" {{ request('valid_day') == 'Yes' ? 'selected' : '' }}>Valid</option>
              <option value="No" {{ request('valid_day') == 'No' ? 'selected' : '' }}>Invalid</option>
              <option value="Off" {{ request('valid_day') == 'Off' ? 'selected' : '' }}>Off</option>
            </select>
          </div>

          <div class="form-group col-md-12">
            <label for="fleet_supervisor">Filter by Fleet Supervisor</label>
            <select class="form-control" id="fleet_supervisor" name="fleet_supervisor">
              <option value="" selected>Select</option>
              @foreach($fleetSupervisors as $supervisor)
              <option value="{{ $supervisor }}" {{ request('fleet_supervisor') == $supervisor ? 'selected' : '' }}>
                {{ $supervisor }}
              </option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-12">
            <label for="bike_assignment_status">Filter by Status</label>
            <select class="form-control " id="bike_assignment_status" name="bike_assignment_status">
              <option value="" selected>Select</option>
              <option value="Active" {{ request('bike_assignment_status') == 'Active' ? 'selected' : '' }}>Active</option>
              <option value="Inactive" {{ request('bike_assignment_status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>
          <div class="col-md-12 form-group text-center">
            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
          </div>
        </div>
      </form>
      @endif
    </div>
  </div>
  <div id="filterOverlay" class="filter-overlay"></div>
</div>

@include('rider_activities.partials.tabs_and_operations', [
'activeActivitiesTab' => $isAllTab ? 'summary' : 'activities'
])

@if($isAllTab)
<section class="content">
  <div class="card h-100" style="border-radius: 0px !important;">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0"><b>Rider Summary</b></h5>
    </div>
    <div class="card-body">
      @if($isConsolidated)
      <div class="alert alert-info mb-3 py-2">
        Showing one consolidated row per rider
        @if(request('rider_id'))
        for the selected rider
        @endif
        @if(request('from_date') && request('to_date'))
        between
        <strong>{{ \Carbon\Carbon::parse(request('from_date'))->format('d M Y') }}</strong>
        and
        <strong>{{ \Carbon\Carbon::parse(request('to_date'))->format('d M Y') }}</strong>
        @elseif(request('from_date'))
        from <strong>{{ \Carbon\Carbon::parse(request('from_date'))->format('d M Y') }}</strong>
        @elseif(request('to_date'))
        up to <strong>{{ \Carbon\Carbon::parse(request('to_date'))->format('d M Y') }}</strong>
        @elseif(request('billing_month'))
        for billing month
        <strong>{{ \Carbon\Carbon::parse(request('billing_month') . '-01')->format('M Y') }}</strong>
        @else
        across all activity records
        @endif.
      </div>
      @endif

      <div id="totalsBar" class="mb-2">
        <div class="totals-cards">
          <div class="total-card total-delivered">
            <div class="label"><i class="fa fa-calendar"></i>Total Days</div>
            <div class="value" id="working_days">{{ number_format($totals['valid_days'] ?? 0) }}</div>
          </div>
          <div class="total-card total-valid-days">
            <div class="label"><i class="fa fa-calendar-check"></i>Total Orders</div>
            <div class="value" id="total_orders">{{ number_format($totals['total_orders'] ?? 0) }}</div>
          </div>
          <div class="total-card total-ontime">
            <div class="label"><i class="fa fa-calendar-check"></i>OnTime%</div>
            <div class="value" id="avg_ontime">{{ number_format($totals['avg_ontime'] ?? 0, 2) }}%</div>
          </div>
          <div class="total-card total-rejected">
            <div class="label"><i class="fa fa-calendar-check"></i>Rejection</div>
            <div class="value" id="total_rejected">{{ number_format($totals['total_rejected'] ?? 0) }}</div>
          </div>
          <div class="total-card total-hours">
            <div class="label"><i class="fa fa-calendar-check"></i>Total Hours</div>
            <div class="value" id="total_hours">{{ number_format($totals['total_hours'] ?? 0, 2) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="content">
  @include('flash::message')
  <div class="clearfix"></div>

  <div class="card" style="border-radius: 0px !important;">
    <div class="card-body table-responsive px-2 py-0" id="table-data">
      @include('rider_activities.table', ['data' => $data, 'totals' => $totals ?? [], 'isConsolidated' => $isConsolidated, 'isAllTab' => false, 'hideDay' => true])
    </div>
  </div>
</div>
@else
<section class="content">
  <div class="card h-100" style="border-radius: 0px !important;">
    <div class="card-header d-flex justify-content-between">
      <h5 class="card-title mb-0"><b>Rider Activities</b> (Statistics)</h5>
    </div>
    <div class="card-body">
      <div id="totalsBar" class="mb-2">
        <div class="totals-cards">
          <div class="total-card total-valid-days">
            <div class="label"><i class="fa fa-calendar-check"></i>Total Orders</div>
            <div class="value" id="total_orders">{{ number_format($totals['total_orders'] ?? 0) }}</div>
          </div>
          <div class="total-card total-ontime">
            <div class="label"><i class="fa fa-calendar-check"></i>OnTime%</div>
            <div class="value" id="avg_ontime">{{ number_format($totals['avg_ontime'] ?? 0, 2) }}%</div>
          </div>
          <div class="total-card total-rejected">
            <div class="label"><i class="fa fa-calendar-check"></i>Rejection</div>
            <div class="value" id="total_rejected">{{ number_format($totals['total_rejected'] ?? 0) }}</div>
          </div>
          <div class="total-card total-hours">
            <div class="label"><i class="fa fa-calendar-check"></i>Total Hours</div>
            <div class="value" id="total_hours">{{ number_format($totals['total_hours'] ?? 0, 2) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="content">
  @include('flash::message')
  <div class="clearfix"></div>

  <div class="card" style="border-radius: 0px !important;">
    <div class="card-body table-responsive px-2 py-0" id="table-data">
      @include('rider_activities.table', ['data' => $data, 'totals' => $totals ?? [], 'isConsolidated' => false, 'isAllTab' => false])
    </div>
  </div>
</div>
@endif
@else
<div class="card">
  <div class="card-body">
    <h5 class="card-title">You are not authorized to access this page</h5>
  </div>
</div>
@endcan
@endsection
@section('page-script')
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
    @unless($isAllTab)
    $('#fleet_supervisor').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Fleet SuperVisor",
      allowClear: true,
    });
    $('#rider_id').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Rider",
      allowClear: true,
    });
    $('#from_date_range').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By From Date Range",
      allowClear: true,
    });
    $('#id').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Rider ID",
      allowClear: true,
    });
    $('#payout_type').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Payout Type",
      allowClear: true,
    });
    $('#valid_day').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Valid Day",
      allowClear: true,
    });
    $('#bike_assignment_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Bike Assignment",
      allowClear: true,
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
    @else
    $('#all_fleet_supervisor').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Fleet SuperVisor",
      allowClear: true,
      width: '100%',
    });
    $('#all_rider_id').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Rider",
      allowClear: true,
      width: '100%',
    });
    $('#all_customer_id').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Project",
      allowClear: true,
      width: '100%',
    });
    $('#all_rider_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Rider Status",
      allowClear: true,
      width: '100%',
    });
    @endunless
  });
</script>

<script type="text/javascript">
  $(document).ready(function() {
    // Operations dropdown
    $('#riderActivitiesOpsBtn').on('click', function(e) {
      e.stopPropagation();
      const dropdown = $('#riderActivitiesOpsMenu');
      const btn = $(this);
      if (dropdown.hasClass('show')) {
        dropdown.removeClass('show');
        btn.removeClass('open');
      } else {
        $('.action-dropdown-menu').removeClass('show');
        $('.action-dropdown-btn').removeClass('open');
        dropdown.addClass('show');
        btn.addClass('open');
      }
    });

    $(document).on('click', function(e) {
      if (!$(e.target).closest('.action-dropdown-container').length) {
        $('.action-dropdown-menu').removeClass('show');
        $('.action-dropdown-btn').removeClass('open');
      }
    });

    // Filter sidebar functionality - open on hover
    $(document).on('mouseenter', '#openFilterSidebar, .openFilterSidebar', function(e) {
      e.preventDefault();
      $('#filterSidebar').addClass('open');
      $('#filterOverlay').addClass('show');
      return false;
    });

    // Keep the original click handler for mobile devices
    $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
      e.preventDefault();
      $('#filterSidebar').addClass('open');
      $('#filterOverlay').addClass('show');
      return false;
    });

    $('#closeSidebar, #filterOverlay').on('click', function() {
      $('#filterSidebar').removeClass('open');
      $('#filterOverlay').removeClass('show');
    });

    @unless($isAllTab)
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
        url: "{{ route('riderActivities.index') }}",
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

          // Reinitialize table sorting after AJAX load
          setTimeout(() => {
            initializeTableSorting();
          }, 100);

          // Update URL
          let newUrl = "{{ route('riderActivities.index') }}" + (formData ? '?' + formData : '');
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
    @else
    $('#allFilterForm').on('submit', function(e) {
      e.preventDefault();

      $('#loading-overlay').show();
      $('#filterSidebar').removeClass('open');
      $('#filterOverlay').removeClass('show');
      const loaderStartTime = Date.now();

      let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && String(field.value).trim() !== '');
      let formData = $.param(filteredFields);

      $.ajax({
        url: "{{ route('riderActivities.index') }}",
        type: "GET",
        data: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(data) {
          $('#table-data').html(data.tableData);

          if (data.totals) {
            $('#working_days').text(parseInt(data.totals.valid_days || 0).toLocaleString());
            $('#total_orders').text(parseInt(data.totals.total_orders || 0).toLocaleString());
            $('#avg_ontime').text(parseFloat(data.totals.avg_ontime || 0).toFixed(2) + '%');
            $('#total_rejected').text(parseInt(data.totals.total_rejected || 0).toLocaleString());
            $('#total_hours').text(parseFloat(data.totals.total_hours || 0).toFixed(2));
          }

          setTimeout(() => {
            initializeTableSorting();
          }, 100);

          let newUrl = "{{ route('riderActivities.index') }}" + (formData ? '?' + formData : '');
          history.pushState(null, '', newUrl);

          // Reload when consolidated mode toggles so the info banner stays in sync
          if (typeof data.isConsolidated !== 'undefined') {
            const currentlyConsolidated = {
              {
                $isConsolidated ? 'true' : 'false'
              }
            };
            if (data.isConsolidated !== currentlyConsolidated) {
              window.location.href = newUrl;
              return;
            }
          }

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
    @endunless
  });
</script>
<script>
  // Function to initialize table sorting
  function initializeTableSorting() {
    const table = document.querySelector('#dataTableBuilder');
    if (!table) return;

    const headers = table.querySelectorAll('th.sorting');
    const tbody = table.querySelector('tbody');

    headers.forEach((header, colIndex) => {
      // Remove existing listeners to prevent duplicates
      header.replaceWith(header.cloneNode(true));
    });

    // Re-select headers after cloning
    const newHeaders = table.querySelectorAll('th.sorting');

    newHeaders.forEach((header, colIndex) => {
      header.addEventListener('click', () => {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAsc = header.classList.contains('sorted-asc');

        // Clear previous sort classes
        newHeaders.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));

        // Add new sort direction
        header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');

        // Sort logic
        rows.sort((a, b) => {
          let aText = a.children[colIndex]?.textContent.trim().toLowerCase();
          let bText = b.children[colIndex]?.textContent.trim().toLowerCase();

          // Handle percentage signs
          aText = aText.replace('%', '');
          bText = bText.replace('%', '');

          // Handle dates
          const aDate = new Date(aText);
          const bDate = new Date(bText);

          let aVal, bVal;

          if (!isNaN(aDate.getTime()) && !isNaN(bDate.getTime()) && aText.includes(' ')) {
            // It's a date
            aVal = aDate.getTime();
            bVal = bDate.getTime();
          } else {
            // Number or text
            aVal = isNaN(parseFloat(aText)) ? aText : parseFloat(aText);
            bVal = isNaN(parseFloat(bText)) ? bText : parseFloat(bText);
          }

          if (aVal < bVal) return isAsc ? 1 : -1;
          if (aVal > bVal) return isAsc ? -1 : 1;
          return 0;
        });

        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
      });
    });
  }

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
    initializeTableSorting();
  });
</script>

<script>
  // Display SweetAlert messages from session flash
  document.addEventListener('DOMContentLoaded', function() {
    @php
    $successMessage = session('success');
    $errorMessage = session('error');
    @endphp

    const successMessage = @json($successMessage ?? '');
    const errorMessage = @json($errorMessage ?? '');

    const escapeHtml = (value) => {
      if (value === null || value === undefined) {
        return '';
      }
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    };

    // Show error message from session flash if exists
    if (errorMessage) {
      // Check if error message contains multiple errors (separated by |)
      const errorParts = errorMessage.split(' | ');

      if (errorParts.length > 1) {
        // Multiple errors - show in a list
        let errorList = '<ul style="text-align: left; margin: 0; padding-left: 20px; max-height: 400px; overflow-y: auto;">';
        errorParts.forEach((error) => {
          errorList += `<li style="margin-bottom: 8px;">${escapeHtml(error)}</li>`;
        });
        errorList += '</ul>';

        Swal.fire({
          icon: 'error',
          title: '⚠️ Import Failed',
          html: errorList,
          confirmButtonText: 'OK',
          confirmButtonColor: '#dc3545',
          width: '700px',
          customClass: {
            popup: 'text-left'
          }
        });
      } else {
        // Single error message
        Swal.fire({
          icon: 'error',
          title: '⚠️ Import Failed',
          text: errorMessage,
          confirmButtonText: 'OK',
          confirmButtonColor: '#dc3545'
        });
      }
    }

    // Show success message from session flash if exists
    if (successMessage) {
      Swal.fire({
        icon: 'success',
        title: '✅ Import Successful',
        text: successMessage,
        confirmButtonText: 'OK',
        confirmButtonColor: '#28a745',
        timer: 3000,
        timerProgressBar: true
      });
    }
  });
</script>
@endsection