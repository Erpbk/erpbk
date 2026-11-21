@extends('layouts.app')
@section('title', 'Vouchers')

@php
  $hideModuleTopBarSlider = true;
@endphp

@push('third_party_stylesheets')
@include('vouchers.partials.demo_shell_styles')
<style>
  .filter-sidebar {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    height: 100vh;
    background: white;
    box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
    transition: right 0.3s ease;
    z-index: 11060;
    overflow-y: auto;
  }
  .filter-sidebar.open { right: 0; }
  .filter-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 11050;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease;
    pointer-events: none;
  }
  .filter-overlay.show {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
  }
  .btn-close {
    border: none;
    background: none;
    font-size: 1.2rem;
    cursor: pointer;
  }
  #table-data .dropdown-menu,
  #dataTableBuilder .dropdown-menu {
    z-index: 1080 !important;
  }
</style>
@endpush

@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
  <div class="spinner-border text-primary" role="status"></div>
</div>

{{-- Overlay first (below drawer), then filter drawer — same stacking as Riders --}}
<div class="filter-overlay" id="filterOverlay"></div>
<div id="filterSidebar" class="filter-sidebar vc-filter-drawer">
  <div class="filter-header d-flex justify-content-between align-items-center">
    <h5>Filter Vouchers</h5>
    <button type="button" class="btn-close" id="closeSidebar" aria-label="Close">&times;</button>
  </div>
  <div class="filter-body" id="searchTopbody">
    <form id="filterForm" action="{{ route('vouchers.index') }}" method="GET">
      <div class="row">
        @if(auth()->user()->hasMultiplebranches())
        <div class="form-group col-md-12">
          <label for="branch_id">Filter by Branch</label>
          <select class="form-control" id="branch_id" name="branch_id">
            @foreach(auth()->user()->branchDropdown() as $id => $name)
            <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
        </div>
        @endif
        <div class="form-group col-md-12">
          <label for="voucher_id">Voucher ID</label>
          <input type="text" name="voucher_id" class="form-control" placeholder="Filter By Voucher ID (e.g., JV-0001)" value="{{ request('voucher_id') }}">
        </div>
        @if(field_visible('voucher', 'trans_date'))
        <div class="form-group col-md-12">
          <label for="trans_date">Date</label>
          <input type="date" name="trans_date" class="form-control" value="{{ request('trans_date') }}">
        </div>
        @endif
        <div class="form-group col-md-12">
          <label for="account_kind">Type</label>
          <select class="form-control" name="account_kind" id="account_kind">
            <option value="">All (Bank &amp; Cash)</option>
            <option value="bank" {{ request('account_kind') === 'bank' ? 'selected' : '' }}>Bank</option>
            <option value="cash" {{ request('account_kind') === 'cash' ? 'selected' : '' }}>Cash</option>
            <option value="oth" {{ request('account_kind') === 'oth' ? 'selected' : '' }}>Account</option>
          </select>
        </div>
        <input type="hidden" name="approval_tab" id="approval_tab" value="{{ $approvalTab ?? request('approval_tab', 'pending') }}">
        @if(field_visible('voucher', 'billing_month'))
        <div class="form-group col-md-12">
          <label for="billing_month">Billing Month</label>
          <input type="month" name="billing_month" class="form-control" value="{{ request('billing_month') }}">
        </div>
        @endif
        @if(field_visible('voucher', 'voucher_type'))
        <div class="form-group col-md-12">
          <label for="voucher_type">Voucher Type</label>
          <select class="form-control" id="voucher_type" name="voucher_type">
            <option value="">Filter By Voucher Type</option>
            @foreach($voucherTypesForFilter ?? \App\Helpers\General::VoucherType() as $key => $value)
            <option value="{{ $key }}" {{ request('voucher_type') == $key ? 'selected' : '' }}>{{ $value }}</option>
            @endforeach
          </select>
        </div>
        @endif
        @if(field_visible('voucher', 'created_by'))
        <div class="form-group col-md-12">
          <label for="created_by">Created By</label>
          <select class="form-control" id="created_by" name="created_by">
            @php
              $createdByUsers = \App\Models\Vouchers::whereNotNull('Created_By')->pluck('Created_By')->unique();
            @endphp
            <option value="">Filter By Created By</option>
            @foreach($createdByUsers as $userId)
              @php $user = \App\Models\User::find($userId); @endphp
              @if($user)
              <option value="{{ $userId }}" {{ request('created_by') == $userId ? 'selected' : '' }}>{{ $user->name }}</option>
              @endif
            @endforeach
          </select>
        </div>
        @endif
        <div class="form-group col-md-12">
          <label for="quickSearchSidebar">Quick Search</label>
          <input type="text" name="quick_search" id="quickSearchSidebar" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
        </div>
        <div class="col-md-12 form-group vc-fgo">
          <button type="submit" class="vc-sbtn p">Filter Data</button>
          <a href="{{ route('vouchers.index') }}" class="vc-sbtn g">Clear Filters</a>
        </div>
      </div>
    </form>
  </div>
</div>

@php
  $tableColumns = [
    ['data' => 'ck', 'title' => 'Select'],
    ['data' => 'trans_date', 'title' => 'Date'],
    ['data' => 'billing_month', 'title' => 'Billing Month'],
    ['data' => 'voucher_id', 'title' => 'Voucher ID'],
    ['data' => 'reference_number', 'title' => 'Reference'],
    ['data' => 'module', 'title' => 'Module'],
    ['data' => 'account_kind', 'title' => 'Type'],
    ['data' => 'paid_from', 'title' => 'Paid From'],
    ['data' => 'amount', 'title' => 'Amount'],
    ['data' => 'created_by', 'title' => 'Created By'],
    ['data' => 'updated_by', 'title' => 'Updated By'],
    ['data' => 'attach_file', 'title' => 'File'],
    ['data' => 'approval_status', 'title' => 'Status'],
    ['data' => 'action', 'title' => 'Actions'],
    ['data' => 'search', 'title' => 'Search'],
    ['data' => 'control', 'title' => 'Control'],
  ];
  $tableColumns = \App\Support\RoleFieldAccess::filterTableColumns($tableColumns, 'voucher');
@endphp
@include('riders.partials.column_control_modal', [
  'tableColumns' => $tableColumns,
  'tableIdentifier' => 'vouchers_table',
  'tableId' => 'dataTableBuilder',
  'fixedColumnsCount' => 3,
  'exportRoute' => route('vouchers.exportCustomizable'),
  'showExportOptions' => true,
  'exportNote' => 'Only ticked columns are exported, in the current order, for all vouchers.',
  'moduleKey' => 'voucher',
])

<div class="vc-demo" id="vouchersDemo">
  <div class="vc-wrap">
    @include('flash::message')

    @include('vouchers.partials.type_ticker', [
      'voucherTypesForFilter' => $voucherTypesForFilter ?? [],
      'typeTickerStats' => $typeTickerStats ?? [],
    ])

    @include('vouchers.partials.demo_toolbar', [
      'data' => $data ?? collect(),
      'tabCounts' => $tabCounts ?? [],
      'approvalTab' => $approvalTab ?? request('approval_tab', 'pending'),
    ])

    @include('vouchers.partials.bulk_bar')

    <div class="vc-tbox" id="table-data">
      @include('vouchers.table', [
        'data' => $data ?? collect(),
        'editDeleteFlags' => $editDeleteFlags ?? [],
      ])
    </div>

    <div id="vcPagerWrap">
      @if(isset($data) && method_exists($data, 'links'))
        @include('components.riders-demo-pagination', [
          'paginator' => $data,
          'entityLabel' => 'vouchers',
          'perPageOptions' => [10, 20, 50, 100, 'all'],
        ])
      @endif
    </div>
  </div>
</div>

@include('vouchers.partials.ui_modals')
@endsection

@section('page-script')
@include('vouchers.partials.demo_scripts')
<script type="text/javascript">
(function checkJQuery() {
  if (typeof jQuery === 'undefined') {
    setTimeout(checkJQuery, 50);
    return;
  }

  $(document).ready(function () {
    function initializeDropdowns() {
      var attempts = 0;
      var maxAttempts = 10;
      function tryInitialize() {
        attempts++;
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
          document.querySelectorAll('#dataTableBuilder [data-bs-toggle="dropdown"]').forEach(function (el) {
            try {
              var existing = bootstrap.Dropdown.getInstance(el);
              if (existing) existing.dispose();
              new bootstrap.Dropdown(el, {
                popperConfig: function (defaultConfig) {
                  return Object.assign({}, defaultConfig, {
                    strategy: 'fixed',
                    modifiers: (defaultConfig.modifiers || []).concat([{
                      name: 'preventOverflow',
                      options: { boundary: 'viewport' }
                    }])
                  });
                }
              });
            } catch (e) {}
          });
        } else if (attempts < maxAttempts) {
          setTimeout(tryInitialize, 100);
        }
      }
      tryInitialize();
    }

    initializeDropdowns();

    if (typeof $.fn.select2 !== 'undefined') {
      $('#voucher_type').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: 'Filter By Voucher Type',
        allowClear: true,
      });
      $('#created_by').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: 'Filter By Created By',
        allowClear: true,
      });
      $('#branch_id').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: 'Filter By Branch',
        allowClear: true,
      });
    }

    $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $('#filterSidebar').addClass('open');
      $('#filterOverlay').addClass('show');
      return false;
    });

    // Close only via × or the dimmed overlay — not clicks inside the drawer
    $(document).on('click', '#closeSidebar', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $('#filterSidebar').removeClass('open');
      $('#filterOverlay').removeClass('show');
      return false;
    });
    $(document).on('click', '#filterOverlay', function (e) {
      if (e.target !== this) return;
      e.preventDefault();
      $('#filterSidebar').removeClass('open');
      $('#filterOverlay').removeClass('show');
      return false;
    });
    $(document).on('click', '#filterSidebar', function (e) {
      e.stopPropagation();
    });

    // Column Control uses riders.partials.column_control_modal (centered rl-ov).
    // Close the Actions menu when that opens.
    $(document).on('click', '.openColumnControlSidebar', function () {
      $('#vcActMenu').removeClass('open');
      $('#vcActBtn').attr('aria-expanded', 'false');
    });

    // Phase 1: full-page filter so ticker / tabs / demo pager stay in sync (no backend change).
    $('#filterForm').on('submit', function () {
      $('#loading-overlay').show();
      $('#filterSidebar').removeClass('open');
      $('#filterOverlay').removeClass('show');
    });

    $('#quickSearch').on('keyup', function (e) {
      if (e.keyCode === 13 || $(this).val().length === 0) {
        $('#quickSearchSidebar').val($(this).val());
        $('#filterForm').trigger('submit');
      }
    });

    $('#quickSearchSidebar').on('keyup', function (e) {
      if (e.keyCode === 13 || $(this).val().length === 0) {
        $('#quickSearch').val($(this).val());
        $('#filterForm').trigger('submit');
      }
    });

    // Pager per-page (riders-demo-pagination)
    $(document).on('change', '#rlPerPage', function () {
      var url = new URL(window.location.href);
      url.searchParams.set('per_page', $(this).val());
      url.searchParams.delete('page');
      window.location.href = url.toString();
    });
  });
})();
</script>
@endsection
