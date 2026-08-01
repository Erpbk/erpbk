<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supplier Invoice #{{ $supplierInvoice->inv_id }}</title>
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
        .invoice-box .no-border td {
            border: none;
            padding: 4px 6px;
        }

        /* ----- HEADER STYLES (alignment with fuel design) ----- */
        .invoice-box .primary-header { background: #211c1d; color: white; }
        .invoice-box .secondary-header { background: #004aad; color: white; font-weight: bold; }
        .invoice-box .accent-total { background: #5271ff; color: white; }
        .invoice-box .light-header { background: #e6f1ff; color: #004aad; }
        .invoice-box .amount-highlight { background: #2A62FF; color: white; }
        .invoice-box .yellow-highlight { background: #ffff00; font-weight: bold; padding: 8px; }

        /* ----- PRINT BUTTONS & CONTROLS (same as fuel invoice) ----- */
        #rightSideModalBody .print-btn,        body > .controls .print-btn {
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            margin-bottom: 20px;
            width: 95%;
            justify-self: center;
            margin-left: auto;
            margin-right: auto;
            justify-content: flex-end;
        }

        /* ----- CARD LAYOUT (matching fuel invoice) ----- */
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

        /* description section */
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

        /* table improvements: consistent like fuel invoice */
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
        .invoice-box tfoot tr td {
            background: #f1f5f9;
            font-weight: 600;
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
            #rightSideModalBody > .controls,            body > .controls {
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
            th, .secondary-header, .card-header strong {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* responsive */
        @media (max-width: 700px) {
            .flex-row-cards {
                flex-direction: column;
            }
            .invoice-box {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<div class="controls no-print">
    <button type="button" class="print-btn" onclick="printModalContent()">Print Invoice</button>
</div>

<div class="invoice-box">
    <!-- HEADER SECTION (consistent with fuel invoice: logo + company + title) -->
    @php
        $settings = company_table('settings')->pluck('value', 'name')->toArray();
        $total = $supplierInvoice->items->sum('total_amount');
        $total_vat = $supplierInvoice->items->sum('tax_amount');
    @endphp
    <table style="margin-bottom: 20px; border: none; background: transparent;">
        <tr style="border: none;">
            <td style="width: 33%; border: none !important; vertical-align: middle;">
                @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                    <img src="{{ storage_url($settings['company_logo']) }}" width="150" alt="logo" />
                @endif
            </td>
            <td style="width: 34%; text-align: center; align-content: center; border: none !important;">
                <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight:700;">{{ ucwords($settings['company_name']) ?? 'Company Name' }}</h4>
                <p style="margin: 3px 0; font-size: 12px;">{{ ucwords($settings['company_address']) ?? 'Company Address' }}</p>
                <p style="margin: 3px 0; font-size: 12px;">TEL: {{ $settings['company_phone'] ?? 'TRN Number' }}</p>
                <p style="margin: 3px 0; font-size: 12px;">TRN: {{ $settings['vat_number'] ?? 'TRN Number' }}</p>
            </td>
            <td style="width: 33%; text-align: center; align-content: center; border: none !important;">
                <h2 style="margin: 0; font-weight: 800; color: #004aad; font-size: 22px;">SUPPLIER INVOICE</h2>
            </td>
        </tr>
    </table>

    <!-- CARD LAYOUT: supplier details + invoice details (exactly like fuel invoice's rider+summary structure) -->
    <div class="flex-row-cards">
        <!-- Supplier Information Card (similar to Rider Card) -->
        <div class="supplier-card">
            <div class="card-header">
                <strong>📇 Supplier Details</strong>
            </div>
            <div class="details-grid">
                <span class="detail-label">Supplier Name:</span>
                <span class="detail-value">{{ $supplierInvoice->supplier->name ?? '—' }}</span>

                <span class="detail-label">Company Name:</span>
                <span class="detail-value">{{ $supplierInvoice->supplier->company_name ?? '—' }}</span>

                <span class="detail-label">Contact:</span>
                <span class="detail-value">{{ $supplierInvoice->supplier->phone ?? '—' }}</span>
                
                @if(!empty($supplierInvoice->supplier->email))
                <span class="detail-label">Email:</span>
                <span class="detail-value">{{ $supplierInvoice->supplier->email }}</span>
                @endif
            </div>
        </div>

        <!-- Invoice Details Card (similar to Invoice Details in fuel invoice) -->
        <div class="details-card">
            <div class="card-header">
                <strong>📄 Invoice Details</strong>
            </div>
            <div class="details-grid">
                <span class="detail-label">Invoice #:</span>
                <span class="detail-value">{{ $supplierInvoice->inv_id }}</span>

                <span class="detail-label">Invoice Date:</span>
                <span class="detail-value">{{ $supplierInvoice->inv_date?->format('d M Y') ?? '—' }}</span>

                <span class="detail-label">Billing Month:</span>
                <span class="detail-value">{{ $supplierInvoice->billing_month ? date('M Y', strtotime($supplierInvoice->billing_month)) : '—' }}</span>

                <span class="detail-label">Garage:</span>
                <span class="detail-value">{{ $supplierInvoice->garage?->name ?? '—' }}</span>

                <span class="detail-label">Created By:</span>
                <span class="detail-value">{{ $supplierInvoice->updatedBy?->name ?? $supplierInvoice->createdBy?->name ?? '—' }}</span>
            </div>
        </div>
    </div>

    <!-- Description Section (if exists) styled like fuel note but elegant -->
    @if($supplierInvoice->descriptions)
    <div class="description-block">
        <strong>📝 Description</strong><br>
        <span style="color: #334155;">{{ $supplierInvoice->descriptions }}</span>
    </div>
    @endif

    <!-- Additional Notes Section (same as supplier original but visually aligned) -->
    @if($supplierInvoice->notes)
    <div class="notes-section">
        <strong>📌 Additional Notes:</strong><br>
        {{ $supplierInvoice->notes }}
    </div>
    @endif

    <!-- Items Table (styled like fuel transaction table, but with supplier items) -->
    @if($supplierInvoice->items && $supplierInvoice->items->count() > 0)
    <div style="overflow-x: auto;">
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 38%;">Item Description</th>
                    <th style="width: 12%;">Quantity</th>
                    <th style="width: 15%;">Rate ({{ \App\Helpers\Currency::code() }})</th>
                    <th style="width: 15%;">VAT ({{ \App\Helpers\Currency::code() }})</th>
                    <th style="width: 20%;">Amount ({{ \App\Helpers\Currency::code() }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($supplierInvoice->items as $item)
                <tr>
                    <td>{{ $item->item_des ?? '—' }}</td>
                    <td class="num">{{ number_format($item->qty ?? 0, 2) }}</td>
                    <td class="num">{{ number_format($item->rate ?? 0, 2) }}</td>
                    <td class="num">{{ number_format($item->tax_amount ?? 0, 2) }}</td>
                    <td class="num">{{ number_format($item->total_amount ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Financial mini summary (like fuel invoice's financial summary but integrated in clean way) -->
    <div style="display: flex; justify-content: flex-end; margin-top: 5px;">
        <table style="width: 45%; min-width: 260px; border: 1px solid #e2e8f0;">
            <thead>
                <tr><th colspan="2" class="secondary-header" style="background:#004aad;">Financial Summary</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 600;">Subtotal (excl. VAT):</td>
                    <td class="num">{{ \App\Helpers\Currency::format(($total??0) - ($total_vat??0), 2) }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">VAT Amount:</td>
                    <td class="num">{{ \App\Helpers\Currency::format($total_vat??0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @else
    <div style="text-align: center; padding: 30px; background: #f9f9fc; border: 1px solid #e9ecef; border-radius: 12px; margin: 20px 0;">
        <p style="margin: 0; color: #5b6e8c;">📭 No items recorded for this invoice</p>
    </div>
    @endif

    <!-- Grand Total (exactly like fuel invoice: background badge style) -->
    <div class="grand-total-wrapper">
        <div class="grand-total-card">
            <div>GRAND TOTAL</div>
            <div>{{ \App\Helpers\Currency::format($total??0, 2) }}</div>
        </div>
    </div>

    <!-- Footer (same as fuel invoice) -->
    <div class="footer-note">
        <p>Thank you for your business!</p>
        <p>For any queries, please contact: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format all .num cells with comma separators (consistent with numeric values)
        document.querySelectorAll('.num').forEach(function(element) {
            let rawText = element.innerText.trim();
            let num = parseFloat(rawText.replace(/,/g, ''));
            if (!isNaN(num) && rawText !== '') {
                let formatted = num.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                // avoid double formatting if already formatted
                if(element.innerText !== formatted) {
                    element.innerText = formatted;
                }
            }
        });
    });
</script>
</body>
</html>