<!-- Header: Logo + Company + Title (modern card style) -->
<table style="margin-bottom: 20px; border: none; background: transparent;">
    <tr style="border: none;">
        <td style="width: 33%; border: none !important; vertical-align: middle;">
            @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                <img src="{{ storage_url($settings['company_logo']) }}" width="150" alt="logo" />
            @endif
        </td>
        <td style="width: 34%; text-align: center; align-content: center; border: none !important;">
            <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight:700;">{{ ucwords($settings['company_name']) ?? '' }}</h4>
            <p style="margin: 3px 0; font-size: 12px;">{{ ucwords($settings['company_address']) ?? '' }}</p>
            <p style="margin: 3px 0; font-size: 12px;">TEL: {{ $settings['company_phone'] ?? '' }}</p>
            <p style="margin: 3px 0; font-size: 12px;">TRN: {{ $settings['vat_number'] ?? '' }}</p>
        </td>
        <td style="width: 33%; text-align: center; align-content: center; border: none !important;">
            <h2 style="margin: 0; font-weight: 800; color: #004aad; font-size: 24px;">RIDER INVOICE</h2>
        </td>
    </tr>
</table>

<div class="flex-row-cards">
    <div class="rider-card">
        <div class="card-header"><strong>Rider Details</strong></div>
        <div class="details-grid">
            <span class="detail-label">Rider ID:</span><span class="detail-value">{{ $riderInvoice->rider->rider_id }}</span>
            <span class="detail-label">Rider Name:</span><span class="detail-value">{{ $riderInvoice->rider->name }}</span>
            <span class="detail-label">Rider Status:</span>
            <span class="detail-value" @if(in_array($riderInvoice->rider->status, [3,4,5])) style="color:red;" @endif>{{ App\Helpers\General::RiderStatus($riderInvoice->rider->status) }}</span>
            <span class="detail-label">Mobile:</span><span class="detail-value">{{ @$riderInvoice->rider->sim->number }}</span>
            <span class="detail-label">Joining Date:</span><span class="detail-value">{{ $riderInvoice->rider->doj }}</span>
            <span class="detail-label">Client:</span><span class="detail-value">{{ @$riderInvoice->rider->vendor->name }}</span>
            <span class="detail-label">Fleet Supervisor:</span><span class="detail-value">{{ @$riderInvoice->rider->fleet_supervisor }}</span>
            <span class="detail-label">Sup. Contact:</span><span class="detail-value">{{ @$riderInvoice->rider->company_contact }}</span>
            <span class="detail-label">Working Days:</span><span class="detail-value">{{ $riderInvoice->working_days }} | Off: {{ @$riderInvoice->off }}</span>
            <span class="detail-label">Perfect Attendance:</span><span class="detail-value">{{ $riderInvoice->perfect_attendance }}</span>
            <span class="detail-label">Rejection:</span><span class="detail-value red">{{ @$riderInvoice->rejection }}</span>
            <span class="detail-label">Performance:</span><span class="detail-value">{{ @$riderInvoice->performance }}</span>
        </div>
    </div>
    <div class="details-card">
        <div class="card-header"><strong>Invoice Summary</strong></div>
        <div class="details-grid">
            <span class="detail-label">Invoice No:</span><span class="detail-value">{{ $invoiceNumber }}</span>
            <span class="detail-label">Invoice Date:</span><span class="detail-value">{{ $riderInvoice->created_at->format('d/m/Y') }}</span>
            <span class="detail-label">Billing Month:</span><span class="detail-value">{{ date('M-Y', strtotime($riderInvoice->billing_month)) }}</span>
            <span class="detail-label">Service Period:</span><span class="detail-value">{{ date('d-m-y', strtotime($riderInvoice->billing_month)) }} to {{ date('t-m-y', strtotime($riderInvoice->billing_month)) }}</span>
            <span class="detail-label">Zone:</span><span class="detail-value">{{ $riderInvoice->zone }}</span>
            <span class="detail-label">Bike No:</span><span class="detail-value">{{ @$riderInvoice->bike->plate }}</span>
            <span class="detail-label">Road Permit No:</span><span class="detail-value">—</span>
            <span class="detail-label">Insurance Policy No:</span><span class="detail-value">—</span>
        </div>
    </div>
</div>

@if($riderInvoice->items && $riderInvoice->items->count() > 0)
@include('rider_invoices.partials.invoice_items_and_totals')
@else
<div style="text-align: center; padding: 40px;"><p>No invoice items found for this period.</p></div>
@endif
