@extends('layouts.app')

@section('title', isset($cloneFromInvoice) ? 'Clone SIM Invoice' : 'Create SIM Invoice')

@section('content')
<section class="content-header">
    @include('flash::message')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>{{ isset($cloneFromInvoice) ? 'Clone SIM Invoice' : 'Create SIM Invoice' }}</h1>
            </div>
            <div class="col-sm-6 text-end">
                <a href="{{ route('simInvoices.index') }}" class="btn btn-secondary">Back to Invoices</a>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')
    <div class="card">
        {!! Form::open(['route' => 'simInvoices.store', 'files' => true, 'id' => 'simInvoiceForm']) !!}
        <div class="card-body">
            @include('sim_invoices.fields')
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-sm btn-primary">Save Invoice</button>
            <a href="{{ route('simInvoices.index') }}" class="btn btn-sm btn-secondary">Cancel</a>
        </div>
        {!! Form::close() !!}
    </div>
</div>
@endsection
