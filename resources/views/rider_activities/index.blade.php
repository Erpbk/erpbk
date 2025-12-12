@extends('layouts.app')

@section('title','Rider Activities')
@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h3>Rider Activities</h3>
      </div>
      <div class="col-sm-6">
        {{-- <a class="btn btn-primary float-right"
                       href="{{ route('riderActivities.create') }}">
        Add New
        </a> --}}
      </div>
      <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Filter Rider Activities</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="searchTopbody">
              <form id="filterForm" action="{{ route('riderActivities.index') }}" method="GET">
                <div class="row">
                  <div class="form-group col-md-4">
                    <label for="id">ID</label>
                    <input type="text" name="id" class="form-control" placeholder="Filter By ID" value="{{ request('id') }}">
                  </div>

                  <div class="form-group col-md-4">
                    <label for="rider_id">Filter by Rider</label>
                    <select class="form-control" id="rider_id" name="rider_id">
                      <option value="" selected>Select</option>
                      @foreach($riders as $rider)
                      <option value="{{ $rider->rider_id }}" {{ request('rider_id') == $rider->rider_id ? 'selected' : '' }}>
                        {{ $rider->rider_id . '-' . $rider->name }}
                      </option>
                      @endforeach
                    </select>
                  </div>

                  {{-- NEW DATE RANGE FILTER --}}
                  <div class="form-group col-md-4">
                    <label for="from_date">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                  </div>

                  <div class="form-group col-md-4">
                    <label for="to_date">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                  </div>

                  {{-- BILLING MONTH FILTER --}}
                  <div class="form-group col-md-4">
                    <label for="billing_month">Billing Month</label>
                    <input type="month" name="billing_month" class="form-control" value="{{ request('billing_month') ?? date('Y-m') }}">
                  </div>
                  <div class="form-group col-md-4">
                    <label for="valid_day">Filter by Valid Day</label>
                    <select class="form-control" id="valid_day" name="valid_day">
                      <option value="" selected>All</option>
                      <option value="Yes" {{ request('valid_day') == 'Yes' ? 'selected' : '' }}>Valid</option>
                      <option value="No" {{ request('valid_day') == 'No' ? 'selected' : '' }}>Invalid</option>
                      <option value="Off" {{ request('valid_day') == 'Off' ? 'selected' : '' }}>Off</option>
                    </select>
                  </div>
                  <div class="form-group col-md-4">
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

                  <div class="form-group col-md-4">
                    <label for="payout_type">Filter by Payout Type</label>
                    <select class="form-control" id="payout_type" name="payout_type">
                      <option value="" selected>Select</option>
                      @foreach($payoutTypes as $type)
                      <option value="{{ $type }}" {{ request('payout_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row mb-3">
      @php
      $activity = new App\Models\RiderActivities();
      $result = $activity->select('*');
      if(request('month')){
      $result->where(\DB::raw('DATE_FORMAT(date, "%Y-%m")'), '=', request('month') ?? date('Y-m'));
      }
      if(request('rider_id')){
      $result->where('rider_id',request('rider_id'));
      }

      //$activity->get();
      @endphp
      <div class="col-12 col-md-12">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between">
            <h5 class="card-title mb-0">Statistics</h5>
            <small class="text-body-secondary"><a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);"> <i class="fa fa-search"></i></a></small>
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
      </div>
    </div>
  </div>
</section>

<div class="content px-3">
  @include('flash::message')
  <div class="clearfix"></div>

  <div class="card">
    <div class="card-body table-responsive px-2 py-0" id="table-data">
      @include('rider_activities.table', ['data' => $data, 'totals' => $totals ?? []])
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
    $('#payout_type').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Payout Type",
      allowClear: true, // ✅ cross icon enable
    });
  });
</script>

<script type="text/javascript">
  $(document).ready(function() {
    $('#filterForm').on('submit', function(e) {
      e.preventDefault();

      $('#loading-overlay').show();
      $('#searchModal').modal('hide');

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
@endsection