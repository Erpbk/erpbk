<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SIM Invoice #{{ $invoice->invoice_number ?? $invoice->id }}</title>
    <style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; }
        .invoice-box { width: 900px; margin: auto; padding: 10px; border: 1px solid #000; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #000; padding: 4px 6px; font-size: 12px; }
        th { background: #d9e1f2; font-weight: bold; }
        .num { text-align: right; }
        .print-btn { position: fixed; top: 10px; right: 10px; background: #004aad; color: #fff; border: none; padding: 8px 12px; border-radius: 3px; text-decoration:none; }
        @media print { .print-btn, .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 9999; display: flex; gap: 10px;">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
        @can('sim_invoice_payment_voucher')
        @if((int) $invoice->status !== 1)
        <a href="javascript:void(0);" data-size="lg" data-title="Create Payment Voucher" data-action="{{ route('simInvoices.paymentVoucher.create', $invoice->id) }}" class="print-btn show-modal">Payment Voucher</a>
        @endif
        @endcan
        <a href="{{ route('simInvoices.edit', $invoice->id) }}" class="print-btn">Edit</a>
        <a href="{{ route('simInvoices.index') }}" class="print-btn">Back to List</a>
    </div>

    <div class="invoice-box">
        <table>
            <tr><td colspan="4" style="text-align:center;font-weight:bold;background:#211c1d;color:#fff;font-size:18px;">SIM INVOICE</td></tr>
            <tr>
                <td><b>Invoice No:</b></td><td>{{ $invoice->invoice_number ?? 'SIMI-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}</td>
                <td><b>SIM Invoice No:</b></td><td>{{ $invoice->sim_invoice_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><b>Reference Number:</b></td><td>{{ $invoice->reference_number ?? 'N/A' }}</td>
                <td><b>Vendor:</b></td><td>{{ $invoice->vendor->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><b>Billing Month:</b></td><td>{{ date('M-Y', strtotime($invoice->billing_month)) }}</td>
                <td><b>Contact Number:</b></td><td>{{ $invoice->vendor->contact_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><b>Description:</b></td><td colspan="3">{{ $invoice->descriptions ?? 'N/A' }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <th>Sr.</th>
                <th>SIM</th>
                <th>Qty</th>
                <th>Days</th>
                <th>Rate (Monthly)</th>
                <th>Amount</th>
                <th>VAT Rate</th>
                <th>VAT Amount</th>
                <th>Total</th>
            </tr>
            @foreach($invoice->items as $key => $item)
                @php
                    $vatAmtRow = $item->tax_amount ?? 0;
                    $rowTotal = $item->total_amount ?? 0;
                    $proratedAmount = $rowTotal - $vatAmtRow;
                @endphp
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $item->sim->number ?? 'N/A' }}</td>
                    <td class="num">1</td>
                    <td class="num">{{ $item->days ?? 1 }}</td>
                    <td class="num">{{ number_format($item->rental_amount, 2) }}</td>
                    <td class="num">{{ number_format($proratedAmount, 2) }}</td>
                    <td class="num">{{ number_format($item->tax_rate ?? 0, 0) }}%</td>
                    <td class="num">{{ number_format($item->tax_amount ?? 0, 2) }}</td>
                    <td class="num">{{ number_format($item->total_amount ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </table>

        <table>
            <tr><td><b>Total Amount (before VAT):</b></td><td class="num">{{ number_format($invoice->subtotal ?? 0, 2) }}</td></tr>
            <tr><td><b>Add: VAT</b></td><td class="num">{{ number_format($invoice->vat ?? 0, 2) }}</td></tr>
            <tr><td><b>TOTAL AMOUNT:</b></td><td class="num"><b>{{ number_format($invoice->total_amount ?? 0, 2) }}</b></td></tr>
        </table>
    </div>
</body>
</html>
