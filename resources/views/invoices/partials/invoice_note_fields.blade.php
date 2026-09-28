{{--
  Invoice-level note/terms fields with party-driven labels.
  Expected vars:
    $invoice (optional)
    $defaultsModule (string)
    $partyNote / $partyTerms / $partyNoteLabel / $partyTermsLabel (optional prefilled)
--}}
@php
    $defaultsModule = $defaultsModule ?? 'customer_invoices';
    $invoiceDefaults = \App\Support\InvoiceModuleDefaults::all($defaultsModule);
    $defaultNoteLabel = 'Invoice Note';
    $defaultTermsLabel = 'Terms & Conditions';
    $noteLabel = $partyNoteLabel ?? $defaultNoteLabel;
    $termsLabel = $partyTermsLabel ?? $defaultTermsLabel;
    $defaultNotes = old('customer_note', isset($invoice) ? ($invoice->customer_note ?? '') : ($invoiceDefaults['customer_notes'] ?? $invoiceDefaults['notes'] ?? ''));
    $defaultTerms = old('terms_and_conditions', isset($invoice) ? ($invoice->terms_and_conditions ?? '') : ($invoiceDefaults['terms_and_conditions'] ?? ''));
@endphp

<div class="form-group mb-3">
    {!! Form::label('customer_note', $noteLabel, ['id' => 'customer_note_field_label']) !!}
    {!! Form::textarea('customer_note', $defaultNotes, [
        'class' => 'form-control',
        'rows' => 3,
        'id' => 'customer_note',
        'placeholder' => 'Thanks for your business.',
    ]) !!}
    <small class="text-muted">Will be displayed on the invoice</small>
</div>

<div class="form-group mb-3">
    {!! Form::label('terms_and_conditions', $termsLabel, ['id' => 'terms_and_conditions_field_label']) !!}
    {!! Form::textarea('terms_and_conditions', $defaultTerms, [
        'class' => 'form-control',
        'rows' => 4,
        'id' => 'terms_and_conditions',
        'placeholder' => 'Enter terms & conditions...',
    ]) !!}
</div>
