@extends('layouts.app')
@section('title', 'Ledger')

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

  /* Fix dropdown z-index issue in table-responsive */
  .table-responsive .dropdown-menu {
    z-index: 9999 !important;
    position: absolute !important;
  }

  /* Ensure dropdown appears above overflow content */
  .dropdown-menu {
    z-index: 9999 !important;
  }

  /* Override Bootstrap's dropdown positioning for table context */
  .table .dropdown-menu {
    transform: none !important;
    will-change: auto !important;
  }

  /* Print styles */
  @media print {

    /* Hide header section completely */
    section.content-header {
      display: none !important;
    }

    /* Hide everything except the table */
    .no-print,
    .action-btn,
    .filter-sidebar,
    .filter-overlay,
    .card-header,
    .pagination-wrapper,
    .openFilterSidebar,
    .openColumnControlSidebar,
    .btn,
    .card-search,
    .content-header,
    .card-title,
    th:last-child,
    th:nth-last-child(2),
    td:last-child,
    td:nth-last-child(2),
    #columnControlSidebar,
    #filterSidebar,
    .content>.clearfix {
      display: none !important;
    }

    /* Show only the table */
    .card-body,
    .table,
    #table-data,
    .table-wrapper {
      display: block !important;
    }

    .content-wrapper {
      padding: 0 !important;
      margin: 0 !important;
    }

    .content {
      padding: 0 !important;
      margin: 0 !important;
    }

    .card {
      border: none !important;
      box-shadow: none !important;
      margin: 0 !important;
    }

    .card-body {
      padding: 0 !important;
      margin: 0 !important;
    }

    .table {
      font-size: 11px;
      width: 100% !important;
      margin: 0 !important;
    }

    .table th,
    .table td {
      padding: 4px !important;
      border: 1px solid #000 !important;
    }

    .table thead th {
      background-color: #f0f0f0 !important;
      font-weight: bold;
    }

    body {
      margin: 0;
      padding: 20px;
    }

    @page {
      margin: 1cm;
    }

    /* Hide view file links in narration */
    .no-print {
      display: none !important;
    }

    /* Ensure table-info and table-warning classes are visible in print */
    .table-info {
      background-color: #d1ecf1 !important;
    }

    .table-warning {
      background-color: #fff3cd !important;
    }
  }
</style>
@endpush

@section('content')
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h3>Ledger</h3>
        </div>
        <div class="col-sm-6">
          <button type="button" class="btn btn-primary action-btn" id="printLedgerBtn" style="margin-right: 5px;">
            <i class="fa fa-print"></i>&nbsp;Print
          </button>
          <a class="btn btn-info action-btn" href="{{ route('ledger.export', request()->all()) }}">
            <i class="fa fa-file-excel"></i>&nbsp;Export to Excel
          </a>
        </div>
      </div>
    </div>
  </section>

  <div class="content px-0">
    <div class="clearfix"></div>

    <!-- Filter Sidebar -->
    <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
      <div class="filter-header">
        <h5>Filter Ledger</h5>
        <button type="button" class="btn-close" id="closeSidebar">&times;</button>
      </div>
      <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('accounts.ledger') }}" method="GET">
          <div class="row">
            <div class="form-group col-md-12">
              <label for="account">Account</label>
              {!! Form::select('account', App\Models\Accounts::dropdown(null), request('account'), ['class' => 'form-control select2', 'id' => 'account']) !!}
            </div>
            <div class="form-group col-md-12">
              <label for="month">Billing Month</label>
              <input type="month" name="month" class="form-control" value="{{ request('month') }}">
            </div>
            <div class="form-group col-md-12">
              <label for="quick_search">Quick Search</label>
              <input type="text" name="quick_search" id="quickSearchSidebar" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            <div class="col-md-12 form-group text-center">
              <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
              <a href="{{ route('accounts.ledger') }}" class="btn btn-secondary pull-right mt-3 mr-2"><i class="fa fa-refresh mx-2"></i> Clear Filters</a>
            </div>
          </div>
        </form>
      </div>
    </div>
    <div class="filter-overlay" id="filterOverlay"></div>

    {{-- Include Column Control Panel --}}
    @php
    $tableColumns = [
    ['data' => 'date', 'title' => 'Date'],
    ['data' => 'account_name', 'title' => 'Account'],
    ['data' => 'billing_month', 'title' => 'Month'],
    ['data' => 'voucher', 'title' => 'Voucher'],
    ['data' => 'narration', 'title' => 'Narration'],
    ['data' => 'debit', 'title' => 'Debit'],
    ['data' => 'credit', 'title' => 'Credit'],
    ['data' => 'balance', 'title' => 'Balance'],
    ['data' => 'search', 'title' => 'Search'],
    ['data' => 'control', 'title' => 'Control']
    ];
    @endphp
    @include('components.column-control-panel', [
    'tableColumns' => $tableColumns,
    'tableIdentifier' => 'ledger_table'
    ])
    <!-- Column Control Overlay -->
    <div class="filter-overlay" id="columnControlOverlay"></div>

    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <div class="card-title">
          <h3>Ledger @if(isset($data)) ({{ $data->total() }} Records) @endif</h3>
        </div>
        <div class="card-search">
          <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
        </div>
      </div>
      <div class="card-body px-2 py-0" id="table-data">
        @include('ledger.table', ['data' => $data ?? collect()])
      </div>
    </div>
  </div>
</div>

<!-- Loading Overlay -->
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
<!-- Include Sortable.js for column reordering -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script type="text/javascript">
  // Wait for jQuery to be available
  (function checkJQuery() {
    if (typeof jQuery === 'undefined') {
      console.warn('jQuery not loaded yet, waiting...');
      setTimeout(checkJQuery, 50);
      return;
    }

    $(document).ready(function() {
      console.log('Ledger filter script loaded'); // Debug line

      // Test jQuery is working
      console.log('jQuery version:', $.fn.jquery);

      // Function to initialize Bootstrap dropdowns
      function initializeDropdowns() {
        console.log('Initializing dropdowns'); // Debug line

        // Wait for Bootstrap to be available
        var attempts = 0;
        var maxAttempts = 10;

        function tryInitialize() {
          attempts++;

          if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            // Initialize Bootstrap 5 dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
              try {
                return new bootstrap.Dropdown(dropdownToggleEl);
              } catch (e) {
                console.warn('Failed to initialize dropdown:', e);
                return null;
              }
            }).filter(Boolean);

            console.log('Dropdowns initialized:', dropdownList.length); // Debug line
          } else if (attempts < maxAttempts) {
            console.log('Bootstrap not ready, retrying...', attempts);
            setTimeout(tryInitialize, 100);
          } else {
            console.warn('Bootstrap dropdown initialization failed after', maxAttempts, 'attempts');
          }
        }

        tryInitialize();
      }

      // Initialize dropdowns on page load
      initializeDropdowns();

      // Initialize Select2 for filter dropdowns if available
      if (typeof $.fn.select2 !== 'undefined') {
        $('#account').select2({
          dropdownParent: $('#searchTopbody'),
          placeholder: "Filter By Account",
          allowClear: true,
        });
      }

      // Sidebar open/close with event delegation
      $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
        e.preventDefault();
        console.log('Filter button clicked!'); // Debug line
        $('#filterSidebar').addClass('open');
        $('#filterOverlay').addClass('show');
        return false;
      });

      $(document).on('click', '#closeSidebar, #filterOverlay', function(e) {
        e.preventDefault();
        console.log('Close button clicked!'); // Debug line
        $('#filterSidebar').removeClass('open');
        $('#filterOverlay').removeClass('show');
        return false;
      });

      // Column control button with event delegation
      $(document).on('click', '.openColumnControlSidebar', function(e) {
        e.preventDefault();
        console.log('Column control button clicked!'); // Debug line
        $('#columnControlSidebar').addClass('open');
        $('#filterOverlay').addClass('show');
        return false;
      });

      // Handle filter form submission
      $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Filter form submitted'); // Debug line

        $('#loading-overlay').show();
        $('#filterSidebar').removeClass('open');
        $('#filterOverlay').removeClass('show');
        const loaderStartTime = Date.now();
        let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
        let formData = $.param(filteredFields);

        $.ajax({
          url: "{{ route('accounts.ledger') }}",
          type: "GET",
          data: formData,
          success: function(data) {
            console.log('Filter response received:', data); // Debug line
            $('#table-data').html(data.tableData);
            let newUrl = "{{ route('accounts.ledger') }}" + (formData ? '?' + formData : '');
            history.pushState(null, '', newUrl);
            if (filteredFields.length > 0) {
              $('#clearFilterBtn').show();
            } else {
              $('#clearFilterBtn').hide();
            }

            // Reinitialize dropdowns after loading new content
            setTimeout(function() {
              initializeDropdowns();
            }, 100);

            // Reapply column control settings after table update
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
            console.error('Filter error:', xhr, status, error); // Debug line
            const elapsed = Date.now() - loaderStartTime;
            const remaining = 1000 - elapsed;
            setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
          }
        });
      });

      // Quick search input (main)
      $('#quickSearch').on('keyup', function(e) {
        if (e.keyCode === 13 || $(this).val().length === 0) {
          // Set the sidebar quick search too
          $('#quickSearchSidebar').val($(this).val());
          $('#filterForm').submit();
        }
      });

      // Quick search input (sidebar)
      $('#quickSearchSidebar').on('keyup', function(e) {
        if (e.keyCode === 13 || $(this).val().length === 0) {
          // Set the main quick search too
          $('#quickSearch').val($(this).val());
          $('#filterForm').submit();
        }
      });

      // Print ledger function (mimics DataTables print functionality)
      $('#printLedgerBtn').on('click', function() {
        printLedgerTable();
      });
    }); // End $(document).ready

    // Print Ledger Table Function (DataTables style)
    function printLedgerTable() {
      // Get account and month info for title
      var accountSelect = $('#account');
      var accountText = accountSelect.find('option:selected').text();
      var monthInput = $('input[name="month"]');
      var monthText = monthInput.val() || 'All Months';

      // Clone the table
      var table = $('#dataTableBuilder').clone();

      // Remove filter and column control columns
      table.find('th:last-child, th:nth-last-child(2)').remove();
      table.find('td:last-child, td:nth-last-child(2)').remove();

      // Remove no-print elements (View File links)
      table.find('.no-print').remove();

      // Create print window
      var printWindow = window.open('', '_blank', 'width=800,height=600');

      // Get current date
      var currentDate = new Date().toLocaleDateString();

      // Build print HTML
      var printContent = '<!DOCTYPE html>' +
        '<html>' +
        '<head>' +
        '<title>Ledger Report</title>' +
        '<style>' +
        '@page { margin: 1cm; }' +
        'body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }' +
        '.print-header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }' +
        '.print-header h2 { margin: 0 0 5px 0; font-size: 18px; }' +
        '.print-header .info { font-size: 11px; color: #666; }' +
        'table { width: 100%; border-collapse: collapse; font-size: 11px; }' +
        'table th, table td { border: 1px solid #000; padding: 6px; text-align: left; }' +
        'table th { background-color: #f0f0f0; font-weight: bold; text-align: center; }' +
        'table tbody tr.table-info { background-color: #d1ecf1; }' +
        'table tbody tr.table-warning { background-color: #fff3cd; }' +
        'table tbody td.text-end { text-align: right; }' +
        'table tbody td.text-start { text-align: left; }' +
        'table tbody td.text-center { text-align: center; }' +
        '.print-footer { margin-top: 20px; font-size: 10px; color: #666; text-align: center; }' +
        '@media print { ' +
        'body { padding: 0; }' +
        '.print-header { page-break-after: avoid; }' +
        'table { page-break-inside: auto; }' +
        'table tr { page-break-inside: avoid; page-break-after: auto; }' +
        'table thead { display: table-header-group; }' +
        'table tfoot { display: table-footer-group; }' +
        '}' +
        '</style>' +
        '</head>' +
        '<body>' +
        '<div class="print-header">' +
        '<h2>Ledger Report</h2>' +
        '<div class="info">' +
        '<strong>Account:</strong> ' + (accountText || 'All Accounts') + ' | ' +
        '<strong>Month:</strong> ' + monthText + ' | ' +
        '<strong>Date:</strong> ' + currentDate +
        '</div>' +
        '</div>' +
        table[0].outerHTML +
        '<div class="print-footer">Generated on ' + currentDate + '</div>' +
        '<scr' + 'ipt>' +
        'window.onload = function() { window.print(); window.onafterprint = function() { window.close(); }; };' +
        '</scr' + 'ipt>' +
        '</body>' +
        '</html>';

      // Write content to print window
      printWindow.document.write(printContent);
      printWindow.document.close();
    }

  })(); // End jQuery availability check
</script>
@endsection