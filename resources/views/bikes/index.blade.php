@extends('layouts.app')

@section('title','Vehicles')

@push('third_party_stylesheets')

<style>
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

    .card-search input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    @media (max-width: 576px) {
        .filter-sidebar {
            width: 100%;
            right: -100%;
        }
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

    .totals-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0 10px 16px 10px;
    }

    .total-card {
        flex: 1 1 calc(16% - 8px);
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

    .total-active {
        border-left-color: #28a745;
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.06), rgba(16, 185, 129, 0.02));
    }

    .total-active .label {
        color: #28a745;
    }

    .total-bikes {
        border-left-color: #007bff;
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.06), rgba(59, 130, 246, 0.02));
    }

    .total-bikes .label {
        color: #007bff;
    }

    .total-inactive {
        border-left-color: #373536;
        background: linear-gradient(180deg, rgba(55, 53, 54, 0.06), rgba(55, 53, 54, 0.02));
    }

    .total-inactive .label {
        color: #544d4d;
    }

    .total-absconded {
        border-left-color: #dc3545;
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.06), rgba(239, 68, 68, 0.02));
    }

    .total-absconded .label {
        color: #dc3545;
    }

    .total-onroad {
        border-left-color: #6f42c1;
        background: linear-gradient(180deg, rgba(111, 66, 193, 0.06), rgba(111, 66, 193, 0.02));
    }

    .total-onroad .label {
        color: #6f42c1;
    }

    .total-offroad {
        border-left-color: #c142bb;
        background: linear-gradient(180deg, rgba(193, 66, 187, 0.06), rgba(193, 66, 187, 0.02));
    }

    .total-offroad .label {
        color: #c142bb;
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
<section class="row content-header">
    <div>
        <div class="row mb-2">
            <div class="col-sm-6">
            </div>
            <div class="col-sm-6">
                <div class="action-buttons d-flex justify-content-end">
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Add Vehicle</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addBikeDropdown">
                            @can('bike_create')
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Vehicle" data-action="{{ route('bikes.create') }}">
                                <i class="ti ti-plus"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Create New Vehicle</div>
                                    <div class="action-dropdown-item-desc">Add a new vehicle to the system</div>
                                </div>
                            </a>
                            @endcan
                            @can('bike_create')
                            <a class="action-dropdown-item" href="{{ route('bikes.import') }}">
                                <i class="ti ti-file-upload"></i>
                                <span>Import Vehicles</span>
                            </a>
                            @endcan
                            @can('bike_view')
                            <a class="action-dropdown-item" href="{{ route('bikes.export')}}" data-size="xl" data-title="Export Vehicles" data-action="{{ route('bikes.export') }}">
                                <i class="ti ti-file-export"></i>
                                <span>Export Vehicles</span>
                            </a>
                            @endcan

                            @can('bike_create')
                            <a class="action-dropdown-item openColumnControlSidebar" href="javascript:void(0);" data-size="sm" data-title="Column Control">
                                    <i class="ti ti-columns"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Column Control</div>
                                        <div class="action-dropdown-item-desc">Open column control modal</div>
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

<!-- Filter Sidebar -->
<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Vehicles</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('bikes.index') }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="bike_code">Filter by Code</label>
                    <select class="form-control " id="bike_code" name="bike_code">
                        @php
                        $bikecode = DB::table('bikes')
                        ->whereNotNull('bike_code')
                        ->where('bike_code', '!=', '')
                        ->pluck('bike_code')
                        ->unique();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($bikecode as $code)
                        <option value="{{ $code }}" {{ request('bike_code') == $code ? 'selected' : '' }}>{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="plate">Plate</label>
                    <input type="text" name="plate" class="form-control" placeholder="Filter By Plate" value="{{ request('plate') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="rider_id">Rider ID</label>
                    <input type="text" name="rider_id" class="form-control" placeholder="Filter By Rider ID" value="{{ request('rider_id') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="rider">Filter by Rider</label>
                    <select class="form-control " id="rider" name="rider">
                        @php
                        $riderid = DB::table('bikes')
                        ->whereNotNull('rider_id')
                        ->where('rider_id', '!=', '')
                        ->pluck('rider_id')
                        ->unique();
                        $riders = DB::table('riders')
                        ->whereIn('id', $riderid)
                        ->select('rider_id','id', 'name')
                        ->get();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($riders as $rider)
                        <option value="{{ $rider->id }}" {{ request('rider_id') == $rider->rider_id ? 'selected' : '' }}>{{ $rider->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="customer_id">Filter by Customer</label>
                    <select class="form-control " id="customer_id" name="customer_id">
                        @php
                        $customerids = DB::table('bikes')
                        ->whereNotNull('customer_id')
                        ->where('customer_id', '!=', '')
                        ->pluck('customer_id')
                        ->unique();
                        $customers = DB::table('customers')
                        ->whereIn('id', $customerids)
                        ->select('id', 'name')
                        ->get();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="company">Filter by Company</label>
                    <select class="form-control " id="company" name="company">
                        @php
                        $companiesid = DB::table('bikes')
                        ->whereNotNull('company')
                        ->where('company', '!=', '')
                        ->pluck('company')
                        ->unique();
                        $companies = DB::table('leasing_companies')
                        ->whereIn('id', $companiesid)
                        ->select('id', 'name')
                        ->get();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ request('company') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="emirates">Filter by Emirates</label>
                    <select class="form-control " id="emirates" name="emirates">
                        @php
                        $emirates = DB::table('bikes')
                        ->whereNotNull('emirates')
                        ->where('emirates', '!=', '')
                        ->pluck('emirates')
                        ->unique();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($emirates as $emirate)
                        <option value="{{ $emirate }}" {{ request('emirates') == $emirate ? 'selected' : '' }}>{{ $emirate }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="warehouse">Filter by Warehouse</label>
                    <select class="form-control " id="warehouse" name="warehouse">
                        @php
                        $warehouses = DB::table('bikes')
                        ->whereNotNull('warehouse')
                        ->where('warehouse', '!=', '')
                        ->pluck('warehouse')
                        ->unique();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse }}" {{ request('warehouse') == $warehouse ? 'selected' : '' }}>{{ $warehouse }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="expiry_date_from">Expiry Date From</label>
                    <input type="date" name="expiry_date_from" class="form-control" placeholder="Filter By Expiry Date From" value="{{ request('expiry_date_from') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="expiry_date_to">Expiry Date To</label>
                    <input type="date" name="expiry_date_to" class="form-control" placeholder="Filter By Expiry Date To" value="{{ request('expiry_date_to') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Filter by Status</label>
                    <select class="form-control " id="status" name="status">
                        <option value="" selected>Select</option>
                        <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Active</option>
                        <option value="3" {{ request('status') == 3 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Filter Overlay -->
<div id="filterOverlay" class="filter-overlay"></div>
</section>
{{-- Include Column Control Panel --}}
@include('components.column-control-panel', [
'tableColumns' => $tableColumns,
'exportRoute' => 'bikes.export',
'tableIdentifier' => 'bikes_table'
])

<div class="content">
    @include('flash::message')
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i>  Filter Vehicles</button>
        </div>
        <div class="totals-cards">
            <div class="total-card total-bikes">
                <div class="label"><i class="fa fa-motorcycle"></i>Total Bikes</div>
                <div class="value" id="total_orders">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="total-card total-active">
                <div class="label"><i class="fa fa-check-circle"></i>Active</div>
                <div class="value" id="avg_ontime">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="total-card total-inactive">
                <div class="label"><i class="fa fa-times-circle"></i>Inactive</div>
                <div class="value" id="total_rejected">{{ $stats['inactive'] ?? 0 }}</div>
            </div>
                <div class="total-card total-onroad">
                <div class="label"><i class="fa fa-building"></i>Onroad</div>
                <div class="value" id="total_hours">{{ $stats['onroad'] ?? 0 }}</div>
            </div>
            <div class="total-card total-offroad">
                <div class="label"><i class="fa fa-building"></i>Offroad</div>
                <div class="value" id="total_hours">{{ $stats['offroad'] ?? 0 }}</div>
            </div>
            <div class="total-card total-absconded">
                <div class="label"><i class="fa fa-user-secret"></i>Absconded</div>
                <div class="value" id="total_hours">{{ $stats['absconded'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            <div class="bikes-table-container">
                @include('bikes.table', ['data' => $data, 'tableColumns' => $tableColumns])
            </div>
            <div class="filter-loading-overlay" style="display: none;">
                <div class="filter-loading-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Applying filters...</p>
                </div>
            </div>
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
        $('#bike_code').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Bike Code",
            allowClear: true
        });
        $('#rider').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Rider",
            allowClear: true
        });
        $('#company').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Company",
            allowClear: true
        });
        $('#emirates').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Emirates",
            allowClear: true
        });
        $('#warehouse').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Warehouse",
            allowClear: true
        });
        $('#status').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Status",
            allowClear: true
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
        // Filter sidebar functionality
        $(document).on('mouseenter', '#openFilterSidebar, .openFilterSidebar', function(e) {
            e.preventDefault();
            console.log('Filter button hovered!'); // Debug line
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
            return false;
        });

        $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
            e.preventDefault();
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

        // Quick search input (main) - redirect to URL with search parameter
        $('#quickSearch').on('keyup', function(e) {
            if (e.keyCode === 13 || $(this).val().length === 0) {
                const searchValue = $(this).val();
                const url = new URL(window.location);

                if (searchValue) {
                    url.searchParams.set('quick_search', searchValue);
                } else {
                    url.searchParams.delete('quick_search');
                }

                window.location.href = url.toString();
            }
        });

        // Quick search input (sidebar) - redirect to URL with search parameter
        $('#quickSearchSidebar').on('keyup', function(e) {
            if (e.keyCode === 13 || $(this).val().length === 0) {
                const searchValue = $(this).val();
                const url = new URL(window.location);

                if (searchValue) {
                    url.searchParams.set('quick_search', searchValue);
                } else {
                    url.searchParams.delete('quick_search');
                }

                window.location.href = url.toString();
            }
        });

        // Handle delete bike functionality
        $(document).on('click', '.delete-bike', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            confirmDelete(url);
        });

        // Action dropdown functionality
        $(document).on('click', '#addBikeDropdownBtn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropdown = $('#addBikeDropdown');
            dropdown.toggleClass('show');
        });

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.action-dropdown-container').length) {
                $('#addBikeDropdown').removeClass('show');
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
                $('#addBikeDropdown').removeClass('show');
                $('#filterSidebar').removeClass('open');
            }
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