@php
$items = \App\Models\Items::dropdown('supplier');
$items = $items->merge(\App\Models\Items::dropdown('garage'));
$garages = \App\Models\Garages::where('status',1)->where('garage_type' , 'internal')->get();
$currencyCode = \App\Helpers\Currency::code();
$invItemsCols = '2.2fr .7fr .9fr .7fr .9fr 1fr .5fr';
@endphp

@include('invoices.partials.invoice_form_styles', ['invItemsCols' => $invItemsCols])

<input type="hidden" name="type" value="{{ $type }}">

<div class="inv-form-wrap">
    <div class="row g-3">
        <div class="col-lg-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-file-alt"></i> Invoice Details</h6>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Supplier</label>
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
                    <div class="col-md-6">
                        <label class="form-label">Invoice Date</label>
                        {!! Form::date('inv_date', isset($invoice) ? $invoice->inv_date?->format('Y-m-d') : null, ['class' => 'form-control', 'required' => true]) !!}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Billing Month</label>
                        {!! Form::month('billing_month', isset($invoice) ? $invoice->billing_month?->format('Y-m') : null, ['class' => 'form-control', 'required' => true]) !!}
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Garage</label>
                        <select class="form-select select2" id="garage_id" name="garage_id" required>
                            <option value="">Select Garage</option>
                            @foreach($garages as $garage)
                            <option value="{{ $garage->id }}"
                                {{ isset($invoice) && $invoice->garage_id == $garage->id ? 'selected' : '' }}>
                                {{ $garage->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        @include('partials.universal_document_upload', [
                          'name' => 'attachment',
                          'label' => 'Attachment',
                          'required' => false,
                          'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx',
                          'inputClass' => 'form-control',
                          'showHint' => true,
                        ])
                        <small class="text-muted">Max: 5MB</small>
                    </div>
                    @else
                    <div class="col-md-6">
                        <label class="form-label">Order Date</label>
                        {!! Form::date('order_date', isset($invoice) ? $invoice->order_date?->format('Y-m-d') : null, ['class' => 'form-control', 'required' => true]) !!}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-align-left"></i> Description</h6>
                {!! Form::textarea('descriptions', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Descriptions']) !!}
            </div>
        </div>

        <div class="col-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-list"></i> Item Details</h6>
                <div class="inv-items-head">
                    <span>Item</span>
                    <span>Qty</span>
                    <span>Rate ({{ $currencyCode }})</span>
                    <span>VAT %</span>
                    <span>VAT Amount</span>
                    <span>Total ({{ $currencyCode }})</span>
                    <span>Action</span>
                </div>
                <div id="rows-container">
                    @if(isset($invoice) && $invoice->items->count())
                    @php
                        $invoicePurchaseIds = $invoice->inventoryPurchases->pluck('id')->all();
                    @endphp
                    @foreach($invoice->items as $itm)
                    @php
                        $purchase = $invoice->inventoryPurchases->values()->get($loop->index);
                        $isLocked = $purchase && $purchase->isUsed() && ! $purchase->canReassignUsage($invoicePurchaseIds);
                    @endphp
                    <div class="row mb-2 {{ $isLocked ? 'inventory-used' : 'item-row' }}">
                        <div class="col-md-3 form-group">
                            @if($isLocked)
                            <input type="hidden" name="item_ids[]" value="{{ $itm->item_id }}">
                            @endif
                            <select @if(!$isLocked) name="item_ids[]" @endif class="form-select item select2" {{ $isLocked ? 'disabled' : '' }} required>
                                <option value="">Select Item</option>
                                @foreach($items as $item)
                                <option value="{{ $item->id }}"
                                    data-price="{{ $item->cost ?? 0 }}"
                                    data-vat="{{ $item->vat ?? 0 }}"
                                    {{ $itm->item_id == $item->id ? 'selected' : '' }}>
                                    {{ $item->name }}
                                </option>
                                @endforeach
                            </select>
                            @if($isLocked)
                            <small class="text-muted">Stock used — item, qty and cost are locked</small>
                            @endif
                        </div>
                        <div class="col-md-1 form-group">
                            <input type="number" name="item_qty[]" value="{{ $itm->qty }}" class="form-control qty" min="0.01" step="any" {{ $isLocked ? 'readonly' : '' }} required>
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="number" name="item_rate[]" value="{{ $itm->rate }}" class="form-control rate" min="0" step="any" {{ $isLocked ? 'readonly' : '' }} required>
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
                        <div class="form-group col-md-1 d-flex align-items-center justify-content-center">
                            @if(!$isLocked)
                            <a href="javascript:void(0);" class="btn-remove-row remove-row" title="Remove"><i class="fa fa-trash"></i></a>
                            @endif
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
                                    data-price="{{ $item->cost ?? 0 }}"
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
                        <div class="form-group col-md-1 d-flex align-items-center justify-content-center">
                            <a href="javascript:void(0);" class="btn-remove-row remove-row" title="Remove"><i class="fa fa-trash"></i></a>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="mt-2">
                    <button type="button" id="add-new-row" class="btn inv-add-item btn-sm">
                        <i class="fa fa-plus"></i> Add Item
                    </button>
                </div>
                <div class="append-line"></div>
            </div>
        </div>

        <div class="col-12">
            <div class="inv-totals-area">
                <div class="inv-totals-notes">
                    @include('invoices.partials.invoice_note_fields', [
                        'defaultsModule' => 'supplier_invoices',
                        'invoice' => $supplierInvoice ?? $invoice ?? null,
                        'render' => 'note',
                        'asTotalsNotes' => true,
                    ])
                </div>
                <div class="inv-totals">
                    <div class="inv-summary-row">
                        <span>Subtotal (excl. VAT)</span>
                        <span class="inv-summary-value"><span id="subtotal_display">0.00</span></span>
                    </div>
                    <div class="inv-summary-row">
                        <span>VAT Amount</span>
                        <span class="inv-summary-value"><span id="vat_total_display">0.00</span></span>
                    </div>
                    <div class="inv-summary-row inv-summary-total">
                        <span>Total Due</span>
                        <span class="inv-summary-value"><span id="total_display">0.00</span> {{ $currencyCode }}</span>
                    </div>
                    <input type="hidden" name="subtotal" id="subtotal" value="0">
                    <input type="hidden" name="vat_total" id="vat_total" value="0">
                    <input type="hidden" name="total_amount" id="total" value="0">
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-file-contract"></i> Terms &amp; Conditions</h6>
                @include('invoices.partials.invoice_note_fields', [
                    'defaultsModule' => 'supplier_invoices',
                    'invoice' => $supplierInvoice ?? $invoice ?? null,
                    'render' => 'terms',
                ])
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
        function syncSummaryDisplay() {
            var subtotal = parseFloat($('#subtotal').val()) || 0;
            var vat = parseFloat($('#vat_total').val()) || 0;
            var total = parseFloat($('#total').val()) || 0;
            $('#subtotal_display').text(subtotal.toFixed(2));
            $('#vat_total_display').text(vat.toFixed(2));
            $('#total_display').text(total.toFixed(2));
        }

        if (typeof window.setTotal === 'function' && !window.setTotal._invPatched) {
            var originalSetTotal = window.setTotal;
            window.setTotal = function() {
                originalSetTotal.apply(this, arguments);
                syncSummaryDisplay();
            };
            window.setTotal._invPatched = true;
        }

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
        syncSummaryDisplay();

        $(document).on('click', '#add-new-row', function () {
            setTimeout(function () {
                var $last = $('#rows-container .row:last');
                $last.removeClass('inventory-used').addClass('item-row');
                $last.find('select.item').prop('disabled', false).attr('name', 'item_ids[]');
                $last.find('input[type="hidden"][name="item_ids[]"]').remove();
                $last.find('.qty, .rate').prop('readonly', false);
                $last.find('small.text-muted').remove();
                if (!$last.find('.btn-remove-row').length) {
                    $last.find('.col-md-1.d-flex').append('<a href="javascript:void(0);" class="btn-remove-row remove-row" title="Remove"><i class="fa fa-trash"></i></a>');
                }
                syncSummaryDisplay();
            }, 0);
        });
    });
</script>
