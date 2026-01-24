<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leasing Company Invoice #{{ $invoice->id }}</title>
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

        th,
        td {
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
            position: fixed;
            top: 10px;
            right: 10px;
            background: #004aad;
            color: #fff;
            border: none;
            padding: 8px 12px;
            font-size: 12px;
            cursor: pointer;
            border-radius: 3px;
            z-index: 9999;
        }

        .print-btn:hover {
            background: #2A62FF;
        }

        @media print {
            body,
            *,
            .primary-header,
            .secondary-header,
            .accent-total,
            .light-header,
            .amount-highlight,
            .yellow-highlight {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-btn {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <button type="button" class="print-btn" onclick="window.print()">Print</button>

    <div class="invoice-box">
        <!-- Header Table -->
        @php
        $settings = DB::table('settings')->pluck('value', 'name')->toArray();
        @endphp
        <table width="100%" style="font-family: sans-serif;">
            <tr>
                <td width="33.33%"><img src="{{ URL::asset('assets/img/logo-full.png') }}" width="150" /></td>
                <td width="33.33%" style="text-align: center;">
                    <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px;">{{$settings['company_name'] ?? ''}}</h4>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">{{$settings['company_address'] ?? ''}}</p>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;"> TRN {{$settings['vat_number'] ?? ''}}</p>
                </td>
                <td width="33.33%" style="text-align: right;">
                    <h2 style="margin: 0; font-size: 24px; font-weight: bold;">Tax Invoice</h2>
                </td>
            </tr>
        </table>

        <!-- Customer and Invoice Details -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
            <tr>
                <td width="50%" style="border: 1px solid #000; padding: 8px; vertical-align: top;">
                    <strong>Customer:</strong><br>
                    {{ $invoice->leasingCompany->name }}<br>
                    @if($invoice->leasingCompany->contact_person)
                    Contact: {{ $invoice->leasingCompany->contact_person }}<br>
                    @endif
                    @if($invoice->leasingCompany->contact_number)
                    Tel: {{ $invoice->leasingCompany->contact_number }}<br>
                    @endif
                    @if($invoice->leasingCompany->detail)
                    {{ $invoice->leasingCompany->detail }}
                    @endif
                </td>
                <td width="50%" style="border: 1px solid #000; padding: 8px; vertical-align: top;">
                    <strong>Invoice Details:</strong><br>
                    Invoice #: {{ $invoice->invoice_number ?? 'INV-' . str_pad($invoice->id, 8, '0', STR_PAD_LEFT) }}<br>
                    Date: {{ $invoice->inv_date->format('d/m/Y') }}<br>
                    Period: {{ date('d/m/Y', strtotime($invoice->billing_month)) }}<br>
                    Due Date: {{ $invoice->inv_date->format('d/m/Y') }}<br>
                    @if($invoice->descriptions)
                    RE: {{ $invoice->descriptions }}<br>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Summary Box (Yellow Highlighted) -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
            <tr>
                <td class="yellow-highlight" style="border: 1px solid #000; padding: 10px;">
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td style="border: none; padding: 0;"><strong>Invoice Amount:</strong></td>
                            <td style="border: none; padding: 0; text-align: right;"><strong>{{ number_format($invoice->subtotal, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 0;"><strong>Tax:</strong></td>
                            <td style="border: none; padding: 0; text-align: right;"><strong>{{ number_format($invoice->vat, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 0; font-size: 16px;"><strong>Total Invoice Amount:</strong></td>
                            <td style="border: none; padding: 0; text-align: right; font-size: 18px;"><strong>{{ number_format($invoice->total_amount, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 0;"><strong>Currency:</strong></td>
                            <td style="border: none; padding: 0; text-align: right;"><strong>AED</strong></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Charges Table -->
        <table>
            <tr>
                <th class="secondary-header">Charge</th>
                <th class="secondary-header">AGR #</th>
                <th class="secondary-header">Fleet #</th>
                <th class="secondary-header">From</th>
                <th class="secondary-header">To</th>
                <th class="secondary-header">Units</th>
                <th class="secondary-header">Rate</th>
                <th class="secondary-header">Price</th>
                <th class="secondary-header">Tax Rate</th>
                <th class="secondary-header">Tax</th>
                <th class="accent-total">Amt(AED)</th>
            </tr>
            @php
            $periodStart = date('01/m/Y', strtotime($invoice->billing_month));
            $periodEnd = date('t/m/Y', strtotime($invoice->billing_month));
            $vatRate = $invoice->items->first()->tax_rate ?? 5;
            @endphp
            @foreach($invoice->items as $index => $item)
            <tr>
                <td>Rental</td>
                <td>{{ 'LA' . str_pad($item->bike_id, 8, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $item->bike->plate ?? 'N/A' }}</td>
                <td>{{ $periodStart }}</td>
                <td>{{ $periodEnd }}</td>
                <td class="num">1.00 M</td>
                <td class="num">{{ number_format($item->rental_amount, 2) }}</td>
                <td class="num">{{ number_format($item->rental_amount, 2) }}</td>
                <td class="num">{{ number_format($vatRate, 0) }}%</td>
                <td class="num">{{ number_format($item->tax_amount, 2) }}</td>
                <td class="num">{{ number_format($item->total_amount, 2) }}</td>
            </tr>
            @endforeach
            <tr class="accent-total">
                <td colspan="10" style="text-align: right; padding: 8px;"><strong>TOTAL</strong></td>
                <td class="num" style="padding: 8px; font-size: 14px;"><strong>{{ number_format($invoice->total_amount, 2) }}</strong></td>
            </tr>
        </table>

        @if($invoice->notes)
        <div style="margin-top: 15px; padding: 10px; background: #f0f0f0; border: 1px solid #000;">
            <strong>Notes:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif

        <div style="margin-top: 20px; text-align: center; font-size: 11px;">
            <p>Thank you for your business!</p>
        </div>
    </div>
</body>

</html>

