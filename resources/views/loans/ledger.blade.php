@extends('layouts.app')
@section('title', 'Loan Ledger')
@section('content')
@include('loans.view', ['loan' => $loan])
<div class="content">
    <div class="card mb-1">
        <div class="card-header"><h5 class="mb-0">Account Ledger — {{ $loan->loan_number }}</h5></div>
        <div class="card-body">
            @push('third_party_stylesheets')
            @include('layouts.datatables_css')
            @endpush

            {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped datatable']) !!}

            @push('third_party_scripts')
            @include('layouts.datatables_js')
            {!! $dataTable->scripts() !!}
            @endpush
        </div>
    </div>
</div>
@endsection
