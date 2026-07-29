@php
$running_total = 0;
$ledger_deductions = $ledger_deductions ?? [];
$ledger_additions = $ledger_additions ?? [];
$vatRate = $invoice_applies_vat ? $invoice_vat_rate : 0;
$qtyText = static fn ($qty) => (float) $qty == 0
? '-'
: rtrim(rtrim(number_format((float) $qty, 2), '0'), '.');
@endphp

<table class="items-table">
    <tr>
        <th rowspan="2" class="secondary-header">Sr.</th>
        <th rowspan="2" class="secondary-header">Product / Service Description</th>
        <th rowspan="2" class="secondary-header">FMO</th>
        <th rowspan="2" class="secondary-header">Qty</th>
        <th rowspan="2" class="secondary-header">Rate</th>
        <th rowspan="2" class="secondary-header">Amount</th>
        <th colspan="2" class="secondary-header">VAT</th>
        <th rowspan="2" class="accent-total">Total (In {{ \App\Helpers\Currency::code() }})</th>
    </tr>
    <tr>
        <th class="secondary-header">Rate</th>
        <th class="secondary-header">Amount</th>
    </tr>
    @foreach($riderInvoice->items as $key => $val)
    @php
    $rowAmount = round((float) $val->amount, 2);
    $vatAmtRow = $invoice_applies_vat ? round($rowAmount * $vatRate / 100, 2) : 0;
    $running_total = round($running_total + $rowAmount + $vatAmtRow, 2);
    @endphp
    <tr>
        <td>{{ $key + 1 }}</td>
        <td>{{ $val->riderInv_item }} {{ \App\Models\Items::where('id', $val->item_id)->value('name') }}</td>
        <td>{{ strtoupper(date('M\'y', strtotime($riderInvoice->billing_month))) }}</td>
        <td class="num">{{ $qtyText($val->qty) }}</td>
        <td class="num">{{ (float) $val->rate == 0 ? '-' : number_format((float) $val->rate, 2) }}</td>
        <td class="num" style="text-align: center;">{{ number_format($rowAmount, 2) }}</td>
        <td>{{ number_format($vatRate, 0) }}%</td>
        <td class="num" style="text-align: center;">{{ number_format($vatAmtRow, 2) }}</td>
        <td class="num" style="font-weight: 700;text-align: center;">{{ number_format($running_total, 2) }}</td>
    </tr>
    @endforeach
    <tr class="accent-total">
        <td colspan="3" style="text-align: right; padding: 8px;">Total Orders ({{ date('M-Y', strtotime($riderInvoice->billing_month)) }})</td>
        <td class="num">{{ number_format($totalOrders, 0) }}</td>
        <td colspan="4" style="text-align: right; padding: 8px;">ITEMS TOTAL</td>
        <td class="num" style="padding: 8px; font-size: 14px; text-align: center;">{{ number_format($items_total, 2) }}</td>
    </tr>
</table>

<table>
    <tr>
        <th colspan="5" class="secondary-header">Deductions</th>
    </tr>
    @if($rider_balance > 0)
    <tr>
        <td colspan="4" style="text-align: left;">Previous Balance (Deduction)</td>
        <td class="num">-{{ number_format(abs($rider_balance), 2) }}</td>
    </tr>
    @endif
    @forelse($ledger_deductions as $deduction)
    <tr>
        <td colspan="4" style="text-align: left;">{{ $deduction['label'] }}</td>
        <td class="num">-{{ number_format($deduction['amount'], 2) }}</td>
    </tr>
    @empty
    @if($rider_balance <= 0)
        <tr>
        <td colspan="5" style="text-align: center;">No ledger deductions for this billing month</td>
        </tr>
        @endif
        @endforelse
        <tr class="accent-total">
            <td colspan="4" style="text-align: left; padding: 8px;">Total Deductions</td>
            <td class="num" style="padding: 8px; font-size: 14px; text-align: right !important;">-{{ number_format($total_deductions, 2) }}</td>
        </tr>
</table>

@if(($rider_balance < 0) || count($ledger_additions)> 0)
    <table>
        <tr>
            <th colspan="5" class="secondary-header">Additions</th>
        </tr>
        @if($rider_balance < 0)
            <tr>
            <td colspan="4" style="text-align: left;">Previous Balance (Addition)</td>
            <td class="num" style="text-align: right !important;">+{{ number_format(abs($rider_balance), 2) }}</td>
            </tr>
            @endif
            @foreach($ledger_additions as $addition)
            <tr>
                <td colspan="4" style="text-align: left;">{{ $addition['label'] }}</td>
                <td class="num" style="text-align: right !important;">+{{ number_format($addition['amount'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="accent-total">
                <td colspan="4" style="text-align: left; padding: 8px !important;">Total Additions</td>
                <td class="num" style="padding: 8px; font-size: 14px; text-align: right !important;">+{{ number_format($total_additions, 2) }}</td>
            </tr>
    </table>
    @endif

    <table class="summary-table">
        <tr class="light-header">
            <td style="padding: 6px;">Total Amount before charges:</td>
            <td class="num" style="padding: 6px; text-align: right !important;">{{ number_format($totalBeforeTax, 2) }}</td>
        </tr>
        @if($invoice_applies_vat)
        <tr class="light-header">
            <td style="padding: 6px;">Add: VAT - {{ number_format($invoice_vat_rate, 0) }}%</td>
            <td class="num" style="padding: 6px; text-align: right !important;">{{ number_format($vatAmount, 2) }}</td>
        </tr>
        @endif
        <tr class="success-highlight">
            <td style="padding: 8px; font-size: 14px;">TOTAL AMOUNT AFTER CHARGES:</td>
            <td class="num" style="padding: 8px; font-size: 14px; text-align: right !important;">{{ number_format($finalAmount, 2) }}</td>
        </tr>
        <tr class="amount-highlight">
            <td style="padding: 6px;">Paid Amount:</td>
            <td class="num" style="padding: 6px; text-align: right !important;">{{ number_format($paid_amount, 2) }}</td>
        </tr>
        <tr class="amount-highlight">
            <td style="padding: 6px;">Balance:</td>
            <td class="num" style="padding: 6px; text-align: right !important;">{{ number_format($rider_balance_final, 2) }}</td>
        </tr>
    </table>

    <div class="footer-note">
        {{ $riderInvoice->notes ?? 'Note : If a rider\Driver\'s monthly orders are less than 400 or if they have attendance for less than 26 days or less than 10 hours of login time in a day, we will charge them half of their bike rent and mobile bill, and they will not be eligible for minimum guarantee fees.' }}
    </div>