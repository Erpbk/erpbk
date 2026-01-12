<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>RiderID: {{$riderInvoice->rider->rider_id}} Month: {{date('M-Y',strtotime($riderInvoice->billing_month))}}</title>
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

        .header {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
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

        .highlight {
            background: #f0f0f0;
            font-weight: bold;
        }

        .green {
            background: #92d050;
            font-weight: bold;
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

        .dark-accent {
            background: #211c1d;
            color: white;
            font-weight: bold;
        }

        .footer-note {
            font-size: 11px;
            margin-top: 10px;
            color: red;
            font-weight: bold;
            text-align: center;
        }

        .sign-box {
            margin-top: 25px;
            text-align: right;
            font-weight: bold;
        }

        .sign-box span {
            display: block;
            margin-top: 8px;
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

        /* Print styles to ensure background colors print without changing design */
        @media print {

            body,
            *,
            .primary-header,
            .secondary-header,
            .accent-total,
            .light-header,
            .amount-highlight,
            .success-highlight,
            .dark-accent,
            .green,
            .yellow,
            .red {
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
            </tr>

        </table>
        <table style="width: 100%; margin-bottom: 10px;">
            <tr>
                <td colspan="4" class="primary-header" style="border: 1px solid #000; padding: 10px; text-align: center; font-size: 18px;">
                    RIDER INVOICE
                </td>
            </tr>
        </table>
        <!-- Invoice and Rider Info Combined -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Invoice No:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 30%;">{{ \App\Helpers\General::inv_sch($riderInvoice->id,$riderInvoice->created_at) }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Joining Date:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 30%;">{{$riderInvoice->rider->doj}}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Invoice Date:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{ $riderInvoice->created_at->format("d/m/Y") }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Zone:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{$riderInvoice->zone}}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Service Period From:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{date('d-m-y', strtotime($riderInvoice->billing_month))}}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Service Period To:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{date('t-m-y', strtotime($riderInvoice->billing_month))}}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Road Permit No:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;"></td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Bike No:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{@$riderInvoice->bike->plate}}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Insurance Policy No:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;"></td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Billing Month:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{date('M-Y',strtotime($riderInvoice->billing_month))}}</td>
            </tr>
        </table>

        <!-- Rider Details Section -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
            <tr>
                <td colspan="4" class="light-header" style="border: 1px solid #000; padding: 8px; text-align: center; font-size: 14px;">
                    RIDER DETAILS
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Rider No:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 30%;">{{$riderInvoice->rider->id}}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0; width: 20%;">Rider Status:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; width: 30%;" @if(in_array($riderInvoice->rider->status,[3,4,5])) style="border: 1px solid #000; padding: 4px 6px; color:red;" @endif>{{ App\Helpers\General::RiderStatus($riderInvoice->rider->status) }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Rider ID:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{$riderInvoice->rider->rider_id }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Working Days:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{$riderInvoice->working_days}} | Off: {{@$riderInvoice->off}}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Rider Name:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{$riderInvoice->rider->name}}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Perfect Attendance:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{$riderInvoice->perfect_attendance}}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Client:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{@$riderInvoice->rider->vendor->name }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Rejection:</td>
                <td style="border: 1px solid #000; padding: 4px 6px; color: red;">{{@$riderInvoice->rejection}}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Mobile:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{@$riderInvoice->rider->sim->number }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Performance:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{@$riderInvoice->performance}}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Fleet Supervisor:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{@$riderInvoice->rider->fleet_supervisor }}</td>
                <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #f0f0f0;">Sup. Contact:</td>
                <td style="border: 1px solid #000; padding: 4px 6px;">{{@$riderInvoice->rider->company_contact }}</td>
            </tr>
        </table>

        <!-- Main Table -->
        <table>
            <tr>
                <th rowspan="2" class="secondary-header">Sr.</th>
                <th rowspan="2" class="secondary-header">Product / Service Description</th>
                <th rowspan="2" class="secondary-header">FMO</th>
                <th rowspan="2" class="secondary-header">Qty</th>
                <th rowspan="2" class="secondary-header">Rate</th>
                <th rowspan="2" class="secondary-header">Amount</th>
                <th colspan="2" class="secondary-header">VAT</th>
                <th rowspan="2" class="accent-total">Total (In AED)</th>
            </tr>
            <tr>
                <th class="secondary-header">Rate</th>
                <th class="secondary-header">Amount</th>
            </tr>
            @php
            $total = 0;
            $total_qty = 0;
            $running_total = 0;
            $vat_percentage = Common::getSetting('vat_percentage');

            @endphp
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
                <td>{{ $key+1 }}</td>
                <td>{{ $val->riderInv_item }} {{ \App\Models\Items::where('id',$val->item_id)->value('name') }}</td>
                <td>{{ strtoupper(date('M\'y', strtotime($riderInvoice->billing_month))) }}</td>
                <td class="num">{{ $val->qty == 0 ? '-' : $val->qty }}</td>
                <td class="num">{{ $val->rate == 0 ? '-' : number_format($val->rate, 2) }}</td>
                <td class="num">{{ number_format($val->amount, 2) }}</td>
                <td>{{ number_format($vatRate, 0) }}%</td>
                <td class="num">{{ number_format($vatAmtRow, 2) }}</td>
                <td class="num">{{ number_format($running_total, 2) }}</td>
            </tr>
            @endforeach

            @php
            // Preserve items-only total (includes VAT per row if applied)
            $items_total = $running_total;
            @endphp
            @php
            $deliveryfee = DB::table('items')->where('name', 'Delivery fees')->first();

            // Calculate total orders from delivery fee item
            $totalOrders = 0;
            $totalOrderValue = 0;

            if ($deliveryfee && isset($deliveryfee->id)) {
            // Find the delivery fee item in the rider invoice items
            $deliveryFeeItem = collect($riderInvoice->items)->firstWhere('item_id', $deliveryfee->id);

            if ($deliveryFeeItem && $deliveryFeeItem->qty > 0) {
            // Calculate total orders: qty * price
            $totalOrders = $deliveryFeeItem->qty;
            $totalOrderValue = $deliveryFeeItem->qty * $deliveryfee->price;
            }
            }
            @endphp
            <tr class="accent-total">
                <td colspan="3" style="text-align:right; padding: 8px;">Total Orders ({{date('M-Y',strtotime($riderInvoice->billing_month))}})</td>
                <td class="num">{{ number_format($totalOrders, 0) }}</td>
                <td colspan="4" style="text-align:right; padding: 8px;">ITEMS TOTAL</td>
                <td class="num" style="padding: 8px; font-size: 14px;">{{ number_format($items_total, 2) }}</td>
            </tr>
        </table>

        @php
        $billing_month = date('M-y', strtotime($riderInvoice->billing_month));
        // Fetch selected adjustments
        $fines = DB::Table('rta_fines')->where('billing_month' , $riderInvoice->billing_month)->where('rider_id' , $riderInvoice->rider->id)->sum('total_amount');
        $salik = DB::Table('saliks')->where('billing_month' , $billing_month)->where('rider_id' , $riderInvoice->rider->id)->sum('total_amount');
        $cod = DB::table('vouchers')->where('ref_id' , $riderInvoice->rider->id)->where('voucher_type' , 'COD')->where('billing_month' , $riderInvoice->billing_month)->sum('amount');
        $penalty = DB::table('vouchers')->where('ref_id' , $riderInvoice->rider->id)->where('voucher_type' , 'PN')->where('billing_month' , $riderInvoice->billing_month)->sum('amount');
        $incentive = DB::table('vouchers')->where('ref_id' , $riderInvoice->rider->id)->where('voucher_type' , 'INC')->where('billing_month' , $riderInvoice->billing_month)->sum('amount');
        $advance_salary = DB::table('vouchers')->where('ref_id' , $riderInvoice->rider->id)->where('voucher_type' , 'AL')->where('billing_month' , $riderInvoice->billing_month)->sum('amount');
        $vendor_charges = DB::table('vouchers')->where('ref_id' , $riderInvoice->rider->id)->where('voucher_type' , 'VC')->where('billing_month' , $riderInvoice->billing_month)->sum('amount');
        // Previous balance from account transactions (sum of debit - credit before invoice month)
        $rider_balance = 0;
        if($riderInvoice->rider && $riderInvoice->rider->account_id) {
        $monthStart = date('Y-m-01', strtotime($riderInvoice->billing_month));
        $rider_balance = \App\Models\Transactions::where('account_id', $riderInvoice->rider->account_id)
        ->whereDate('billing_month', '<', $monthStart)
            ->sum(\DB::raw('debit - credit'));
            }

            // Build totals
            $total_deductions = ($fines > 0 ? $fines : 0)
            + ($salik > 0 ? $salik : 0)
            + ($cod > 0 ? $cod : 0)
            + ($penalty > 0 ? $penalty : 0)
            + ($advance_salary > 0 ? $advance_salary : 0)
            + ($vendor_charges > 0 ? $vendor_charges : 0)
            + ($rider_balance > 0 ? $rider_balance : 0); // positive balance is deduction

            $total_additions = ($incentive > 0 ? $incentive : 0)
            + ($rider_balance < 0 ? abs($rider_balance) : 0); // negative balance is addition

                $finalAmount=$items_total - $total_deductions + $total_additions;
                @endphp

                <!-- Deductions Section -->
                <table>
                    <tr>
                        <th colspan="5" class="secondary-header">Deductions</th>
                    </tr>
                    @if($rider_balance > 0)
                    <tr>
                        <td colspan="4">Previous Balance (Deduction)</td>
                        <td class="num">-{{ number_format(abs($rider_balance), 2) }}</td>
                    </tr>
                    @endif
                    @if($fines > 0)
                    <tr>
                        <td colspan="4">RTA Fine Charges</td>
                        <td class="num">-{{ number_format($fines, 2) }}</td>
                    </tr>
                    @endif
                    @if($salik > 0)
                    <tr>
                        <td colspan="4">Salik Charges</td>
                        <td class="num">-{{ number_format($salik, 2) }}</td>
                    </tr>
                    @endif
                    @if($cod > 0)
                    <tr>
                        <td colspan="4">COD Amount</td>
                        <td class="num">-{{ number_format($cod, 2) }}</td>
                    </tr>
                    @endif
                    @if($penalty > 0)
                    <tr>
                        <td colspan="4">Penalty Amount</td>
                        <td class="num">-{{ number_format($penalty, 2) }}</td>
                    </tr>
                    @endif
                    @if($advance_salary > 0)
                    <tr>
                        <td colspan="4">Advance Loan</td>
                        <td class="num">-{{ number_format($advance_salary, 2) }}</td>
                    </tr>
                    @endif
                    @if($vendor_charges > 0)
                    <tr>
                        <td colspan="4">Vendor Charges</td>
                        <td class="num">-{{ number_format($vendor_charges, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="accent-total">
                        <td colspan="4" style="text-align:right; padding: 8px;">Total Deductions</td>
                        <td class="num" style="padding: 8px; font-size: 14px;">-{{ number_format($total_deductions, 2) }}</td>
                    </tr>
                </table>

                <!-- Additions Section -->
                @if($incentive > 0)
                <table>
                    <tr>
                        <th colspan="5" class="secondary-header">Additions</th>
                    </tr>
                    @if($rider_balance < 0)
                        <tr>
                        <td colspan="4">Previous Balance (Addition)</td>
                        <td class="num">+{{ number_format(abs($rider_balance), 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="4">Incentive Amount</td>
                            <td class="num">+{{ number_format($incentive, 2) }}</td>
                        </tr>
                        <tr class="accent-total">
                            <td colspan="4" style="text-align:right; padding: 8px;">Total Additions</td>
                            <td class="num" style="padding: 8px; font-size: 14px;">+{{ number_format($total_additions, 2) }}</td>
                        </tr>
                </table>
                @endif

                <!-- Amount in Words -->
                <table class="no-border">
                    <tr>
                        <td class="amount-highlight" style="padding: 8px; font-size: 13px;"><b>Total Invoice Amount in Words:</b> {{ $finalAmount }} AED</td>
                    </tr>
                </table>

                <!-- Summary -->
                @php
                $totalBeforeTax = $total;
                $vatAmount = $riderInvoice->vat > 0 ? $total * $vat_percentage / 100 : 0;
                $totalAfterTax = $totalBeforeTax + $vatAmount;
                @endphp
                <table>
                    <tr class="light-header">
                        <td style="padding: 6px;">Total Amount before charges:</td>
                        <td class="num" style="padding: 6px;">{{ number_format($totalBeforeTax, 2) }}</td>
                    </tr>
                    @if($vatAmount > 0)
                    <tr class="light-header">
                        <td style="padding: 6px;">Add: VAT - {{ $vat_percentage }}%</td>
                        <td class="num" style="padding: 6px;">{{ number_format($vatAmount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="success-highlight">
                        <td style="padding: 8px; font-size: 14px;">TOTAL AMOUNT AFTER CHARGES:</td>
                        <td class="num" style="padding: 8px; font-size: 14px;">{{ number_format($finalAmount, 2) }}</td>
                    </tr>
                    @php
                    $paid_amount = DB::table('vouchers')->where('ref_id', $riderInvoice->rider->id)->where('voucher_type', 'PAY')->where('billing_month', $riderInvoice->billing_month)->sum('amount');
                    $rider_balance = $paid_amount - $finalAmount;
                    @endphp
                    <tr class="amount-highlight">
                        <td style="padding: 6px;">Paid Amount to Rider:</td>
                        <td class="num" style="padding: 6px;">{{ number_format($paid_amount, 2) }}</td>
                    </tr>
                    <tr class="amount-highlight">
                        <td style="padding: 6px;">Rider Balance:</td>
                        <td class="num" style="padding: 6px;">{{ number_format($rider_balance, 2) }}</td>
                    </tr>
                </table>

                <!-- Footer -->
                <div class="footer-note">
                    {{$riderInvoice->notes ?? 'Note : If a rider\'s monthly orders are less than 400 or if they have attendance for less than 26 days or less than 10 hours of login time in a day, we will charge them half of their bike rent and mobile bill, and they will not be eligible for minimum guarantee fees.'}}
                </div>

                <!-- Signature -->
                <div class="sign-box">
                    For Rider Name <br>
                    <span class="yellow">{{$riderInvoice->rider->name}}</span>
                    <span>### Sign</span>
                </div>
    </div>

</body>

</html>