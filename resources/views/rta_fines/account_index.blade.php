@extends('rta_fines.viewindex')
@push('third_party_stylesheets')
<style> 
  .totals-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0 10px 16px 10px;
    }

    .total-card {
        flex: 1 1 calc(10% - 8px);
        min-width: 120px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-left-width: 4px;
        border-radius: 6px;
        padding: 8px 10px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        margin-bottom: 0;
    }

    .total-card .label {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: #6b7280;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .total-card .label i {
        font-size: 10px;
        flex-shrink: 0;
    }

    .total-card .value {
        font-size: 14px;
        font-weight: 700;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .total-paid {
        border-left-color: #28a745;
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.06), rgba(16, 185, 129, 0.02));
    }

    .total-paid .label {
        color: #28a745;
    }

    .total-accounts {
        border-left-color: #007bff;
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.06), rgba(59, 130, 246, 0.02));
    }

    .total-accounts .label {
        color: #007bff;
    }

    .total-inactive {
        border-left-color: #373536;
        background: linear-gradient(180deg, rgba(55, 53, 54, 0.06), rgba(55, 53, 54, 0.02));
    }

    .total-inactive .label {
        color: #544d4d;
    }

    .total-unpaid {
        border-left-color: #dc3545;
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.06), rgba(239, 68, 68, 0.02));
    }

    .total-unpaid .label {
        color: #dc3545;
    }

    .total-amount {
        border-left-color: #6f42c1;
        background: linear-gradient(180deg, rgba(111, 66, 193, 0.06), rgba(111, 66, 193, 0.02));
    }

    .total-amount .label {
        color: #6f42c1;
    }

    .total-tickets-amount {
        border-left-color: #c142bb;
        background: linear-gradient(180deg, rgba(193, 66, 187, 0.06), rgba(193, 66, 187, 0.02));
    }

    .total-tickets-amount .label {
        color: #c142bb;
    }

    .total-service-charges {
        border-left-color: #17a2b8;
        background: linear-gradient(180deg, rgba(23, 162, 184, 0.06), rgba(23, 162, 184, 0.02));
    }

    .total-service-charges .label {
        color: #17a2b8;
    }

    .total-admin-charges {
        border-left-color: #ffc107;
        background: linear-gradient(180deg, rgba(255, 193, 7, 0.06), rgba(255, 193, 7, 0.02));
    }

    .total-admin-charges .label {
        color: #ffc107;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .action-dropdown-menu {
            right: -20px;
            min-width: 260px;
        }

        .action-dropdown-btn {
            min-width: 120px;
            padding: 10px 16px;
            font-size: 13px;
        }

        .totals-cards {
        gap: 6px;
        }
        
        .total-card {
        flex: 1 1 calc(50% - 6px);
        min-width: 140px;
        padding: 6px 8px;
        }
        
        .total-card .label {
        font-size: 9px;
        }
        
        .total-card .value {
        font-size: 12px;
        }
        
        /* Reduce table cell padding on mobile */
        #dataTableBuilder td,
        #dataTableBuilder th {
        padding: 6px 8px;
        font-size: 12px;
        }
        
        /* Make badges smaller on mobile */
        .badge {
        font-size: 10px !important;
        padding: 3px 6px;
        }
    }
</style>
@endpush
@section('page_content')
<div class="modal modal-default filtetmodal fade" id="createaccount" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New RTA Fines Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="searchTopbody">
        <form action="{{ route('rtaFines.accountcreate') }}" method="POST">
          @csrf
          <div class="row">
            <div class="form-group c ol-md-12">
              <label for="name">Name</label>
              <input type="text" name="name" class="form-control" placeholder="Enter Your Account Name" required>
            </div>
            <div class="form-group col-md-12">
              <label for="name">Traffic Code Number</label>
              <input type="text" name="traffic_code_number" class="form-control" placeholder="Enter Your Account Name" required>
            </div>
            <div class="form-group col-md-12">
              <label for="account_tax">Service Charges</label>
              <input type="number" name="account_tax" class="form-control" placeholder="Enter Your Service" required>
            </div>
            <div class="form-group col-md-12">
              <label for="admin_charges">Admin Charges</label>
              <input type="number" name="admin_charges" class="form-control" placeholder="Enter Your Admin Charges" required>
            </div>
            <div class="col-md-12 form-group text-center">
              <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Filter Accounts</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('rtaFines.index') }}" method="GET">
          <div class="row">
            <div class="form-group col-md-6">
              <label for="account_code">Account Code</label>
              <input type="number" name="account_code" class="form-control" placeholder="Filter By Account Code" value="{{ request('account_code') }}">
            </div>
            <div class="form-group col-md-6">
              <label for="name">Name</label>
              <input type="text" name="name" class="form-control" placeholder="Filter By Name" value="{{ request('name') }}">
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
<div class="content">
  @include('flash::message')
  
  <div class="clearfix"></div>
  <div class="card">
    <div class="card-header d-flex justify-content-between">
        <div></div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchModal"> <i class="fa fa-search"></i>  Filter RTA Accounts</button>
    </div>
    <div class="totals-cards">
        <div class="total-card total-accounts">
            <div class="label"><i class="fas fa-landmark"></i>Total Accounts</div>
            <div class="value" id="total_orders">{{ $data->count() ?? 0 }}</div>
        </div>
        <div class="total-card total-unpaid">
            <div class="label"><i class="fa fa-times-circle"></i>Unpaid Fines</div>
            <div class="value" id="avg_ontime">{{ DB::table('rta_fines')->where('status', 'unpaid')->count() ?? 0 }}</div>
        </div>
        <div class="total-card total-paid">
            <div class="label"><i class="fas fa-stamp"></i>Paid Fines</div>
            <div class="value" id="total_rejected">{{ DB::table('rta_fines')->where('status', 'paid')->count() ?? 0 }}</div>
        </div>
            <div class="total-card total-amount">
            <div class="label"><i class="far fa-money-bill-alt"></i>Total Amount</div>
            <div class="value" id="total_hours">{{ DB::table('rta_fines')->sum('total_amount') ?? 0 }}</div>
        </div>
        <div class="total-card total-tickets-amount">
            <div class="label"><i class="far fa-money-bill-alt"></i>Ticket Amount</div>
            <div class="value" id="total_hours">{{ DB::table('rta_fines')->sum('amount') ?? 0 }}</div>
        </div>
        <div class="total-card total-service-charges">
            <div class="label"><i class="far fa-money-bill-alt"></i>Service Charges</div>
            <div class="value" id="total_hours">{{ DB::table('rta_fines')->sum('service_charges') ?? 0 }}</div>
        </div>
        <div class="total-card total-admin-charges">
            <div class="label"><i class="far fa-money-bill-alt"></i>Admin Charges</div>
            <div class="value" id="total_hours">{{ DB::table('rta_fines')->sum('admin_fee') ?? 0 }}</div>
        </div>
    </div>
    <div class="card-body table-responsive px-2 py-0" id="table-data">
      @include('rta_fines.account_table', ['data' => $data])
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
    $('#parent_id').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Add Parent Account",
            allowClear: true
    });
    $('#rider_id').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Rider",
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
        url: "{{ route('rtaFines.index') }}",
        type: "GET",
        data: formData,
        success: function(data) {
          $('#table-data').html(data.tableData);

          // Update URL
          let newUrl = "{{ route('rtaFines.index') }}" + (formData ? '?' + formData : '');
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
