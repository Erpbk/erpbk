@extends('layouts.app')

@section('title','Employee Invoices')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
@can('employees_invoice_view')
<section class="content-header ">
    @include('flash::message')
    <div>
        <div class="row mb-2">
            <div class="col-sm-12 col-lg-12">
                <div class="action-buttons d-flex justify-content-end" >
                <div class="action-dropdown-container">
                    <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                        <i class="ti ti-plus"></i>
                        <span>Add New</span>
                        <i class="ti ti-chevron-down"></i>
                    </button>
                    <div class="action-dropdown-menu" id="addBikeDropdown">
                        @can('employees_invoice_create')
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Employee Invoice" data-action="{{ route('employeeInvoices.create') }}">
                            <i class="ti ti-plus"></i>
                            <div>
                                <div class="action-dropdown-item-text">New</div>
                                <div class="action-dropdown-item-desc">Add New Invoice</div>
                            </div>
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</section>
<div class="content px-0 mt-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-header text-end">
            <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i> Filter Cards</button>
        </div>
        <div class="card-body table-responsive py-0" id="table-data">
            @include('employee_invoices.table', ['data' => $data, 'currentMonthTotal' => $currentMonthTotal])
        </div>
    </div>
</div>
@else
<div class="alert alert-danger mt-4" role="alert">
    You do not have permission to view employee invoices.
</div>
@endcan
@endsection

