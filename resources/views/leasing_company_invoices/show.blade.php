<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leasing Company Invoice #{{ $invoice->invoice_number ?? $invoice->id }} Month: {{ date('M-Y', strtotime($invoice->billing_month)) }}</title>
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
    </style>
</head>

<body>

    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 9999; display: flex; gap: 10px;">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
        <a href="{{ route('leasingCompanyInvoices.edit', $invoice->id) }}" class="print-btn" style="text-decoration: none; display: inline-block; padding: 8px 12px;">Edit</a>
        <a href="{{ route('leasingCompanyInvoices.index') }}" class="print-btn" style="text-decoration: none; display: inline-block; padding: 8px 12px;">Back to List</a>
    </div>

    <div class="invoice-box">
        @php
        $settings = DB::table('settings')->pluck('value', 'name')->toArray();
        @endphp
        <table width="100%" style="font-family: sans-serif;">
            <tr>
                <td width="33.33%"><img src="{{ URL::asset('assets/img/logo-full.png') }}" width="150" /></td>
                <td width="33.33%" style="text-align: center;">
                    <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px;">{{ $settings['company_name'] ?? '' }}</h4>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">{{ $settings['company_address'] ?? '' }}</p>
                    <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;"> TRN {{ $settings['vat_number'] ?? '' }}</p>
                </td>
                <td width="33.33%"></td>
            </tr>
        </table>

        <table style="width: 100%; margin-bottom: 10px;">
            <tr>
                <td colspan="4" class="primary-header" style="border: 1px solid #000; padding: 10px; text-align: center; font-size: 18px;">
                    LEASING COMPANY INVOICE
                </td>
            </tr>
        </table>

        <!-- Invoice and Leasing Company Info -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Invoice No:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 30%;">{{ $invoice->invoice_number ?? 'LI-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Leasing Company Invoice No:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 30%;">{{ $invoice->leasing_company_invoice_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Reference Number:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 30%;">{{ $invoice->reference_number ?? 'N/A' }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Leasing Company:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 30%;">{{ $invoice->leasingCompany->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Billed To:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 30%;">{{ $settings['company_name'] ?? 'N/A' }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Billed To:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 30%;">{{ $settings['company_name'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">TRN Number:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ $invoice->leasingCompany->trn_number ?? 'N/A' }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Billing Month:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ date('M-Y', strtotime($invoice->billing_month)) }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Service Period From:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ date('01/m/Y', strtotime($invoice->billing_month)) }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Service Period To:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ date('t/m/Y', strtotime($invoice->billing_month)) }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Contact Person:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ $invoice->leasingCompany->contact_person ?? 'N/A' }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Contact Number:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ $invoice->leasingCompany->contact_number ?? 'N/A' }}</td>
            </tr>
        </table>

        <!-- Leasing Company Details Section -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Description:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 80%;">{{ $invoice->descriptions ?? 'N/A' }}</td>
            </tr>
        </table>

        <!-- Main Items Table (same structure as rider invoice) -->
        <table>
            <tr>
                <th rowspan="2" class="secondary-header">Sr.</th>
                <th rowspan="2" class="secondary-header">Product / Service Description</th>
                <th rowspan="2" class="secondary-header">Qty</th>
                <th rowspan="2" class="secondary-header">Days</th>
                <th rowspan="2" class="secondary-header">Rate (Monthly)</th>
                <th rowspan="2" class="secondary-header">Amount</th>
                <th colspan="2" class="secondary-header">VAT</th>
                <th rowspan="2" class="accent-total">Total (In AED)</th>
            </tr>
            <tr>
                <th class="secondary-header">Rate</th>
                <th class="secondary-header">Amount</th>
            </tr>
            @php
            $running_total = 0;
            @endphp
            @foreach($invoice->items as $key => $item)
            @php
            $vatRate = $item->tax_rate ?? 0;
            $vatAmtRow = $item->tax_amount ?? 0;
            $rowTotal = $item->total_amount ?? ($item->rental_amount + $vatAmtRow);
            $proratedAmount = $rowTotal - $vatAmtRow;
            $running_total += $rowTotal;
            @endphp
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>Bike # {{ $item->bike->plate ?? 'N/A' }} ({{ DB::table('bikes')->where('id', $item->bike_id)->first()->emirates ?? 'N/A' }})</td>
                <td class="num">1</td>
                <td class="num">{{ $item->days ?? 1 }}</td>
                <td class="num">{{ number_format($item->rental_amount, 2) }}</td>
                <td class="num">{{ number_format($proratedAmount, 2) }}</td>
                <td>{{ number_format($vatRate, 0) }}%</td>
                <td class="num">{{ number_format($item->tax_amount ?? 0, 2) }}</td>
                <td class="num">{{ number_format($running_total, 2) }}</td>
            </tr>
            @endforeach
            @php
            $items_total = $running_total;
            @endphp
            <tr class="accent-total">
                <td></td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold;">Total Bikes:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; text-align: right;">{{ $invoice->items->count() ?? 0 }} Bikes</td>
                <td colspan="5" style="text-align:right; padding: 8px;">ITEMS TOTAL</td>
                <td colspan="2" class="num" style="padding: 8px; font-size: 14px;">{{ number_format($invoice->total_amount ?? $items_total, 2) }}</td>
            </tr>
        </table>

        <!-- Amount in Words -->
        <table class="no-border">
            <tr>
                <td class="amount-highlight" style="padding: 8px; font-size: 13px;"><b>Total Invoice Amount in Words:</b> {{ \App\Helpers\Helpers::numberToWords($invoice->total_amount ?? 0) }} AED Only</td>
            </tr>
        </table>

        <!-- Summary -->
        <table>
            <tr class="light-header">
                <td style="padding: 6px;">Total Amount (before VAT):</td>
                <td class="num" style="padding: 6px;">{{ number_format($invoice->subtotal ?? 0, 2) }}</td>
            </tr>
            @if(($invoice->vat ?? 0) > 0)
            <tr class="light-header">
                <td style="padding: 6px;">Add: VAT</td>
                <td class="num" style="padding: 6px;">{{ number_format($invoice->vat ?? 0, 2) }}</td>
            </tr>
            @endif
            <tr class="success-highlight">
                <td style="padding: 8px; font-size: 14px;">TOTAL AMOUNT:</td>
                <td class="num" style="padding: 8px; font-size: 14px;">{{ number_format($invoice->total_amount ?? 0, 2) }}</td>
            </tr>
        </table>

        @if($invoice->notes)
        <div style="margin-top: 15px; padding: 10px; background: #f0f0f0; border: 1px solid #000;">
            <strong>Notes:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif
    </div>

</body>

</html>