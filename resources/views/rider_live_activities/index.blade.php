@extends('layouts.app')

@section('title','Rider Live Activities')

@php
$importSummary = $importSummary ?? null;
$importSuccessMessage = session('success');
$importErrorMessage = session('error');
@endphp

@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
@endpush

@section('content')
<div class="row mb-2">
  <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
      <h5>Filter Live Activities</h5>
      <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
      <form id="filterForm" action="{{ route('rider.liveactivities') }}" method="GET">
        <div class="row">
          <div class="form-group col-md-12">
            <label for="id">Filter by Rider ID</label>
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
              <option value="{{ $rider->id }}" {{ request('rider_id') == $rider->id ? 'selected' : '' }}>
                {{ $rider->name }}
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

          {{-- DATE RANGE FILTER --}}
          <div class="form-group col-md-12">
            <label for="from_date">From Date</label>
            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
          </div>

          <div class="form-group col-md-12">
            <label for="to_date">To Date</label>
            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
          </div>

          {{-- BILLING MONTH FILTER --}}
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
            <label for="payout_type">Filter by Payout Type</label>
            <select class="form-control" id="payout_type" name="payout_type">
              <option value="" selected>Select</option>
              @foreach($payoutTypes as $type)
              <option value="{{ $type }}" {{ request('payout_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group col-md-12">
            <label for="bike_assignment_status">Filter by Status</label>
            <select class="form-control" id="bike_assignment_status" name="bike_assignment_status">
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
    </div>
  </div>
  <!-- Filter Overlay -->
  <div id="filterOverlay" class="filter-overlay"></div>
</div>
<section class="content">
  @php
  $activity = new App\Models\liveactivities();
  $result = $activity->select('*');
  if(request('month')){
  $result->where(\DB::raw('DATE_FORMAT(date, "%Y-%m")'), '=', request('month') ?? date('Y-m'));
  }
  if(request('rider_id')){
  $result->where('rider_id',request('rider_id'));
  }

  //$activity->get();
  @endphp
  <div class="card h-100" style="border-radius: 0px !important;">
    <div class="card-header d-flex justify-content-between">
      <h5 class="card-title mb-0"><b>Rider Live Activities</b> (Statistics)</h5>
      <small class="text-body-secondary">
        <a class="btn btn-primary show-modal mx-2" href="javascript:void(0);" data-size="sm" data-title="Import Rider Activities" data-action="{{ route('rider.live_activities_import') }}"> <i class="ti ti-activity"></i> Import Live Activities</a>
        <a class="btn btn-primary openFilterSidebar" href="javascript:void(0);"> <i class="fa fa-search"></i></a>
      </small>
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
      @include('rider_live_activities.table', ['data' => $data])
    </div>
  </div>
</div>

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
    $('#bike_assignment_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Bike Assignment",
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
        url: "{{ route('rider.liveactivities') }}",
        type: "GET",
        data: formData,
        success: function(data) {
          $('#table-data').html(data.tableData);

          // Reinitialize table sorting after AJAX load
          setTimeout(() => {
            initializeTableSorting();
          }, 100);

          // Update URL
          let newUrl = "{{ route('rider.liveactivities') }}" + (formData ? '?' + formData : '');
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

        // Handle import summary messages (redirected from import)
        @if($importSummary)
          (function() {
              const summary = @json($importSummary);
              const successMessage = @json($importSuccessMessage ?? '');
              const errorMessage = @json($importErrorMessage ?? '');
              const errorsRoute = @json(route('rider.live_activities_import_errors', ['type' => 'noon']));

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

              // Priority: fatal errors > validation errors > summary errors > success
              let messageShown = false;

              // 1. Handle fatal error flash message
              if (errorMessage && errorMessage.trim() !== '' && typeof Swal !== 'undefined') {
                Swal.fire({
                  icon: 'error',
                  title: 'Import Failed',
                  text: errorMessage,
                  confirmButtonText: 'OK',
                  confirmButtonColor: '#dc3545'
                });
                messageShown = true;
                // 2. Check for summary errors (row-level errors) - only if no fatal error
                if (!messageShown && summary && summary.errors && Array.isArray(summary.errors) && summary.errors.length > 0 && typeof Swal !== 'undefined') {
                  const totalRows = summary.total_rows ?? 0;
                  const successCount = summary.success_count ?? 0;
                  const skippedCount = summary.skipped_count ?? 0;
                  const errorCount = summary.error_count ?? summary.errors.length;

                  let errorHtml = '<div style="text-align: left;">';
                  errorHtml += '<div class="mb-3" style="background: #f8f9fa; padding: 15px; border-radius: 5px;">';
                  errorHtml += '<div class="row">';
                  errorHtml += `<div class='col-6'><strong>📊 Total Rows:</strong> <span style='color: #007bff;'>${escapeHtml(totalRows)}</span></div>`;
                  errorHtml += `<div class='col-6'><strong>✅ Imported:</strong> <span style='color: #28a745;'>${escapeHtml(successCount)}</span></div>`;
                  errorHtml += '</div>';
                  errorHtml += "<div class='row mt-1'>";
                  errorHtml += `<div class='col-6'><strong>⚠️ Skipped:</strong> <span style='color: #ffc107;'>${escapeHtml(skippedCount)}</span></div>`;
                  errorHtml += `<div class='col-6'><strong>❌ Errors:</strong> <span style='color: #dc3545;'>${escapeHtml(errorCount)}</span></div>`;
                  errorHtml += '</div>';
                  errorHtml += '</div>';

                  errorHtml += '<div class="alert alert-danger" style="max-height: 400px; overflow-y: auto; margin-bottom: 0;">';
                  errorHtml += '<strong>⚠️ Error Details - Please Review:</strong>';
                  errorHtml += '<table class="table table-sm table-bordered mt-2 mb-0" style="background: white;">';
                  errorHtml += '<thead style="background: #343a40; color: white;">';
                  errorHtml += '<tr>';
                  errorHtml += '<th style="width: 80px; text-align: center;">Excel Row #</th>';
                  errorHtml += '<th style="width: 150px;">Error Type</th>';
                  errorHtml += '<th>What Went Wrong</th>';
                  errorHtml += '<th style="width: 120px;">Rider ID</th>';
                  errorHtml += '</tr>';
                  errorHtml += '</thead>';
                  errorHtml += '<tbody>';

                  summary.errors.forEach((errorItem) => {
                    const row = escapeHtml(errorItem.row ?? 'N/A');
                    const errorType = escapeHtml(errorItem.error_type ?? 'N/A');
                    const message = escapeHtml(errorItem.message ?? '-');
                    const riderId = escapeHtml(errorItem.rider_id ?? errorItem.payout_type ?? 'N/A');

                    errorHtml += '<tr>';
                    errorHtml += `<td class="text-center" style="background: #fff3cd;"><strong style="color: #856404; font-size: 14px;">Row ${row}</strong></td>`;
                    errorHtml += `<td><span class="badge badge-danger" style="font-size: 11px;">${errorType}</span></td>`;
                    errorHtml += `<td style="font-size: 13px;">${message}</td>`;
                    errorHtml += `<td><code>${riderId}</code></td>`;
                    errorHtml += '</tr>';
                  });

                  errorHtml += '</tbody></table>';
                  errorHtml += '</div>';

                  errorHtml += '<div class="alert alert-info mt-3 mb-0" style="font-size: 13px;">';
                  errorHtml += '<strong>📝 How to Fix These Errors:</strong>';
                  errorHtml += '<ol style="margin-bottom: 0; padding-left: 25px;">';
                  errorHtml += '<li><strong>Open your Excel file</strong> and locate the row numbers shown above</li>';
                  errorHtml += '<li><strong>Check Rider IDs:</strong> Make sure they exist in the Riders database</li>';
                  errorHtml += '<li><strong>Verify Dates:</strong> Use format YYYY-MM-DD. <strong>IMPORTANT:</strong> Only today\'s date will be processed. Other dates will be skipped.</li>';
                  errorHtml += '<li><strong>Fill Empty Fields:</strong> Ensure rider_id and date are not blank</li>';
                  errorHtml += '<li><strong>Save and Re-import:</strong> After fixing, upload the file again</li>';
                  errorHtml += '</ol>';
                  errorHtml += '</div>';
                  errorHtml += '</div>';

                  Swal.fire({
                    icon: 'warning',
                    title: `⚠️ Import Completed with ${escapeHtml(errorCount)} Error(s)`,
                    html: errorHtml,
                    width: '950px',
                    showCancelButton: true,
                    confirmButtonText: 'View Detailed Report',
                    cancelButtonText: 'Close',
                    confirmButtonColor: '#17a2b8',
                    cancelButtonColor: '#6c757d',
                    customClass: {
                      popup: 'text-left',
                      title: 'swal-title-custom'
                    }
                  }).then((result) => {
                    if (result.isConfirmed && errorsRoute) {
                      window.open(errorsRoute, '_blank');
                    }
                  });
                  messageShown = true;
                }
                // 3. Show success if summary exists with no errors
                else if (!messageShown && summary && (!summary.errors || !Array.isArray(summary.errors) || summary.errors.length === 0) && typeof Swal !== 'undefined') {
                  const totalRows = summary.total_rows ?? 0;
                  const successCount = summary.success_count ?? 0;

                  let successHtml = '<div style="text-align: center;">';
                  successHtml += '<div class="mb-3" style="background: #d4edda; padding: 20px; border-radius: 5px; border: 2px solid #28a745;">';
                  successHtml += '<h4 style="color: #155724; margin-bottom: 15px;">✅ All Records Imported Successfully!</h4>';
                  successHtml += '<div class="row">';
                  successHtml += `<div class="col-6"><strong style="font-size: 16px;">Total Rows:</strong><br><span style="color: #007bff; font-size: 24px; font-weight: bold;">${escapeHtml(totalRows)}</span></div>`;
                  successHtml += `<div class="col-6"><strong style="font-size: 16px;">Imported:</strong><br><span style="color: #28a745; font-size: 24px; font-weight: bold;">${escapeHtml(successCount)}</span></div>`;
                  successHtml += '</div>';
                  successHtml += '</div>';
                  successHtml += '</div>';

                  Swal.fire({
                    icon: 'success',
                    title: 'Import Successful',
                    html: successHtml,
                    confirmButtonText: 'Great!',
                    confirmButtonColor: '#28a745',
                    width: '500px'
                  });
                  messageShown = true;
                }
                // 4. Show simple success message if no summary but success message exists
                else if (!messageShown && successMessage && successMessage.trim() !== '' && typeof Swal !== 'undefined') {
                  Swal.fire({
                    icon: 'success',
                    title: 'Import Successful',
                    text: successMessage,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#28a745'
                  });
                  messageShown = true;
                }
              })(); @endif
          });
</script>
@endsection