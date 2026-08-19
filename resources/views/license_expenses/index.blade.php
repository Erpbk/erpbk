@extends('riders.view')
@section('title','License Expenses')
@section('page_content')
@php
$accountId = $account->rider_id;
$totalUnpaid = company_table('license_expenses')->where('payment_status', 'unpaid')->where('rider_id', $accountId)->sum('amount');
$totalPaid = company_table('license_expenses')->where('payment_status', 'paid')->where('rider_id', $accountId)->sum('amount');
$unpaidCount = company_table('license_expenses')->where('rider_id', $accountId)->where('payment_status', 'unpaid')->count();
$paidCount = company_table('license_expenses')->where('rider_id', $accountId)->where('payment_status', 'paid')->count();

@endphp

<div class="content">
  @include('flash::message')
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h3 class="mb-0">License Expense - {{ $account->name }}</h3>
      @can('license_expense_create')
      <a class="btn btn-primary action-btn show-modal"
        href="javascript:void(0);" data-action="{{ route('LicenseExpense.create' , $account->id) }}" data-size="lg" data-title="New expense entry">
        Add New Expense
      </a>
      @endcan
    </div>
    <div class="totals-cards pt-3">
      <div class="total-card total-red">
        <div class="label">Total Unpaid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $totalUnpaid, 2) }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Total Paid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $totalPaid, 2) }}</div>
      </div>
      <div class="total-card total-red">
        <div class="label">Unpaid Expenses</div>
        <div class="value">{{ $unpaidCount }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Paid Expenses</div>
        <div class="value">{{ $paidCount }}</div>
      </div>
    </div>
    <div class="card-body table-responsive px-2 py-0" id="table-data">
      @include('license_expenses.table', ['data' => $data])
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h3 class="mb-0">License Installments</h3>
      <div class="d-flex flex-wrap gap-2">
        @if(!empty($canCreateInstallment))
        <a class="btn btn-sm btn-success action-btn show-modal"
          href="javascript:void(0);"
          data-action="{{ route($installmentCreateFormRoute ?? 'LicenseExpense.createInstallmentPlanForm', $account->id) }}"
          data-size="lg"
          data-title="Create Installment Entry">
          <i class="fa fa-plus"></i> Installment Plan
        </a>
        @endif
        @if(isset($installmentData) && $installmentData->count() > 0)
        <a href="javascript:void(0);"
          class="btn btn-sm btn-info action-btn show-modal"
          data-action="{{ route($installmentInvoiceRoute ?? 'LicenseExpense.generateInstallmentInvoice', ['riderId' => $account->id]) }}"
          data-size="xl"
          data-title="Installment plan invoice — {{ $account->name ?? 'Rider' }}">
          <i class="fa fa-file-invoice"></i> Invoice
        </a>
        @endif
      </div>
    </div>
    @php
      $installmentStats = $installmentStats ?? [
          'unpaid_amount' => 0,
          'paid_amount' => 0,
          'paid_count' => 0,
          'unpaid_count' => 0,
      ];
    @endphp
    <div class="totals-cards pt-3">
      <div class="total-card total-red">
        <div class="label">Total Unpaid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $installmentStats['unpaid_amount'], 2) }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Total Paid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $installmentStats['paid_amount'], 2) }}</div>
      </div>
      <div class="total-card total-red">
        <div class="label">Unpaid Installments</div>
        <div class="value">{{ (int) $installmentStats['unpaid_count'] }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Paid Installments</div>
        <div class="value">{{ (int) $installmentStats['paid_count'] }}</div>
      </div>
    </div>
    <div class="card-body table-responsive px-2 py-0">
      @include('visa_expenses.installmentPlanTable', ['data' => $installmentData ?? collect(), 'account' => $account])
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
    $('#license_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By License Status",
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
        url: "{{ route('LicenseExpense.index') }}",
        type: "GET",
        data: formData,
        success: function(data) {
          $('#table-data').html(data.tableData);

          // Update URL
          let newUrl = "{{ route('LicenseExpense.index') }}" + (formData ? '?' + formData : '');
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
    const table = document.querySelector('#LicenseExpensesDataTable');
    if (!table) return;
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

<script>
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.js-inline-update-btn');
    if (!btn) return;

    var row = btn.closest('tr[data-row-id]');
    if (!row) return;

    var updateUrl = btn.getAttribute('data-update-url');
    var id = btn.getAttribute('data-id');
    var amount = row.querySelector('.js-inline-amount')?.value || '';
    var date = row.querySelector('.js-inline-date')?.value || '';
    var billingMonth = row.querySelector('.js-inline-billing-month')?.value || '';

    var formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('id', id);
    formData.append('amount', amount);
    formData.append('date', date);
    formData.append('billing_month', billingMonth);

    fetch(updateUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function(res) {
        return res.json();
      })
      .then(function(data) {
        if (data.success && typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            text: data.message || 'Updated successfully',
            timer: 1200,
            showConfirmButton: false
          });
          return;
        }
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'Could not update row.'
          });
        }
      })
      .catch(function() {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Could not update row.'
          });
        }
      });
  });
</script>

<script>
  document.addEventListener('click', function(e) {
    var deleteLink = e.target.closest('.js-delete-visa-expense');
    if (!deleteLink) return;
    var url = deleteLink.getAttribute('data-delete-url');
    if (url) {
      confirmDelete(url);
    }
  });
</script>

@endsection
