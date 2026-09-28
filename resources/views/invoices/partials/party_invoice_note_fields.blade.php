{{--
  Party invoice note + terms with customizable labels.
  Expected: $party (model|null), $partyPrefix (e.g. 'rider'), optional $defaultsModule
--}}
@php
    $party = $party ?? null;
    $defaultsModule = $defaultsModule ?? null;
    $moduleDefaults = $defaultsModule
        ? \App\Support\InvoiceModuleDefaults::all($defaultsModule)
        : ['notes' => '', 'terms_and_conditions' => '', 'customer_notes' => ''];
    $defaultNoteLabel = 'Invoice Note';
    $defaultTermsLabel = 'Terms & Conditions';
    $noteDefault = old('invoice_note', $party->invoice_note ?? ($moduleDefaults['customer_notes'] ?? $moduleDefaults['notes'] ?? ''));
    $termsDefault = old('terms_and_conditions', $party->terms_and_conditions ?? ($moduleDefaults['terms_and_conditions'] ?? ''));
    $noteLabelDefault = old('invoice_note_label', $party->invoice_note_label ?? '');
    $termsLabelDefault = old('terms_and_conditions_label', $party->terms_and_conditions_label ?? '');
@endphp

<div class="form-group col-sm-12">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        {!! Form::label('terms_and_conditions', 'Terms & Conditions:', ['class' => 'fw-bold mb-0']) !!}
        <div class="d-flex align-items-center gap-2" style="min-width: 220px; max-width: 320px; flex: 1;">
            <span class="text-muted small text-nowrap">Invoice label</span>
            {!! Form::text('terms_and_conditions_label', $termsLabelDefault, [
                'class' => 'form-control form-control-sm',
                'placeholder' => $defaultTermsLabel,
                'maxlength' => 100,
            ]) !!}
        </div>
    </div>
    {!! Form::textarea('terms_and_conditions', $termsDefault, [
        'class' => 'form-control',
        'rows' => 4,
        'placeholder' => 'Terms & conditions shown on invoices...',
    ]) !!}
</div>

<div class="form-group col-sm-12">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
        {!! Form::label('invoice_note', 'Invoice Notes:', ['class' => 'fw-bold mb-0']) !!}
        <div class="d-flex align-items-center gap-2" style="min-width: 220px; max-width: 320px; flex: 1;">
            <span class="text-muted small text-nowrap">Invoice label</span>
            {!! Form::text('invoice_note_label', $noteLabelDefault, [
                'class' => 'form-control form-control-sm',
                'placeholder' => $defaultNoteLabel,
                'maxlength' => 100,
            ]) !!}
        </div>
    </div>
    {!! Form::textarea('invoice_note', $noteDefault, [
        'class' => 'form-control',
        'rows' => 3,
        'placeholder' => 'Thanks for your business.',
    ]) !!}
    <small class="text-muted">Displayed on the invoice using the label above (default: {{ $defaultNoteLabel }})</small>
</div>
