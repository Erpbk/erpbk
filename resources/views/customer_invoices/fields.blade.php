<div class="row">
    {{-- Company Information (Read-only) --}}
    <div class="form-group col-md-3">
        {!! Form::label('company_info', 'Customer') !!}
        <select class="form-control select2" id="customer_id" name="customer_id" required>
            @php
            $customers = \App\Models\Customers::all();
            @endphp
            <option value="" selected>Select</option>
            @foreach($customers as $customer)
            <option value="{{ $customer->id }}" {{ isset($invoice) ? $invoice->customer_id == $customer->id ? 'selected' : '' : '' }} {{ isset($customer_id) ? $customer_id == $customer->id ? 'selected' : '' : '' }}>
                {{ $customer->name }} ({{ company_table('branches')->where('id', $customer->branch_id)->value('code') }})
            </option>
            @endforeach
        </select>
    </div>

    {{-- Invoice Date --}}
    <div class="form-group col-md-2">
        {!! Form::label('inv_date', 'Invoice Date') !!}
        {!! Form::date('inv_date', isset($invoice)? $invoice->inv_date->format('Y-m-d') :null, ['class' => 'form-control', 'required' => true]) !!}
    </div>

    {{-- Billing Month --}}
    <div class="form-group col-md-2">
        {!! Form::label('billing_month', 'Billing Month') !!}
        {!! Form::month('billing_month', isset($invoice)? $invoice->billing_month->format('Y-m') :null, ['class' => 'form-control', 'required' => true]) !!}
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

    {{-- Period From --}}
    <div class="form-group col-md-3">
        {!! Form::label('date_from', 'Period From') !!}
        {!! Form::date('date_from', isset($invoice)? $invoice->date_from->format('Y-m-d') :null, ['class' => 'form-control', 'required' => true]) !!}
    </div>

    {{-- Period To --}}
    <div class="form-group col-md-3">
        {!! Form::label('date_to', 'Period To') !!}
        {!! Form::date('date_to', isset($invoice)? $invoice->date_to->format('Y-m-d') :null, ['class' => 'form-control', 'required' => true]) !!}
    </div>
</div>

<div class="row">
    {{-- Description --}}
    <div class="form-group col-md-6">
        {!! Form::label('description', 'Description') !!}
        {!! Form::textarea('description', null, [
        'class' => 'form-control',
        'rows' => 3,
        'placeholder' => 'Enter invoice description...'
        ]) !!}
    </div>
</div>
@php
$items = \App\Models\Items::dropdown('customer');
@endphp
{{-- Invoice Items Section --}}
<h5 class="my-3">Invoice Items</h5>
<div class="scrollbar p-2 border rounded">
    <div class="row">
        <div class="form-group col-md-3">
            {!! Form::label('item', 'Item') !!}
        </div>
        <div class="form-group col-md-1">
            {!! Form::label('quantity', 'Qty') !!}
        </div>
        <div class="form-group col-md-2">
            {!! Form::label('rate', 'Rate') !!}
        </div>
        <div class="form-group col-md-1">
            {!! Form::label('vat', 'VAT (%)') !!}
        </div>
        <div class="form-group col-md-2">
            {!! Form::label('vat', 'VAT Amount') !!}
        </div>
        <div class="form-group col-md-2">
            {!! Form::label('total', 'Total Amount') !!}
        </div>
    </div>
    <div id="rows-container">
        @if(isset($invoice))
        @foreach($invoice->items as $index => $itm)
        <div class="row mb-2 item-row">
            <div class="form-group col-md-3">
                <select name="item_ids[]" class="form-control select2 item" required>
                    <option value="">Select Item</option>
                    @foreach($items as $item)
                    <option value="{{ $item->id }}"
                        data-price="{{ $item->price }}"
                        data-vat="{{ $item->vat }}"
                        {{ $itm->item_id === $item->id ? 'selected' : '' }}>
                        {{ $item->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-1">
                {!! Form::number('item_qty[]', $itm->quantity, [
                'class' => 'form-control qty',
                'step' => 'any',
                'required' => true
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::number('item_rate[]', $itm->rate, [
                'class' => 'form-control rate',
                'step' => 'any',
                'required' => true
                ]) !!}
            </div>
            <div class="form-group col-md-1">
                {!! Form::number('item_vat[]', $itm->vat, [
                'class' => 'form-control vat',
                'step' => 'any',
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::number('item_vatAmount[]', $itm->vat_amount, [
                'class' => 'form-control vat_amount',
                'step' => 'any',
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::number('items_total[]', $itm->total_amount, [
                'class' => 'form-control amount',
                'step' => 'any',
                'readonly' => true
                ]) !!}
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm btn-remove-row">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        @endforeach
        @else
        <div class="row mb-2 item-row">
            <div class="form-group col-md-3">
                <select name="item_ids[]" class="form-control select2 item" required>
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
                {!! Form::number('item_qty[]', 1, [
                'class' => 'form-control qty',
                'step' => 'any',
                'min' => '0.01',
                'required' => true
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::number('item_rate[]', 0, [
                'class' => 'form-control rate',
                'step' => 'any',
                'required' => true
                ]) !!}
            </div>
            <div class="form-group col-md-1">
                {!! Form::number('item_vat[]', 0, [
                'class' => 'form-control vat',
                'step' => 'any',
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::number('item_vatAmount[]', 0, [
                'class' => 'form-control vat_amount',
                'step' => 'any',
                ]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::number('items_total[]', null, [
                'class' => 'form-control amount',
                'step' => 'any',
                'readonly' => true
                ]) !!}
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm btn-remove-row">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        @endif
    </div>
</div>
<div>
    <button type="button" id="add-new-row" class="btn btn-success btn-sm">
        <i class="fas fa-plus"></i> Add Item
    </button>
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
{{-- Buttons Section --}}
<div class="d-flex justify-content-between align-items-center gap-3 mt-3">
    <div>

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
            <input type="number" name="total" class="form-control" id="total" readonly style="min-width: 150px; font-weight: bold;">
        </div>
    </div>
</div>
</div>

<div class="card-footer">
    <div class="action-btn">
        {!! Form::submit('Save Invoice', ['class' => 'btn btn-primary']) !!}
    </div>
</div>

<script>
    $(document).ready(function() {

        // Initialize select2
        $('.select2').select2({
            allowClear: true,
            dropdownParent: $('#modalTopbody')
        });
        $('#rows-container .row').each(function() {
            setItemTotal($(this));
        });
        setTotal();
    });
</script>