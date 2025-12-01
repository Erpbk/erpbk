@extends('layouts.app')

@section('title','Account Ledger')
@section('content')
<div class="container">
    <h2>Account Ledger</h2>
<form action="" method="get">
    <div class="row mb-3">
        <div class="col-md-3">
          {!! Form::select('account_id[]', App\Models\Accounts::dropdown(null), null, ['class' => 'form-select form-select-sm select2']) !!}
        </div>
        <div class="col-md-3">
            <input type="month" name="billing_month" class="form-control" placeholder="Billing Month">
        </div>
        <div class="col-md-3">
            <button id="filter" class="btn btn-primary">Filter</button>
            <button id="reset" class="btn btn-secondary">Reset</button>
        </div>
    </div>
  </form>
    <div class="content">

      @include('flash::message')

      <div class="clearfix"></div>

      <div class="card p-4">
        <table class="table table-responsive dataTable">
          <tr>
            <th>Account</th>
            <th>Debit</th>
            <th>Credit</th>
            <th>Balance</th>
          </tr>
@php
    $balance=0;
@endphp
          @foreach($transactions as $row)
          <tr>
            <td>{{$row->account->name}}</td>
            <td>{{$row->debit}}</td>
            <td>{{$row->credit}}</td>
            <td>@php
                $balance += $row->debit;
        $balance -= $row->credit;
        echo number_format($balance, 2);
            @endphp</td>
          </tr>
          @endforeach
        </table>
        <div class="card-footer dataTables_info" style="float: right;">
        {{ $transactions->links()}}
      </div>

      </div>
    </div>
@endsection
