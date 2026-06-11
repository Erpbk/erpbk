<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Inventory Assignment Contract - {{ $rider->name }}</title>
    @php
    $docRef = $contract->contract_number;
    $docDateLabel = 'Contract Date';
    $docDateValue = $contract->contract_date?->format('d M Y') ?? now()->format('d M Y');
    $statusLabel = 'Assignment';
    $statusTone = 'issue';
    @endphp
    @include('passport_handover.partials.contract_styles')
</head>
<body>
    <div class="print-toolbar">
        <button type="button" class="btn-print" onclick="window.print()">Print / Download</button>
    </div>

    <div class="document-sheet">
        @include('passport_handover.partials.contract_header')

        <div class="doc-body">
            <div class="title-block">
                <h1>Rider Inventory Assignment Contract</h1>
                <p class="subtitle">Official inventory assignment acknowledgement</p>
                <span class="status-pill {{ $statusTone }}">{{ $statusLabel }}</span>
            </div>

            <div class="section-card">
                <div class="section-card-header">Rider Details</div>
                <table class="info-table">
                    <tr><th>Full Name</th><td>{{ $rider->name }}</td></tr>
                    <tr><th>Rider ID</th><td>{{ $rider->rider_id ?? $rider->id }}</td></tr>
                    <tr><th>Emirates ID</th><td>{{ $rider->emirate_id ?? '—' }}</td></tr>
                    <tr><th>Passport No.</th><td>{{ $rider->passport ?? '—' }}</td></tr>
                    <tr><th>Phone</th><td>{{ $rider->personal_contact ?? '—' }}</td></tr>
                </table>
            </div>

            <div class="section-card">
                <div class="section-card-header">Assigned Inventory</div>
                <table class="info-table">
                    <thead>
                        <tr>
                            <th style="width:40px;text-align:center;">✓</th>
                            <th>Item Name</th>
                            <th>Assignment Date</th>
                            <th>Item Value</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $row)
                        <tr>
                            <td style="text-align:center;">☑</td>
                            <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                            <td>{{ $row->assigned_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ number_format((float) $row->amount, 2) }}</td>
                            <td><strong>Assigned</strong></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="declaration-box">
                By receiving the inventory items listed in this document, the rider acknowledges full responsibility for their safekeeping and proper use. All assigned items must be returned to the company upon request, resignation, termination, or transfer. Any item not returned, damaged beyond acceptable wear and tear, or reported as lost may be charged to the rider at the item's recorded value. The company reserves the right to recover such amounts from the rider's account, salary, settlements, or any other payable balances.
            </div>

            <div class="signature-grid">
                <div class="signature-card">
                    <div class="signature-card-inner">
                        <div class="sig-label">Company Representative</div>
                        <div class="signature-line">
                            <strong>{{ auth()->user()->name ?? 'Authorized Signatory' }}</strong>
                            <small>Signature &amp; Date</small>
                        </div>
                    </div>
                </div>
                <div class="signature-card">
                    <div class="signature-card-inner">
                        <div class="sig-label">Rider</div>
                        <div class="signature-line">
                            <strong>{{ $rider->name }}</strong>
                            <small>{{ $rider->rider_id ?? '' }} · Signature &amp; Date</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="doc-footer">
            <strong>{{ $branding['company_name'] ?? config('app.name') }}</strong>
            @if(!empty($branding['location_line'])) · {{ $branding['location_line'] }}@endif
            <br>Rider Inventory Module · Assignment Contract · {{ $contract->contract_number }}
        </div>
    </div>
</body>
</html>
