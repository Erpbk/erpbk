{{--
  Expected: $partyNote, $partyNoteLabel, $subtotalAmount, $vatAmount, $totalAmount, $currency
  Optional: $paidAmount, $balanceAmount (when passed, shown under Total Due)
--}}
@php
$partyNote = $partyNote ?? null;
$partyNoteLabel = $partyNoteLabel ?? 'Invoice Note';
$subtotalAmount = $subtotalAmount ?? 0;
$vatAmount = $vatAmount ?? 0;
$totalAmount = $totalAmount ?? 0;
$currency = $currency ?? \App\Helpers\Currency::code();
$showPaidBalance = isset($paidAmount) || isset($balanceAmount);
$pdfBlue = ($brand['primary_color'] ?? null) ?: '#004aad';
@endphp

@if(!empty($isPdf))
<table class="pdf-totals-area" width="100%" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:separate; border-spacing:10px 0; margin:12px 0 8px;">
    <tr>
        @if($partyNote)
        <td width="55%" valign="top" style="width:55%; vertical-align:top; background:#f8fafc; border:1px solid #e2e8f0; border-left:4px solid {{ $pdfBlue }}; border-top:2px solid {{ $pdfBlue }}; padding:10px 12px;">
            <div style="font-size:9px; font-weight:700; letter-spacing:0.7px; text-transform:uppercase; color:{{ $pdfBlue }}; margin:0 0 6px;">{{ $partyNoteLabel }}</div>
            <div style="font-size:10px; color:#334155; line-height:1.5;">{!! nl2br(e($partyNote)) !!}</div>
        </td>
        @else
        <td width="55%" valign="top" style="width:55%;"></td>
        @endif
        <td width="40%" valign="top" style="width:40%; vertical-align:top;">
            @include('invoices.partials.tax_invoice_totals_block')
        </td>
    </tr>
</table>
@else
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
@endif
