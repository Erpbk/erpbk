@extends('banks.viewindex')
@section('page_content')
<div class="content px-3">

    @include('banks.partials.nav_tabs')

    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
        </div>
        <div class="totals-cards totals-cards-single-row">
            <div class="total-card total-blue">
                <div class="label"><i class="fas fa-dollar-sign"></i>Total Payments</div>
                <div class="value">{{ $totals['count'] ?? 0 }}</div>
            </div>
            <div class="total-card total-3">
                <div class="label"><i class="far fa-money-bill-alt"></i>Total Amount</div>
                <div class="value">{{ \App\Helpers\Currency::format($totals['amount'] ?? 0, 2) }}</div>
            </div>
            <div class="total-card total-2">
                <div class="label"><i class="fa fa-calendar"></i>This Month</div>
                <div class="value">{{ $totals['month_count'] ?? 0 }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label"><i class="far fa-money-bill-alt"></i>Amount This Month</div>
                <div class="value">{{ \App\Helpers\Currency::format($totals['month_amount'] ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('payments.table')
        </div>
    </div>
</div>
@endsection
