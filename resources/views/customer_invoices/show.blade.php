<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customer Invoice #{{ $invoice->invoice_number ?? $invoice->id }} Month: {{ date('M-Y', strtotime($invoice->billing_month)) }}</title>
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

        .invoice-box {
            --blue: #004aad;
            --blue-2: #1a5fc4;
            --blue-soft: #eef4fc;
            --blue-line: #c5d8f0;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --paper: #ffffff;
            max-width: 920px;
            width: 100%;
            margin: 0 auto;
            background: var(--paper);
            border-radius: 4px;
            box-shadow:
                0 1px 2px rgba(15, 23, 42, 0.04),
                0 12px 40px rgba(0, 74, 173, 0.1);
            overflow: hidden;
            position: relative;
        }

        /* Top brand strip */
        .invoice-box .band {
            height: 2px;
            background: var(--blue);
        }

        .invoice-box .sheet {
            padding: 36px 40px 28px;
            position: relative;
        }

        /* ========== HEADER ========== */
        .invoice-box .hdr {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 24px;
            border-bottom: 2px solid var(--blue);
        }

        .invoice-box .brand {
            display: contents;
        }

        .invoice-box .brand-logo {
            flex-shrink: 0;
            width: 165px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            grid-column: 1;
        }

        .invoice-box .brand-logo img {
            max-width: 200px;
            max-height: 80px;
            object-fit: contain;
        }

        .invoice-box .brand-logo.placeholder {
            background: var(--blue-soft);
            border: 1px dashed var(--blue-line);
            border-radius: 6px;
            color: var(--blue);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .invoice-box .brand-text {
            grid-column: 2;
            text-align: center;
            padding: 0 8px;
            min-width: 0;
        }

        .invoice-box .brand-text h1 {
            margin: 0 0 6px;
            font-size: 18px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.02em;
            line-height: 1.25;
        }

        .invoice-box .brand-text .meta {
            margin: 0;
            font-size: 11.5px;
            color: var(--muted);
            line-height: 1.55;
        }

        .invoice-box .doc-stamp {
            text-align: right;
            min-width: 200px;
            grid-column: 3;
        }

        .invoice-box .doc-stamp .label {
            display: inline-block;
            background: var(--blue);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 7px 14px;
            border-radius: 6px;
            margin-bottom: 12px;
        }

        .invoice-box .doc-stamp .kv {
            display: grid;
            grid-template-columns: auto auto;
            gap: 4px 14px;
            justify-content: end;
            font-size: 12px;
        }

        .invoice-box .doc-stamp .kv span:nth-child(odd) {
            color: var(--muted);
            font-weight: 500;
            text-align: left;
        }

        .invoice-box .doc-stamp .kv span:nth-child(even) {
            color: var(--ink);
            font-weight: 700;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* ========== PARTIES ========== */
        .invoice-box .parties {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 20px;
            margin-bottom: 26px;
        }

        .invoice-box .party {
            background: var(--blue-soft);
            border: 1px solid var(--blue-line);
            border-radius: 6px;
            padding: 16px 18px;
            min-height: 100%;
        }

        .invoice-box .party.alt {
            background: #f8fafc;
            border-color: var(--line);
        }

        .invoice-box .party-title {
            margin: 0 0 12px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.1px;
            text-transform: uppercase;
            color: var(--blue);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .invoice-box .party-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--blue-line);
        }

        .invoice-box .party.alt .party-title {
            color: #475569;
        }

        .invoice-box .party.alt .party-title::after {
            background: var(--line);
        }

        .invoice-box .party-name {
            margin: 0 0 10px;
            font-size: 15px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.3;
        }

        .invoice-box .party-grid {
            display: grid;
            gap: 6px;
        }

        .invoice-box .party-line {
            display: grid;
            grid-template-columns: 92px 1fr;
            gap: 8px;
            font-size: 12px;
        }

        .invoice-box .party-line .k {
            color: var(--muted);
            font-weight: 500;
        }

        .invoice-box .party-line .v {
            color: var(--ink);
            font-weight: 600;
            word-break: break-word;
        }

        /* ========== DESCRIPTION ========== */
        .invoice-box .desc {
            margin-bottom: 22px;
            padding: 12px 16px;
            border-left: 3px solid var(--blue);
            background: #f8fafc;
            border-radius: 6px;
        }

        .invoice-box .desc .t {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: var(--blue);
            margin-bottom: 4px;
        }

        .invoice-box .desc p {
            margin: 0;
            color: #334155;
            font-size: 12.5px;
        }

        /* ========== ITEMS TABLE ========== */
        .invoice-box .tbl-wrap {
            border: 1px solid var(--line);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .invoice-box table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .invoice-box table.items thead th {
            background: var(--blue);
            color: #fff;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            padding: 11px 12px;
            border: none;
            text-align: right;
            white-space: nowrap;
        }

        .invoice-box table.items thead th.col-desc,
        .invoice-box table.items thead th.col-sr {
            text-align: left;
        }

        .invoice-box table.items thead th.col-sr {
            text-align: center;
            width: 44px;
        }

        .invoice-box table.items tbody td {
            padding: 11px 12px;
            border: none;
            border-bottom: 1px solid var(--line);
            font-size: 12.5px;
            color: var(--ink);
            vertical-align: middle;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .invoice-box table.items tbody td.col-desc {
            text-align: left;
            font-weight: 500;
        }

        .invoice-box table.items tbody td.col-sr {
            text-align: center;
            color: var(--muted);
            font-weight: 600;
            width: 44px;
        }

        .invoice-box table.items tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .invoice-box table.items tbody tr:last-child td {
            border-bottom: none;
        }

        .invoice-box table.items tbody td.total-cell {
            font-weight: 700;
            color: var(--blue);
        }

        /* ========== TOTALS ========== */
        .invoice-box .totals-area {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin: 18px 0 8px;
        }

        .invoice-box .totals-notes {
            flex: 1;
            min-width: 0;
            max-width: calc(100% - 324px);
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid var(--line);
            border-top: 2px solid var(--blue);
            border-radius: 6px;
        }

        .invoice-box .totals-notes h4 {
            margin: 0 0 8px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.9px;
            text-transform: uppercase;
            color: var(--blue);
        }

        .invoice-box .totals-notes .body {
            font-size: 12px;
            color: #334155;
            line-height: 1.75;
        }

        .invoice-box .totals {
            width: 300px;
            flex-shrink: 0;
            margin-left: auto;
        }

        .invoice-box .totals .line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--line);
            font-size: 12.5px;
        }

        .invoice-box .totals .line .k {
            color: var(--muted);
            font-weight: 500;
        }

        .invoice-box .totals .line .v {
            color: var(--ink);
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .invoice-box .totals .grand {
            margin-top: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            background: var(--blue);
            color: #fff;
            border-radius: 6px;
        }

        .invoice-box .totals .grand .k {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            opacity: 0.92;
        }

        .invoice-box .totals .grand .v {
            font-size: 18px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }

        /* ========== NOTES / TERMS ========== */
        .invoice-box .footnotes {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid var(--line);
        }

        .invoice-box .footnotes.one {
            grid-template-columns: 1fr;
        }

        .invoice-box .footnotes.three {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .invoice-box .note-card {
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid var(--line);
            border-left: 2px solid var(--blue);
            border-radius: 6px;
        }

        .invoice-box .note-card h4 {
            margin: 0 0 8px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.9px;
            text-transform: uppercase;
            color: var(--blue);
        }

        .invoice-box .note-card .body {
            font-size: 12px;
            color: #334155;
            line-height: 1.75;
        }

        .invoice-box .empty {
            text-align: center;
            padding: 48px 20px;
            color: var(--muted);
            background: #f8fafc;
            border: 1px dashed var(--line);
            border-radius: 6px;
            font-size: 13px;
        }

        /* ========== FOOTER ========== */
        .invoice-box .foot {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 11px;
            color: var(--muted);
        }

        .invoice-box .foot strong {
            color: var(--ink);
            font-weight: 600;
        }

        .invoice-box .foot .thanks {
            color: var(--blue);
            font-weight: 600;
            font-style: italic;
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
            /* background: #fff; */
            /* padding: 10px 14px; */
            border-radius: 10px;
            /* box-shadow: 0 2px 12px rgba(15, 23, 42, 0.1); */
            margin: auto 8px 18px auto;
            width: fit-content;
            max-width: 920px;
            justify-content: center;
        }

        #rightSideModalBody>.controls .action-btn,
        body>.controls .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            color: #004aad;
            border: 1px solid #004aad;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.3;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.15s, color 0.15s;
            font-family: inherit;
        }

        #rightSideModalBody>.controls .action-btn i,
        body>.controls .action-btn i {
            font-size: 15px;
            line-height: 1;
        }

        #rightSideModalBody>.controls .action-btn:hover,
        body>.controls .action-btn:hover {
            background: #eef4fc;
            color: #004aad;
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

        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }

        @media print {

            html,
            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                min-width: 100% !important;
                max-width: none !important;
                height: auto !important;
                font-size: 11px !important;
                line-height: 1.4 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .controls,
            .no-print {
                display: none !important;
            }

            /* Full A4 width — do NOT use break-inside:avoid on the whole box
               (browsers shrink-to-fit and leave large empty margins). */
            .invoice-box {
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: none !important;
                width: 100% !important;
                min-width: 100% !important;
                margin: 0 !important;
                overflow: visible !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }

            .invoice-box .band {
                display: none !important;
                height: 0 !important;
            }

            .invoice-box .sheet {
                padding: 0 !important;
                width: 100% !important;
            }

            /* Header */
            .invoice-box .hdr {
                display: grid !important;
                grid-template-columns: auto 1fr auto !important;
                gap: 12px !important;
                align-items: center !important;
                margin-bottom: 14px !important;
                padding-bottom: 12px !important;
                border-bottom-width: 2px !important;
                width: 100% !important;
            }

            .invoice-box .brand {
                display: contents !important;
            }

            .invoice-box .brand-logo {
                width: 165px !important;
                height: 80px !important;
            }

            .invoice-box .brand-logo img {
                max-width: 200px !important;
                max-height: 80px !important;
            }

            .invoice-box .brand-text {
                text-align: center !important;
                padding: 0 6px !important;
            }

            .invoice-box .brand-text h1 {
                font-size: 15px !important;
                margin-bottom: 4px !important;
            }

            .invoice-box .brand-text .meta {
                font-size: 10px !important;
                line-height: 1.4 !important;
            }

            .invoice-box .doc-stamp {
                min-width: 168px !important;
                text-align: right !important;
            }

            .invoice-box .doc-stamp .label {
                font-size: 11px !important;
                letter-spacing: 1px !important;
                padding: 6px 12px !important;
                margin-bottom: 8px !important;
            }

            .invoice-box .doc-stamp .kv {
                gap: 3px 10px !important;
                font-size: 10.5px !important;
                justify-content: end !important;
            }

            /* Parties — keep two columns on A4 */
            .invoice-box .parties {
                display: grid !important;
                grid-template-columns: 1.15fr 0.85fr !important;
                gap: 12px !important;
                margin-bottom: 14px !important;
                width: 100% !important;
            }

            .invoice-box .party {
                padding: 10px 12px !important;
            }

            .invoice-box .party-title {
                margin-bottom: 8px !important;
                font-size: 9.5px !important;
            }

            .invoice-box .party-name {
                font-size: 12px !important;
                margin-bottom: 5px !important;
            }

            .invoice-box .party-grid {
                gap: 4px !important;
            }

            .invoice-box .party-line {
                grid-template-columns: 78px 1fr !important;
                gap: 6px !important;
                font-size: 10.5px !important;
            }

            /* Description */
            .invoice-box .desc {
                margin-bottom: 12px !important;
                padding: 8px 12px !important;
                width: 100% !important;
            }

            .invoice-box .desc .t {
                font-size: 9px !important;
                margin-bottom: 3px !important;
            }

            .invoice-box .desc p {
                font-size: 11px !important;
            }

            /* Table — stretch full sheet width */
            .invoice-box .tbl-wrap {
                margin-bottom: 8px !important;
                border-radius: 4px !important;
                width: 100% !important;
                overflow: visible !important;
            }

            .invoice-box table.items {
                width: 100% !important;
                table-layout: auto !important;
            }

            .invoice-box table.items thead th {
                font-size: 9.5px !important;
                padding: 8px 8px !important;
                letter-spacing: 0.35px !important;
            }

            .invoice-box table.items thead th.col-desc,
            .invoice-box table.items tbody td.col-desc {
                width: auto !important;
            }

            .invoice-box table.items tbody td {
                font-size: 10.5px !important;
                padding: 7px 8px !important;
            }

            .invoice-box table.items tbody tr:nth-child(even) td {
                background: #f8fafc !important;
            }

            /* Totals */
            .invoice-box .totals-area {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                gap: 16px !important;
                margin: 12px 0 8px !important;
                width: 100% !important;
            }

            .invoice-box .totals-notes {
                flex: 1 !important;
                max-width: calc(100% - 300px) !important;
                padding: 8px 10px !important;
            }

            .invoice-box .totals-notes h4 {
                font-size: 9px !important;
                margin-bottom: 4px !important;
            }

            .invoice-box .totals-notes .body {
                font-size: 9.5px !important;
                line-height: 1.75 !important;
            }

            .invoice-box .totals {
                width: 280px !important;
                max-width: 42% !important;
                flex-shrink: 0 !important;
                margin-left: auto !important;
            }

            .invoice-box .totals .line {
                padding: 5px 0 !important;
                font-size: 10.5px !important;
            }

            .invoice-box .totals .grand {
                margin-top: 6px !important;
                padding: 10px 12px !important;
                border-radius: 4px !important;
            }

            .invoice-box .totals .grand .k {
                font-size: 10px !important;
            }

            .invoice-box .totals .grand .v {
                font-size: 14px !important;
            }

            /* Notes */
            .invoice-box .footnotes {
                display: grid !important;
                gap: 10px !important;
                margin-top: 12px !important;
                padding-top: 10px !important;
                width: 100% !important;
            }

            .invoice-box .footnotes:not(.three):not(.one) {
                grid-template-columns: 1fr 1fr !important;
            }

            .invoice-box .footnotes.one {
                grid-template-columns: 1fr !important;
            }

            .invoice-box .footnotes.three {
                grid-template-columns: 1fr 1fr 1fr !important;
            }

            .invoice-box .footnotes.one .note-card {
                width: 100% !important;
                max-width: 100% !important;
            }

            .invoice-box .note-card {
                padding: 8px 10px !important;
                border-left: 2px solid var(--blue) !important;
            }

            .invoice-box .note-card h4 {
                font-size: 9px !important;
                margin-bottom: 4px !important;
            }

            .invoice-box .note-card .body {
                font-size: 9.5px !important;
                line-height: 1.75 !important;
                max-height: none !important;
                overflow: visible !important;
            }

            .invoice-box .empty {
                padding: 16px !important;
                font-size: 11px !important;
            }

            /* Footer */
            .invoice-box .foot {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                margin-top: 14px !important;
                padding-top: 8px !important;
                font-size: 9.5px !important;
                width: 100% !important;
            }

            .invoice-box .band,
            .invoice-box .doc-stamp .label,
            .invoice-box table.items thead th,
            .invoice-box .totals .grand,
            .invoice-box .party,
            .invoice-box .note-card,
            .invoice-box .totals-notes {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            /* Keep small blocks together; allow the sheet/table to flow across pages */
            .invoice-box .hdr,
            .invoice-box .parties,
            .invoice-box .desc,
            .invoice-box .totals-area,
            .invoice-box .footnotes,
            .invoice-box .foot,
            .invoice-box table.items thead,
            .invoice-box table.items tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .invoice-box .tbl-wrap {
                page-break-inside: auto !important;
                break-inside: auto !important;
            }
        }

        @media screen and (max-width: 720px) {
            .invoice-box .sheet {
                padding: 22px 18px;
            }

            .invoice-box .hdr,
            .invoice-box .parties,
            .invoice-box .footnotes,
            .invoice-box .footnotes.three {
                grid-template-columns: 1fr;
            }

            .invoice-box .hdr {
                gap: 16px;
            }

            .invoice-box .brand {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }

            .invoice-box .brand-logo,
            .invoice-box .brand-text,
            .invoice-box .doc-stamp {
                grid-column: auto;
            }

            .invoice-box .brand-text {
                text-align: center;
                padding: 0;
            }

            .invoice-box .doc-stamp {
                text-align: left;
                min-width: 0;
            }

            .invoice-box .doc-stamp .kv {
                justify-content: start;
            }

            .invoice-box .totals-area {
                flex-direction: column;
            }

            .invoice-box .totals-notes {
                max-width: 100%;
            }

            .invoice-box .totals {
                width: 100%;
            }

            .invoice-box .foot {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    @php
    $settings = company_table('settings')->pluck('value', 'name')->toArray();
    $running_total = 0;
    $subtotal_from_items = 0;
    $currency = \App\Helpers\Currency::code();
    $defaults = \App\Support\CustomerInvoiceDefaults::all();
    $invoiceTitle = $defaults['title'] ?: 'CUSTOMER INVOICE';
    $customerNote = $invoice->customer_note
    ?: ($invoice->customer->customer_note ?? null)
    ?: ($defaults['customer_notes'] ?: null);
    $termsAndConditions = $invoice->terms_and_conditions
    ?: ($invoice->customer->terms_and_conditions ?? null)
    ?: ($defaults['terms_and_conditions'] ?: null);
    $customerNoteLabel = $invoice->customer
    ? $invoice->customer->resolvedCustomerNoteLabel()
    : \App\Models\Customers::DEFAULT_CUSTOMER_NOTE_LABEL;
    $termsAndConditionsLabel = $invoice->customer
    ? $invoice->customer->resolvedTermsAndConditionsLabel()
    : \App\Models\Customers::DEFAULT_TERMS_AND_CONDITIONS_LABEL;
    $invoiceNumber = $invoice->invoice_number ?? ('CI-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT));
    $customerDisplay = $invoice->customer->company_name
    ?: ($invoice->customer->name ?? 'N/A');
    $projectName = $invoice->customer->name ?? 'N/A';
    $noteCards = collect([
    $termsAndConditions ? ['title' => $termsAndConditionsLabel, 'body' => $termsAndConditions] : null,
    $invoice->notes ? ['title' => 'Internal Notes', 'body' => $invoice->notes] : null,
    ])->filter()->values();
    $noteGridClass = match ($noteCards->count()) {
    1 => 'one',
    3 => 'three',
    default => '',
    };
    @endphp

    @if(empty($isPdf))
    <div class="controls no-print">
        @canany(['customers_invoices_edit', 'bike_on_rent_invoices_edit'])
        <a href="javascript:void(0);"
            class="action-btn show-modal"
            data-size="xl"
            data-title="Edit Invoice"
            data-close-right-modal="1"
            data-action="{{ route('customer_invoice.edit', $invoice->id) }}">
            <i class="ti ti-edit"></i><span>Edit</span>
        </a>
        @endcanany

        @canany(['customers_invoices_create', 'bike_on_rent_invoices_create'])
        <a href="javascript:void(0);"
            class="action-btn show-modal"
            data-size="xl"
            data-title="Clone Invoice"
            data-close-right-modal="1"
            data-action="{{ route('customer_invoice.clone', $invoice) }}">
            <i class="ti ti-copy"></i><span>Clone</span>
        </a>
        @endcanany

        @canany(['customers_invoices_delete', 'bike_on_rent_invoices_delete'])
        {!! Form::open(['route' => ['customer_invoices.destroy', $invoice], 'method' => 'DELETE', 'id' => 'formajax']) !!}
        <button type="submit"
            class="action-btn danger"
            onclick="return confirm('Are you sure you want to delete this invoice?');">
            <i class="ti ti-trash"></i><span>Delete</span>
        </button>
        {!! Form::close() !!}
        @endcanany

        <button type="button" class="action-btn js-print-modal-content">
            <i class="ti ti-file-description"></i><span>PDF/Print</span>
        </button>

        @can('email_create')
        <a href="javascript:void(0);"
            class="action-btn show-modal"
            data-size="md"
            data-title="Email Invoice"
            data-action="{{ route('customer_invoices.sendEmail', $invoice->id) }}">
            <i class="ti ti-arrow-right"></i><span>Email</span>
        </a>
        @endcan
    </div>
    @endif

    <div class="invoice-box">
        <div class="band"></div>
        <div class="sheet">

            {{-- Header --}}
            <header class="hdr">
                <div class="brand">
                    <div class="brand-logo {{ empty($settings['company_logo']) ? 'placeholder' : '' }}">
                        @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                        <img src="{{ storage_url($settings['company_logo']) }}" alt="{{ $settings['company_name'] ?? 'Logo' }}">
                        @else
                        Logo
                        @endif
                    </div>
                    <div class="brand-text">
                        <h1>{{ ucwords($settings['company_name'] ?? '') }}</h1>
                        <p class="meta">
                            @if(!empty($settings['vat_number']))
                            TRN: {{ $settings['vat_number'] }}
                            @endif
                            @if(!empty($settings['company_phone']) && !empty($settings['vat_number']))
                            &nbsp;·&nbsp;
                            @endif
                            <br>
                            @if(!empty($settings['company_phone']))
                            Tel: {{ $settings['company_phone'] }}
                            @endif
                            @if(!empty($settings['company_email']))
                            {{ $settings['company_email'] }}
                            @endif
                            <br>
                            @if(!empty($settings['company_address']))
                            {{ ucwords($settings['company_address']) }}<br>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="doc-stamp">
                    <div class="label">{{ $invoiceTitle }}</div>
                    <div class="kv">
                        <span>Invoice No</span><span>{{ $invoiceNumber }}</span>
                        <span>Date</span><span>{{ date('d M Y', strtotime($invoice->inv_date)) }}</span>
                        <span>Billing</span><span>{{ date('M Y', strtotime($invoice->billing_month)) }}</span>
                    </div>
                </div>
            </header>

            {{-- Parties --}}
            <div class="parties">
                <div class="party">
                    <h3 class="party-title">Bill To</h3>
                    <p class="party-name">{{ $customerDisplay }}</p>
                    <div class="party-grid">
                        <div class="party-line">
                            <span class="k">Project</span>
                            <span class="v">{{ $projectName }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">TRN</span>
                            <span class="v">{{ $invoice->customer->tax_number ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Contact</span>
                            <span class="v">{{ $invoice->customer->contact_number ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Email</span>
                            <span class="v">{{ $invoice->customer->company_email ?? '—' }}</span>
                        </div>
                    </div>
                </div>
                <div class="party alt">
                    <h3 class="party-title">Service Period</h3>
                    <div class="party-grid" style="margin-top: 4px;">
                        <div class="party-line">
                            <span class="k">From</span>
                            <span class="v">{{ date('d M Y', strtotime($invoice->date_from)) }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">To</span>
                            <span class="v">{{ date('d M Y', strtotime($invoice->date_to)) }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Month</span>
                            <span class="v">{{ date('F Y', strtotime($invoice->billing_month)) }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Currency</span>
                            <span class="v">{{ $currency }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($invoice->description)
            <div class="desc">
                <span class="t">Description</span>
                <p>{{ $invoice->description }}</p>
            </div>
            @endif

            {{-- Line items --}}
            @if($invoice->items && $invoice->items->count() > 0)
            <div class="tbl-wrap">
                <table class="items">
                    <thead>
                        <tr>
                            <th class="col-sr">#</th>
                            <th class="col-desc">Description</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th>VAT %</th>
                            <th>VAT</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $key => $item)
                        @php
                        $quantity = $item->quantity ?? 1;
                        $rate = $item->rate ?? 0;
                        $vatPercent = $item->vat ?? 0;
                        $subtotal = $quantity * $rate;
                        $vatAmount = $subtotal * ($vatPercent / 100);
                        $rowTotal = $subtotal + $vatAmount;
                        $running_total += $rowTotal;
                        $subtotal_from_items += $subtotal;
                        @endphp
                        <tr>
                            <td class="col-sr">{{ $key + 1 }}</td>
                            <td class="col-desc">{{ $item->item_name ?? 'N/A' }}</td>
                            <td>{{ number_format($quantity, 2) }}</td>
                            <td>{{ number_format($rate, 2) }}</td>
                            <td>{{ number_format($subtotal, 2) }}</td>
                            <td>{{ number_format($vatPercent, 2) }}%</td>
                            <td>{{ number_format($vatAmount, 2) }}</td>
                            <td class="total-cell">{{ number_format($rowTotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="totals-area">
                @if($customerNote)
                <div class="totals-notes">
                    <h4>{{ $customerNoteLabel }}</h4>
                    <div class="body">{!! nl2br(e($customerNote)) !!}</div>
                </div>
                @endif
                <div class="totals">
                    <div class="line">
                        <span class="k">Subtotal (excl. VAT)</span>
                        <span class="v">{{ number_format($invoice->subtotal ?? $subtotal_from_items, 2) }}</span>
                    </div>
                    @if(($invoice->vat ?? 0) != 0)
                    <div class="line">
                        <span class="k">VAT Amount</span>
                        <span class="v">{{ number_format($invoice->vat ?? 0, 2) }}</span>
                    </div>
                    @endif
                    <div class="grand">
                        <span class="k">Total Due</span>
                        <span class="v">{{ number_format($invoice->total ?? $running_total, 2) }} {{ $currency }}</span>
                    </div>
                </div>
            </div>
            @else
            <div class="empty">No line items on this invoice.</div>
            @endif

            @if($noteCards->isNotEmpty())
            <div class="footnotes {{ $noteGridClass }}">
                @foreach($noteCards as $card)
                <div class="note-card">
                    <h4>{{ $card['title'] }}</h4>
                    <div class="body">{!! nl2br(e($card['body'])) !!}</div>
                </div>
                @endforeach
            </div>
            @endif

            <footer class="foot">
                <span class="thanks">Thank you for your business.</span>
                <span>
                    Queries:
                    <strong>{{ $settings['company_phone'] ?? '—' }}</strong>
                    @if(!empty($settings['company_email']))
                    &nbsp;·&nbsp; <strong>{{ $settings['company_email'] }}</strong>
                    @endif
                </span>
            </footer>
        </div>
    </div>

    @if(empty($isPdf))
    <script>
        (function() {
            if (typeof window.printModalContent === 'function') {
                return;
            }
            window.printModalContent = function() {
                var box = document.querySelector('.invoice-box');
                if (!box) {
                    window.print();
                    return;
                }
                var styles = '';
                document.querySelectorAll('style').forEach(function(node) {
                    styles += node.outerHTML;
                });
                var title = (document.title || 'Customer Invoice').replace(/</g, '');
                var win = window.open('', '_blank');
                if (!win) {
                    window.print();
                    return;
                }
                win.document.open();
                win.document.write(
                    '<!DOCTYPE html><html><head><meta charset="utf-8">' +
                    '<meta name="viewport" content="width=794">' +
                    '<title>' + title + '</title>' +
                    styles +
                    '<style>' +
                    '@page{size:A4 portrait;margin:10mm 12mm;}' +
                    'html,body{margin:0!important;padding:0!important;background:#fff!important;' +
                    'width:100%!important;min-width:100%!important;max-width:none!important;}' +
                    '.invoice-box{max-width:none!important;width:100%!important;min-width:100%!important;' +
                    'margin:0!important;box-shadow:none!important;border-radius:0!important;' +
                    'page-break-inside:auto!important;break-inside:auto!important;}' +
                    '.invoice-box .sheet{padding:0!important;width:100%!important;}' +
                    '@media print{' +
                    'html,body,.invoice-box{width:100%!important;max-width:none!important;}' +
                    '.invoice-box{page-break-inside:auto!important;break-inside:auto!important;}' +
                    '}' +
                    '</style>' +
                    '</head><body>' + box.outerHTML + '</body></html>'
                );
                win.document.close();
                setTimeout(function() {
                    try {
                        win.focus();
                        win.print();
                    } catch (e) {}
                    win.onafterprint = function() {
                        win.close();
                    };
                }, 400);
            };
            document.querySelectorAll('.js-print-modal-content').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.printModalContent();
                });
            });
        })();
    </script>
    @endif
</body>

</html>