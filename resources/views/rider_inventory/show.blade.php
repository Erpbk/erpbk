@extends('layouts.app')

@section('title', 'Rider Inventory — ' . $rider->name)

@section('content')
<section class="content-header">
</section>

<div class="content">
    @include('flash::message')

    <div class="card">
        <div class="card-header">
            <div class="container-fluid mb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="mb-0">Rider Inventory</h3>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @can('riderinventory_edit')
                        <a href="{{ route('RiderInventory.returnToCustomerForm') }}" class="btn btn-info">
                            <i class="ti ti-truck-return"></i> Return to Customer
                        </a>
                        @endcan
                        <a href="{{ route('RiderInventory.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left"></i> Back
                        </a>
                        @can('riderinventory_contract_print')
                        @if($assignments->where('status', 'assigned')->isNotEmpty())
                        <a href="{{ route('RiderInventory.assignmentContract', $rider->id) }}" class="btn btn-outline-primary" target="_blank">
                            <i class="ti ti-file-certificate"></i> Assignment Contract
                        </a>
                        <a href="{{ route('RiderInventory.returnContractForm', $rider->id) }}" class="btn btn-outline-warning">
                            <i class="ti ti-arrow-back-up"></i> Return Contract
                        </a>
                        @endif
                        @endcan
                        @can('riderinventory_create')
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
            <div class="col-md-4"><strong>Rider:</strong> {{ $rider->name . ' (' . $rider->rider_id . ')' }}</div>
        </div>
        <div class="card-body table-responsive" id="assignmentTableWrapper">
            @include('rider_inventory.assignment_table', compact('assignments', 'rider'))
        </div>
    </div>
</div>
@endsection
