<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supplier Invoice #{{ $supplierInvoice->inv_id }}</title>
    <style>
        /* ----- RESET & GLOBAL (inherited from fuel invoice styles) ----- */
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
        }

        /* ----- TABLES (clean border style from version 2) ----- */
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

        /* ----- HEADER STYLES (kept from v1 but enhanced) ----- */
        .primary-header { background: #211c1d; color: white; }
        .secondary-header { background: #004aad; color: white; font-weight: bold; }
        .accent-total { background: #5271ff; color: white; }
        .light-header { background: #e6f1ff; color: #004aad; }
        .amount-highlight { background: #2A62FF; color: white; }
        .yellow-highlight { background: #ffff00; font-weight: bold; padding: 8px; }

        /* ----- PRINT BUTTONS & CONTROLS (consistent with fuel invoice) ----- */
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

        /* ----- CARD LAYOUT (unique from v2, matches fuel invoice card style) ----- */
        .supplier-card, .details-card {
            padding: 16px 18px;
            margin-bottom: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .invoice-box  .card-header {
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

        /* description section (clean as v2) */
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

        /* notes section (v2 style) */
        .notes-section {
            margin: 20px 0;
            padding: 12px 16px;
            background: #fef9e6;
            border-left: 4px solid #ffb347;
            border-radius: 8px;
        }

        /* Grand Total Area (v2 premium badge) */
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

        /* footer note (from v2) */
        .footer-note {
            margin-top: 28px;
            text-align: center;
            font-size: 11px;
            color: #5b6e8c;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
        }

        /* items table enhancements: aligns with fuel invoice table */
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
        .invoice-box  tfoot tr td {
            background: #f1f5f9;
            font-weight: 600;
        }

        /* financial summary table (inline, clean) */
        .summary-mini-table {
            width: 45%;
            min-width: 260px;
            border: 1px solid #e2e8f0;
        }
        .summary-mini-table th {
            background: #004aad;
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
            .summary-mini-table {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="controls no-print">
    <button type="button" class="print-btn" onclick="printModalContent()">Print Order</button>
</div>

<div class="invoice-box">
    <!-- HEADER SECTION (fully aligned to fuel invoice: logo + company + title) -->
    @php
        $settings = DB::table('settings')->pluck('value', 'name')->toArray();
        $total = $supplierInvoice->items->sum('total_amount');
        $total_vat = $supplierInvoice->items->sum('tax_amount');
        $subtotal_excl_vat = ($total ?? 0) - ($total_vat ?? 0);
    @endphp
    <table style="margin-bottom: 20px; border: none; background: transparent;">
        <tr style="border: none;">
            <td style="width: 33%; border: none !important; vertical-align: middle;">
                @if(file_exists(public_path('assets/img/logo-full.png')))
                    <img src="{{ URL::asset('assets/img/logo-full.png') }}" width="150" alt="logo" />
                @else
                    <h3 style="color:#004aad;">{{ $settings['company_name'] ?? 'Company Name' }}</h3>
                @endif
            </td>
            <td style="width: 34%; text-align: center; border: none !important;">
                <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight:700;">{{ $settings['company_name'] ?? 'Company Name' }}</h4>
                <p style="margin: 3px 0; font-size: 12px;">{{ $settings['company_address'] ?? 'Company Address' }}</p>
                <p style="margin: 3px 0; font-size: 12px;">TRN {{ $settings['vat_number'] ?? 'TRN Number' }}</p>
            </td>
            <td style="width: 33%; text-align: center; border: none !important;">
                <h2 style="margin: 0; font-weight: 800; color: #004aad; font-size: 22px;">PURCHASE ORDER</h2>
            </td>
        </tr>
    </table>

    <!-- CARD LAYOUT: Supplier card + Invoice details (exactly matching fuel invoice's structure) -->
    <div class="flex-row-cards">
        <!-- Supplier Information Card (modern card version) -->
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

        <!-- Invoice Details Card (showing order/invoice fields from original, enhanced with billing month + garage) -->
        <div class="details-card">
            <div class="card-header">
                <strong>📄 Invoice Details</strong>
            </div>
            <div class="details-grid">
                <span class="detail-label">Order #:</span>
                <span class="detail-value">{{ $supplierInvoice->inv_id }}</span>

                <span class="detail-label">Order Date:</span>
                <span class="detail-value">{{ $supplierInvoice->order_date?->format('d M Y') ?? '' }}</span>

                <span class="detail-label">Garage:</span>
                <span class="detail-value">{{ $supplierInvoice->garage?->name ?? '—' }}</span>

                <span class="detail-label">Created By:</span>
                <span class="detail-value">{{ $supplierInvoice->creatorBy?->name ?? ($supplierInvoice->updatedBy?->name ?? '—') }}</span>
            </div>
        </div>
    </div>

    <!-- Description Section (if exists) exactly like fuel invoice description block -->
    @if($supplierInvoice->descriptions)
    <div class="description-block">
        <strong>📝 Description</strong><br>
        <span style="color: #334155;">{{ $supplierInvoice->descriptions }}</span>
    </div>
    @endif

    <!-- Additional Notes Section (kept from original but elevated with v2 style) -->
    @if($supplierInvoice->notes)
    <div class="notes-section">
        <strong>📌 Additional Notes:</strong><br>
        {{ $supplierInvoice->notes }}
    </div>
    @endif

    <!-- Items Table (styled as fuel invoice robust table) -->
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
    
    <!-- Financial summary (integrated from fuel invoice's approach: subtotal excl vat + vat amount) -->
    <div style="display: flex; justify-content: flex-end; margin-top: 5px;">
        <table style="width: 45%; min-width: 260px; border: 1px solid #e2e8f0;">
            <thead>
                <tr><th colspan="2" class="secondary-header" style="background:#004aad;">Financial Summary</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: 600;">Subtotal (excl. VAT):</td>
                    <td class="num">{{ \App\Helpers\Currency::format($subtotal_excl_vat, 2) }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">VAT Amount:</td>
                    <td class="num">{{ \App\Helpers\Currency::format($total_vat ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @else
    <div style="text-align: center; padding: 30px; background: #f9f9fc; border: 1px solid #e9ecef; border-radius: 12px; margin: 20px 0;">
        <p style="margin: 0; color: #5b6e8c;">📭 No items recorded for this invoice</p>
    </div>
    @endif

    <!-- Grand Total badge (matching fuel invoice grand total card) -->
    <div class="grand-total-wrapper">
        <div class="grand-total-card">
            <div>GRAND TOTAL</div>
            <div>{{ \App\Helpers\Currency::format($total ?? 0, 2) }}</div>
        </div>
    </div>

    <!-- Footer (exactly like fuel invoice footer) -->
    <div class="footer-note">
        <p>Thank you for your business!</p>
        <p>For any queries, please contact: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format all .num cells with comma separators for numeric values, avoid double formatting.
        document.querySelectorAll('.num').forEach(function(element) {
            let rawText = element.innerText.trim();
            // Remove existing commas to parse cleanly
            let numericString = rawText.replace(/,/g, '');
            let num = parseFloat(numericString);
            if (!isNaN(num) && numericString !== '') {
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