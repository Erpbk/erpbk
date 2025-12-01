@extends('layouts.app')
@section('title', 'Bank List')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6 d-flex gap-2">
                <a href="{{ route('banks.index') }}" class="@if(request()->segment(1) =='banks' && !in_array(request()->segment(2), ['receipts','payments'])) btn btn-primary  @else btn btn-default @endif action-btn"><i class="fa fa-bank"></i> Banks</a>
                <a href="{{ route('receipts.index') }}" class="@if(request()->segment(1) =='receipts') btn btn-primary @else btn btn-default @endif action-btn"><i class="fa fa-receipt"></i> Receipts</a>
                <a href="{{ route('payments.index') }}" class="@if(request()->segment(1) =='payments') btn btn-primary @else btn btn-default @endif action-btn"><i class="ti ti-cash"></i> Payments</a>
            </div>
            <div class="col-sm-6">
                @can('bank_create')
                @if(request()->segment(1) =='banks')
                <a class="btn btn-primary float-right show-modal action-btn"
                    href="javascript:void(0);" data-action="{{ route('banks.create') }}" data-title="Add New" data-size="lg">
                    Add New
                </a>
                @elseif(request()->segment(1) =='receipts')
                <a class="btn btn-primary float-right show-modal action-btn"
                    href="javascript:void(0);" data-action="{{ route('receipts.create') }}" data-title="Add New" data-size="lg">
                    Add New
                </a>
                @elseif(request()->segment(1) =='payments')
                <a class="btn btn-primary float-right show-modal action-btn"
                    href="javascript:void(0);" data-action="{{ route('payments.create') }}" data-title="Add New" data-size="lg">
                    Add New
                </a>
                @endif
                @endcan
            </div>
        </div>
    </div>
</section>
@yield('page_content')
@endsection