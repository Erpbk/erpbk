@extends('layouts.app')

@section('title','RTA Fines')
@push('third_party_stylesheets')
<style> 
   .totals-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0 10px 16px 10px;
    }

    .total-card {
        flex: 1 1 calc(10% - 8px);
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

    .total-paid {
        border-left-color: #28a745;
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.06), rgba(16, 185, 129, 0.02));
    }

    .total-paid .label {
        color: #28a745;
    }

    .total-accounts {
        border-left-color: #007bff;
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.06), rgba(59, 130, 246, 0.02));
    }

    .total-accounts .label {
        color: #007bff;
    }

    .total-inactive {
        border-left-color: #373536;
        background: linear-gradient(180deg, rgba(55, 53, 54, 0.06), rgba(55, 53, 54, 0.02));
    }

    .total-inactive .label {
        color: #544d4d;
    }

    .total-unpaid {
        border-left-color: #dc3545;
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.06), rgba(239, 68, 68, 0.02));
    }

    .total-unpaid .label {
        color: #dc3545;
    }

    .total-amount {
        border-left-color: #6f42c1;
        background: linear-gradient(180deg, rgba(111, 66, 193, 0.06), rgba(111, 66, 193, 0.02));
    }

    .total-amount .label {
        color: #6f42c1;
    }

    .total-tickets-amount {
        border-left-color: #c142bb;
        background: linear-gradient(180deg, rgba(193, 66, 187, 0.06), rgba(193, 66, 187, 0.02));
    }

    .total-tickets-amount .label {
        color: #c142bb;
    }

    .total-service-charges {
        border-left-color: #17a2b8;
        background: linear-gradient(180deg, rgba(23, 162, 184, 0.06), rgba(23, 162, 184, 0.02));
    }

    .total-service-charges .label {
        color: #17a2b8;
    }

    .total-admin-charges {
        border-left-color: #ffc107;
        background: linear-gradient(180deg, rgba(255, 193, 7, 0.06), rgba(255, 193, 7, 0.02));
    }

    .total-admin-charges .label {
        color: #ffc107;
    }

    #dataTableBuilder {
      margin-bottom: 0;
      min-width: 800px; 
      width: 100%;
   }

   #dataTableBuilder td,
   #dataTableBuilder th {
      white-space: nowrap;
      padding: 8px 12px;
      vertical-align: middle;
   }

   td:focus,
   th:focus {
      outline: 2px solid #2196f3;
      outline-offset: -2px;
      background: #e3f2fd;
   }

   th {
      white-space: nowrap;
   }

   /* Table header bold and fixed */
   #dataTableBuilder thead th {
      font-weight: bold;
      position: sticky;
      top: 0;
      z-index: 10;
      background-color: #f8f9fa;
      box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
   }

   /* Ensure table container is scrollable */
   .table-responsive {
      max-height: calc(100vh - 240px);
      overflow-y: auto;
      overflow-x: auto;
      position: relative;
      -webkit-overflow-scrolling: touch;
   }

   /* Hide scrollbar for Chrome, Safari and Opera */
   .table-responsive::-webkit-scrollbar {
      display: none;
   }

   /* Hide scrollbar for IE, Edge and Firefox */
   .table-responsive {
      -ms-overflow-style: none;
      /* IE and Edge */
      scrollbar-width: none;
      /* Firefox */
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

        .openFilterSidebar {
        font-size: 12px;
        padding: 6px 12px;
        }

        .filter-sidebar {
            width: 250px;
        }
    }
</style>
@endpush
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>{{ $account->name }} | Rta Fines</h3>
            </div>
            <div class="col-sm-6">
                <div class="action-buttons d-flex justify-content-end">
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Add Fine</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addBikeDropdown">
                            @can('rtafine_create')
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="New Fine" data-action="{{ route('rtaFines.create' , $account->id) }}">
                                <i class="ti ti-plus"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Add New Fine</div>
                                    <div class="action-dropdown-item-desc">Add a new fine against a bike</div>
                                </div>
                            </a>
                            @endcan
                            @can('rtafine_create')
                            <a class="action-dropdown-item" href="{{ route('rtaFines.import.form', $account->id) }}">
                                <i class="ti ti-file-upload"></i>
                                <span>Import Fines</span>
                            </a>
                            @endcan
                            @can('bike_view')
                            <a class="action-dropdown-item" href="#" data-size="xl" data-title="Export Vehicles" data-action="#">
                                <i class="ti ti-file-export"></i>
                                <span>Export Fines</span>
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
        <h5>Filter Fines</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('rtaFines.tickets', $account->id) }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="ticket_no">Ticket Number</label>
                    <input type="number" name="ticket_no" class="form-control" placeholder="Filter By Ticket Number" value="{{ request('ticket_no') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="trans_code">Transcation Code</label>
                    <input type="text" name="trans_code" class="form-control" placeholder="Filter By Transcation Code" value="{{ request('trans_code') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="billing_month">Billing Month</label>
                    <input type="month" name="billing_month" class="form-control" placeholder="Filter By Billing Month" value="{{ request('billing_month') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="rider_id">Filter by Rider</label>
                    <select class="form-control " id="rider_id" name="rider_id">
                        <option value="">Select</option>
                        @php
                        $riderid = DB::table('rta_fines')
                        ->whereNotNull('rider_id')
                        ->where('rider_id', '!=', '')
                        ->pluck('rider_id')
                        ->unique();
                        $riders = DB::table('riders')
                        ->whereIn('id', $riderid)
                        ->select('id', 'rider_id', 'name')
                        ->get();
                        @endphp
                        @foreach($riders as $rider)
                        <option value="{{ $rider->id }}" {{ request('rider_id') == $rider->id ? 'selected' : '' }}>{{ $rider->rider_id }} - {{ $rider->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="bike_id">Filter by Bike</label>
                    <select class="form-control " id="bike_id" name="bike_id">
                        @php
                        $bikeid = DB::table('rta_fines')
                        ->whereNotNull('bike_id')
                        ->where('bike_id', '!=', '')
                        ->pluck('bike_id')
                        ->unique();
                        $bikes = DB::table('bikes')
                        ->whereIn('id', $bikeid)
                        ->select('id', 'plate')
                        ->get();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($bikes as $bike)
                        <option value="{{ $bike->id }}" {{ request('bike_id') == $bike->id ? 'selected' : '' }}>{{ $bike->plate }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Filter by Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="" selected>Select</option>
                        <option value="paid" >paid</option>
                        <option value="unpaid" >unpaid</option>
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

<div class="content">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            <button class="btn btn-primary openFilterSidebar" id="openFilterSidebar"> <i class="fa fa-search"></i>  Filter Fines</button>
        </div>
        <div class="totals-cards">
            <div class="total-card total-accounts">
                <div class="label"><i class="fa fa-ticket"></i>Total Tickets</div>
                <div class="value" id="total_orders">{{ $totaltickets ?? 0 }}</div>
                <div class="label"><i class="fa fa-dollar"></i>Fine</div>
                <div class="value" id="total_orders">{{ $totalAmount ?? 0 }}</div>
            </div>
            <div class="total-card total-unpaid">
                <div class="label"><i class="fa fa-times-circle"></i>Unpaid Fines</div>
                <div class="value" id="avg_ontime">{{ $unpaidCount ?? 0 }}</div>
                <div class="label"><i class="fa fa-dollar"></i>Unpaid Amount</div>
                <div class="value" id="avg_ontime">{{ $unpaidAmount ?? 0 }}</div>
            </div>
            <div class="total-card total-paid">
                <div class="label"><i class="fas fa-stamp"></i>Paid Fines</div>
                <div class="value" id="total_rejected">{{ $paidCount ?? 0 }}</div>
                <div class="label"><i class="fa fa-dollar"></i>Paid Amount</div>
                <div class="value" id="total_rejected">{{ $paidAmount ?? 0 }}</div>
            </div>
            <div class="total-card total-tickets-amount">
                <div class="label"><i class="far fa-money-bill-alt"></i>Ticket Amount</div>
                <div class="value" id="total_hours">{{ $total_Amount ?? 0 }}</div>
            </div>
            <div class="total-card total-service-charges">
                <div class="label"><i class="far fa-money-bill-alt"></i>Service Charges</div>
                <div class="value" id="total_hours">{{ $serviceCharges ?? 0 }}</div>
            </div>
            <div class="total-card total-admin-charges">
                <div class="label"><i class="far fa-money-bill-alt"></i>Admin Charges</div>
                <div class="value" id="total_hours">{{ $adminFee ?? 0 }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('rta_fines.table', ['data' => $data])
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
        $('#rider_id').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: "Filter By Rider",
        });
        $('#bike_id').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: "Filter By Bike Plate",
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
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

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#filterSidebar').length) {
                $('#filterSidebar').removeClass('open');
            }
        });

        // Close dropdown when pressing escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
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