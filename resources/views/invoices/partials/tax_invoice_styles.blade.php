    <style>
        .invoice-box,
        .invoice-box * {
            box-sizing: border-box;
        }

        @php $invoiceBrand =$brand ?? ($companyBrand ?? []);
        $invBlue =$invoiceBrand['primary_color'] ?? '#004aad';
        $invBlue2 =$invoiceBrand['secondary_color'] ?? ($invoiceBrand['primary_dark'] ?? '#1a5fc4');
        $invBlueSoft =$invoiceBrand['primary_soft'] ?? ($invoiceBrand['primary_light'] ?? '#eef4fc');
        $invBlueLine =$invoiceBrand['border_color'] ?? '#c5d8f0';
        $invBlueRgb =$invoiceBrand['primary_rgb'] ?? '0, 74, 173';

        @endphp body:has(> .invoice-box),
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
            --blue: {
                    {
                    $invBlue
                }
            }

            ;

            --blue-2: {
                    {
                    $invBlue2
                }
            }

            ;

            --blue-soft: {
                    {
                    $invBlueSoft
                }
            }

            ;

            --blue-line: {
                    {
                    $invBlueLine
                }
            }

            ;

            --blue-rgb: {
                    {
                    $invBlueRgb
                }
            }

            ;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --paper: #ffffff;
            max-width: 920px;
            width: 100%;
            margin: 0 auto;
            background: var(--paper);
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04),
            0 12px 40px rgba(var(--blue-rgb), 0.1);
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
            grid-template-columns: minmax(220px, 280px) 1fr auto;
            gap: 16px;
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
            width: 100%;
            max-width: 280px;
            min-height: 72px;
            height: auto;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            grid-column: 1;
            overflow: visible;
        }

        .invoice-box .brand-logo img {
            display: block;
            width: auto;
            height: auto;
            max-width: 280px;
            max-height: 100px;
            object-fit: contain;
            object-position: left center;
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
            justify-content: center;
            min-height: 80px;
        }

        .invoice-box .brand-text {
            grid-column: 2;
            text-align: center;
            padding: 0 8px;
            min-width: 0;
        }

        .invoice-box .brand-text h1 {
            margin: 0 0 6px;
            font-size: 16px;
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
            border-left: 4px solid var(--blue);
            border-radius: 6px;
            padding: 16px 18px;
            min-height: 100%;
        }

        .invoice-box .party.alt {
            background: #f8fafc;
            border-color: var(--line);
            border-left: 4px solid var(--blue);
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
            border: 1px solid var(--line);
            border-left: 4px solid var(--blue);
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
            border-left: 4px solid var(--blue);
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
            align-items: stretch;
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
            border-left: 4px solid var(--blue);
            border-top: 2px solid var(--blue);
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            align-self: stretch;
            box-sizing: border-box;
        }

        .invoice-box .totals-notes h4 {
            margin: 0 0 8px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.9px;
            text-transform: uppercase;
            color: var(--blue);
            flex-shrink: 0;
        }

        .invoice-box .totals-notes .body {
            font-size: 12px;
            color: #334155;
            line-height: 1.75;
            flex: 1 1 auto;
        }

        .invoice-box .totals {
            width: 300px;
            flex-shrink: 0;
            margin-left: auto;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .invoice-box .totals .line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px;
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
            padding-top: 22px;
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
            border-left: 4px solid var(--blue);
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

        @keyframes invoice-pay-status-blink {

            0%,
            100% {
                opacity: 1;
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4);
            }

            50% {
                opacity: 0.55;
                box-shadow: 0 0 0 4px rgba(34, 197, 94, 0);
            }
        }

        @keyframes invoice-pay-status-blink-red {

            0%,
            100% {
                opacity: 1;
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }

            50% {
                opacity: 0.55;
                box-shadow: 0 0 0 4px rgba(239, 68, 68, 0);
            }
        }

        #rightSideModalBody>.controls .invoice-pay-status.is-partial,
        body>.controls .invoice-pay-status.is-partial {
            color: #92400e;
            background: #fef3c7;
            border-color: #fcd34d;
            animation-name: invoice-pay-status-blink-amber;
        }

        @keyframes invoice-pay-status-blink-amber {

            0%,
            100% {
                opacity: 1;
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
            }

            50% {
                opacity: 0.55;
                box-shadow: 0 0 0 4px rgba(245, 158, 11, 0);
            }
        }

        #rightSideModalBody>.controls .action-btn,
        body>.controls .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;

            color: {
                    {
                    $invBlue
                }
            }

            ;

            border: 1px solid {
                    {
                    $invBlue
                }
            }

            ;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.3;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.15s,
            color 0.15s;
            font-family: inherit;
        }

        #rightSideModalBody>.controls .action-btn i,
        body>.controls .action-btn i {
            font-size: 15px;
            line-height: 1;
        }

        #rightSideModalBody>.controls .action-btn:hover,
        body>.controls .action-btn:hover {
            background: {
                    {
                    $invBlueSoft
                }
            }

            ;

            color: {
                    {
                    $invBlue
                }
            }

            ;
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
            margin: 6mm;
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
                font-size: 10px !important;
                line-height: 1.3 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .controls,
            .no-print {
                display: none !important;
            }

            /* Full A4 width — JS fitInvoiceToSinglePage scales to one page */
            .invoice-fit-wrap {
                width: 100% !important;
                overflow: hidden !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
            }

            .invoice-box {
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: none !important;
                width: 100% !important;
                min-width: 100% !important;
                margin: 0 !important;
                overflow: visible !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
            }

            .invoice-box .band {
                display: none !important;
                height: 0 !important;
            }

            .invoice-box .sheet {
                padding: 8px 10px !important;
                width: 100% !important;
            }

            /* Header */
            .invoice-box .hdr {
                display: grid !important;
                grid-template-columns: minmax(180px, 220px) 1fr auto !important;
                gap: 8px !important;
                align-items: center !important;
                margin-bottom: 8px !important;
                padding-bottom: 8px !important;
                border-bottom-width: 2px !important;
                width: 100% !important;
            }

            .invoice-box .brand {
                display: contents !important;
            }

            .invoice-box .brand-logo {
                width: 100% !important;
                max-width: 200px !important;
                min-height: 48px !important;
                height: auto !important;
                overflow: visible !important;
            }

            .invoice-box .brand-logo img {
                display: block !important;
                width: auto !important;
                height: auto !important;
                max-width: 200px !important;
                max-height: 56px !important;
                object-fit: contain !important;
                object-position: left center !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
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
                gap: 8px !important;
                margin-bottom: 8px !important;
                width: 100% !important;
            }

            .invoice-box .party {
                padding: 6px 8px !important;
            }

            .invoice-box .party-title {
                margin-bottom: 4px !important;
                font-size: 8.5px !important;
            }

            .invoice-box .party-name {
                font-size: 11px !important;
                margin-bottom: 3px !important;
            }

            .invoice-box .party-grid {
                gap: 2px !important;
            }

            .invoice-box .party-line {
                grid-template-columns: 70px 1fr !important;
                gap: 4px !important;
                font-size: 9px !important;
            }

            /* Description */
            .invoice-box .desc {
                margin-bottom: 6px !important;
                padding: 5px 8px !important;
                width: 100% !important;
            }

            .invoice-box .desc .t {
                font-size: 8px !important;
                margin-bottom: 2px !important;
            }

            .invoice-box .desc p {
                font-size: 9.5px !important;
                margin: 0 !important;
                line-height: 1.3 !important;
            }

            /* Table — stretch full sheet width (items + rider items-table) */
            .invoice-box .tbl-wrap {
                margin-bottom: 6px !important;
                border-radius: 4px !important;
                width: 100% !important;
                overflow: visible !important;
            }

            .invoice-box table.items,
            .invoice-box table.items-table,
            .invoice-box .rider-template-items table,
            .invoice-box .sheet>table {
                width: 100% !important;
                table-layout: auto !important;
                margin-bottom: 6px !important;
            }

            .invoice-box table.items thead th,
            .invoice-box table.items-table th,
            .invoice-box .rider-template-items table th,
            .invoice-box .sheet>table th {
                font-size: 8.5px !important;
                padding: 4px 5px !important;
                letter-spacing: 0.2px !important;
            }

            .invoice-box table.items thead th.col-desc,
            .invoice-box table.items tbody td.col-desc {
                width: auto !important;
            }

            .invoice-box table.items tbody td,
            .invoice-box table.items-table td,
            .invoice-box .rider-template-items table td,
            .invoice-box .sheet>table td {
                font-size: 9px !important;
                padding: 3px 5px !important;
                line-height: 1.25 !important;
            }

            .invoice-box table.items tbody tr:nth-child(even) td {
                background: #f8fafc !important;
            }

            /* Totals */
            .invoice-box .totals-area {
                display: flex !important;
                justify-content: space-between !important;
                align-items: stretch !important;
                gap: 12px !important;
                margin: 6px 0 4px !important;
                width: 100% !important;
            }

            .invoice-box .totals-notes {
                flex: 1 !important;
                max-width: calc(100% - 260px) !important;
                padding: 5px 8px !important;
                display: flex !important;
                flex-direction: column !important;
                align-self: stretch !important;
            }

            .invoice-box .totals-notes h4 {
                font-size: 8px !important;
                margin-bottom: 2px !important;
            }

            .invoice-box .totals-notes .body {
                font-size: 8.5px !important;
                line-height: 1.35 !important;
                flex: 1 1 auto !important;
            }

            .invoice-box .totals {
                width: 240px !important;
                max-width: 42% !important;
                flex-shrink: 0 !important;
                margin-left: auto !important;
            }

            .invoice-box .totals .line {
                padding: 3px 10px !important;
                font-size: 9px !important;
            }

            .invoice-box .totals .grand {
                margin-top: 3px !important;
                padding: 6px 10px !important;
                border-radius: 4px !important;
            }

            .invoice-box .totals .grand .k {
                font-size: 8.5px !important;
            }

            .invoice-box .totals .grand .v {
                font-size: 12px !important;
            }

            /* Notes */
            .invoice-box .footnotes {
                display: grid !important;
                gap: 6px !important;
                margin-top: 6px !important;
                padding-top: 6px !important;
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
                padding: 5px 8px !important;
                border-left: 4px solid var(--blue) !important;
            }

            .invoice-box .note-card h4 {
                font-size: 8px !important;
                margin-bottom: 2px !important;
            }

            .invoice-box .note-card .body {
                font-size: 8.5px !important;
                line-height: 1.35 !important;
                max-height: none !important;
                overflow: visible !important;
            }

            .invoice-box .empty {
                padding: 10px !important;
                font-size: 10px !important;
            }

            /* Footer */
            .invoice-box .foot {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                margin-top: 6px !important;
                padding-top: 4px !important;
                font-size: 8.5px !important;
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

            /* Prefer keeping totals/terms with the tables above (avoid orphan page 2) */
            .invoice-box .hdr,
            .invoice-box .parties,
            .invoice-box .desc,
            .invoice-box .totals-area,
            .invoice-box .footnotes,
            .invoice-box .foot,
            .invoice-box table.items thead,
            .invoice-box table.items tr,
            .invoice-box table.items-table tr,
            .invoice-box .rider-template-items table tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .invoice-box .totals-area,
            .invoice-box .footnotes,
            .invoice-box .foot {
                page-break-before: avoid !important;
                break-before: avoid-page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .invoice-box .tbl-wrap {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .invoice-box .hdr,
            .invoice-box .parties,
            .invoice-box .desc,
            .invoice-box table.items,
            .invoice-box table.items-table,
            .invoice-box .rider-template-items {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
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