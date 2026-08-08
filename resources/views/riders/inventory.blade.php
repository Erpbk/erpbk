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
@php
    $assignedItems = $assignedItems ?? [
        'bike' => null,
        'fuel_card' => null,
        'sim_card' => null,
        'account_balance' => null,
    ];
    $currencySymbol = \App\Helpers\Currency::symbol();
    $otherAssets = [
        [
            'type' => 'Bike',
            'item' => $assignedItems['bike'] ?? null,
            'prefix' => '',
        ],
        [
            'type' => 'Fuel Card',
            'item' => $assignedItems['fuel_card'] ?? null,
            'prefix' => '',
        ],
        [
            'type' => 'Sim Card',
            'item' => $assignedItems['sim_card'] ?? null,
            'prefix' => '',
        ],
        [
            'type' => 'Account Balance',
            'item' => $assignedItems['account_balance'] ?? null,
            'prefix' => $currencySymbol . ' ',
        ],
    ];
@endphp

<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0">Other Assets</h5>
    </div>
    <div class="card-body table-responsive px-2 py-0">
        <table class="table dataTable no-footer mb-0">
            <thead class="text-center">
                <tr>
                    <th>Item</th>
                    <th>Details</th>
                    <th>Assign Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($otherAssets as $asset)
                    @php $item = $asset['item']; @endphp
                    <tr>
                        <td>{{ $asset['type'] }}</td>
                        <td>
                            @if(!empty($item))
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener">
                                    {{ ($asset['prefix'] ?? '') . $item['label'] }}
                                </a>
                                @if(!empty($item['meta']))
                                    <div class="text-muted small">{{ $item['meta'] }}</div>
                                @endif
                            @else
                                <span class="text-muted">Not assigned</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $item['assign_date'] ?? '' }}</td>
                        <td class="text-center">{{ $item['return_date'] ?? '' }}</td>
                        <td class="text-center">
                            @if(!empty($item['status']))
                                {{ $item['status'] }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card card-action mb-1">
    <div class="card-header align-items-center">
        <h5 class="card-action-title mb-0">Rider Inventory</h5>
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
