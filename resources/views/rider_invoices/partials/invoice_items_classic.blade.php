@php
    $vatRate = $invoice_applies_vat ? $invoice_vat_rate : 0;
    $qtyText = static fn ($qty) => (float) $qty == 0
        ? '-'
        : rtrim(rtrim(number_format((float) $qty, 2), '0'), '.');
@endphp

<div class="tbl-wrap">
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
</div>

@if(($total_deductions ?? 0) > 0 || ($total_additions ?? 0) > 0)
<div class="tbl-wrap">
<table class="items-table" style="margin-top: 0; margin-bottom: 0;">
    <tr>
        <td style="border: none; padding-left: 0;">
            @if(($total_deductions ?? 0) > 0)
            <p style="margin: 8px 0 4px;"><strong>Deductions:</strong> -{{ number_format($total_deductions, 2) }}</p>
            @endif
            @if(($total_additions ?? 0) > 0)
            <p style="margin: 4px 0;"><strong>Additions:</strong> +{{ number_format($total_additions, 2) }}</p>
            @endif
        </td>
    </tr>
</table>
</div>
@endif
