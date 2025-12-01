@extends('layouts.app')
@section('title','Penalties - ' . $rider->name)
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>{{ $rider->rider_id }} - {{ $rider->name }} | Penalties</h3>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-action="{{ route('penalties.create') }}" data-size="lg" data-title="New Penalty">
                    Add New
                </a>
                <a class="btn btn-secondary" href="{{ route('penalties.index') }}">
                    <i class="fas fa-arrow-left"></i> Back to All Penalties
                </a>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    <!-- Statistics Row -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($paidAmount, 2) }}</h3>
                    <p>Paid ({{ $paidCount }})</p>
                </div>
                <div class="icon">
                    <i class="ion ion-checkmark-circled"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($unpaidAmount, 2) }}</h3>
                    <p>Unpaid ({{ $unpaidCount }})</p>
                </div>
                <div class="icon">
                    <i class="ion ion-close-circled"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($pendingAmount, 2) }}</h3>
                    <p>Pending ({{ $pendingCount }})</p>
                </div>
                <div class="icon">
                    <i class="ion ion-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($paidAmount + $unpaidAmount + $pendingAmount, 2) }}</h3>
                    <p>Total Amount</p>
                </div>
                <div class="icon">
                    <i class="ion ion-calculator"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="clearfix"></div>

    @include('flash::message')

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="card-title">Penalty Entries for {{ $rider->name }}</h4>
                </div>
                <div class="col-md-6">
                    <div class="float-right">
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#searchModal">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div id="dataTableContainer">
                @include('penalties.rider_table')
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="searchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Penalties</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="GET" action="{{ route('penalties.rider', $rider->id) }}" class="mb-4">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="transaction_date">Transaction Date</label>
                            <input type="date" name="transaction_date" id="transaction_date" class="form-control" value="{{ request('transaction_date') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="billing_month">Billing Month</label>
                            <input type="month" name="billing_month" id="billing_month" class="form-control" value="{{ request('billing_month') }}">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                            <a href="{{ route('penalties.rider', $rider->id) }}" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection