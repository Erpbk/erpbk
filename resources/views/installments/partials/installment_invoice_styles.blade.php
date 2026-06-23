<style>
    /* Scoped clone of rider_invoices/show.blade.php styles for installment invoice (modal + standalone) */
    .visa-installment-invo-wrap * {
        box-sizing: border-box;
    }
    .visa-installment-invo-wrap {
        font-family: Calibri, Arial, sans-serif;
        font-size: 12px;
        color: #000;
        background: #eef2f5;
        margin: 0;
        padding: 20px;
    }
    .visa-installment-invo-wrap .invoice-box {
        max-width: 1200px;
        width: 100%;
        margin: 0 auto;
        background: white;
        padding: 20px 25px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        display: flex;
        flex-direction: column;
        min-height: auto;
    }
    .visa-installment-invo-wrap .invoice-box table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }
    .visa-installment-invo-wrap .invoice-box th,
    .visa-installment-invo-wrap .invoice-box td {
        border: 1px solid #ddd;
        padding: 8px 10px;
        font-size: 12px;
        vertical-align: top;
    }
    .visa-installment-invo-wrap .invoice-box th {
        background: #004aad;
        color: white;
        font-weight: 600;
        text-align: center;
    }
    .visa-installment-invo-wrap .invoice-box td {
        text-align: left;
    }
    .visa-installment-invo-wrap .invoice-box td.num {
        text-align: right;
    }
    .visa-installment-invo-wrap .no-border td {
        border: none;
        padding: 4px 6px;
    }
    .visa-installment-invo-wrap .primary-header { background: #211c1d; color: white; font-weight: bold; }
    .visa-installment-invo-wrap .secondary-header { background: #004aad; color: white; font-weight: bold; }
    .visa-installment-invo-wrap .accent-total { background: #5271ff; color: white; font-weight: bold; }
    .visa-installment-invo-wrap .light-header { background: #e6f1ff; color: #004aad; font-weight: bold; }
    .visa-installment-invo-wrap .amount-highlight { background: #2A62FF; color: white; font-weight: bold; }
    .visa-installment-invo-wrap .success-highlight { background: #004aad; color: white; font-weight: bold; }
    .visa-installment-invo-wrap .yellow-highlight { background: #ffff00; font-weight: bold; padding: 8px; }
    .visa-installment-invo-wrap .dark-accent { background: #211c1d; color: white; font-weight: bold; }
    .visa-installment-invo-wrap .print-btn {
        background: #004aad;
        color: #fff;
        border: none;
        padding: 8px 16px;
        font-size: 13px;
        cursor: pointer;
        border-radius: 6px;
        text-decoration: none;
        display: inline-block;
        font-weight: 500;
        transition: 0.2s;
    }
    .visa-installment-invo-wrap .print-btn:hover {
        background: #2A62FF;
    }
    .visa-installment-invo-wrap .controls {
        position: sticky;
        top: 10px;
        z-index: 100;
        display: flex;
        gap: 12px;
        background: white;
        padding: 10px 20px;
        border-radius: 40px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        width: 95%;
        justify-self: center;
        margin-left: auto;
        margin-right: auto;
        justify-content: flex-end;
    }
    .visa-installment-invo-wrap .rider-card,
    .visa-installment-invo-wrap .details-card {
        padding: 16px 18px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .visa-installment-invo-wrap .invoice-box .card-header {
        margin-bottom: 14px;
        padding-bottom: 8px;
        border-bottom: 2px solid #004aad;
        background-color: white !important;
    }
    .visa-installment-invo-wrap .invoice-box .card-header strong {
        color: #004aad;
        font-size: 15px;
        letter-spacing: 0.3px;
    }
    .visa-installment-invo-wrap .details-grid {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 12px 8px;
        align-items: baseline;
    }
    .visa-installment-invo-wrap .detail-item {
        display: contents;
    }
    .visa-installment-invo-wrap .detail-label {
        font-weight: 700;
        color: #2c3e66;
        font-size: 12px;
    }
    .visa-installment-invo-wrap .detail-value {
        color: #1e293b;
        font-weight: 500;
    }
    .visa-installment-invo-wrap .flex-row-cards {
        display: flex;
        gap: 20px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .visa-installment-invo-wrap .flex-row-cards > div {
        flex: 1;
        min-width: 280px;
    }
    .visa-installment-invo-wrap .description-block {
        background: #f8fafc;
        border-left: 4px solid #004aad;
        padding: 12px 18px;
        margin: 16px 0;
        border-radius: 10px;
    }
    .visa-installment-invo-wrap .notes-section {
        margin: 20px 0;
        padding: 12px 16px;
        background: #fef9e6;
        border-left: 4px solid #ffb347;
        border-radius: 8px;
    }
    .visa-installment-invo-wrap .items-table th,
    .visa-installment-invo-wrap .items-table td {
        border: 1px solid #ccc;
    }
    .visa-installment-invo-wrap .items-table th {
        background: #004aad;
        color: white;
        font-weight: 600;
        text-align: center;
    }
    .visa-installment-invo-wrap .financial-summary {
        display: flex;
        justify-content: flex-end;
        margin-top: 10px;
        margin-bottom: 15px;
    }
    .visa-installment-invo-wrap .financial-summary table {
        width: 45%;
        min-width: 270px;
        border: 1px solid #e2e8f0;
    }
    .visa-installment-invo-wrap .grand-total-wrapper {
        margin-top: 24px;
        text-align: right;
    }
    .visa-installment-invo-wrap .grand-total-card {
        display: inline-block;
        padding: 12px 28px;
        background: #004aad;
        color: white;
        border-radius: 30px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,74,173,0.2);
    }
    .visa-installment-invo-wrap .grand-total-card div:first-child {
        font-size: 14px;
        letter-spacing: 1px;
        margin-bottom: 4px;
    }
    .visa-installment-invo-wrap .grand-total-card div:last-child {
        font-size: 26px;
        font-weight: 800;
    }
    .visa-installment-invo-wrap .footer-note {
        margin-top: 28px;
        text-align: center;
        font-size: 11px;
        color: #5b6e8c;
        border-top: 1px solid #e2e8f0;
        padding-top: 16px;
        margin-top: auto;
    }
    .visa-installment-invo-wrap .yellow {
        background: #ffff00;
        font-weight: bold;
        padding: 3px 6px;
        display: inline-block;
    }
    .visa-installment-invo-wrap .red {
        color: red;
        font-weight: bold;
    }
    @media print {
        @page {
            size: A4 portrait;
            margin: 6mm;
        }
        html, body {
            height: auto !important;
            overflow: visible !important;
        }
        /* Fit entire installment invoice on one sheet (Chrome/Edge; zoom is ignored by some Firefox builds) */
        .visa-installment-invo-wrap {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 9px !important;
            line-height: 1.25 !important;
            zoom: 0.92;
            -moz-transform: scale(0.92);
            -moz-transform-origin: top left;
        }
        .visa-installment-invo-wrap .invoice-box {
            box-shadow: none !important;
            padding: 4px 8px !important;
            border-radius: 0 !important;
            min-height: 0 !important;
            max-width: 100% !important;
        }
        .visa-installment-invo-wrap .controls {
            display: none !important;
        }
        .visa-installment-invo-wrap .invoice-box > table.no-border {
            margin-bottom: 8px !important;
        }
        .visa-installment-invo-wrap .invoice-box > table.no-border img {
            max-width: 120px !important;
            height: auto !important;
        }
        .visa-installment-invo-wrap .invoice-box h2 {
            font-size: 16px !important;
        }
        .visa-installment-invo-wrap .invoice-box h4 {
            font-size: 11px !important;
        }
        .visa-installment-invo-wrap .flex-row-cards {
            gap: 8px !important;
            margin-bottom: 8px !important;
        }
        .visa-installment-invo-wrap .rider-card,
        .visa-installment-invo-wrap .details-card {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            padding: 8px 10px !important;
            break-inside: avoid;
        }
        .visa-installment-invo-wrap .invoice-box .card-header {
            margin-bottom: 6px !important;
            padding-bottom: 4px !important;
        }
        .visa-installment-invo-wrap .invoice-box .card-header strong {
            font-size: 12px !important;
        }
        .visa-installment-invo-wrap .details-grid {
            gap: 4px 6px !important;
            grid-template-columns: 110px 1fr !important;
        }
        .visa-installment-invo-wrap .detail-label,
        .visa-installment-invo-wrap .detail-value {
            font-size: 9px !important;
        }
        .visa-installment-invo-wrap .invoice-box th,
        .visa-installment-invo-wrap .invoice-box td {
            padding: 3px 5px !important;
            font-size: 9px !important;
        }
        .visa-installment-invo-wrap .items-table th,
        .visa-installment-invo-wrap .items-table td {
            padding: 2px 4px !important;
            font-size: 9px !important;
        }
        .visa-installment-invo-wrap .financial-summary {
            margin-top: 4px !important;
            margin-bottom: 4px !important;
        }
        .visa-installment-invo-wrap .financial-summary table {
            width: 42% !important;
            min-width: 200px !important;
        }
        .visa-installment-invo-wrap .grand-total-wrapper {
            margin-top: 6px !important;
        }
        .visa-installment-invo-wrap .grand-total-card {
            padding: 6px 16px !important;
        }
        .visa-installment-invo-wrap .grand-total-card div:first-child {
            font-size: 10px !important;
            margin-bottom: 2px !important;
        }
        .visa-installment-invo-wrap .grand-total-card div:last-child {
            font-size: 16px !important;
        }
        .visa-installment-invo-wrap .notes-section {
            margin: 6px 0 !important;
            padding: 6px 10px !important;
            font-size: 8px !important;
        }
        .visa-installment-invo-wrap .installment-sign-block {
            margin-top: 8px !important;
        }
        .visa-installment-invo-wrap .installment-sign-block .sign-spacer {
            display: none !important;
        }
        .visa-installment-invo-wrap .footer-note {
            margin-top: 6px !important;
            padding-top: 6px !important;
            font-size: 8px !important;
        }
        .visa-installment-invo-wrap th,
        .visa-installment-invo-wrap .secondary-header,
        .visa-installment-invo-wrap .card-header strong,
        .visa-installment-invo-wrap .grand-total-card {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
    @media (max-width: 700px) {
        .visa-installment-invo-wrap .flex-row-cards {
            flex-direction: column;
        }
        .visa-installment-invo-wrap .invoice-box {
            padding: 15px;
        }
        .visa-installment-invo-wrap .financial-summary table {
            width: 100%;
        }
    }
</style>
