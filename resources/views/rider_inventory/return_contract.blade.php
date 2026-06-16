<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Inventory Return Contract - {{ $rider->name }}</title>
    @php
    $docRef = $contract->contract_number;
    $docDateLabel = 'Contract Date';
    $docDateValue = $contract->contract_date?->format('d M Y') ?? now()->format('d M Y');
    $statusLabel = 'Return';
    $statusTone = 'return';
    $totalAssigned = (int) ($contract->total_items ?? $allItems->count());
    $totalReturned = (int) ($contract->total_returned ?? $returnedItems->count());
    $totalLost = (int) ($contract->total_lost ?? $lostItems->count());
    $totalChargeable = (float) ($contract->total_chargeable_amount ?? $lostItems->sum('amount'));
    @endphp
    @include('passport_handover.partials.contract_styles')
</head>
<body>
    <div class="print-toolbar">
        <button type="button" class="btn-print" onclick="window.print()">Print / Download</button>
        <a href="{{ route('RiderInventory.show', $rider->id) }}" class="btn-print" style="text-decoration:none;display:inline-block;">Back to Inventory</a>
    </div>

    <div class="document-sheet">
        @include('passport_handover.partials.contract_header')

        <div class="doc-body">
            <div class="title-block">
                <h1>Rider Inventory Return Contract</h1>
                <p class="subtitle">Official inventory return and settlement document</p>
                <span class="status-pill {{ $statusTone }}">{{ $statusLabel }}</span>
            </div>

            <div class="section-card">
                <div class="section-card-header">Rider Details</div>
                <table class="info-table">
                    <tr><th>Full Name</th><td>{{ $rider->name }}</td></tr>
                    <tr><th>Rider ID</th><td>{{ $rider->rider_id ?? $rider->id }}</td></tr>
                    <tr><th>Contract Number</th><td><strong>{{ $contract->contract_number }}</strong></td></tr>
                </table>
            </div>

            @if($returnedItems->isNotEmpty())
            <div class="section-card">
                <div class="section-card-header">Returned Items</div>
                <table class="info-table">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Item Name</th>
                            <th style="text-align:left;">Assignment Date</th>
                            <th style="text-align:left;">Item Value</th>
                            <th style="text-align:left;">Return Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($returnedItems as $row)
                        <tr>
                            <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                            <td>{{ $row->assigned_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ number_format((float) $row->amount, 2) }}</td>
                            <td style="white-space: nowrap;">{{ $row->return_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if($lostItems->isNotEmpty())
            <div class="section-card">
                <div class="section-card-header">Lost Items</div>
                <table class="info-table">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Item Name</th>
                            <th style="text-align:left;">Assignment Date</th>
                            <th style="text-align:left;">Item Value</th>
                            <th style="text-align:left;">Loss Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lostItems as $row)
                        <tr>
                            <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                            <td>{{ $row->assigned_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ number_format((float) $row->amount, 2) }}</td>
                            <td style="white-space: nowrap;">{{ $row->loss_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div class="section-card">
                <div class="section-card-header">Return Summary</div>
                <table class="info-table">
                    <tr><th>Total Items on Contract</th><td>{{ $totalAssigned }}</td></tr>
                    <tr><th>Total Returned Items</th><td>{{ $totalReturned }}</td></tr>
                    <tr><th>Total Lost Items</th><td>{{ $totalLost }}</td></tr>
                    <tr><th>Total Chargeable Amount</th><td><strong>{{ number_format($totalChargeable, 2) }}</strong></td></tr>
                </table>
            </div>

            @if($totalLost > 0)
            <div class="declaration-box">
                The rider acknowledges that the above lost items have been charged to their account at the recorded inventory value and an Inventory Loss voucher has been generated accordingly.
            </div>
            @endif

            @if($contract->remarks)
            <div class="declaration-box">
                <strong>Remarks:</strong> {{ $contract->remarks }}
            </div>
            @endif

            <div class="signature-grid">
                <div class="signature-card">
                    <div class="signature-card-inner">
                        <div class="sig-label">Rider</div>
                        <div class="signature-line">
                            <strong>{{ $rider->name }}</strong>
                            <small>Signature &amp; Date</small>
                        </div>
                    </div>
                </div>
                <div class="signature-card">
                    <div class="signature-card-inner">
                        <div class="sig-label">Company Representative</div>
                        <div class="signature-line">
                            <strong>{{ $contract->generatedByUser->name ?? auth()->user()->name ?? 'Authorized Signatory' }}</strong>
                            <small>Signature &amp; Date</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="doc-footer">
            <strong>{{ $branding['company_name'] ?? config('app.name') }}</strong>
            @if(!empty($branding['location_line'])) · {{ $branding['location_line'] }}@endif
            <br>Rider Inventory Module · Return Contract · {{ $contract->contract_number }}
        </div>
    </div>
</body>
</html>
