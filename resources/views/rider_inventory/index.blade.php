@extends('layouts.app')

@section('title', 'Rider Inventory')

@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<style>
    .totals-cards .total-card.active {
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.35);
    }
    .rider-inventory-index .totals-cards-single-row {
        overflow-x: auto;
    }
    .rider-inventory-index .totals-cards-single-row .total-card {
        flex: 1 1 0;
        min-width: 110px;
    }
</style>
@endpush

@section('content')
<section class="content-header">
    @include('flash::message')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12 col-lg-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="mb-0"></h3>
                    @canany(['riders_inventory_create', 'riders_inventory_edit'])
                    <div class="action-buttons d-flex justify-content-end">
                        <div class="action-dropdown-container">
                            <button type="button" class="action-dropdown-btn" id="addBikeDropdownBtn">
                                <i class="ti ti-plus"></i>
                                <span>Actions</span>
                                <i class="ti ti-chevron-down"></i>
                            </button>
                            <div class="action-dropdown-menu" id="addBikeDropdown">
                                @can('riders_inventory_create')
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);"
                                    data-action="{{ route('RiderInventory.assignForm') }}"
                                    data-size="xl" data-title="Assign Inventory">
                                    <i class="ti ti-package"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Assign Inventory</div>
                                        <div class="action-dropdown-item-desc">Assign items to a rider</div>
                                    </div>
                                </a>
                                @endcan
                                @can('riders_inventory_edit')
                                <a class="action-dropdown-item" href="{{ route('RiderInventory.returnToCustomerForm') }}">
                                    <i class="ti ti-truck-return"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Return to Customer</div>
                                        <div class="action-dropdown-item-desc">Mark returned items as sent back to customer</div>
                                    </div>
                                </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                    @endcanany
                </div>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">

    <div class="card rider-inventory-index">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control"
                    placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('RiderInventory.reports') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-report"></i> Reports
                </a>
            </div>
        </div>

        <div class="totals-cards totals-cards-single-row">
            <div class="total-card total-black {{ $statusFilter === '' ? 'active' : '' }}"
                role="button" tabindex="0" onclick="filterByInventoryStatus('')"
                style="cursor:pointer;">
                <div class="label"><i class="fa fa-users"></i> Riders</div>
                <div class="value">{{ $riderCount ?? 0 }}</div>
            </div>
            <div class="total-card total-1 {{ $statusFilter === '' ? 'active' : '' }}"
                role="button" tabindex="0" onclick="filterByInventoryStatus('')"
                style="cursor:pointer;">
                <div class="label"><i class="fa fa-cubes"></i> Total Items</div>
                <div class="value">{{ $totalItemsCount ?? 0 }}</div>
            </div>
            <div class="total-card total-blue {{ $statusFilter === 'assigned' ? 'active' : '' }}"
                role="button" tabindex="0" onclick="filterByInventoryStatus('assigned')"
                style="cursor:pointer;">
                <div class="label"><i class="fa fa-box"></i> Assigned</div>
                <div class="value">{{ $assignedCount ?? 0 }}</div>
            </div>
            <div class="total-card total-green {{ $statusFilter === 'returned' ? 'active' : '' }}"
                role="button" tabindex="0" onclick="filterByInventoryStatus('returned')"
                style="cursor:pointer;">
                <div class="label"><i class="fa fa-undo"></i> Returned</div>
                <div class="value">{{ $returnedCount ?? 0 }}</div>
            </div>
            <div class="total-card total-red {{ $statusFilter === 'lost' ? 'active' : '' }}"
                role="button" tabindex="0" onclick="filterByInventoryStatus('lost')"
                style="cursor:pointer;">
                <div class="label"><i class="fa fa-exclamation-triangle"></i> Lost</div>
                <div class="value">{{ $lostCount ?? 0 }}</div>
            </div>
            <div class="total-card total-3 {{ $statusFilter === 'returned_to_customer' ? 'active' : '' }}"
                role="button" tabindex="0" onclick="filterByInventoryStatus('returned_to_customer')"
                style="cursor:pointer;">
                <div class="label"><i class="fa fa-truck"></i> To Customer</div>
                <div class="value">{{ $returnedToCustomerCount ?? 0 }}</div>
            </div>
        </div>

        <div class="card-body">
            <div id="riderInventoryTableWrapper">
                @include('rider_inventory.assignment_index_table', ['riders' => $riders])
            </div>
            <div id="paginationLinks">
                @if(method_exists($riders, 'links'))
                {!! $riders->links('components.global-pagination') !!}
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('page-scripts')
<script>
function filterByInventoryStatus(status) {
    const url = new URL(window.location.href);
    const current = url.searchParams.get('status_filter') || '';
    if (status && status === current) {
        url.searchParams.delete('status_filter');
    } else if (status) {
        url.searchParams.set('status_filter', status);
    } else {
        url.searchParams.delete('status_filter');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

$(document).ready(function () {
    $('#quickSearch').on('keyup', function (e) {
        if (e.keyCode === 13 || $(this).val().length === 0) {
            const searchValue = $(this).val();
            const url = new URL(window.location.href);

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
@endpush
