@extends('riders.view')
@section('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh + 350px);
    }
</style>
@endsection
@section('page_content')
@can('riders_inventory_view')
<div class="card card-action mb-1">
    <div class="card-header align-items-center">
        <h5 class="card-action-title mb-0">Inventory</h5>
        <div class="d-flex gap-1 flex-wrap">
            @can('riders_inventory_edit')
            <a href="{{ route('RiderInventory.returnToCustomerForm') }}" class="btn btn-sm btn-info" style="padding: 2px 8px; font-size: 0.75rem;">
                <i class="ti ti-truck-return"></i> Return to Customer
            </a>
            @endcan
            @can('riders_inventory_view')
            @if($assignments->where('status', 'assigned')->isNotEmpty())
            <a href="{{ route('RiderInventory.assignmentContract', $rider->id) }}" class="btn btn-sm btn-outline-primary" target="_blank" style="padding: 2px 8px; font-size: 0.75rem;">
                <i class="ti ti-file-certificate"></i> Assignment Contract
            </a>
            <a href="{{ route('RiderInventory.returnContractForm', $rider->id) }}" class="btn btn-sm btn-outline-warning" style="padding: 2px 8px; font-size: 0.75rem;">
                <i class="ti ti-arrow-back-up"></i> Return Contract
            </a>
            @endif
            @endcan
            @can('riders_inventory_create')
            <a href="javascript:void(0);" class="btn btn-sm btn-primary show-modal"
                data-action="{{ route('RiderInventory.assignForm', $rider->id) }}"
                data-size="xl" data-title="Assign Inventory"
                style="padding: 2px 8px; font-size: 0.75rem;">
                <i class="ti ti-plus"></i> Assign Item
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body table-responsive px-2" id="assignmentTableWrapper">
        @include('rider_inventory.assignment_table', compact('assignments', 'rider'))
    </div>
</div>
@else
<div class="card">
    <div class="card-body">
        <h5 class="card-title">You are not authorized to access this page</h5>
    </div>
</div>
@endcan
@endsection
