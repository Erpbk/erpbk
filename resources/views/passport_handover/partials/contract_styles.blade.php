@php
$p = $branding['primary_color'] ?? '#1e3a8a';
$s = $branding['secondary_color'] ?? '#2563eb';
$pLight = $branding['primary_light'] ?? '#eef2ff';
$pSoft = $branding['primary_soft'] ?? '#e0e7ff';
$pDark = $branding['primary_dark'] ?? '#0f172a';
$pMuted = $branding['primary_muted'] ?? '#c7d2fe';
$border = $branding['border_color'] ?? '#c7d2fe';
$onPrimary = $branding['text_on_primary'] ?? '#ffffff';
$companyName = $branding['company_name'] ?? config('app.name', 'Company');
$docRef = $docRef ?? ('PH-' . str_pad((string) ($history->id ?? 0), 5, '0', STR_PAD_LEFT) . '-' . date('Y'));
$docDateLabel = $docDateLabel ?? 'Document Date';
$docDateValue = $docDateValue ?? now()->format('d M Y, H:i');
$statusLabel = $statusLabel ?? 'Issued';
$statusTone = $statusTone ?? 'issue';
@endphp
<style>
    @page {
        size: A4 portrait;
        margin: 10mm 12mm;
    }

    :root {
        --ph-primary: {{ $p }};
        --ph-secondary: {{ $s }};
        --ph-primary-light: {{ $pLight }};
        --ph-primary-soft: {{ $pSoft }};
        --ph-primary-dark: {{ $pDark }};
        --ph-border: {{ $border }};
        --ph-on-primary: {{ $onPrimary }};
    }

    * {
        box-sizing: border-box;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
    }

    html, body {
        font-family: 'Segoe UI', Calibri, 'Helvetica Neue', Arial, sans-serif;
        color: #1e293b;
        margin: 0;
        padding: 0;
        font-size: 10pt;
        line-height: 1.5;
    }

    body {
        background-color: {{ $pLight }};
        background-image: linear-gradient(135deg, {{ $pLight }} 0%, #f8fafc 45%, #ffffff 100%);
        padding: 24px;
    }

    .print-toolbar {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        display: flex;
        gap: 10px;
    }

    .print-toolbar .btn-print {
        background: {{ $p }};
        color: {{ $onPrimary }};
        border: none;
        border-radius: 8px;
        padding: 10px 18px;
        font-size: 10pt;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.15);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .print-toolbar .btn-print:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
    }

    .document-sheet {
        max-width: 920px;
        margin: 0 auto;
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.12);
        border: 1px solid {{ $border }};
    }

    .doc-top-band {
        background-color: {{ $p }};
        background-image: linear-gradient(135deg, {{ $p }} 0%, {{ $pDark }} 100%);
        color: {{ $onPrimary }};
        padding: 22px 32px;
    }

    .doc-top-band table {
        width: 100%;
        border-collapse: collapse;
    }

    .doc-top-band td {
        vertical-align: middle;
        border: none;
        padding: 0;
        color: {{ $onPrimary }};
    }

    .company-logo {
        max-height: 58px;
        max-width: 120px;
        display: block;
        background: #fff;
        padding: 6px;
        border-radius: 8px;
    }

    .logo-fallback {
        width: 58px;
        height: 58px;
        border-radius: 10px;
        background: rgba(255,255,255,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 700;
    }

    .company-title {
        font-size: 18pt;
        font-weight: 700;
        margin: 0 0 4px;
        letter-spacing: 0.3px;
    }

    .company-meta {
        font-size: 8.5pt;
        opacity: 0.92;
        line-height: 1.45;
        margin: 0;
    }

    .doc-badge {
        background: rgba(0,0,0,0.22);
        border: 1px solid rgba(255,255,255,0.18);
        border-radius: 10px;
        padding: 12px 14px;
        text-align: right;
        font-size: 8.5pt;
        line-height: 1.45;
    }

    .doc-badge strong {
        display: block;
        font-size: 10pt;
        margin-bottom: 3px;
    }

    .accent-line {
        height: 5px;
        background-color: {{ $s }};
        background-image: linear-gradient(90deg, {{ $s }} 0%, {{ $p }} 100%);
    }

    .doc-body {
        padding: 28px 32px 34px;
    }

    .title-block {
        text-align: center;
        margin-bottom: 22px;
        padding-bottom: 18px;
        border-bottom: 1px solid {{ $border }};
    }

    .title-block h1 {
        margin: 0 0 6px;
        font-size: 17pt;
        font-weight: 700;
        color: {{ $pDark }};
        text-transform: uppercase;
        letter-spacing: 1.2px;
    }

    .title-block .subtitle {
        margin: 0;
        color: #64748b;
        font-size: 9.5pt;
    }

    .status-pill {
        display: inline-block;
        margin-top: 12px;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 8.5pt;
        font-weight: 700;
        letter-spacing: 0.4px;
        text-transform: uppercase;
    }

    .status-pill.issue {
        background: {{ $pSoft }};
        color: {{ $pDark }};
        border: 1px solid {{ $border }};
    }

    .status-pill.return {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .section-card {
        margin-bottom: 18px;
        border: 1px solid {{ $border }};
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }

    .section-card-header {
        background: {{ $p }};
        color: {{ $onPrimary }};
        padding: 10px 16px;
        font-size: 9pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .info-table th,
    .info-table td {
        padding: 11px 16px;
        border-bottom: 1px solid {{ $pMuted }};
        vertical-align: top;
        font-size: 9.5pt;
    }

    .info-table tr:last-child th,
    .info-table tr:last-child td {
        border-bottom: none;
    }

    .info-table th {
        width: 34%;
        background: {{ $pLight }};
        color: {{ $pDark }};
        font-weight: 600;
    }

    .info-table td {
        color: #0f172a;
        background: #fff;
    }

    .declaration-box {
        background: {{ $pLight }};
        border: 1px solid {{ $border }};
        border-left: 4px solid {{ $s }};
        border-radius: 10px;
        padding: 16px 18px;
        margin: 20px 0 26px;
        color: #334155;
        font-size: 9.5pt;
        line-height: 1.65;
    }

    .signature-grid {
        display: table;
        width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 22px 0;
        margin-top: 10px;
    }

    .signature-card {
        display: table-cell;
        width: 50%;
        vertical-align: top;
        border: 1px dashed {{ $border }};
        border-radius: 12px;
        padding: 16px;
        background-color: #fcfdff;
        min-height: 150px;
    }

    .signature-card-inner {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 118px;
    }

    .signature-card .sig-label {
        font-size: 8pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: {{ $p }};
        margin-bottom: 8px;
    }

    .signature-line {
        margin-top: 42px;
        border-top: 2px solid {{ $pDark }};
        padding-top: 10px;
        text-align: center;
        font-size: 9.5pt;
        color: #0f172a;
    }

    .signature-line small {
        display: block;
        margin-top: 4px;
        color: #64748b;
        font-size: 8pt;
    }

    .doc-footer {
        background: {{ $pLight }};
        border-top: 1px solid {{ $border }};
        padding: 14px 32px;
        text-align: center;
        font-size: 8pt;
        color: #64748b;
        line-height: 1.5;
    }

    .doc-footer strong {
        color: {{ $pDark }};
    }

    @media print {
        html, body {
            background: #fff !important;
            background-image: none !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }

        .print-toolbar {
            display: none !important;
        }

        .document-sheet {
            max-width: 100% !important;
            width: 100% !important;
            margin: 0 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            border: 1px solid {{ $border }} !important;
            overflow: visible !important;
        }

        .doc-top-band {
            background-color: {{ $p }} !important;
            background-image: none !important;
            color: {{ $onPrimary }} !important;
            padding: 22px 32px !important;
        }

        .doc-top-band td,
        .doc-top-band .company-title,
        .doc-top-band .company-meta,
        .doc-top-band .doc-badge {
            color: {{ $onPrimary }} !important;
        }

        .accent-line {
            background-color: {{ $s }} !important;
            background-image: none !important;
        }

        .doc-body {
            padding: 28px 32px 34px !important;
        }

        .doc-footer {
            background-color: {{ $pLight }} !important;
            padding: 14px 32px !important;
        }

        .section-card-header {
            background-color: {{ $p }} !important;
            color: {{ $onPrimary }} !important;
        }

        .info-table th {
            background-color: {{ $pLight }} !important;
            color: {{ $pDark }} !important;
        }

        .declaration-box {
            background-color: {{ $pLight }} !important;
            border-left-color: {{ $s }} !important;
        }

        .status-pill.issue {
            background-color: {{ $pSoft }} !important;
            color: {{ $pDark }} !important;
        }

        .status-pill.return {
            background-color: #ecfdf5 !important;
            color: #065f46 !important;
        }

        .signature-card {
            background-color: #fcfdff !important;
            border-color: {{ $border }} !important;
        }

        .title-block h1 {
            color: {{ $pDark }} !important;
        }

        .section-card,
        .declaration-box,
        .signature-card,
        .title-block {
            break-inside: avoid;
            page-break-inside: avoid;
        }
    }

    @media screen and (max-width: 768px) {
        body { padding: 12px; }
        .doc-top-band, .doc-body, .doc-footer { padding-left: 18px; padding-right: 18px; }
        .signature-grid {
            display: block;
        }
        .signature-card {
            display: block;
            width: 100%;
            margin-bottom: 16px;
        }
        .doc-badge { margin-top: 14px; text-align: left; }
    }
</style>
