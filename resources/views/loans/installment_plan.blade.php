@extends('layouts.app')
@section('title', 'Installment Schedule — '.$loan->loan_number)
@section('content')
@include('loans.view', ['loan' => $loan])
<div class="content">
    @include('flash::message')
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Installment Plan</h5></div>
        <div class="card-body table-responsive py-0" id="table-data">
            @include('loans.installment_plan_table', ['data' => $data, 'loan' => $loan])
        </div>
    </div>
</div>
@endsection
