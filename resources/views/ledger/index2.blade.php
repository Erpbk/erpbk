@extends('layouts.app')
@section('title', 'Ledger')
@section('content')

<div class="container-fluid card mb-1">
  <div class="card-header d-flex justify-content-between">
    <h5><i class="ti ti-file-stack ti-lg text-body me-2"></i>Account Ledger</h5>
    <form action="" method="get" class="d-flex gap-2">
        <input type="month" name="month" value="{{request('month')}}" style="height: 38px;" class="form-control" onchange="form.submit();"/>
        <select name="account" class="form-control select-2" style="height: 38px; width: 300px;" onchange="form.submit();">
            <option value="">Select An Account</option>
            @foreach(\App\Models\Accounts::all() as $account)
                <option value="{{$account->id}}" {{request('account') == $account->id ? 'selected' : ''}}>{{$account->account_code .'-'. $account->name}}</option>
            @endforeach
        </select>
    </form>
  </div>
  
  <div class="card-body pt-0 px-0">
    <div class="table-responsive" style="max-height: 800px; overflow: auto;">
      @push('third_party_stylesheets')
        @include('layouts.datatables_css')
      @endpush
      
      {!! $dataTable->table([
          'width' => '100%', 
          'class' => 'table table-striped datatable',
      ]) !!}
      
      @push('third_party_scripts')
        @include('layouts.datatables_js')
        <script>
            $(document).ready(function() {
                $('.select-2').select2({
                    width: '300px',
                });
            });
        </script>
        {!! $dataTable->scripts() !!}
      @endpush
    </div>
  </div>
</div>
@endsection
