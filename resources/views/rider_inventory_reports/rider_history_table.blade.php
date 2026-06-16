@if($rider)
<div class="mb-3"><strong>Rider:</strong> {{ $rider->rider_id }} — {{ $rider->name }}</div>
@endif
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Item</th>
                <th>Amount</th>
                <th>Assigned Date</th>
                <th>Assigned By</th>
                <th>Status</th>
                <th>Return / Loss Date</th>
                <th>Returned By</th>
                <th>Remarks</th>
                <th>Voucher</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row->inventoryItem->name ?? '-' }}</td>
                <td>{{ number_format((float) $row->amount, 2) }}</td>
                <td>{{ $row->assigned_date?->format('Y-m-d') }}</td>
                <td>{{ $row->assignedByUser->name ?? '-' }}</td>
                <td>{{ ucfirst($row->status) }}</td>
                <td>{{ $row->return_date?->format('Y-m-d') ?? '-' }}</td>
                <td>{{ $row->returnedByUser->name ?? '-' }}</td>
                <td>{{ $row->remarks ?? '-' }}</td>
                <td>
                    @if($row->voucher_id)
                    <a href="javascript:void(0);" class="show-modal" data-action="{{ route('vouchers.show', $row->voucher_id) }}" data-size="xl" data-title="Voucher">View</a>
                    @else
                    —
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No history found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
