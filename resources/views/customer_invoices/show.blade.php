<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Company Invoice #{{ $invoice->invoice_number ?? $invoice->id }} Month: {{ date('M-Y', strtotime($invoice->billing_month)) }}</title>
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

        .success-highlight {
            background: #004aad;
            color: white;
            font-weight: bold;
        }

        .yellow {
            background: #ffff00;
            font-weight: bold;
            padding: 3px 6px;
            display: inline-block;
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
            .success-highlight,
            .yellow {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-btn,
            .no-print {
                display: none !important;
            }
        }

        .action-buttons {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 9999;
            display: flex;
            gap: 10px;
        }
        
        .action-buttons .btn {
            text-decoration: none;
            display: inline-block;
            padding: 8px 12px;
            background: #004aad;
            color: #fff;
            border: none;
            border-radius: 3px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .action-buttons .btn:hover {
            background: #2A62FF;
        }
    </style>
</head>

<body>

    <div class="action-buttons no-print">
        <button type="button" class="btn" onclick="window.print()">Print</button>
        <a href="{{ url()->previous() }}" class="btn">Go Back</a>
    </div>

    <div class="invoice-box">
        @php
        $settings = DB::table('settings')->pluck('value', 'name')->toArray();
        @endphp
        <table width="100%" style="font-family: sans-serif;">
            <tr>
                <td width="33.33%" style="border: none !important;"><img src="{{ URL::asset('assets/img/logo-full.png') }}" width="150" /></td>
                <td width="33.33%" style="text-align: center; border: none !important;">
                    <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px;">{{ $settings['company_name'] ?? '' }}</h4>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">{{ $settings['company_address'] ?? '' }}</p>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;"> TRN {{ $settings['vat_number'] ?? '' }}</p>
                </td>
                <td width="33.33%" style="border: none !important;"></td>
            </tr>
        </table>

        <table style="width: 100%; margin-bottom: 10px;">
            <tr>
                <td colspan="4" class="primary-header" style="border: 1px solid #000; padding: 10px; text-align: center; font-size: 18px;">
                    CUSTOMER INVOICE
                </td>
            </tr>
        </table>

        <!-- Invoice and Company Info -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Invoice No:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 30%;">{{ $invoice->invoice_number ?? 'CI-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Customer:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ $invoice->customer->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Invoice Date:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ date('d M Y', strtotime($invoice->inv_date)) }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">TRN Number:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ $invoice->customer->tax_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Billing Month:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ date('F Y', strtotime($invoice->billing_month)) }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Service Period:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ date('d M Y', strtotime($invoice->date_from)) }} - {{ date('d M Y', strtotime($invoice->date_to)) }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Contact Number:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ $invoice->customer->contact_number ?? 'N/A' }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Email:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ $invoice->customer->email ?? 'N/A' }}</td>
            </tr>
        </table>

        <!-- Description Section -->
        @if($invoice->description)
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Description:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 80%;">{{ $invoice->description }}</td>
            </tr>
        </table>
        @endif

        <!-- Main Items Table -->
        <table>
            <thead>
                <tr>
                    <th class="secondary-header" style="width: 5%;">Sr.</th>
                    <th class="secondary-header" style="width: 40%;">Product / Service Description</th>
                    <th class="secondary-header" style="width: 8%;">Qty</th>
                    <th class="secondary-header" style="width: 12%;">Rate (AED)</th>
                    <th class="secondary-header" style="width: 12%;">Amount (AED)</th>
                    <th class="secondary-header" style="width: 10%;">VAT (%)</th>
                    <th class="secondary-header" style="width: 12%;">VAT Amount</th>
                    <th class="accent-total" style="width: 13%;">Total (AED)</th>
                </tr>
            </thead>
            <tbody>
                @php
                $running_total = 0;
                @endphp
                @foreach($invoice->items as $key => $item)
                @php
                $quantity = $item->quantity ?? 1;
                $rate = $item->rate ?? 0;
                $vatPercent = $item->vat ?? 0;
                $subtotal = $quantity * $rate;
                $vatAmount = $subtotal * ($vatPercent / 100);
                $rowTotal = $subtotal + $vatAmount;
                $running_total += $rowTotal;
                @endphp
                <tr>
                    <td class="num">{{ $key + 1 }}</td>
                    <td>{{ $item->item_name ?? 'N/A' }}</td>
                    <td class="num">{{ number_format($quantity, 2) }}</td>
                    <td class="num">{{ number_format($rate, 2) }}</td>
                    <td class="num">{{ number_format($subtotal, 2) }}</td>
                    <td class="num">{{ number_format($vatPercent, 2) }}%</td>
                    <td class="num">{{ number_format($vatAmount, 2) }}</td>
                    <td class="num">{{ number_format($rowTotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="accent-total">
                    <td colspan="7" style="text-align: right; font-weight: bold; padding: 8px;">TOTAL:</td>
                    <td class="num" style="padding: 8px; font-size: 14px; font-weight: bold;">{{ number_format($running_total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Amount in Words -->
        <table class="no-border">
            <tr>
                <td class="amount-highlight" style="padding: 8px; font-size: 13px;">
                    <b>Total Invoice Amount in Words:</b> {{ \App\Helpers\Helpers::numberToWords($invoice->total ?? $running_total) }} AED Only
                </td>
            </tr>
        </table>

        <!-- Summary -->
        <table>
            <tr class="light-header">
                <td style="padding: 6px; width: 80%;">Subtotal Amount (before VAT):</td>
                <td class="num" style="padding: 6px; width: 20%;">{{ number_format($invoice->subtotal ?? ($running_total - ($invoice->vat ?? 0)), 2) }}</td>
            </tr>
            @if(($invoice->vat ?? 0) > 0)
            <tr class="light-header">
                <td style="padding: 6px;">VAT Amount ({{ $invoice->vat_percent ?? 5 }}%):</td>
                <td class="num" style="padding: 6px;">{{ number_format($invoice->vat ?? 0, 2) }}</td>
            </tr>
            @endif
            <tr class="success-highlight">
                <td style="padding: 8px; font-size: 14px; font-weight: bold;">TOTAL AMOUNT:</td>
                <td class="num" style="padding: 8px; font-size: 14px; font-weight: bold;">{{ number_format($invoice->total ?? $running_total, 2) }}</td>
            </tr>
        </table>

        <!-- Notes Section -->
        @if($invoice->notes)
        <div style="margin-top: 15px; padding: 10px; background: #f0f0f0; border: 1px solid #000;">
            <strong>Notes:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif

        <!-- Footer -->
        <table style="margin-top: 15px;">
            <tr>
                <td style="border: none; text-align: center; font-size: 10px; color: #666;">
                    Thank you for your business. This is a computer-generated invoice and does not require a signature.
                </td>
            </tr>
        </table>
    </div>

</body>

</html>