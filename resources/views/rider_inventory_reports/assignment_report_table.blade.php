<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Rider</th>
                <th>Item</th>
                <th>Amount</th>
                <th>Assigned Date</th>
                <th>Assigned By</th>
                <th>Status</th>
                <th>Return Date</th>
                <th>Returned By</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row->rider->name ?? '-' }} ({{ $row->rider->rider_id ?? $row->rider_id }})</td>
                <td>{{ $row->inventoryItem->name ?? '-' }}</td>
                <td>{{ number_format((float) $row->amount, 2) }}</td>
                <td>{{ $row->assigned_date?->format('Y-m-d') }}</td>
                <td>{{ $row->assignedByUser->name ?? '-' }}</td>
                <td>{{ ucfirst($row->status) }}</td>
                <td>{{ $row->return_date?->format('Y-m-d') ?? '-' }}</td>
                <td>{{ $row->returnedByUser->name ?? '-' }}</td>
                <td>{{ $row->remarks ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
