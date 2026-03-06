@extends('layouts.app')

@section('title', 'Attendance Summary - ' . $date->format('F Y'))

@section('content')
@can('attendance_view')
<div class="container-fluid m-0 p-0">
    <!-- Header Section with Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-4">
        <h3 class="mb-0">
            <i class="fas fa-calendar-check text-primary me-2"></i>
            Attendance Summary
        </h3>
    </div>

    <!-- Summary Table -->
    <div class="card shadow-sm border-0">
        
        <div class="card-body">
            <form method="GET" action="{{ route('attendance.summary') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-calendar me-1"></i>Month
                    </label>
                    <input type="month" name="date" class="form-control" 
                           value="{{ $date->format('Y-m') }}" 
                           onchange="this.form.submit()">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-users me-1"></i>User Type
                    </label>
                    <select name="user_type" class="form-select" onchange="loadUsers(this.value)">
                        <option value="employee" {{ $userType == 'employee' ? 'selected' : '' }}>Employees Only</option>
                        <option value="rider" {{ $userType == 'rider' ? 'selected' : '' }}>Riders Only</option>
                    </select>
                </div>
                <!-- User Selection -->
                <div class="col-md-3">
                    <label for="ref_id" class="form-label fw-semibold required">
                        <i class="fas fa-user me-1"></i>Select User
                    </label>
                    <select class="form-select select2" onchange="this.form.submit()"
                            id="user_id" name="user_id" required>
                    </select>
                </div>
                
                <div class="col-md-3 text-end">
                    <label class="form-label fw-semibold">&nbsp;</label>
                    <div>
                        <a href="{{ route('attendance.summary.export') }}?date={{ $date->format('Y-m-d') }}&user_type={{ $userType }}" 
                           class="btn btn-success" target="_blank">
                            <i class="fas fa-file-excel me-2"></i>Export to Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <div class="totals-cards mt-3">
            <div class="total-card total-green">
                <div class="label"><i class="fa fa-check-circle"></i>Present Rate</div>
                <div class="value">{{ $presentRate }}%</div>
                <div class="value"><small>{{ $summary['total_present'] .'/'. $totalAttendances }}</small></div>
            </div>
            <div class="total-card total-red">
                <div class="label"><i class="fa fa-times-circle"></i>Absent Rate</div>
                <div class="value">{{ $absentRate }}%</div>
                <div class="value"><small>{{ $summary['total_absent'] .'/'. $totalAttendances }}</small></div>
            </div>
            <div class="total-card total-black">
                <div class="label"><i class="fa fa-motorcycle"></i>Unmark Rate</div>
                <div class="value">{{ $unmarkRate }}%</div>
                <div class="value"><small>{{ $summary['total_unmarked'] .'/'. $totalAttendances }}</small></div>
            </div>
            <div class="total-card total-blue">
                <div class="label"><i class="fa fa-motorcycle"></i>Late Arrival</div>
                <div class="value"><i class="fas fa-clock text-primary"></i></div>
                <div class="value"><small>{{ $summary['total_late'] .'/'. $totalAttendances }}</small></div>
            </div>
            <div class="total-card total-1">
                <div class="label"><i class="fa fa-building"></i>On leave</div>
                <div class="value"><i class="fa fa-head-side-cough " style="color: #c142bb;"></i></div>
                <div class="value"><small>{{ $summary['total_leave'] .'/'. $totalAttendances }}</small></div>
            </div>
            <div class="total-card total-2">
                <div class="label"><i class="fa fa-building"></i>Half Day</div>
                <div class="value"><i class="fas fa-adjust " style="color: #6f42c1;"></i></div>
                <div class="value"><small>{{ $summary['total_halfday'] .'/'. $totalAttendances }}</small></div>
            </div>
            <div class="total-card total-3">
                <div class="label"><i class="fa fa-user-secret"></i>Holidays Marked</div>
                <div class="value"><i class="fas fa-umbrella-beach " style="color: #17a2b8;"></i></div>
                <div class="value"><small>{{ $summary['total_holiday'] .'/'. $totalAttendances }}</small></div>
            </div>
        </div>
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex justify-content-between gap-2">
                    <h5 class="mb-0">
                        {{ $date->format('F Y') }}
                    </h5>
                    <div class="btn-group btn-group-sm shadow-sm">
                        <a href="{{ route('attendance.summary', ['date' => $prevMonth, 'user_type' => $userType]) }}" 
                        class="btn btn-outline-primary" title="Previous Month">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <a href="{{ route('attendance.summary', ['date' => now()->format('Y-m-d'), 'user_type' => $userType]) }}" 
                        class="btn btn-primary">
                            <i class="fas fa-calendar-alt me-1"></i>Current
                        </a>
                        <a href="{{ route('attendance.summary', ['date' => $nextMonth, 'user_type' => $userType]) }}" 
                        class="btn btn-outline-primary" title="Next Month">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <span class="badge bg-primary rounded-pill px-3 py-2">
                    {{ $users->count() }} Users • {{ $totalDays }} Days
                </span>
            </div>
        </div>

        <!-- Legend -->
        <div class="d-flex flex-wrap gap-2 m-3 p-2">
            <span class="badge bg-success px-3 py-2 rounded-pill">
                <i class="fas fa-check-circle me-1"></i>P - Present
            </span>
            <span class="badge bg-danger px-3 py-2 rounded-pill">
                <i class="fas fa-times-circle me-1"></i>A - Absent
            </span>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                <i class="fas fa-clock me-1"></i>L - Late
            </span>
            <span class="badge bg-info px-3 py-2 rounded-pill">
                <i class="fas fa-adjust me-1"></i>HD - Half Day
            </span>
            <span class="badge bg-secondary px-3 py-2 rounded-pill">
                <i class="fas fa-umbrella-beach me-1"></i>H - Holiday
            </span>
            <span class="badge bg-dark px-3 py-2 rounded-pill">
                <i class="fas fa-plane-departure me-1"></i>OL - On Leave
            </span>
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                <i class="fas fa-minus-circle me-1"></i>- No Record
            </span>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                <table class="table mb-0">
                    <thead class="table-info" style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th class="text-center" style="width: 50px; color: black !important;">ID</th>
                            <th style="min-width: 200px; color: black !important;">Name</th>
                            <th style="width: 80px; color: black !important;" class="text-center">Type</th>
                            
                            @foreach($days as $day)
                                <th class="text-center  {{ $day['is_today'] ? 'bg-info' : '' }}" style="min-width: 50px; color: black !important;">
                                    <div class="fw-bold">{{ $day['number'] }}</div>
                                    <small class="  {{ $day['is_weekend'] ? 'text-danger' : 'text-primary' }}">{{ $day['day_name'] }}</small>
                                </th>
                            @endforeach
                            
                            <th class="text-center" style=" color: black !important;">Total Present</th>
                            <th class="text-center" style="min-width: 80px; color: black !important;">History</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td style="white-space: nowrap;" class="text-center align-middle">{{ $user->employee_id ?? $user->rider_id }}</td>
                                <td class="text-start">
                                    <div>
                                        <span class="fw-semibold">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge {{ $user->type == 'employee' ? 'bg-primary' : 'bg-success' }} rounded-pill">
                                        {{ $user->type_label }}
                                    </span>
                                </td>
                                
                                @foreach($days as $day)
                                    @php
                                        $attendance = $user->attendance_data[$day['date']] ?? null;
                                        $badgeClass = '';
                                        $statusText = '';
                                        
                                        if ($attendance && $attendance['exists']) {
                                            switch($attendance['status']) {
                                                case 'present':
                                                    $badgeClass = 'bg-success';
                                                    $statusText = 'P';
                                                    break;
                                                case 'absent':
                                                    $badgeClass = 'bg-danger';
                                                    $statusText = 'A';
                                                    break;
                                                case 'late':
                                                    $badgeClass = 'bg-warning text-dark';
                                                    $statusText = 'L';
                                                    break;
                                                case 'half day':
                                                    $badgeClass = 'bg-info';
                                                    $statusText = 'HD';
                                                    break;
                                                case 'holiday':
                                                    $badgeClass = 'bg-secondary';
                                                    $statusText = 'H';
                                                    break;
                                                case 'on leave':
                                                    $badgeClass = 'bg-dark';
                                                    $statusText = 'OL';
                                                    break;
                                            }
                                        } else {
                                            $badgeClass = 'bg-light text-black border';
                                            $statusText = '-';
                                        }
                                    @endphp
                                    
                                    <td class="text-center align-middle p-1">
                                        <a href="javascript:void(0)" class="show-modal"
                                         @if( $attendance && $attendance['exists'] ) data-action="{{ route('attendance.edit', App\Models\Attendance::find($attendance['id'])) }}" data-title="Edit Attendance ( {{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }} )"
                                         @else data-action="{{ route('attendance.create') }}?ref_type={{ $user->type }}&ref_id={{ $user->id }}&date={{ $day['date'] }}" data-title="Mark Attendance ( {{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }} )"
                                         @endif>
                                            <span class="badge {{ $badgeClass }} rounded-pill px-3 py-2" 
                                                style="cursor: pointer; min-width: 45px;"
                                                data-bs-toggle="tooltip" 
                                                title="{{ 
                                                    $attendance && $attendance['exists'] ? (
                                                        ($attendance['check_in'] ? 'In: ' . $attendance['check_in'] : '' )
                                                        . ($attendance['check_out'] ? ' | Out: ' . $attendance['check_out'] :'' )
                                                        . ((!$attendance['check_in'] && !$attendance['check_out']) ? 'Click to edit' : '')
                                                        . ($attendance['notes'] ? ' Note: ' . $attendance['notes'] : '')
                                                        ) 
                                                    : 'Click to mark attendance' }}">
                                                {{ $statusText }}
                                            </span>
                                        </a>
                                    </td>
                                @endforeach
                                
                                <td class="text-center align-middle">
                                    <strong>{{ $user->total_present }}</strong>
                                </td>
                                
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="showUserHistory({{ $user->id }}, '{{ $user->type }}')"
                                            data-bs-toggle="tooltip" title="View History">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($days) + 4 }}" class="text-center py-5">
                                    <img src="{{ asset('images/no-data.svg') }}" alt="No data" style="width: 120px; opacity: 0.5;" class="mb-3">
                                    <h5 class="text-muted">No users found</h5>
                                    <p class="text-muted mb-0">Try changing your filter criteria</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Click on any day cell to mark or edit attendance
                </small>
            </div>
        </div>
    </div>
</div>

<!-- User History Modal -->
<div class="modal fade" id="userHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-history me-2"></i>Attendance History
                </h5>
                <button type="button" class="btn-close btn-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userHistoryBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-info" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading history...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@cannot('attendance_view')
    <div class="alert alert-danger" role="alert">
        You do not have permission to view attendance records.
    </div>
@endcannot


@endsection

@section('page-script')
<script>
$(document).ready(function() {
    loadUsers('{{ $userType }}');
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Show user history
function showUserHistory(userId, userType) {
    const modal = new bootstrap.Modal(document.getElementById('userHistoryModal'));
    
    $.get(`/attendance/user/${userId}/history`, {
        type: userType
    }, function(data) {
        $('#userHistoryBody').html(data);
    }).fail(function() {
        $('#userHistoryBody').html(`
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No history found for this user.
            </div>
        `);
    });
    
    modal.show();
}

function loadUsers(refType) {
    var select = $('#user_id');
    select.html('<option value="">Loading users...</option>').prop('disabled', true);
    
    if (refType) {
        $.ajax({
            url: '{{ route("attendance.users", "refType") }}'.replace("refType", refType),
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                var userId = '{{ $usersId }}';
                console.log('userId from PHP:', userId, 'Type:', typeof userId);
                select.html('<option value="">-- Select User --</option><option value="all"' + (userId == 'all' ? 'selected' : '') + '>All</option>');
                $.each(data, function(index, user) {
                    var selected = (userId == user.id) ? 'selected' : '';
                    select.append('<option value="' + user.id + '"' + selected + '>' + user.name + '</option>');
                });
                select.prop('disabled', false);
            },
            error: function() {
                select.html('<option value="">Error loading users</option>');
                alert('Failed to load users. Please try again.');
            }
        });
    } else {
        select.html('<option value="">-- Select user type first --</option>').prop('disabled', true);
    }
}

// Keyboard navigation
$(document).keydown(function(e) {
    if (e.ctrlKey && e.key === 'ArrowLeft') {
        window.location.href = '{{ route("attendance.summary")}}?date={{ $prevMonth }}&user_type={{ $userType }}';
    } else if (e.ctrlKey && e.key === 'ArrowRight') {
        window.location.href = '{{ route("attendance.summary")}}?date={{ $nextMonth }}&user_type={{ $userType }}';
    }
});
</script>
@endsection