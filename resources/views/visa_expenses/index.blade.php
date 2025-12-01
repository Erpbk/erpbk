@extends('riders.view')
@section('title','Visa Expenses')
@section('page_content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h3>{{ $account->name }} | Visa Expense </h3>
      </div>
      <div class="col-sm-6">
        @can('visaloan_view')
        <a class="btn btn-primary action-btn show-modal mx-2"
          href="{{ route('VisaExpense.installmentPlan' , $account->id) }}" data-size="lg" data-title="Installment Plan">
          Installment Plan
        </a>
        @endcan
        @can('visaexpense_create')
        <a class="btn btn-primary action-btn show-modal"
          href="javascript:void(0);" data-action="{{ route('VisaExpense.create' , $account->id) }}" data-size="lg" data-title="New expense Ticket">
          Add New
        </a>
        @endcan
        <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Filter Expenses</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="searchTopbody">
                <form id="filterForm" action="{{ route('VisaExpense.index') }}" method="GET">
                  <div class="row">
                    <div class="form-group col-md-4">
                      <label for="trans_date">Ticket Number</label>
                      <input type="date" name="trans_date" class="form-control" placeholder="Filter By Transcation Date" value="{{ request('trans_date') }}">
                    </div>
                    <div class="form-group col-md-4">
                      <label for="trans_code">Transcation Code</label>
                      <input type="text" name="trans_code" class="form-control" placeholder="Filter By Transcation Code" value="{{ request('trans_code') }}">
                    </div>
                    <div class="form-group col-md-4">
                      <label for="date">Date</label>
                      <input type="date" name="date" class="form-control" placeholder="Filter By date" value="{{ request('date') }}">
                    </div>
                    <div class="form-group col-md-4">
                      <label for="visa_status">Filter by Visa Status</label>
                      <select class="form-control " id="visa_status" name="visa_status">
                        <option value="" selected>Select</option>
                        @foreach($visaStatuses as $status)
                        <option value="{{ $status->name }}" {{ request('visa_status') === $status->name ? 'selected' : '' }}>{{ $status->name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="form-group col-md-4">
                      <label for="payment_status">Filter by Payment Status</label>
                      <select class="form-control " id="payment_status" name="payment_status">
                        <option value="" selected>Select</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option {{ request('payment_status') === 'paid' ? 'selected' : '' }} value="unpaid">unpaid</option>
                      </select>
                    </div>
                    <div class="col-md-12 form-group text-center">
                      <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-12 mt-3">
        <div class="card h-100">
          <div class="card-body d-flex align-items-end">
            <div class="w-100">
              <div class="row gy-3">
                <div class="col-md-3 col-6">
                  <div class="d-flex align-items-center">
                    <div class="badge rounded bg-label-danger me-4 p-2"><i class="menu-icon tf-icons ti ti-cash"></i></div>
                    <div class="card-info">
                      <h5 class="mb-0">{{ DB::table('visa_expenses')->where('payment_status' , 'unpaid')->where('rider_id', $account->id)->sum('amount') }}</h5>
                      <small>Total Unpaid Amount</small>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-6">
                  <div class="d-flex align-items-center">
                    <div class="badge rounded bg-label-info me-4 p-2"><i class="menu-icon tf-icons ti ti-cash"></i></div>
                    <div class="card-info">
                      <h5 class="mb-0">{{ DB::table('visa_expenses')->where('payment_status' , 'paid')->where('rider_id', $account->id)->sum('amount') }}</h5>
                      <small>Total Paid Amount</small>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-6">
                  <div class="d-flex align-items-center">
                    <div class="badge rounded bg-label-success me-4 p-2"><i class="menu-icon tf-icons ti ti-receipt"></i></div>
                    <div class="card-info">
                      <h5 class="mb-0">{{ DB::Table('visa_expenses')->where('rider_id' , $account->id)->where('payment_status' , 'paid')->get()->count() }}</h5>
                      <small>Paid Expense</small>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-6">
                  <div class="d-flex align-items-center">
                    <div class="badge rounded bg-label-danger me-4 p-2"><i class="menu-icon tf-icons ti ti-receipt"></i></div>
                    <div class="card-info">
                      <h5 class="mb-0">{{ DB::Table('visa_expenses')->where('rider_id' , $account->id)->where('payment_status' , 'unpaid')->get()->count() }}</h5>
                      <small>Unpaid Expenses</small>
                    </div>
                  </div>
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
      @include('visa_expenses.table', ['data' => $data])
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
    $('#payment_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Payment Status",
      allowClear: true
    });
    $('#visa_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Visa Status",
      allowClear: true
    });
    $('#bike_id').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Bike Plate",
      allowClear: true
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
        url: "{{ route('VisaExpense.index') }}",
        type: "GET",
        data: formData,
        success: function(data) {
          $('#table-data').html(data.tableData);

          // Update URL
          let newUrl = "{{ route('VisaExpense.index') }}" + (formData ? '?' + formData : '');
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