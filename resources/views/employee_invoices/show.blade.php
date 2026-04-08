<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Invoice #{{ $employeeInvoice->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f5f5f5; text-align: left; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h2>Employee Invoice</h2>
    <table>
        <tr>
            <th>Invoice #</th>
            <td>{{ $employeeInvoice->id }}</td>
            <th>Date</th>
            <td>{{ \Carbon\Carbon::parse($employeeInvoice->inv_date)->format('d M Y') }}</td>
        </tr>
        <tr>
            <th>Employee</th>
            <td>{{ optional($employeeInvoice->employee)->employee_id }} - {{ optional($employeeInvoice->employee)->name }}</td>
            <th>Billing Month</th>
            <td>{{ \Carbon\Carbon::parse($employeeInvoice->billing_month)->format('M Y') }}</td>
        </tr>
        <tr>
            <th>Descriptions</th>
            <td colspan="3">{{ $employeeInvoice->descriptions }}</td>
        </tr>
        <tr>
            <th>Notes</th>
            <td colspan="3">{{ $employeeInvoice->notes }}</td>
        </tr>
    </table>

    <h4>Items</h4>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Discount</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employeeInvoice->items as $item)
            <tr>
                <td>{{ optional(\App\Models\Items::find($item->item_id))->name }}</td>
                <td class="text-end">{{ $item->qty }}</td>
                <td class="text-end">{{ number_format($item->rate, 2) }}</td>
                <td class="text-end">{{ number_format($item->discount, 2) }}</td>
                <td class="text-end">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-end">Subtotal</th>
                <th class="text-end">{{ number_format($employeeInvoice->subtotal, 2) }}</th>
            </tr>
            <tr>
                <th colspan="4" class="text-end">VAT</th>
                <th class="text-end">{{ number_format($employeeInvoice->vat, 2) }}</th>
            </tr>
            <tr>
                <th colspan="4" class="text-end">Total</th>
                <th class="text-end">{{ number_format($employeeInvoice->total_amount, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>

