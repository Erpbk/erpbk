@extends('customers.view')

@section('page_content')
<div class="content">
    @include('flash::message')
    @can('customers_inventory_view')
    <div class="card card-action mb-1">
        <div class="card-header align-items-center">
            <h5 class="card-action-title mb-0">
                <i class="ti ti-package ti-lg text-body me-2"></i>Customer Inventory
            </h5>
            @can('customers_inventory_edit')
            <a href="{{ route('RiderInventory.returnToCustomerForm') }}" class="btn btn-sm btn-info">
                <i class="ti ti-truck-return"></i> Return to Customer
            </a>
            @endcan
        </div>
        <div class="card-body table-responsive px-2">
            <table class="table dataTable no-footer">
                <thead class="text-center">
                    <tr>
                        <th>Item</th>
                        <th>Rider</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Assigned</th>
                        <th>Status</th>
                        <th>Return Date</th>
                        <th>Returned to Customer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $row)
                    <tr>
                        <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                        <td>
                            @if($row->rider)
                            <a href="javascript:void(0);"
                                class="show-modal-right"
                                data-action="{{ route('RiderInventory.show', $row->rider_id) }}"
                                data-size="xl"
                                data-title="{{ $row->rider->name }} inventory">
                                {{ $row->rider->name }} ({{ $row->rider->rider_id ?? $row->rider_id }})
                            </a>
                            @else
                            —
                            @endif
                        </td>
                        <td>{{ (int) ($row->qty ?? 1) }}</td>
                        <td>{{ number_format((float) $row->amount, 2) }}</td>
                        <td>{{ number_format($row->lineTotal(), 2) }}</td>
                        <td>{{ $row->assigned_date?->format('Y-m-d') ?? '—' }}</td>
                        <td>
                            @if($row->status === 'assigned')
                            <span class="badge bg-primary">Assigned</span>
                            @elseif($row->status === 'returned')
                            <span class="badge bg-success">Returned</span>
                            @elseif($row->status === 'returned_to_customer')
                            <span class="badge bg-info">Returned to Customer</span>
                            @else
                            <span class="badge bg-danger">Lost</span>
                            @endif
                        </td>
                        <td>{{ $row->return_date?->format('Y-m-d') ?? '—' }}</td>
                        <td>
                            {{ $row->returned_to_customer?->format('Y-m-d') ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No inventory assignments linked to this vendor.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body">
            <h5>You are not authorized to access this page</h5>
        </div>
    </div>
    @endcan
</div>
@endsection
