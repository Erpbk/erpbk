<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leasing Company Billing Invoice #{{ $invoice->invoice_number ?? $invoice->id }} Month: {{ date('M-Y', strtotime($invoice->billing_month)) }}</title>
    <style>
        /* ----- RESET & GLOBAL (modern card design from supplier invoice) ----- */
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
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            background: white;
            padding: 20px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ----- TABLES (clean border style) ----- */
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

        /* ----- HEADER STYLES (aligned with fuel/supplier design) ----- */
        .primary-header { background: #211c1d; color: white; }
        .secondary-header { background: #004aad; color: white; font-weight: bold; }
        .accent-total { background: #5271ff; color: white; }
        .light-header { background: #e6f1ff; color: #004aad; }
        .amount-highlight { background: #2A62FF; color: white; }
        .success-highlight { background: #004aad; color: white; font-weight: bold; }

        /* ----- PRINT BUTTONS & CONTROLS (matching modern template) ----- */
        .print-btn {
            background: #004aad;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            cursor: pointer;
            border-radius: 4px;
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            margin-bottom: 20px;
            width: 95%;
            justify-self: center;
            margin-left: auto;
            margin-right: auto;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        /* ----- CARD LAYOUT (modern cards from fuel invoice) ----- */
        .supplier-card, .details-card {
            padding: 16px 18px;
            margin-bottom: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
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
            min-width: 260px;
        }

        /* description block */
        .description-block {
            background: #f8fafc;
            border-left: 4px solid #004aad;
            padding: 12px 18px;
            margin: 20px 0;
            border-radius: 10px;
        }
        .description-block strong {
            color: #004aad;
            font-size: 13px;
        }

        /* Notes section */
        .notes-section {
            margin: 20px 0;
            padding: 12px 16px;
            background: #fef9e6;
            border-left: 4px solid #ffb347;
            border-radius: 8px;
        }

        /* Grand Total */
        .grand-total-wrapper {
            margin-top: 28px;
            text-align: right;
        }
        .grand-total-card {
            display: inline-block;
            padding: 12px 28px;
            background: #004aad;
            color: white;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,74,173,0.2);
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

        /* footer */
        .footer-note {
            margin-top: 28px;
            text-align: center;
            font-size: 11px;
            color: #5b6e8c;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            margin-top: auto;
        }

        /* items table + summary styling */
        .items-table th, .items-table td {
            border: 1px solid #ccc;
        }
        .items-table th {
            background: #004aad;
            color: white;
            font-weight: 600;
            text-align: center;
        }
        .summary-grid {
            
        }
        .summary-table {
            
        }
        .summary-table td {
            border: 1px solid #ddd;
            padding: 8px 12px;
        }
        .words-highlight {
            background: #2A62FF;
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            display: inline-block;
            font-size: 12px;
            margin-top: 15px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .invoice-box {
                box-shadow: none;
                padding: 10px;
                max-width: 100%;
                border-radius: 0;
            }
            .controls {
                display: none !important;
            }
            .supplier-card, .details-card {
                box-shadow: none;
                border: 1px solid #ccc;
                break-inside: avoid;
            }
            .grand-total-card {
                background: #004aad !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            th, .secondary-header, .card-header strong, .amount-highlight, .success-highlight {
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
            .summary-table {
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
        // Fetch settings for company info (matching supplier design)
        $settings = [];
        try {
            $settings = company_table('settings')->pluck('value', 'name')->toArray();
        } catch (\Exception $e) {
            $settings = [];
        }
        $companyName = $settings['company_name'] ?? ($invoice->customer->company_name ?? 'Leasing Company');
        $companyAddress = $settings['company_address'] ?? 'Dubai, UAE';
        $vatNumber = $settings['vat_number'] ?? 'TRN 123456789';
        $companyPhone = $settings['company_phone'] ?? '+971 4 123 4567';
        $companyEmail = $settings['company_email'] ?? 'info@leasingco.ae';
        
        $subtotal = $invoice->subtotal ?? 0;
        $totalVat = $invoice->vat ?? 0;
        $grandTotal = $invoice->total_amount ?? ($subtotal + $totalVat);
    @endphp

    <!-- HEADER: modern layout with logo, company details and title -->
    <table style="margin-bottom: 20px; border: none; background: transparent;">
        <tr style="border: none;">
            <td style="width: 33%; border: none !important; vertical-align: middle;">
                @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                    <img src="{{ Storage::url($settings['company_logo']) }}" width="150" alt="logo" />
                @endif
            </td>
            <td style="width: 34%; text-align: center; align-content: center; border: none !important;">
                <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight:700;">{{ ucwords($companyName) }}</h4>
                <p style="margin: 3px 0; font-size: 12px;">{{ ucwords($companyAddress) }}</p>
                <p style="margin: 3px 0; font-size: 12px;">TEL: {{ $companyPhone }}</p>
                <p style="margin: 3px 0; font-size: 12px;">TRN: {{ $vatNumber }}</p>
            </td>
            <td style="width: 33%; text-align: center; align-content: center; border: none !important;">
                <h2 style="margin: 0; font-weight: 800; color: #004aad; font-size: 22px;">RENTAL BILL</h2>
            </td>
        </tr>
    </table>

    <!-- CARD LAYOUT: Customer card + Invoice details card (flex row) -->
    <div class="flex-row-cards">
        <!-- Customer Details Card (similar to supplier card) -->
        <div class="supplier-card">
            <div class="card-header">
                <strong>🏢 Customer Details</strong>
            </div>
            <div class="details-grid">
                <span class="detail-label">Customer Name:</span>
                <span class="detail-value">{{ $invoice->customer->name ?? '—' }}</span>

                <span class="detail-label">Company Name:</span>
                <span class="detail-value">{{ $invoice->customer->company_name ?? ($invoice->customer->name ?? '—') }}</span>

                <span class="detail-label">Contact:</span>
                <span class="detail-value">{{ $invoice->customer->company_contact ?? '—' }}</span>
                
                <span class="detail-label">Email:</span>
                <span class="detail-value">{{ $invoice->customer->email ?? '—' }}</span>
            </div>
        </div>

        <!-- Invoice Details Card (modern) -->
        <div class="details-card">
            <div class="card-header">
                <strong>📄 Invoice Details</strong>
            </div>
            <div class="details-grid">
                <span class="detail-label">Invoice No:</span>
                <span class="detail-value">{{ $invoice->invoice_number ?? 'LBI-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}</span>

                <span class="detail-label">Customer Invoice No:</span>
                <span class="detail-value">{{ $invoice->customer_invoice_number ?? 'N/A' }}</span>

                <span class="detail-label">Reference Number:</span>
                <span class="detail-value">{{ $invoice->reference_number ?? 'N/A' }}</span>

                <span class="detail-label">Billing Month:</span>
                <span class="detail-value">{{ date('M-Y', strtotime($invoice->billing_month)) }}</span>
            </div>
        </div>
    </div>

    <!-- Description Section (unified with supplier design) -->
    @if(!empty($invoice->descriptions))
    <div class="description-block">
        <strong>📝 Description</strong><br>
        <span style="color: #334155;">{{ $invoice->descriptions }}</span>
    </div>
    @endif

    <!-- Items Table (with all leasing columns: bike, days, rate, VAT etc) -->
    @if($invoice->items && $invoice->items->count() > 0)
    <div style="overflow-x: auto;">
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 28%;">Product / Service Description</th>
                    <th style="width: 6%;">Qty</th>
                    <th style="width: 8%;">Days</th>
                    <th style="width: 12%;">Rate (Monthly)</th>
                    <th style="width: 12%;">Amount (excl. VAT)</th>
                    <th style="width: 8%;">VAT Rate</th>
                    <th style="width: 10%;">VAT Amount</th>
                    <th style="width: 12%;">Total ({{ \App\Helpers\Currency::code() ?? 'AED' }})</th>
                </tr>
            </thead>
            <tbody>
                @php $runningTotalDisplay = 0; @endphp
                @foreach($invoice->items as $key => $item)
                    @php
                        $vatRate = $item->tax_rate ?? 0;
                        $vatAmtRow = $item->tax_amount ?? 0;
                        $rowTotal = $item->total_amount ?? ($item->rental_amount + $vatAmtRow);
                        $proratedAmount = $rowTotal - $vatAmtRow;
                        $runningTotalDisplay += $rowTotal;
                        // Bike plate and emirates extraction
                        $bikePlate = $item->bike->plate ?? 'N/A';
                        $bikeEmirates = '';
                        if(isset($item->bike_id) && $item->bike_id) {
                            try {
                                $bikeData = company_table('bikes')->where('id', $item->bike_id)->first();
                                $bikeEmirates = $bikeData->emirates ?? 'N/A';
                            } catch(\Exception $e) { $bikeEmirates = 'N/A'; }
                        }
                    @endphp
                    <tr>
                        <td class="num">{{ $loop->iteration }}</td>
                        <td>Bike # {{ $bikePlate }} ({{ $bikeEmirates }})</td>
                        <td class="num">{{ number_format($item->qty ?? 1, 2) }}</td>
                        <td class="num">{{ number_format($item->days ?? 1, 2) }}</td>
                        <td class="num">{{ number_format($item->rental_amount ?? 0, 2) }}</td>
                        <td class="num">{{ number_format($proratedAmount, 2) }}</td>
                        <td class="num">{{ number_format($vatRate, 0) }}%</td>
                        <td class="num">{{ number_format($vatAmtRow, 2) }}</td>
                        <td class="num">{{ number_format($rowTotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Financial summary card (styled like supplier invoice) -->
    <div style="display: flex;
            justify-content: flex-end;
            margin-top: 15px;">
        <table style="width: 45%;
            min-width: 280px;
            border: 1px solid #e2e8f0;
            background: white;">
            <thead>
                <tr><th colspan="2" class="secondary-header" style="background:#004aad;">Financial Summary</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 600;">Total Bikes:</td>
                    <td class="num">{{ $invoice->items->count() ?? 0 }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Total Amount (before VAT):</td>
                    <td class="num">{{ \App\Helpers\Currency::format($subtotal, 2) ?? number_format($subtotal, 2) }}</td>
                </tr>
                @if(($totalVat) > 0)
                <tr>
                    <td style="font-weight: 600;">VAT</td>
                    <td class="num">{{ \App\Helpers\Currency::format($totalVat, 2) ?? number_format($totalVat, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    @else
    <div style="text-align: center; padding: 30px; background: #f9f9fc; border: 1px solid #e9ecef; border-radius: 12px; margin: 20px 0;">
        <p style="margin: 0; color: #5b6e8c;">📭 No items recorded for this leasing invoice</p>
    </div>
    @endif

    <!-- Notes Section (modern consistent notes) -->
    @if($invoice->notes)
    <div class="notes-section">
        <strong>📌 Notes:</strong><br>
        {{ $invoice->notes }}
    </div>
    @endif

    <!-- Grand Total Card (consistent with supplier design) -->
    <div class="grand-total-wrapper">
        <div class="grand-total-card">
            <div>GRAND TOTAL</div>
            <div>{{ \App\Helpers\Currency::format($grandTotal, 2) ?? number_format($grandTotal, 2) }}</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer-note">
        <p>Thank you for your business!</p>
        <p>For any queries, please contact: {{ $companyPhone }} | {{ $companyEmail }}</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format all numeric cells with commas & decimals (consistent modern design)
        document.querySelectorAll('.num').forEach(function(element) {
            let rawText = element.innerText.trim();
            if(rawText.includes(',') && !rawText.includes('NaN')) return;
            let num = parseFloat(rawText.replace(/,/g, ''));
            if (!isNaN(num) && rawText !== '') {
                let formatted = num.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                if(element.innerText !== formatted) {
                    element.innerText = formatted;
                }
            }
        });
    });
</script>
</body>
</html>