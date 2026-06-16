<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Rider ID</th>
                <th>Name</th>
                <th>Assigned</th>
                <th>Returned</th>
                <th>Lost</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($riders as $rider)
            @php
            $counts = collect($assignmentCounts[$rider->id] ?? []);
            $assigned = (int) ($counts->firstWhere('status', 'assigned')->total ?? 0);
            $returned = (int) ($counts->firstWhere('status', 'returned')->total ?? 0);
            $lost = (int) ($counts->firstWhere('status', 'lost')->total ?? 0);
            @endphp
            <tr>
                <td>{{ $rider->rider_id ?? $rider->id }}</td>
                <td>{{ $rider->name }}</td>
                <td><span class="badge bg-primary">{{ $assigned }}</span></td>
                <td><span class="badge bg-success">{{ $returned }}</span></td>
                <td><span class="badge bg-danger">{{ $lost }}</span></td>
                <td>
                    <a href="{{ route('RiderInventory.show', $rider->id) }}" class="btn btn-sm btn-primary">
                        <i class="ti ti-package"></i> Manage
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">No riders found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
