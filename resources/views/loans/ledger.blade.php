@extends('layouts.app')
@section('title', 'Loan Ledger')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>{{ $loan->loan_number }} <small class="text-muted">Ledger</small></h3>
            </div>
            <div class="col-sm-6 text-end">
                <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to Loans
                </a>
            </div>
        </div>
    </div>
</section>

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
