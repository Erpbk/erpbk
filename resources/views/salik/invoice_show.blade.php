<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salik Invoice #{{ $summary->inv_id }}</title>
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
            .light-header, .amount-highlight {
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
        @php
        $settings = company_table('settings')->pluck('value', 'name')->toArray();
        $firstTrip = $summary->transactions->first();
        @endphp
        <table width="100%" style="font-family: sans-serif;">
            <tr>
                <td width="33.33%" style="border: none !important;">
                    @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                        <img src="{{ storage_url($settings['company_logo']) }}" width="150" alt="logo" />
                    @endif
                </td>
                <td width="33.33%" style="text-align: center; align-content: center; border: none !important;">
                    <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px;">{{ ucwords($settings['company_name'] ?? '') }}</h4>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">{{ ucwords($settings['company_address'] ?? '') }}</p>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">TEL: {{ $settings['company_phone'] ?? '' }}</p>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">TRN: {{ $settings['vat_number'] ?? '' }}</p>
                </td>
                <td width="33.33%" style="text-align: center; align-content: center; border: none !important;">
                    <h2 style="margin: 0; font-weight: 600; color: #004aad; font-size: 24px;">SALIK INVOICE</h2>
                </td>
            </tr>
        </table>

        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
            <div class="rider-card" style="flex: 1;">
                <div class="card-header">
                    <strong>{{ $summary->rider_id ? 'Rider Details' : 'Company Details' }}</strong>
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; align-items: center;">
                    <div style="font-weight: 600; color: #555;">{{ $summary->rider_id ? 'Rider Name:' : 'Company Name:' }}</div>
                    <div>{{ $summary->chargee_name ?? 'N/A' }}</div>

                    <div style="font-weight: 600; color: #555;">Plate No:</div>
                    <div>{{ $firstTrip->plate ?? 'N/A' }}</div>

                    <div style="font-weight: 600; color: #555;">Total Trips:</div>
                    <div>{{ $summary->transaction_count }}</div>
                </div>
            </div>

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

        @if($summary->transactions && $summary->transactions->count() > 0)
        <table>
            <thead>
                <tr>
                    <th class="secondary-header" style="width: 12%;">Trip Date</th>
                    <th class="secondary-header" style="width: 14%;">Transaction ID</th>
                    <th class="secondary-header" style="width: 10%;">Plate</th>
                    <th class="secondary-header" style="width: 14%;">Toll Gate</th>
                    <th class="secondary-header" style="width: 10%;">Direction</th>
                    <th class="secondary-header" style="width: 10%;">Toll ({{ \App\Helpers\Currency::code() }})</th>
                    <th class="secondary-header" style="width: 10%;">Admin ({{ \App\Helpers\Currency::code() }})</th>
                    <th class="secondary-header" style="width: 10%;">VAT ({{ \App\Helpers\Currency::code() }})</th>
                    <th class="secondary-header" style="width: 10%;">Total ({{ \App\Helpers\Currency::code() }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary->transactions as $trip)
                <tr>
                    <td>{{ App\Helpers\General::DateFormat($trip->trip_date) }}</td>
                    <td>{{ $trip->transaction_id }}</td>
                    <td>{{ $trip->plate }}</td>
                    <td>{{ $trip->toll_gate }}</td>
                    <td>{{ $trip->direction }}</td>
                    <td class="num">{{ number_format($trip->amount, 2) }}</td>
                    <td class="num">{{ number_format($trip->admin_charges, 2) }}</td>
                    <td class="num">{{ number_format($trip->vat, 2) }}</td>
                    <td class="num">{{ number_format($trip->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table style="margin-top: 20px; width: 50%; float: right;">
            <thead>
                <tr>
                    <th colspan="2" class="secondary-header">Financial Summary</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Toll Amount:</strong></td>
                    <td class="num">{{ \App\Helpers\Currency::format($summary->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Admin Charges:</strong></td>
                    <td class="num">{{ \App\Helpers\Currency::format($summary->total_admin_charges, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>VAT Amount:</strong></td>
                    <td class="num">{{ \App\Helpers\Currency::format($summary->total_vat, 2) }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <div style="text-align: center; padding: 20px; background: #f9f9f9; border: 1px solid #ddd;">
            <p style="margin: 0;">No trips found for this invoice</p>
        </div>
        @endif

        <div style="clear: both;"></div>

        <div style="margin-top: 30px; text-align: right;">
            <div style="display: inline-block; padding: 12px 28px; background: #004aad; color: white; border-radius: 20px; text-align: center; box-shadow: 0 4px 10px rgba(0,74,173,0.2);">
                <div style="font-size: 16px; margin-bottom: 5px; text-align: center;">Grand Total</div>
                <div style="font-size: 24px; font-weight: bold;">{{ \App\Helpers\Currency::format($summary->total_grand, 2) }}</div>
            </div>
        </div>

        <div style="margin-top: 20px; text-align: center; font-size: 11px; color: #666;">
            <p>Thank you for your business!</p>
            <p>For any queries, please contact: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.num').forEach(function(element) {
                let text = element.textContent;
                let num = parseFloat(text.replace(/[^\d.-]/g, ''));
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
