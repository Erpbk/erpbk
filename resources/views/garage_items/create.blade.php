@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Garage Items /</span> Create
    </h4>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Create Garage Item</h5>
            <a href="{{ route('garage-items.index') }}" class="btn btn-secondary float-end">
                <i class="ti ti-arrow-left me-1"></i>Back
            </a>
        </div>
        <div class="card-body">
            {!! Form::open(['route' => 'garage-items.store']) !!}
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        {!! Form::label('name', 'Name', ['class' => 'form-label']) !!}
                        {!! Form::text('name', null, ['class' => 'form-control' . ($errors->has('name') ? ' is-invalid' : ''), 'required']) !!}
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        {!! Form::label('item_code', 'Item Code (Optional)', ['class' => 'form-label']) !!}
                        {!! Form::text('item_code', null, ['class' => 'form-control' . ($errors->has('item_code') ? ' is-invalid' : ''), 'placeholder' => 'Leave empty for auto-generation']) !!}
                        <small class="text-muted">If left empty, a unique code will be automatically generated</small>
                        @error('item_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        {!! Form::label('qty', 'Quantity', ['class' => 'form-label']) !!}
                        {!! Form::number('qty', null, ['class' => 'form-control' . ($errors->has('qty') ? ' is-invalid' : ''), 'required', 'min' => '0', 'id' => 'qty', 'onchange' => 'calculateTotal()']) !!}
                        @error('qty')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        {!! Form::label('price', 'Price Per Unit', ['class' => 'form-label']) !!}
                        {!! Form::number('price', null, ['class' => 'form-control' . ($errors->has('price') ? ' is-invalid' : ''), 'required', 'min' => '0', 'step' => '0.01', 'id' => 'price', 'onchange' => 'calculateTotal()']) !!}
                        @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        {!! Form::label('total_amount', 'Total Amount', ['class' => 'form-label']) !!}
                        {!! Form::number('total_amount', null, ['class' => 'form-control', 'readonly', 'id' => 'total_amount', 'step' => '0.01']) !!}
                        <small class="text-muted">This is calculated automatically (Quantity × Price)</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        {!! Form::label('avg_price', 'Average Price Per Unit', ['class' => 'form-label']) !!}
                        {!! Form::number('avg_price', null, ['class' => 'form-control', 'readonly', 'id' => 'avg_price', 'step' => '0.01']) !!}
                        <small class="text-muted">For new items, this equals the Price Per Unit</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        {!! Form::label('supplier_id', 'Supplier', ['class' => 'form-label']) !!}
                        {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control' . ($errors->has('supplier_id') ? ' is-invalid' : ''), 'required']) !!}
                        @error('supplier_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        {!! Form::label('purchase_date', 'Purchase Date', ['class' => 'form-label']) !!}
                        {!! Form::date('purchase_date', \Carbon\Carbon::now(), ['class' => 'form-control' . ($errors->has('purchase_date') ? ' is-invalid' : ''), 'required']) !!}
                        @error('purchase_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="{{ route('garage-items.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@endsection

@push('page-scripts')
<script>
    function calculateTotal() {
        const qty = parseFloat(document.getElementById('qty').value) || 0;
        const price = parseFloat(document.getElementById('price').value) || 0;
        const totalAmount = qty * price;

        document.getElementById('total_amount').value = totalAmount.toFixed(2);
        document.getElementById('avg_price').value = price.toFixed(2);
    }

    // Calculate initially when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        calculateTotal();
    });
</script>
@endpush