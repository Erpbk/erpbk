{{--
  Shared totals for screen + PDF (same markup).
  Expected: $partyNote, $partyNoteLabel, $subtotalAmount, $vatAmount, $totalAmount, $currency
  Optional: $paidAmount, $balanceAmount
--}}
@php
$partyNote = $partyNote ?? null;
$partyNoteLabel = $partyNoteLabel ?? 'Invoice Note';
$subtotalAmount = $subtotalAmount ?? 0;
$vatAmount = $vatAmount ?? 0;
$totalAmount = $totalAmount ?? 0;
$currency = $currency ?? \App\Helpers\Currency::code();
$showPaidBalance = isset($paidAmount) || isset($balanceAmount);
@endphp

<table class="inv-totals-area" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td class="inv-totals-notes" width="55%" valign="top">
            @if($partyNote)
                <div class="inv-note-box">
                    <div class="inv-note-title">{{ $partyNoteLabel }}</div>
                    <div class="inv-note-body">{!! nl2br(e($partyNote)) !!}</div>
                </div>
            @endif
        </td>
        <td class="inv-totals" width="40%" valign="top">
            <table class="inv-totals-table" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="k">Subtotal (excl. VAT)</td>
                    <td class="v">{{ number_format((float) $subtotalAmount, 2) }}</td>
                </tr>
                @if((float) $vatAmount != 0)
                <tr>
                    <td class="k">VAT Amount</td>
                    <td class="v">{{ number_format((float) $vatAmount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td colspan="2" class="grand-cell">
                        <table class="inv-grand" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="k">Total Due</td>
                                <td class="v">{{ number_format((float) $totalAmount, 2) }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @if($showPaidBalance)
                <tr>
                    <td class="k">Paid Amount</td>
                    <td class="v">{{ number_format((float) ($paidAmount ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td class="k">Balance</td>
                    <td class="v">{{ number_format((float) ($balanceAmount ?? 0), 2) }}</td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
