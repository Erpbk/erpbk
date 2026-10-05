<script src="{{ asset('js/modal_custom.js') }}"></script>
@php
$currencyCode = \App\Helpers\Currency::code();
$isEdit = isset($invoice);
$defaultsModule = 'rider_invoices';
$invoiceDefaults = \App\Support\InvoiceModuleDefaults::all($defaultsModule);
$defaultNotes = old('customer_note', isset($invoice) ? ($invoice->customer_note ?? '') : ($invoiceDefaults['customer_notes'] ?? $invoiceDefaults['notes'] ?? ''));
$defaultTerms = old('terms_and_conditions', isset($invoice) ? ($invoice->terms_and_conditions ?? '') : ($invoiceDefaults['terms_and_conditions'] ?? ''));
@endphp

@include('invoices.partials.invoice_form_styles')

<div class="ri-form-wrap">
    <div class="row g-3">
        {{-- Rider & Attendance --}}
        <div class="col-lg-6">
            <div class="ri-card">
                <h6 class="ri-card-title"><i class="fa fa-user"></i> Rider &amp; Attendance</h6>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Rider</label>
                        {!! Form::select('rider_id', $riders, null, ['class' => 'form-select form-select-sm select2', 'id' => 'rider_id', 'data-placeholder' => 'Search or select rider']) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Zone</label>
                        {!! Form::text('zone', null, ['class' => 'form-control', 'placeholder' => 'Select zone']) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Login Hours</label>
                        {!! Form::text('login_hours', $isEdit ? null : '0', ['class' => 'form-control', 'placeholder' => '0']) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Working Days</label>
                        {!! Form::text('working_days', $isEdit ? null : '0', ['class' => 'form-control', 'placeholder' => '0']) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Perfect Attendance</label>
                        {!! Form::text('perfect_attendance', $isEdit ? null : '0', ['class' => 'form-control', 'placeholder' => '0']) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rejections</label>
                        {!! Form::text('rejection', $isEdit ? null : '0', ['class' => 'form-control', 'placeholder' => '0']) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Off Days</label>
                        {!! Form::text('off', $isEdit ? null : '0', ['class' => 'form-control', 'placeholder' => '0']) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Performance</label>
                        {!! Form::text('performance', null, ['class' => 'form-control', 'placeholder' => 'Select performance']) !!}
                    </div>
                </div>
            </div>
        </div>

        {{-- Invoice Details --}}
        <div class="col-lg-6">
            <div class="ri-card">
                <h6 class="ri-card-title"><i class="fa fa-file-alt"></i> Invoice Details</h6>
                <div class="row g-3">
                    @if(isset($invoiceTemplates) && $invoiceTemplates->count() > 0)
                    <div class="col-md-6">
                        <label class="form-label">Invoice Template</label>
                        <select name="template_id" class="form-select form-select-sm">
                            @foreach($invoiceTemplates as $tpl)
                            <option value="{{ $tpl->id }}" @selected((int) old('template_id', isset($invoice) ? ($invoice->template_id ?? ($defaultTemplate->id ?? 0)) : ($defaultTemplate->id ?? 0)) === (int) $tpl->id)>
                                {{ $tpl->template_name }}@if($tpl->is_default) (Default)@endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <label class="form-label">Invoice Date</label>
                        <input type="date"
                            class="form-control"
                            value="{{ isset($invoice) ? \Carbon\Carbon::parse($invoice->inv_date)->format('Y-m-d') : date('Y-m-d') }}"
                            name="inv_date"
                            placeholder="DD/MM/YYYY">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="billing_month">Billing Month</label>
                        <input type="month" name="billing_month" class="form-control"
                            value="@isset($invoice->billing_month){{ date('Y-m', strtotime($invoice->billing_month)) }}@endisset"
                            id="billing_month"
                            placeholder="Select month" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Service Period From</label>
                        <input type="date"
                            class="form-control"
                            name="service_period_from"
                            id="service_period_from"
                            required
                            value="{{ old('service_period_from', isset($invoice) ? optional($invoice->service_period_from)->format('Y-m-d') ?? date('Y-m-01', strtotime($invoice->billing_month)) : date('Y-m-01')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Service Period To</label>
                        <input type="date"
                            class="form-control"
                            name="service_period_to"
                            id="service_period_to"
                            required
                            value="{{ old('service_period_to', isset($invoice) ? optional($invoice->service_period_to)->format('Y-m-d') ?? date('Y-m-t', strtotime($invoice->billing_month)) : date('Y-m-t')) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Description --}}
        <div class="col-12">
            <div class="ri-card">
                <h6 class="ri-card-title"><i class="fa fa-align-left"></i> Description</h6>
                {!! Form::textarea('descriptions', null, [
                'class' => 'form-control',
                'placeholder' => 'Enter invoice description',
                'rows' => 3,
                ]) !!}
            </div>
        </div>

        {{-- Invoice Items --}}
        <div class="col-12">
            <div class="ri-card">
                <h6 class="ri-card-title"><i class="fa fa-list"></i> Invoice Items</h6>
                <div class="ri-items-head">
                    <span>Item Description</span>
                    <span>Qty</span>
                    <span>Rate ({{ $currencyCode }})</span>
                    <span>Discount</span>
                    <span>VAT %</span>
                    <span>Amount ({{ $currencyCode }})</span>
                    <span>Action</span>
                </div>
                <div id="rows-container">
                    @if($isEdit)
                    @foreach($invoice->items as $item)
                    <div class="row mt-1">
                        <div class="col-md-3 form-group">
                            <select name="item_ids[]" class="form-select item-select select2 item" required>
                                <option value="">Select item</option>
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
                        <div class="col-md-2 form-group">
                            <input type="text" value="{{ $item->qty }}" class="form-control qty" name="qty[]" placeholder="0">
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="text" value="{{ $item->rate }}" class="form-control rate" name="rate[]" placeholder="0.00">
                        </div>
                        <div class="col-md-1 form-group">
                            <input type="text" value="{{ $item->discount }}" class="form-control discount" name="discount[]" placeholder="0.00">
                        </div>
                        <div class="col-md-1 form-group">
                            <input type="text" value="{{ $item->tax }}" class="form-control vat" name="tax[]" placeholder="0">
                            <input type="hidden" value="0.00" name="vat_amount[]" class="vat_amount">
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="text" class="form-control amount" readonly name="amount[]" value="{{ number_format(round($item->amount, 2), 2, '.', '') }}" placeholder="0.00" data-numeric-value="{{ number_format(round($item->amount, 2), 2, '.', '') }}">
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-center justify-content-center">
                            <a href="javascript:void(0);" class="btn-remove-row" title="Remove"><i class="fa fa-trash"></i></a>
                        </div>
                    </div>
                    @endforeach
                    @else
                    @for($i = 0; $i < 2; $i++)
                        <div class="row mt-1">
                        <div class="col-md-3 form-group">
                            <select name="item_ids[]" class="form-select item-select select2 item" @if($i===0) required @endif>
                                <option value="">Select item</option>
                                @foreach($items as $item)
                                <option value="{{ $item->id }}"
                                    data-price="{{ $item->price ?? 0 }}"
                                    data-vat="{{ $item->vat ?? 0 }}">
                                    {{ $item->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="text" class="form-control qty" name="qty[]" value="0" placeholder="0">
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="text" class="form-control rate" name="rate[]" placeholder="0.00" value="0.00">
                        </div>
                        <div class="col-md-1 form-group">
                            <input type="text" class="form-control discount" name="discount[]" placeholder="0.00" value="0.00">
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
                @endfor
                @endif
            </div>
            <div class="mt-2">
                <button type="button" id="add-new-row" class="btn ri-add-item btn-sm">
                    <i class="fa fa-plus"></i> Add Item
                </button>
            </div>
            </div>
        </div>

    {{-- Note + totals (matches invoice show layout) --}}
    <div class="col-12">
        <div class="ri-totals-area">
            <div class="ri-totals-notes">
                <h4>Invoice Note</h4>
                {!! Form::textarea('customer_note', $defaultNotes ?: 'Thanks for your business.', [
                'class' => 'form-control',
                'rows' => 4,
                'id' => 'customer_note',
                'placeholder' => 'Thanks for your business.',
                ]) !!}
            </div>
            <div class="ri-totals">
                <div class="ri-summary-row">
                    <span>Subtotal (excl. VAT)</span>
                    <span class="ri-summary-value"><span id="subtotal_display">0.00</span></span>
                </div>
                <div class="ri-summary-row">
                    <span>VAT Amount</span>
                    <span class="ri-summary-value"><span id="vat_total_display">0.00</span></span>
                </div>
                <div class="ri-summary-row ri-summary-total">
                    <span>Total Due</span>
                    <span class="ri-summary-value"><span id="total_display">0.00</span> {{ $currencyCode }}</span>
                </div>
                <input type="hidden" name="subtotal" id="subtotal" value="0">
                <input type="hidden" name="vat_total" id="vat_total" value="0">
                <input type="hidden" name="total_amount" id="total" value="0">
            </div>
        </div>
    </div>

    {{-- Terms & Conditions (full width under summary) --}}
    <div class="col-12">
        <div class="ri-card">
            <h6 class="ri-card-title"><i class="fa fa-file-contract"></i> Terms &amp; Conditions</h6>
            {!! Form::textarea('terms_and_conditions', $defaultTerms, [
            'class' => 'form-control',
            'rows' => 4,
            'id' => 'terms_and_conditions',
            'placeholder' => 'Enter terms & conditions...',
            ]) !!}
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

        if (typeof window.setTotal === 'function' && !window.setTotal._riPatched) {
            var originalSetTotal = window.setTotal;
            window.setTotal = function() {
                originalSetTotal.apply(this, arguments);
                syncSummaryDisplay();
            };
            window.setTotal._riPatched = true;
        }

        $('.select2').select2({
            allowClear: true,
            placeholder: function() {
                return $(this).data('placeholder') || 'Select';
            },
            dropdownParent: $('#modalTopbody')
        });

        $('#rows-container .row').each(function() {
            setItemTotal($(this));
        });
        setTotal();
        syncSummaryDisplay();

        $('#billing_month').on('change', function() {
            var billingMonth = $(this).val();
            if (!billingMonth) {
                return;
            }

            var parts = billingMonth.split('-');
            var year = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10);
            var lastDay = new Date(year, month, 0).getDate();

            $('#service_period_from').val(billingMonth + '-01');
            $('#service_period_to').val(billingMonth + '-' + String(lastDay).padStart(2, '0'));
        });
    });
</script>