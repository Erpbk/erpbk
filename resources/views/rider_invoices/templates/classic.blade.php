<table class="no-border inv-header" width="100%">
    <tr>
        <td width="33.33%" class="no-border" style="vertical-align: middle;">
            @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                <img src="{{ storage_url($settings['company_logo']) }}" width="140" alt="logo" />
            @else
                <img src="{{ URL::asset('assets/img/logo-full.png') }}" width="140" alt="logo" />
            @endif
        </td>
        <td width="33.33%" class="no-border" style="text-align: center; vertical-align: middle;">
            <h4 style="margin: 0 0 6px; font-size: 14px; font-weight: 700; color: var(--inv-primary);">{{ ucwords($settings['company_name'] ?? '') }}</h4>
            <p class="inv-meta inv-meta-muted" style="margin: 2px 0;">{{ ucwords($settings['company_address'] ?? '') }}</p>
            <p class="inv-meta inv-meta-muted" style="margin: 2px 0;">TEL: {{ $settings['company_phone'] ?? '' }}</p>
            <p class="inv-meta inv-meta-muted" style="margin: 2px 0;">TRN: {{ $settings['vat_number'] ?? '' }}</p>
        </td>
        <td width="33.33%" class="no-border" style="text-align: right; vertical-align: top;">
            <h2 class="inv-doc-title">RIDER INVOICE</h2>
            <p class="inv-meta"><strong># {{ $invoiceNumber }}</strong></p>
            @php
                $previewFinal = $riderInvoice->total_amount ?? 0;
                $previewPaid = 0;
                if ($riderInvoice->rider && $riderInvoice->rider->account_id) {
                    $previewPaid = \App\Models\Payment::where('payee_account_id', $riderInvoice->rider->account_id)
                        ->whereDate('billing_month', $riderInvoice->billing_month)
                        ->sum('amount');
                }
                $previewBalance = max(0, $previewFinal - $previewPaid);
            @endphp
            <p class="inv-meta">Balance Due: <strong>{{ \App\Helpers\Currency::format($previewBalance, 2) }}</strong></p>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td width="50%" class="inv-panel" style="vertical-align: top; border: 1px solid var(--inv-border);">
            <span class="inv-section-header">Bill To</span>
            <p><strong>{{ $riderInvoice->rider->name }}</strong></p>
            <p><span class="inv-label">Rider ID:</span> {{ $riderInvoice->rider->rider_id }}</p>
            <p><span class="inv-label">Mobile:</span> {{ @$riderInvoice->rider->sim->number }}</p>
            <p><span class="inv-label">Client:</span> {{ @$riderInvoice->rider->vendor->name }}</p>
        </td>
        <td width="50%" class="inv-panel" style="vertical-align: top; border: 1px solid var(--inv-border);">
            <table class="no-border" style="width: 100%; margin: 0;">
                <tr class="no-border">
                    <td class="no-border inv-label" style="text-align: right; padding-right: 12px;">Date</td>
                    <td class="no-border">{{ $riderInvoice->created_at->format('Y-m-d') }}</td>
                </tr>
                <tr class="no-border">
                    <td class="no-border inv-label" style="text-align: right; padding-right: 12px;">Billing Month</td>
                    <td class="no-border">{{ date('M-Y', strtotime($riderInvoice->billing_month)) }}</td>
                </tr>
                <tr class="no-border">
                    <td class="no-border inv-label" style="text-align: right; padding-right: 12px;">Service Period</td>
                    <td class="no-border">
                        {{ $riderInvoice->service_period_from?->format('d-m-Y') ?? date('d-m-Y', strtotime($riderInvoice->billing_month)) }}
                        to
                        {{ $riderInvoice->service_period_to?->format('d-m-Y') ?? date('t-m-Y', strtotime($riderInvoice->billing_month)) }}
                    </td>
                </tr>
                <tr class="no-border">
                    <td class="no-border inv-label" style="text-align: right; padding-right: 12px;">Zone</td>
                    <td class="no-border">{{ $riderInvoice->zone }}</td>
                </tr>
                <tr class="no-border">
                    <td class="no-border inv-label" style="text-align: right; padding-right: 12px;">Working Days</td>
                    <td class="no-border">{{ $riderInvoice->working_days }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@include('rider_invoices.partials.invoice_description_summary')

@if($riderInvoice->items && $riderInvoice->items->count() > 0)
    @include('rider_invoices.partials.invoice_items_classic')
@else
    <p style="text-align: center; padding: 32px; color: var(--inv-text-muted);">No invoice items found for this period.</p>
@endif
