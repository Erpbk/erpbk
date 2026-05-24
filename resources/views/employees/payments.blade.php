@extends('layouts.app')
@section('title', 'Employee Payments')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="px-2">
        <div class="row mb-4">
            <div class="col-sm-6 d-flex gap-2">
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
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Employee Payment" data-action="{{ route('payments.create') }}?employee_payment=1">
                            <i class="ti ti-plus"></i>
                            <div>
                                <div class="action-dropdown-item-text">New</div>
                                <div class="action-dropdown-item-desc">Add a new Employee Payment</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<div class="contenst">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('payments.table')
        </div>
    </div>
</div>
@endsection