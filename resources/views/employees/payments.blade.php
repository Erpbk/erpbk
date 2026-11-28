@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Payment Out Sent')
@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/module-list.css') }}?v=20261030-tbox">
<style>
  .table-responsive { max-height: calc(100vh - 280px); }
  .card-search { max-width: 300px; min-width: 220px; }
  .filter-sidebar.riders-filter-sidebar .form-group {
    margin-bottom: 1rem;
  }
  .filter-sidebar.riders-filter-sidebar .form-group label {
    display: block;
    margin-bottom: 0.35rem;
    font-weight: 500;
  }
  .filter-sidebar.riders-filter-sidebar .filter-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: flex-end;
    margin-top: 0.5rem;
    padding-bottom: 1rem;
  }
</style>
@endpush
@section('content')
@php
$banks = $banks ?? \App\Models\Banks::active()->get();
@endphp
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
@canany(['employees_payment_view', 'employees_payments_view', 'employees_payments_create'])
<div class="module-list">
  <div class="rl-wrap">
    @include('flash::message')

    <div class="rl-toolbar">
      <div class="rl-tb-left">
        <div class="rl-dd">
          <button type="button" class="rl-btn" id="empPayActBtn" aria-haspopup="true" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            Payment Actions
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="rl-menu" id="empPayActMenu" hidden role="menu">
            @can('employees_payments_create')
            <a role="menuitem" class="show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Employee Payment" data-action="{{ route('payments.create') }}?employee_payment=1">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>
              <span><strong>Salary Payment</strong><em>Record a payment against employee invoices</em></span>
            </a>
            @endcan
          </div>
        </div>
        <a class="rl-btn" href="{{ route('employee.payment') }}">All Payments</a>
        <button type="button" class="rl-btn openFilterSidebar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          Filter
        </button>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <h5 class="mb-0">Employee Payments</h5>
        <div class="card-search">
          <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive" id="table-data">
          @include('payments.table')
        </div>
      </div>
    </div>
  </div>
</div>

<div id="filterOverlay" class="filter-overlay"></div>
<div id="filterSidebar" class="filter-sidebar riders-filter-sidebar">
  <div class="filter-header">
    <h5>Filter Payments</h5>
    <button type="button" class="btn-close" id="closeSidebar" aria-label="Close"></button>
  </div>
  <div class="filter-body" id="searchTopbody">
    <form id="filterForm" action="{{ route('employee.payment') }}" method="GET">
      <div class="row">
        <div class="form-group col-md-12">
          <label for="voucher_id">Voucher ID</label>
          <input type="text" name="voucher_id" id="voucher_id" class="form-control" placeholder="e.g. PV-0001" value="{{ request('voucher_id') }}">
        </div>
        <div class="form-group col-md-12">
          <label for="date_of_payment">Date of Payment</label>
          <input type="date" name="date_of_payment" id="date_of_payment" class="form-control" value="{{ request('date_of_payment') }}">
        </div>
            <div class="form-group col-md-12">
              <label for="filter_billing_month">Billing Month</label>
              <input type="month" name="billing_month" id="filter_billing_month" class="form-control" value="{{ request('billing_month') }}">
            </div>
        <div class="form-group col-md-12">
          <label for="amount_type">Payment Mode</label>
          <select class="form-control" id="amount_type" name="amount_type">
            <option value="">Select</option>
            @foreach(['Cash', 'Online', 'Cheque', 'Credit'] as $mode)
            <option value="{{ $mode }}" {{ request('amount_type') == $mode ? 'selected' : '' }}>{{ $mode }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="bank_id">Bank / Cash</label>
          <select class="form-control" id="bank_id" name="bank_id">
            <option value="">Select</option>
            @foreach($banks as $bank)
            <option value="{{ $bank->id }}" {{ (string) request('bank_id') === (string) $bank->id ? 'selected' : '' }}>
              {{ optional($bank->account)->account_code }} - {{ optional($bank->account)->name ?? $bank->name }}
            </option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="reference">Reference</label>
          <input type="text" name="reference" id="reference" class="form-control" placeholder="Filter by reference" value="{{ request('reference') }}">
        </div>
        <div class="form-group col-md-12">
          <label for="quickSearchSidebar">Quick Search</label>
          <input type="text" name="quick_search" id="quickSearchSidebar" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
        </div>
        <div class="col-md-12 filter-actions">
          <a href="{{ route('employee.payment') }}" class="btn btn-secondary"><i class="fa fa-refresh mx-1"></i> Clear Filters</a>
          <button type="submit" class="btn btn-primary"><i class="fa fa-filter mx-1"></i> Filter Data</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endcanany
@endsection

@section('page-script')
<script type="text/javascript">
(function checkJQuery() {
  if (typeof jQuery === 'undefined') {
    setTimeout(checkJQuery, 50);
    return;
  }

  $(document).ready(function () {
    var btn = document.getElementById('empPayActBtn');
    var menu = document.getElementById('empPayActMenu');
    if (btn && menu) {
      btn.addEventListener('click', function () {
        var open = menu.hidden;
        menu.hidden = !open;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      document.addEventListener('click', function (e) {
        if (btn.contains(e.target) || menu.contains(e.target)) return;
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
      });
    }

    if (typeof $.fn.select2 !== 'undefined') {
      $('#amount_type, #bank_id').select2({
        dropdownParent: $('#searchTopbody'),
        allowClear: true,
        placeholder: 'Select'
      });
    }

    $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function (e) {
      e.preventDefault();
      $('#filterSidebar').addClass('open');
      $('#filterOverlay').addClass('show');
    });

    $(document).on('click', '#closeSidebar, #filterOverlay', function (e) {
      e.preventDefault();
      $('#filterSidebar').removeClass('open');
      $('#filterOverlay').removeClass('show');
    });

    $('#filterForm').on('submit', function (e) {
      e.preventDefault();
      $('#loading-overlay').css('display', 'flex');
      $('#filterSidebar').removeClass('open');
      $('#filterOverlay').removeClass('show');

      var filteredFields = $(this).serializeArray().filter(function (field) {
        return field.name !== '_token' && String(field.value).trim() !== '';
      });
      var formData = $.param(filteredFields);

      $.ajax({
        url: "{{ route('employee.payment') }}",
        type: "GET",
        data: formData,
        success: function (data) {
          $('#table-data').html(data.tableData);
          var newUrl = "{{ route('employee.payment') }}" + (formData ? '?' + formData : '');
          history.pushState(null, '', newUrl);
          $('#quickSearch').val($('#quickSearchSidebar').val() || '');
          $('#loading-overlay').hide();
        },
        error: function () {
          $('#loading-overlay').hide();
        }
      });
    });

    $('#quickSearch').on('keyup', function (e) {
      if (e.keyCode === 13 || $(this).val().length === 0) {
        $('#quickSearchSidebar').val($(this).val());
        $('#filterForm').submit();
      }
    });

    $('#quickSearchSidebar').on('keyup', function (e) {
      if (e.keyCode === 13 || $(this).val().length === 0) {
        $('#quickSearch').val($(this).val());
        $('#filterForm').submit();
      }
    });
  });
})();
</script>
@endsection
