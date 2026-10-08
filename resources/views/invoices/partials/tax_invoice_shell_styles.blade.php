{{-- Unified screen + PDF shell — soft pale side-by-side cards (tax invoice reference) --}}
@php
$invoiceBrand = $brand ?? ($companyBrand ?? []);
$invBlue = $invoiceBrand['primary_color'] ?? '#004aad';
$invBlue2 = $invoiceBrand['secondary_color'] ?? ($invoiceBrand['primary_dark'] ?? '#1a5fc4');
$invBlueSoft = $invoiceBrand['primary_soft'] ?? ($invoiceBrand['primary_light'] ?? '#eef4fc');
$invBlueLine = $invoiceBrand['border_color'] ?? '#c5d8f0';
$invBlueRgb = $invoiceBrand['primary_rgb'] ?? '0, 74, 173';
$isPdfMode = !empty($isPdf);
$pdfFitScale = (float) ($pdfFitScale ?? 1);
if ($pdfFitScale <= 0 || $pdfFitScale > 1) {
    $pdfFitScale = 1.0;
}
$fs = $isPdfMode ? (10 * $pdfFitScale) : 12.5;
$padSheet = $isPdfMode
    ? (max(2, (int) round(6 * $pdfFitScale)) . 'px ' . max(2, (int) round(4 * $pdfFitScale)) . 'px')
    : '28px 32px 22px';
$radius = $isPdfMode ? '0' : '8px';
$partyGapNum = $isPdfMode ? 10 : 12;
$partyGap = $partyGapNum . 'px';
$partyPad = $isPdfMode ? '10px 12px' : '16px 18px';
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
        font-size: {{ number_format($fs, 2, '.', '') }}px;
        line-height: 1.4;
    }

    .invoice-box .band {
        display: none;
    }

    .invoice-box .sheet {
        padding: {{ $padSheet }};
    }

    /* ===== HEADER ===== */
    .invoice-box .inv-hdr {
        width: 100%;
        border-collapse: collapse;
        margin: 0 0 {{ $isPdfMode ? '12px' : '20px' }} 0;
        border-bottom: 2.5px solid {{ $invBlue }};
    }

    .invoice-box .inv-hdr td {
        padding: 0 0 {{ $isPdfMode ? '10px' : '16px' }} 0;
        vertical-align: middle;
        border: none;
    }

    .invoice-box .inv-hdr-logo {
        width: 22%;
    }

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
        border-radius: {{ $isPdfMode ? '0' : '6px' }};
    }

    .invoice-box .inv-hdr-brand {
        width: 46%;
        text-align: center;
        padding: 0 10px;
    }

    .invoice-box .inv-company {
        font-size: {{ $isPdfMode ? '13px' : '17px' }};
        font-weight: 700;
        color: {{ $invBlue }};
        margin: 0 0 5px;
        letter-spacing: 0.2px;
    }

    .invoice-box .inv-meta {
        font-size: {{ $isPdfMode ? '8.5px' : '11px' }};
        color: #64748b;
        line-height: 1.5;
    }

    .invoice-box .inv-hdr-stamp {
        width: 32%;
        text-align: right;
        vertical-align: top;
    }

    .invoice-box .inv-badge {
        display: inline-block;
        background: {{ $invBlue }};
        color: #fff;
        font-size: {{ $isPdfMode ? '10px' : '11px' }};
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        padding: {{ $isPdfMode ? '6px 12px' : '7px 14px' }};
        margin: 0 0 10px;
        border-radius: {{ $isPdfMode ? '0' : '6px' }};
    }

    .invoice-box .inv-kv {
        border-collapse: collapse;
        margin-left: auto;
        font-size: {{ $isPdfMode ? '9.5px' : '12px' }};
    }

    .invoice-box .inv-kv td {
        padding: {{ $isPdfMode ? '2px 0 2px 12px' : '3px 0 3px 16px' }} !important;
        border: none !important;
        background: transparent;
    }

    .invoice-box .inv-kv .k {
        color: #64748b;
        font-weight: 500;
        text-align: left;
        white-space: nowrap;
    }

    .invoice-box .inv-kv .v {
        color: #0f172a;
        font-weight: 700;
        text-align: right;
        white-space: nowrap;
    }

    /* ===== PARTIES — equal-width soft cards (div columns) ===== */
    @if($isPdfMode)
    /* DomPDF: table-cell columns (flex unsupported) */
    .invoice-box .parties {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        margin: 0 0 12px 0 !important;
    }

    .invoice-box .parties > .party,
    .invoice-box .parties > .party.alt {
        display: table-cell !important;
        width: 50% !important;
        vertical-align: top;
        background-color: {{ $invBlueSoft }} !important;
        border: 1px solid {{ $invBlueLine }} !important;
        border-top: 3px solid #1f3edb !important;
        border-radius: 0;
        padding: {{ $partyPad }};
    }

    .invoice-box .parties > .party:first-child {
        border-right: {{ $partyGapNum }}px solid #ffffff !important;
    }

    .invoice-box .parties > .party.alt {
        background-color: {{ $invBlueSoft }} !important;
    }
    @else
    .invoice-box .parties {
        display: flex;
        flex-direction: row;
        align-items: stretch;
        gap: {{ $partyGap }};
        width: 100%;
        margin: 0 0 16px 0;
        box-sizing: border-box;
    }

    .invoice-box .parties > .party,
    .invoice-box .parties > .party.alt {
        flex: 1 1 0;
        width: 50%;
        min-width: 0;
        box-sizing: border-box;
        vertical-align: top;
        background: {{ $invBlueSoft }} !important;
        border: 1px solid {{ $invBlueLine }} !important;
        border-top: 3px solid #1f3edb !important;
        border-radius: {{ $radius }};
        padding: {{ $partyPad }};
    }

    .invoice-box .parties > .party.alt {
        background: {{ $invBlueSoft }} !important;
    }
    @endif

    .invoice-box .party-title {
        display: table;
        width: 100%;
        margin: 0 0 {{ $isPdfMode ? '8px' : '12px' }};
        font-size: {{ $isPdfMode ? '8.5px' : '10px' }};
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: {{ $invBlue }};
        text-align: left;
        white-space: nowrap;
        background: transparent;
        border: none;
        padding: 0;
    }

    .invoice-box .party-title::after {
        content: '';
        display: table-cell;
        width: 100%;
        border-bottom: 1px solid {{ $invBlue }};
        vertical-align: middle;
        padding-left: 10px;
        opacity: 0.55;
    }

    .invoice-box .party-name {
        margin: 0 0 {{ $isPdfMode ? '8px' : '10px' }};
        padding: 0;
        font-size: {{ $isPdfMode ? '13px' : '15px' }};
        font-weight: 700;
        color: #0f172a;
        text-align: left;
        line-height: 1.35;
    }

    .invoice-box .party-grid {
        display: block;
        width: 100%;
        padding: 0;
    }

    .invoice-box .party-line {
        display: table;
        width: 100%;
        table-layout: fixed;
        margin: 0 0 {{ $isPdfMode ? '4px' : '6px' }};
        font-size: {{ $isPdfMode ? '9.5px' : '12.5px' }};
        line-height: 1.5;
    }

    .invoice-box .party-line .k,
    .invoice-box .party-line .v {
        display: table-cell;
        vertical-align: top;
    }

    .invoice-box .party-line .k {
        width: {{ $isPdfMode ? '88px' : '100px' }};
        color: #64748b;
        font-weight: 500;
        text-align: left;
        padding-right: 10px;
        white-space: nowrap;
    }

    .invoice-box .party-line .v {
        color: #0f172a;
        font-weight: 700;
        text-align: right;
        word-break: break-word;
    }

    .invoice-box .party.alt .party-line .v {
        text-align: right;
    }

    /* ===== DESCRIPTION ===== */
    .invoice-box .desc {
        margin: 0 0 {{ $isPdfMode ? '10px' : '14px' }};
        padding: {{ $isPdfMode ? '8px 10px' : '12px 14px' }};
        border: 1px solid {{ $invBlueLine }};
        border-top: 3px solid #1f3edb;
        background: {{ $invBlueSoft }};
        border-radius: {{ $radius }};
        text-align: left;
    }

    .invoice-box .desc .t {
        display: block;
        font-size: {{ $isPdfMode ? '8.5px' : '10px' }};
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: {{ $invBlue }};
        margin: 0 0 4px;
        padding: 0;
        text-align: left;
        background: transparent;
        border: none;
    }

    .invoice-box .desc p {
        margin: 0;
        padding: 0;
        color: #334155;
        font-size: {{ $isPdfMode ? '10px' : '12.5px' }};
        text-align: left;
    }

    /* ===== TABLES ===== */
    .invoice-box .tbl-wrap,
    .invoice-box .rider-template-items .tbl-wrap {
        border: 1px solid {{ $invBlueLine }};
        border-top: 3px solid #1f3edb;
        border-radius: {{ $isPdfMode ? '0' : '6px' }};
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
    .invoice-box .accent-total,
    .invoice-box .light-header,
    .invoice-box .success-highlight,
    .invoice-box .amount-highlight {
        background: {{ $invBlueSoft }} !important;
        color: #0f172a !important;
        font-size: {{ $isPdfMode ? '8.5px' : '10.5px' }};
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        padding: {{ $isPdfMode ? '6px 5px' : '9px 10px' }};
        border: none;
        border-bottom: 1px solid {{ $invBlueLine }};
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
        padding: {{ $isPdfMode ? '5px' : '8px 10px' }};
        border: none;
        border-bottom: 1px solid #e2e8f0;
        font-size: {{ $isPdfMode ? '9.5px' : '12px' }};
        color: #0f172a;
        text-align: center;
        vertical-align: middle;
    }

    .invoice-box table.items tbody td.col-desc,
    .invoice-box table.ledger tbody td.col-desc,
    .invoice-box .rider-template-items table td:first-child {
        text-align: left;
        font-weight: 500;
    }

    .invoice-box table.items tbody td.col-sr {
        text-align: center;
        color: #64748b;
        font-weight: 600;
    }

    .invoice-box table.items tbody tr:nth-child(even) td,
    .invoice-box table.ledger tbody tr:nth-child(even) td {
        background: #fafbfc;
    }

    .invoice-box table.items tbody td.total-cell,
    .invoice-box table.ledger tbody td.total-cell,
    .invoice-box .rider-template-items table td:last-child {
        font-weight: 700;
        color: #0f172a;
    }

    .invoice-box table.items tfoot td,
    .invoice-box .rider-template-items table tfoot td {
        background: {{ $invBlueSoft }};
        font-weight: 700;
        padding: {{ $isPdfMode ? '6px 5px' : '9px 10px' }};
        border-top: 1px solid {{ $invBlueLine }};
    }

    /* ===== TOTALS — div columns ===== */
    @if($isPdfMode)
    .invoice-box .inv-totals-area {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        margin: 8px 0 !important;
    }

    .invoice-box .inv-totals-notes,
    .invoice-box .inv-totals {
        display: table-cell !important;
        vertical-align: top !important;
    }

    .invoice-box .inv-totals-notes {
        width: 55% !important;
        padding-right: 12px !important;
    }

    .invoice-box .inv-totals {
        width: 45% !important;
    }
    @else
    .invoice-box .inv-totals-area {
        display: flex;
        flex-direction: row;
        align-items: stretch;
        gap: 16px;
        width: 100%;
        margin: 14px 0;
        box-sizing: border-box;
    }

    .invoice-box .inv-totals-notes {
        flex: 1 1 55%;
        min-width: 0;
        margin-right: 5px;
    }

    .invoice-box .inv-totals {
        flex: 0 0 42%;
        min-width: 0;
    }
    @endif

    .invoice-box .inv-note-box {
        background: {{ $invBlueSoft }};
        border: 1px solid {{ $invBlueLine }};
        border-top: 3px solid {{ $invBlue }};
        border-radius: {{ $radius }};
        padding: {{ $isPdfMode ? '8px 10px' : '12px 14px' }};
        box-sizing: border-box;
        height: 100%;
        min-height: 100%;
        text-align: left;
    }

    .invoice-box .inv-note-title {
        font-size: {{ $isPdfMode ? '8.5px' : '10px' }};
        font-weight: 700;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: {{ $invBlue }};
        margin: 0 0 6px;
        padding: 0;
        background: transparent;
        border: none;
        text-align: left;
    }

    .invoice-box .inv-note-body {
        font-size: {{ $isPdfMode ? '9.5px' : '12px' }};
        color: #64748b;
        line-height: 1.55;
        padding: 0;
        text-align: left;
    }

    .invoice-box .inv-totals-table {
        width: 100%;
        display: block;
    }

    .invoice-box .inv-totals-row {
        display: table;
        width: 100%;
        table-layout: fixed;
        padding: {{ $isPdfMode ? '5px 8px' : '8px 12px' }};
        font-size: {{ $isPdfMode ? '9.5px' : '12.5px' }};
        border-bottom: 1px solid #e2e8f0;
        box-sizing: border-box;
    }

    .invoice-box .inv-totals-row:last-child {
        border-bottom: none;
    }

    .invoice-box .inv-totals-row-grand {
        border-bottom: none;
        padding: {{ $isPdfMode ? '6px 0' : '8px 0' }};
    }

    .invoice-box .inv-totals-row .k,
    .invoice-box .inv-totals-row .v {
        display: table-cell;
        vertical-align: middle;
    }

    .invoice-box .inv-totals-row .k {
        color: #64748b;
        font-weight: 500;
        text-align: left;
    }

    .invoice-box .inv-totals-row .v {
        color: #0f172a;
        font-weight: 700;
        text-align: right;
    }

    .invoice-box .inv-grand {
        display: table;
        width: 100%;
        table-layout: fixed;
        background: {{ $invBlue }};
        border-radius: {{ $isPdfMode ? '0' : '4px' }};
        padding: {{ $isPdfMode ? '8px 10px' : '12px 14px' }};
        box-sizing: border-box;
    }

    .invoice-box .inv-grand .k,
    .invoice-box .inv-grand .v {
        display: table-cell;
        vertical-align: middle;
        color: #ffffff !important;
        border: none;
    }

    .invoice-box .inv-grand .k {
        font-size: {{ $isPdfMode ? '9.5px' : '11px' }};
        font-weight: 700;
        text-transform: uppercase;
        text-align: left;
    }

    .invoice-box .inv-grand .v {
        font-size: {{ $isPdfMode ? '13px' : '18px' }};
        font-weight: 800;
        text-align: right;
    }

    /* ===== FOOTNOTES ===== */
    .invoice-box .inv-footnotes {
        width: 100%;
        margin-top: {{ $isPdfMode ? '8px' : '12px' }};
    }

    .invoice-box .inv-footnote-cell {
        vertical-align: top;
    }

    /* ===== FOOTER (thank-you line removed) ===== */
    .invoice-box .foot {
        display: none !important;
    }

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
        vertical-align: middle;
    }

    .invoice-box .status-badge.status-green {
        color: #166534;
        background: #dcfce7;
        border-color: #86efac;
    }

    .invoice-box .status-badge.red {
        color: #b91c1c;
        background: #fee2e2;
        border-color: #fca5a5;
    }

    .controls,
    .no-print {
        {{ $isPdfMode ? 'display:none !important;' : '' }}
    }

    @if($isPdfMode)
    @@page {
        size: A4 portrait;
        margin: 6mm;
    }

    html,
    body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .invoice-box {
        max-width: 100% !important;
        box-shadow: none !important;
    }
    @endif
</style>
