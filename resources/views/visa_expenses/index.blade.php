@extends('riders.view')
@section('title','Visa Expenses')
@section('page_content')

  {{-- Visa Expenses --}}
  <div class="content">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <div class="col-sm-6">
          <h3>Visa Expense </h3>
        </div>
        <div class="col-sm-6">
          @can('visaexpense_create')
          <a class="btn btn-primary action-btn show-modal"
            href="javascript:void(0);" data-action="{{ route('VisaExpense.create' , $account->id) }}" data-size="lg" data-title="New expense Ticket">
            Add New
          </a>
          @endcan
        </div>
      </div>
      <div class="totals-cards pt-3">
        <div class="total-card total-red">
            <div class="label"><i class="fa fa-times-circle"></i>Total Unpaid Amount</div>
            <div class="value" id="avg_ontime">{{ DB::table('visa_expenses')->where('payment_status' , 'unpaid')->where('rider_id', $account->id)->sum('amount') }}</div>
        </div>
        <div class="total-card total-green">
            <div class="label"><i class="far fa-money-bill-alt"></i>Total Paid Amount</div>
            <div class="value" id="total_hours">{{ DB::table('visa_expenses')->where('payment_status' , 'paid')->where('rider_id', $account->id)->sum('amount') }}</div>
        </div>
        <div class="total-card total-red">
            <div class="label"><i class="fa fa-times-circle"></i>Unpaid Expenses</div>
            <div class="value" id="total_rejected">{{ DB::Table('visa_expenses')->where('rider_id' , $account->id)->where('payment_status' , 'unpaid')->get()->count() }}</div>
        </div>
        <div class="total-card total-green">
            <div class="label"><i class="fa fa-ticket"></i>Paid Expenses</div>
            <div class="value" id="total_orders">{{ DB::Table('visa_expenses')->where('rider_id' , $account->id)->where('payment_status' , 'paid')->get()->count() }}</div>
        </div>
      </div>
      <div class="card-body table-responsive px-2 py-0" id="table-data">
        @include('visa_expenses.table', ['data' => $data])
      </div>
    </div>
  </div>

  {{-- Visa Installment Plan --}}
  <div class="content">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <div class="col-sm-6">
          <h3>Visa Installments </h3>
        </div>
        <div class="col-sm-6">
          @can('visaloan_create')
          <a class="btn btn-sm btn-success action-btn show-modal"
              href="javascript:void(0);" data-action="{{ route('VisaExpense.createInstallmentPlanForm', $account->id) }}" data-size="lg" data-title="Create Installment Entry">
              <i class="fa fa-plus"></i>Installment Plan
          </a>
          @endcan
          @if($installmentData->count() > 0)
          <a href="{{ route('VisaExpense.generateInstallmentInvoice', $account->id) }}"
              class="btn btn-sm btn-info action-btn mx-2 " target="_blank">
              <i class="fa fa-file-invoice"></i>Invoice
          </a>
          @endif
        </div>
      </div>
      <div class="totals-cards pt-3">
        <div class="total-card total-red">
            <div class="label"><i class="fa fa-times-circle"></i>Total Unpaid Amount</div>
            <div class="value" id="avg_ontime">{{ DB::table('visa_installment_plans')->where('status' , 'pending')->where('rider_id', $account->id)->sum('amount') }}</div>
        </div>
        <div class="total-card total-green">
            <div class="label"><i class="far fa-money-bill-alt"></i>Total Paid Amount</div>
            <div class="value" id="total_hours">{{ DB::table('visa_installment_plans')->where('status' , 'paid')->where('rider_id', $account->id)->sum('amount') }}</div>
        </div>
        <div class="total-card total-red">
            <div class="label"><i class="fa fa-times-circle"></i>Unpaid Installments</div>
            <div class="value" id="total_rejected">{{ DB::Table('visa_installment_plans')->where('rider_id' , $account->id)->where('status' , 'pending')->get()->count() }}</div>
        </div>
        <div class="total-card total-green">
            <div class="label"><i class="fa fa-ticket"></i>Paid Installments</div>
            <div class="value" id="total_orders">{{ DB::Table('visa_installment_plans')->where('rider_id' , $account->id)->where('status' , 'paid')->get()->count() }}</div>
        </div>
      </div>
      <div class="card-body table-responsive px-2 py-0" id="table-data">
          @include('visa_expenses.installmentPlanTable', ['data' => $installmentData, 'account' => $account])
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