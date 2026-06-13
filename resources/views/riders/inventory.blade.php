@extends('riders.view')
@section('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh + 350px);
    }
</style>
@endsection
@section('page_content')
<div class="card card-action mb-1">
    <div class="card-header align-items-center">
        <h5 class="card-action-title mb-0">
            <i class="ti ti-package ti-lg text-body me-2"></i>Rider Inventory
        </h5>
        <div class="d-flex gap-2 flex-wrap">
            @can('riderinventory_contract_print')
            @if($assignments->where('status', 'assigned')->isNotEmpty())
            <a href="{{ route('RiderInventory.assignmentContract', $rider->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                <i class="ti ti-file-certificate"></i> Assignment Contract
            </a>
            <a href="{{ route('RiderInventory.returnContractForm', $rider->id) }}" class="btn btn-sm btn-outline-warning">
                <i class="ti ti-arrow-back-up"></i> Return Contract
            </a>
            @endif
            @endcan
            @can('riderinventory_create')
            <a href="javascript:void(0);" class="btn btn-sm btn-primary show-modal"
                data-action="{{ route('RiderInventory.assignForm', $rider->id) }}"
                data-size="md" data-title="Assign Inventory Item">
                <i class="ti ti-plus"></i> Assign Item
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body table-responsive px-2" id="assignmentTableWrapper">
        @include('rider_inventory.assignment_table', compact('assignments', 'rider'))
    </div>
</div>
@endsection
