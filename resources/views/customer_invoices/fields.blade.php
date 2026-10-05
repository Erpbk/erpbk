@php
$currencyCode = \App\Helpers\Currency::code();
$invItemsCols = '2.4fr .8fr .9fr .7fr 1fr .5fr';
$items = \App\Models\Items::dropdown('customer');
$invoiceDefaults = \App\Support\CustomerInvoiceDefaults::all();
$defaultCustomerNotes = old('customer_note', isset($invoice) ? ($invoice->customer_note ?? '') : $invoiceDefaults['customer_notes']);
$defaultTerms = old('terms_and_conditions', isset($invoice) ? ($invoice->terms_and_conditions ?? '') : $invoiceDefaults['terms_and_conditions']);
$defaultCustomerNoteLabel = \App\Models\Customers::DEFAULT_CUSTOMER_NOTE_LABEL;
$defaultTermsLabel = \App\Models\Customers::DEFAULT_TERMS_AND_CONDITIONS_LABEL;
$invoiceCustomerNoteLabel = isset($invoice) && $invoice->customer
    ? $invoice->customer->resolvedCustomerNoteLabel()
    : $defaultCustomerNoteLabel;
$invoiceTermsLabel = isset($invoice) && $invoice->customer
    ? $invoice->customer->resolvedTermsAndConditionsLabel()
    : $defaultTermsLabel;
@endphp

@include('invoices.partials.invoice_form_styles', ['invItemsCols' => $invItemsCols])

<div class="inv-form-wrap">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-file-alt"></i> Invoice Details</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Customer</label>
                        <select class="form-control select2" id="customer_id" name="customer_id" required>
                            @php
                            $customers = \App\Models\Customers::all();
                            @endphp
                            <option value="" selected>Select</option>
                            @foreach($customers as $customer)
                            <option
                                value="{{ $customer->id }}"
                                data-customer-note="{{ json_encode($customer->customer_note ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                data-terms-and-conditions="{{ json_encode($customer->terms_and_conditions ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                data-customer-note-label="{{ json_encode($customer->resolvedCustomerNoteLabel(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                data-terms-and-conditions-label="{{ json_encode($customer->resolvedTermsAndConditionsLabel(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                {{ isset($invoice) ? $invoice->customer_id == $customer->id ? 'selected' : '' : '' }}
                                {{ isset($customer_id) ? $customer_id == $customer->id ? 'selected' : '' : '' }}>
                                {{ $customer->name }} ({{ company_table('branches')->where('id', $customer->branch_id)->value('code') }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Invoice Date</label>
                        {!! Form::date('inv_date', isset($invoice)? $invoice->inv_date->format('Y-m-d') :null, ['class' => 'form-control', 'required' => true]) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Billing Month</label>
                        {!! Form::month('billing_month', isset($invoice)? $invoice->billing_month->format('Y-m') :null, ['class' => 'form-control', 'required' => true]) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Period From</label>
                        {!! Form::date('date_from', isset($invoice)? $invoice->date_from->format('Y-m-d') :null, ['class' => 'form-control', 'required' => true]) !!}
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Period To</label>
                        {!! Form::date('date_to', isset($invoice)? $invoice->date_to->format('Y-m-d') :null, ['class' => 'form-control', 'required' => true]) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-paperclip"></i> Attachment</h6>
                @include('partials.universal_document_upload', [
                'name' => 'attachment',
                'label' => 'Attachment',
                'required' => false,
                'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx',
                'inputClass' => 'form-control',
                'showHint' => false,
                'variant' => 'dropzone',
                'uploadLabel' => 'Upload',
                'existingUrl' => !empty($invoice?->attachment) ? asset('storage/' . $invoice->attachment) : null,
                'existingName' => !empty($invoice?->attachment) ? basename($invoice->attachment) : null,
                'existingIsPdf' => !empty($invoice?->attachment) && \Illuminate\Support\Str::endsWith(strtolower($invoice->attachment), '.pdf'),
                ])
                <small class="text-muted d-block mt-1">Max: 5MB</small>
                @if(!empty($invoice?->attachment))
                <div class="mt-1">
                    <a href="{{ asset('storage/' . $invoice->attachment) }}" target="_blank" class="d-inline-block text-primary small">
                        <i class="fa fa-paperclip"></i> {{ basename($invoice->attachment) }}
                    </a>
                    <small class="text-muted d-block">Leave empty to keep the existing attachment.</small>
                </div>
                @endif
            </div>
        </div>

        <div class="col-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-align-left"></i> Description</h6>
                {!! Form::textarea('description', null, [
                'class' => 'form-control',
                'rows' => 3,
                'placeholder' => 'Enter invoice description...'
                ]) !!}
            </div>
        </div>

        <div class="col-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-list"></i> Invoice Items</h6>
                <div class="inv-items-head">
                    <span>Item</span>
                    <span>Qty</span>
                    <span>Rate ({{ $currencyCode }})</span>
                    <span>VAT %</span>
                    <span>Total ({{ $currencyCode }})</span>
                    <span>Action</span>
                </div>
                <div id="rows-container">
                    @if(isset($invoice))
                    @foreach($invoice->items as $index => $itm)
                    <div class="row mb-2 item-row">
                        <div class="form-group col-md-4">
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
                        <div class="form-group col-md-2">
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
                            {!! Form::hidden('item_vatAmount[]', $itm->vat_amount, ['class' => 'vat_amount']) !!}
                        </div>
                        <div class="form-group col-md-2">
                            {!! Form::number('items_total[]', $itm->total_amount, [
                            'class' => 'form-control amount',
                            'step' => 'any',
                            'readonly' => true
                            ]) !!}
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-center justify-content-center">
                            <a href="javascript:void(0);" class="btn-remove-row" title="Remove"><i class="fa fa-trash"></i></a>
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="row mb-2 item-row">
                        <div class="form-group col-md-4">
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
                        <div class="form-group col-md-2">
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
                            {!! Form::hidden('item_vatAmount[]', 0, ['class' => 'vat_amount']) !!}
                        </div>
                        <div class="form-group col-md-2">
                            {!! Form::number('items_total[]', null, [
                            'class' => 'form-control amount',
                            'step' => 'any',
                            'readonly' => true
                            ]) !!}
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
                    <h4 id="customer_note_field_label">{{ $invoiceCustomerNoteLabel }}</h4>
                    {!! Form::textarea('customer_note', $defaultCustomerNotes, [
                    'class' => 'form-control',
                    'rows' => 4,
                    'id' => 'customer_note',
                    'placeholder' => 'Thanks for your business.',
                    ]) !!}
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
                    <input type="hidden" name="total" id="total" value="0">
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="inv-card">
                <h6 class="inv-card-title"><i class="fa fa-file-contract"></i> Terms &amp; Conditions</h6>
                <label class="form-label" id="terms_and_conditions_field_label">{{ $invoiceTermsLabel }}</label>
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
            allowClear: true,
            dropdownParent: $('#modalTopbody')
        });
        $('#rows-container .row').each(function() {
            setItemTotal($(this));
        });
        setTotal();
        syncSummaryDisplay();

        var settingsNotes = @json($invoiceDefaults['customer_notes']);
        var settingsTerms = @json($invoiceDefaults['terms_and_conditions']);
        var defaultNoteLabel = @json($defaultCustomerNoteLabel);
        var defaultTermsLabel = @json($defaultTermsLabel);

        function parseOptionJsonAttr($opt, attr) {
            var raw = $opt.attr(attr) || '""';
            try {
                return JSON.parse(raw);
            } catch (e) {
                return raw;
            }
        }

        function setInvoiceFieldLabels(noteLabel, termsLabel) {
            $('#customer_note_field_label').text(noteLabel || defaultNoteLabel);
            $('#terms_and_conditions_field_label').text(termsLabel || defaultTermsLabel);
        }

        function fillCustomerNoteAndTermsFromSelected() {
            var $opt = $('#customer_id').find('option:selected');
            if (!$opt.length || !$opt.val()) {
                setInvoiceFieldLabels(defaultNoteLabel, defaultTermsLabel);
                @if(!isset($invoice))
                if (!$('#customer_note').val()) {
                    $('#customer_note').val(settingsNotes || '');
                }
                if (!$('#terms_and_conditions').val()) {
                    $('#terms_and_conditions').val(settingsTerms || '');
                }
                @endif
                return;
            }

            var note = parseOptionJsonAttr($opt, 'data-customer-note');
            var terms = parseOptionJsonAttr($opt, 'data-terms-and-conditions');
            var noteLabel = parseOptionJsonAttr($opt, 'data-customer-note-label') || defaultNoteLabel;
            var termsLabel = parseOptionJsonAttr($opt, 'data-terms-and-conditions-label') || defaultTermsLabel;

            note = note || settingsNotes || '';
            terms = terms || settingsTerms || '';

            setInvoiceFieldLabels(noteLabel, termsLabel);

            @if(!isset($invoice))
            $('#customer_note').val(note);
            $('#terms_and_conditions').val(terms);
            @else
            if (!$('#customer_note').val()) {
                $('#customer_note').val(note);
            }
            if (!$('#terms_and_conditions').val()) {
                $('#terms_and_conditions').val(terms);
            }
            @endif
        }

        $('#customer_id').on('change', fillCustomerNoteAndTermsFromSelected);
        fillCustomerNoteAndTermsFromSelected();
    });
</script>
