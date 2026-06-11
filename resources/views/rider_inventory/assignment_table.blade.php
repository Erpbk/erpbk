<table class="table table-striped table-sm">
    <thead>
        <tr>
            <th>Item</th>
            <th>Price</th>
            <th>Assigned</th>
            <th>Assigned By</th>
            <th>Status</th>
            <th>Return / Loss</th>
            <th>Returned / Lost By</th>
            <th>Contract Ref</th>
            <th>IL Voucher</th>
            <th>Remarks</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($assignments as $row)
        <tr>
            <td>{{ $row->inventoryItem->name ?? '-' }}</td>
            <td>{{ number_format((float) $row->amount, 2) }}</td>
            <td>{{ $row->assigned_date?->format('Y-m-d') }}</td>
            <td>{{ $row->assignedByUser->name ?? '-' }}</td>
            <td>
                @if($row->status === 'assigned')
                <span class="badge bg-primary">Assigned</span>
                @elseif($row->status === 'returned')
                <span class="badge bg-success">Returned</span>
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
                @if($row->status === 'lost')
                {{ $row->lostByUser->name ?? '-' }}
                @elseif($row->status === 'returned')
                {{ $row->returnedByUser->name ?? '-' }}
                @else
                —
                @endif
            </td>
            <td class="small">
                @if($row->assignment_contract_number)
                <div>A: {{ $row->assignment_contract_number }}</div>
                @endif
                @if($row->return_contract_number)
                <div>R: {{ $row->return_contract_number }}</div>
                @endif
                @if(!$row->assignment_contract_number && !$row->return_contract_number)—@endif
            </td>
            <td>{{ $row->il_voucher_number ?? ($row->voucher_id ? ($row->trans_code ?? 'IL') : '—') }}</td>
            <td>{{ $row->remarks ?? '-' }}</td>
            <td>
                @if($row->isAssigned())
                <div class="btn-group">
                    @can('riderinventory_edit')
                    <a href="javascript:void(0);" class="btn btn-sm btn-warning show-modal"
                        data-action="{{ route('RiderInventory.returnForm', $row->id) }}"
                        data-size="md" data-title="Return Inventory Item">
                        Return
                    </a>
                    <a href="javascript:void(0);" class="btn btn-sm btn-danger show-modal"
                        data-action="{{ route('RiderInventory.lostForm', $row->id) }}"
                        data-size="md" data-title="Mark as Lost">
                        Lost
                    </a>
                    @endcan
                </div>
                @elseif($row->status === 'lost' && $row->voucher_id)
                <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary show-modal"
                    data-action="{{ route('vouchers.show', $row->voucher_id) }}"
                    data-size="xl" data-title="Inventory Loss Voucher">
                    Voucher
                </a>
                @elseif($row->return_contract_number)
                @php $returnContractId = \App\Models\RiderInventoryContract::where('contract_number', $row->return_contract_number)->value('id'); @endphp
                @if($returnContractId)
                <a href="{{ route('RiderInventory.returnContractDocument', $returnContractId) }}"
                    class="btn btn-sm btn-outline-primary" target="_blank">
                    Contract
                </a>
                @endif
                @else
                —
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" class="text-center text-muted py-4">No inventory assignments for this rider.</td>
        </tr>
        @endforelse
    </tbody>
</table>
