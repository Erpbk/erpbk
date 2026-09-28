{{--
  Expected: $partyNote, $partyNoteLabel, $subtotalAmount, $vatAmount, $totalAmount, $currency
  Optional: $paidAmount, $balanceAmount, $showPaidBalance (when set, shown under Total Due)
--}}
@php
    $partyNote = $partyNote ?? null;
    $partyNoteLabel = $partyNoteLabel ?? 'Invoice Note';
    $subtotalAmount = $subtotalAmount ?? 0;
    $vatAmount = $vatAmount ?? 0;
    $totalAmount = $totalAmount ?? 0;
    $currency = $currency ?? \App\Helpers\Currency::code();
    $showPaidBalance = $showPaidBalance
        ?? (isset($paidAmount) || isset($balanceAmount));
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
        @if($showPaidBalance)
        <div class="line">
            <span class="k">Paid Amount</span>
            <span class="v">{{ number_format((float) ($paidAmount ?? 0), 2) }}</span>
        </div>
        <div class="line">
            <span class="k">Balance</span>
            <span class="v">{{ number_format((float) ($balanceAmount ?? 0), 2) }}</span>
        </div>
        @endif
    </div>
</div>
