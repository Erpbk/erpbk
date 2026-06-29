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
        @php $running_total = 0; $tax_total = 0; @endphp
        @foreach($riderInvoice->items as $key => $val)
        @php
            $total += $val->amount;
            $vatRate = $riderInvoice->vat > 0 ? $vat_percentage : 0;
            $vatAmtRow = $riderInvoice->vat > 0 ? $val->amount * $vatRate / 100 : 0;
            $rowTotal = $val->amount + $vatAmtRow;
            $running_total += $rowTotal;
            $tax_total += $vatAmtRow;
        @endphp
        <tr>
            <td class="num">{{ $key + 1 }}</td>
            <td>{{ $val->riderInv_item }} {{ \App\Models\Items::where('id', $val->item_id)->value('name') }}</td>
            <td class="num">{{ $val->qty == 0 ? '-' : $val->qty }}</td>
            <td class="num">{{ $val->rate == 0 ? '-' : number_format($val->rate, 2) }}</td>
            <td class="num">{{ number_format($val->amount, 2) }}</td>
            <td class="num">{{ number_format($vatAmtRow, 2) }}@if($vatRate > 0) ({{ number_format($vatRate, 0) }}%)@endif</td>
            <td class="num">{{ number_format($rowTotal, 2) }}</td>
        </tr>
        @endforeach
        @php $items_total = $running_total; @endphp
    </tbody>
</table>

@php
    $finalAmount = $items_total - $total_deductions + $total_additions;
    $paid_amount = company_table('vouchers')->where('ref_id', $riderInvoice->rider->id)->where('voucher_type', 'PAY')->where('billing_month', $riderInvoice->billing_month)->sum('amount');
    $balanceDue = max(0, $finalAmount - $paid_amount);
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
                <tr><td>Taxable Amount</td><td class="num">{{ \App\Helpers\Currency::format($total, 2) }}</td></tr>
                @if($riderInvoice->vat > 0)
                <tr><td>Total VAT</td><td class="num">{{ \App\Helpers\Currency::format($tax_total, 2) }}</td></tr>
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
