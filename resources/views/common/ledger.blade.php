@extends('banks.view')

@section('page_content')

<div class="card card-action mb-1">
  <div class="card-header align-items-center">
    <h5 class="card-action-title mb-0"><i class="ti ti-file-stack ti-lg text-body me-2"></i>Account Ledger</h5>
    <form action="" method="get">
      <input type="month" name="month" value="{{request('month')}}" class="form-control" onchange="form.submit();"/>
    </form>
  </div>
  <div class="card-body pt-0 px-2">
    @push('third_party_stylesheets')
    @include('layouts.datatables_css')
@endpush

<div class="card-body px-0" >
    {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped dataTable']) !!}
</div>

@push('third_party_scripts')
    @include('layouts.datatables_js')
    {!! $dataTable->scripts() !!}
@endpush
  </div>
</div>

    @endsection


