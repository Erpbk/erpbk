@php
  $activeAttendanceTab = $activeAttendanceTab ?? 'attendance';
  $attendanceUserType = $attendanceUserType
    ?? request('user_type')
    ?? request('ref_type')
    ?? 'employee';
@endphp

<style>
  .attendance-module-tabs .nav-link {
    color: #6b7280;
    font-weight: 600;
  }
  .attendance-module-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
  }
</style>

<div class="content mb-2">
  <ul class="nav nav-tabs attendance-module-tabs" role="tablist">
    <li class="nav-item" role="presentation">
      <a class="nav-link {{ $activeAttendanceTab === 'attendance' ? 'active' : '' }}"
         href="{{ route('attendance.summary', ['user_type' => $attendanceUserType]) }}">
        Attendance
      </a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link {{ $activeAttendanceTab === 'report' ? 'active' : '' }}"
         href="{{ route('attendance.index', ['ref_type' => $attendanceUserType]) }}">
        Attendance Report
      </a>
    </li>
  </ul>
</div>
