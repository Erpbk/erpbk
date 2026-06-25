<div style="overflow-x: auto; margin-top: 5px;">
    <table class="items-table">
        <thead>
            <tr>
                <th>Sr.</th>
                <th>Product / Service Description</th>
                <th>FMO</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>Amount</th>
                <th>VAT Rate</th>
                <th>VAT Amount</th>
                <th>Total ({{ \App\Helpers\Currency::code() }})</th>
            </tr>
        </thead>
        <tbody>
            @php $running_total = 0; @endphp
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
                <td class="num">{{ $key + 1 }}</td>
                <td>{{ $val->riderInv_item }} {{ \App\Models\Items::where('id', $val->item_id)->value('name') }}</td>
                <td>{{ strtoupper(date('M\'y', strtotime($riderInvoice->billing_month))) }}</td>
                <td class="num">{{ $val->qty == 0 ? '-' : $val->qty }}</td>
                <td class="num">{{ $val->rate == 0 ? '-' : number_format($val->rate, 2) }}</td>
                <td class="num">{{ number_format($val->amount, 2) }}</td>
                <td class="num">{{ number_format($vatRate, 0) }}%</td>
                <td class="num">{{ number_format($vatAmtRow, 2) }}</td>
                <td class="num">{{ number_format($running_total, 2) }}</td>
            </tr>
            @endforeach
            @php $items_total = $running_total; @endphp
            <tr class="accent-total">
                <td colspan="3" style="text-align:right; font-weight:bold;">Total Orders ({{ date('M-Y', strtotime($riderInvoice->billing_month)) }})</td>
                <td class="num">{{ number_format($totalOrders, 0) }}</td>
                <td colspan="4" style="text-align:right; font-weight:bold;">ITEMS TOTAL</td>
                <td class="num" style="font-weight:bold;">{{ number_format($items_total, 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>

@if($total_deductions > 0 || $fines > 0 || $salik > 0 || $cod > 0 || $penalty > 0 || $advance_salary > 0 || $vendor_charges > 0 || $rider_balance > 0)
<table style="margin-top: 12px;">
    <thead><tr><th colspan="5" class="secondary-header">Deductions</th></tr></thead>
    <tbody>
        @if($rider_balance > 0)<tr><td colspan="4">Previous Balance (Deduction)</td><td class="num">-{{ number_format(abs($rider_balance), 2) }}</td></tr>@endif
        @if($fines > 0)<tr><td colspan="4">RTA Fine Charges</td><td class="num">-{{ number_format($fines, 2) }}</td></tr>@endif
        @if($salik > 0)<tr><td colspan="4">Salik Charges</td><td class="num">-{{ number_format($salik, 2) }}</td></tr>@endif
        @if($cod > 0)<tr><td colspan="4">COD Amount</td><td class="num">-{{ number_format($cod, 2) }}</td></tr>@endif
        @if($penalty > 0)<tr><td colspan="4">Penalty Amount</td><td class="num">-{{ number_format($penalty, 2) }}</td></tr>@endif
        @if($advance_salary > 0)<tr><td colspan="4">Advance Loan</td><td class="num">-{{ number_format($advance_salary, 2) }}</td></tr>@endif
        @if($vendor_charges > 0)<tr><td colspan="4">Vendor Charges</td><td class="num">-{{ number_format($vendor_charges, 2) }}</td></tr>@endif
        <tr class="accent-total"><td colspan="4" style="text-align:right;">Total Deductions</td><td class="num">-{{ number_format($total_deductions, 2) }}</td></tr>
    </tbody>
</table>
@endif

@if($total_additions > 0)
<table style="margin-top: 8px;">
    <thead><tr><th colspan="5" class="secondary-header">Additions</th></tr></thead>
    <tbody>
        @if($rider_balance < 0)<tr><td colspan="4">Previous Balance (Addition)</td><td class="num">+{{ number_format(abs($rider_balance), 2) }}</td></tr>@endif
        @if($incentive > 0)<tr><td colspan="4">Incentive Amount</td><td class="num">+{{ number_format($incentive, 2) }}</td></tr>@endif
        <tr class="accent-total"><td colspan="4" style="text-align:right;">Total Additions</td><td class="num">+{{ number_format($total_additions, 2) }}</td></tr>
    </tbody>
</table>
@endif

@php
    $finalAmount = $items_total - $total_deductions + $total_additions;
    $paid_amount = company_table('vouchers')->where('ref_id', $riderInvoice->rider->id)->where('voucher_type', 'PAY')->where('billing_month', $riderInvoice->billing_month)->sum('amount');
    $rider_balance_final = $paid_amount - $finalAmount;
@endphp

<div class="financial-summary">
    <table>
        <thead><tr><th colspan="2" class="secondary-header">Financial Summary</th></tr></thead>
        <tbody>
            <tr><td style="font-weight: 600;">Subtotal (Items)</td><td class="num">{{ number_format($total, 2) }}</td></tr>
            @if($riderInvoice->vat > 0)<tr><td>VAT ({{ $vat_percentage }}%)</td><td class="num">{{ number_format($total * $vat_percentage / 100, 2) }}</td></tr>@endif
            <tr><td>Total Deductions</td><td class="num">-{{ number_format($total_deductions, 2) }}</td></tr>
            <tr><td>Total Additions</td><td class="num">+{{ number_format($total_additions, 2) }}</td></tr>
            <tr class="success-highlight"><td><strong>NET PAYABLE</strong></td><td class="num"><strong>{{ number_format($finalAmount, 2) }}</strong></td></tr>
            <tr><td>Paid Amount to Rider</td><td class="num">{{ number_format($paid_amount, 2) }}</td></tr>
            <tr><td>Rider Balance</td><td class="num">{{ number_format($rider_balance_final, 2) }}</td></tr>
        </tbody>
    </table>
</div>

<div class="grand-total-wrapper">
    <div class="grand-total-card">
        <div>TOTAL INVOICE AMOUNT</div>
        <div>{{ \App\Helpers\Currency::format($finalAmount, 2) }}</div>
    </div>
</div>

<div class="notes-section">
    <strong>Note:</strong><br>
    {{ $riderInvoice->notes ?? 'If a rider\'s monthly orders are less than 400 or they have attendance for less than 26 days or less than 10 hours of login time in a day, we will charge them half of their bike rent and mobile bill, and they will not be eligible for minimum guarantee fees.' }}
</div>

<div style="text-align: right; margin-top: 30px;">
    <br><br><br>
    <span style="display: inline-block; border-top: 2px solid #000; padding-top: 8px; font-weight: bold;">{{ $riderInvoice->rider->name }}</span>
</div>

<div class="footer-note" style="margin-top: 28px;">
    Thank you for your partnership! For queries reach: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}
</div>
