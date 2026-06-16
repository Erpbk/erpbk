@if($assignments->isEmpty())
<p class="text-muted mb-0">No returned items are pending return to this customer.</p>
@else
<table class="table table-striped">
    <thead>
        <tr>
            <th style="width: 40px;">
                <input type="checkbox" id="selectAllReturnToCustomer" title="Select all">
            </th>
            <th>Item</th>
            <th>Rider</th>
            <th>Assigned</th>
            <th>Returned</th>
            <th>Amount</th>
            <th>Returned By</th>
        </tr>
    </thead>
    <tbody>
        @foreach($assignments as $row)
        <tr>
            <td>
                <input type="checkbox" name="assignment_ids[]" value="{{ $row->id }}">
            </td>
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
            <td>{{ $row->assigned_date?->format('Y-m-d') ?? '—' }}</td>
            <td>{{ $row->return_date?->format('Y-m-d') ?? '—' }}</td>
            <td>{{ number_format((float) $row->amount, 2) }}</td>
            <td>{{ $row->returnedByUser->name ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
