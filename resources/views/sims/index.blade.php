@extends('layouts.app')

@section('title','Sims')

@push('third_party_stylesheets')
<style>
  /* Sticky header container */
  .sticky-header-container {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: white;
  }

  /* Statistics section */
  .sticky-statistics {
    background: white;
    border-bottom: 1px solid #dee2e6;
    padding: 10px 0;
    margin-bottom: 0;
  }

  /* Make statistics cards compact */
  .sticky-statistics .totals-cards {
    display: flex;
    gap: 8px;
    margin-bottom: 0;
  }

  .sticky-statistics .totals-cards-single-row {
    flex-wrap: nowrap;
  }

  .sticky-statistics .totals-cards-single-row .total-card {
    flex: 1 1 0;
    min-width: 0;
  }

  .sticky-statistics .total-card {
    flex: 1 1 calc(20% - 8px);
    min-width: 120px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-left-width: 4px;
    border-radius: 6px;
    padding: 8px 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    margin-bottom: 0;
  }

  .sticky-statistics .total-card .label {
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

  .sticky-statistics .total-card .label i {
    font-size: 10px;
    flex-shrink: 0;
  }

  .sticky-statistics .total-card .value {
    font-size: 14px;
    font-weight: 700;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Table container with scroll */
  .table-scroll-container {
    max-height: calc(100vh - 350px);
    overflow-y: auto;
    overflow-x: auto;
    position: relative;
    -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
  }

  /* Hide scrollbar for Chrome, Safari and Opera */
  .table-scroll-container::-webkit-scrollbar {
    display: none;
  }

  /* Hide scrollbar for IE, Edge and Firefox */
  .table-scroll-container {
    -ms-overflow-style: none; /* IE and Edge */
    scrollbar-width: none; /* Firefox */
  }

  /* Make table headers sticky inside the scroll container */
  .table-scroll-container table thead th {
    font-weight: bold;
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #f8f9fa;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
  }

  /* Statistics card specific colors */
  .sticky-statistics .total-active {
    border-left-color: #28a745;
     background: linear-gradient(180deg, rgba(16, 185, 129, 0.06), rgba(16, 185, 129, 0.02));
  }

  .sticky-statistics .total-active .label {
    color: #28a745;
  }

  .sticky-statistics .total-sims {
    border-left-color: #007bff;
    background: linear-gradient(180deg, rgba(59, 130, 246, 0.06), rgba(59, 130, 246, 0.02));
  }

  .sticky-statistics .total-sims .label {
    color: #007bff;
  }

  .sticky-statistics .total-inactive {
    border-left-color: #dc3545;
    background: linear-gradient(180deg, rgba(239, 68, 68, 0.06), rgba(239, 68, 68, 0.02));
  }
  
  .sticky-statistics .total-inactive .label {
    color: #dc3545;
  }

  .sticky-statistics .total-user-absconded {
    border-left-color: #7c2d12;
    background: linear-gradient(180deg, rgba(124, 45, 18, 0.08), rgba(124, 45, 18, 0.02));
  }

  .sticky-statistics .total-user-absconded .label {
    color: #9a3412;
  }

  .sticky-statistics a.total-card {
    display: block;
    color: inherit;
    text-decoration: none;
    cursor: pointer;
    transition: box-shadow 0.15s ease, transform 0.15s ease;
  }

  .sticky-statistics a.total-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .sticky-statistics a.total-card.is-active {
    box-shadow: 0 0 0 2px rgba(17, 24, 39, 0.28);
  }

  .sticky-statistics .total-in-office {
    border-left-color: #0dcaf0;
    background: linear-gradient(180deg, rgba(13, 202, 240, 0.08), rgba(13, 202, 240, 0.02));
  }

  .sticky-statistics .total-in-office .label {
    color: #0aa2c0;
  }
  
  .sticky-statistics .total-du,
  .sticky-statistics .total-sim-company-0 {
    border-left-color: #6f42c1;
    background: linear-gradient(180deg, rgba(111, 66, 193, 0.06), rgba(111, 66, 193, 0.02));
  }

  .sticky-statistics .total-du .label,
  .sticky-statistics .total-sim-company-0 .label {
    color: #6f42c1;
  }

  .sticky-statistics .total-etisalat,
  .sticky-statistics .total-sim-company-1 {
    border-left-color: #c142bb;
    background: linear-gradient(180deg, rgba(193, 66, 187, 0.06), rgba(193, 66, 187, 0.02));
  }

  .sticky-statistics .total-etisalat .label,
  .sticky-statistics .total-sim-company-1 .label {
    color: #c142bb;
  }

  .sticky-statistics .total-sim-company-2 {
    border-left-color: #d97706;
    background: linear-gradient(180deg, rgba(217, 119, 6, 0.08), rgba(217, 119, 6, 0.02));
  }

  .sticky-statistics .total-sim-company-2 .label {
    color: #d97706;
  }

  /* Adjust table styling */
  #dataTableBuilder {
    margin-bottom: 0;
    width: 100%;
    min-width: 800px;
  }

  #dataTableBuilder td,
  #dataTableBuilder th {
    white-space: nowrap;
    padding: 8px 12px;
    vertical-align: middle;
  }

  /* Action Dropdown Styles */
    .action-buttons {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .action-dropdown-container {
        position: relative;
        display: inline-block;
    }

    .action-dropdown-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        min-width: 140px;
        justify-content: space-between;
    }

    .action-dropdown-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .action-dropdown-btn:active {
        transform: translateY(0);
    }

    .action-dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.08);
        min-width: 280px;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        margin-top: 8px;
        overflow: hidden;
    }

    .action-dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .action-dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        color: #333;
        text-decoration: none;
        transition: all 0.2s ease;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .action-dropdown-item:last-child {
        border-bottom: none;
    }

    .action-dropdown-item:hover {
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
        color: #667eea;
        text-decoration: none;
    }

    .action-dropdown-item i {
        font-size: 18px;
        width: 24px;
        text-align: center;
        color: #667eea;
    }

    .action-dropdown-item-text {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 2px;
    }

    .action-dropdown-item-desc {
        font-size: 12px;
        color: #666;
        line-height: 1.3;
    }

    .filter-sidebar {
        position: fixed;
        top: 0;
        right: -420px;
        width: 420px;
        height: 100%;
        background: #ffffff;
        box-shadow: -2px 0 8px rgba(0, 0, 0, .1);
        z-index: 1051;
        transition: right .3s ease;
        overflow-y: auto;
        border-left: 1px solid #dee2e6;
    }

    .filter-sidebar.open {
        right: 0;
    }

    .filter-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, .4);
        z-index: 1050;
        display: none;
    }

    .filter-overlay.show {
        display: block;
    }

    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #eee;
        background: #f8f9fa;
    }

    .filter-body {
        padding: 1rem;
        height: calc(100vh - 70px);
        overflow-y: auto;
    }

    .filter-sidebar .btn-close {
        box-shadow: none;
    }

    .card-search input {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 8px 12px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sticky-statistics .totals-cards {
        gap: 6px;
        }
        
        .sticky-statistics .totals-cards-single-row .total-card {
        flex: 1 1 0;
        min-width: 0;
        padding: 6px 8px;
        }
        
        .sticky-statistics .total-card .label {
        font-size: 9px;
        }
        
        .sticky-statistics .total-card .value {
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
        
        /* Action dropdown adjustments */
        .action-dropdown-menu {
        right: -20px;
        min-width: 260px;
        }

        .action-dropdown-btn {
        min-width: 120px;
        padding: 10px 16px;
        font-size: 13px;
        }
        
        /* Filter button on mobile */
        .openFilterSidebar {
        font-size: 12px;
        padding: 6px 12px;
        }
        
        .filter-sidebar {
            width: 250px;
        }
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush
@section('content')
    <section class="content-header ">
        @include('flash::message')
        <div>
            <div class="row mb-2">
                <div class="col-sm-12 col-lg-12">
                    @include('sims.partials.actions_dropdown')
                </div>
            </div>
        </div>
    </section>

    @include('sims.partials.nav_tabs')

    {{-- Include Column Control Panel --}}
    @include('components.column-control-panel', [
    'tableColumns' => $tableColumns,
    'exportRoute' => 'sims.export',
    'tableIdentifier' => 'sims_table',
    'fixedColumnsCount' => 1
    ])

    <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
        <div class="filter-header">
            <h5>Filter Sims</h5>
            <button type="button" class="btn-close" id="closeSidebar"></button>
        </div>
        <div class="filter-body" id="searchTopbody">
            <form id="filterForm" action="{{ route('sims.index') }}" method="GET" data-rfp-skip-lock="1">
                @csrf
                <div class="row">
                    @if(auth()->user()->hasMultiplebranches())
                    @fieldVisible('sim', 'branch_id')
                    <div class="form-group col-md-12">
                        <label for="branch_id">Filter by Branch</label>
                        <select class="form-control " id="branch_id" name="branch_id">
                            @foreach(auth()->user()->branchDropdown() as $id => $name)
                            <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endfieldVisible
                    @endif
                    @fieldVisible('sim', 'number')
                    <div class="form-group col-md-12 col-sm-12">
                            <label for="number">Sim Number</label>
                            <input type="text" name="number" class="form-control" placeholder="Filter By Sim Number" value="{{ request('number') }}">
                        </div>
                    @endfieldVisible
                    @fieldVisible('sim', 'emi')
                    <div class="form-group col-md-12">
                        <label for="emi">EMI Number</label>
                        <input type="text" name="emi" class="form-control" placeholder="Filter By EMI Number" value="{{ request('emi') }}">
                    </div>
                    @endfieldVisible
                    @fieldVisible('sim', 'company')
                    <div class="form-group col-md-12">
                        <label for="company">Company</label>
                            <select class="form-control " id="company" name="company">
                            @php
                            $companies  = company_table('sims')
                                ->whereNotNull('company')
                                ->select('company')
                                ->distinct()
                                ->pluck('company');
                            @endphp
                            <option value="" selected>Select</option>
                            @foreach($companies as $company)
                                <option value="{{ $company }}" {{ request('company') == $company ? 'selected' : '' }}>{{ $company }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endfieldVisible
                    @fieldVisible('sim', 'status')
                    <div class="form-group col-md-12">
                        <label for="status">Status</label>
                        <select class="form-control " id="status" name="status">
                            <option value="" {{ request('status') ? '' : 'selected' }}>Select</option>
                            <option value="assigned" {{ in_array(request('status'), ['assigned', 'active'], true) ? 'selected' : '' }}>Assigned</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="in_office" {{ request('status') == 'in_office' ? 'selected' : '' }}>In office</option>
                            <option value="user_absconded" {{ in_array(request('status'), ['user_absconded', 'absconded'], true) ? 'selected' : '' }}>User Absconded</option>
                        </select>
                    </div>
                    @endfieldVisible
                    <div class="col-md-12 form-group text-center">
                        <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content">
        <div class="clearfix"></div>
        <div class="card">
            <div class="card-body table-responsive px-2 py-0" id="table-data">
                @include('sims.table', ['data' => $data, 'stats' => $stats, 'tableColumns' => $tableColumns])
            </div>
        </div>
    </div>

@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">

$(document).ready(function () {
    $('#company').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By Company",
        allowClear: true
    });
    $('#status').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By status",
        allowClear: true
    });
    $('#branch_id').select2({
        dropdownParent: $('#searchTopbody'),
        allowClear: true
    });
});
</script>

<script type="text/javascript">
$(document).ready(function () {
    // Filter sidebar functionality - open on hover
    $(document).on('mouseenter', '#openFilterSidebar, .openFilterSidebar', function(e) {
        e.preventDefault();
        console.log('Filter button hovered!'); // Debug line
        $('#filterSidebar').addClass('open');
        $('#filterOverlay').addClass('show');
        return false;
    });

    if ("{{ session('message') }}") {
        toastr.success("{{ session('message') }}");
    }
    if ("{{ session('error') }}") {
        toastr.error("{{ session('error') }}");
    }

    // Filter sidebar functionality
    $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
        console.log('Filter button clicked!'); // Debug line
        $('#filterSidebar').addClass('open');
        $('#filterOverlay').addClass('show');
        return false;
    });

    $('#closeSidebar, #filterOverlay').on('click', function() {
        $('#filterSidebar').removeClass('open');
        $('#filterOverlay').removeClass('show');
    });

    $('#filterForm').on('submit', function(e) {
        // Let the form submit naturally - no need to prevent default
        $('#filterSidebar').removeClass('open');
        $('#filterOverlay').removeClass('show');

        
    });

    // Action dropdown functionality
    $(document).on('click', '#addSimDropdownBtn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const dropdown = $('#addSimDropdown');
        dropdown.toggleClass('show');
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.action-dropdown-container').length) {
            $('#addSimDropdown').removeClass('show');
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#filterSidebar').length) {
            $('#filterSidebar').removeClass('open');
        }
    });

    // Close dropdown when pressing escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#addSimDropdown').removeClass('show');
            $('#filterSidebar').removeClass('open');
        }
    });
});

</script>

<script>
function confirmDelete(url) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will move the SIM to the Recycle Bin!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: response.queued ? 'Delete requested' : 'Deleted!',
                        html: response.message,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => { location.reload(); });
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while deleting.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors).join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire({ icon: 'error', title: 'Error!', html: errorMessage });
                }
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Initialize sorting when page loads
    initializeTableSorting();
    
    // Re-initialize after AJAX loads
    $(document).ajaxComplete(function() {
        setTimeout(initializeTableSorting, 100);
    });
});

function reinitializeColumnControl() {
    // Check if column controller exists
    if (typeof ColumnController !== 'undefined') {
        // Reattach event listeners to new DOM elements
        ColumnController.setupEventListeners();
        ColumnController.loadUserSettings();
    } else {
        // If ColumnController not defined, wait and retry
        setTimeout(reinitializeColumnControl, 100);
    }
}

function initializeTableSorting() {
    const table = document.querySelector('#dataTableBuilder');
    if (!table) return;
    
    const headers = table.querySelectorAll('th.sorting');
    const tbody = table.querySelector('tbody');

    // Remove previous event listeners
    headers.forEach(header => {
        header.replaceWith(header.cloneNode(true));
    });

    // Get fresh references
    const newHeaders = table.querySelectorAll('th.sorting');

    newHeaders.forEach((header, colIndex) => {
        header.addEventListener('click', () => {
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const isAsc = header.classList.contains('sorted-asc');

            // Clear previous sort classes
            newHeaders.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));

            // Add new sort direction
            header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');

            // Sort logic
            rows.sort((a, b) => {
                let aText = a.children[colIndex]?.textContent.trim().toLowerCase();
                let bText = b.children[colIndex]?.textContent.trim().toLowerCase();

                // Special handling for action column
                if (colIndex >= 4) {
                    // For action columns, don't sort
                    return 0;
                }

                // Check if values are numeric
                const aNum = parseFloat(aText);
                const bNum = parseFloat(bText);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    // Numeric comparison
                    return isAsc ? bNum - aNum : aNum - bNum;
                } else {
                    // String comparison
                    if (aText < bText) return isAsc ? 1 : -1;
                    if (aText > bText) return isAsc ? -1 : 1;
                    return 0;
                }
            });

            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        });
    });
}

// Fallback: Periodically check and initialize Select2 for dynamically loaded modals
setInterval(function() {
    $('.modal:visible select').each(function() {
        var $select = $(this);
        if (!$select.hasClass('select2-hidden-accessible')) {
            console.log('Fallback: Initializing', $select.attr('id'));
            $select.select2({
                dropdownParent: $('.modal:visible'),
                allowClear: true,
                width: '100%'
            });
        }
    });
}, 500);

// Keep action dropdown visible outside scroll/overflow containers.
$(document).on('shown.bs.dropdown', '#table-data .sim-table-action-dropdown', function() {
    var $dropdown = $(this);
    var $toggle = $dropdown.find('[data-bs-toggle="dropdown"]').first();
    var $menu = $dropdown.find('.sim-table-dropdown-menu').first();
    if (!$toggle.length || !$menu.length) return;

    var toggleRect = $toggle[0].getBoundingClientRect();
    var menuWidth = $menu.outerWidth() || 180;
    var menuHeight = $menu.outerHeight() || 0;
    var left = toggleRect.right - menuWidth;
    var top = toggleRect.bottom + 4;

    if (left < 8) left = 8;
    var maxLeft = window.innerWidth - menuWidth - 8;
    if (left > maxLeft) left = maxLeft;

    var maxBottom = window.innerHeight - 8;
    if (top + menuHeight > maxBottom) {
        top = Math.max(8, toggleRect.top - menuHeight - 4);
    }

    $menu.data('dropdown-parent', $dropdown);
    $menu.appendTo('body').css({
        position: 'fixed',
        top: top + 'px',
        left: left + 'px',
        zIndex: 2000
    });
});

$(document).on('hide.bs.dropdown', '#table-data .sim-table-action-dropdown', function() {
    var $dropdown = $(this);
    var $menu = $('.sim-table-dropdown-menu[aria-labelledby="' + $dropdown.find('[data-bs-toggle="dropdown"]').attr('id') + '"]');
    if (!$menu.length) return;
    $menu.css({
        position: '',
        top: '',
        left: '',
        zIndex: 1050
    }).appendTo($dropdown);
});
</script>
@endsection