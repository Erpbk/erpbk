<script src="{{ asset('js/modal_custom.js') }}"></script>
@php
$currencyCode = \App\Helpers\Currency::code();
$invItemsCols = '2.2fr .7fr .9fr .8fr .7fr 1fr .5fr';
@endphp

@include('invoices.partials.invoice_form_styles', ['invItemsCols' => $invItemsCols])

<div class="inv-form-wrap">
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-user"></i> Employee</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Employee</label>
                        {!! Form::select('employee_id', $employees, null, ['class' => 'form-select form-select-sm select2', 'id' => 'employee_id']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-file-alt"></i> Invoice Details</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Invoice Date</label>
                        <input type="date" class="form-control"
                            value="{{ isset($invoice) ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d') }}"
                            name="inv_date" placeholder="Invoice Date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Billing Month</label>
                        <input type="month" name="billing_month" class="form-control"
                            value="@isset($invoice->billing_month){{ date('Y-m', strtotime($invoice->billing_month)) }}@endisset"
                            id="billing_month" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-align-left"></i> Description</h6>
                {!! Form::textarea('descriptions', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Descriptions']) !!}
            </div>
        </div>

        <div class="col-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-list"></i> Item Details</h6>
                <div class="inv-items-head">
                    <span>Item</span>
                    <span>Qty</span>
                    <span>Rate ({{ $currencyCode }})</span>
                    <span>Discount</span>
                    <span>VAT %</span>
                    <span>Total ({{ $currencyCode }})</span>
                    <span>Action</span>
                </div>
                <div id="rows-container">
                    @if(isset($invoice))
                        @foreach ($invoice->items as $item)
                            <div class="row mt-2">
                                <div class="col-md-3 form-group">
                                    <select name="item_id[]" class="form-control select2 item">
                                        <option value="">Select</option>
                                        @foreach($items as $itm)
                                        <option value="{{ $itm->id }}"
                                            data-price="{{ $itm->price }}"
                                            data-vat="{{ $itm->vat }}"
                                            {{ $item->item_id == $itm->id ? 'selected' : ''}}>
                                                {{ $itm->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1 form-group">
                                    <input type="text" value="{{ $item->qty }}" class="form-control qty" name="qty[]">
                                </div>
                                <div class="col-md-2 form-group">
                                    <input type="text" value="{{ $item->rate }}" class="form-control rate" name="rate[]">
                                </div>
                                <div class="col-md-2 form-group">
                                    <input type="text" value="{{ $item->discount }}" class="form-control discount" name="discount[]">
                                </div>
                                <div class="col-md-1 form-group">
                                    <input type="text" value="{{ $item->tax }}" class="form-control vat" name="tax[]">
                                    <input type="hidden" value="0.00" name="vat_amount[]" class="vat_amount">
                                </div>
                                <div class="col-md-2 form-group">
                                    <input type="text" class="form-control amount" readonly name="amount[]" value="{{ number_format($item->amount, 2) }}" data-numeric-value="{{ number_format(round($item->amount, 2), 2, '.', '') }}">
                                </div>
                                <div class="form-group col-md-1 d-flex align-items-center justify-content-center">
                                    <a href="javascript:void(0);" class="btn-remove-row" title="Remove"><i class="fa fa-trash"></i></a>
                                </div>
                            </div>
                        @endforeach
                    @else
                    <div class="row mt-2">
                        <div class="col-md-3 form-group">
                            <select name="item_id[]" class="form-control select2 item">
                                <option value="">Select</option>
                                @foreach($items as $item)
                                <option value="{{ $item->id }}"
                                    data-price="{{ $item->price }}"
                                    data-vat="{{ $item->vat }}">
                                        {{ $item->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 form-group">
                            <input type="text" class="form-control qty" name="qty[]" placeholder="0" value="1">
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="text" class="form-control rate" name="rate[]" placeholder="0" value="0">
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="text" class="form-control discount" name="discount[]" placeholder="0" value="0">
                        </div>
                        <div class="col-md-1 form-group">
                            <input type="text" class="form-control vat" name="tax[]" placeholder="0" value="0">
                            <input type="hidden" value="0.00" name="vat_amount[]" class="vat_amount">
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="text" class="form-control amount" readonly name="amount[]" placeholder="0.00" value="0.00">
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-center justify-content-center">
                            <a href="javascript:void(0);" class="btn-remove-row" title="Remove"><i class="fa fa-trash"></i></a>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="mt-2">
                    <button type="button" id="add-new-row" class="btn inv-add-item btn-sm">
                        <i class="fa fa-plus"></i> Add Item
                    </button>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="inv-totals-area">
                <div class="inv-totals-notes">
                    @include('invoices.partials.invoice_note_fields', [
                        'defaultsModule' => 'employee_invoices',
                        'invoice' => $employeeInvoice ?? $invoice ?? null,
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
                    'defaultsModule' => 'employee_invoices',
                    'invoice' => $employeeInvoice ?? $invoice ?? null,
                    'render' => 'terms',
                ])
            </div>
        </div>
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
        allowClear: true,
        dropdownParent: $('#modalTopbody')
    });
    $('#rows-container .row').each(function() {
        setItemTotal($(this));
    });
    setTotal();
    syncSummaryDisplay();
});
</script>
