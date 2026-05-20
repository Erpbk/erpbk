<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fine Invoice #{{ $rtaFine->ticket_no ?? $rtaFine->id }}</title>
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
            border-bottom: 1px solid #000;
            padding: 4px 6px;
            font-size: 12px;
            text-align: center;
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
        
        .fine-detail-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin: 15px 0;
        }
        
        .text-danger {
            color: #dc3545;
        }
        
        .text-success {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <button type="button" class="print-btn" onclick="printModalContent()">Print</button>
    </div>

    <div class="invoice-box">
        @php
            $settings = company_table('settings')->pluck('value', 'name')->toArray();
            $fineAmount = $rtaFine->amount ;
            $serviceCharges = $rtaFine->service_charges ?? 0;
            $adminFee = $rtaFine->admin_fee ?? 0;
            $vat = $rtaFine->vat ?? 0;
            $serviceVat = ($rtaFine->service_vat / 100) * $serviceCharges;
            $adminVat = ($rtaFine->admin_vat / 100) * $adminFee;
            $totalAmount = $rtaFine->total_amount ?? ($fineAmount + $serviceCharges + $adminFee + $vat);
            $companyLogoUrl = asset('assets/img/logo-full.png');
        @endphp

        <!-- Header Table -->
        <table width="100%" style="font-family: sans-serif;">
            <tr>
                <td width="33%" style="border: none !important;">
                    @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                        <img src="{{ Storage::url($settings['company_logo']) }}" width="150" alt="logo" />
                    @endif
                </td>
                <td width="37%" style="text-align: center; align-content: center; border: none !important;">
                    <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px;">{{ $settings['company_name'] }}</h4>
                    <p style="margin-bottom: 5px;font-size: 12px;margin-top: 5px;">{{ $settings['company_address'] }}</p>
                    <p style="margin-bottom: 5px;font-size: 12px;margin-top: 5px;">TRN {{ $settings['vat_number']}}</p>
                    <p style="margin-bottom: 5px;font-size: 11px;margin-top: 5px;">Tel: {{ $settings['company_phone'] }} | Email: {{ $settings['company_email'] }}</p>
                </td>
                <td width="30%" style="text-align: center; align-content: center; border: none !important;">
                    <h3 style="margin: 0; font-weight: 600; color: #004aad; font-size: 20px;">VEHICLE FINE</h3>
                </td>
            </tr>
        </table>

        <!-- Customer and Invoice Details Section -->
        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
            <!-- Customer Information Card -->
            <div style="flex: 1; padding: 15px;">
                <div style="margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #004aad;">
                    <strong style="color: #004aad; font-size: 14px;">Customer Details</strong>
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; align-items: center;">
                    <div style="font-weight: 600; color: #555;">Rider:</div>
                    <div>{{ $rtaFine->rider? ($rtaFine->rider->rider_id .' | '. $rtaFine->rider->name) : ($rtaFine->rentalCompany?->name ?? 'N/A')  }}</div>

                    @if($rtaFine->bike->rentalCompany)
                    <div style="font-weight: 600; color: #555;">Address:</div>
                    <div>{{ $rtaFine->bike->rentalCompany->address ?? '' }}</div>
                    
                    <div style="font-weight: 600; color: #555;">TRN#:</div>
                    <div>{{ $rtaFine->bike->rentalCompany->trn ?? '' }}</div>
                    @endif
                    <div style="font-weight: 600; color: #555;">Contact:</div>
                    <div>{{ $rtaFine->rider->company_contact ??  $rtaFine->rider->personal_contact ?? $rtaFine->rentalCompany->company_conatct ?? '' }}</div>
                </div>
            </div>
            
            <!-- Invoice Details Card -->
            <div style="flex: 1; padding: 15px;">
                <div style="margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #004aad;">
                    <strong style="color: #004aad; font-size: 14px;">Invoice Details</strong>
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; align-items: center;">
                    <div style="font-weight: 600; color: #555;">Ticket #:</div>
                    <div>{{ $rtaFine->ticket_no }}</div>
                    
                    <div style="font-weight: 600; color: #555;">Date:</div>
                    <div>{{ $rtaFine->trip_date->format('d M Y') }}</div>
                    
                    <div style="font-weight: 600; color: #555;">VEH #:</div>
                    <div>{{ $rtaFine->bike->emirates .'-'.$rtaFine->plate_no }}</div>
                </div>
            </div>
        </div>

        <!-- Charges Table -->
        <table>
            <thead>
                <tr>
                    <th class="secondary-header">Description</th>
                    <th class="secondary-header">Bike #</th>
                    <th class="secondary-header">Trip Date</th>
                    <th class="secondary-header">Amount ({{ \App\Helpers\Currency::code() ?? 'AED' }})</th>
                    <th class="secondary-header">Tax Rate</th>
                    <th class="secondary-header">Tax Amt ({{ \App\Helpers\Currency::code() ?? 'AED' }})</th>
                    <th class="secondary-header">Total ({{ \App\Helpers\Currency::code() ?? 'AED' }})</th>
                </tr>
            </thead>
            <tbody>
                <!-- Fine Amount Row -->
                <tr>
                    <td>FINE</td>
                    <td>{{ ($rtaFine->bike->emirates ?? '').$rtaFine->plate_no  }}</td>
                    <td>{{ $rtaFine->trip_date->format('d-M-y')}}</td>
                    <td>{{ number_format($fineAmount, 2) }}</td>
                    <td>{{ '0%' }}</td>
                    <td>{{ number_format(0, 2) }}</td>
                    <td>{{ number_format($fineAmount , 2) }}</td>
                </tr>
                
                <!-- Service Charges Row (if exists) -->
                @if($serviceCharges > 0)
                <tr>
                    <td>Service Charges</td>
                    <td>{{ ($rtaFine->bike->emirates ?? '').$rtaFine->plate_no  }}</td>
                    <td>{{ $rtaFine->trip_date->format('d-M-y')}}</td>
                    <td>{{ number_format($serviceCharges, 2) }}</td>
                    <td>{{ $rtaFine->service_vat.'%' }}</td>
                    <td>{{ number_format($serviceVat, 2) }}</td>
                    <td>{{ number_format($serviceCharges + $serviceVat, 2) }}</td>
                </tr>
                @endif
                
                <!-- Admin Fee / Fine SER Row -->
                @if($rtaFine->admin_fee)
                <tr>
                    <td>Admin Charges</td>
                    <td>{{ ($rtaFine->bike->emirates ?? '').$rtaFine->plate_no }}</td>
                    <td>{{ $rtaFine->trip_date->format('d-M-y')}}</td>
                    <td>{{ number_format($adminFee, 2) }}</td>
                    <td>{{ $rtaFine->admin_vat.'%' }}</td>
                    <td>{{ number_format($adminVat, 2) }}</td>
                    <td>{{ number_format($adminFee + $adminVat, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <!-- Total Section -->
        <div style="margin-top: 20px; text-align: right;">
            <div style="display: inline-block; width: 350px;">
                <div class="total-row" style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <span><strong>Subtotal:</strong></span>
                    <span>{{ number_format($fineAmount + $serviceCharges + $adminFee, 2) }} AED</span>
                </div>
                <div class="total-row" style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <span><strong>VAT:</strong></span>
                    <span>{{ number_format($vat, 2) }} AED</span>
                </div>
                <div style="padding: 12px; background: #004aad; color: white; border-radius: 5px; margin-top: 10px;">
                    <div style="font-size: 22px; font-weight: bold; text-align: center;">{{ number_format($totalAmount, 2) }} AED</div>
                    <div style="font-size: 11px; text-align: center; margin-top: 5px;">
                        AED {{ ucfirst(strtolower(\App\Helpers\General::numToWordsRec($totalAmount))) }}
                    </div>
                </div>
            </div>
        </div>

        <div style="margin: 20px 30px 20px 30px; border: 2px dashed #333; border-radius: 8px; padding: 15px;">
            <div style="display: flex; gap: 20px;">
                <!-- Left Column - Fine Details -->
                <div style="flex: 1;">
                    <table style="width: 100%;">
                        <!-- Row 1: Fine # -->
                        <tr>
                            <td style="width: 10%; padding: 8px;  border: none !important;">
                                <strong style="font-size: 18px;">🎟️</strong>
                            </td>
                            <td style="width: 90%; padding: 8px; border: none !important; text-align: left; font-size: 16px;">
                                {{ $rtaFine->ticket_no }}
                            </td>
                        </tr>
                        
                        <!-- Row 2: Date & Time -->
                        <tr>
                            <td style="width: 10%; padding: 8px; border: none !important;">
                                <strong style="font-size: 18px; border: none !important;">📅</strong>
                            </td>
                            <td style="width: 90%; padding: 8px; border: none !important; text-align: left; font-size: 16px;">
                                {{ $rtaFine->trip_date->format('d-M-y') }} 
                                {{ $rtaFine->trip_time->format('h:i:s a')  }}
                            </td>
                        </tr>
                        
                        <!-- Row 3: Description -->
                        <tr>
                            <td style="width: 10%; padding: 8px; border: none !important;">
                                <strong style="font-size: 18px; border: none !important;">🧾</strong>
                            </td>
                            <td style="width: 90%; padding: 8px; border: none !important; text-align: left; font-size: 16px;">
                                {{ $rtaFine->detail}}
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Right Column - Number Plate -->
                <div style="flex: 1;">
                    <div style="background: #fff; border-radius: 8px; padding: 12px; border: 1px solid black;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="font-size: 18px; font-weight: bold;">
                                {{ $rtaFine->bike->bike_code ?? '?' }}
                            </div>
                            <div style="font-size: 18px; font-weight: bold; text-align: center;">
                                {{ $rtaFine->bike->emirates ?? 'DXB' }}
                            </div>
                            <div style="font-size: 18px; font-weight: bold; text-align: right;">
                                {{ $rtaFine->plate_no }}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fine Amount - Simple -->
                    <div style="margin-top: 50px; text-align: center; font-size: 18px;">
                         AED {{ number_format($rtaFine->amount ?? 500, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        {{-- <div style="margin-top: 25px; padding: 12px; background: #f8f9fa; border: 1px solid #ddd;">
            <div style="font-weight: bold; margin-bottom: 8px;">Payment Information:</div>
            <div style="font-size: 11px;">
                <strong>Credit Notes Total:</strong> 0.00 &nbsp;&nbsp;|&nbsp;&nbsp;
                <strong>Payments Total:</strong> 0.00 &nbsp;&nbsp;|&nbsp;&nbsp;
                <strong>Invoice Balance:</strong> {{ number_format($totalAmount, 2) }} AED
            </div>
            <div style="font-size: 11px; margin-top: 8px;">
                &gt; All Cheques must be issued in the name of {{ $settings['company_name'] }} &amp; in AED<br>
                &gt; Bank Transfer Details: Bank Name: EMIRATES ISLAMIC BANK 00001 | Company Name: {{ $settings['company_name'] ?? 'EASY LEASE MOTOR CYCLE RENTAL P.S.C' }} | Account No.: 3707413595301 | IBAN: AE890340003707413595301 | Swift Code: MEBLAEAD | Currency: AED
            </div>
        </div> --}}

        {{-- <div class="" style="height: 20px;"></div>
        
        <div class="footer" style="position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 11px; color: #5b6e8c; border-top: 1px solid #e2e8f0; padding-top: 16px; padding-bottom: 0px; background: white; width: 100%; z-index: 1000;">
            <p>For any queries, please contact: {{ $settings['company_phone'] ?? '00971 4 2999669' }} | {{ $settings['company_email'] ?? 'info@easylease.ae' }}</p>
        </div> --}}
    </div>

    <script>
        
        function printInvoice() {
            window.print();
        }
    </script>
</body>
</html>