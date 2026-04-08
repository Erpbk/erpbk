@extends('layouts.app')

@section('title','SIM Invoices')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>SIM Invoices</h3>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-size="xl" data-title="Create SIM Invoice" data-action="{{ route('simInvoices.create') }}">
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
            @include('sim_invoices.table', ['data' => $data])
        </div>
    </div>
</div>
@endsection
