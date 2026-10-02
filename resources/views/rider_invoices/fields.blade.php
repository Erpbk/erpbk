<script src="{{ asset('js/modal_custom.js') }}"></script>
@php
$currencyCode = \App\Helpers\Currency::code();
$isEdit = isset($invoice);
$defaultsModule = 'rider_invoices';
$invoiceDefaults = \App\Support\InvoiceModuleDefaults::all($defaultsModule);
$defaultNotes = old('customer_note', isset($invoice) ? ($invoice->customer_note ?? '') : ($invoiceDefaults['customer_notes'] ?? $invoiceDefaults['notes'] ?? ''));
$defaultTerms = old('terms_and_conditions', isset($invoice) ? ($invoice->terms_and_conditions ?? '') : ($invoiceDefaults['terms_and_conditions'] ?? ''));
@endphp

<style>
    .ri-form-wrap {
        --ri-primary: #1e3a5f;
        --ri-primary-soft: #e8f0fe;
        --ri-border: #e2e8f0;
        --ri-muted: #64748b;
        --ri-bg: #f0f4f8;
        background: var(--ri-bg);
        margin: -1rem;
        padding: 1.25rem;
        border-radius: 0 0 .5rem .5rem;
    }

    .ri-form-wrap .ri-card {
        background: #fff;
        border: 1px solid var(--ri-border);
        border-radius: 12px;
        padding: 1.15rem 1.25rem;
        height: 100%;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .ri-form-wrap .ri-card-title {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .95rem;
        font-weight: 700;
        color: var(--ri-primary);
        margin: 0 0 1rem;
    }

    .ri-form-wrap .ri-card-title i {
        color: #3b82f6;
        font-size: 1rem;
    }

    .ri-form-wrap .form-label,
    .ri-form-wrap label {
        font-size: .78rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: .35rem;
    }

    .ri-form-wrap .form-control,
    .ri-form-wrap .form-select {
        border-radius: 8px;
        border-color: var(--ri-border);
        font-size: .875rem;
        min-height: 38px;
    }

    .ri-form-wrap .form-control:focus,
    .ri-form-wrap .form-select:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .15);
    }

    .ri-form-wrap .ri-items-head {
        display: grid;
        grid-template-columns: 2.2fr .7fr .9fr .8fr .7fr 1fr .5fr;
        gap: .5rem;
        padding: 0 .25rem .5rem;
        border-bottom: 1px solid var(--ri-border);
        margin-bottom: .5rem;
    }

    .ri-form-wrap .ri-items-head span {
        font-size: .75rem;
        font-weight: 700;
        color: #475569;
    }

    .ri-form-wrap #rows-container>.row {
        display: grid !important;
        grid-template-columns: 2.2fr .7fr .9fr .8fr .7fr 1fr .5fr;
        gap: .5rem;
        margin: 0 0 .5rem !important;
        align-items: center;
    }

    .ri-form-wrap #rows-container>.row>[class*="col-"],
    .ri-form-wrap #rows-container>.row>.form-group {
        width: auto !important;
        max-width: none !important;
        flex: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .ri-form-wrap .ri-add-item {
        border: 1px solid #3b82f6;
        color: #2563eb;
        background: #fff;
        border-radius: 8px;
        font-weight: 600;
        padding: .4rem .9rem;
    }

    .ri-form-wrap .ri-add-item:hover {
        background: var(--ri-primary-soft);
        color: var(--ri-primary);
    }

    .ri-form-wrap .ri-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .55rem 0;
        font-size: .9rem;
        color: #334155;
        border-bottom: 1px solid var(--ri-border);
    }

    .ri-form-wrap .ri-summary-row:last-child {
        border-bottom: 0;
    }

    .ri-form-wrap .ri-summary-total {
        background: var(--ri-primary-soft);
        border-radius: 8px;
        padding: .7rem .85rem;
        margin-top: .35rem;
        border-bottom: 0;
        font-weight: 700;
        color: var(--ri-primary);
    }

    .ri-form-wrap .ri-summary-value {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .ri-form-wrap .btn-remove-row {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        color: #ef4444 !important;
        background: #fef2f2;
        text-decoration: none;
    }

    .ri-form-wrap .btn-remove-row:hover {
        background: #fee2e2;
    }

    .ri-form-wrap .select2-container .select2-selection--single {
        height: 38px !important;
        border-radius: 8px !important;
        border-color: var(--ri-border) !important;
        padding-top: 4px;
    }

    .ri-form-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }

    @media (max-width: 991.98px) {
        .ri-form-wrap .ri-items-head {
            display: none;
        }

        .ri-form-wrap #rows-container>.row {
            grid-template-columns: 1fr 1fr;
        }

        .ri-form-wrap #rows-container>.row> :first-child {
            grid-column: 1 / -1;
        }
    }
</style>

<div class="ri-form-wrap">
    <div class="row g-3">
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

    {{-- Notes & Terms --}}
    <div class="col-lg-8">
        <div class="ri-card">
            <h6 class="ri-card-title"><i class="fa fa-sticky-note"></i> Notes &amp; Terms</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" id="customer_note_field_label">Invoice Note</label>
                    {!! Form::textarea('customer_note', $defaultNotes ?: 'Thanks for your business.', [
                    'class' => 'form-control',
                    'rows' => 4,
                    'id' => 'customer_note',
                    'placeholder' => 'Thanks for your business.',
                    ]) !!}
                </div>
                <div class="col-md-6">
                    <label class="form-label" id="terms_and_conditions_field_label">Terms &amp; Conditions</label>
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

    {{-- Invoice Summary --}}
    <div class="col-lg-4">
        <div class="ri-card">
            <h6 class="ri-card-title"><i class="fa fa-calculator"></i> Invoice Summary</h6>
            <div class="ri-summary-row">
                <span>Subtotal</span>
                <span class="ri-summary-value">{{ $currencyCode }} <span id="subtotal_display">0.00</span></span>
            </div>
            <div class="ri-summary-row">
                <span>VAT Amount</span>
                <span class="ri-summary-value">{{ $currencyCode }} <span id="vat_total_display">0.00</span></span>
            </div>
            <div class="ri-summary-row ri-summary-total">
                <span>Total</span>
                <span class="ri-summary-value">{{ $currencyCode }} <span id="total_display">0.00</span></span>
            </div>
            <input type="hidden" name="subtotal" id="subtotal" value="0">
            <input type="hidden" name="vat_total" id="vat_total" value="0">
            <input type="hidden" name="total_amount" id="total" value="0">
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