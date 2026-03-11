@extends('layouts.app')
@section('title', 'Expense Vouchers')

@push('page-styles')
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
    z-index: 1050;
    overflow-y: auto;
  }

  .filter-sidebar.open {
    right: 0;
  }

  .filter-header {
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
  }

  .filter-body {
    padding: 20px;
  }

  .filter-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1040;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease;
  }

  .filter-overlay.show {
    opacity: 1;
    visibility: visible;
  }

  .card-search {
    max-width: 300px;
  }

  .btn-close {
    border: none;
    background: none;
    font-size: 1.2rem;
    cursor: pointer;
  }

  .table-responsive .dropdown-menu {
    z-index: 9999 !important;
    position: absolute !important;
  }

  .dropdown-menu {
    z-index: 9999 !important;
  }

  .table .dropdown-menu {
    transform: none !important;
    will-change: auto !important;
  }
</style>
@endpush

@section('content')
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h3>Expense Vouchers</h3>
        </div>
        <div class="col-sm-6">
          <div class="action-buttons d-flex justify-content-end">
            <div class="action-dropdown-container">
              <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                <i class="ti ti-plus"></i>
                <span>Add New</span>
                <i class="ti ti-chevron-down"></i>
              </button>
              <div class="action-dropdown-menu" id="addBikeDropdown">
                @can('expense_voucher_create')
                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Expense Voucher" data-action="{{ route('expenses.voucher.create') }}">
                  <i class="ti ti-file-invoice"></i>
                  <div>
                    <div class="action-dropdown-item-text">New Expense Voucher</div>
                    <div class="action-dropdown-item-desc">Add a new Expense Voucher</div>
                  </div>
                </a>
                @endcan
                @can('expenses_create')
                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Expense Account" data-action="{{ route('expenses.create') }}">
                  <i class="ti ti-wallet"></i>
                  <div>
                    <div class="action-dropdown-item-text">New Expense Account</div>
                    <div class="action-dropdown-item-desc">Add a new Expense Account</div>
                  </div>
                </a>
                @endcan
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  <div class="content px-0">
    <div class="clearfix"></div>

    <!-- Filter Sidebar -->
    <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
      <div class="filter-header">
        <h5>Filter Expense Vouchers</h5>
        <button type="button" class="btn-close" id="closeSidebar">&times;</button>
      </div>
      <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('expenses.index') }}" method="GET">
          <div class="row">
            <div class="form-group col-md-12">
              <label for="voucher_id">Voucher ID</label>
              <input type="text" name="voucher_id" class="form-control" placeholder="Filter By Voucher ID (e.g., EXP-0001)" value="{{ request('voucher_id') }}">
            </div>
            <div class="form-group col-md-12">
              <label for="trans_date">Transaction Date</label>
              <input type="date" name="trans_date" class="form-control" value="{{ request('trans_date') }}">
            </div>
            <div class="form-group col-md-12">
              <label for="billing_month">Billing Month</label>
              <input type="month" name="billing_month" class="form-control" value="{{ request('billing_month') }}">
            </div>
            <div class="form-group col-md-12">
              <label for="created_by">Created By</label>
              <select class="form-control" id="created_by" name="created_by">
                @php
                $createdByUsers = \App\Models\Vouchers::where('voucher_type', 'EXP')
                ->whereNotNull('Created_By')
                ->pluck('Created_By')
                ->unique();
                @endphp
                <option value="" selected>Select</option>
                @foreach($createdByUsers as $userId)
                @php
                $user = \App\Models\User::find($userId);
                @endphp
                @if($user)
                <option value="{{ $userId }}" {{ request('created_by') == $userId ? 'selected' : '' }}>{{ $user->name }}</option>
                @endif
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-12">
              <label for="quick_search">Quick Search</label>
              <input type="text" name="quick_search" id="quickSearchSidebar" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            <div class="col-md-12 form-group text-center">
              <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
              <a href="{{ route('expenses.index') }}" class="btn btn-secondary pull-right mt-3 mr-2"><i class="fa fa-refresh mx-2"></i> Clear Filters</a>
            </div>
          </div>
        </form>
      </div>
    </div>
    <div class="filter-overlay" id="filterOverlay"></div>

    {{-- Include Column Control Panel --}}
    @php
    $tableColumns = [
    ['data' => 'voucher_id', 'title' => 'Voucher ID'],
    ['data' => 'trans_date', 'title' => 'Date'],
    ['data' => 'trans_code', 'title' => 'Trans Code'],
    ['data' => 'billing_month', 'title' => 'Billing Month'],
    ['data' => 'reference_number', 'title' => 'Reference Number'],
    ['data' => 'amount', 'title' => 'Amount'],
    ['data' => 'created_by', 'title' => 'Created By'],
    ['data' => 'updated_by', 'title' => 'Updated By'],
    ['data' => 'attach_file', 'title' => 'File'],
    ['data' => 'action', 'title' => 'Actions'],
    ['data' => 'search', 'title' => 'Search'],
    ['data' => 'control', 'title' => 'Control']
    ];
    @endphp
    @include('components.column-control-panel', [
    'tableColumns' => $tableColumns,
    'tableIdentifier' => 'expense_vouchers_table'
    ])
    <div class="filter-overlay" id="columnControlOverlay"></div>

    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <div class="card-title">
          <h3>Expense Vouchers @if(isset($data)) ({{ $data->total() }} Records) @endif</h3>
        </div>
        <div class="card-search">
          <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
        </div>
      </div>
      <div class="card-body px-2 py-0 table-responsive" id="table-data">
        @include('expenses.table', ['data' => $data ?? collect()])
      </div>
    </div>
  </div>
</div>

<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
  <div class="text-white text-center">
    <div class="spinner-border" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
    <div class="mt-2">Loading...</div>
  </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script type="text/javascript">
  (function checkJQuery() {
    if (typeof jQuery === 'undefined') {
      setTimeout(checkJQuery, 50);
      return;
    }

    $(document).ready(function() {
      function initializeDropdowns() {
        var attempts = 0;
        var maxAttempts = 10;

        function tryInitialize() {
          attempts++;

          if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
              try {
                return new bootstrap.Dropdown(dropdownToggleEl);
              } catch (e) {
                return null;
              }
            }).filter(Boolean);
          } else if (attempts < maxAttempts) {
            setTimeout(tryInitialize, 100);
          }
        }

        tryInitialize();
      }

      initializeDropdowns();

      if (typeof $.fn.select2 !== 'undefined') {
        $('#created_by').select2({
          dropdownParent: $('#searchTopbody'),
          placeholder: "Filter By Created By",
          allowClear: true,
        });
      }

      $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
        e.preventDefault();
        $('#filterSidebar').addClass('open');
        $('#filterOverlay').addClass('show');
        return false;
      });

      $(document).on('click', '#closeSidebar, #filterOverlay', function(e) {
        e.preventDefault();
        $('#filterSidebar').removeClass('open');
        $('#filterOverlay').removeClass('show');
        return false;
      });

      $(document).on('click', '.openColumnControlSidebar', function(e) {
        e.preventDefault();
        $('#columnControlSidebar').addClass('open');
        $('#filterOverlay').addClass('show');
        return false;
      });

      $('#filterForm').on('submit', function(e) {
        e.preventDefault();

        $('#loading-overlay').show();
        $('#filterSidebar').removeClass('open');
        $('#filterOverlay').removeClass('show');
        const loaderStartTime = Date.now();
        let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
        let formData = $.param(filteredFields);

        $.ajax({
          url: "{{ route('expenses.index') }}",
          type: "GET",
          data: formData,
          success: function(data) {
            $('#table-data').html(data.tableData);
            let newUrl = "{{ route('expenses.index') }}" + (formData ? '?' + formData : '');
            history.pushState(null, '', newUrl);
            if (filteredFields.length > 0) {
              $('#clearFilterBtn').show();
            } else {
              $('#clearFilterBtn').hide();
            }

            setTimeout(function() {
              initializeDropdowns();
            }, 100);

            if (window.ColumnController) {
              setTimeout(() => {
                window.ColumnController.reapplySettings();
                window.ColumnController.initializeDropdowns();
              }, 100);
            }

            const elapsed = Date.now() - loaderStartTime;
            const remaining = 1000 - elapsed;
            setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
          },
          error: function(xhr, status, error) {
            const elapsed = Date.now() - loaderStartTime;
            const remaining = 1000 - elapsed;
            setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
          }
        });
      });

      $('#quickSearch').on('keyup', function(e) {
        if (e.keyCode === 13 || $(this).val().length === 0) {
          $('#quickSearchSidebar').val($(this).val());
          $('#filterForm').submit();
        }
      });

      $('#quickSearchSidebar').on('keyup', function(e) {
        if (e.keyCode === 13 || $(this).val().length === 0) {
          $('#quickSearch').val($(this).val());
          $('#filterForm').submit();
        }
      });
    });
  })();
</script>
@endsection