<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bike Maintenance Invoice #{{ $maintenance->id }}</title>
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
                border: none;
                width: 100%;
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
        
        .maintenance-details {
            background: #f5f5f5;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 10px 0;
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
    </style>
</head>
<body>
    <div class="controls no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
        <a href="{{ route('bikeMaintenance.edit', $maintenance->id) }}" class="print-btn">Edit</a>
        <a href="{{ route('bikeMaintenance.index') }}" class="print-btn">Back to List</a>
    </div>

    <div class="invoice-box">
        <!-- Header Table -->
        @php
        $settings = DB::table('settings')->pluck('value', 'name')->toArray();
        @endphp
        <table width="100%" style="font-family: sans-serif;">
            <tr>
                <td width="33.33%">
                    @if(file_exists(public_path('assets/img/logo-full.png')))
                    <img src="{{ URL::asset('assets/img/logo-full.png') }}" width="150" />
                    @else
                    <h3>{{ $settings['company_name'] ?? 'Company Name' }}</h3>
                    @endif
                </td>
                <td width="33.33%" style="text-align: center;">
                    <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px;">{{ $settings['company_name'] ?? 'Company Name' }}</h4>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">{{ $settings['company_address'] ?? 'Company Address' }}</p>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">TRN {{ $settings['vat_number'] ?? 'TRN Number' }}</p>
                </td>
                <td width="33.33%" style="text-align: right;">
                    <h2 style="margin: 0; font-size: 24px; font-weight: bold;">Maintenance Invoice</h2>
                </td>
            </tr>
        </table>

        <!-- Maintenance Details -->
        <div class="maintenance-details">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
                <tr>
                    <td width="50%" style="border: 1px solid #000; padding: 8px; vertical-align: top;">
                        <strong>Bike Information:</strong><br>
                        Bike: {{ $maintenance->bike->emirates }}-{{ $maintenance->bike->plate }}<br>
                        Rider: {{ $maintenance->bike->rider->name ?? 'No Rider Assigned' }}<br>
                        @if($maintenance->bike->rider)
                        Rider ID: {{ $maintenance->bike->rider->rider_id }}<br>
                        @endif
                    </td>
                    <td width="50%" style="border: 1px solid #000; padding: 8px; vertical-align: top;">
                        <strong>Maintenance Details:</strong><br>
                        Invoice #: MAINT-{{ str_pad($maintenance->id, 6, '0', STR_PAD_LEFT) }}<br>
                        Date: {{ $maintenance->maintenance_date->format('d/m/Y') }}<br>
                        Created By: {{ $maintenance->createdBy->name ?? 'System' }}<br>
                        Created At: {{ $maintenance->created_at->format('d/m/Y H:i') }}<br>
                        @if($maintenance->description)
                        Notes: {{ $maintenance->description }}<br>
                        @endif
                    </td>
                </tr>
            </table>
            
            <!-- Kilometer Details -->
            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-label">Previous KM Reading:</span>
                    <span class="detail-value">{{ number_format($maintenance->previous_km, 0) }} KM</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Current KM Reading:</span>
                    <span class="detail-value">{{ number_format($maintenance->current_km, 0) }} KM</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Maintenance Interval:</span>
                    <span class="detail-value">{{ number_format($maintenance->maintenance_km, 0) }} KM</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Overdue KM:</span>
                    <span class="detail-value {{ $maintenance->overdue_km > 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($maintenance->overdue_km, 1) }} KM
                    </span>
                </div>
                @if($maintenance->overdue_km > 0)
                <div class="detail-item">
                    <span class="detail-label">Overdue Cost Per KM:</span>
                    <span class="detail-value">AED {{ number_format($maintenance->overdue_cost_per_km, 2) }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Overdue Cost Paid By:</span>
                    <span class="detail-value">{{ $maintenance->overdue_paidby ?? 'Not Specified' }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Cost Summary -->
        <div class="total-section">
            <div class="total-row">
                <span>Maintenance Items Total:</span>
                <span>AED {{ number_format($maintenance->maintenanceItems->sum('total_amount'), 2) }}</span>
            </div>
            @if($maintenance->overdue_cost > 0)
            <div class="total-row">
                <span>Overdue Cost:</span>
                <span>AED {{ number_format($maintenance->overdue_cost, 2) }}</span>
            </div>
            @endif
            <div class="total-row grand-total">
                <span>Total Invoice Amount:</span>
                <span>AED {{ number_format($maintenance->total_cost, 2) }}</span>
            </div>
            <div class="total-row">
                <span>Currency:</span>
                <span>AED</span>
            </div>
        </div>

        <!-- Maintenance Items Table -->
        @if($maintenance->maintenanceItems->count() > 0)
        <table>
            <tr>
                <th class="secondary-header">Item</th>
                <th class="secondary-header">Description</th>
                <th class="secondary-header">Quantity</th>
                <th class="secondary-header">Rate (AED)</th>
                <th class="secondary-header">Discount</th>
                <th class="secondary-header">VAT</th>
                <th class="accent-total">Total (AED)</th>
            </tr>
            @foreach($maintenance->maintenanceItems as $item)
            <tr>
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->item->description ?? 'Maintenance Item' }}</td>
                <td class="num">{{ number_format($item->quantity, 2) }}</td>
                <td class="num">{{ number_format($item->rate, 2) }}</td>
                <td class="num">{{ number_format($item->discount, 2) }}</td>
                <td class="num">{{ number_format($item->vat, 2) }}</td>
                <td class="num">{{ number_format($item->total_amount, 2) }}</td>
            </tr>
            @endforeach
            <tr class="accent-total">
                <td colspan="6" style="text-align: right; padding: 8px;"><strong>SUBTOTAL</strong></td>
                <td class="num" style="padding: 8px; font-size: 14px;">
                    <strong>{{ number_format($maintenance->maintenanceItems->sum('total_amount'), 2) }}</strong>
                </td>
            </tr>
        </table>
        @else
        <div style="text-align: center; padding: 20px; background: #f9f9f9; border: 1px solid #ddd;">
            <p style="margin: 0;">No maintenance items recorded</p>
        </div>
        @endif

        <!-- Overdue Charges (if applicable) -->
        @if($maintenance->overdue_cost > 0)
        <table style="margin-top: 15px;">
            <tr>
                <th class="light-header" colspan="3">Overdue Charges Details</th>
            </tr>
            <tr>
                <td><strong>Overdue KM:</strong></td>
                <td class="num">{{ number_format($maintenance->overdue_km, 1) }} KM</td>
                <td rowspan="2" style="text-align: center; vertical-align: middle; background: #fff3cd;">
                    <strong>Overdue Charge</strong><br>
                    <span style="font-size: 16px;">AED {{ number_format($maintenance->overdue_cost, 2) }}</span>
                </td>
            </tr>
            <tr>
                <td><strong>Cost Per KM:</strong></td>
                <td class="num">AED {{ number_format($maintenance->overdue_cost_per_km, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Paid By:</strong></td>
                <td colspan="2">{{ $maintenance->overdue_paidby ?? 'Not Specified' }}</td>
            </tr>
        </table>
        @endif

        <!-- Grand Total -->
        <div style="margin-top: 20px; text-align: right;">
            <div style="display: inline-block; padding: 15px; background: #004aad; color: white; border-radius: 5px;">
                <div style="font-size: 16px; margin-bottom: 5px;">Grand Total</div>
                <div style="font-size: 24px; font-weight: bold;">AED {{ number_format($maintenance->total_cost, 2) }}</div>
                <div style="font-size: 12px; margin-top: 5px;">Currency: AED</div>
            </div>
        </div>

        @if($maintenance->attachment)
        <div style="margin-top: 15px; padding: 10px; background: #e9ecef; border: 1px solid #000;">
            <strong>Attachment:</strong>
            <a href="{{ Storage::url($maintenance->attachment) }}" target="_blank" style="margin-left: 10px;">
                View Attachment
            </a>
        </div>
        @endif

        @if($maintenance->description)
        <div style="margin-top: 15px; padding: 10px; background: #f0f0f0; border: 1px solid #000;">
            <strong>Additional Notes:</strong><br>
            {{ $maintenance->description }}
        </div>
        @endif

        <!-- Signature Section -->
        <div style="margin-top: 30px; border-top: 2px solid #000; padding-top: 15px;">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="width: 50%; text-align: center; border: none;">
                        <div style="border-bottom: 1px solid #000; padding-bottom: 30px; width: 80%; margin: 0 auto;">
                            <strong>Authorized Signature</strong>
                        </div>
                        <p style="margin-top: 5px;">For {{ $settings['company_name'] ?? 'Company Name' }}</p>
                    </td>
                    <td style="width: 50%; text-align: center; border: none;">
                        <div style="border-bottom: 1px solid #000; padding-bottom: 30px; width: 80%; margin: 0 auto;">
                            <strong>Customer Signature</strong>
                        </div>
                        <p style="margin-top: 5px;">Received By</p>
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 20px; text-align: center; font-size: 11px; color: #666;">
            <p>Thank you for your business!</p>
            <p>For any queries, please contact: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}</p>
        </div>
    </div>

    <script>
        // Auto-print option (optional)
        // window.onload = function() {
        //     window.print();
        // };
        
        // Calculate totals for display
        document.addEventListener('DOMContentLoaded', function() {
            // Format numbers with commas
            document.querySelectorAll('.num').forEach(function(element) {
                let text = element.textContent;
                if (text.match(/^\d+/)) {
                    element.textContent = parseFloat(text).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            });
        });
    </script>
</body>
</html>