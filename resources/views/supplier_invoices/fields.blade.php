<script src="{{ asset('js/modal_custom.js') }}"></script>
@php
$items = \App\Models\Items::where('status', 1)->get();
@endphp

<div class="row">
    <input type="hidden" name="type" value="{{ $type }}">

    <div class="col-md-3 form-group">
        <label>Supplier</label>
        <select class="form-select form-select-sm select2" id="customer_id" name="supplier_id" required>
            <option value="">Select Supplier</option>
            @foreach($suppliers as $supplier)
            <option value="{{ $supplier->id }}"
                {{ isset($invoice) && $invoice->supplier_id == $supplier->id ? 'selected' : '' }}
                {{ isset($supplier_id) && $supplier_id == $supplier->id ? 'selected' : '' }}>
                {{ $supplier->name }}
            </option>
            @endforeach
        </select>
    </div>

    @if($type == 'invoice')
    <div class="col-md-2 form-group">
        <label>Invoice Date</label>
        {!! Form::date('inv_date', isset($invoice) ? $invoice->inv_date?->format('Y-m-d') : null, ['class' => 'form-control', 'required' => true]) !!}
    </div>
    <div class="col-md-2 form-group">
        <label>Billing Month</label>
        {!! Form::month('billing_month', isset($invoice) ? $invoice->billing_month?->format('Y-m') : null, ['class' => 'form-control', 'required' => true]) !!}
    </div>
    <div class="col-md-3 form-group">
        <label>Attachment</label>
        {!! Form::file('attachment', ['class' => 'form-control', 'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx']) !!}
        <small class="text-muted">Max: 5MB</small>
    </div>
    @else
    <div class="col-md-2 form-group">
        <label>Order Date</label>
        {!! Form::date('order_date', isset($invoice) ? $invoice->order_date?->format('Y-m-d') : null, ['class' => 'form-control', 'required' => true]) !!}
    </div>
    @endif

    <div class="col-md-6 form-group">
        <label>Descriptions</label>
        {!! Form::textarea('descriptions', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Descriptions']) !!}
    </div>
    <div class="col-md-6 form-group">
        <label>Notes</label>
        {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Notes']) !!}
    </div>
</div>

<div>
    <div class="card-header bg-blue mt-3">
        <b class="card-title">Item Details</b>
    </div>

    <div id="rows-container">
        <div id="row-container">
            @if(isset($invoice) && $invoice->items->count())
            @foreach($invoice->items as $itm)
            <div class="row mb-2 item-row">
                <div class="col-md-3 form-group">
                    <label>Item Description</label>
                    <select name="item_ids[]" class="form-select form-select-sm select2 item-select" onchange="supplier_item_price(this);" required>
                        <option value="">Select Item</option>
                        @foreach($items as $item)
                        <option value="{{ $item->id }}"
                            data-price="{{ $item->price ?? 0 }}"
                            data-vat="{{ $item->vat ?? 0 }}"
                            {{ $itm->item_id == $item->id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 form-group">
                    <label>Qty</label>
                    <input type="number" name="item_qty[]" value="{{ $itm->qty }}" class="form-control quantity" min="0.01" step="any" onkeyup="supplier_calculate_price(this);" onchange="supplier_calculate_price(this);" required>
                </div>
                <div class="col-md-2 form-group">
                    <label>Rate</label>
                    <input type="number" name="item_rate[]" value="{{ $itm->rate }}" class="form-control rate" min="0" step="any" onkeyup="supplier_calculate_price(this);" onchange="supplier_calculate_price(this);" required>
                </div>
                <div class="col-md-1 form-group">
                    <label>VAT %</label>
                    <input type="number" name="item_vat[]" value="{{ $itm->tax }}" class="form-control vat" min="0" max="100" step="any" onkeyup="supplier_calculate_price(this);" onchange="supplier_calculate_price(this);">
                </div>
                <div class="col-md-2 form-group">
                    <label>VAT Amount</label>
                    <input type="number" name="item_vatAmount[]" value="{{ $itm->tax_amount }}" class="form-control vatAmount" readonly>
                </div>
                <div class="col-md-2 form-group">
                    <label>Total Amount</label>
                    <input type="number" name="items_total[]" value="{{ $itm->total_amount }}" class="form-control item-total" readonly>
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <a href="javascript:void(0);" class="text-danger remove-row btn-remove-row"><i class="fa fa-trash"></i></a>
                </div>
            </div>
            @endforeach
            @else
            <div class="row mb-2 item-row">
                <div class="col-md-3 form-group">
                    <label>Item Description</label>
                    <select name="item_ids[]" class="form-select form-select-sm select2 item-select" onchange="supplier_item_price(this);" required>
                        <option value="">Select Item</option>
                        @foreach($items as $item)
                        <option value="{{ $item->id }}"
                            data-price="{{ $item->price ?? 0 }}"
                            data-vat="{{ $item->vat ?? 0 }}">
                            {{ $item->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 form-group">
                    <label>Qty</label>
                    <input type="number" name="item_qty[]" value="1" class="form-control quantity" min="0.01" step="any" onkeyup="supplier_calculate_price(this);" onchange="supplier_calculate_price(this);" required>
                </div>
                <div class="col-md-2 form-group">
                    <label>Rate</label>
                    <input type="number" name="item_rate[]" value="0" class="form-control rate" min="0" step="any" onkeyup="supplier_calculate_price(this);" onchange="supplier_calculate_price(this);" required>
                </div>
                <div class="col-md-1 form-group">
                    <label>VAT %</label>
                    <input type="number" name="item_vat[]" value="0" class="form-control vat" min="0" max="100" step="any" onkeyup="supplier_calculate_price(this);" onchange="supplier_calculate_price(this);">
                </div>
                <div class="col-md-2 form-group">
                    <label>VAT Amount</label>
                    <input type="number" name="item_vatAmount[]" value="0" class="form-control vatAmount" readonly>
                </div>
                <div class="col-md-2 form-group">
                    <label>Total Amount</label>
                    <input type="number" name="items_total[]" value="0" class="form-control item-total" readonly>
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <a href="javascript:void(0);" class="text-danger remove-row btn-remove-row"><i class="fa fa-trash"></i></a>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="append-line"></div>
    <div class="col-md-2 form-group">
        <button type="button" id="add-new-row" class="btn btn-success btn-sm mt-2 mb-2">Add New</button>
    </div>

    <div class="row">
        <div class="col-md-2 offset-md-6 form-group text-right">
            <label><strong>Sub Total</strong></label>
            <input type="text" class="form-control" id="subtotal" readonly>
        </div>
        <div class="col-md-2 form-group text-right">
            <label><strong>VAT Total</strong></label>
            <input type="text" class="form-control" id="vat_total" readonly>
        </div>
        <div class="col-md-2 form-group text-right">
            <label><strong>Grand Total</strong></label>
            <input type="text" class="form-control" id="total_cost" readonly>
        </div>
    </div>
</div>

<div class="card-footer">
    <div class="action-btn">
        {!! Form::submit('Save Invoice', ['class' => 'btn btn-primary']) !!}
    </div>
</div>

<script>
    (function() {
        if (typeof initSupplierInvoiceForm === 'function') {
            initSupplierInvoiceForm(document);
            return;
        }

        // Retry briefly if shared script loads after partial render
        setTimeout(function() {
            if (typeof initSupplierInvoiceForm === 'function') {
                initSupplierInvoiceForm(document);
            }
        }, 150);
    })();
</script>