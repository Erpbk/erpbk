<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Customer Invoice #{{ $invoice->invoice_number ?? $invoice->id }} Month: {{ date('M-Y', strtotime($invoice->billing_month)) }}</title>
    <style>
        /* ----- RESET & GLOBAL (modern card style) ----- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #eef2f5;
            margin: 0;
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
        .no-border td {
            border: none;
            padding: 4px 6px;
        }

        /* ----- HEADER STYLES (premium palette) ----- */
        .primary-header { background: #211c1d; color: white; font-weight: bold; }
        .secondary-header { background: #004aad; color: white; font-weight: bold; }
        .accent-total { background: #5271ff; color: white; font-weight: bold; }
        .light-header { background: #e6f1ff; color: #004aad; font-weight: bold; }
        .amount-highlight { background: #2A62FF; color: white; font-weight: bold; }
        .success-highlight { background: #004aad; color: white; font-weight: bold; }
        .yellow { background: #ffff00; font-weight: bold; padding: 3px 6px; display: inline-block; }

        /* ----- CARD LAYOUT ----- */
        .customer-card, .details-card {
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
        .print-btn {
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
        .print-btn:hover {
            background: #2A62FF;
        }
        .controls {
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
            .customer-card, .details-card {
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
        @php
        $settings = DB::table('settings')->pluck('value', 'name')->toArray();
        $running_total = 0;
        $subtotal_from_items = 0;
        @endphp

        <!-- HEADER: Logo + Company + Title -->
        <table style="margin-bottom: 20px; border: none; background: transparent;">
            <tr style="border: none;">
                <td style="width: 33%; border: none !important; vertical-align: middle;">
                    <img src="{{ URL::asset('assets/img/logo-full.png') }}" width="150" alt="logo" />
                </td>
                <td style="width: 34%; text-align: center; border: none !important;">
                    <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight:700;">{{ $settings['company_name'] ?? '' }}</h4>
                    <p style="margin: 3px 0; font-size: 12px;">{{ $settings['company_address'] ?? '' }}</p>
                    <p style="margin: 3px 0; font-size: 12px;">TRN {{ $settings['vat_number'] ?? '' }}</p>
                </td>
                <td style="width: 33%; text-align: center; border: none !important;">
                    <h2 style="margin: 0; font-weight: 800; color: #004aad; font-size: 24px;">CUSTOMER INVOICE</h2>
                </td>
            </table>
        </table>

        <!-- Two card layout: Customer Details + Invoice Info -->
        <div class="flex-row-cards">
            <!-- Customer Details Card -->
            <div class="customer-card">
                <div class="card-header">
                    <strong>👤 Customer Details</strong>
                </div>
                <div class="details-grid">
                    <span class="detail-label">Customer Name:</span>
                    <span class="detail-value">{{ $invoice->customer->name ?? 'N/A' }}</span>
                    <span class="detail-label">TRN Number:</span>
                    <span class="detail-value">{{ $invoice->customer->tax_number ?? 'N/A' }}</span>
                    <span class="detail-label">Contact Number:</span>
                    <span class="detail-value">{{ $invoice->customer->contact_number ?? 'N/A' }}</span>
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $invoice->customer->email ?? 'N/A' }}</span>
                </div>
            </div>

            <!-- Invoice Details Card -->
            <div class="details-card">
                <div class="card-header">
                    <strong>📄 Invoice Details</strong>
                </div>
                <div class="details-grid">
                    <span class="detail-label">Invoice No:</span>
                    <span class="detail-value">{{ $invoice->invoice_number ?? 'CI-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</span>
                    <span class="detail-label">Invoice Date:</span>
                    <span class="detail-value">{{ date('d M Y', strtotime($invoice->inv_date)) }}</span>
                    <span class="detail-label">Billing Month:</span>
                    <span class="detail-value">{{ date('F Y', strtotime($invoice->billing_month)) }}</span>
                    <span class="detail-label">Service Period:</span>
                    <span class="detail-value">{{ date('d M Y', strtotime($invoice->date_from)) }} - {{ date('d M Y', strtotime($invoice->date_to)) }}</span>
                </div>
            </div>
        </div>

        <!-- Description Section (if any) -->
        @if($invoice->description)
        <div class="description-block">
            <strong>📝 Description</strong><br>
            <span style="color: #334155;">{{ $invoice->description }}</span>
        </div>
        @endif

        <!-- Main Items Table (modern clean table) -->
        @if($invoice->items && $invoice->items->count() > 0)
        <div style="overflow-x: auto;">
            <table class="items-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 5%;">Sr.</th>
                        <th style="width: 35%;">Product / Service Description</th>
                        <th style="width: 8%;">Qty</th>
                        <th style="width: 12%;">Rate ({{ \App\Helpers\Currency::code() }})</th>
                        <th style="width: 12%;">Amount</th>
                        <th style="width: 10%;">VAT (%)</th>
                        <th style="width: 12%;">VAT Amount</th>
                        <th style="width: 13%;">Total ({{ \App\Helpers\Currency::code() }})</th>
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
                        <td class="num">{{ $key + 1 }}</td>
                        <td>{{ $item->item_name ?? 'N/A' }}</td>
                        <td class="num">{{ number_format($quantity, 2) }}</td>
                        <td class="num">{{ number_format($rate, 2) }}</td>
                        <td class="num">{{ number_format($subtotal, 2) }}</td>
                        <td class="num">{{ number_format($vatPercent, 2) }}%</td>
                        <td class="num">{{ number_format($vatAmount, 2) }}</td>
                        <td class="num">{{ number_format($rowTotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Financial Summary (compact card on right) -->
        <div class="financial-summary">
            <table>
                <thead>
                    <tr><th colspan="2" class="secondary-header">Financial Summary</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight: 600;">Subtotal (excl. VAT):</td>
                        <td class="num">{{ number_format($invoice->subtotal ?? $subtotal_from_items, 2) }}</td>
                    </tr>
                    @if(($invoice->vat ?? 0) > 0)
                    <tr>
                        <td style="font-weight: 600;">VAT Amount ({{ $invoice->vat_percent ?? 5 }}%):</td>
                        <td class="num">{{ number_format($invoice->vat ?? 0, 2) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Grand Total Card (modern badge) -->
        <div class="grand-total-wrapper">
            <div class="grand-total-card">
                <div>TOTAL AMOUNT</div>
                <div>{{ number_format($invoice->total ?? $running_total, 2) }} {{ \App\Helpers\Currency::code() }}</div>
            </div>
        </div>

        <!-- Notes Section -->
        @if($invoice->notes)
        <div class="notes-section">
            <strong>📌 Notes:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif

        @else
        <div style="text-align: center; padding: 40px; background: #f9f9fc; border-radius: 12px;">
            <p>No items found for this invoice.</p>
        </div>
        @endif

        <!-- Footer -->
        <div style="height: 20px;"></div>
        <div style="position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 11px; color: #5b6e8c; border-top: 1px solid #e2e8f0; padding-top: 16px; padding-bottom: 0px; background: white; width: 100%; z-index: 1000;">
            <p style="margin-top: 5px; font-size: 10px;">For queries contact: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}</p>
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