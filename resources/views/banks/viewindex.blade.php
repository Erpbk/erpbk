@extends('layouts.app')
@section('title', 'Bank List')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="px-2">
        <div class="row mb-4">
            <div class="col-sm-6 d-flex gap-2">
                <a href="{{ route('banks.index') }}" class="@if(request()->segment(3) =='banks' && !in_array(request()->segment(3), ['receipts','payments'])) btn btn-primary  @else btn btn-default @endif action-btn"><i class="fa fa-bank"></i> Banks</a>
                <a href="{{ route('receipts.index') }}" class="@if(request()->segment(3) =='receipts') btn btn-primary @else btn btn-default @endif action-btn"><i class="fa fa-receipt"></i> Cash In</a>
                <a href="{{ route('payments.index') }}" class="@if(request()->segment(3) =='payments') btn btn-primary @else btn btn-default @endif action-btn"><i class="ti ti-cash"></i> Cash Out</a>
            </div>
            <div class="col-sm-6">
            <div class="action-buttons d-flex justify-content-end">
                <div class="action-dropdown-container">
                    <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                        <i class="ti ti-plus"></i>
                        <span>Add New</span>
                        <i class="ti ti-chevron-down"></i>
                    </button>
                    <div class="action-dropdown-menu" id="addBikeDropdown">
                        @can('bank_create')
                            @if(Route::is('banks.index'))
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Bank" data-action="{{ route('banks.create') }}">
                                    <i class="ti ti-plus"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">New Bank Account</div>
                                        <div class="action-dropdown-item-desc">Add a new Bank Account</div>
                                    </div>
                                </a>
                            @elseif(Route::is('receipts.index'))
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Receipt (Cash In)" data-action="{{ route('receipts.create') }}">
                                    <i class="ti ti-plus"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">New Receipt</div>
                                        <div class="action-dropdown-item-desc">Add a new Receipt</div>
                                    </div>
                                </a>
                            @elseif(Route::is('payments.index'))
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Payment (Cash Out)" data-action="{{ route('payments.create') }}">
                                    <i class="ti ti-plus"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">New Payment</div>
                                        <div class="action-dropdown-item-desc">Add a new Payment</div>
                                    </div>
                                </a>
                            @endif
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
            <div class="container mt-4">
                @php
                $netBalance = $fundsIn - $fundsOut;
                $balanceClass = $netBalance >= 0 ? 'text-success' : 'text-danger';
                $balanceIcon = $netBalance >= 0 ? '↑' : '↓';
                @endphp

                <div class="row">
                    <!-- Funds In Card -->
                    <div class="col-md-4 mb-3">
                        <div class="card border-success h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-arrow-down-circle fs-1 text-success"></i>
                                </div>
                                <h5 class="card-title text-success">Funds In</h5>
                                <h3 class="card-text fw-bold">AED {{ number_format($fundsIn, 2) }}</h3>
                                <p class="card-text text-muted">Total incoming transactions</p>
                            </div>
                        </div>
                    </div>

                    <!-- Net Balance Card -->
                    <div class="col-md-4 mb-3">
                        <div class="card border-primary h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-calculator fs-1 text-primary"></i>
                                </div>
                                <h5 class="card-title text-primary">Net Balance</h5>
                                <h3 class="card-text fw-bold {{ $balanceClass }}">
                                    {{ $balanceIcon }} AED {{ number_format(abs($netBalance), 2) }}
                                </h3>
                            </div>
                        </div>
                    </div>

                    <!-- Funds Out Card -->
                    <div class="col-md-4 mb-3">
                        <div class="card border-danger h-100">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="bi bi-arrow-up-circle fs-1 text-danger"></i>
                                </div>
                                <h5 class="card-title text-danger">Funds Out</h5>
                                <h3 class="card-text fw-bold">AED {{ number_format($fundsOut, 2) }}</h3>
                                <p class="card-text text-muted">Total outgoing transactions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</section>
@yield('page_content')
@endsection