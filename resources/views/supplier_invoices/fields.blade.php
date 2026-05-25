@php
$items = \App\Models\Items::dropdown('supplier');
$items = $items->merge(\App\Models\Items::dropdown('garage'));
$garages = \App\Models\Garages::where('status',1)->where('garage_type' , 'internal')->get();
@endphp

<div class="row">
    <input type="hidden" name="type" value="{{ $type }}">

    <div class="col-md-3 form-group">
        <label>Supplier</label>
        <select class="form-select select2" id="" name="supplier_id" required>
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
    <div class="col-md-2 form-group">
        <label>Garage</label>
        <select class="form-select select2" id="" name="garage_id" required>
            <option value="">Select Garage</option>
            @foreach($garages as $garage)
            <option value="{{ $garage->id }}"
                {{ isset($invoice) && $invoice->garage_id == $garage->id ? 'selected' : '' }}>
                {{ $garage->name }}
            </option>
            @endforeach
        </select>
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

    <div class="scrollbar p-2 border rounded">
        <div class="row">
            <div class="col-md-3 form-group">
                {!! Form::label('item', 'Item') !!}
            </div>
            <div class="col-md-1 form-group">
                <label>Qty</label>
            </div>
            <div class="col-md-2 form-group">
                <label>Rate</label>
            </div>
            <div class="col-md-1 form-group">
                <label>VAT</label>
            </div>
            <div class="col-md-2 form-group">
                <label>VAT Amount</label>
            </div>
            <div class="col-md-2 form-group">
                <label>Total Amount</label>
            </div>
        </div>
        <div id="rows-container">
            @if(isset($invoice) && $invoice->items->count())
            @foreach($invoice->items as $itm)
            <div class="row mb-2">
                <div class="col-md-3 form-group">
                    <select name="item_ids[]" class="form-select item select2" required>
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
                    <input type="number" name="item_qty[]" value="{{ $itm->qty }}" class="form-control qty" min="0.01" step="any" required>
                </div>
                <div class="col-md-2 form-group">
                    <input type="number" name="item_rate[]" value="{{ $itm->rate }}" class="form-control rate" min="0" step="any" required>
                </div>
                <div class="col-md-1 form-group">
                    <input type="number" name="item_vat[]" value="{{ $itm->tax }}" class="form-control vat" min="0" max="100" step="any">
                </div>
                <div class="col-md-2 form-group">
                    <input type="number" name="item_vatAmount[]" value="{{ $itm->tax_amount }}" class="form-control vat_amount" readonly>
                </div>
                <div class="col-md-2 form-group">
                    <input type="number" name="items_total[]" value="{{ $itm->total_amount }}" class="form-control amount" readonly>
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <a href="javascript:void(0);" class="text-danger remove-row btn-remove-row"><i class="fa fa-trash"></i></a>
                </div>
            </div>
            @endforeach
            @else
            <div class="row mb-2 item-row">
                <div class="col-md-3 form-group">
                    <select name="item_ids[]" class="form-select item select2" required>
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
                    <input type="number" name="item_qty[]" value="1" class="form-control qty" min="0.01" step="any" onkeyup="supplier_calculate_price(this);" onchange="supplier_calculate_price(this);" required>
                </div>
                <div class="col-md-2 form-group">
                    <input type="number" name="item_rate[]" value="0" class="form-control rate" min="0" step="any" onkeyup="supplier_calculate_price(this);" onchange="supplier_calculate_price(this);" required>
                </div>
                <div class="col-md-1 form-group">
                    <input type="number" name="item_vat[]" value="0" class="form-control vat" min="0" max="100" step="any" onkeyup="supplier_calculate_price(this);" onchange="supplier_calculate_price(this);">
                </div>
                <div class="col-md-2 form-group">
                    <input type="number" name="item_vatAmount[]" value="0" class="form-control vat_amount" readonly>
                </div>
                <div class="col-md-2 form-group">
                    <input type="number" name="items_total[]" value="0" class="form-control amount" readonly>
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <a href="javascript:void(0);" class="text-danger remove-row btn-remove-row"><i class="fa fa-trash"></i></a>
                </div>
            </div>
            @endif
        </div>
    </div>
    <div class="col-md-1 form-group">
        <button type="button" id="add-new-row" class="btn btn-success btn-sm mt-1 mb-3">Add New</button>
    </div>
    <div class="append-line"></div>
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
                <input type="number" name="total_amount" class="form-control" id="total" readonly style="min-width: 150px; font-weight: bold;">
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
        $('.select2').select2({
            dropdownParent: $('#modalTopbody'),
            allowClear: true
        });
        $('#garage_id').select2({
            dropdownParent: $('#modalTopbody'),
            allowClear: true
        });
        $('#rows-container .row').each(function() {
            setItemTotal($(this));
        });
        setTotal();

    });
</script>