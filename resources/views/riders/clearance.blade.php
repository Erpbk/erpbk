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
    ];
    $customerInventoryRows = $customerInventoryRows ?? [];
    $assignedAssets = [
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
    ];
    foreach ($customerInventoryRows as $inventoryRow) {
        $assignedAssets[] = $inventoryRow;
    }
@endphp

<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">Assigned Assets</h5>
        @isset($rider)
        <a href="{{ route('RiderInventory.clearanceCertificate', $rider->id) }}"
            class="btn btn-sm btn-success"
            target="_blank"
            style="padding: 2px 8px; font-size: 0.75rem;">
            <i class="ti ti-certificate"></i> Clearance Certificate
        </a>
        @endisset
    </div>
    <div class="card-body table-responsive px-2 py-0">
        @if(!empty($lastCustomerName))
        <div class="px-2 pt-2 pb-1 text-muted small">
            Last customer: <strong>{{ $lastCustomerName }}</strong>
        </div>
        @endif
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
                @foreach($assignedAssets as $asset)
                    @php $item = $asset['item']; @endphp
                    <tr>
                        <td>{{ $asset['type'] }}</td>
                        <td>
                            @if(!empty($item))
                                @if(!empty($item['url']))
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener">
                                    {{ ($asset['prefix'] ?? '') . $item['label'] }}
                                </a>
                                @else
                                {{ ($asset['prefix'] ?? '') . $item['label'] }}
                                @endif
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
@else
<div class="card">
    <div class="card-body">
        <h5 class="card-title">You are not authorized to access this page</h5>
    </div>
</div>
@endcan
@endsection
