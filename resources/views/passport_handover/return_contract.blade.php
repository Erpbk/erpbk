<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passport Return Acknowledgement - {{ $history->personName() }}</title>
    @php
    $docRef = 'PHR-' . str_pad((string) $history->id, 5, '0', STR_PAD_LEFT) . '-' . ($history->return_date?->format('Y') ?? date('Y'));
    $docDateLabel = 'Return Date';
    $docDateValue = $history->return_date ? $history->return_date->format('d M Y, H:i') : now()->format('d M Y, H:i');
    $statusLabel = 'Returned';
    $statusTone = 'return';
    @endphp
    @include('passport_handover.partials.contract_styles')
</head>
<body>
    <div class="print-toolbar">
        <button type="button" class="btn-print" onclick="window.print()">
            Print / Download
        </button>
    </div>

    <div class="document-sheet">
        @include('passport_handover.partials.contract_header')

        <div class="doc-body">
            <div class="title-block">
                <h1>Passport Return Acknowledgement</h1>
                <p class="subtitle">Official passport handover return document</p>
                <span class="status-pill {{ $statusTone }}">{{ $statusLabel }}</span>
            </div>

            <div class="declaration-box">
                This document confirms that the passport listed below has been officially returned by
                <strong>{{ $history->returned_by }}</strong> to
                <strong>{{ $branding['company_name'] ?? 'the company' }}</strong>.
                Both parties acknowledge completion of the passport return process.
            </div>

            <div class="section-card">
                <div class="section-card-header">Employee / Rider Details</div>
                <table class="info-table">
                    <tr><th>Full Name</th><td>{{ $history->personName() }}</td></tr>
                    <tr><th>ID Number</th><td>{{ $history->personCode() }}</td></tr>
                    <tr><th>Person Type</th><td>{{ ucfirst($history->holder_type) }}</td></tr>
                    <tr><th>Passport Holder Name</th><td>{{ $history->holder_name }}</td></tr>
                    <tr><th>Passport Number</th><td>{{ $history->passport_number ?: '—' }}</td></tr>
                </table>
            </div>

            <div class="section-card">
                <div class="section-card-header">Original Issue Reference</div>
                <table class="info-table">
                    <tr><th>Issue Date &amp; Time</th><td>{{ $history->note_date ? $history->note_date->format('d F Y, H:i') : '—' }}</td></tr>
                    <tr><th>Handed Over By</th><td>{{ $history->handed_over_by }}</td></tr>
                    <tr><th>Originally Received By</th><td>{{ $history->received_by }}</td></tr>
                </table>
            </div>

            <div class="section-card">
                <div class="section-card-header">Return Details</div>
                <table class="info-table">
                    <tr><th>Return Date &amp; Time</th><td>{{ $history->return_date ? $history->return_date->format('d F Y, H:i') : '—' }}</td></tr>
                    <tr><th>Returned By</th><td>{{ $history->returned_by }}</td></tr>
                    <tr><th>Return Received By</th><td>{{ $history->return_received_by }}</td></tr>
                    <tr><th>Document Status</th><td><strong>{{ $statusLabel }}</strong></td></tr>
                    @if($history->remarks)
                    <tr><th>Remarks</th><td>{{ $history->remarks }}</td></tr>
                    @endif
                </table>
            </div>

            <div class="declaration-box">
                I, <strong>{{ $history->returned_by }}</strong>, confirm that the passport identified above has been
                returned to the company. The company representative
                <strong>{{ $history->return_received_by }}</strong> acknowledges receipt of the passport in good order.
            </div>

            <div class="signature-grid">
                <div class="signature-card">
                    <div class="signature-card-inner">
                        <div class="sig-label">Returned By</div>
                        <div class="signature-line">
                            <strong>{{ $history->returned_by }}</strong>
                            <small>{{ $history->holder_name }} · Signature &amp; Date</small>
                        </div>
                    </div>
                </div>
                <div class="signature-card">
                    <div class="signature-card-inner">
                        <div class="sig-label">Company Representative</div>
                        <div class="signature-line">
                            <strong>{{ $history->return_received_by }}</strong>
                            <small>Return Received By · Signature &amp; Date</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="doc-footer">
            <strong>{{ $branding['company_name'] ?? config('app.name') }}</strong>
            @if(!empty($branding['location_line'])) · {{ $branding['location_line'] }}@endif
            <br>Passport Handover Module · Return Acknowledgement · Generated {{ now()->format('d M Y') }}
        </div>
    </div>
</body>
</html>
