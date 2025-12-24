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
    padding: 15px 0;
    margin-bottom: 0;
  }

  /* Make statistics cards compact */
  .sticky-statistics .totals-cards {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    margin-bottom: 0;
  }

  .sticky-statistics .total-card {
    flex: 1 1 0;
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
  }

  .sticky-statistics .total-card .label i {
    font-size: 10px;
  }

  .sticky-statistics .total-card .value {
    font-size: 14px;
    font-weight: 700;
    color: #111827;
  }

  /* Table container with scroll */
  .table-scroll-container {
    max-height: 500px; /* Adjust as needed */
    overflow-y: auto;
  }

  /* Make table headers sticky inside the scroll container */
  .table-scroll-container table thead th {
    position: sticky;
    top: 0; /* This will stick below the statistics */
    background-color: #f8f9fa !important;
    z-index: 999;
    border-bottom: 2px solid #dee2e6;
  }

  /* Calculate top position based on statistics height */
  .table-scroll-container.with-stats thead th {
    top: 130px; /* Height of statistics + some padding */
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
  
  .sticky-statistics .total-du {
    border-left-color: #6f42c1;
    background: linear-gradient(180deg, rgba(111, 66, 193, 0.06), rgba(111, 66, 193, 0.02));
  }

  .sticky-statistics .total-du .label {
    color: #6f42c1;
  }

  .sticky-statistics .total-etisalat {
    border-left-color: #c142bb;
    background: linear-gradient(180deg, rgba(193, 66, 187, 0.06), rgba(193, 66, 187, 0.02));
  }

  .sticky-statistics .total-etisalat .label {
    color: #c142bb;
  }

  /* Adjust table styling */
  #dataTableBuilder {
    margin-bottom: 0;
  }

  #dataTableBuilder thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 100;
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
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    {{-- @can('sim_create')
                    <a class="btn btn-warning action-btn ms-2" style="margin-right: 5px;"
                    href="{{ route('sims.trash') }}"  id="sim-trash-btn">
                        Trash
                    </a>
                    @endcan --}}
                    <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static"role="dialog" aria-hidden="true">
                       <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                          <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Filter Sims</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                             <div class="modal-body" id="searchTopbody">
                                <form id="filterForm" action="{{ route('sims.index') }}" method="GET">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="number">Sim Number</label>
                                            <input type="text" name="number" class="form-control" placeholder="Filter By Sim Number" value="{{ request('number') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="emi">EMI Number</label>
                                            <input type="text" name="emi" class="form-control" placeholder="Filter By EMI Number" value="{{ request('title') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="company">Company</label>
                                                <select class="form-control " id="company" name="company">
                                                @php
                                                $companies  = DB::table('sims')
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
                                        <div class="form-group col-md-4">
                                            <label for="status">Status</label>
                                            <select class="form-control " id="status" name="status">
                                                <option value="" selected>Select</option>
                                                <option value="1" >Active</option>
                                                <option value="0" >Inactive</option>
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
            </div>
        </div>
    </section>
    {{-- Include Column Control Panel --}}
    @include('components.column-control-panel', [
    'tableColumns' => $tableColumns,
    'exportRoute' => 'sims.export',
    'tableIdentifier' => 'sims_table',
    'fixedColumnsCount' => 1
    ])


    <div class="content px-3">
        @include('flash::message')
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

$(document).ready(function () {
    $('#company').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By Company",
        allowClear: true
    });
    $('#fleet_supervisor').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By Super Visor",
        allowClear: true
    });
    $('#status').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By status",
        allowClear: true
    });
});
</script>

<script type="text/javascript">
$(document).ready(function () {
    $('#filterForm').on('submit', function (e) {

        e.preventDefault();

        $('#loading-overlay').show();
        $('#searchModal').modal('hide');

        const loaderStartTime = Date.now();

        // Exclude _token and empty fields
        let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
        let formData = $.param(filteredFields);
        let filters = {
                number: $('input[name="number"]').val(),
                emi: $('input[name="emi"]').val(),
                company: $('#company').val(),
                status: $('#status').val(),
                fleet_supervisor: $('#fleet_supervisor').val()
            };

        $.ajax({
            url: "{{ route('sims.index') }}",
            type: "GET",
            data: formData,
            success: function (data) {
                $('#table-data').html(data.tableData);

                // Update URL
                let newUrl = "{{ route('sims.index') }}" + (formData ? '?' + formData : '');
                history.pushState(null, '', newUrl);

                // Re-initialize Column Control Panel
                reinitializeColumnControl();
                // Re-initialize Select2 for dynamically loaded selects
                $('#company, #fleet_supervisor, #status').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                    $(this).select2({
                        dropdownParent: $('#searchTopbody'),
                        placeholder: $(this).attr('placeholder') || "Select...",
                        allowClear: true
                    });
                });

                // Ensure loader is visible at least 3s
                const elapsed = Date.now() - loaderStartTime;
                const remaining = 1000 - elapsed;
                setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
            },
            error: function (xhr, status, error) {
                console.error(error);

                const elapsed = Date.now() - loaderStartTime;
                const remaining = 1000 - elapsed;
                setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
            }
        });
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

        // Close dropdown when pressing escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#addSimDropdown').removeClass('show');
            }
        });
});

</script>

<script>
function confirmDelete(url) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Sim will be deleted permanently!",
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
                placeholder: "Search...",
                allowClear: true,
                width: '100%'
            });
        }
    });
}, 500);
</script>
@endsection