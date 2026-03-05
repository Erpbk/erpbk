<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">
        <i class="fas fa-user me-2"></i>{{ $user->name ?? 'User' }}
        <span class="badge {{ $userType == 'employee' ? 'bg-primary' : 'bg-success' }} ms-2">
            {{ ucfirst($userType) }}
        </span>
    </h6>
    <small class="text-muted">Last {{ $months }} months</small>
</div>

@if($attendances->count() > 0)
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->date->format('d M Y') }}</td>
                        <td>
                            @php
                                $badgeClass = match($attendance->status) {
                                    'present' => 'bg-success',
                                    'absent' => 'bg-danger',
                                    'late' => 'bg-warning',
                                    'half-day' => 'bg-info',
                                    'holiday' => 'bg-secondary',
                                    default => 'bg-secondary'
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">
                                {{ ucfirst($attendance->status) }}
                            </span>
                        </td>
                        <td>{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '-' }}</td>
                        <td>{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '-' }}</td>
                        <td>{{ $attendance->notes ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-4">
        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
        <p class="text-muted">No attendance records found for the last {{ $months }} months.</p>
    </div>
@endif