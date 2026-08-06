@extends('layouts.app')

@section('title','Fuel Cards')

@section('content')
    <section class="content-header ">
        @include('flash::message')
        <div>
            <div class="row mb-2">
                <div class="col-sm-12 col-lg-12">
                    @include('fuel_cards.partials.actions_dropdown')
                </div>
            </div>
        </div>
    </section>

    @include('fuel_cards.partials.nav_tabs')

    <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
        <div class="filter-header">
            <h5>Filter Fuel Cards</h5>
            <button type="button" class="btn-close" id="closeSidebar"></button>
        </div>
        <div class="filter-body" id="searchTopbody">
            <form id="filterForm" action="{{ route('fuelCards.index') }}" method="GET">
                <input type="hidden" name="quick_search" value="{{ request('quick_search') }}">
                <div class="row">
                    @if(auth()->user()->hasMultiplebranches())
                    <div class="form-group col-md-12">
                        <label for="branch_id">Filter by Branch</label>
                        <select class="form-control " id="branch_id" name="branch_id">
                            @foreach(auth()->user()->branchDropdown() as $id => $name)
                            <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="form-group col-md-12 col-sm-12">
                            <label for="number">Card Number</label>
                            <input type="text" name="card_number" class="form-control" placeholder="Filter By Card Number" >
                        </div>
                    <div class="form-group col-md-12">
                        <label for="assigned_to">User</label>
                        <select name="assigned_to" class="form-control" id="user">
                            <option value="">Select</option>
                            @foreach(\App\Models\Riders::where('status', 1)->get() as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->rider_id.'-'.$user->name }}
                            </option>
                            @endforeach
                        </select>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Search card no, rider ID, name, plate..." value="{{ request('quick_search') }}">
                </div>
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
    $('#user').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By Assigned User",
        allowClear: true
    });
    $('#status').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By status",
        allowClear: true
    });
    $('#branch_id').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By Branch",
        allowClear: true
    });

    $('#quickSearch').on('keyup', function(e) {
        if (e.keyCode === 13 || $(this).val().length === 0) {
            const url = new URL(window.location);
            const searchValue = $(this).val().trim();
            if (searchValue) {
                url.searchParams.set('quick_search', searchValue);
            } else {
                url.searchParams.delete('quick_search');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }
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