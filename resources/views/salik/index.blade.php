@extends('layouts.app')
@section('title','Salik')
@push('third_party_stylesheets')
<style> 

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


    /* Responsive adjustments */
    @media (max-width: 768px) {

        /* Reduce table cell padding on mobile */
        #dataTableBuilder td,
        #dataTableBuilder th {
        padding: 6px 8px;
        font-size: 12px;
        }
        
    }
</style>
@endpush
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>{{ $account->name }} | Salik</h3>
            </div>
            <div class="col-sm-6">
                <div class="action-buttons d-flex justify-content-end">
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Add Salik</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addBikeDropdown">
                            @can('salik_create')
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Salik" data-action="{{ route('salik.create' , $account->id) }}">
                                <i class="ti ti-plus"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Add New Salik</div>
                                    <div class="action-dropdown-item-desc">Add a new salik against a bike</div>
                                </div>
                            </a>
                            @endcan
                            @can('salik_create')
                            <a class="action-dropdown-item" href="{{ route('salik.import.form', $account->id) }}">
                                <i class="ti ti-file-upload"></i>
                                <span>Import Saliks</span>
                            </a>
                            @endcan
                            @can('bike_view')
                            <a class="action-dropdown-item" href="#" data-size="xl" data-title="Export Salik Sheet" data-action="#">
                                <i class="ti ti-file-export"></i>
                                <span>Export Saliks</span>
                            </a>
                            @endcan

                            @can('salik_create')
                            <a class="action-dropdown-item" href="{{ route('salik.missing.records') }}" >
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Missing Salik Records</span>    
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
        <form id="filterForm" action="{{ route('salik.tickets', $account->id) }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="transaction_id">Transaction ID</label>
                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" placeholder="Filter By Transaction ID" value="{{ request('transaction_id') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="billing_month">Billing Month</label>
                    <input type="month" name="billing_month" id="billing_month" class="form-control" placeholder="Filter By Billing Month" value="{{ request('billing_month') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="rider_id">Filter by Rider</label>
                    <label for="rider_id">Rider</label>
                    <select name="rider_id" id="rider_id" class="form-contro">
                        <option value="">All Riders</option>
                        @foreach(DB::table('riders')->select('id', 'rider_id', 'name')->get() as $rider)
                        <option value="{{ $rider->id }}" {{ request('rider_id') == $rider->id ? 'selected' : '' }}>{{ $rider->rider_id }} - {{ $rider->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="trip_date">Trip Date</label>
                    <input type="date" name="trip_date" id="trip_date" class="form-control" placeholder="Filter By Trip Date" value="{{ request('trip_date') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="tag_number">Tag Number</label>
                    <input type="text" name="tag_number" id="tag_number" class="form-control" placeholder="Filter By Tag Number" value="{{ request('tag_number') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="plate">Plate</label>
                    <input type="text" name="plate" id="plate" class="form-control" placeholder="Filter By Plate" value="{{ request('plate') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="direction">Direction</label>
                    <select name="direction" id="direction" class="form-control">
                        <option value="">All Directions</option>
                        @foreach(DB::table('saliks')->select('direction')->distinct()->whereNotNull('direction')->pluck('direction') as $direction)
                        <option value="{{ $direction }}" {{ request('direction') == $direction ? 'selected' : '' }}>{{ $direction }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="toll_gate">Toll Gate</label>
                    <select name="toll_gate" id="toll_gate" class="form-control">
                        <option value="">All Toll Gates</option>
                        @foreach(DB::table('saliks')->select('toll_gate')->distinct()->whereNotNull('toll_gate')->pluck('toll_gate') as $toll_gate)
                        <option value="{{ $toll_gate }}" {{ request('toll_gate') == $toll_gate ? 'selected' : '' }}>{{ $toll_gate }}</option>
                        @endforeach
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
            <div class="total-card total-red">
                <div class="label"><i class="fa fa-times-circle"></i>Unpaid Saliks</div>
                <div class="value" id="avg_ontime">{{ $unpaidCount ?? 0 }}</div>
            </div>
            <div class="total-card total-2">
                <div class="label"><i class="far fa-money-bill-alt"></i>Unpaid Amount</div>
                <div class="value" id="total_hours">{{ $unpaidAmount ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="fas fa-stamp"></i>Paid Salik</div>
                <div class="value" id="total_rejected">{{ $paidCount ?? 0 }}</div>
            </div>
            <div class="total-card total-3">
                <div class="label"><i class="fa fa-ticket"></i>Paid Amount</div>
                <div class="value" id="total_orders">{{ $paidAmount ?? 0 }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('salik.table', ['data' => $data])
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
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the record.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading()
                    }
                });

                // Make AJAX request to delete
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'The Salik record has been deleted successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Reload the page to refresh the table
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while deleting the record.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            title: 'Error!',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        })
    }
    $(document).ready(function() {
        $('#rider_id').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: "Filter By Rider",
        });
        $('#direction').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: "Filter By Direction",
        });
        $('#toll_gate').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: "Filter By Toll Gate",
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