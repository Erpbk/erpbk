@php
$ledger_deductions = $ledger_deductions ?? [];
$ledger_additions = $ledger_additions ?? [];
$employee_balance = $employee_balance ?? ($rider_balance ?? 0);
$employee_balance_final = $employee_balance_final ?? ($rider_balance_final ?? 0);
$items_subtotal = 0;
$items_vat = 0;
@endphp

@if($employeeInvoice->items && $employeeInvoice->items->count() > 0)
<div style="overflow-x: auto;">
    <table style="width: 100%;">
        <thead>
            <tr>
                <th style="width: 5%;">Sr.</th>
                <th style="width: 35%;">Item Description</th>
                <th style="width: 5%;" class="num">Qty</th>
                <th style="width: 10%;" class="num">Rate ({{ \App\Helpers\Currency::code() }})</th>
                <th style="width: 10%;" class="num">Discount</th>
                <th style="width: 10%;" class="num">Vat (%)</th>
                <th style="width: 10%;" class="num">Vat ({{ \App\Helpers\Currency::code() }})</th>
                <th style="width: 15%;" class="num">Amount ({{ \App\Helpers\Currency::code() }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employeeInvoice->items as $key => $item)
            @php
                $subtotal = ($item->rate * $item->qty) - $item->discount;
                $vat = ($item->tax / 100) * $subtotal;
                $items_subtotal += $subtotal;
                $items_vat += $vat;
            @endphp
            <tr>
                <td class="num">{{ $key + 1 }}</td>
                <td>{{ optional(\App\Models\Items::find($item->item_id))->name ?? 'N/A' }}</td>
                <td class="num">{{ number_format($item->qty, 0) }}</td>
                <td class="num">{{ number_format($item->rate, 2) }}</td>
                <td class="num">{{ number_format($item->discount, 2) }}</td>
                <td class="num">{{ number_format($item->tax, 2) }}</td>
                <td class="num">{{ number_format($vat, 2) }}</td>
                <td class="num">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="accent-total">
                <td colspan="7" style="text-align: right; padding: 8px;">ITEMS TOTAL</td>
                <td class="num" style="padding: 8px; font-size: 14px;">{{ number_format($items_total ?? ($items_subtotal + $items_vat), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@else
<div style="text-align: center; padding: 40px; background: #f9f9fc; border-radius: 12px;">
    <p>No items found for this invoice.</p>
</div>
@endif

<table>
    <tr>
        <th colspan="5" class="secondary-header">Deductions</th>
    </tr>
    @if($employee_balance > 0)
    <tr>
        <td colspan="4" style="text-align: left;">Previous Balance (Deduction)</td>
        <td class="num">-{{ number_format(abs($employee_balance), 2) }}</td>
    </tr>
    @endif
    @forelse($ledger_deductions as $deduction)
    <tr>
        <td colspan="4" style="text-align: left;">{{ $deduction['label'] }}</td>
        <td class="num">-{{ number_format($deduction['amount'], 2) }}</td>
    </tr>
    @empty
        @if($employee_balance <= 0)
        <tr>
            <td colspan="5" style="text-align: center;">No ledger deductions for this billing month</td>
        </tr>
        @endif
    @endforelse
    <tr class="accent-total">
        <td colspan="4" style="text-align: left; padding: 8px;">Total Deductions</td>
        <td class="num" style="padding: 8px; font-size: 14px; text-align: right !important;">-{{ number_format($total_deductions ?? 0, 2) }}</td>
    </tr>
</table>

@if(($employee_balance < 0) || count($ledger_additions) > 0)
<table>
    <tr>
        <th colspan="5" class="secondary-header">Additions</th>
    </tr>
    @if($employee_balance < 0)
    <tr>
        <td colspan="4" style="text-align: left;">Previous Balance (Addition)</td>
        <td class="num" style="text-align: right !important;">+{{ number_format(abs($employee_balance), 2) }}</td>
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
        <td class="num" style="padding: 8px; font-size: 14px; text-align: right !important;">+{{ number_format($total_additions ?? 0, 2) }}</td>
    </tr>
</table>
@endif

<table class="summary-table">
    <tr class="light-header">
        <td style="padding: 6px;">Total Amount before charges:</td>
        <td class="num" style="padding: 6px; text-align: right !important;">{{ number_format($totalBeforeTax ?? $items_subtotal, 2) }}</td>
    </tr>
    @if(!empty($invoice_applies_vat) || ($vatAmount ?? 0) > 0)
    <tr class="light-header">
        <td style="padding: 6px;">Add: VAT{{ isset($invoice_vat_rate) ? ' - ' . number_format($invoice_vat_rate, 0) . '%' : '' }}</td>
        <td class="num" style="padding: 6px; text-align: right !important;">{{ number_format($vatAmount ?? $items_vat, 2) }}</td>
    </tr>
    @endif
    <tr class="success-highlight">
        <td style="padding: 8px; font-size: 14px;">TOTAL AMOUNT AFTER CHARGES:</td>
        <td class="num" style="padding: 8px; font-size: 14px; text-align: right !important;">{{ number_format($finalAmount ?? 0, 2) }}</td>
    </tr>
    <tr class="amount-highlight">
        <td style="padding: 6px;">Paid Amount:</td>
        <td class="num" style="padding: 6px; text-align: right !important;">{{ number_format($paid_amount ?? 0, 2) }}</td>
    </tr>
    <tr class="amount-highlight">
        <td style="padding: 6px;">Balance:</td>
        <td class="num" style="padding: 6px; text-align: right !important;">{{ number_format($employee_balance_final, 2) }}</td>
    </tr>
</table>
