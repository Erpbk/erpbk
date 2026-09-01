@extends('layouts.app')

@section('title', 'Attendance - ' . $date->format('F Y'))

@section('content')
@canany(['employees_attendance_view', 'riders_attendance_view'])
<style>
    .attendance-summary-card {
        border-radius: 16px;
        overflow: visible;
    }

    .summary-toolbar {
        background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
        border-bottom: 1px solid #e5ecf6;
    }

    .mode-toggle .btn {
        min-width: 108px;
    }

    .status-chip {
        font-size: 0.78rem;
        letter-spacing: 0.01em;
    }

    .attendance-table thead th {
        background: #e8f2ff;
    }

    .attendance-table td,
    .attendance-table th {
        vertical-align: middle;
    }

    .ontime-percent-value {
        font-weight: 600;
    }

    .ontime-percent-symbol {
        color: #9ca3af;
        font-weight: 400;
    }
</style>

@include('attendance.partials.tabs', [
    'activeAttendanceTab' => 'attendance',
    'attendanceUserType' => $userType ?? request('user_type', 'employee'),
])

<div class="container-fluid m-0 p-0">
    <!-- Summary Table -->
    <div class="card shadow-sm border-0 attendance-summary-card">

        <div class="card-body summary-toolbar">
            @include('attendance.partials.filter_sidebar', [
                'filterAction' => route('attendance.summary'),
                'resetUrl' => route('attendance.summary', ['user_type' => $userType]),
                'typeName' => 'user_type',
                'userName' => 'user_id',
                'selectedType' => $userType,
                'selectedUser' => $usersId,
                'selectedStatus' => $statusFilter ?? request('status'),
                'selectedDate' => $filterDate ?? request('filter_date'),
                'fromDate' => $fromDate ?? request('from_date'),
                'toDate' => $toDate ?? request('to_date'),
                'dateFieldName' => 'filter_date',
                'allowAllTypes' => false,
                'showMonth' => true,
                'monthValue' => $date->format('Y-m'),
                'projects' => $projects,
                'projectId' => $projectId,
                'fleetSupervisors' => $fleetSupervisors,
                'fleetSupervisor' => $fleetSupervisor,
                'hiddenFields' => [
                    'view_mode' => $isCustomRange ? 'month' : $viewMode,
                    'view_start' => $isCustomRange ? 1 : $viewStart,
                ],
                'exportUrl' => route('attendance.summary.export', array_filter([
                    'date' => $date->format('Y-m-d'),
                    'user_type' => $userType,
                    'user_id' => $usersId,
                    'project_id' => $projectId,
                    'fleet_supervisor' => $fleetSupervisor,
                    'status' => $statusFilter,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'filter_date' => $filterDate ?? null,
                ])),
                'exportLabel' => 'Export',
            ])
        </div>
        {{-- <div class="totals-cards mt-3">
            <div class="total-card total-green">
                <div class="label"><i class="fa fa-check-circle"></i>Present Rate</div>
                <div class="value">{{ $presentRate }}%
    </div>
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
    <div class="label"><i class="fa fa-user-secret"></i>Weekends Marked</div>
    <div class="value"><i class="fas fa-umbrella-beach " style="color: #17a2b8;"></i></div>
    <div class="value"><small>{{ $summary['total_weekend'] .'/'. $totalAttendances }}</small></div>
</div>
</div> --}}

@php
$summaryQueryBase = array_filter([
    'date' => $date->format('Y-m-d'),
    'user_type' => $userType,
    'user_id' => $usersId,
    'project_id' => $projectId ?: null,
    'fleet_supervisor' => $fleetSupervisor ?: null,
    'status' => $statusFilter ?: null,
], fn ($value) => $value !== null && $value !== '');
$summaryQuery = http_build_query($summaryQueryBase);
$summaryRangeQuery = http_build_query(array_filter($summaryQueryBase + [
    'from_date' => $fromDate ?: null,
    'to_date' => $toDate ?: null,
    'filter_date' => ($filterDate ?? null) ?: null,
], fn ($value) => $value !== null && $value !== ''));
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 m-3 p-2">
    <div class="btn-group mode-toggle" role="group" aria-label="Summary view modes">
        <a href="{{ route('attendance.summary') }}?{{ $summaryQuery }}&view_mode=week&view_start=1"
            class="btn {{ $viewMode === 'week' ? 'btn-primary' : 'btn-outline-primary' }}">
            <i class="fas fa-calendar-week me-1"></i>1 Week
        </a>
        <a href="{{ route('attendance.summary') }}?{{ $summaryQuery }}&view_mode=ten_days&view_start=1"
            class="btn {{ $viewMode === 'ten_days' ? 'btn-primary' : 'btn-outline-primary' }}">
            <i class="fas fa-calendar-day me-1"></i>10 Days
        </a>
        <a href="{{ route('attendance.summary') }}?{{ $summaryQuery }}&view_mode=month&view_start=1"
            class="btn {{ $viewMode === 'month' ? 'btn-primary' : 'btn-outline-primary' }}">
            <i class="fas fa-calendar-alt me-1"></i>Full Month
        </a>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('attendance.summary') }}?{{ $summaryRangeQuery }}&view_mode={{ $viewMode }}&view_start={{ $prevStart }}"
            class="btn btn-outline-secondary {{ !$hasPrevWindow ? 'disabled' : '' }}"
            aria-disabled="{{ !$hasPrevWindow ? 'true' : 'false' }}">
            <i class="fas fa-chevron-left"></i>
        </a>
        <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
            @if($isCustomRange && count($days) > 0)
            {{ \Carbon\Carbon::parse($days[0]['date'])->format('d M') }} - {{ \Carbon\Carbon::parse($days[count($days) - 1]['date'])->format('d M') }}
            @else
            {{ $days[0]['number'] ?? 0 }} - {{ end($days)['number'] ?? 0 }} of {{ $monthTotalDays }} Days
            @endif
        </span>
        <a href="{{ route('attendance.summary') }}?{{ $summaryRangeQuery }}&view_mode={{ $viewMode }}&view_start={{ $nextStart }}"
            class="btn btn-outline-secondary {{ !$hasNextWindow ? 'disabled' : '' }}"
            aria-disabled="{{ !$hasNextWindow ? 'true' : 'false' }}">
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</div>


<div class="card-body p-2">
    <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
        <table class="table attendance-table mb-0">
            <thead class="table-info" style="position: sticky; top: 0; z-index: 10;">
                <tr>
                    <th class="text-start" style="min-width: 200px; color: black !important;">Name</th>

                    <th class="text-center" style=" color: black !important;">Total <br> Present</th>

                    @foreach($days as $day)
                    <th class="text-center  {{ $day['is_today'] ? 'bg-info' : '' }}" style="min-width: 50px; color: black !important;">
                        <div class="fw-bold">{{ $day['number'] }}</div>
                        <small class="  {{ $day['is_weekend'] ? 'text-danger' : 'text-primary' }}">{{ $day['day_name'] }}</small>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td class="text-start">
                        <div>
                            <span class="fw-semibold d-flex align-items-center gap-2 flex-wrap">
                                <a target="_blank" href="@if($user->type === 'employee'){{ route('employees.show', $user->id) }}@elseif($user->type === 'rider'){{ route('riders.show', $user->id) }}@endif">{{ $user->name }}</a>
                                @if($user->type === 'rider')
                                @include('riders._status_badges', [
                                    'employmentStatus' => $user->status,
                                    'rider' => $user,
                                    'wrapperClass' => 'align-items-start',
                                ])
                                @elseif($user->type === 'employee')
                                @include('employees._status_badges', ['status' => $user->status])
                                @endif
                            </span>
                            <br><span class="text-muted" style="white-space: nowrap;">{{ ($user->designation ?? '') . '-' . $user->branch?->name}}</span>

                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <strong>{{ $user->total_present }}</strong>
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
                    $statusText = "Present";
                    break;
                    case 'absent':
                    $badgeClass = 'bg-danger';
                    $statusText = 'Absent';
                    break;
                    case 'late':
                    $badgeClass = 'bg-warning text-dark';
                    $statusText = 'Late';
                    break;
                    case 'half day':
                    $badgeClass = 'bg-info';
                    $statusText = 'Half Day';
                    break;
                    case 'weekend':
                    $badgeClass = 'bg-secondary';
                    $statusText = 'Weekend';
                    break;
                    case 'on leave':
                    $badgeClass = 'bg-dark';
                    $statusText = 'On Leave';
                    break;
                    }
                    } else {
                    $badgeClass = 'bg-light text-black border';
                    $statusText = '+';
                    }
                    $ontimeDisplay = \App\Services\Attendance\RiderAttendanceActivitySync::formatOntimePercentageDisplay(
                    $attendance['ontime_orders_percentage'] ?? null
                    );
                    @endphp

                    <td class="text-center align-middle p-1">
                        <a href="javascript:void(0)" class="show-modal"
                            @if( $attendance && $attendance['exists'] ) data-action="{{ route('attendance.edit', App\Models\Attendance::find($attendance['id'])) }}" data-title="Edit Attendance ( {{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }} )"
                            @else data-action="{{ route('attendance.create', $user->type) }}?ref_type={{ $user->type }}&ref_id={{ $user->id }}&date={{ $day['date'] }}" data-title="Mark Attendance ( {{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }} )"
                            @endif>
                            @if($attendance && $attendance['exists'])
                            <span class="badge {{ $badgeClass }} rounded px-3 py-2"
                                style="cursor: pointer; min-width: 115px; min-height: 55px; font-size: 13px;"
                                data-bs-toggle="tooltip"
                                title="{{ 
                                                    $attendance && $attendance['exists'] ? (
                                                        ($attendance['check_in'] ? 'In: ' . $attendance['check_in'] : '' )
                                                        . ($attendance['check_out'] ? ' | Out: ' . $attendance['check_out'] :'' )
                                                        . ((!$attendance['check_in'] && !$attendance['check_out']) ? 'Click to edit' : '')
                                                        . ($ontimeDisplay !== null ? ' | Ontime: ' . $ontimeDisplay . '%' : '')
                                                        . ($attendance['notes'] ? ' Note: ' . $attendance['notes'] : '')
                                                        ) 
                                                    : 'Click to mark attendance' }}">
                                @if($statusText === 'Present')
                                @if($attendance['check_in'] && $attendance['check_out'])
                                {{ $attendance['check_in'] }} - {{ $attendance['check_out'] }}
                                @elseif($attendance['check_in'])
                                {{ $attendance['check_in'] }}
                                @elseif($attendance['check_out'])
                                {{ $attendance['check_out'] }}
                                @else
                                Present
                                @endif
                                @if($ontimeDisplay !== null)
                                <br><small><span class="ontime-percent-value">{{ $ontimeDisplay }}</span><span class="ontime-percent-symbol text-white">%</span></small>
                                @endif
                                @else
                                {{ $statusText }}
                                @if ($attendance['exists'] && !($statusText === 'Absent') && !($statusText === 'On Leave') && !($statusText === 'Weekend') )
                                <br>{{ 'In: '.($attendance['check_in'] ?? '') }}<br>{{ 'Out: '.($attendance['check_out'] ?? '') }}
                                @endif
                                @endif
                            </span>
                            @else
                            <span class="badge {{ $badgeClass }} rounded px-3 py-2"
                                style="cursor: pointer; min-width: 115px;height: 55px; font-size: 37px;"
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
                            @endif

                        </a>
                    </td>
                    @endforeach
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
@endcanany  
@if(!auth()->user()->canany(['employees_attendance_view', 'riders_attendance_view']))
<div class="alert alert-danger mt-4" role="alert">
    You do not have permission to view attendance records.
</div>
@endif


@endsection

@section('page-script')
<script>
    const prevWindowUrl = "{{ route('attendance.summary') . '?' . $summaryRangeQuery . '&view_mode=' . $viewMode . '&view_start=' . $prevStart }}";
    const nextWindowUrl = "{{ route('attendance.summary') . '?' . $summaryRangeQuery . '&view_mode=' . $viewMode . '&view_start=' . $nextStart }}";

    $(document).ready(function() {
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // Keyboard navigation
    $(document).keydown(function(e) {
        if (e.ctrlKey && e.key === 'ArrowLeft') {
            window.location.href = prevWindowUrl;
        } else if (e.ctrlKey && e.key === 'ArrowRight') {
            window.location.href = nextWindowUrl;
        }
    });
</script>
@endsection