{{-- Unified screen + PDF shell styles (table layout = DomPDF-safe) --}}
@php
    $invoiceBrand = $brand ?? ($companyBrand ?? []);
    $invBlue = $invoiceBrand['primary_color'] ?? '#004aad';
    $invBlue2 = $invoiceBrand['secondary_color'] ?? ($invoiceBrand['primary_dark'] ?? '#1a5fc4');
    $invBlueSoft = $invoiceBrand['primary_soft'] ?? ($invoiceBrand['primary_light'] ?? '#eef4fc');
    $invBlueLine = $invoiceBrand['border_color'] ?? '#c5d8f0';
    $invBlueRgb = $invoiceBrand['primary_rgb'] ?? '0, 74, 173';
    $isPdfMode = !empty($isPdf);
@endphp
<style>
    .invoice-box {
        --blue: {{ $invBlue }};
        --blue-2: {{ $invBlue2 }};
        --blue-soft: {{ $invBlueSoft }};
        --blue-line: {{ $invBlueLine }};
        --blue-rgb: {{ $invBlueRgb }};
        --ink: #0f172a;
        --muted: #64748b;
        --line: #e2e8f0;
        --paper: #ffffff;
        max-width: 920px;
        width: 100%;
        margin: 0 auto;
        background: #ffffff;
        border-radius: {{ $isPdfMode ? '0' : '4px' }};
        box-shadow: {{ $isPdfMode ? 'none' : '0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 40px rgba(' . $invBlueRgb . ', 0.1)' }};
        overflow: hidden;
        font-family: 'Segoe UI', DejaVu Sans, Arial, Helvetica, sans-serif;
        color: #0f172a;
        font-size: {{ $isPdfMode ? '10px' : '12.5px' }};
        line-height: 1.4;
    }

    .invoice-box .band { display: none; }
    .invoice-box .sheet { padding: {{ $isPdfMode ? '6px 4px' : '28px 32px 22px' }}; }

    /* ===== HEADER ===== */
    .invoice-box .inv-hdr {
        width: 100%;
        border-collapse: collapse;
        margin: 0 0 {{ $isPdfMode ? '10px' : '22px' }} 0;
        border-bottom: 2px solid {{ $invBlue }};
    }
    .invoice-box .inv-hdr td { padding: 0 0 {{ $isPdfMode ? '8px' : '16px' }} 0; vertical-align: middle; }
    .invoice-box .inv-hdr-logo { width: 22%; }
    .invoice-box .inv-hdr-logo img {
        display: block;
        max-width: {{ $isPdfMode ? '110px' : '160px' }};
        max-height: {{ $isPdfMode ? '52px' : '72px' }};
        width: auto;
        height: auto;
    }
    .invoice-box .inv-logo-ph {
        background: {{ $invBlueSoft }};
        border: 1px dashed {{ $invBlueLine }};
        color: {{ $invBlue }};
        font-size: 10px;
        font-weight: 700;
        text-align: center;
        padding: 16px 8px;
        text-transform: uppercase;
    }
    .invoice-box .inv-hdr-brand { width: 46%; text-align: center; padding: 0 8px {{ $isPdfMode ? '8px' : '16px' }}; }
    .invoice-box .inv-company {
        font-size: {{ $isPdfMode ? '13px' : '16px' }};
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 4px;
    }
    .invoice-box .inv-meta {
        font-size: {{ $isPdfMode ? '8.5px' : '11px' }};
        color: #64748b;
        line-height: 1.45;
    }
    .invoice-box .inv-hdr-stamp { width: 32%; text-align: right; vertical-align: top; }
    .invoice-box .inv-badge {
        display: inline-block;
        background: {{ $invBlue }};
        color: #fff;
        font-size: {{ $isPdfMode ? '10px' : '11px' }};
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: {{ $isPdfMode ? '6px 12px' : '7px 14px' }};
        margin: 0 0 8px;
    }
    .invoice-box .inv-kv {
        border-collapse: collapse;
        margin-left: auto;
        font-size: {{ $isPdfMode ? '9.5px' : '12px' }};
        border: 1px solid {{ $invBlueLine }};
    }
    .invoice-box .inv-kv td {
        padding: {{ $isPdfMode ? '3px 8px' : '4px 10px' }} !important;
        border-bottom: 1px solid {{ $invBlueLine }};
    }
    .invoice-box .inv-kv tr:last-child td { border-bottom: none; }
    .invoice-box .inv-kv .k { color: #64748b; font-weight: 500; text-align: left; white-space: nowrap; }
    .invoice-box .inv-kv .v { color: #0f172a; font-weight: 700; text-align: right; white-space: nowrap; }

    /* ===== PARTIES (real HTML table for DomPDF) ===== */
    .invoice-box table.parties {
        width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: {{ $isPdfMode ? '8px' : '16px' }} 0;
        margin: 0 0 {{ $isPdfMode ? '10px' : '20px' }} 0;
    }
    .invoice-box .parties > tbody > tr > .party,
    .invoice-box .parties > tr > .party,
    .invoice-box td.party,
    .invoice-box td.party.alt {
        width: 50%;
        vertical-align: top;
        background: {{ $invBlueSoft }};
        border: 1px solid {{ $invBlueLine }};
        border-left: 4px solid {{ $invBlue }};
        padding: {{ $isPdfMode ? '8px 10px' : '14px 16px' }};
    }
    .invoice-box td.party.alt {
        background: #f8fafc;
        border-color: #e2e8f0;
        border-left: 4px solid {{ $invBlue }};
    }
    /* Legacy div-based parties (fallback) */
    .invoice-box div.parties {
        display: table;
        width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: {{ $isPdfMode ? '8px' : '16px' }} 0;
        margin: 0 0 {{ $isPdfMode ? '10px' : '20px' }} 0;
    }
    .invoice-box div.parties > .party,
    .invoice-box div.parties > .party.alt {
        display: table-cell;
        width: 50%;
        vertical-align: top;
        background: {{ $invBlueSoft }};
        border: 1px solid {{ $invBlueLine }};
        border-left: 4px solid {{ $invBlue }};
        padding: {{ $isPdfMode ? '8px 10px' : '14px 16px' }};
    }
    .invoice-box div.parties > .party.alt {
        background: #f8fafc;
        border-color: #e2e8f0;
        border-left: 4px solid {{ $invBlue }};
    }
    .invoice-box .party-title {
        margin: 0 0 {{ $isPdfMode ? '6px' : '10px' }};
        font-size: {{ $isPdfMode ? '8.5px' : '10px' }};
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: {{ $invBlue }};
    }
    .invoice-box .party-title::after { display: none; content: none; }
    .invoice-box .party-name {
        margin: 0 0 {{ $isPdfMode ? '6px' : '8px' }};
        font-size: {{ $isPdfMode ? '12px' : '15px' }};
        font-weight: 700;
        color: #0f172a;
    }
    .invoice-box .party-grid { display: block; }
    .invoice-box .party-line {
        display: table;
        width: 100%;
        margin: 0 0 3px;
        font-size: {{ $isPdfMode ? '9.5px' : '12px' }};
    }
    .invoice-box .party-line .k,
    .invoice-box .party-line .v { display: table-cell; vertical-align: top; }
    .invoice-box .party-line .k { width: 92px; color: #64748b; font-weight: 500; }
    .invoice-box .party-line .v { color: #0f172a; font-weight: 600; }

    /* ===== DESCRIPTION ===== */
    .invoice-box .desc {
        margin: 0 0 {{ $isPdfMode ? '10px' : '16px' }};
        padding: {{ $isPdfMode ? '7px 10px' : '12px 16px' }};
        border: 1px solid #e2e8f0;
        border-left: 4px solid {{ $invBlue }};
        background: #f8fafc;
    }
    .invoice-box .desc .t {
        display: block;
        font-size: {{ $isPdfMode ? '8.5px' : '10px' }};
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: {{ $invBlue }};
        margin: 0 0 3px;
    }
    .invoice-box .desc p {
        margin: 0;
        color: #334155;
        font-size: {{ $isPdfMode ? '10px' : '12.5px' }};
    }

    /* ===== TABLES ===== */
    .invoice-box .tbl-wrap,
    .invoice-box .rider-template-items .tbl-wrap {
        border: 1px solid #e2e8f0;
        border-left: 4px solid {{ $invBlue }};
        margin: 0 0 {{ $isPdfMode ? '8px' : '12px' }};
        overflow: hidden;
    }
    .invoice-box table.items,
    .invoice-box table.items-table,
    .invoice-box table.ledger,
    .invoice-box .rider-template-items table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }
    .invoice-box table.items thead th,
    .invoice-box table.items-table th,
    .invoice-box table.ledger thead th,
    .invoice-box .rider-template-items table th,
    .invoice-box .primary-header,
    .invoice-box .secondary-header,
    .invoice-box .accent-total {
        background: {{ $invBlue }} !important;
        color: #ffffff !important;
        font-size: {{ $isPdfMode ? '8.5px' : '10.5px' }};
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding: {{ $isPdfMode ? '6px 5px' : '10px 12px' }};
        border: none;
        text-align: right;
    }
    .invoice-box table.items thead th.col-desc,
    .invoice-box table.items thead th.col-sr,
    .invoice-box table.ledger thead th.col-desc,
    .invoice-box .rider-template-items table th:first-child {
        text-align: left;
    }
    .invoice-box table.items tbody td,
    .invoice-box table.items-table td,
    .invoice-box table.ledger tbody td,
    .invoice-box .rider-template-items table td {
        padding: {{ $isPdfMode ? '5px' : '10px 12px' }};
        border: none;
        border-bottom: 1px solid #e2e8f0;
        font-size: {{ $isPdfMode ? '9.5px' : '12.5px' }};
        color: #0f172a;
        text-align: right;
        vertical-align: middle;
    }
    .invoice-box table.items tbody td.col-desc,
    .invoice-box table.ledger tbody td.col-desc,
    .invoice-box .rider-template-items table td:first-child {
        text-align: left;
        font-weight: 500;
    }
    .invoice-box table.items tbody td.col-sr { text-align: center; color: #64748b; font-weight: 600; }
    .invoice-box table.items tbody tr:nth-child(even) td,
    .invoice-box table.ledger tbody tr:nth-child(even) td { background: #f8fafc; }
    .invoice-box table.items tbody td.total-cell,
    .invoice-box table.ledger tbody td.total-cell {
        font-weight: 700;
        color: {{ $invBlue }};
    }

    /* ===== TOTALS ===== */
    .invoice-box .inv-totals-area {
        width: 100%;
        border-collapse: separate;
        border-spacing: {{ $isPdfMode ? '8px' : '16px' }} 0;
        margin: {{ $isPdfMode ? '8px' : '14px' }} 0;
    }
    .invoice-box .inv-totals-notes { width: 55%; vertical-align: top; }
    .invoice-box .inv-totals { width: 40%; vertical-align: top; }
    .invoice-box .inv-note-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: 4px solid {{ $invBlue }};
        border-top: 2px solid {{ $invBlue }};
        padding: {{ $isPdfMode ? '8px 10px' : '12px 14px' }};
    }
    .invoice-box .inv-note-title {
        font-size: {{ $isPdfMode ? '8.5px' : '10px' }};
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: {{ $invBlue }};
        margin: 0 0 6px;
    }
    .invoice-box .inv-note-body {
        font-size: {{ $isPdfMode ? '9.5px' : '12px' }};
        color: #334155;
        line-height: 1.55;
    }
    .invoice-box .inv-totals-table { width: 100%; border-collapse: collapse; }
    .invoice-box .inv-totals-table td {
        padding: {{ $isPdfMode ? '5px 8px' : '8px 12px' }};
        border-bottom: 1px solid #e2e8f0;
        font-size: {{ $isPdfMode ? '9.5px' : '12.5px' }};
    }
    .invoice-box .inv-totals-table .k { color: #64748b; font-weight: 500; text-align: left; }
    .invoice-box .inv-totals-table .v { color: #0f172a; font-weight: 700; text-align: right; }
    .invoice-box .inv-totals-table .grand-cell { padding: 6px 0 0; border-bottom: none; }
    .invoice-box .inv-grand {
        width: 100%;
        border-collapse: collapse;
        background: {{ $invBlue }};
    }
    .invoice-box .inv-grand td {
        padding: {{ $isPdfMode ? '8px 10px' : '12px 14px' }};
        color: #ffffff !important;
        border: none;
    }
    .invoice-box .inv-grand .k {
        font-size: {{ $isPdfMode ? '9.5px' : '11px' }};
        font-weight: 700;
        text-transform: uppercase;
        text-align: left;
        color: #ffffff !important;
    }
    .invoice-box .inv-grand .v {
        font-size: {{ $isPdfMode ? '13px' : '18px' }};
        font-weight: 800;
        text-align: right;
        color: #ffffff !important;
    }

    /* ===== FOOTNOTES ===== */
    .invoice-box .inv-footnotes {
        width: 100%;
        border-collapse: separate;
        border-spacing: {{ $isPdfMode ? '8px' : '12px' }} 0;
        margin-top: {{ $isPdfMode ? '8px' : '12px' }};
    }
    .invoice-box .inv-footnote-cell { vertical-align: top; }

    /* ===== FOOTER ===== */
    .invoice-box .foot {
        margin-top: {{ $isPdfMode ? '10px' : '24px' }};
        padding-top: {{ $isPdfMode ? '6px' : '12px' }};
        border-top: 1px solid #e2e8f0;
        font-size: {{ $isPdfMode ? '8.5px' : '11px' }};
        color: #64748b;
    }
    .invoice-box .foot strong { color: #0f172a; }
    .invoice-box .foot .thanks { color: {{ $invBlue }}; font-weight: 600; font-style: italic; }

    .invoice-box .empty {
        text-align: center;
        padding: 24px;
        color: #64748b;
        background: #f8fafc;
        border: 1px dashed #e2e8f0;
    }

    .invoice-box .status-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: {{ $isPdfMode ? '8px' : '11px' }};
        font-weight: 700;
        line-height: 1.3;
        border: 1px solid transparent;
        animation: none;
    }
    .invoice-box .status-badge.status-green {
        color: #15803d;
        background: #dcfce7;
        border-color: #86efac;
    }
    .invoice-box .status-badge.red {
        color: #b91c1c;
        background: #fee2e2;
        border-color: #fca5a5;
    }

    .controls, .no-print { {{ $isPdfMode ? 'display:none !important;' : '' }} }

    @if($isPdfMode)
    @page { size: A4 portrait; margin: 8mm; }
    html, body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .invoice-box { max-width: 100% !important; }
    @endif
</style>
