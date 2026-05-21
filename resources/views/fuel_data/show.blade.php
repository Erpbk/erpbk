<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fuel Invoice #{{ $summary->inv_id }}</title>
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
        }

        .invoice-box table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .invoice-box th,
        .invoice-box td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 12px;
        }

        .invoice-box th {
            background: #004aad;
            font-weight: bold;
            text-align: center;
        }

        .invoice-box td {
            text-align: center;
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
            justify-content: right;
            margin-left: auto;
            margin-right: auto;
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

        .rider-card, .summary-card {
            padding: 16px 18px;
            margin-bottom: 0;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }

        .card-header {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #004aad;
            background-color: white !important;
        }

        .card-header strong {
            color: #004aad;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <button type="button" class="print-btn" onclick="printModalContent()">Print</button>
    </div>

    <div class="invoice-box">
        <!-- Header Table -->
        @php
        $settings = company_table('settings')->pluck('value', 'name')->toArray();
        $serviceCharges = 0;
        @endphp
        <table width="100%" style="font-family: sans-serif;">
            <tr>
                <td width="33.33%" style="border: none !important;">
                    @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                        <img src="{{ Storage::url($settings['company_logo']) }}" width="150" alt="logo" />
                    @endif
                </td>
                <td width="33.33%" style="text-align: center; align-content: center; border: none !important;">
                    <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px;">{{ ucwords($settings['company_name']) ?? '' }}</h4>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">{{ ucwords($settings['company_address']) ?? '' }}</p>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">TEL: {{ $settings['company_phone'] ?? '' }}</p>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">TRN: {{ $settings['vat_number'] ?? '' }}</p>
                </td>
                <td width="33.33%" style="text-align: center; align-content: center; border: none !important;">
                    <h2 style="margin: 0; font-weight: 600; color: #004aad; font-size: 24px;">FUEL INVOICE</h2>
                </td>
            </tr>
        </table>

        <!-- Rider and Invoice Details Section -->
        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
            <!-- Rider Information Card -->
            <div class="rider-card" style="flex: 1;">
                <div class="card-header">
                    <strong>Rider Details</strong>
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; align-items: center;">
                    <div style="font-weight: 600; color: #555;">Rider Name:</div>
                    <div>{{ $summary->rider_name ?? 'N/A' }}</div>
                    
                    <div style="font-weight: 600; color: #555;">Card Number:</div>
                    <div>{{ $summary->transactions->first()->card_no ?? 'N/A' }}</div>
                    
                    <div style="font-weight: 600; color: #555;">Total Transactions:</div>
                    <div>{{ $summary->transaction_count }}</div>
                </div>
            </div>
            
            <!-- Invoice Details Card -->
            <div class="summary-card" style="flex: 1;">
                <div class="card-header">
                    <strong>Invoice Details</strong>
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; align-items: center;">
                    <div style="font-weight: 600; color: #555;">Invoice #:</div>
                    <div>{{ $summary->inv_id }}</div>
                    
                    <div style="font-weight: 600; color: #555;">Billing Month:</div>
                    <div>{{ \Carbon\Carbon::parse($summary->billing_month)->format('F Y') }}</div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        @if($summary->transactions && $summary->transactions->count() > 0)
        <table>
            <thead>
                <tr>
                    <th class="secondary-header" style="width: 15%;">Trans Date</th>
                    <th class="secondary-header" style="width: 15%;">Trans No</th>
                    <th class="secondary-header" style="width: 15%;">Bike</th>
                    <th class="secondary-header" style="width: 10%;">Product</th>
                    <th class="secondary-header" style="width: 10%;">Qty (L)</th>
                    <th class="secondary-header" style="width: 10%;">Price/L ({{ \App\Helpers\Currency::code() }})</th>
                    <th class="secondary-header" style="width: 10%;">Vat ({{ \App\Helpers\Currency::code() }})</th>
                    <th class="secondary-header" style="width: 15%;">Total ({{ \App\Helpers\Currency::code() }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary->transactions as $index => $transaction)
                <tr>
                    <td>{{ $transaction->trans_date->format('d M Y h:i A') }}</td>
                    <td>{{ $transaction->trans_no }}</td>
                    <td>{{ $transaction->bike_no }}</td>
                    <td>{{ $transaction->product }}</td>
                    <td class="num">{{ number_format($transaction->qty, 2) }}</td>
                    <td class="num">{{ number_format($transaction->price, 2) }}</td>
                    <td class="num">{{ number_format($transaction->vat_amount, 2) }}</td>
                    <td class="num">{{ number_format($transaction->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Financial Summary Table -->
        <table style="margin-top: 20px; width: 50%; float: right;">
            <thead>
                <tr>
                    <th colspan="2" class="secondary-header">Financial Summary</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td class="num">{{ \App\Helpers\Currency::format($summary->total_subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>VAT Amount:</strong></td>
                    <td class="num">{{ \App\Helpers\Currency::format($summary->total_vat, 2) }}</td>
                </tr>
                @if($transaction->service_charges > 0)
                <tr>
                    <td><strong>Service Charges:</strong></td>
                    <td class="num">{{ \App\Helpers\Currency::format($transaction->service_charges, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
        @else
        <div style="text-align: center; padding: 20px; background: #f9f9f9; border: 1px solid #ddd;">
            <p style="margin: 0;">No transactions found for this invoice</p>
        </div>
        @endif

        <div style="clear: both;"></div>

        <!-- Grand Total -->
        <div style="margin-top: 30px; text-align: right;">
            <div style="display: inline-block; padding: 12px 28px; background: #004aad; color: white; border-radius: 20px; text-align: center;
            box-shadow: 0 4px 10px rgba(0,74,173,0.2);">
                <div style="font-size: 16px; margin-bottom: 5px; text-align: center;">Grand Total</div>
                <div style="font-size: 24px; font-weight: bold;">{{ \App\Helpers\Currency::format($summary->total_amount + $transaction->service_charges, 2) }}</div>
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