@extends('riders.view')

@section('page_content')

<div class="card card-action mb-0">
  <div class="card-header align-items-center">
    <h5 class="card-action-title mb-0"><i class="ti ti-calendar-check ti-lg text-body me-2"></i>Attendance</h5>
  </div>
  <div class="card-body pt-0 px-2">
    @push('third_party_stylesheets')
    @include('layouts.datatables_css')
@endpush

<div class="card-body px-0 pt-0" >
    {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped dataTable']) !!}
</div>

@push('third_party_scripts')
    @include('layouts.datatables_js')
    {!! $dataTable->scripts() !!}
@endpush
  </div>
</div>

    @endsection


