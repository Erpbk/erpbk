@extends('layouts.app')

@section('title','Fuel Cards')

@section('content')
    <style>
        a.total-card {
            display: block;
            color: inherit;
            text-decoration: none;
            cursor: pointer;
        }
        a.total-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        a.total-card.is-active {
            box-shadow: 0 0 0 2px rgba(17, 24, 39, 0.28);
        }
        .total-user-absconded {
            border-left-color: #7c2d12;
            background: linear-gradient(180deg, rgba(124, 45, 18, 0.08), rgba(124, 45, 18, 0.02));
        }
        .total-user-absconded .label { color: #9a3412; }
        .total-no-vehicle {
            border-left-color: #d97706;
            background: linear-gradient(180deg, rgba(217, 119, 6, 0.08), rgba(217, 119, 6, 0.02));
        }
        .total-no-vehicle .label { color: #b45309; }
        .total-vehicle-changed {
            border-left-color: #dc2626;
            background: linear-gradient(180deg, rgba(220, 38, 38, 0.08), rgba(220, 38, 38, 0.02));
        }
        .total-vehicle-changed .label { color: #b91c1c; }
    </style>
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
                            <input type="text" name="card_number" class="form-control" placeholder="Filter By Card Number" value="{{ request('card_number') }}">
                        </div>
                    <div class="form-group col-md-12">
                        <label for="assigned_to">User</label>
                        <select name="assigned_to" class="form-control" id="user">
                            <option value="">Select</option>
                            @foreach(\App\Models\Riders::where('status', 1)->get() as $user)
                            <option value="{{ $user->id }}" {{ (string) request('assigned_to') === (string) $user->id ? 'selected' : '' }}>
                                {{ $user->rider_id.'-'.$user->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="status">Status</label>
                        <select class="form-control " id="status" name="status">
                            <option value="" {{ request('status') ? '' : 'selected' }}>Select</option>
                            <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="In Office" {{ request('status') === 'In Office' ? 'selected' : '' }}>In Office</option>
                            <option value="Deactivated" {{ request('status') === 'Deactivated' ? 'selected' : '' }}>Deactivated</option>
                            <option value="Lost" {{ request('status') === 'Lost' ? 'selected' : '' }}>Lost</option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="other">Other</label>
                        <select class="form-control" id="other" name="other">
                            <option value="" {{ request('other') ? '' : 'selected' }}>Select</option>
                            <option value="absconded" {{ request('other') === 'absconded' ? 'selected' : '' }}>Absconded</option>
                            <option value="no_vehicle" {{ request('other') === 'no_vehicle' ? 'selected' : '' }}>No Vehicle Assigned</option>
                            <option value="vehicle_changed" {{ in_array(request('other'), ['vehicle_changed', 'vehicle-changed'], true) ? 'selected' : '' }}>Vehicle Changed</option>
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
        @php
            $statBaseQuery = request()->except(['page']);
            $currentOther = strtolower((string) request('other', ''));
            $abscondedActive = $currentOther === 'absconded';
            $noVehicleActive = $currentOther === 'no_vehicle';
            $vehicleChangedActive = in_array($currentOther, ['vehicle_changed', 'vehicle-changed'], true);
            $fuelStatUrl = function (array $overrides) use ($statBaseQuery) {
                $params = array_merge($statBaseQuery, $overrides);
                foreach ($params as $key => $value) {
                    if ($value === null || $value === '') {
                        unset($params[$key]);
                    }
                }
                return route('fuelCards.index', $params);
            };
        @endphp
        <div class="totals-cards">
            <div class="total-card total-blue">
                <div class="label"><i class="fa fa-motorcycle"></i>Total Cards</div>
                <div class="value" id="total_orders">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="fa fa-check-circle"></i>Active</div>
                <div class="value" id="avg_ontime">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="total-card total-blue">
                <div class="label"><i class="fa fa-building"></i>In Office</div>
                <div class="value" id="total_in_office">{{ $stats['in_office'] ?? 0 }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label"><i class="fa fa-times-circle"></i>Deactivated</div>
                <div class="value" id="total_rejected">{{ $stats['deactivated'] ?? 0 }}</div>
            </div>
            <div class="total-card total-1">
                <div class="label"><i class="fa fa-exclamation-triangle"></i>Lost</div>
                <div class="value" id="total_lost">{{ $stats['lost'] ?? 0 }}</div>
            </div>
            <a href="{{ $fuelStatUrl(['other' => $abscondedActive ? null : 'absconded']) }}" class="total-card total-user-absconded{{ $abscondedActive ? ' is-active' : '' }}" title="{{ $abscondedActive ? 'Clear Absconded filter' : 'Show cards assigned to absconded riders' }}">
                <div class="label"><i class="fa fa-user-secret"></i>Absconded</div>
                <div class="value" id="total_absconded">{{ $stats['absconded'] ?? 0 }}</div>
            </a>
            <a href="{{ $fuelStatUrl(['other' => $noVehicleActive ? null : 'no_vehicle']) }}" class="total-card total-no-vehicle{{ $noVehicleActive ? ' is-active' : '' }}" title="{{ $noVehicleActive ? 'Clear No Vehicle filter' : 'Show cards whose rider has no vehicle' }}">
                <div class="label"><i class="fa fa-motorcycle"></i>No Vehicle</div>
                <div class="value" id="total_no_vehicle">{{ $stats['no_vehicle'] ?? 0 }}</div>
            </a>
            <a href="{{ $fuelStatUrl(['other' => $vehicleChangedActive ? null : 'vehicle_changed']) }}" class="total-card total-vehicle-changed{{ $vehicleChangedActive ? ' is-active' : '' }}" title="{{ $vehicleChangedActive ? 'Clear Vehicle Changed filter' : 'Show cards whose rider vehicle changed' }}">
                <div class="label"><i class="fa fa-exchange"></i>Vehicle Changed</div>
                <div class="value" id="total_vehicle_changed">{{ $stats['vehicle_changed'] ?? 0 }}</div>
            </a>
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
    $('#other').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By other",
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