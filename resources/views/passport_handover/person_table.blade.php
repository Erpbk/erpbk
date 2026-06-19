<table class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Type</th>
            <th>Name</th>
            <th>ID</th>
            <th>Passport Number</th>
            <th>Current Status</th>
            <th>History</th>
            <th class="text-end">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($persons as $person)
        <tr>
            <td>
                <span class="badge bg-label-{{ $person['type'] === 'rider' ? 'primary' : 'info' }}">
                    {{ ucfirst($person['type']) }}
                </span>
            </td>
            <td>{{ $person['name'] }}</td>
            <td>{{ $person['code'] }}</td>
            <td>{{ $person['passport'] ?: '-' }}</td>
            <td>
                @if($person['current_status'] === 'issued')
                <span class="badge bg-warning">Issued</span>
                @elseif($person['current_status'] === 'returned')
                <span class="badge bg-success">Returned</span>
                @else
                <span class="badge bg-secondary">No History</span>
                @endif
            </td>
            <td>{{ $person['history_count'] }} record(s)</td>
            <td class="text-end">
                <div class="d-flex flex-wrap justify-content-end gap-1">
                    @can('passport_handover_issue')
                    @if(!$person['has_open_issue'])
                    <a href="javascript:void(0);" class="btn btn-sm btn-primary show-modal"
                        data-action="{{ route('passportHandover.issueForm', ['type' => $person['type'], 'id' => $person['id']]) }}"
                        data-size="lg" data-title="Issue Passport">
                        <i class="ti ti-e-passport me-1"></i> Issue
                    </a>
                    @endif
                    @endcan
                    @can('passport_handover_return')
                    @if($person['has_open_issue'])
                    <a href="javascript:void(0);" class="btn btn-sm btn-warning show-modal"
                        data-action="{{ route('passportHandover.returnForm', ['type' => $person['type'], 'id' => $person['id']]) }}"
                        data-size="lg" data-title="Return Passport">
                        <i class="ti ti-arrow-back-up me-1"></i> Return
                    </a>
                    @endif
                    @endcan
                    <a href="{{ route('passportHandover.history', ['type' => $person['type'], 'id' => $person['id']]) }}"
                        class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-history me-1"></i> History
                    </a>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center text-muted py-4">
                No riders or employees found. Use search to find a person, or persons with passport details appear here by default.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
