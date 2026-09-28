<div class="row">
    <div class="col-md-9">
        <div class="row">
            {{-- Customer --}}
            <div class="form-group col-md-4">
                {!! Form::label('company_info', 'Customer') !!}
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

            {{-- Invoice Date --}}
            <div class="form-group col-md-4">
                {!! Form::label('inv_date', 'Invoice Date') !!}
                {!! Form::date('inv_date', isset($invoice)? $invoice->inv_date->format('Y-m-d') :null, ['class' => 'form-control', 'required' => true]) !!}
            </div>

            {{-- Billing Month --}}
            <div class="form-group col-md-4">
                {!! Form::label('billing_month', 'Billing Month') !!}
                {!! Form::month('billing_month', isset($invoice)? $invoice->billing_month->format('Y-m') :null, ['class' => 'form-control', 'required' => true]) !!}
            </div>

            {{-- Period From --}}
            <div class="form-group col-md-4">
                {!! Form::label('date_from', 'Period From') !!}
                {!! Form::date('date_from', isset($invoice)? $invoice->date_from->format('Y-m-d') :null, ['class' => 'form-control', 'required' => true]) !!}
            </div>

            {{-- Period To --}}
            <div class="form-group col-md-4">
                {!! Form::label('date_to', 'Period To') !!}
                {!! Form::date('date_to', isset($invoice)? $invoice->date_to->format('Y-m-d') :null, ['class' => 'form-control', 'required' => true]) !!}
            </div>
        </div>

        <div class="row">
            {{-- Description --}}
            <div class="form-group col-md-12">
                {!! Form::label('description', 'Description') !!}
                {!! Form::textarea('description', null, [
                'class' => 'form-control',
                'rows' => 3,
                'placeholder' => 'Enter invoice description...'
                ]) !!}
            </div>
        </div>
    </div>

    <div class="col-md-3">
        {{-- Attachment (right column, aligned like New Fine) --}}
        <div class="form-group">
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
            <small class="text-muted d-block text-center mt-1">Max: 5MB</small>
            @if(!empty($invoice?->attachment))
            <div class="mt-1 text-center">
                <a href="{{ asset('storage/' . $invoice->attachment) }}" target="_blank" class="d-inline-block text-primary small">
                    <i class="fa fa-paperclip"></i> {{ basename($invoice->attachment) }}
                </a>
                <small class="text-muted d-block">Leave empty to keep the existing attachment.</small>
            </div>
            @endif
        </div>
    </div>
</div>
@php
$items = \App\Models\Items::dropdown('customer');
@endphp
{{-- Invoice Items Section --}}
<h5 class="my-3">Invoice Items</h5>
<div class="scrollbar p-2 border rounded">
    <div class="row">
        <div class="form-group col-md-4">
            {!! Form::label('item', 'Item') !!}
        </div>
        <div class="form-group col-md-2">
            {!! Form::label('quantity', 'Qty') !!}
        </div>
        <div class="form-group col-md-2">
            {!! Form::label('rate', 'Rate') !!}
        </div>
        <div class="form-group col-md-1">
            {!! Form::label('vat', 'VAT (%)') !!}
        </div>
        <div class="form-group col-md-2">
            {!! Form::label('total', 'Total Amount') !!}
        </div>
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
            <div class="form-group col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm btn-remove-row">
                    <i class="fas fa-trash"></i>
                </button>
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

@php
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

<div class="row mt-3 align-items-start">
    <div class="col-md-7">
        {{-- Customer Notes (shown on invoice) --}}
        <!-- <div class="form-group mb-3">
            {!! Form::label('customer_note', $invoiceCustomerNoteLabel, ['id' => 'customer_note_field_label']) !!}
            {!! Form::textarea('customer_note', $defaultCustomerNotes, [
            'class' => 'form-control',
            'rows' => 3,
            'id' => 'customer_note',
            'placeholder' => 'Thanks for your business.',
            ]) !!}
            <small class="text-muted">Will be displayed on the invoice</small>
        </div> -->


        {{-- Internal notes --}}
        <div class="form-group mb-3">
            {!! Form::label('notes', 'Internal Notes') !!}
            {!! Form::textarea('notes', isset($invoice) ? $invoice->notes : null, [
            'class' => 'form-control',
            'rows' => 2,
            'placeholder' => 'Internal notes (not shown as customer note)...'
            ]) !!}
        </div>
        {{-- Terms & Conditions --}}
        <!-- <div class="form-group mb-3">
            {!! Form::label('terms_and_conditions', $invoiceTermsLabel, ['id' => 'terms_and_conditions_field_label']) !!}
            {!! Form::textarea('terms_and_conditions', $defaultTerms, [
            'class' => 'form-control',
            'rows' => 4,
            'id' => 'terms_and_conditions',
            'placeholder' => 'Enter terms & conditions...',
            ]) !!}
        </div> -->
    </div>

    <div class="col-md-5">
        <div class="border rounded p-3 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Sub Total</span>
                <input type="number" name="subtotal" class="form-control form-control-sm text-end" id="subtotal" readonly style="max-width: 160px;">
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">VAT Amount</span>
                <input type="number" name="vat_total" class="form-control form-control-sm text-end" id="vat_total" readonly style="max-width: 160px;">
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between align-items-center">
                <strong>Total ( {{ \App\Helpers\Currency::code() }} )</strong>
                <input type="number" name="total" class="form-control form-control-sm text-end fw-bold" id="total" readonly style="max-width: 160px;">
            </div>
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
        @if(!isset($invoice))
        fillCustomerNoteAndTermsFromSelected();
        @else
        fillCustomerNoteAndTermsFromSelected();
        @endif
    });
</script>