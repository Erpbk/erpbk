<table class="invoice-description-summary">
    <tr>
        <th class="secondary-header">Description</th>
        <th class="secondary-header">Orders</th>
        <th class="secondary-header">Total Amount</th>
    </tr>
    <tr>
        <td>{{ $riderInvoice->descriptions ?: '—' }}</td>
        <td class="num">{{ number_format($totalOrders, 0) }}</td>
        <td class="num">{{ \App\Helpers\Currency::format($items_total, 2) }}</td>
    </tr>
</table>
