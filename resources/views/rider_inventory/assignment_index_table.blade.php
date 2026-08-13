<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead class="text-center">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Assigned</th>
                <th>Returned</th>
                <th>Lost</th>
                <th>Last assigned</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riders as $rider)
            <tr>
                <td class="text-center">{{ $rider->rider_id ?? $rider->id }}</td>
                <td>
                    <a href="javascript:void(0);"
                        class="show-modal-right"
                        data-action="{{ route('RiderInventory.show', $rider->id) }}"
                        data-size="xl"
                        data-title="{{ $rider->name }} inventory">
                        {{ $rider->name }}
                    </a>
                </td>
                <td class="text-center"><span class="badge bg-primary">{{ (int) ($rider->assigned_count ?? 0) }}</span></td>
                <td class="text-center"><span class="badge bg-success">{{ (int) ($rider->returned_count ?? 0) }}</span></td>
                <td class="text-center"><span class="badge bg-danger">{{ (int) ($rider->lost_count ?? 0) }}</span></td>
                <td class="text-center">
                    {{ $rider->last_assigned_date ? \Carbon\Carbon::parse($rider->last_assigned_date)->format('Y-m-d') : '—' }}
                </td>
                <td class="text-center">
                    @can('riders_inventory_view')
                    <a href="{{ route('RiderInventory.show', $rider->id) }}" class="btn btn-sm btn-primary">
                        <i class="ti ti-package"></i> Manage
                    </a>
                    @endcan
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">No riders with inventory assignments found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
