<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supplier Invoice #{{ $supplierInvoice->inv_id }}</title>
    <style>
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .invoice-box {
            width: 850px;
            margin: auto;
            padding: 10px;
            border: 1px solid #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 12px;
        }

        th {
            background: #d9e1f2;
            font-weight: bold;
        }

        td.num {
            text-align: right;
        }

        .no-border td {
            border: none;
            padding: 3px 6px;
        }

        .primary-header {
            background: #211c1d;
            color: white;
            font-weight: bold;
        }

        .secondary-header {
            background: #004aad;
            color: white;
            font-weight: bold;
        }

        .accent-total {
            background: #5271ff;
            color: white;
            font-weight: bold;
        }

        .light-header {
            background: #e6f1ff;
            color: #004aad;
            font-weight: bold;
        }

        .amount-highlight {
            background: #2A62FF;
            font-weight: bold;
            color: #FFFFFF;
        }

        .yellow-highlight {
            background: #ffff00;
            font-weight: bold;
            padding: 8px;
        }

        .print-btn {
            background: #004aad;
            color: #fff;
            border: none;
            padding: 8px 12px;
            font-size: 12px;
            cursor: pointer;
            border-radius: 3px;
            text-decoration: none;
            display: inline-block;
        }

        .print-btn:hover {
            background: #2A62FF;
        }

        @media print {
            body, *, .primary-header, .secondary-header, .accent-total, 
            .light-header, .amount-highlight, .yellow-highlight {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none !important;
            }
            
            .invoice-box {
                max-width: 100% !important;
                width: 100% !important;
                margin: auto !important;
                padding: 10px !important;
                border: none !important;
                box-sizing: border-box !important;
            }
        }
        
        .controls {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 9999;
            display: flex;
            gap: 10px;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 10px 10px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        
        .detail-value {
            color: #333;
        }
        
        .total-section {
            background: #f0f8ff;
            padding: 15px;
            border: 2px solid #004aad;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            padding: 5px 0;
        }
        
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #004aad;
            border-top: 2px solid #004aad;
            padding-top: 10px;
            margin-top: 10px;
        }

        .supplier-card {
            padding: 15px;
            margin-bottom: 15px;
        }

        .card-header {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #004aad;
        }

        .card-header strong {
            color: #004aad;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
        <a href="{{ route('supplier_invoices.index') }}" class="print-btn">Back to List</a>
    </div>

    <div class="invoice-box">
        <!-- Header Table -->
        @php
        $settings = DB::table('settings')->pluck('value', 'name')->toArray();
        @endphp
        <table width="100%" style="font-family: sans-serif;">
            <tr>
                <td width="33.33%" style="border: none !important;">
                    @if(file_exists(public_path('assets/img/logo-full.png')))
                    <img src="{{ URL::asset('assets/img/logo-full.png') }}" width="150" />
                    @else
                    <h3>{{ $settings['company_name'] ?? 'Company Name' }}</h3>
                    @endif
                </td>
                <td width="33.33%" style="text-align: center; border: none !important;">
                    <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px;">{{ $settings['company_name'] ?? 'Company Name' }}</h4>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">{{ $settings['company_address'] ?? 'Company Address' }}</p>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">TRN {{ $settings['vat_number'] ?? 'TRN Number' }}</p>
                </td>
                <td width="33.33%" style="text-align: center; border: none !important;">
                    <h2 style="margin: 0; font-weight: bold;">
                            Supplier Invoice
                    </h2>
                </td>
            </tr>
        </table>

        <!-- Invoice Details Section -->
        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
            <!-- Supplier Information Card -->
            <div class="supplier-card" style="flex: 1;">
                <div class="card-header">
                    <strong>Supplier Details</strong>
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; align-items: center;">
                    
                    <div style="font-weight: 600; color: #555;">Supplier Name:</div>
                    <div>{{ $supplierInvoice->supplier->name }}</div>
                    
                    <div style="font-weight: 600; color: #555;">Company Name:</div>
                    <div>{{ $supplierInvoice->supplier->company_name }}</div>
                    
                    <div style="font-weight: 600; color: #555;">Contact:</div>
                    <div>{{ $supplierInvoice->supplier->phone }}</div>
                </div>
            </div>
            
            <!-- Invoice Details Card -->
            <div class="supplier-card" style="flex: 1;">
                <div class="card-header">
                    <strong>Invoice Details</strong>
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; align-items: center;">
                    
                    <div style="font-weight: 600; color: #555;">Invoice #:</div>
                    <div>{{ $supplierInvoice->inv_id }}</div>
                    
                    <div style="font-weight: 600; color: #555;">Invoice Date:</div>
                    <div>{{ $supplierInvoice->inv_date?->format('d M Y') ?? '' }}</div>
                    
                    <div style="font-weight: 600; color: #555;">Billing Month:</div>
                    <div>{{ date('M Y', strtotime($supplierInvoice->billing_month)) }}</div>

                    <div style="font-weight: 600; color: #555;">Garage:</div>
                    <div>{{ $supplierInvoice->garage?->name ?? '—' }}</div>

                    <div style="font-weight: 600; color: #555;">Created By:</div>
                    <div>{{ $supplierInvoice->updatedBy?->name ?? $supplierInvoice->createdBy?->name ?? ''}}</div>
                </div>
            </div>
        </div>

        <!-- Description Section -->
        @if($supplierInvoice->descriptions)
        <div style="margin: 10px; padding-bottom: 8px; border-bottom: 2px solid #004aad;">
            <strong style="color: #004aad; font-size: 14px;">Description</strong>
        </div>
        <div class="details-grid">
            <div class="detail-item">
                <span class="detail-label">Description:</span>
                <span class="detail-value">{{ $supplierInvoice->descriptions }}</span>
            </div>
        </div>
        @endif

        <!-- Items Table -->
        @if($supplierInvoice->items->count() > 0)
        <table>
            <thead>
                <tr>
                    <th class="secondary-header" style="width: 40%;">Item</th>
                    <th class="secondary-header" style="width: 15%;">Quantity</th>
                    <th class="secondary-header" style="width: 15%;">Rate ({{ \App\Helpers\Currency::code() }})</th>
                    <th class="secondary-header" style="width: 15%;">VAT ({{ \App\Helpers\Currency::code() }})</th>
                    <th class="secondary-header" style="width: 15%;">Amount ({{ \App\Helpers\Currency::code() }})</th>
                </tr>
            </thead>
            <tbody>
                @php $total = $supplierInvoice->items->sum('total_amount'); $total_vat = $supplierInvoice->items->sum('tax_amount'); @endphp
                @foreach($supplierInvoice->items as $key => $val)
                    <tr>
                        <td>{{ $val->item_des}}</td>
                        <td class="num">{{ number_format($val->qty, 2) }}</td>
                        <td class="num">{{ number_format($val->rate, 2) }}</td>
                        <td class="num">{{ number_format($val->tax_amount, 2) }}</td>
                        <td class="num">{{ number_format($val->total_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="border-top: 1px solid #000;">
                    <td colspan="3" style="text-align: right; padding: 8px;"><strong>Subtotal:</strong></td>
                    <td class="num" style="padding: 8px;"><strong>{{ number_format($total_vat ?? 0, 2) }}</strong></td>
                    <td class="num" style="padding: 8px;"><strong>{{ number_format($total??0 - $total_vat??0, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        @else
        <div style="text-align: center; padding: 20px; background: #f9f9f9; border: 1px solid #ddd;">
            <p style="margin: 0;">No items recorded for this invoice</p>
        </div>
        @endif

        <!-- Notes Section -->
        @if($supplierInvoice->notes)
        <div style="margin-top: 15px; padding: 10px; background: #f0f0f0; border: 1px solid #000;">
            <strong>Additional Notes:</strong><br>
            {{ $supplierInvoice->notes }}
        </div>
        @endif

        <!-- Grand Total -->
        <div style="margin-top: 20px; text-align: right;">
            <div style="display: inline-block; padding: 15px; background: #004aad; color: white; border-radius: 5px;">
                <div style="font-size: 16px; margin-bottom: 5px; text-align: center;">Grand Total</div>
                <div style="font-size: 24px; font-weight: bold;">{{ \App\Helpers\Currency::format($total??0, 2) }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div style="margin-top: 20px; text-align: center; font-size: 11px; color: #666;">
            <p>Thank you for your business!</p>
            <p>For any queries, please contact: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Format numbers with commas
            document.querySelectorAll('.num').forEach(function(element) {
                let text = element.textContent;
                let num = parseFloat(text);
                if (!isNaN(num)) {
                    element.textContent = num.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            });
        });
    </script>
</body>
</html>