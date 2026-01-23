@extends('layouts.app')

@section('title','Fuel Cards')

@section('content')
    <section class="content-header ">
        @include('flash::message')
        <div>
            <div class="row mb-2">
                <div class="col-sm-12 col-lg-12">
                    <div class="action-buttons d-flex justify-content-end" >
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Add Fuel Card</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addBikeDropdown">
                            @can('fuel_create')
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="md" data-title="Add New Card" data-action="{{ route('fuelCards.create') }}">
                                <i class="ti ti-plus"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Add Fuel Card</div>
                                    <div class="action-dropdown-item-desc">Add a new Fuel Card</div>
                                </div>
                            </a>
                            @endcan
                            @can('fuel_create')
                            <a class="action-dropdown-item" href="{{ route('fuelCards.import') }}">
                                <i class="ti ti-file-upload"></i>
                                <span>Import Fuel card Data</span>
                            </a>
                            @endcan
                            @can('fuel_view')
                            <a class="action-dropdown-item" href="{{ route('fuelCards.export')}}">
                                <i class="ti ti-file-export"></i>
                                <span>Export Fuel Card Data</span>
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </section>

    <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
        <div class="filter-header">
            <h5>Filter Sims</h5>
            <button type="button" class="btn-close" id="closeSidebar"></button>
        </div>
        <div class="filter-body" id="searchTopbody">
            <form id="filterForm" action="{{ route('fuelCards.index') }}" method="GET">
                @csrf
                <div class="row">
                    <div class="form-group col-md-12 col-sm-12">
                            <label for="number">Card Number</label>
                            <input type="text" name="card_number" class="form-control" placeholder="Filter By Card Number" value="{{ request('card_number') }}">
                        </div>
                    <div class="form-group col-md-12">
                        <label for="assigned_to">User</label>
                        <input type="text" name="assigned_to" class="form-control" placeholder="Filter By User" value="{{ request('assigned_to') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="status">Status</label>
                        <select class="form-control " id="status" name="status">
                            <option value="" selected>Select</option>
                            <option value='Active' >Active</option>
                            <option value='Inactive' >Inactive</option>
                        </select>
                    </div>
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
            <div class="card-header text-end">
            <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i> Filter Cards</button>
        </div>
        <div class="totals-cards">
            <div class="total-card total-blue">
                <div class="label"><i class="fa fa-motorcycle"></i>Total Cards</div>
                <div class="value" id="total_orders">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="fa fa-check-circle"></i>Active</div>
                <div class="value" id="avg_ontime">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label"><i class="fa fa-times-circle"></i>Inactive</div>
                <div class="value" id="total_rejected">{{ $stats['inactive'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('fuel_cards.table', ['data' => $data,])
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
});
</script>

<script type="text/javascript">

$(document).ready(function () {


    $('#filterForm').on('submit', function(e) {
        // Let the form submit naturally - no need to prevent default
        $('#filterSidebar').removeClass('open');
        $('#filterOverlay').removeClass('show');

        
    });
});

</script>

<script>
function confirmDelete(url) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Card will be deleted permanently!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire(
                        'Deleted!',
                        'Fuel Card has been deleted.',
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire(
                        'Error!',
                        'Failed to delete Fuel Card. ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'),
                        'error'
                    );
                }
            });
        }
    })
}

</script>
@endsection