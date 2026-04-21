<div class="row">
    <input type="hidden" name="type" value="{{ $type }}">
    {{-- Company Information (Read-only) --}}
    <div class="form-group col-md-3">
        {!! Form::label('company_info', 'Supplier') !!}
        <select class="form-control select2" id="customer_id" name="supplier_id" required>
            <option value="" selected>Select</option>
            @foreach($suppliers as $supplier)
            <option value="{{ $supplier->id }}" {{ isset($invoice) ? $invoice->supplier_id == $supplier->id ? 'selected' : '' : '' }} {{ isset($supplier_id) ? $supplier_id == $supplier->id ? 'selected' : '' : '' }}>
                {{ $supplier->name }}
            </option>
            @endforeach
        </select>
    </div>
    @if($type=='invoice')
    {{-- Invoice Date --}}
    <div class="form-group col-md-2">
        {!! Form::label('inv_date', 'Invoice Date') !!}
        {!! Form::date('inv_date', isset($invoice)? $invoice->inv_date?->format('Y-m-d') ?? null :null, ['class' => 'form-control', 'required' => true]) !!}
    </div>
    @else
    {{-- Order Date --}}
    <div class="form-group col-md-2">
        {!! Form::label('inv_date', 'Order Date') !!}
        {!! Form::date('order_date', isset($invoice)? $invoice->order_date?->format('Y-m-d') ?? '' :null, ['class' => 'form-control', 'required' => true]) !!}
    </div>
    @endif
    @if($type=='invoice')
    {{-- Billing Month --}}
    <div class="form-group col-md-2">
        {!! Form::label('billing_month', 'Billing Month') !!}
        {!! Form::month('billing_month', isset($invoice)? $invoice->billing_month?->format('Y-m') ?? null :null, ['class' => 'form-control', 'required' => true]) !!}
    </div>
    {{-- Attachment --}}
    <div class="form-group col-md-3">
        {!! Form::label('attachment', 'Attachment') !!}
        {!! Form::file('attachment', [
        'class' => 'form-control',
        'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx'
        ]) !!}
        <small class="text-muted">Max: 5MB</small>
    </div>
    @endif
</div>

<div class="row">
    {{-- Description --}}
    <div class="form-group col-md-6">
        {!! Form::label('descriptions', 'Description') !!}
        {!! Form::textarea('descriptions', null, [
        'class' => 'form-control',
        'rows' => 3,
        'placeholder' => 'Enter invoice description...'
        ]) !!}
    </div>

    {{-- Notes --}}
    <div class="form-group col-md-6">
        {!! Form::label('notes', 'Notes') !!}
        {!! Form::textarea('notes', null, [
        'class' => 'form-control',
        'rows' => 3,
        'placeholder' => 'Additional notes or payment instructions...'
        ]) !!}
    </div>
</div>
@php
$items = \App\Models\Items::where('status', 1)->get();
@endphp
{{-- Invoice Items Section --}}
<h5 class="my-3">Invoice Items</h5>
<div class="scrollbar p-2 border rounded">
    <div id="row-container">
        @if(isset($invoice))
        @foreach($invoice->items as $index => $itm)
        @if($index == 0)
        <div class="row mb-2 item-row">
            <div class="form-group col-md-3">
                {!! Form::label('item', 'Item') !!}
                <select name="item_ids[]" class="form-control select2 item-select" required>
                    <option value="">Select Item</option>
                    @foreach($items as $item)
                    <option value="{{ $item->id }}"
                        data-price="{{ $item->price ?? 0 }}"
                        data-vat="{{ $item->vat ?? 0 }}"
                        data-name="{{ $item->name }}"
                        {{ $itm->item_id == $item->id ? 'selected' : '' }}>
                        {{ $item->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-1">
                {!! Form::label('quantity', 'Qty') !!}
                {!! Form::number('item_qty[]', $itm->qty, [
                'class' => 'form-control quantity',
                'step' => 'any',
                'min' => '0.01',
                'required' => true
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('rate', 'Rate') !!}
                {!! Form::number('item_rate[]', $itm->rate, [
                'class' => 'form-control rate',
                'step' => 'any',
                'min' => '0',
                'required' => true
                ]) !!}
            </div>
            <div class="form-group col-md-1">
                {!! Form::label('vat', 'VAT (%)') !!}
                {!! Form::number('item_vat[]', $itm->tax, [
                'class' => 'form-control vat',
                'step' => 'any',
                'min' => '0',
                'max' => '100'
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('vat', 'VAT Amount') !!}
                {!! Form::number('item_vatAmount[]', $itm->tax_amount, [
                'class' => 'form-control vatAmount',
                'step' => 'any',
                'min' => '0',
                'readonly' => true,
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('total', 'Total Amount') !!}
                {!! Form::number('items_total[]', $itm->total_amount, [
                'class' => 'form-control item-total',
                'step' => 'any',
                'readonly' => true
                ]) !!}
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm remove-row" style="display: none;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        @else
        <div class="row mb-2 item-row">
            <div class="form-group col-md-3">
                <select name="item_ids[]" class="form-control select2 item-select" required>
                    <option value="">Select Item</option>
                    @foreach($items as $item)
                    <option value="{{ $item->id }}"
                        data-price="{{ $item->price ?? 0 }}"
                        data-vat="{{ $item->vat ?? 0 }}"
                        data-name="{{ $item->name }}"
                        {{ $itm->item_id === $item->id ? 'selected' : '' }}>
                        {{ $item->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-1">
                {!! Form::number('item_qty[]', $itm->qty, [
                'class' => 'form-control quantity',
                'step' => 'any',
                'min' => '0.01',
                'required' => true
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::number('item_rate[]', $itm->rate, [
                'class' => 'form-control rate',
                'step' => 'any',
                'min' => '0',
                'required' => true
                ]) !!}
            </div>
            <div class="form-group col-md-1">
                {!! Form::number('item_vat[]', $itm->tax, [
                'class' => 'form-control vat',
                'step' => 'any',
                'min' => '0',
                'max' => '100'
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::number('item_vatAmount[]', $itm->tax_amount, [
                'class' => 'form-control vatAmount',
                'step' => 'any',
                'min' => '0',
                'readonly' => true,
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::number('items_total[]', $itm->total_amount, [
                'class' => 'form-control item-total',
                'step' => 'any',
                'readonly' => true
                ]) !!}
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm remove-row" style="display: none;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        @endif
        @endforeach
        @else
        <div class="row mb-2 item-row">
            <div class="form-group col-md-3">
                {!! Form::label('item', 'Item') !!}
                <select name="item_ids[]" class="form-control select2 item-select" required>
                    <option value="">Select Item</option>
                    @foreach($items as $item)
                    <option value="{{ $item->id }}"
                        data-price="{{ $item->price ?? 0 }}"
                        data-vat="{{ $item->vat ?? 0 }}"
                        data-name="{{ $item->name }}">
                        {{ $item->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-1">
                {!! Form::label('quantity', 'Qty') !!}
                {!! Form::number('item_qty[]', 1, [
                'class' => 'form-control quantity',
                'step' => 'any',
                'min' => '0.01',
                'required' => true
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('rate', 'Rate') !!}
                {!! Form::number('item_rate[]', 0, [
                'class' => 'form-control rate',
                'step' => 'any',
                'min' => '0',
                'required' => true
                ]) !!}
            </div>
            <div class="form-group col-md-1">
                {!! Form::label('vat', 'VAT (%)') !!}
                {!! Form::number('item_vat[]', 0, [
                'class' => 'form-control vat',
                'step' => 'any',
                'min' => '0',
                'max' => '100'
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('vat', 'VAT Amount') !!}
                {!! Form::number('item_vatAmount[]', 0, [
                'class' => 'form-control vatAmount',
                'step' => 'any',
                'min' => '0',
                'readonly' => true,
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('total', 'Total Amount') !!}
                {!! Form::number('items_total[]', null, [
                'class' => 'form-control item-total',
                'step' => 'any',
                'readonly' => true
                ]) !!}
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm remove-row" style="display: none;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Buttons Section --}}
<div class="d-flex justify-content-between align-items-center gap-3 mt-3">
    <div>
        <button type="button" id="add-row" class="btn btn-success btn-sm">
            <i class="fas fa-plus"></i> Add Item
        </button>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="input-group">
            <span class="input-group-text bg-light">Subtotal</span>
            <input type="number" name="subtotal" class="form-control" id="subtotal" readonly style="min-width: 150px;">
        </div>
        <div class="input-group">
            <span class="input-group-text bg-light">VAT Amount</span>
            <input type="number" name="vat_total" class="form-control" id="vat_total" readonly style="min-width: 150px;">
        </div>
        <div class="input-group">
            <span class="input-group-text bg-primary text-white">Total</span>
            <input type="number" name="total" class="form-control" id="total_cost" readonly style="min-width: 150px; font-weight: bold;">
        </div>
    </div>
</div>
</div>

<div class="card-footer">
    <div class="action-btn">
        {!! Form::submit('Save Invoice', ['class' => 'btn btn-primary']) !!}
    </div>
</div>