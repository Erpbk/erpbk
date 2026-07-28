@php
    $running_total = 0;
    $totalOrders = 0;

    if (! empty($deliveryfee) && isset($deliveryfee->id)) {
        $deliveryFeeItem = $riderInvoice->items->firstWhere('item_id', $deliveryfee->id);
        if ($deliveryFeeItem && $deliveryFeeItem->qty > 0) {
            $totalOrders = $deliveryFeeItem->qty;
        }
    }
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
        $total += $val->amount;
        $total_qty += $val->qty;
        $vatRate = $riderInvoice->vat > 0 ? $vat_percentage : 0;
        $vatAmtRow = $riderInvoice->vat > 0 ? $val->amount * $vatRate / 100 : 0;
        $rowTotal = $val->amount + $vatAmtRow;
        $running_total += $rowTotal;
    @endphp
    <tr>
        <td>{{ $key + 1 }}</td>
        <td>{{ $val->riderInv_item }} {{ \App\Models\Items::where('id', $val->item_id)->value('name') }}</td>
        <td>{{ strtoupper(date('M\'y', strtotime($riderInvoice->billing_month))) }}</td>
        <td class="num">{{ $val->qty == 0 ? '-' : $val->qty }}</td>
        <td class="num">{{ $val->rate == 0 ? '-' : number_format($val->rate, 2) }}</td>
        <td class="num">{{ number_format($val->amount, 2) }}</td>
        <td>{{ number_format($vatRate, 0) }}%</td>
        <td class="num">{{ number_format($vatAmtRow, 2) }}</td>
        <td class="num" style="font-weight: 700;">{{ number_format($running_total, 2) }}</td>
    </tr>
    @endforeach
    @php $items_total = $running_total; @endphp
    <tr class="accent-total">
        <td colspan="3" style="text-align: right; padding: 8px;">Total Orders ({{ date('M-Y', strtotime($riderInvoice->billing_month)) }})</td>
        <td class="num">{{ number_format($totalOrders, 0) }}</td>
        <td colspan="4" style="text-align: right; padding: 8px;">ITEMS TOTAL</td>
        <td class="num" style="padding: 8px; font-size: 14px;">{{ number_format($items_total, 2) }}</td>
    </tr>
</table>

@php
    $finalAmount = $items_total - $total_deductions + $total_additions;
@endphp

<table>
    <tr>
        <th colspan="5" class="secondary-header">Deductions</th>
    </tr>
    @if($rider_balance > 0)
    <tr>
        <td colspan="4">Previous Balance (Deduction)</td>
        <td class="num">-{{ number_format(abs($rider_balance), 2) }}</td>
    </tr>
    @endif
    @if($fines > 0)
    <tr>
        <td colspan="4">RTA Fine Charges</td>
        <td class="num">-{{ number_format($fines, 2) }}</td>
    </tr>
    @endif
    @if($salik > 0)
    <tr>
        <td colspan="4">Salik Charges</td>
        <td class="num">-{{ number_format($salik, 2) }}</td>
    </tr>
    @endif
    @if($cod > 0)
    <tr>
        <td colspan="4">COD Amount</td>
        <td class="num">-{{ number_format($cod, 2) }}</td>
    </tr>
    @endif
    @if($penalty > 0)
    <tr>
        <td colspan="4">Penalty Amount</td>
        <td class="num">-{{ number_format($penalty, 2) }}</td>
    </tr>
    @endif
    @if($advance_salary > 0)
    <tr>
        <td colspan="4">Advance Loan</td>
        <td class="num">-{{ number_format($advance_salary, 2) }}</td>
    </tr>
    @endif
    @if($vendor_charges > 0)
    <tr>
        <td colspan="4">Vendor Charges</td>
        <td class="num">-{{ number_format($vendor_charges, 2) }}</td>
    </tr>
    @endif
    <tr class="accent-total">
        <td colspan="4" style="text-align: right; padding: 8px;">Total Deductions</td>
        <td class="num" style="padding: 8px; font-size: 14px;">-{{ number_format($total_deductions, 2) }}</td>
    </tr>
</table>

@if($incentive > 0)
<table>
    <tr>
        <th colspan="5" class="secondary-header">Additions</th>
    </tr>
    @if($rider_balance < 0)
    <tr>
        <td colspan="4">Previous Balance (Addition)</td>
        <td class="num">+{{ number_format(abs($rider_balance), 2) }}</td>
    </tr>
    @endif
    <tr>
        <td colspan="4">Incentive Amount</td>
        <td class="num">+{{ number_format($incentive, 2) }}</td>
    </tr>
    <tr class="accent-total">
        <td colspan="4" style="text-align: right; padding: 8px;">Total Additions</td>
        <td class="num" style="padding: 8px; font-size: 14px;">+{{ number_format($total_additions, 2) }}</td>
    </tr>
</table>
@endif

<table class="no-border">
    <tr>
        <td class="amount-highlight" style="padding: 8px; font-size: 13px;">
            <b>Total Invoice Amount in Words:</b> {{ $finalAmount }} {{ \App\Helpers\Currency::code() }}
        </td>
    </tr>
</table>

@php
    $totalBeforeTax = $total;
    $vatAmount = $riderInvoice->vat > 0 ? $total * $vat_percentage / 100 : 0;
    $paid_amount = 0;
    if ($riderInvoice->rider && $riderInvoice->rider->account_id) {
        $paid_amount = \App\Models\Payment::where('payee_account_id', $riderInvoice->rider->account_id)
            ->whereDate('billing_month', $riderInvoice->billing_month)
            ->sum('amount');
    }
    $rider_balance_final = $paid_amount - $finalAmount;
@endphp

<table class="summary-table">
    <tr class="light-header">
        <td style="padding: 6px;">Total Amount before charges:</td>
        <td class="num" style="padding: 6px;">{{ number_format($totalBeforeTax, 2) }}</td>
    </tr>
    @if($vatAmount > 0)
    <tr class="light-header">
        <td style="padding: 6px;">Add: VAT - {{ $vat_percentage }}%</td>
        <td class="num" style="padding: 6px;">{{ number_format($vatAmount, 2) }}</td>
    </tr>
    @endif
    <tr class="success-highlight">
        <td style="padding: 8px; font-size: 14px;">TOTAL AMOUNT AFTER CHARGES:</td>
        <td class="num" style="padding: 8px; font-size: 14px;">{{ number_format($finalAmount, 2) }}</td>
    </tr>
    <tr class="amount-highlight">
        <td style="padding: 6px;">Paid Amount to Rider:</td>
        <td class="num" style="padding: 6px;">{{ number_format($paid_amount, 2) }}</td>
    </tr>
    <tr class="amount-highlight">
        <td style="padding: 6px;">Rider Balance:</td>
        <td class="num" style="padding: 6px;">{{ number_format($rider_balance_final, 2) }}</td>
    </tr>
</table>

<div class="footer-note">
    {{ $riderInvoice->notes ?? 'Note : If a rider\'s monthly orders are less than 400 or if they have attendance for less than 26 days or less than 10 hours of login time in a day, we will charge them half of their bike rent and mobile bill, and they will not be eligible for minimum guarantee fees.' }}
</div>

<div class="sign-box">
    For Rider Name <br>
    <span class="yellow">{{ $riderInvoice->rider->name }}</span>
    <span>### Sign</span>
</div>
