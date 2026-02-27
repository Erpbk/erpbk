@extends('employees.view')
@section('page-content')

<div class="card">
  <div class="d-flex justify-content-between p-3">
    <h5><i class="ti ti-file-stack ti-lg text-body me-2"></i>Account Ledger</h5>
    <form action="" method="get">
      <input type="month" name="month" value="{{request('month')}}" class="form-control" onchange="form.submit();"/>
    </form>
  </div>
  
  <div class="pt-0 px-2">
    <div class="table-responsive" style="max-height: 800px; overflow: auto;">
      @push('third_party_stylesheets')
        @include('layouts.datatables_css')
      @endpush
      
      {!! $dataTable->table([
          'width' => '100%', 
          'class' => 'table table-striped datatable'
      ]) !!}
      
      @push('third_party_scripts')
        @include('layouts.datatables_js')
        {!! $dataTable->scripts() !!}
      @endpush
    </div>
  </div>
</div>
@endsection