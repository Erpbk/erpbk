@extends('riders.view')
@section('title','Visa Expenses')
@section('page_content')
@php
$accountId = $account->rider_id;
$categoryId = (int) ($activeRenewalCategory->id ?? 0);
$categoryExpenseQuery = \App\Support\VisaRenewalCategoryService::expensesForAccountQuery(
    (int) $account->id,
    (int) $accountId,
    $categoryId
);
$totalUnpaid = (clone $categoryExpenseQuery)->where('payment_status', 'unpaid')->sum('amount');
$totalPaid = (clone $categoryExpenseQuery)->where('payment_status', 'paid')->sum('amount');
$unpaidCount = (clone $categoryExpenseQuery)->where('payment_status', 'unpaid')->count();
$paidCount = (clone $categoryExpenseQuery)->where('payment_status', 'paid')->count();
@endphp

<div class="content">
  @include('flash::message')

  @if(isset($siblingAccounts) && $siblingAccounts->count() > 0)
  <div class="alert alert-light border mb-3">
    <span class="text-muted me-2">Other renewal accounts for this rider:</span>
    @foreach($siblingAccounts as $sibling)
    <a href="{{ route('VisaExpense.generatentries', $sibling->id) }}" class="btn btn-sm btn-outline-secondary me-1 mb-1">
      {{ $sibling->renewalCategory->name ?? 'Account #' . $sibling->id }}
    </a>
    @endforeach
  </div>
  @endif

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h3 class="mb-0">Visa Expense - {{ $account->name }} <span class="text-muted">({{ $activeRenewalCategory->name ?? 'New Visa' }})</span></h3>
      @can('visaexpense_create')
      <a class="btn btn-primary action-btn show-modal"
        href="javascript:void(0);"
        data-action="{{ route('VisaExpense.create', ['id' => $account->id]) }}"
        data-size="lg"
        data-title="New expense entry — {{ $activeRenewalCategory->name }}">
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
      @include('visa_expenses.table', ['data' => $data])
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h3 class="mb-0">Visa Installments</h3>
      <div class="d-flex flex-wrap gap-2">
        @canany(['installment_create', 'visaloan_create'])
        <a class="btn btn-sm btn-success action-btn show-modal"
          href="javascript:void(0);"
          data-action="{{ route('Installments.createInstallmentPlanForm', $account->id) }}"
          data-size="lg"
          data-title="Create Installment Entry">
          <i class="fa fa-plus"></i> Installment Plan
        </a>
        @endcanany
        @if(isset($installmentData) && $installmentData->count() > 0)
        <a href="javascript:void(0);"
          class="btn btn-sm btn-info action-btn show-modal"
          data-action="{{ route('Installments.generateInstallmentInvoice', ['riderId' => $account->id]) }}"
          data-size="xl"
          data-title="Installment plan invoice — {{ $account->name ?? 'Rider' }}">
          <i class="fa fa-file-invoice"></i> Invoice
        </a>
        @endif
      </div>
    </div>
    <div class="totals-cards pt-3">
      <div class="total-card total-red">
        <div class="label">Total Unpaid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) company_table('visa_installment_plans')->where('status', 'pending')->where('rider_id', $account->rider_id)->sum('amount'), 2) }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Total Paid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) company_table('visa_installment_plans')->where('status', 'paid')->where('rider_id', $account->rider_id)->sum('amount'), 2) }}</div>
      </div>
      <div class="total-card total-red">
        <div class="label">Unpaid Installments</div>
        <div class="value">{{ company_table('visa_installment_plans')->where('rider_id', $account->rider_id)->where('status', 'pending')->count() }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Paid Installments</div>
        <div class="value">{{ company_table('visa_installment_plans')->where('rider_id', $account->rider_id)->where('status', 'paid')->count() }}</div>
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

      let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
      let formData = $.param(filteredFields);

      $.ajax({
        url: "{{ route('VisaExpense.generatentries', ['id' => $account->id, 'renewal_category' => $activeRenewalCategory->id]) }}",
        type: "GET",
        data: formData,
        success: function(data) {
          $('#table-data').html(data.tableData);

          let newUrl = "{{ route('VisaExpense.generatentries', ['id' => $account->id, 'renewal_category' => $activeRenewalCategory->id]) }}" + (formData ? '?' + formData : '');
          history.pushState(null, '', newUrl);

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
    const table = document.querySelector('#visaExpensesDataTable');
    if (!table) return;
    const headers = table.querySelectorAll('th.sorting');
    const tbody = table.querySelector('tbody');

    headers.forEach((header, colIndex) => {
      header.addEventListener('click', () => {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAsc = header.classList.contains('sorted-asc');

        headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));

        header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');

        rows.sort((a, b) => {
          let aText = a.children[colIndex]?.textContent.trim().toLowerCase();
          let bText = b.children[colIndex]?.textContent.trim().toLowerCase();

          const aVal = isNaN(aText) ? aText : parseFloat(aText);
          const bVal = isNaN(bText) ? bText : parseFloat(bText);

          if (aVal < bVal) return isAsc ? 1 : -1;
          if (aVal > bVal) return isAsc ? -1 : 1;
          return 0;
        });

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
