@extends('riders.view')
@section('title','COD - ' . $rider->name)
@section('page_content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>{{ $rider->rider_id }} - {{ $rider->name }} | COD</h3>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-action="{{ route('cod.create') }}" data-size="lg" data-title="New COD">
                    Add New
                </a>
                <a class="btn btn-secondary" href="{{ route('cod.index') }}">
                    <i class="fas fa-arrow-left"></i> Back to All COD
                </a>
            </div>
        </div>
    </div>
</section>


<div class="content px-3">
    <div class="clearfix"></div>

    @include('flash::message')

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="card-title">COD Entries</h4>
                </div>
                <div class="col-md-6">
                    <div class="float-right text-end">
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#searchModal">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div id="dataTableContainer">
                @include('cod.table')
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="searchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter COD</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="GET" action="{{ route('cod.index') }}" class="mb-4">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="rider_id">Rider</label>
                            <select name="rider_id" id="rider_id" class="form-control select2">
                                <option value="">All Riders</option>
                                @foreach(\App\Models\Riders::select('id', 'name', 'rider_id')->get() as $rider)
                                <option value="{{ $rider->id }}" {{ request('rider_id') == $rider->id ? 'selected' : '' }}>
                                    {{ $rider->rider_id }} - {{ $rider->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
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
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                            <a href="{{ route('cod.index') }}" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection