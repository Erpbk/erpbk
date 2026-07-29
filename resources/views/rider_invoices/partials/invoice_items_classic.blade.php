@php
    $vatRate = $invoice_applies_vat ? $invoice_vat_rate : 0;
    $qtyText = static fn ($qty) => (float) $qty == 0
        ? '-'
        : rtrim(rtrim(number_format((float) $qty, 2), '0'), '.');
@endphp

<table class="items-table" style="margin-bottom: 0;">
    <thead>
        <tr>
            <th>#</th>
            <th>Item &amp; Desc</th>
            <th>Qty</th>
            <th>Rate</th>
            <th>Excl. Amount</th>
            <th>Tax</th>
            <th>Incl. Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($riderInvoice->items as $key => $val)
        @php
            $rowAmount = round((float) $val->amount, 2);
            $vatAmtRow = $invoice_applies_vat ? round($rowAmount * $vatRate / 100, 2) : 0;
            $rowTotal = round($rowAmount + $vatAmtRow, 2);
        @endphp
        <tr>
            <td class="num">{{ $key + 1 }}</td>
            <td>{{ $val->riderInv_item }} {{ \App\Models\Items::where('id', $val->item_id)->value('name') }}</td>
            <td class="num">{{ $qtyText($val->qty) }}</td>
            <td class="num">{{ (float) $val->rate == 0 ? '-' : number_format((float) $val->rate, 2) }}</td>
            <td class="num">{{ number_format($rowAmount, 2) }}</td>
            <td class="num">{{ number_format($vatAmtRow, 2) }}@if($vatRate > 0) ({{ number_format($vatRate, 0) }}%)@endif</td>
            <td class="num">{{ number_format($rowTotal, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@php
    $balanceDue = max(0, $rider_balance_final);
@endphp

<table style="margin-top: 0;">
    <tr>
        <td style="width: 55%; vertical-align: top; border: none; padding-left: 0;">
            @if($total_deductions > 0)
            <p style="margin: 8px 0 4px;"><span class="inv-label">Deductions:</span> -{{ number_format($total_deductions, 2) }}</p>
            @endif
            @if($total_additions > 0)
            <p style="margin: 4px 0;"><span class="inv-label">Additions:</span> +{{ number_format($total_additions, 2) }}</p>
            @endif
            @if($riderInvoice->notes)
            <p style="margin-top: 12px; font-size: 11px; color: var(--inv-text-muted);">{{ $riderInvoice->notes }}</p>
            @endif
        </td>
        <td style="width: 45%; vertical-align: top; border: none; padding-right: 0;">
            <table style="margin: 0;">
                <tr><td>Taxable Amount</td><td class="num">{{ \App\Helpers\Currency::format($totalBeforeTax, 2) }}</td></tr>
                @if($invoice_applies_vat)
                <tr><td>Total VAT</td><td class="num">{{ \App\Helpers\Currency::format($vatAmount, 2) }}</td></tr>
                @endif
                <tr class="inv-total-row"><td><strong>Total</strong></td><td class="num"><strong>{{ \App\Helpers\Currency::format($finalAmount, 2) }}</strong></td></tr>
                <tr><td>Received Amount</td><td class="num">{{ \App\Helpers\Currency::format($paid_amount, 2) }}</td></tr>
                <tr class="inv-grand-total"><td><strong>Balance Due</strong></td><td class="num"><strong>{{ \App\Helpers\Currency::format($balanceDue, 2) }}</strong></td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="inv-footer-note">
    Thank you for your partnership! For queries reach: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}
</div>
