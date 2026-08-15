@extends('layouts.app')

@section('title', 'Rider Inventory — ' . $rider->name)

@section('content')
<div class="content">
    @include('flash::message')
    @can('riders_inventory_view')
    @php $manageActions = true; @endphp
    <div class="card">
        <div class="card-header">
            <div class="container-fluid mb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="mb-0">Inventory</h3>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('RiderInventory.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left"></i> Back
                        </a>
                        @can('riders_inventory_view')
                        @if($assignments->where('status', 'assigned')->isNotEmpty())
                        <a href="{{ route('RiderInventory.assignmentContractForm', $rider->id) }}" class="btn btn-outline-primary" target="_blank">
                            <i class="ti ti-file-certificate"></i> Assignment Contract
                        </a>
                        <a href="{{ route('RiderInventory.returnContractForm', $rider->id) }}" class="btn btn-outline-warning" target="_blank">
                            <i class="ti ti-arrow-back-up"></i> Bulk Return
                        </a>
                        @endif
                        @endcan
                        @can('riders_inventory_create')
                        <a href="javascript:void(0);" class="btn btn-primary show-modal"
                            data-action="{{ route('RiderInventory.assignForm', $rider->id) }}"
                            data-size="xl" data-title="Assign Inventory">
                            <i class="ti ti-plus"></i> Assign Item
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body row">
            <div class="col-md-4">{{ $rider->rider_id . ' - ' . $rider->name }}</div>
        </div>
        <div class="card-body table-responsive" id="assignmentTableWrapper">
            @include('rider_inventory.assignment_table', compact('assignments', 'rider', 'manageActions'))
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">You are not authorized to access this page</h5>
        </div>
    </div>
    @endcan
</div>
@endsection
