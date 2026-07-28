@php
    $serviceFrom = date('d-m-y', strtotime($riderInvoice->billing_month));
    $serviceTo = date('t-m-y', strtotime($riderInvoice->billing_month));
    $branch = $riderInvoice->rider->branch ?? null;
    $branchLabel = $branch
        ? trim($branch->name . ($branch->code ? ' (' . $branch->code . ')' : ''))
        : '';
    $bikePlate = $riderInvoice->rider->bikes?->plate
        ?? $riderInvoice->bike?->plate
        ?? '';
@endphp

<table class="no-border" width="100%">
    <tr>
        <td width="33.33%" class="no-border">
            @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                <img src="{{ storage_url($settings['company_logo']) }}" width="150" alt="logo" />
            @else
                <img src="{{ URL::asset('assets/img/logo-full.png') }}" width="150" alt="logo" />
            @endif
        </td>
        <td width="66.67%" class="no-border" style="text-align: center;">
            <h4 style="margin-bottom: 10px; margin-top: 5px; font-size: 14px;">{{ ucwords($settings['company_name'] ?? '') }}</h4>
            <p style="margin-bottom: 5px; font-size: 14px; margin-top: 5px;">{{ ucwords($settings['company_address'] ?? '') }}</p>
            <p style="margin-bottom: 5px; font-size: 14px; margin-top: 5px;">TRN {{ $settings['vat_number'] ?? '' }}</p>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td colspan="4" class="primary-header" style="padding: 10px; text-align: center; font-size: 18px;">Salary Slip</td>
    </tr>
    <tr>
        <td class="label-cell">Invoice No</td>
        <td class="value-cell">{{ $invoiceNumber }}</td>
        <td class="label-cell">Invoice Date</td>
        <td class="value-cell">{{ $riderInvoice->created_at->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="label-cell">Service Period From</td>
        <td class="value-cell">{{ $serviceFrom }}</td>
        <td class="label-cell">Service Period To</td>
        <td class="value-cell">{{ $serviceTo }}</td>
    </tr>
    <tr>
        <td class="label-cell">Joining Date</td>
        <td class="value-cell">{{ $riderInvoice->rider->doj ?? '' }}</td>
        <td class="label-cell">Billing Month:</td>
        <td class="value-cell">{{ date('M-Y', strtotime($riderInvoice->billing_month)) }}</td>
    </tr>
    <tr>
        <td class="label-cell">Bike No</td>
        <td class="value-cell">{{ $bikePlate }}</td>
        <td class="label-cell">Zone</td>
        <td class="value-cell">{{ $riderInvoice->zone ?? '' }}</td>
    </tr>
    <tr>
        <td class="label-cell">Project</td>
        <td class="value-cell">{{ @$riderInvoice->rider->vendor->name }}</td>
        <td class="label-cell">Branch</td>
        <td class="value-cell">{{ $branchLabel }}</td>
    </tr>
</table>

<table>
    <tr>
        <td colspan="4" class="light-header" style="padding: 8px; text-align: center; font-size: 14px;">RIDER DETAILS</td>
    </tr>
    <tr>
        <td class="label-cell">Rider ID:</td>
        <td class="value-cell">{{ $riderInvoice->rider->rider_id }}</td>
        <td class="label-cell">Rider Status:</td>
        <td class="value-cell @if(in_array((int) ($riderInvoice->rider->status ?? 0), [3, 4, 5], true)) red @endif">{{ $riderStatusLabel }}</td>
    </tr>
    <tr>
        <td class="label-cell">Rider Name:</td>
        <td class="value-cell">{{ $riderInvoice->rider->name }}</td>
        <td class="label-cell">Working Days:</td>
        <td class="value-cell">{{ $riderInvoice->working_days }} | Off: {{ @$riderInvoice->off }}</td>
    </tr>
    <tr>
        <td class="label-cell">Mobile:</td>
        <td class="value-cell">{{ @$riderInvoice->rider->sim->number }}</td>
        <td class="label-cell">Perfect Attendance:</td>
        <td class="value-cell">{{ $riderInvoice->perfect_attendance }}</td>
    </tr>
    <tr>
        <td class="label-cell">Fleet Supervisor:</td>
        <td class="value-cell">{{ @$riderInvoice->rider->fleet_supervisor }}</td>
        <td class="label-cell">Rejection:</td>
        <td class="value-cell red">{{ @$riderInvoice->rejection }}</td>
    </tr>
    <tr>
        <td class="label-cell">Sup. Contact:</td>
        <td class="value-cell">{{ @$riderInvoice->rider->company_contact }}</td>
        <td class="label-cell">Performance:</td>
        <td class="value-cell">{{ @$riderInvoice->performance }}</td>
    </tr>
</table>

@if($riderInvoice->items && $riderInvoice->items->count() > 0)
    @include('rider_invoices.partials.invoice_items_and_totals')
@endif
