<table class="table dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr>
            <th>Item</th>
            <th>Customer</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Total</th>
            <th>Assigned</th>
            <th>Assigned By</th>
            <th>Status</th>
            <th>Return / Loss</th>
            <th>Returned To</th>
            <th>Returned to Customer</th>
            <th>IL Voucher</th>
            <th>Remarks</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($assignments as $row)
        <tr>
            <td>{{ $row->inventoryItem->name ?? '-' }}</td>
            <td>
                @if($row->customer_id && $row->customer)
                <a href="{{ route('customer.inventory', $row->customer_id) }}">
                    {{ $row->customer->name }}{{ $row->customer->company_name ? ' — ' . $row->customer->company_name : '' }}
                </a>
                @else
                —
                @endif
            </td>
            <td>{{ (int) ($row->qty ?? 1) }}</td>
            <td>{{ number_format((float) $row->amount, 2) }}</td>
            <td>{{ number_format($row->lineTotal(), 2) }}</td>
            <td>{{ $row->assigned_date?->format('Y-m-d') }}</td>
            <td>{{ $row->assignedByUser->name ?? '-' }}</td>
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
                @if($row->status === 'lost')
                {{ $row->lostByUser->name ?? '-' }}
                @elseif(in_array($row->status, ['returned', 'returned_to_customer'], true))
                {{ $row->returnedByUser->name ?? '-' }}
                @else
                —
                @endif
            </td>
            <td>
                {{ $row->returned_to_customer?->format('Y-m-d') ?? '—' }}
            </td>
            @if($row->voucher_id)
            <td>
                <a href="javascript:void(0);" class=" show-modal"
                    data-action="{{ route('vouchers.show', $row->voucher_id) }}"
                    data-size="xl" data-title="Inventory Loss Voucher">
                    {{ ($row->voucher_id ? 'IL-' : '—' ) . $row->voucher_id }}
                </a>
            </td>
            @else
            <td>—</td>
            @endif
            <td class="small">{{ $row->remarks ?? '-' }}</td>
            <td>
                @if($row->isAssigned())
                <div class="btn-group">
                    @can('riders_inventory_edit')
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
                    @can('riders_inventory_delete')
                    <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="if(confirm('Delete this assignment? It will be moved to the Recycle Bin.')) { document.getElementById('delete-assignment-{{ $row->id }}').submit(); }">
                        Delete
                    </button>
                    <form id="delete-assignment-{{ $row->id }}"
                        action="{{ route('RiderInventory.destroyAssignment', $row->id) }}"
                        method="POST" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endcan
                </div>
                @elseif(in_array($row->status, ['returned', 'lost', 'returned_to_customer'], true))
                @can('riders_inventory_edit')
                <div class="d-flex gap-1 flex-wrap justify-content-center">
                    <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary show-modal"
                        data-action="{{ route('RiderInventory.changeStatusForm', $row->id) }}"
                        data-size="md" data-title="Change Inventory Status">
                        Change Status
                    </a>
                    @if($row->return_contract_number)
                    @php $returnContractId = \App\Models\RiderInventoryContract::where('contract_number', $row->return_contract_number)->value('id'); @endphp
                    @if($returnContractId)
                    <a href="{{ route('RiderInventory.returnContractDocument', $returnContractId) }}"
                        class="btn btn-sm btn-outline-primary" target="_blank">
                        Contract
                    </a>
                    @endif
                    @endif
                </div>
                @endcan
                @else
                —
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="15" class="text-center text-muted py-4">No inventory assignments for this rider.</td>
        </tr>
        @endforelse
    </tbody>
</table>
