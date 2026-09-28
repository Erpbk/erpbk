{{--
  Expected: $partyNote, $partyNoteLabel, $subtotalAmount, $vatAmount, $totalAmount, $currency
--}}
@php
    $partyNote = $partyNote ?? null;
    $partyNoteLabel = $partyNoteLabel ?? 'Invoice Note';
    $subtotalAmount = $subtotalAmount ?? 0;
    $vatAmount = $vatAmount ?? 0;
    $totalAmount = $totalAmount ?? 0;
    $currency = $currency ?? \App\Helpers\Currency::code();
@endphp
<div class="totals-area">
    @if($partyNote)
    <div class="totals-notes">
        <h4>{{ $partyNoteLabel }}</h4>
        <div class="body">{!! nl2br(e($partyNote)) !!}</div>
    </div>
    @endif
    <div class="totals">
        <div class="line">
            <span class="k">Subtotal (excl. VAT)</span>
            <span class="v">{{ number_format((float) $subtotalAmount, 2) }}</span>
        </div>
        @if((float) $vatAmount != 0)
        <div class="line">
            <span class="k">VAT Amount</span>
            <span class="v">{{ number_format((float) $vatAmount, 2) }}</span>
        </div>
        @endif
        <div class="grand">
            <span class="k">Total Due</span>
            <span class="v">{{ number_format((float) $totalAmount, 2) }} {{ $currency }}</span>
        </div>
    </div>
</div>
