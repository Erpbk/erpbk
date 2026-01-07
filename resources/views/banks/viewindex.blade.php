@extends('layouts.app')
@section('title', 'Bank List')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="px-2">
        <div class="row mb-2">
            <div class="col-sm-6 d-flex gap-2">
                <a href="{{ route('banks.index') }}" class="@if(request()->segment(1) =='banks' && !in_array(request()->segment(2), ['receipts','payments'])) btn btn-primary  @else btn btn-default @endif action-btn"><i class="fa fa-bank"></i> Banks</a>
                <a href="{{ route('receipts.index') }}" class="@if(request()->segment(1) =='receipts') btn btn-primary @else btn btn-default @endif action-btn"><i class="fa fa-receipt"></i> Receipts</a>
                <a href="{{ route('payments.index') }}" class="@if(request()->segment(1) =='payments') btn btn-primary @else btn btn-default @endif action-btn"><i class="ti ti-cash"></i> Payments</a>
            </div>
            <div class="col-sm-6 pt-2">
                <div class="action-buttons d-flex justify-content-end">
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Add New</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addBikeDropdown">
                            @can('bank_create')
                                @if(request()->segment(1) =='banks')
                                    <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Bank" data-action="{{ route('banks.create') }}">
                                        <i class="ti ti-plus"></i>
                                        <div>
                                            <div class="action-dropdown-item-text">New Bank Account</div>
                                            <div class="action-dropdown-item-desc">Add a new Bank Account</div>
                                        </div>
                                    </a>
                                @elseif(request()->segment(1) =='receipts')
                                    <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Receipt" data-action="{{ route('receipts.create') }}">
                                        <i class="ti ti-plus"></i>
                                        <div>
                                            <div class="action-dropdown-item-text">New Receipt</div>
                                            <div class="action-dropdown-item-desc">Add a new Receipt</div>
                                        </div>
                                    </a>
                                @elseif(request()->segment(1) =='payments')
                                    <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Payment" data-action="{{ route('payments.create') }}">
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
        </div>
    </div>
</section>
@yield('page_content')
@endsection