@extends('layouts.app')

@section('title', 'Rider Inventory')

@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
@endpush

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="mb-0">Rider Inventory</h3>
            @can('riderinventory_create')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#selectRiderModal">
                <i class="ti ti-package me-1"></i> Select Rider
            </button>
            @endcan
        </div>
    </div>
</section>

<div class="content">
    @include('flash::message')

    <div class="fleet-supervisor-section mb-3">
        <div class="fleet-supervisor-cards slider-track d-flex gap-3 flex-wrap">
            <div class="fleet-supervisor-card @if($statusFilter === '') active filtered @endif" onclick="filterByInventoryStatus('')">
                <h3 class="fleet-supervisor-name">All Riders</h3>
            </div>
            <div class="fleet-supervisor-card @if($statusFilter === 'assigned') active filtered @endif" onclick="filterByInventoryStatus('assigned')">
                <h3 class="fleet-supervisor-name">Assigned</h3>
                <div class="fleet-stat-value">{{ $assignedCount }}</div>
            </div>
            <div class="fleet-supervisor-card @if($statusFilter === 'returned') active filtered @endif" onclick="filterByInventoryStatus('returned')">
                <h3 class="fleet-supervisor-name">Returned</h3>
                <div class="fleet-stat-value">{{ $returnedCount }}</div>
            </div>
            <div class="fleet-supervisor-card @if($statusFilter === 'lost') active filtered @endif" onclick="filterByInventoryStatus('lost')">
                <h3 class="fleet-supervisor-name">Lost</h3>
                <div class="fleet-stat-value">{{ $lostCount }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Riders</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('RiderInventory.reports') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-report"></i> Reports
                </a>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="fa fa-search"></i> Filter
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="riderInventoryTableWrapper">
                @include('rider_inventory.rider_table', ['riders' => $riders, 'assignmentCounts' => $assignmentCounts])
            </div>
            <div id="paginationLinks">{!! $riders->links('components.global-pagination') !!}</div>
        </div>
    </div>
</div>

<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="filterForm" action="{{ route('RiderInventory.index') }}" method="GET">
                @if(request()->filled('status_filter'))
                <input type="hidden" name="status_filter" value="{{ request('status_filter') }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">Filter Riders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="quick_search">Quick Search</label>
                        <input type="text" name="quick_search" id="quick_search" class="form-control" value="{{ request('quick_search') }}" placeholder="Rider ID, name, person code">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>
</div>

@can('riderinventory_create')
<div class="modal fade" id="selectRiderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Rider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="rider_select">Rider</label>
                    <select id="rider_select" class="form-control select2">
                        <option value="">Select Rider</option>
                        @foreach($allRiders as $r)
                        <option value="{{ $r->id }}">{{ $r->rider_id }} - {{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="goToRiderInventoryBtn">Open Inventory</button>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@push('page_scripts')
<script>
function filterByInventoryStatus(status) {
    const url = new URL(window.location.href);
    if (status) {
        url.searchParams.set('status_filter', status);
    } else {
        url.searchParams.delete('status_filter');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

document.getElementById('goToRiderInventoryBtn')?.addEventListener('click', function () {
    const riderId = document.getElementById('rider_select').value;
    if (!riderId) {
        alert('Please select a rider.');
        return;
    }
    window.location.href = '{{ url('RiderInventory/show') }}/' + riderId;
});
</script>
@endpush
