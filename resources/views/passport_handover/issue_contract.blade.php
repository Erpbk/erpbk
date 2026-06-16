<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passport Issue Acknowledgement - {{ $history->personName() }}</title>
    @php
    $docRef = 'PHI-' . str_pad((string) $history->id, 5, '0', STR_PAD_LEFT) . '-' . ($history->note_date?->format('Y') ?? date('Y'));
    $docDateLabel = 'Issue Date';
    $docDateValue = $history->note_date ? $history->note_date->format('d M Y, H:i') : now()->format('d M Y, H:i');
    $statusLabel = 'Issued';
    $statusTone = 'issue';
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
                <h1>Passport Issue Acknowledgement</h1>
                <p class="subtitle">Official passport handover issue document</p>
                <span class="status-pill {{ $statusTone }}">{{ $statusLabel }}</span>
            </div>

            <div class="declaration-box">
                This document confirms that the passport listed below has been officially handed over by
                <strong>{{ $branding['company_name'] ?? 'the company' }}</strong> to the named employee/rider
                for legitimate employment, visa processing, and company-related travel purposes.
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
                <div class="section-card-header">Handover Details</div>
                <table class="info-table">
                    <tr><th>Issue Date &amp; Time</th><td>{{ $history->note_date ? $history->note_date->format('d F Y, H:i') : '—' }}</td></tr>
                    <tr><th>Handed Over By</th><td>{{ $history->handed_over_by }}</td></tr>
                    <tr><th>Received By</th><td>{{ $history->received_by }}</td></tr>
                    <tr><th>Document Status</th><td><strong>{{ $statusLabel }}</strong></td></tr>
                    @if($history->remarks)
                    <tr><th>Remarks</th><td>{{ $history->remarks }}</td></tr>
                    @endif
                </table>
            </div>

            <div class="declaration-box">
                I, <strong>{{ $history->holder_name }}</strong>, acknowledge receipt of the passport detailed above.
                I agree to keep it secure, use it only for authorized purposes, and return it to the company upon
                request in accordance with company policy and applicable law.
            </div>

            <div class="signature-grid">
                <div class="signature-card">
                    <div class="signature-card-inner">
                        <div class="sig-label">Company Representative</div>
                        <div class="signature-line">
                            <strong>{{ $history->handed_over_by }}</strong>
                            <small>Handed Over By · Signature &amp; Date</small>
                        </div>
                    </div>
                </div>
                <div class="signature-card">
                    <div class="signature-card-inner">
                        <div class="sig-label">Recipient</div>
                        <div class="signature-line">
                            <strong>{{ $history->received_by }}</strong>
                            <small>{{ $history->holder_name }} · Received By · Signature &amp; Date</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="doc-footer">
            <strong>{{ $branding['company_name'] ?? config('app.name') }}</strong>
            @if(!empty($branding['location_line'])) · {{ $branding['location_line'] }}@endif
            <br>Passport Handover Module · Issue Acknowledgement · Generated {{ now()->format('d M Y') }}
        </div>
    </div>
</body>
</html>
