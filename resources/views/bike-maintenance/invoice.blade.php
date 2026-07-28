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
        }

        .invoice-box table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .invoice-box th, .invoice-box td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 12px;
        }

        .invoice-box th {
            background: #d9e1f2;
            font-weight: bold;
        }

        .invoice-box td.num {
            text-align: right;
        }

        .no-border td {
            border: none;
            padding: 3px 6px;
        }

        .invoice-box .primary-header {
            background: #211c1d;
            color: white;
            font-weight: bold;
        }

        .invoice-box .secondary-header {
            background: #004aad;
            color: white;
            font-weight: bold;
        }

        .invoice-box .accent-total {
            background: #5271ff;
            color: white;
            font-weight: bold;
        }

        .invoice-box .light-header {
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

        .invoice-box .footer {
            display: none;
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

            .invoice-box .footer {
                display: block;
            }
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
        @endphp
        <table width="100%" style="font-family: sans-serif;">
            <tr>
                <td width="33.33%" style=" border: none !important;">
                    @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                        <img src="{{ storage_url($settings['company_logo']) }}" width="150" alt="logo" />
                    @endif
                </td>
                <td width="33.33%" style="text-align: center; align-content: center; border: none !important;">
                    <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px; font-weight: bold;">{{ ucwords($settings['company_name']) ?? 'Company Name' }}</h4>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">{{ ucwords($settings['company_address']) ?? 'Company Address' }}</p>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">TEL: {{ $settings['company_phone'] ?? 'Company Contact' }}</p>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">TRN: {{ $settings['vat_number'] ?? 'TRN Number' }}</p>
                </td>
                <td width="33.33%" style="text-align: center; align-content: center; border: none !important;">
                    <h3 style="margin: 0; font-weight: 600; color: #004aad; font-size: 20px;">Maintenance Bill</h3>
                </td>
            </tr>
        </table>

        <!-- Maintenance Details Section -->
        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
            <!-- Bike Information Card -->
            <div style="flex: 1; padding: 15px;">
                <div style="margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #004aad;">
                    <strong style="color: #004aad; font-size: 14px;">Bike Details</strong>
                </div>
                <div style="display: grid; grid-template-columns: 100px 1fr; gap: 8px; align-items: center;">
                    <div style="font-weight: 600; color: #555;">Bike:</div>
                    <div>{{ $maintenance->bike->emirates ?? '' }}-{{ $maintenance->bike->plate ?? '' }}</div>

                    @if($maintenance->rider)
                    <div style="font-weight: 600; color: #555;">Leasing Company:</div>
                    <div>{{ $maintenance->bike?->LeasingCompany?->name ?? '' }}</div>
                    <div style="font-weight: 600; color: #555;">Rider:</div>
                    <div>{{ $maintenance->rider->rider_id }}-{{ $maintenance->rider->name }}</div>
                    <div style="font-weight: 600; color: #555;">Rider Contact:</div>
                    <div>{{ $maintenance->rider->company_contact ?? $maintenance->bike?->rider?->company_contact ?? '' }}</div>
                    @elseif($maintenance->rentalCompany)
                    <div style="font-weight: 600; color: #555;">User:</div>
                    <div>{{ $maintenance->rentalCompany->name ?? '' }}</div>
                    <div style="font-weight: 600; color: #555;">User Contact:</div>
                    <div>{{ $maintenance->rentalCompany->company_contact ?? '' }}</div>
                    <div style="font-weight: 600; color: #555;">Address:</div>
                    <div>{{ $maintenance->rentalCompany->address ?? '' }}</div>
                    @else
                    <div style="font-weight: 600; color: #555;">Assigned To:</div>
                    <div>No User Assigned</div>
                    @endif

                    <div style="font-weight: 600; color: #555;">Garage:</div>
                    <div>{{ $maintenance->garage?->name ?? '' }}</div>
                </div>
            </div>
            
            <!-- Invoice Details Card -->
            <div style="flex: 1; padding: 15px;">
                <div style="margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #004aad;">
                    <strong style="color: #004aad; font-size: 14px;">Bill Details</strong>
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; align-items: center;">
                    <div style="font-weight: 600; color: #555;">Bill No:</div>
                    <div>MA-{{ $maintenance->id }}</div>
                    
                    <div style="font-weight: 600; color: #555;">Maintenance Date:</div>
                    <div>{{ $maintenance->maintenance_date->format('d M Y') }}</div>

                    <div style="font-weight: 600; color: #555;">Billing Month:</div>
                    <div>{{ $maintenance->billing_month->format('M Y') }}</div>
                    
                    <div style="font-weight: 600; color: #555;">Created By:</div>
                    <div>{{ $maintenance->createdBy->name ?? 'System' }}</div>
                    
                    <div class="no-print" style="font-weight: 600; color: #555;">Created At:</div>
                    <div class="no-print">{{ $maintenance->created_at->format('d M Y H:i') }}</div>

                    @if($maintenance->updated_by)
                        <div class="no-print" style="font-weight: 600; color: #555;">Updated By:</div>
                        <div class="no-print">{{ $maintenance->UpdatedBy->name }}</div>

                        <div class="no-print" style="font-weight: 600; color: #555;">Updated At:</div>
                        <div class="no-print">{{ $maintenance->updated_at->format('d M Y H:i') }}</div>

                    @endif
                </div>
            </div>
        </div>

        <!-- Kilometer Details -->
        <div style="margin: 10px; padding-bottom: 8px; border-bottom: 2px solid #004aad;">
            <strong style="color: #004aad; font-size: 14px;">Maintenance Details</strong>
        </div>
        <div class="details-grid">
            <div class="detail-item">
                <span class="detail-label">Previous KM Reading:</span>
                <span class="detail-value">{{ number_format($maintenance->previous_km ?? 0, 0) }} KM</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Overdue KM:</span>
                <span class="detail-value {{ ($maintenance->overdue_km ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                    {{ number_format($maintenance->overdue_km ?? 0, 1) }} KM
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Current KM Reading:</span>
                <span class="detail-value">{{ number_format($maintenance->current_km ?? 0, 0) }} KM</span>
            </div>
            @if(($maintenance->overdue_km ?? 0) > 0)
                <div class="detail-item">
                    <span class="detail-label">Overdue Cost Per KM:</span>
                    <span class="detail-value">{{ \App\Helpers\Currency::format($maintenance->overdue_cost_per_km ?? 0, 2) }}</span>
                </div>
            @else
                <div class="detail-item">
                </div>
            @endif
            <div class="detail-item">
                <span class="detail-label">Maintenance Interval:</span>
                @php
                    $maintenance_km = max(0, (float) ($maintenance->bike?->maintenance_km ?? 0));
                @endphp
                <span class="detail-value">{{ number_format($maintenance_km, 2) }} KM</span>
            </div>
            @if(($maintenance->overdue_km ?? 0) > 0)
                <div class="detail-item">
                    <span class="detail-label">Overdue KM:</span>
                    <span class="detail-value">{{ $maintenance->overdue_km  }}</span>
                </div>
            @else
                <div class="detail-item">
                </div>
            @endif
            <div class="detail-item">
                <span class="detail-label">Next Service:</span>
                <span class="detail-value">{{ number_format($maintenance_km + (float) ($maintenance->current_km ?? 0), 2) }} KM</span>
            </div>
        </div>

        <!-- Maintenance Items Table -->
        @if($maintenance->maintenanceItems->count() > 0)
            <table>
                <tr>
                    <th class="secondary-header">Item</th>
                    <th class="secondary-header">Description</th>
                    <th class="secondary-header">Quantity</th>
                    <th class="secondary-header">Rate ({{ \App\Helpers\Currency::code() }})</th>
                    <th class="secondary-header">Discount</th>
                    <th class="secondary-header">VAT(%)</th>
                    <th class="secondary-header">Total ({{ \App\Helpers\Currency::code() }})</th>
                </tr>
                @php
                    $riderItems = $maintenance->maintenanceItems->where('charge_to','User');
                    $companyItems = $maintenance->maintenanceItems->where('charge_to','Company');
                @endphp
                @if($riderItems->count() > 0)
                    <tr>
                        <td colspan="7" style="text-align: center; font-weight: bold;">User Items</td>
                    </tr>
                    @foreach($riderItems as $item)
                    <tr>
                        <td>{{ $item->item_name }}</td>
                        <td>{{ $item->item->description ?? 'Maintenance Item' }}</td>
                        <td class="num">{{ number_format($item->quantity, 2) }}</td>
                        <td class="num">{{ number_format($item->rate, 2) }}</td>
                        <td class="num">{{ number_format($item->discount, 2) }}</td>
                        <td class="num">{{ number_format($item->vat, 2) }}</td>
                        <td class="num">{{ $item->total_amount }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="6" style="text-align: right; padding: 8px;"><strong>SUBTOTAL</strong></td>
                        <td class="num" style="padding: 8px; font-size: 14px;">
                            <strong>{{ number_format($riderItems->sum('total_amount'), 2) }}</strong>
                        </td>
                    </tr>
                @endif
                @if($companyItems->count() > 0)
                    <tr>
                        <td colspan="7" style="text-align: center; font-weight: bold;">Company Items</td>
                    </tr>
                    @foreach($companyItems as $item)
                    <tr>
                        <td>{{ $item->item_name }}</td>
                        <td>{{ $item->item->description ?? 'Maintenance Item' }}</td>
                        <td class="num">{{ number_format($item->quantity, 2) }}</td>
                        <td class="num">{{ number_format($item->rate, 2) }}</td>
                        <td class="num">{{ number_format($item->discount, 2) }}</td>
                        <td class="num">{{ number_format($item->vat, 2) }}</td>
                        <td class="num">{{ $item->total_amount }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="6" style="text-align: right; padding: 8px;"><strong>SUBTOTAL</strong></td>
                        <td class="num" style="padding: 8px; font-size: 14px;">
                            <strong>{{ number_format($companyItems->sum('total_amount'), 2) }}</strong>
                        </td>
                    </tr>
                @endif
            </table>
        @else
            <div style="text-align: center; padding: 20px; background: #f9f9f9; border: 1px solid #ddd;">
                <p style="margin: 0;">No maintenance items recorded</p>
            </div>
        @endif

        <!-- Grand Total -->
        <div style="margin-top: 20px; text-align: right;">
            <div style="display: inline-block; padding: 15px; background: #004aad; color: white; border-radius: 5px;">
                <div style="font-size: 16px; margin-bottom: 5px; text-align: center;">Grand Total</div>
                <div style="font-size: 24px; font-weight: bold;">{{ \App\Helpers\Currency::format($maintenance->total_cost, 2) }}</div>
            </div>
        </div>

        @if($maintenance->attachment)
        <div class="no-print" style="margin-top: 15px; padding: 10px; background: #e9ecef; border: 1px solid #000;">
            <strong>Attachment:</strong>
            <a href="{{ storage_url($maintenance->attachment) }}" target="_blank" style="margin-left: 10px;">
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

        {{-- <!-- Signature Section -->
        <div style="margin-top: 30px; padding-top: 15px;">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="width: 50%; text-align: center; border: none;">
                    </td>
                    <td style="width: 50%; text-align: center; border: none;">
                        <div style="border-bottom: 1px solid #000; padding-bottom: 30px; width: 80%; margin: 0 auto;">
                        </div>
                        <p style="margin-top: 5px;">{{ $maintenance->rider? $maintenance->rider->rider_id.'-'.$maintenance->rider->name :' Received By' }}</p>
                    </td>
                </tr>
            </table>
        </div> --}}
        <div class="" style="height: 20px;"></div>
        <div class="footer" style="position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 11px; color: #5b6e8c; border-top: 1px solid #e2e8f0; padding-top: 16px; padding-bottom: 0px; background: white; width: 100%; z-index: 1000;">
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