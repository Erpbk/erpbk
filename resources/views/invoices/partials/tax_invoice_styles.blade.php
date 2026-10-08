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
        margin: auto 8px 18px auto;
        width: fit-content;
        max-width: 920px;
        justify-content: center;
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
        margin: 6mm;
    }

    #invoice-print-root {
        position: fixed !important;
        left: -10000px !important;
        top: 0 !important;
        width: 198mm !important;
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
            min-height: 285mm !important;
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
            max-width: 198mm !important;
            height: 100% !important;
            min-height: 285mm !important;
            margin: 0 auto !important;
        }

        /* Short invoices: light vertical distribution without crushing soft cards */
        body.printing-invoice #invoice-print-root .invoice-print-fit.is-fill .invoice-box.invoice-print-tall,
        body.printing-invoice #invoice-print-root .invoice-print-fit.is-fill .invoice-box.invoice-print-tall .sheet {
            height: 100% !important;
            min-height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
        }

        body.printing-invoice #invoice-print-root .invoice-box.invoice-print-tall .sheet {
            justify-content: space-between !important;
            padding: 10px 6px !important;
        }

        body.printing-invoice #invoice-print-root .invoice-box.invoice-print-tall .inv-hdr {
            margin-bottom: 16px !important;
        }

        body.printing-invoice #invoice-print-root .invoice-box.invoice-print-tall .parties {
            margin-bottom: 14px !important;
        }

        body.printing-invoice #invoice-print-root .invoice-box.invoice-print-tall .rider-template-items,
        body.printing-invoice #invoice-print-root .invoice-box.invoice-print-tall .tbl-wrap {
            flex: 1 1 auto !important;
        }

        body.printing-invoice #invoice-print-root .invoice-box.invoice-print-tall .inv-totals-area {
            margin: 14px 0 !important;
        }

        body.printing-invoice #invoice-print-root .invoice-box.invoice-print-tall .foot {
            margin-top: 12px !important;
            padding-top: 10px !important;
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

    @@media screen and (max-width: 720px) {
        .invoice-box .sheet {
            padding: 18px 14px;
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
            margin-bottom: 0;
        }
    }
</style>
