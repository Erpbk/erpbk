<table style="margin-bottom: 16px; border: none;">
    <tr>
        <td style="width: 50%; border: none; vertical-align: top;">
            @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                <img src="{{ storage_url($settings['company_logo']) }}" width="130" alt="logo" style="margin-bottom: 8px;" />
            @endif
            <h4 style="margin: 0 0 6px 0; font-size: 15px;">{{ ucwords($settings['company_name'] ?? '') }}</h4>
            <p style="margin: 2px 0; font-size: 12px;">{{ ucwords($settings['company_address'] ?? '') }}</p>
            <p style="margin: 2px 0; font-size: 12px;">TEL: {{ $settings['company_phone'] ?? '' }}</p>
            <p style="margin: 2px 0; font-size: 12px;">TRN: {{ $settings['vat_number'] ?? '' }}</p>
        </td>
        <td style="width: 50%; border: none; text-align: right; vertical-align: top;">
            <h2 style="margin: 0 0 8px 0; font-size: 28px; font-weight: 800; color: #333;">RIDER INVOICE</h2>
            <p style="margin: 4px 0; font-size: 13px;"><strong># {{ $invoiceNumber }}</strong></p>
            @php
                $previewFinal = $riderInvoice->total_amount ?? 0;
                $previewPaid = company_table('vouchers')->where('ref_id', $riderInvoice->rider->id)->where('voucher_type', 'PAY')->where('billing_month', $riderInvoice->billing_month)->sum('amount');
                $previewBalance = max(0, $previewFinal - $previewPaid);
            @endphp
            <p style="margin: 4px 0; font-size: 13px;">Balance Due: <strong>{{ \App\Helpers\Currency::format($previewBalance, 2) }}</strong></p>
        </td>
    </tr>
</table>

<table style="margin-bottom: 14px;">
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <strong style="display:block; margin-bottom: 6px; background: #444; color: #fff; padding: 6px 8px;">Bill To</strong>
            <p style="margin: 4px 0;"><strong>{{ $riderInvoice->rider->name }}</strong></p>
            <p style="margin: 4px 0;">Rider ID: {{ $riderInvoice->rider->rider_id }}</p>
            <p style="margin: 4px 0;">Mobile: {{ @$riderInvoice->rider->sim->number }}</p>
            <p style="margin: 4px 0;">Client: {{ @$riderInvoice->rider->vendor->name }}</p>
        </td>
        <td style="width: 50%; vertical-align: top;">
            <table class="no-border" style="width: 100%; margin: 0;">
                <tr class="no-border"><td class="no-border" style="text-align:right; padding-right: 12px;"><strong>Date</strong></td><td class="no-border">{{ $riderInvoice->created_at->format('Y-m-d') }}</td></tr>
                <tr class="no-border"><td class="no-border" style="text-align:right; padding-right: 12px;"><strong>Billing Month</strong></td><td class="no-border">{{ date('M-Y', strtotime($riderInvoice->billing_month)) }}</td></tr>
                <tr class="no-border"><td class="no-border" style="text-align:right; padding-right: 12px;"><strong>Zone</strong></td><td class="no-border">{{ $riderInvoice->zone }}</td></tr>
                <tr class="no-border"><td class="no-border" style="text-align:right; padding-right: 12px;"><strong>Working Days</strong></td><td class="no-border">{{ $riderInvoice->working_days }}</td></tr>
            </table>
        </td>
    </tr>
</table>

@if($riderInvoice->items && $riderInvoice->items->count() > 0)
@include('rider_invoices.partials.invoice_items_classic')
@else
<div style="text-align: center; padding: 40px;"><p>No invoice items found for this period.</p></div>
@endif
