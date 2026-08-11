@extends('banks.viewindex')
@section('title','Cheques')
@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<style>
    .table-responsive {
        max-height: calc(100vh - 210px);
    }
</style>
@endpush
@section('page_content')

        @include('banks.partials.nav_tabs')

        @include('flash::message')
        <div class="clearfix"></div>
        @can('cash_&_banks_cheques_view')
                <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
            </div>
            <div class="totals-cards totals-cards-single-row">
                <div class="total-card total-blue">
                    <div class="label"><i class="fas fa-money-check"></i>Total Cheques</div>
                    <div class="value">{{ $totals['count'] ?? 0 }}</div>
                </div>
                <div class="total-card total-2">
                    <div class="label"><i class="fas fa-shield-alt"></i>Security Cheques</div>
                    <div class="value">{{ $totals['security_count'] ?? 0 }}</div>
                </div>
                <div class="total-card total-3">
                    <div class="label"><i class="fas fa-arrow-up"></i>Payable Amount</div>
                    <div class="value">{{ \App\Helpers\Currency::format($totals['payable_amount'] ?? 0, 2) }}</div>
                </div>
                <div class="total-card total-green">
                    <div class="label"><i class="fas fa-arrow-down"></i>Receivable Amount</div>
                    <div class="value">{{ \App\Helpers\Currency::format($totals['receivable_amount'] ?? 0, 2) }}</div>
                </div>
            </div>
            <div class="card-body table-responsive py-0" id="table-data">
                @include('cheques.table')
            </div>
        </div>
        @endcan
        @cannot('cash_&_banks_cheques_view')
            <div class="text-center mt-5">
                <h3>You do not have permission to view Cheques.</h3> 
            </div>
        @endcannot
@endsection

