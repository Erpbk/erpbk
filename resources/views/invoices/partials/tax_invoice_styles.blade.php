{{-- Screen + print chrome. Visual shell (header/parties/tables/totals) lives in tax_invoice_shell_styles. --}}
@php
    if (empty($brand) && empty($companyBrand) && class_exists(\App\Services\Agreements\AgreementPdfBranding::class)) {
        try {
            $brand = app(\App\Services\Agreements\AgreementPdfBranding::class)
                ->forCompany(\App\Support\CompanyContext::id());
        } catch (\Throwable $e) {
            $brand = [];
        }
    }
    $invoiceBrand = $brand ?? ($companyBrand ?? []);
    $invBlue = $invoiceBrand['primary_color'] ?? '#004aad';
    $invBlueSoft = $invoiceBrand['primary_soft'] ?? ($invoiceBrand['primary_light'] ?? '#eef4fc');
@endphp

@include('invoices.partials.tax_invoice_pdf_inline')
@include('invoices.partials.tax_invoice_shell_styles')

@if(empty($isPdf))
<script>
    (function() {
        if (document.querySelector('meta[name="viewport"]')) return;
        var m = document.createElement('meta');
        m.name = 'viewport';
        m.content = 'width=device-width, initial-scale=1, viewport-fit=cover';
        document.head.appendChild(m);
    })();
</script>
@endif

<style>
    .invoice-box,
    .invoice-box * {
        box-sizing: border-box;
    }

    body:has(> .invoice-box),
    body:has(> .controls) {
        font-family: 'Segoe UI', Calibri, Arial, Helvetica, sans-serif;
        font-size: 12.5px;
        color: #0f172a;
        background: #edf1f7;
        margin: 0;
        padding: 24px 16px;
        line-height: 1.5;
        -webkit-font-smoothing: antialiased;
    }

    #rightSideModalBody:has(.invoice-box) {
        font-family: 'Segoe UI', Calibri, Arial, Helvetica, sans-serif;
        font-size: 12.5px;
        color: #0f172a;
        background: #edf1f7;
        padding: 20px 12px;
        line-height: 1.5;
        max-width: 100%;
        overflow-x: hidden;
        container-type: inline-size;
        container-name: inv-modal;
    }

    .invoice-box {
        width: 100%;
        max-width: min(920px, 100%);
    }

    /* Horizontal scroll for wide item tables on small screens */
    .invoice-box .tbl-wrap,
    .invoice-box .rider-template-items,
    .invoice-box .rider-template-items .tbl-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Soft salary-slip table headers (tint + brand text) */
    .invoice-box.invoice-layout-modern table.items-table th,
    .invoice-box.invoice-layout-modern .secondary-header,
    .invoice-box.invoice-layout-modern .accent-total,
    .invoice-box.invoice-layout-modern .light-header,
    .invoice-box.invoice-layout-modern .success-highlight,
    .invoice-box.invoice-layout-modern .amount-highlight,
    .invoice-box.invoice-layout-modern .primary-header,
    .invoice-box.invoice-layout-modern table.items thead th,
    .invoice-box table.items-table th,
    .invoice-box .primary-header,
    .invoice-box .secondary-header,
    .invoice-box .accent-total,
    .invoice-box table.items thead th {
        background: {{ $invBlueSoft }} !important;
        color: #0f172a !important;
    }

    /* ========== CONTROLS ========== */
    #rightSideModalBody>.controls,
    body>.controls {
        position: sticky;
        top: 10px;
        z-index: 100;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        border-radius: 10px;
        box-sizing: border-box;
    }

    /* Standalone page: centered action strip */
    body>.controls {
        margin: auto auto 18px auto;
        width: fit-content;
        max-width: 920px;
        justify-content: center;
    }

    /* Modal drawer: never wider than panel (Fold / phone) */
    #rightSideModalBody>.controls {
        margin: 0 0 14px 0;
        width: 100%;
        max-width: 100%;
        justify-content: flex-start;
    }

    #rightSideModalBody>.controls .invoice-pay-status,
    body>.controls .invoice-pay-status {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.4px;
        line-height: 1.3;
        border: 1px solid transparent;
        white-space: nowrap;
        animation: invoice-pay-status-blink 1.25s ease-in-out infinite;
    }

    #rightSideModalBody>.controls .invoice-pay-status.is-paid,
    body>.controls .invoice-pay-status.is-paid {
        color: #166534;
        background: #dcfce7;
        border-color: #86efac;
    }

    #rightSideModalBody>.controls .invoice-pay-status.is-unpaid,
    body>.controls .invoice-pay-status.is-unpaid {
        color: #991b1b;
        background: #fee2e2;
        border-color: #fca5a5;
        animation-name: invoice-pay-status-blink-red;
    }

    #rightSideModalBody>.controls .invoice-pay-status.is-partial,
    body>.controls .invoice-pay-status.is-partial {
        color: #92400e;
        background: #fef3c7;
        border-color: #fcd34d;
        animation-name: invoice-pay-status-blink-amber;
    }

    @@keyframes invoice-pay-status-blink {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
        50% { opacity: 0.55; box-shadow: 0 0 0 4px rgba(34, 197, 94, 0); }
    }

    @@keyframes invoice-pay-status-blink-red {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
        50% { opacity: 0.55; box-shadow: 0 0 0 4px rgba(239, 68, 68, 0); }
    }

    @@keyframes invoice-pay-status-blink-amber {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
        50% { opacity: 0.55; box-shadow: 0 0 0 4px rgba(245, 158, 11, 0); }
    }

    #rightSideModalBody>.controls .action-btn,
    body>.controls .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff;
        color: {{ $invBlue }};
        border: 1px solid {{ $invBlue }};
        padding: 6px 14px;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.3;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        white-space: nowrap;
        transition: background 0.15s, color 0.15s, border-color 0.15s;
        font-family: inherit;
    }

    #rightSideModalBody>.controls .action-btn i,
    body>.controls .action-btn i {
        font-size: 15px;
        line-height: 1;
    }

    #rightSideModalBody>.controls .action-btn:hover,
    body>.controls .action-btn:hover {
        background: {{ $invBlueSoft }};
        color: {{ $invBlue }};
        border-color: {{ $invBlue }};
        text-decoration: none;
    }

    #rightSideModalBody>.controls .action-btn.danger,
    body>.controls .action-btn.danger {
        color: #dc3545;
        border-color: #dc3545;
    }

    #rightSideModalBody>.controls .action-btn.danger:hover,
    body>.controls .action-btn.danger:hover {
        background: #fff5f5;
        color: #dc3545;
    }

    #rightSideModalBody>.controls form,
    body>.controls form {
        display: inline;
        margin: 0;
    }

    @@page {
        size: A4 portrait;
        margin: 4mm;
    }

    #invoice-print-root {
        position: fixed !important;
        left: -10000px !important;
        top: 0 !important;
        width: 202mm !important;
        visibility: hidden !important;
        pointer-events: none !important;
        z-index: -1 !important;
        overflow: hidden !important;
    }

    @@media print {
        html,
        body {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            height: 100% !important;
            min-height: 100% !important;
            overflow: hidden !important;
            font-size: 10px !important;
            line-height: 1.3 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        body.printing-invoice>*:not(#invoice-print-root) {
            display: none !important;
        }

        body.printing-invoice #invoice-print-root {
            display: block !important;
            position: static !important;
            left: auto !important;
            top: auto !important;
            visibility: visible !important;
            pointer-events: auto !important;
            z-index: auto !important;
            width: 100% !important;
            height: 100% !important;
            min-height: 289mm !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            background: #fff !important;
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        body.printing-invoice #invoice-print-root .invoice-print-fit {
            overflow: hidden !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            page-break-after: avoid !important;
            box-sizing: border-box !important;
        }

        body.printing-invoice #invoice-print-root .invoice-print-fit.is-page-frame {
            width: 100% !important;
            max-width: none !important;
            height: 100% !important;
            min-height: 289mm !important;
            margin: 0 !important;
        }

        body.printing-invoice #invoice-print-root .invoice-box {
            position: relative !important;
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            overflow: visible !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body.printing-invoice #invoice-print-root .invoice-box .sheet,
        body.printing-invoice #invoice-print-root .invoice-box table,
        body.printing-invoice #invoice-print-root .invoice-box .tbl-wrap,
        body.printing-invoice #invoice-print-root .invoice-box .parties,
        body.printing-invoice #invoice-print-root .invoice-box .desc {
            width: 100% !important;
            max-width: none !important;
        }

        .controls,
        .no-print {
            display: none !important;
        }

        .invoice-box {
            box-shadow: none !important;
            border-radius: 0 !important;
            max-width: none !important;
        }

        .invoice-box .inv-badge,
        .invoice-box .inv-grand,
        .invoice-box table.items thead th,
        .invoice-box table.items-table th,
        .invoice-box .party,
        .invoice-box .desc,
        .invoice-box .inv-note-box {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        .invoice-box .inv-hdr,
        .invoice-box .parties,
        .invoice-box .desc,
        .invoice-box .inv-totals-area,
        .invoice-box .inv-footnotes,
        .invoice-box .foot,
        .invoice-box table.items thead,
        .invoice-box table.items tr,
        .invoice-box table.items-table tr,
        .invoice-box .rider-template-items table tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .invoice-box .inv-totals-area,
        .invoice-box .inv-footnotes,
        .invoice-box .foot {
            page-break-before: avoid !important;
            break-before: avoid-page !important;
        }

        .invoice-box.invoice-print-dense table.items-table th,
        .invoice-box.invoice-print-dense table.items-table td,
        .invoice-box.invoice-print-dense .rider-template-items table th,
        .invoice-box.invoice-print-dense .rider-template-items table td,
        .invoice-box.invoice-print-ultra table.items-table th,
        .invoice-box.invoice-print-ultra table.items-table td,
        .invoice-box.invoice-print-ultra .rider-template-items table th,
        .invoice-box.invoice-print-ultra .rider-template-items table td {
            padding: 2px 4px !important;
            font-size: 8.5px !important;
            line-height: 1.2 !important;
        }
    }

    /* ========== RESPONSIVE (screen only; print/PDF unchanged) ========== */
    @@media screen and (max-width: 960px) {
        body:has(> .invoice-box),
        body:has(> .controls) {
            padding: 16px 12px;
        }

        #rightSideModalBody:has(.invoice-box) {
            padding: 14px 10px;
        }

        .invoice-box .sheet {
            padding: 22px 18px 18px;
        }

        #rightSideModalBody>.controls,
        body>.controls {
            width: 100%;
            max-width: 100%;
            margin: 0 0 14px 0;
            justify-content: flex-start;
        }
    }

    @@media screen and (max-width: 720px) {
        body:has(> .invoice-box),
        body:has(> .controls) {
            padding: 12px 8px;
            font-size: 12px;
        }

        .invoice-box {
            border-radius: 0;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        }

        .invoice-box .sheet {
            padding: 16px 12px 14px;
        }

        /* Header stacks: logo → company → badge/meta */
        .invoice-box .inv-hdr,
        .invoice-box .inv-hdr > tbody,
        .invoice-box .inv-hdr > tbody > tr,
        .invoice-box .inv-hdr tr {
            display: block !important;
            width: 100% !important;
        }

        .invoice-box .inv-hdr td,
        .invoice-box .inv-hdr-logo,
        .invoice-box .inv-hdr-brand,
        .invoice-box .inv-hdr-stamp {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            text-align: center !important;
            padding: 0 0 10px 0 !important;
        }

        .invoice-box .inv-hdr-logo img {
            margin: 0 auto;
            max-width: 140px;
            max-height: 64px;
        }

        .invoice-box .inv-logo-ph {
            margin: 0 auto;
            max-width: 160px;
        }

        .invoice-box .inv-company {
            font-size: 15px;
        }

        .invoice-box .inv-meta {
            font-size: 11px;
        }

        .invoice-box .inv-badge {
            margin: 0 auto 8px;
        }

        .invoice-box .inv-kv {
            margin: 0 auto !important;
        }

        .invoice-box .inv-hdr {
            border-bottom-width: 2px;
            margin-bottom: 14px !important;
        }

        /* Bill To / Service Period + notes / totals → stacked columns */
        .invoice-box .parties,
        .invoice-box .inv-totals-area,
        .invoice-box .inv-footnotes {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            gap: 10px !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .invoice-box .parties > .party,
        .invoice-box .parties > .party.alt,
        .invoice-box .inv-totals-notes,
        .invoice-box .inv-totals,
        .invoice-box .inv-footnote-cell {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            flex: none !important;
            margin: 0 !important;
        }

        .invoice-box .inv-note-box {
            height: auto !important;
            min-height: 0 !important;
        }

        .invoice-box .inv-totals {
            width: 100% !important;
        }

        /* Wide tables scroll instead of overflowing the viewport */
        .invoice-box table.items,
        .invoice-box table.items-table,
        .invoice-box table.ledger,
        .invoice-box .rider-template-items table {
            min-width: 620px;
        }

        .invoice-box table.items-table th,
        .invoice-box table.items-table td,
        .invoice-box .rider-template-items table th,
        .invoice-box .rider-template-items table td {
            font-size: 11px;
            padding: 7px 8px;
        }

        #rightSideModalBody>.controls,
        body>.controls {
            gap: 6px;
            top: 6px;
        }

        #rightSideModalBody>.controls .action-btn,
        body>.controls .action-btn {
            padding: 6px 10px;
            font-size: 12px;
        }
    }

    @@media screen and (max-width: 480px) {
        body:has(> .invoice-box),
        body:has(> .controls) {
            padding: 8px 6px;
        }

        .invoice-box .sheet {
            padding: 12px 10px;
        }

        .invoice-box .inv-company {
            font-size: 14px;
        }

        .invoice-box .party-name {
            font-size: 14px;
        }

        .invoice-box .party-line,
        .invoice-box .inv-totals-row {
            font-size: 12px;
        }

        .invoice-box .inv-grand .v {
            font-size: 15px;
        }

        .invoice-box table.items,
        .invoice-box table.items-table,
        .invoice-box table.ledger,
        .invoice-box .rider-template-items table {
            min-width: 560px;
        }

        #rightSideModalBody>.controls .action-btn,
        body>.controls .action-btn {
            flex: 1 1 calc(50% - 6px);
            justify-content: center;
        }

        #rightSideModalBody>.controls .invoice-pay-status,
        body>.controls .invoice-pay-status {
            flex: 1 1 100%;
            justify-content: center;
        }
    }

    /*
     * Modal / Fold: layout follows PANEL width, not viewport.
     * Z Fold cover or split can be ~360–900px wide while @media still sees a wide screen.
     */
    @@container inv-modal (max-width: 860px) {
        .invoice-box {
            padding: 0;
        }

        .invoice-box .sheet {
            padding: 16px 12px 14px;
        }

        .invoice-box .inv-hdr,
        .invoice-box .inv-hdr > tbody,
        .invoice-box .inv-hdr > tbody > tr,
        .invoice-box .inv-hdr tr {
            display: block !important;
            width: 100% !important;
        }

        .invoice-box .inv-hdr td,
        .invoice-box .inv-hdr-logo,
        .invoice-box .inv-hdr-brand,
        .invoice-box .inv-hdr-stamp {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            text-align: center !important;
            padding: 0 0 10px 0 !important;
        }

        .invoice-box .inv-hdr-logo img {
            margin: 0 auto;
            max-width: 140px;
            max-height: 64px;
        }

        .invoice-box .inv-logo-ph {
            margin: 0 auto;
            max-width: 160px;
        }

        .invoice-box .inv-badge {
            margin: 0 auto 8px;
        }

        .invoice-box .inv-kv {
            margin: 0 auto !important;
        }

        .invoice-box .parties,
        .invoice-box .inv-totals-area,
        .invoice-box .inv-footnotes {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            gap: 10px !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .invoice-box .parties > .party,
        .invoice-box .parties > .party.alt,
        .invoice-box .inv-totals-notes,
        .invoice-box .inv-totals,
        .invoice-box .inv-footnote-cell {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            flex: none !important;
            margin: 0 !important;
        }

        .invoice-box .inv-note-box {
            height: auto !important;
            min-height: 0 !important;
        }

        .invoice-box .inv-totals {
            width: 100% !important;
        }

        .invoice-box table.items,
        .invoice-box table.items-table,
        .invoice-box table.ledger,
        .invoice-box .rider-template-items table {
            min-width: 560px;
        }

        #rightSideModalBody>.controls .action-btn {
            flex: 1 1 calc(50% - 6px);
            justify-content: center;
            padding: 8px 10px;
            font-size: 12px;
        }

        #rightSideModalBody>.controls .invoice-pay-status {
            flex: 1 1 100%;
            justify-content: center;
        }
    }

    @@container inv-modal (max-width: 480px) {
        .invoice-box .sheet {
            padding: 12px 8px;
        }

        .invoice-box .inv-company {
            font-size: 14px;
        }

        .invoice-box table.items,
        .invoice-box table.items-table,
        .invoice-box table.ledger,
        .invoice-box .rider-template-items table {
            min-width: 520px;
        }

        #rightSideModalBody>.controls {
            gap: 6px;
        }

        #rightSideModalBody>.controls .action-btn {
            flex: 1 1 calc(50% - 6px);
            min-width: 0;
        }
    }
</style>
