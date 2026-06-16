<div class="table-responsive">
    <table class="table table-striped">
        <thead class="text-center">
            <tr>
                <th>Item</th>
                <th>Rider</th>
                <th>Customer</th>
                <th>Price</th>
                <th>Assigned</th>
                <th>Status</th>
                <th>Return / Loss</th>
                <th>Returned to Customer</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assignments as $row)
            <tr>
                <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                <td>
                    @if($row->rider)
                    <a href="{{ route('rider.inventory', $row->rider_id) }}">
                        {{ $row->rider->name }} ({{ $row->rider->rider_id ?? $row->rider_id }})
                    </a>
                    @else
                    —
                    @endif
                </td>
                <td>
                    @if($row->customer_id && $row->customer)
                    <a href="{{ route('customer.inventory', $row->customer_id) }}">
                        {{ $row->customer->name }}{{ $row->customer->company_name ? ' — ' . $row->customer->company_name : '' }}
                    </a>
                    @else
                    —
                    @endif
                </td>
                <td>{{ number_format((float) $row->amount, 2) }}</td>
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
                <td>
                    @if($row->return_date)
                    {{ $row->return_date->format('Y-m-d') }}
                    @elseif($row->loss_date)
                    {{ $row->loss_date->format('Y-m-d') }}
                    @else
                    —
                    @endif
                </td>
                <td>
                    {{ $row->returned_to_customer?->format('Y-m-d') ?? '—' }}
                </td>
                <td>
                    <a href="{{ route('RiderInventory.show', $row->rider_id) }}" class="btn btn-sm btn-primary">
                        <i class="ti ti-package"></i> Manage
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center text-muted py-4">No inventory assignments found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
