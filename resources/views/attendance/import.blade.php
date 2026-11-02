@extends('layouts.app')
@section('title', 'Import Attendance')
@section('content')
@canany(['employees_attendance_create', 'riders_attendance_create'])
<div class="container-fluid py-3">
  @include('flash::message')
  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Import Attendance</h5>
      <a href="{{ route('attendance.import.template', ['ref_type' => $refType]) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-download me-1"></i> Download CSV template
      </a>
    </div>
    <div class="card-body">
      <p class="text-muted">
        Upload a CSV with columns: <strong>Employee Name</strong>, <strong>Date</strong>, <strong>Status</strong>, <strong>Check In</strong>, <strong>Check Out</strong>.
        Status values: Present, Late, Absent, Leave, Half day, Weekend.
      </p>
      <form action="{{ route('attendance.import') }}" method="POST" enctype="multipart/form-data" class="row g-3">
        @csrf
        <input type="hidden" name="ref_type" value="{{ $refType }}">
        <div class="col-md-6">
          <label class="form-label" for="file">CSV file</label>
          <input type="file" name="file" id="file" class="form-control" accept=".csv,text/csv" required>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Import records</button>
          <a href="{{ route('attendance.index', ['ref_type' => $refType]) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endcanany
@endsection
