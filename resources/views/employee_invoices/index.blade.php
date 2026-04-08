@extends('layouts.app')

@section('title','Employee Invoices')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Employee Invoices</h3>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-primary action-btn show-modal" href="javascript:void(0);" data-size="xl" data-title="Create Employee Invoice" data-action="{{ route('employeeInvoices.create') }}">
                    Create Invoice
                </a>
            </div>
        </div>
    </div>
</section>
<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('employee_invoices.table', ['data' => $data, 'currentMonthTotal' => $currentMonthTotal])
        </div>
    </div>
</div>
@endsection

