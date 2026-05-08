<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leasing Company Invoice #{{ $invoice->invoice_number ?? $invoice->id }} Month: {{ date('M-Y', strtotime($invoice->billing_month)) }}</title>
    <style>
        /* ----- RESET & GLOBAL (following supplier invoice modern structure) ----- */
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

        /* ----- TABLES (clean border style from supplier invoice) ----- */
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

        /* ----- HEADER STYLES (aligned with supplier invoice) ----- */
        .primary-header { background: #211c1d; color: white; font-weight: bold; }
        .secondary-header { background: #004aad; color: white; font-weight: bold; }
        .accent-total { background: #5271ff; color: white; font-weight: bold; }
        .light-header { background: #e6f1ff; color: #004aad; font-weight: bold; }
        .amount-highlight { background: #2A62FF; color: white; font-weight: bold; }
        .success-highlight { background: #004aad; color: white; font-weight: bold; }
        .yellow-highlight { background: #ffff00; font-weight: bold; padding: 8px; }

        /* ----- PRINT BUTTONS & CONTROLS (same as supplier invoice) ----- */
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
        }

        /* ----- CARD LAYOUT (matching supplier invoice card style) ----- */
        .leasing-card, .details-card {
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
        .detail-item {
            display: contents;
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
            min-width: 240px;
        }

        /* description section (supplier style) */
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

        /* Grand Total (supplier style badge) */
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

        /* items table improvement */
        .items-table th, .items-table td {
            border: 1px solid #ccc;
        }
        .items-table th {
            background: #004aad;
            color: white;
            font-weight: 600;
            text-align: center;
        }
        .items-table td {
            padding: 8px 10px;
        }

        /* financial summary table */
        .financial-summary {
            display: flex;
            justify-content: flex-end;
            margin-top: 5px;
        }
        .financial-summary table {
            width: 45%;
            min-width: 260px;
            border: 1px solid #e2e8f0;
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
                padding: 10px;
                max-width: 100%;
                border-radius: 0;
            }
            .controls {
                display: none !important;
            }
            .leasing-card, .details-card {
                box-shadow: none;
                border: 1px solid #ccc;
                break-inside: avoid;
            }
            .grand-total-card {
                background: #004aad !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            th, .secondary-header, .card-header strong {
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
    <a href="javascript:void(0);" data-size="xl" data-title="Edit Invoice" data-action="{{ route('leasingCompanyInvoices.edit', $invoice->id) }}" class="print-btn show-modal" style="text-decoration: none;">Edit</a>
</div>

<div class="invoice-box">
    @php
        $settings = DB::table('settings')->pluck('value', 'name')->toArray();
        $running_total = 0;
        $items_total = 0;
        $subtotal = $invoice->subtotal ?? 0;
        $vat_total = $invoice->vat ?? 0;
        $final_total = $invoice->total_amount ?? 0;
    @endphp

    <!-- HEADER SECTION: logo + company + title (supplier invoice style) -->
    <table style="margin-bottom: 20px; border: none; background: transparent;">
        <tr style="border: none;">
            <td style="width: 33%; border: none !important; vertical-align: middle;">
                <img src="{{ $companyLogoUrl ?? URL::asset('assets/img/logo-full.png') }}" width="150" alt="logo" />
            </td>
            <td style="width: 34%; text-align: center; border: none !important;">
                <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight:700;">{{ $settings['company_name'] ?? '' }}</h4>
                <p style="margin: 3px 0; font-size: 12px;">{{ $settings['company_address'] ?? '' }}</p>
                <p style="margin: 3px 0; font-size: 12px;">TRN {{ $settings['vat_number'] ?? '' }}</p>
            </td>
            <td style="width: 33%; text-align: center; border: none !important;">
                <h2 style="margin: 0; font-weight: 800; color: #004aad; font-size: 22px;">LEASING COMPANY INVOICE</h2>
            </td>
        </tr>
    </table>

    <!-- CARD LAYOUT: Leasing Company Info + Invoice Details (exactly like supplier invoice card structure) -->
    <div class="flex-row-cards">
        <!-- Leasing Company Card (similar to Supplier Details card) -->
        <div class="leasing-card">
            <div class="card-header">
                <strong>🏢 Leasing Company Details</strong>
            </div>
            <div class="details-grid">
                <span class="detail-label">Company Name:</span>
                <span class="detail-value">{{ $invoice->leasingCompany->name ?? 'N/A' }}</span>

                <span class="detail-label">TRN Number:</span>
                <span class="detail-value">{{ $invoice->leasingCompany->trn_number ?? 'N/A' }}</span>

                <span class="detail-label">Contact Person:</span>
                <span class="detail-value">{{ $invoice->leasingCompany->contact_person ?? 'N/A' }}</span>

                <span class="detail-label">Contact Number:</span>
                <span class="detail-value">{{ $invoice->leasingCompany->contact_number ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Invoice Details Card (matching supplier invoice details style) -->
        <div class="details-card">
            <div class="card-header">
                <strong>📄 Invoice Details</strong>
            </div>
            <div class="details-grid">
                <span class="detail-label">Invoice No:</span>
                <span class="detail-value">{{ $invoice->invoice_number ?? 'LI-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}</span>

                <span class="detail-label">Leasing Company Invoice No:</span>
                <span class="detail-value">{{ $invoice->leasing_company_invoice_number ?? 'N/A' }}</span>

                <span class="detail-label">Reference Number:</span>
                <span class="detail-value">{{ $invoice->reference_number ?? 'N/A' }}</span>

                <span class="detail-label">Billing Month:</span>
                <span class="detail-value">{{ date('M-Y', strtotime($invoice->billing_month)) }}</span>

                <span class="detail-label">Service Period:</span>
                <span class="detail-value">{{ date('01/m/Y', strtotime($invoice->billing_month)) }} - {{ date('t/m/Y', strtotime($invoice->billing_month)) }}</span>

                <span class="detail-label">Billed To:</span>
                <span class="detail-value">{{ $settings['company_name'] ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <!-- Description Section (styled like supplier) -->
    @if($invoice->descriptions)
    <div class="description-block">
        <strong>📝 Description</strong><br>
        <span style="color: #334155;">{{ $invoice->descriptions }}</span>
    </div>
    @endif

    <!-- Additional Notes Section -->
    @if($invoice->notes)
    <div class="notes-section">
        <strong>📌 Additional Notes:</strong><br>
        {{ $invoice->notes }}
    </div>
    @endif

    <!-- Main Items Table (styled like supplier invoice's clean table) -->
    @if($invoice->items && $invoice->items->count() > 0)
    <div style="overflow-x: auto;">
        <table class="items-table">
            <thead>
                <tr>
                    <th>Sr.</th>
                    <th>Product / Service Description</th>
                    <th>Qty</th>
                    <th>Days</th>
                    <th>Rate (Monthly)</th>
                    <th>Amount</th>
                    <th>VAT Rate</th>
                    <th>VAT Amount</th>
                    <th>Total ({{ \App\Helpers\Currency::code() }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $key => $item)
                @php
                    $vatRate = $item->tax_rate ?? 0;
                    $vatAmtRow = $item->tax_amount ?? 0;
                    $rowTotal = $item->total_amount ?? ($item->rental_amount + $vatAmtRow);
                    $proratedAmount = $rowTotal - $vatAmtRow;
                    $running_total += $rowTotal;
                @endphp
                <tr>
                    <td class="num">{{ $key + 1 }}</td>
                    <td>Bike # {{ $item->bike->plate ?? 'N/A' }} ({{ DB::table('bikes')->where('id', $item->bike_id)->first()->emirates ?? 'N/A' }})</td>
                    <td class="num">1</td>
                    <td class="num">{{ $item->days ?? 1 }}</td>
                    <td class="num">{{ number_format($item->rental_amount, 2) }}</td>
                    <td class="num">{{ number_format($proratedAmount, 2) }}</td>
                    <td class="num">{{ number_format($vatRate, 0) }}%</td>
                    <td class="num">{{ number_format($item->tax_amount ?? 0, 2) }}</td>
                    <td class="num">{{ number_format($running_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Financial Summary (exactly like supplier invoice's mini summary table) -->
    <div class="financial-summary">
        <table style="border: 1px solid #e2e8f0;">
            <thead>
                <tr><th colspan="2" class="secondary-header" style="background:#004aad;">Financial Summary</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 600;">Total Bikes:</td>
                    <td class="num">{{ $invoice->items->count() ?? 0 }} Bikes</td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Subtotal (excl. VAT):</td>
                    <td class="num">{{ number_format($subtotal, 2) }}</td>
                </tr>
                @if(($vat_total) > 0)
                <tr>
                    <td style="font-weight: 600;">VAT Amount:</td>
                    <td class="num">{{ number_format($vat_total, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @else
    <div style="text-align: center; padding: 30px; background: #f9f9fc; border: 1px solid #e9ecef; border-radius: 12px; margin: 20px 0;">
        <p style="margin: 0; color: #5b6e8c;">📭 No items recorded for this invoice</p>
    </div>
    @endif

    <!-- Grand Total (exactly like supplier invoice card badge) -->
    <div class="grand-total-wrapper">
        <div class="grand-total-card">
            <div>GRAND TOTAL</div>
            <div>{{ \App\Helpers\Currency::format($final_total ?? 0, 2) }}</div>
        </div>
    </div>

    <!-- Footer (matching supplier invoice) -->
    <div class="footer-note">
        <p>Thank you for your business!</p>
        <p>For any queries, please contact: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format all .num cells with proper thousand separators
        document.querySelectorAll('.num').forEach(function(element) {
            let rawText = element.innerText.trim();
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