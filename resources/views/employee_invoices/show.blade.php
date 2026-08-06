<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Invoice #{{ $employeeInvoice->id }} Month: {{ date('M-Y', strtotime($employeeInvoice->billing_month)) }}</title>
    <style>
        /* Scoped to invoice content — do not leak into app when loaded in right-side modal */
        .invoice-box,
        .invoice-box * {
            box-sizing: border-box;
        }
        body:has(> .invoice-box),
        body:has(> .controls) {
            font-family: Calibri, Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #eef2f5;
            margin: 0;
            padding: 20px;
        }
        #rightSideModalBody:has(.invoice-box) {
            font-family: Calibri, Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #eef2f5;
            padding: 20px;
        }
        .invoice-box {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
        }

        /* ----- TABLES CLEAN BORDER ----- */
        .invoice-box table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .invoice-box th,
        .invoice-box td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            font-size: 12px;
            vertical-align: top;
        }
        .invoice-box th {
            background: #004aad;
            color: white;
            font-weight: 600;
            text-align: center;
        }
        .invoice-box td {
            text-align: left;
        }
        .invoice-box td.num {
            text-align: right;
        }
        .invoice-box .no-border td {
            border: none;
            padding: 4px 6px;
        }

        /* ----- HEADER STYLES (premium palette) ----- */
        .invoice-box .primary-header { background: #211c1d; color: white; font-weight: bold; }
        .invoice-box .secondary-header { background: #004aad; color: white; font-weight: bold; }
        .invoice-box .accent-total { background: #5271ff; color: white; font-weight: bold; }
        .invoice-box .light-header { background: #e6f1ff; color: #004aad; font-weight: bold; }
        .invoice-box .amount-highlight { background: #2A62FF; color: white; font-weight: bold; }
        .invoice-box .success-highlight { background: #004aad; color: white; font-weight: bold; }

        /* ----- CARD LAYOUT ----- */
        .invoice-box .employee-card, .invoice-box .details-card {
            padding: 16px 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .invoice-box .card-header {
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid #004aad;
            background-color: white !important;
        }
        .invoice-box .card-header strong {
            color: #004aad;
            font-size: 15px;
            letter-spacing: 0.3px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 12px 8px;
            align-items: baseline;
        }
        .detail-label {
            font-weight: 700;
            color: #2c3e66;
            font-size: 12px;
        }
        .detail-value {
            color: #1e293b;
            font-weight: 500;
        }
        .flex-row-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .flex-row-cards > div {
            flex: 1;
            min-width: 280px;
        }

        /* description block */
        .description-block {
            background: #f8fafc;
            border-left: 4px solid #004aad;
            padding: 12px 18px;
            margin: 16px 0;
            border-radius: 10px;
        }
        .notes-section {
            margin: 20px 0;
            padding: 12px 16px;
            background: #fef9e6;
            border-left: 4px solid #ffb347;
            border-radius: 8px;
        }

        /* financial summary & grand total */
        .financial-summary {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .financial-summary table {
            width: 45%;
            min-width: 270px;
            border: 1px solid #e2e8f0;
        }
        .grand-total-wrapper {
            margin-top: 24px;
            text-align: right;
        }
        .grand-total-card {
            display: inline-block;
            padding: 12px 28px;
            background: #004aad;
            color: white;
            border-radius: 30px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,74,173,0.2);
        }
        .grand-total-card div:first-child {
            font-size: 14px;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .grand-total-card div:last-child {
            font-size: 26px;
            font-weight: 800;
        }

        /* ----- PRINT BUTTONS & CONTROLS (supplier style) ----- */
        #rightSideModalBody .print-btn,        body > .controls .print-btn {
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
        #rightSideModalBody .print-btn:hover,        body > .controls .print-btn:hover {
            background: #2A62FF;
        }
        #rightSideModalBody > .controls,        body > .controls {
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

        .invoice-box .summary-table {
            width: 100%;
            margin-top: 12px;
        }
        .invoice-box .summary-table td:first-child {
            width: 70%;
        }

        .footer-note {
            margin-top: 28px;
            text-align: center;
            font-size: 11px;
            color: #5b6e8c;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            margin-top: auto;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .invoice-box {
                box-shadow: none;
                padding: 12px;
                border-radius: 0;
            }
            .action-buttons, .no-print {
                display: none !important;
            }
            .employee-card, .details-card {
                box-shadow: none;
                border: 1px solid #ccc;
                break-inside: avoid;
            }
            th, .secondary-header, .card-header strong, .grand-total-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @media (max-width: 700px) {
            .flex-row-cards {
                flex-direction: column;
            }
            .invoice-box {
                padding: 15px;
            }
            .financial-summary table {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="controls no-print">
        <button type="button" class="print-btn" onclick="printModalContent()">Print Invoice</button>
    </div>

    <div class="invoice-box">
        <!-- HEADER: Logo + Company + Title -->
        <table style="margin-bottom: 20px; border: none; background: transparent;">
            <tr style="border: none;">
                <td style="width: 33%; border: none !important; vertical-align: middle;">
                    @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                        <img src="{{ storage_url($settings['company_logo']) }}" width="150" alt="logo" />
                    @endif
                </td>
                <td style="width: 34%; text-align: center; align-content: center; border: none !important;">
                    <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight:700;">{{ ucwords($settings['company_name'] ?? '') }}</h4>
                    <p style="margin: 3px 0; font-size: 12px;">{{ ucwords($settings['company_address'] ?? '') }}</p>
                    <p style="margin: 3px 0; font-size: 12px;">TEL: {{ $settings['company_phone'] ?? '' }}</p>
                    <p style="margin: 3px 0; font-size: 12px;">TRN: {{ $settings['vat_number'] ?? '' }}</p>
                </td>
                <td style="width: 33%; text-align: center; align-content: center; border: none !important;">
                    <h4 style="margin: 0; font-weight: 600; color: #004aad; font-size: 24px;">EMPLOYEE INVOICE</h4>
                </td>
            </tr>
        </table>

        <!-- Two card layout: Employee Details + Invoice Info -->
        <div class="flex-row-cards">
            <div class="employee-card">
                <div class="card-header">
                    <strong>Employee Details</strong>
                </div>
                <div class="details-grid">
                    <span class="detail-label">Employee ID:</span>
                    <span class="detail-value">{{ $employeeInvoice->employee?->employee_id ?? 'N/A' }}</span>
                    <span class="detail-label">Employee Name:</span>
                    <span class="detail-value">{{ $employeeInvoice->employee?->name ?? 'N/A' }}</span>
                    <span class="detail-label">Designation:</span>
                    <span class="detail-value">{{ $employeeInvoice->employee?->designation ?? 'N/A' }}</span>
                    <span class="detail-label">Department:</span>
                    <span class="detail-value">{{ $employeeInvoice->employee?->department?->name ?? 'N/A' }}</span>
                </div>
            </div>

            <div class="details-card">
                <div class="card-header">
                    <strong>Invoice Details</strong>
                </div>
                <div class="details-grid">
                    <span class="detail-label">Invoice No:</span>
                    <span class="detail-value">{{ $invoiceNumber ?? $employeeInvoice->invoice_number }}</span>
                    <span class="detail-label">Invoice Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($employeeInvoice->inv_date)->format('d M Y') }}</span>
                    <span class="detail-label">Billing Month:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($employeeInvoice->billing_month)->format('M Y') }}</span>
                    <span class="detail-label">Service Period:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($employeeInvoice->billing_month)->startOfMonth()->format('d M Y') }} - {{ \Carbon\Carbon::parse($employeeInvoice->billing_month)->endOfMonth()->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        @if($employeeInvoice->descriptions)
        <div class="description-block">
            <strong>Description</strong><br>
            <span style="color: #334155;">{{ $employeeInvoice->descriptions }}</span>
        </div>
        @endif

        @if($employeeInvoice->notes)
        <div class="notes-section">
            <strong>Notes:</strong><br>
            {{ $employeeInvoice->notes }}
        </div>
        @endif

        @include('employee_invoices.partials.invoice_items_and_totals')

        <div class="footer-note">
            For queries reach: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.num').forEach(function(el) {
                let raw = el.innerText.trim();
                let num = parseFloat(raw.replace(/,/g, ''));
                if (!isNaN(num) && raw !== '') {
                    let formatted = num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    if (el.innerText !== formatted) el.innerText = formatted;
                }
            });
        });
    </script>
</body>
</html>