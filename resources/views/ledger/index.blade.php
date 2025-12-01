@extends('layouts.app')

@section('title','Ledger')
@section('content')
<div class="">
    <h2>Ledger</h2>
<form action="" method="get">
    <div class="row mb-3">
        <div class="col-md-3">
          {!! Form::select('account', App\Models\Accounts::dropdown(null), request('account'), ['class' => 'form-select form-select-sm select2']) !!}
        </div>
        <div class="col-md-3">
            <input type="month" name="month" value="{{request('month')}}" class="form-control" placeholder="Billing Month">
        </div>
        <div class="col-md-3">
            <button id="filter" class="btn btn-primary">Generate Ledger</button>
        </div>
    </div>
  </form>

    <div class="content">

      @include('flash::message')

      <div class="clearfix"></div>

      <div class="card">
        {{-- @isset($summary)
        <div class="row mb-3 bg-label-success m-2 ">
          <table class="table table-responsive dataTable">
            <tr>
              <th>Account:</th>
              <th>Debit</th>
              <th>Credit</th>
              <th>Balance</th>
            </tr>
          </table>
        </div>
        @endisset --}}

    @push('third_party_stylesheets')
    @include('layouts.datatables_css')
@endpush

<div class="card-body px-4" style="overflow-x: auto !important;">
    {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped dataTable']) !!}
</div>

@push('third_party_scripts')
    @include('layouts.datatables_js')
    {!! $dataTable->scripts() !!}
@endpush
      </div>
    </div>
@endsection
