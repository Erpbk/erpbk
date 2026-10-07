{{--
  Invoice-level note/terms fields with party-driven labels.
  Expected vars:
    $invoice (optional)
    $defaultsModule (string)
    $partyNote / $partyTerms / $partyNoteLabel / $partyTermsLabel (optional prefilled)
    $render: 'both' (default) | 'note' | 'terms'
    $asTotalsNotes: when true, note uses uppercase h4 (invoice show style)
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
    $render = $render ?? 'both';
    $asTotalsNotes = !empty($asTotalsNotes);
@endphp

@if($render === 'both' || $render === 'note')
<div class="form-group mb-0">
    @if($asTotalsNotes)
        <h4 id="customer_note_field_label">{{ $noteLabel }}</h4>
    @else
        {!! Form::label('customer_note', $noteLabel, ['id' => 'customer_note_field_label', 'class' => 'form-label']) !!}
    @endif
    {!! Form::textarea('customer_note', $defaultNotes, [
        'class' => 'form-control',
        'rows' => $asTotalsNotes ? 4 : 3,
        'id' => 'customer_note',
        'placeholder' => 'Thanks for your business.',
    ]) !!}
    @unless($asTotalsNotes)
        <small class="text-muted">Will be displayed on the invoice</small>
    @endunless
</div>
@endif

@if($render === 'both' || $render === 'terms')
<div class="form-group mb-0 {{ $render === 'both' ? 'mt-3' : '' }}">
    {!! Form::label('terms_and_conditions', $termsLabel, ['id' => 'terms_and_conditions_field_label', 'class' => 'form-label']) !!}
    {!! Form::textarea('terms_and_conditions', $defaultTerms, [
        'class' => 'form-control',
        'rows' => 4,
        'id' => 'terms_and_conditions',
        'placeholder' => 'Enter terms & conditions...',
    ]) !!}
</div>
@endif
