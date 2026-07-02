<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Voucher No.</th>
                <th>Date</th>
                <th>Rider Name</th>
                <th>Inventory Item</th>
                <th>Amount</th>
                <th>Remarks</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $voucher)
            @php $assignment = $assignments[$voucher->ref_id] ?? null; @endphp
            <tr>
                <td>{{ $voucher->trans_code ?? $voucher->id }}</td>
                <td>{{ $voucher->trans_date }}</td>
                <td>{{ $assignment?->rider?->name ?? '-' }}</td>
                <td>{{ $assignment?->inventoryItem?->name ?? '-' }}</td>
                <td>{{ number_format((float) $voucher->amount, 2) }}</td>
                <td>{{ $voucher->remarks ?? '-' }}</td>
                <td>
                    @can('riderinventory_view')
                    <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary show-modal"
                        data-action="{{ route('vouchers.show', $voucher->id) }}"
                        data-size="xl" data-title="Inventory Loss Voucher">
                        View
                    </a>
                    @endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No inventory loss vouchers found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
