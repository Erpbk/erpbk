@extends('employees.view')

@section('page_content')

<div class="card card-action mb-6">
  <div class="card-header align-items-center d-flex flex-wrap justify-content-between gap-2">
    <h5 class="card-action-title mb-0">
      <i class="ti ti-calendar-check ti-lg text-body me-2"></i>Attendance
    </h5>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <form method="GET" action="{{ route('employee.attendance', $employee->id) }}" class="d-flex align-items-center gap-2">
        <label for="attendance-month" class="form-label mb-0 text-nowrap small text-muted">Month</label>
        <input type="month" id="attendance-month" name="month" class="form-control form-control-sm"
          value="{{ $month ?? date('Y-m') }}" onchange="this.form.submit()">
      </form>
      @can('employees_attendance_create')
      <a href="javascript:void(0);" class="btn btn-primary btn-sm show-modal"
        data-size="md" data-title="Add Attendance — {{ $employee->name }}"
        data-action="{{ route('attendance.create', ['refType' => 'employee']) }}?ref_id={{ $employee->id }}&date={{ date('Y-m-d') }}">
        <i class="ti ti-plus me-1"></i>Add record
      </a>
      @endcan
    </div>
  </div>

  <div class="card-body pt-3 px-4 px-md-5">
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-4 col-lg">
        <div class="border rounded p-3 text-center h-100">
          <div class="text-muted small">Total records</div>
          <div class="fs-4 fw-semibold">{{ (int) ($summary['total'] ?? 0) }}</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg">
        <div class="border rounded p-3 text-center h-100">
          <div class="text-muted small">Present</div>
          <div class="fs-4 fw-semibold text-success">{{ (int) ($summary['present'] ?? 0) }}</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg">
        <div class="border rounded p-3 text-center h-100">
          <div class="text-muted small">Absent</div>
          <div class="fs-4 fw-semibold text-danger">{{ (int) ($summary['absent'] ?? 0) }}</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg">
        <div class="border rounded p-3 text-center h-100">
          <div class="text-muted small">Late</div>
          <div class="fs-4 fw-semibold text-warning">{{ (int) ($summary['late'] ?? 0) }}</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg">
        <div class="border rounded p-3 text-center h-100">
          <div class="text-muted small">On leave / Weekend</div>
          <div class="fs-4 fw-semibold text-info">{{ (int) (($summary['on_leave'] ?? 0) + ($summary['weekend'] ?? 0)) }}</div>
        </div>
      </div>
    </div>

    @if(!\Illuminate\Support\Facades\Schema::hasTable('attendance'))
    <p class="text-muted mb-0">The attendance table is not available. Run database migrations to enable this feature.</p>
    @elseif($attendances->isEmpty())
    <p class="text-muted mb-0">No attendance records for {{ \Carbon\Carbon::parse(($month ?? date('Y-m')) . '-01')->format('F Y') }}.</p>
    @else
    @include('employees.partials.attendance_table', ['attendances' => $attendances])
    @endif
  </div>
</div>

@endsection
