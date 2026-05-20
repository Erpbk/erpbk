<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RiderID: {{$riderInvoice->rider->rider_id}} Month: {{date('M-Y',strtotime($riderInvoice->billing_month))}}</title>
    <style>
        /* ----- RESET & GLOBAL (modern card style from supplier/leasing invoice) ----- */
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
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ----- TABLES CLEAN BORDER (modern structure) ----- */
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

        /* ----- HEADER STYLES (same premium palette) ----- */
        .primary-header { background: #211c1d; color: white; font-weight: bold; }
        .secondary-header { background: #004aad; color: white; font-weight: bold; }
        .accent-total { background: #5271ff; color: white; font-weight: bold; }
        .light-header { background: #e6f1ff; color: #004aad; font-weight: bold; }
        .amount-highlight { background: #2A62FF; color: white; font-weight: bold; }
        .success-highlight { background: #004aad; color: white; font-weight: bold; }
        .yellow-highlight { background: #ffff00; font-weight: bold; padding: 8px; }
        .dark-accent { background: #211c1d; color: white; font-weight: bold; }

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

        /* ----- CARD LAYOUT (matching leasing invoice modern card style) ----- */
        .rider-card, .details-card {
            padding: 16px 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .invoice-box .card-header {
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
            min-width: 280px;
        }

        /* description / notes sections */
        .description-block {
            background: #f8fafc;
            border-left: 4px solid #004aad;
            padding: 12px 18px;
            margin: 16px 0;
            border-radius: 10px;
        }
        .notes-section {
            margin: 20px 0;
            padding: 12px 16px;
            background: #fef9e6;
            border-left: 4px solid #ffb347;
            border-radius: 8px;
        }

        /* items table enhancement */
        .items-table th, .items-table td {
            border: 1px solid #ccc;
        }
        .items-table th {
            background: #004aad;
            color: white;
            font-weight: 600;
            text-align: center;
        }

        /* financial summary compact card / right side */
        .financial-summary {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .financial-summary table {
            width: 45%;
            min-width: 270px;
            border: 1px solid #e2e8f0;
        }

        /* grand total modern badge */
        .grand-total-wrapper {
            margin-top: 24px;
            text-align: right;
        }
        .grand-total-card {
            display: inline-block;
            padding: 12px 28px;
            background: #004aad;
            color: white;
            border-radius: 30px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,74,173,0.2);
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

        .footer-note {
            margin-top: 28px;
            text-align: center;
            font-size: 11px;
            color: #5b6e8c;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            margin-top: auto;
        }
        .yellow {
            background: #ffff00;
            font-weight: bold;
            padding: 3px 6px;
            display: inline-block;
        }
        .red {
            color: red;
            font-weight: bold;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .invoice-box {
                box-shadow: none;
                padding: 12px;
                border-radius: 0;
            }
            .controls {
                display: none !important;
            }
            .rider-card, .details-card {
                box-shadow: none;
                border: 1px solid #ccc;
                break-inside: avoid;
            }
            th, .secondary-header, .card-header strong, .grand-total-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @media (max-width: 700px) {
            .flex-row-cards {
                flex-direction: column;
            }
            .invoice-box {
                padding: 15px;
            }
            .financial-summary table {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="controls no-print">
    <button type="button" class="print-btn" onclick="printModalContent()">Print Invoice</button>
</div>

<div class="invoice-box">
    @php
        $settings = company_table('settings')->pluck('value', 'name')->toArray();
        $total = 0;
        $total_qty = 0;
        $running_total = 0;
        $vat_percentage = Common::getSetting('vat_percentage');
        $deliveryfee = company_table('items')->where('name', 'Delivery fees')->first();
        $totalOrders = 0;
        $totalOrderValue = 0;
        if ($deliveryfee && isset($deliveryfee->id)) {
            $deliveryFeeItem = collect($riderInvoice->items)->firstWhere('item_id', $deliveryfee->id);
            if ($deliveryFeeItem && $deliveryFeeItem->qty > 0) {
                $totalOrders = $deliveryFeeItem->qty;
                $totalOrderValue = $deliveryFeeItem->qty * $deliveryfee->price;
            }
        }
        // adjustments
        $billing_month = date('M-y', strtotime($riderInvoice->billing_month));
        $fines = company_table('rta_fines')->where('billing_month' , $riderInvoice->billing_month)->where('rider_id' , $riderInvoice->rider->id)->sum('total_amount');
        $salik = company_table('saliks')->where('billing_month' , $billing_month)->where('rider_id' , $riderInvoice->rider->id)->sum('total_amount');
        $cod = company_table('vouchers')->where('ref_id' , $riderInvoice->rider->id)->where('voucher_type' , 'COD')->where('billing_month' , $riderInvoice->billing_month)->sum('amount');
        $penalty = company_table('vouchers')->where('ref_id' , $riderInvoice->rider->id)->where('voucher_type' , 'PN')->where('billing_month' , $riderInvoice->billing_month)->sum('amount');
        $incentive = company_table('vouchers')->where('ref_id' , $riderInvoice->rider->id)->where('voucher_type' , 'INC')->where('billing_month' , $riderInvoice->billing_month)->sum('amount');
        $advance_salary = company_table('vouchers')->where('ref_id' , $riderInvoice->rider->id)->where('voucher_type' , 'AL')->where('billing_month' , $riderInvoice->billing_month)->sum('amount');
        $vendor_charges = company_table('vouchers')->where('ref_id' , $riderInvoice->rider->id)->where('voucher_type' , 'VC')->where('billing_month' , $riderInvoice->billing_month)->sum('amount');
        $rider_balance = 0;
        if($riderInvoice->rider && $riderInvoice->rider->account_id) {
            $monthStart = date('Y-m-01', strtotime($riderInvoice->billing_month));
            $rider_balance = \App\Models\Transactions::where('account_id', $riderInvoice->rider->account_id)
                ->whereDate('billing_month', '<', $monthStart)
                ->sum(\DB::raw('debit - credit'));
        }
        $total_deductions = ($fines > 0 ? $fines : 0) + ($salik > 0 ? $salik : 0) + ($cod > 0 ? $cod : 0) + ($penalty > 0 ? $penalty : 0) + ($advance_salary > 0 ? $advance_salary : 0) + ($vendor_charges > 0 ? $vendor_charges : 0) + ($rider_balance > 0 ? $rider_balance : 0);
        $total_additions = ($incentive > 0 ? $incentive : 0) + ($rider_balance < 0 ? abs($rider_balance) : 0);
        $totalBeforeTax = 0;
        $finalAmount = 0;
        $paid_amount = 0;
        $rider_balance_final = 0;
    @endphp

    <!-- Header: Logo + Company + Title (modern card style) -->
    <table style="margin-bottom: 20px; border: none; background: transparent;">
        <tr style="border: none;">
            <td style="width: 33%; border: none !important; vertical-align: middle;">
                @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                    <img src="{{ Storage::url($settings['company_logo']) }}" width="150" alt="logo" />
                @endif
            </td>
            <td style="width: 34%; text-align: center; align-content: center; border: none !important;">
                <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight:700;">{{ $settings['company_name'] ?? '' }}</h4>
                <p style="margin: 3px 0; font-size: 12px;">{{ $settings['company_address'] ?? '' }}</p>
                <p style="margin: 3px 0; font-size: 12px;">TRN {{ $settings['vat_number'] ?? '' }}</p>
            </td>
            <td style="width: 33%; text-align: center; align-content: center; border: none !important;">
                <h2 style="margin: 0; font-weight: 800; color: #004aad; font-size: 24px;">RIDER INVOICE</h2>
            </td>
        </tr>
    </table>

    <!-- Two card layout: Rider Details + Invoice Reference (exactly like leasing invoice) -->
    <div class="flex-row-cards">
        <!-- Rider Main Card -->
        <div class="rider-card">
            <div class="card-header">
                <strong>👤 Rider Details</strong>
            </div>
            <div class="details-grid">
                <span class="detail-label">Rider ID:</span>
                <span class="detail-value">{{$riderInvoice->rider->rider_id}}</span>
                <span class="detail-label">Rider Name:</span>
                <span class="detail-value">{{$riderInvoice->rider->name}}</span>
                <span class="detail-label">Rider Status:</span>
                <span class="detail-value" @if(in_array($riderInvoice->rider->status,[3,4,5])) style="color:red;" @endif>{{ App\Helpers\General::RiderStatus($riderInvoice->rider->status) }}</span>
                <span class="detail-label">Mobile:</span>
                <span class="detail-value">{{@$riderInvoice->rider->sim->number}}</span>
                <span class="detail-label">Joining Date:</span>
                <span class="detail-value">{{$riderInvoice->rider->doj}}</span>
                <span class="detail-label">Client:</span>
                <span class="detail-value">{{@$riderInvoice->rider->vendor->name}}</span>
                <span class="detail-label">Fleet Supervisor:</span>
                <span class="detail-value">{{@$riderInvoice->rider->fleet_supervisor}}</span>
                <span class="detail-label">Sup. Contact:</span>
                <span class="detail-value">{{@$riderInvoice->rider->company_contact}}</span>
                <span class="detail-label">Working Days:</span>
                <span class="detail-value">{{$riderInvoice->working_days}} | Off: {{@$riderInvoice->off}}</span>
                <span class="detail-label">Perfect Attendance:</span>
                <span class="detail-value">{{$riderInvoice->perfect_attendance}}</span>
                <span class="detail-label">Rejection:</span>
                <span class="detail-value red">{{@$riderInvoice->rejection}}</span>
                <span class="detail-label">Performance:</span>
                <span class="detail-value">{{@$riderInvoice->performance}}</span>
            </div>
        </div>

        <!-- Invoice & Service Period Card -->
        <div class="details-card">
            <div class="card-header">
                <strong>📄 Invoice Summary</strong>
            </div>
            <div class="details-grid">
                <span class="detail-label">Invoice No:</span>
                <span class="detail-value">{{ \App\Helpers\General::inv_sch($riderInvoice->id,$riderInvoice->created_at) }}</span>
                <span class="detail-label">Invoice Date:</span>
                <span class="detail-value">{{ $riderInvoice->created_at->format("d/m/Y") }}</span>
                <span class="detail-label">Billing Month:</span>
                <span class="detail-value">{{date('M-Y',strtotime($riderInvoice->billing_month))}}</span>
                <span class="detail-label">Service Period:</span>
                <span class="detail-value">{{date('d-m-y', strtotime($riderInvoice->billing_month))}} to {{date('t-m-y', strtotime($riderInvoice->billing_month))}}</span>
                <span class="detail-label">Zone:</span>
                <span class="detail-value">{{$riderInvoice->zone}}</span>
                <span class="detail-label">Bike No:</span>
                <span class="detail-value">{{@$riderInvoice->bike->plate}}</span>
                <span class="detail-label">Road Permit No:</span>
                <span class="detail-value">—</span>
                <span class="detail-label">Insurance Policy No:</span>
                <span class="detail-value">—</span>
            </div>
        </div>
    </div>

    <!-- Items Table (modern card style, like leasing invoice) -->
    @if($riderInvoice->items && $riderInvoice->items->count() > 0)
    <div style="overflow-x: auto; margin-top: 5px;">
        <table class="items-table">
            <thead>
                <tr>
                    <th>Sr.</th>
                    <th>Product / Service Description</th>
                    <th>FMO</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Amount</th>
                    <th>VAT Rate</th>
                    <th>VAT Amount</th>
                    <th>Total ({{ \App\Helpers\Currency::code() }})</th>
                </tr>
            </thead>
            <tbody>
                @php $running_total = 0; @endphp
                @foreach($riderInvoice->items as $key=>$val)
                @php
                    $total += $val->amount;
                    $total_qty += $val->qty;
                    $vatRate = $riderInvoice->vat > 0 ? $vat_percentage : 0;
                    $vatAmtRow = $riderInvoice->vat > 0 ? $val->amount * $vatRate / 100 : 0;
                    $rowTotal = $val->amount + $vatAmtRow;
                    $running_total += $rowTotal;
                @endphp
                <tr>
                    <td class="num">{{ $key+1 }}</td>
                    <td>{{ $val->riderInv_item }} {{ \App\Models\Items::where('id',$val->item_id)->value('name') }}</td>
                    <td>{{ strtoupper(date('M\'y', strtotime($riderInvoice->billing_month))) }}</td>
                    <td class="num">{{ $val->qty == 0 ? '-' : $val->qty }}</td>
                    <td class="num">{{ $val->rate == 0 ? '-' : number_format($val->rate, 2) }}</td>
                    <td class="num">{{ number_format($val->amount, 2) }}</td>
                    <td class="num">{{ number_format($vatRate, 0) }}%</td>
                    <td class="num">{{ number_format($vatAmtRow, 2) }}</td>
                    <td class="num">{{ number_format($running_total, 2) }}</td>
                </tr>
                @endforeach
                @php $items_total = $running_total; @endphp
                <tr class="accent-total">
                    <td colspan="3" style="text-align:right; font-weight:bold;">Total Orders ({{date('M-Y',strtotime($riderInvoice->billing_month))}})</td>
                    <td class="num">{{ number_format($totalOrders, 0) }}</td>
                    <td colspan="4" style="text-align:right; font-weight:bold;">ITEMS TOTAL</td>
                    <td class="num" style="font-weight:bold;">{{ number_format($items_total, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Deductions Table (clean bordered) -->
    @if($total_deductions > 0 || $fines > 0 || $salik > 0 || $cod > 0 || $penalty > 0 || $advance_salary > 0 || $vendor_charges > 0 || $rider_balance > 0)
    <table style="margin-top: 12px;">
        <thead>
            <tr><th colspan="5" class="secondary-header">Deductions</th></tr>
        </thead>
        <tbody>
            @if($rider_balance > 0)
            <tr><td colspan="4">Previous Balance (Deduction)</td><td class="num">-{{ number_format(abs($rider_balance), 2) }}</td></tr>
            @endif
            @if($fines > 0)
            <tr><td colspan="4">RTA Fine Charges</td><td class="num">-{{ number_format($fines, 2) }}</td></tr>
            @endif
            @if($salik > 0)
            <tr><td colspan="4">Salik Charges</td><td class="num">-{{ number_format($salik, 2) }}</td></tr>
            @endif
            @if($cod > 0)
            <tr><td colspan="4">COD Amount</td><td class="num">-{{ number_format($cod, 2) }}</td></tr>
            @endif
            @if($penalty > 0)
            <tr><td colspan="4">Penalty Amount</td><td class="num">-{{ number_format($penalty, 2) }}</td></tr>
            @endif
            @if($advance_salary > 0)
            <tr><td colspan="4">Advance Loan</td><td class="num">-{{ number_format($advance_salary, 2) }}</td></tr>
            @endif
            @if($vendor_charges > 0)
            <tr><td colspan="4">Vendor Charges</td><td class="num">-{{ number_format($vendor_charges, 2) }}</td></tr>
            @endif
            <tr class="accent-total"><td colspan="4" style="text-align:right;">Total Deductions</td><td class="num">-{{ number_format($total_deductions, 2) }}</td></tr>
        </tbody>
    </table>
    @endif

    <!-- Additions Section -->
    @if($total_additions > 0)
    <table style="margin-top: 8px;">
        <thead><tr><th colspan="5" class="secondary-header">Additions</th></tr></thead>
        <tbody>
            @if($rider_balance < 0)
            <tr><td colspan="4">Previous Balance (Addition)</td><td class="num">+{{ number_format(abs($rider_balance), 2) }}</td></tr>
            @endif
            @if($incentive > 0)
            <tr><td colspan="4">Incentive Amount</td><td class="num">+{{ number_format($incentive, 2) }}</td></tr>
            @endif
            <tr class="accent-total"><td colspan="4" style="text-align:right;">Total Additions</td><td class="num">+{{ number_format($total_additions, 2) }}</td></tr>
        </tbody>
    </table>
    @endif

    <!-- Financial Summary Compact (Right) + Grand Total (badge) -->
    @php
        $finalAmount = $items_total - $total_deductions + $total_additions;
        $paid_amount = company_table('vouchers')->where('ref_id', $riderInvoice->rider->id)->where('voucher_type', 'PAY')->where('billing_month', $riderInvoice->billing_month)->sum('amount');
        $rider_balance_final = $paid_amount - $finalAmount;
    @endphp
    <div class="financial-summary">
        <table>
            <thead><tr><th colspan="2" class="secondary-header">Financial Summary</th></tr></thead>
            <tbody>
                <tr><td style="font-weight: 600;">Subtotal (Items)</td><td class="num">{{ number_format($total, 2) }}</td></tr>
                @if($riderInvoice->vat > 0)<tr><td>VAT ({{$vat_percentage}}%)</td><td class="num">{{ number_format($total * $vat_percentage / 100, 2) }}</td></tr>@endif
                <tr><td>Total Deductions</td><td class="num">-{{ number_format($total_deductions, 2) }}</td></tr>
                <tr><td>Total Additions</td><td class="num">+{{ number_format($total_additions, 2) }}</td></tr>
                <tr class="success-highlight"><td><strong>NET PAYABLE</strong></td><td class="num"><strong>{{ number_format($finalAmount, 2) }}</strong></td></tr>
                <tr><td>Paid Amount to Rider</td><td class="num">{{ number_format($paid_amount, 2) }}</td></tr>
                <tr><td>Rider Balance</td><td class="num">{{ number_format($rider_balance_final, 2) }}</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Grand Total Card (modern badge) -->
    <div class="grand-total-wrapper">
        <div class="grand-total-card">
            <div>TOTAL INVOICE AMOUNT</div>
            <div>{{ \App\Helpers\Currency::format($finalAmount, 2) }}</div>
        </div>
    </div>

    <!-- Notes & Footer -->
    <div class="notes-section">
        <strong>📌 Note:</strong><br>
        {{$riderInvoice->notes ?? 'If a rider\'s monthly orders are less than 400 or they have attendance for less than 26 days or less than 10 hours of login time in a day, we will charge them half of their bike rent and mobile bill, and they will not be eligible for minimum guarantee fees.'}}
    </div>

    <div style="text-align: right; margin-top: 30px;">
        <div>
            <br><br><br>
            <span style="display: inline-block; border-top: 2px solid #000; padding-top: 8px; font-weight: bold;">{{$riderInvoice->rider->name}}</span>
            <br>
        </div>
    </div>
    <div style="height: 20px;"></div>
    <div style="position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 11px; color: #5b6e8c; border-top: 1px solid #e2e8f0; padding-top: 16px; padding-bottom: 0px; background: white; width: 100%; z-index: 1000;">
        <p style="margin: 0; background: white;">Thank you for your partnership! For queries reach: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}</p>
    </div>
    @else
    <div style="text-align: center; padding: 40px;"><p>No invoice items found for this period.</p></div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.num').forEach(function(el) {
            let raw = el.innerText.trim();
            let num = parseFloat(raw.replace(/,/g, ''));
            if (!isNaN(num) && raw !== '') {
                let formatted = num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (el.innerText !== formatted) el.innerText = formatted;
            }
        });
    });
</script>
</body>
</html>