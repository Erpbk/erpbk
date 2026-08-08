<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Inventory Clearance Certificate - {{ $rider->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 12mm 14mm;
            background: #fff;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Calibri, 'Helvetica Neue', Arial, sans-serif;
            color: #1f2937;
            font-size: 10pt;
            line-height: 1.45;
            background: #e5e7eb;
        }

        body {
            padding: 20px;
        }

        .print-toolbar {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1000;
            display: flex;
            gap: 8px;
        }

        .print-toolbar .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 10pt;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
        }

        .btn-print {
            background: #16a34a;
            color: #fff;
        }

        .btn-back {
            background: #fff;
            color: #334155;
            border: 1px solid #cbd5e1 !important;
        }

        .certificate-wrap {
            width: 210mm;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 14mm;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        .print-sheet {
            width: 100%;
            border-collapse: collapse;
        }

        .print-sheet > thead > tr > th,
        .print-sheet > tbody > tr > td {
            padding: 0;
            border: 0;
            vertical-align: top;
            text-align: left;
            font-weight: inherit;
        }

        .cert-header {
            display: grid;
            grid-template-columns: 90px 1fr 90px;
            gap: 12px;
            align-items: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
        }

        .cert-logo {
            max-width: 84px;
            max-height: 64px;
            object-fit: contain;
        }

        .logo-fallback {
            width: 64px;
            height: 64px;
            border-radius: 10px;
            background: #0f172a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
        }

        .cert-company {
            text-align: center;
        }

        .cert-company h1 {
            margin: 0 0 4px;
            font-size: 15pt;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #0f172a;
        }

        .cert-company p {
            margin: 0;
            font-size: 8.5pt;
            color: #64748b;
            line-height: 1.4;
        }

        .cert-qr img {
            width: 78px;
            height: 78px;
            display: block;
            margin-left: auto;
        }

        .cert-title {
            text-align: center;
            margin: 0 0 16px;
            padding: 8px 0;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
        }

        .cert-title h2 {
            margin: 0;
            font-size: 14pt;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #0f172a;
        }

        .cert-title .meta {
            margin-top: 4px;
            font-size: 8.5pt;
            color: #64748b;
        }

        .section-title {
            margin: 0 0 8px;
            font-size: 10pt;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 24px;
            margin-bottom: 16px;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #fafafa;
        }

        .info-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 8px;
            font-size: 9pt;
        }

        .info-row .label {
            color: #64748b;
            font-weight: 600;
        }

        .info-row .value {
            color: #111827;
            font-weight: 600;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 8.5pt;
        }

        .items-table thead th {
            background: #f3f4f6;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            font-size: 7.5pt;
            padding: 7px 8px;
            border: 1px solid #e5e7eb;
            text-align: left;
        }

        .items-table tbody td {
            border: 1px solid #e5e7eb;
            padding: 7px 8px;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .items-table tbody tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .item-cell {
            font-weight: 600;
            white-space: nowrap;
        }

        .details-cell,
        .remarks-cell {
            white-space: pre-line;
            color: #374151;
            line-height: 1.35;
        }

        .check {
            color: #16a34a;
            font-size: 14px;
            font-weight: 700;
        }

        .closing-balance-card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-bottom: 16px;
            overflow: hidden;
            max-width: 340px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .closing-balance-card h4 {
            margin: 0;
            padding: 7px 10px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            font-size: 8.5pt;
            font-weight: 700;
            color: #0f172a;
        }

        .closing-balance-card .body {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
        }

        .closing-balance-card .label {
            font-size: 9pt;
            color: #64748b;
            font-weight: 600;
        }

        .closing-balance-card .amount {
            font-size: 12pt;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }

        .signatures-section {
            break-inside: avoid;
            page-break-inside: avoid;
            -webkit-column-break-inside: avoid;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 8px;
            margin-bottom: 12px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .sig-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px 8px 8px;
            text-align: center;
            min-height: 100px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .sig-box .role {
            font-size: 7.5pt;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .sig-box .line {
            border-bottom: 1px solid #94a3b8;
            height: 28px;
            margin: 0 4px 6px;
        }

        .sig-box .name {
            font-size: 7.5pt;
            font-weight: 600;
            color: #111827;
            min-height: 16px;
        }

        .sig-box .date {
            font-size: 7pt;
            color: #94a3b8;
        }

        .cert-footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8pt;
            color: #64748b;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        @media print {
            html,
            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .print-toolbar {
                display: none !important;
            }

            .certificate-wrap {
                width: auto;
                min-height: 0;
                margin: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 0;
                background: #fff !important;
            }

            .print-sheet,
            .print-sheet > thead > tr > th,
            .print-sheet > tbody > tr > td {
                background: #fff !important;
            }

            .print-sheet > thead {
                display: table-header-group;
            }

            .print-sheet > tbody {
                display: table-row-group;
            }

            .cert-header {
                margin-bottom: 10px;
                padding-bottom: 8px;
                background: #fff !important;
            }

            .signatures-section,
            .signatures,
            .sig-box,
            .closing-balance-card,
            .cert-footer,
            .items-table tbody tr {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>
@php
    $companyName = $branding['company_name'] ?? config('app.name', 'Company');
    $qrPayload = urlencode(($docRef ?? '') . '|' . ($rider->rider_id ?? $rider->id) . '|' . ($rider->name ?? ''));
    $project = optional($rider->vendor)->name ?? optional($rider->customer)->name ?? '';
    $joinDate = !empty($rider->doj) ? \Carbon\Carbon::parse($rider->doj)->format('d M Y') : '';
    $lastWorkingDay = $lastWorkingDay ?? '';
    $contactNo = $contactNo ?? '';
@endphp

<div class="print-toolbar">
    <button type="button" class="btn btn-print" onclick="window.print()">
        Print Certificate
    </button>
    <a href="{{ route('rider.inventory', $rider->id) }}" class="btn btn-back">Back</a>
</div>

<div class="certificate-wrap">
    <table class="print-sheet">
        <thead>
            <tr>
                <th>
                    <div class="cert-header">
                        <div>
                            @if(!empty($branding['logo_src']))
                                <img src="{{ $branding['logo_src'] }}" alt="{{ $companyName }}" class="cert-logo">
                            @elseif(!empty($branding['logo_url']))
                                <img src="{{ $branding['logo_url'] }}" alt="{{ $companyName }}" class="cert-logo">
                            @else
                                <div class="logo-fallback">{{ strtoupper(substr($companyName, 0, 1)) }}</div>
                            @endif
                        </div>
                        <div class="cert-company">
                            <h1>{{ $companyName }}</h1>
                            <p>
                                @if(!empty($branding['address'])){{ $branding['address'] }}<br>@endif
                                @if(!empty($branding['location_line'])){{ $branding['location_line'] }}<br>@endif
                                @if(!empty($branding['phone']))Tel: {{ $branding['phone'] }}@endif
                                @if(!empty($branding['phone']) && !empty($branding['email'])) · @endif
                                @if(!empty($branding['email'])){{ $branding['email'] }}@endif
                            </p>
                        </div>
                        <div class="cert-qr">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ $qrPayload }}" alt="QR">
                        </div>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="cert-title">
                        <h2>Rider Inventory Clearance Certificate</h2>
                        <div class="meta">{{ $docRef }} · {{ now()->format('d M Y') }}</div>
                    </div>

                    <h3 class="section-title">Rider Information</h3>
                    <div class="info-grid">
                        <div class="info-row"><span class="label">Rider ID</span><span class="value">{{ $rider->rider_id ?? $rider->id }}</span></div>
                        <div class="info-row"><span class="label">Project / Vendor</span><span class="value">{{ $project }}</span></div>
                        <div class="info-row"><span class="label">Rider Name</span><span class="value">{{ $rider->name }}</span></div>
                        <div class="info-row"><span class="label">Fleet Supervisor</span><span class="value">{{ $rider->fleet_supervisor ?: '' }}</span></div>
                        <div class="info-row"><span class="label">Contact No.</span><span class="value">{{ $contactNo }}</span></div>
                        <div class="info-row"><span class="label">Join Date</span><span class="value">{{ $joinDate }}</span></div>
                        <div class="info-row"><span class="label">Emirates ID</span><span class="value">{{ $rider->emirate_id ?: '' }}</span></div>
                        <div class="info-row"><span class="label">Last Working Day</span><span class="value">{{ $lastWorkingDay }}</span></div>
                        <div class="info-row"><span class="label">Clearance Date</span><span class="value">{{ now()->format('d M Y') }}</span></div>
                        <div class="info-row"><span class="label">Generated By</span><span class="value">{{ auth()->user()->name ?? '' }}</span></div>
                    </div>

                    <h3 class="section-title">Inventory Items</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width:36px;">#</th>
                                <th style="width:140px;">Item</th>
                                <th>Details</th>
                                <th style="width:70px; text-align:center;">Assigned</th>
                                <th style="width:70px; text-align:center;">Returned</th>
                                <th style="width:180px;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($certificateRows as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><div class="item-cell">{{ $row['item'] }}</div></td>
                                    <td class="details-cell">{{ $row['details'] ?? '' }}</td>
                                    <td style="text-align:center;">
                                        @if($row['assigned'])
                                            <span class="check">✓</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        @if($row['returned'])
                                            <span class="check">✓</span>
                                        @endif
                                    </td>
                                    <td class="remarks-cell">{{ $row['remarks'] ?? '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align:center; color:#94a3b8; padding:16px;">No inventory or linked assets found for this rider.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="closing-balance-card">
                        <h4>Account Closing Balance</h4>
                        <div class="body">
                            <span class="label">Closing Balance</span>
                            <span class="amount">{{ $currencySymbol }} {{ number_format((float) $accountClosingBalance, 2) }}</span>
                        </div>
                    </div>

                    <div class="signatures-section">
                        <h3 class="section-title">Approvals &amp; Signatures</h3>
                        <div class="signatures">
                            @foreach([
                                'Accounts',
                                'Fleet Supervisor',
                                'Rider',
                            ] as $role)
                                <div class="sig-box">
                                    <div class="role">{{ $role }}</div>
                                    <div class="line"></div>
                                    <div class="name">
                                        @if($role === 'Rider')
                                            {{ $rider->name }}
                                        @elseif($role === 'Fleet Supervisor')
                                            {{ $rider->fleet_supervisor ?: '' }}
                                        @endif
                                    </div>
                                    <div class="date">Date: ____________</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="cert-footer">
                        This is a system generated document and does not require company stamp.<br>
                        {{ $companyName }}
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>
