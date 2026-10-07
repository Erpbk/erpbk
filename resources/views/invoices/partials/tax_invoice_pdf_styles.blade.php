{{-- DomPDF-safe styles: no CSS variables, no grid/flex reliance --}}
@if(!empty($isPdf))
@php
    if (empty($brand) && empty($companyBrand) && class_exists(\App\Services\Agreements\AgreementPdfBranding::class)) {
        $brand = app(\App\Services\Agreements\AgreementPdfBranding::class)
            ->forCompany(\App\Support\CompanyContext::id());
    }
    $pdfBrand = $brand ?? ($companyBrand ?? []);
    $pdfBlue = $pdfBrand['primary_color'] ?? '#004aad';
    $pdfBlueSoft = $pdfBrand['primary_soft'] ?? ($pdfBrand['primary_light'] ?? '#eef4fc');
    $pdfBlueLine = $pdfBrand['border_color'] ?? '#c5d8f0';
    $pdfInk = '#0f172a';
    $pdfMuted = '#64748b';
    $pdfLine = '#e2e8f0';
@endphp
<style>
    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    * {
        box-sizing: border-box;
    }

    html, body {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif !important;
        font-size: 11px !important;
        line-height: 1.35 !important;
        color: {{ $pdfInk }} !important;
    }

    .controls,
    .no-print,
    .invoice-box .band {
        display: none !important;
    }

    .invoice-box {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        overflow: visible !important;
    }

    .invoice-box .sheet {
        padding: 4px 2px !important;
        position: relative !important;
    }

    /* Header / totals / footnotes use real HTML tables when isPdf */

    /* ===== PARTIES ===== */
    .invoice-box .parties {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
        margin: 0 0 12px !important;
        border-collapse: separate !important;
        border-spacing: 8px 0 !important;
    }

    .invoice-box .party,
    .invoice-box .party.alt {
        display: table-cell !important;
        width: 50% !important;
        vertical-align: top !important;
        background: {{ $pdfBlueSoft }} !important;
        border: 1px solid {{ $pdfBlueLine }} !important;
        border-left: 4px solid {{ $pdfBlue }} !important;
        border-radius: 0 !important;
        padding: 10px 12px !important;
    }

    .invoice-box .party.alt {
        background: #f8fafc !important;
        border-color: {{ $pdfLine }} !important;
        border-left: 4px solid {{ $pdfBlue }} !important;
    }

    .invoice-box .party-title {
        margin: 0 0 8px !important;
        font-size: 9px !important;
        font-weight: 700 !important;
        letter-spacing: 0.8px !important;
        text-transform: uppercase !important;
        color: {{ $pdfBlue }} !important;
    }

    .invoice-box .party-title::after {
        display: none !important;
        content: none !important;
    }

    .invoice-box .party-name {
        margin: 0 0 8px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        color: {{ $pdfInk }} !important;
    }

    .invoice-box .party-grid {
        display: block !important;
    }

    .invoice-box .party-line {
        display: table !important;
        width: 100% !important;
        margin: 0 0 3px !important;
        font-size: 10px !important;
    }

    .invoice-box .party-line .k,
    .invoice-box .party-line .v {
        display: table-cell !important;
        vertical-align: top !important;
    }

    .invoice-box .party-line .k {
        width: 90px !important;
        color: {{ $pdfMuted }} !important;
        font-weight: 500 !important;
    }

    .invoice-box .party-line .v {
        color: {{ $pdfInk }} !important;
        font-weight: 600 !important;
    }

    /* ===== DESCRIPTION ===== */
    .invoice-box .desc {
        margin: 0 0 12px !important;
        padding: 8px 12px !important;
        border: 1px solid {{ $pdfLine }} !important;
        border-left: 4px solid {{ $pdfBlue }} !important;
        background: #f8fafc !important;
        border-radius: 4px !important;
    }

    .invoice-box .desc .t {
        display: block !important;
        font-size: 9px !important;
        font-weight: 700 !important;
        letter-spacing: 0.7px !important;
        text-transform: uppercase !important;
        color: {{ $pdfBlue }} !important;
        margin: 0 0 3px !important;
    }

    .invoice-box .desc p {
        margin: 0 !important;
        color: #334155 !important;
        font-size: 11px !important;
    }

    /* ===== TABLES ===== */
    .invoice-box .tbl-wrap {
        border: 1px solid {{ $pdfLine }} !important;
        border-left: 4px solid {{ $pdfBlue }} !important;
        border-radius: 4px !important;
        overflow: visible !important;
        margin: 0 0 10px !important;
    }

    .invoice-box table.items,
    .invoice-box table.items-table,
    .invoice-box table.ledger,
    .invoice-box .rider-template-items table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 0 !important;
    }

    .invoice-box table.items thead th,
    .invoice-box table.items-table th,
    .invoice-box table.ledger thead th,
    .invoice-box .rider-template-items table th,
    .invoice-box .primary-header,
    .invoice-box .secondary-header,
    .invoice-box .accent-total {
        background: {{ $pdfBlue }} !important;
        color: #ffffff !important;
        font-size: 9px !important;
        font-weight: 700 !important;
        letter-spacing: 0.4px !important;
        text-transform: uppercase !important;
        padding: 7px 6px !important;
        border: 1px solid {{ $pdfBlue }} !important;
        text-align: right !important;
    }

    .invoice-box table.items thead th.col-desc,
    .invoice-box table.items thead th.col-sr,
    .invoice-box table.ledger thead th.col-desc {
        text-align: left !important;
    }

    .invoice-box table.items tbody td,
    .invoice-box table.items-table td,
    .invoice-box table.ledger tbody td,
    .invoice-box .rider-template-items table td {
        padding: 6px !important;
        border: none !important;
        border-bottom: 1px solid {{ $pdfLine }} !important;
        font-size: 10px !important;
        color: {{ $pdfInk }} !important;
        vertical-align: middle !important;
        text-align: right !important;
        background: #ffffff !important;
    }

    .invoice-box table.items tbody td.col-desc,
    .invoice-box table.ledger tbody td.col-desc {
        text-align: left !important;
        font-weight: 500 !important;
    }

    .invoice-box table.items tbody td.col-sr {
        text-align: center !important;
        color: {{ $pdfMuted }} !important;
    }

    .invoice-box table.items tbody tr:nth-child(even) td,
    .invoice-box table.ledger tbody tr:nth-child(even) td {
        background: #f8fafc !important;
    }

    .invoice-box table.items tbody td.total-cell,
    .invoice-box table.ledger tbody td.total-cell {
        font-weight: 700 !important;
        color: {{ $pdfBlue }} !important;
    }

    /* Totals + footnotes rendered as HTML tables when isPdf */

    .invoice-box .empty {
        text-align: center !important;
        padding: 20px !important;
        color: {{ $pdfMuted }} !important;
        background: #f8fafc !important;
        border: 1px dashed {{ $pdfLine }} !important;
    }

    /* ===== FOOTER ===== */
    .invoice-box .foot {
        margin-top: 14px !important;
        padding-top: 8px !important;
        border-top: 1px solid {{ $pdfLine }} !important;
        font-size: 9px !important;
        color: {{ $pdfMuted }} !important;
    }

    .invoice-box .foot strong {
        color: {{ $pdfInk }} !important;
    }

    .invoice-box .foot .thanks {
        color: {{ $pdfBlue }} !important;
        font-weight: 600 !important;
        font-style: italic !important;
    }

    .invoice-box .status-badge {
        display: inline-block !important;
        padding: 1px 6px !important;
        border-radius: 3px !important;
        font-size: 9px !important;
        font-weight: 700 !important;
        animation: none !important;
    }

    .invoice-box .status-badge.status-green {
        color: #15803d !important;
        background: #dcfce7 !important;
        border: 1px solid #86efac !important;
    }

    .invoice-box .status-badge.red {
        color: #b91c1c !important;
        background: #fee2e2 !important;
        border: 1px solid #fca5a5 !important;
    }
</style>
@endif
