{{--
  Shared totals for screen + PDF (same markup).
  Expected: $partyNote, $partyNoteLabel, $subtotalAmount, $vatAmount, $totalAmount, $currency
  Optional: $paidAmount, $balanceAmount
  PDF: uses $invPdf inline styles (from tax_invoice_pdf_inline) so DomPDF matches screen.
--}}
@php
$partyNote = $partyNote ?? null;
$partyNoteLabel = $partyNoteLabel ?? 'Invoice Note';
$subtotalAmount = $subtotalAmount ?? 0;
$vatAmount = $vatAmount ?? 0;
$totalAmount = $totalAmount ?? 0;
$currency = $currency ?? \App\Helpers\Currency::code();
$showPaidBalance = isset($paidAmount) || isset($balanceAmount);
$invPdf = $invPdf ?? [];
@endphp

<div class="inv-totals-area"@if(!empty($invPdf['totalsArea'])) style="{{ $invPdf['totalsArea'] }}"@endif>
    <div class="inv-totals-notes"@if(!empty($invPdf['totalsNotes'])) style="{{ $invPdf['totalsNotes'] }}"@endif>
        @if($partyNote)
            <div class="inv-note-box"@if(!empty($invPdf['noteBox'])) style="{{ $invPdf['noteBox'] }}"@endif>
                <div class="inv-note-title"@if(!empty($invPdf['noteTitle'])) style="{{ $invPdf['noteTitle'] }}"@endif>{{ $partyNoteLabel }}</div>
                <div class="inv-note-body"@if(!empty($invPdf['noteBody'])) style="{{ $invPdf['noteBody'] }}"@endif>{!! nl2br(e($partyNote)) !!}</div>
            </div>
        @endif
    </div>
    <div class="inv-totals"@if(!empty($invPdf['totalsCol'])) style="{{ $invPdf['totalsCol'] }}"@endif>
        <div class="inv-totals-table"@if(!empty($invPdf['totalsTable'])) style="{{ $invPdf['totalsTable'] }}"@endif>
            <div class="inv-totals-row"@if(!empty($invPdf['totalsRow'])) style="{{ $invPdf['totalsRow'] }}"@endif>
                <span class="k"@if(!empty($invPdf['totalsK'])) style="{{ $invPdf['totalsK'] }}"@endif>Subtotal (excl. VAT)</span>
                <span class="v"@if(!empty($invPdf['totalsV'])) style="{{ $invPdf['totalsV'] }}"@endif>{{ number_format((float) $subtotalAmount, 2) }}</span>
            </div>
            @if((float) $vatAmount != 0)
            <div class="inv-totals-row"@if(!empty($invPdf['totalsRow'])) style="{{ $invPdf['totalsRow'] }}"@endif>
                <span class="k"@if(!empty($invPdf['totalsK'])) style="{{ $invPdf['totalsK'] }}"@endif>VAT Amount</span>
                <span class="v"@if(!empty($invPdf['totalsV'])) style="{{ $invPdf['totalsV'] }}"@endif>{{ number_format((float) $vatAmount, 2) }}</span>
            </div>
            @endif
            <div class="inv-totals-row inv-totals-row-grand"@if(!empty($invPdf['totalsRowGrand'])) style="{{ $invPdf['totalsRowGrand'] }}"@endif>
                <div class="inv-grand"@if(!empty($invPdf['grand'])) style="{{ $invPdf['grand'] }}"@endif>
                    <span class="k"@if(!empty($invPdf['grandK'])) style="{{ $invPdf['grandK'] }}"@endif>Total Due</span>
                    <span class="v"@if(!empty($invPdf['grandV'])) style="{{ $invPdf['grandV'] }}"@endif>{{ number_format((float) $totalAmount, 2) }} {{ $currency }}</span>
                </div>
            </div>
            @if($showPaidBalance)
            <div class="inv-totals-row"@if(!empty($invPdf['totalsRow'])) style="{{ $invPdf['totalsRow'] }}"@endif>
                <span class="k"@if(!empty($invPdf['totalsK'])) style="{{ $invPdf['totalsK'] }}"@endif>Paid Amount</span>
                <span class="v"@if(!empty($invPdf['totalsV'])) style="{{ $invPdf['totalsV'] }}"@endif>{{ number_format((float) ($paidAmount ?? 0), 2) }}</span>
            </div>
            <div class="inv-totals-row"@if(!empty($invPdf['totalsRowLast'])) style="{{ $invPdf['totalsRowLast'] }}"@endif>
                <span class="k"@if(!empty($invPdf['totalsK'])) style="{{ $invPdf['totalsK'] }}"@endif>Balance</span>
                <span class="v"@if(!empty($invPdf['totalsV'])) style="{{ $invPdf['totalsV'] }}"@endif>{{ number_format((float) ($balanceAmount ?? 0), 2) }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
