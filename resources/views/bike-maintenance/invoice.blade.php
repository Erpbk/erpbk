<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bike Maintenance Invoice #{{ $maintenance->id }}</title>

</head>



<body>

    @include('invoices.partials.tax_invoice_styles')

    @include('invoices.partials.tax_invoice_pdf_styles')
    @php
        $settings = company_table('settings')->pluck('value', 'name')->toArray();
        $currency = \App\Helpers\Currency::code();
        $invoiceTitle = 'MAINTENANCE BILL';
        $invoiceNumber = 'MA-' . $maintenance->id;
        $invoiceDateLabel = $maintenance->maintenance_date
            ? $maintenance->maintenance_date->format('d M Y')
            : '';
        $billingLabel = $maintenance->billing_month
            ? $maintenance->billing_month->format('M Y')
            : '';
        $items = $maintenance->maintenanceItems ?? collect();
        $subtotalAmount = (float) ($maintenance->subtotal ?? $items->sum(function ($i) {
            return (float) $i->total_amount - (float) ($i->vat_amount ?? 0);
        }));
        $vatAmt = (float) ($maintenance->vat_total ?? $items->sum('vat_amount'));
        $totalAmt = (float) ($maintenance->total_cost ?? $items->sum('total_amount'));
        $maintenance_km = max(0, (float) ($maintenance->bike?->maintenance_km ?? 0));
        $noteCards = collect([
            $maintenance->description ? ['title' => 'Additional Notes', 'body' => $maintenance->description] : null,
        ])->filter()->values();
        $noteGridClass = match ($noteCards->count()) {
            1 => 'one',
            3 => 'three',
            default => '',
        };
        $partyName = $maintenance->rider
            ? ($maintenance->rider->rider_id . '-' . $maintenance->rider->name)
            : ($maintenance->rentalCompany->name ?? 'No User Assigned');
    @endphp

    @if(empty($isPdf))
    <div class="controls no-print">
        <a href="{{ route('bike-maintenance.invoice.download', $maintenance) }}" class="action-btn" target="_blank" rel="noopener">
            <i class="ti ti-download"></i><span>Download</span>
        </a>
        <button type="button" class="action-btn js-print-modal-content">
            <i class="ti ti-printer"></i><span>Print</span>
        </button>
    </div>
    @endif

    <div class="invoice-box"@if(!empty($invPdf['box'])) style="{{ $invPdf['box'] }}"@endif>
        <div class="band"></div>
        <div class="sheet"@if(!empty($invPdf['sheet'])) style="{{ $invPdf['sheet'] }}"@endif>
            @include('invoices.partials.tax_invoice_header', [
                'settings' => $settings,
                'invoiceTitle' => $invoiceTitle,
                'invoiceNumber' => $invoiceNumber,
                'invoiceDateLabel' => $invoiceDateLabel,
                'billingLabel' => $billingLabel,
            ])

            <div class="parties"@if(!empty($invPdf['parties'])) style="{{ $invPdf['parties'] }}"@endif>
                    <div class="party"@if(!empty($invPdf['partyFirst'])) style="{{ $invPdf['partyFirst'] }}"@endif>
                        <h3 class="party-title"@if(!empty($invPdf['partyTitle'])) style="{{ $invPdf['partyTitle'] }}"@endif>Bike Details</h3>
                        <p class="party-name"@if(!empty($invPdf['partyName'])) style="{{ $invPdf['partyName'] }}"@endif>{{ $maintenance->bike->emirates ?? '' }}-{{ $maintenance->bike->plate ?? '' }}</p>
                        <div class="party-grid">
                            @if($maintenance->rider)
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Leasing Company</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $maintenance->bike?->LeasingCompany?->name ?? '—' }}</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Rider</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $partyName }}</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Rider Contact</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $maintenance->rider->company_contact ?? $maintenance->bike?->rider?->company_contact ?? '—' }}</span>
                            </div>
                            @elseif($maintenance->rentalCompany)
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>User</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $maintenance->rentalCompany->name ?? '—' }}</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Contact</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $maintenance->rentalCompany->company_contact ?? '—' }}</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Address</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $maintenance->rentalCompany->address ?? '—' }}</span>
                            </div>
                            @else
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Assigned To</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>No User Assigned</span>
                            </div>
                            @endif
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Garage</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $maintenance->garage?->name ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="party alt"@if(!empty($invPdf['partyAlt'])) style="{{ $invPdf['partyAlt'] }}"@endif>
                        <h3 class="party-title"@if(!empty($invPdf['partyTitle'])) style="{{ $invPdf['partyTitle'] }}"@endif>Bill Info</h3>
                        <div class="party-grid" style="margin-top: 4px;">
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Created By</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $maintenance->createdBy->name ?? 'System' }}</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Month</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $billingLabel !== '' ? $billingLabel : '—' }}</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Currency</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $currency }}</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Previous KM</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ number_format($maintenance->previous_km ?? 0, 0) }} KM</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Current KM</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ number_format($maintenance->current_km ?? 0, 0) }} KM</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Next Service</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ number_format($maintenance_km + (float) ($maintenance->current_km ?? 0), 2) }} KM</span>
                            </div>
                        </div>
                    </div>
            </div>

            @if(($maintenance->overdue_km ?? 0) > 0)
            <div class="desc"@if(!empty($invPdf['desc'])) style="{{ $invPdf['desc'] }}"@endif>
                <span class="t"@if(!empty($invPdf['descTitle'])) style="{{ $invPdf['descTitle'] }}"@endif>Overdue</span>
                <p @if(!empty($invPdf['descBody'])) style="{{ $invPdf['descBody'] }}"@endif>
                    Overdue KM: {{ number_format($maintenance->overdue_km ?? 0, 1) }} —
                    Cost/KM: {{ \App\Helpers\Currency::format($maintenance->overdue_cost_per_km ?? 0, 2) }}
                </p>
            </div>
            @endif

            @if($items->count() > 0)
            @php
                $riderItems = $items->where('charge_to','User');
                $companyItems = $items->where('charge_to','Company');
            @endphp
            <div class="tbl-wrap">
                <table class="items">
                    <thead>
                        <tr>
                            <th class="col-sr">#</th>
                            <th class="col-desc">Description</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>VAT</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowNum = 0; @endphp
                        @if($riderItems->count() > 0)
                        <tr>
                            <td colspan="6" style="text-align:center;font-weight:700;background:var(--blue-soft);color:var(--blue);">User Items</td>
                        </tr>
                        @foreach($riderItems as $item)
                        @php $rowNum++; @endphp
                        <tr>
                            <td class="col-sr">{{ $rowNum }}</td>
                            <td class="col-desc">{{ $item->item_name }}</td>
                            <td>{{ number_format($item->quantity, 2) }}</td>
                            <td>{{ number_format($item->rate, 2) }}</td>
                            <td>{{ number_format($item->vat, 2) }}%</td>
                            <td class="total-cell">{{ number_format($item->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                        @endif
                        @if($companyItems->count() > 0)
                        <tr>
                            <td colspan="6" style="text-align:center;font-weight:700;background:var(--blue-soft);color:var(--blue);">Company Items</td>
                        </tr>
                        @foreach($companyItems as $item)
                        @php $rowNum++; @endphp
                        <tr>
                            <td class="col-sr">{{ $rowNum }}</td>
                            <td class="col-desc">{{ $item->item_name }}</td>
                            <td>{{ number_format($item->quantity, 2) }}</td>
                            <td>{{ number_format($item->rate, 2) }}</td>
                            <td>{{ number_format($item->vat, 2) }}%</td>
                            <td class="total-cell">{{ number_format($item->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            @include('invoices.partials.tax_invoice_totals_notes', [
                'partyNote' => null,
                'partyNoteLabel' => 'Invoice Note',
                'subtotalAmount' => $subtotalAmount,
                'vatAmount' => $vatAmt,
                'totalAmount' => $totalAmt,
                'currency' => $currency,
                'paidAmount' => 0,
                'balanceAmount' => $totalAmt,
            ])
            @else
            <div class="empty">No maintenance items recorded.</div>
            @endif

            @if($maintenance->attachment)
            <div class="desc no-print">
                <span class="t"@if(!empty($invPdf['descTitle'])) style="{{ $invPdf['descTitle'] }}"@endif>Attachment</span>
                <p><a href="{{ storage_url($maintenance->attachment) }}" target="_blank">View Attachment</a></p>
            </div>
            @endif

            @include('invoices.partials.tax_invoice_footnotes', [
                'noteCards' => $noteCards,
                'noteGridClass' => $noteGridClass,
            ])

            @include('invoices.partials.tax_invoice_footer', ['settings' => $settings])
        </div>
    </div>

    @include('invoices.partials.tax_invoice_print_script', ['isPdf' => $isPdf ?? null])
</body>
</html>
