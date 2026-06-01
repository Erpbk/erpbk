<!DOCTYPE html>
<html>
<head>
    <title>Account Statement</title>

    <style>

        @page{
            margin:10mm;
        }

        body{
            font-family: Arial, sans-serif;
            font-size:12px;
            color:#222;
        }
        .header{
            display:flex;
            justify-content:space-between;
            margin-bottom:30px;
        }

        .company h1{
            margin:0;
            font-size:30px;
        }

        .company p{
            margin:3px 0;
            color:#666;
        }

        .statement{
        }

        .statement h2{
            margin:0;
            color:#004aad;
            font-size:38px;
            font-weight:300;
        }

        .statement table{
            margin-top:10px;
            border-collapse:collapse;
        }

        .statement td{
            padding:6px 10px;
            border-bottom:1px solid #ddd;
        }

        .account-box{
            border-top:3px solid #d4aa00;
            border-bottom:1px solid #d4aa00;
            padding:15px 0;
            margin-bottom:20px;
        }

        .account-box h3{
            margin:0 0 10px;
            font-size:28px;
        }

        table.transactions{
            width:100%;
            border-collapse:collapse;
        }

        table.transactions th{
            background:#004aad !important;
            color:white;
            text-align:center;
            padding:10px;
            font-size:13px;
        }

        table.transactions td{
            padding:8px 10px;
            border-bottom:1px solid #e5e5e5;
            vertical-align:top;
        }

        table.transactions tr:nth-child(even){
            background:#fafafa;
        }

        .text-right{
            text-align:right;
        }

        .footer{
            margin-top:25px;
            text-align:center;
            color:#666;
            font-size:11px;
        }

        .page-break{
            page-break-after:always;
        }

        @media print {

            *{
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            table.transactions th{
                background:#c4c6c9 !important;
                color:black !important;
            }

        }

    </style>
</head>
<body>

@php
$settings = company_table('settings')->pluck('value', 'name')->toArray();
$runningBalance = $openingBalance;

$totalDebit = 0;
$totalCredit = 0;

$accountName = 'All Accounts';

if(request('account')){
    $account = \App\Models\Accounts::find(request('account'));

    if($account){
        $accountName = $account->account_code . ' - ' . $account->name;
    }
}

@endphp

<div class="header">

    <div class="company" style="margin-left: 20px;">
        @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
            <img src="{{ storage_url($settings['company_logo']) }}" width="150" alt="logo" />
        @endif
        <h1 style="color: #004aad;">{{ ucwords($settings['company_name']) }}</h1>
        <p>{{ 'Address: ' . $settings['company_address'] }}</p>
        <p>{{ 'TRN: ' . $settings['vat_number'] }}</p>
        <p>{{ 'Phone: ' . $settings['company_phone'] }}</p>
        <p>{{ 'Email: ' . $settings['company_email'] }}</p>
    </div>

    <div class="statement">

        <h2>Account Statement</h2>

        <table>

            <tr>
                <td><strong>Account</strong></td>
                <td>{{ $accountName }}</td>
            </tr>

            <tr>
                <td><strong>Period</strong></td>
                <td>

                    @if(request('from_date'))

                        {{ request('from_date') }}
                        to
                        {{ request('to_date') }}

                    @elseif(request('month'))

                        {{ \Carbon\Carbon::parse(request('month').'-01')->format('F Y') }}

                    @else

                        All History

                    @endif

                </td>
            </tr>
            <tr>
                <td><strong>Opening Balance</strong></td>
                <td>{{ number_format($openingBalance, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Closing Balance</strong></td>
                <td>{{ number_format($closingBalance, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Generated</strong></td>
                <td>{{ now()->format('d M Y h:i A') }}</td>
            </tr>

        </table>

    </div>

</div>

<table class="transactions">

    <thead>

        <tr style="background:#004aad; color:white;">

            <th>Date</th>

            <th style="text-align:left !important;">Description</th>

            <th style="text-align:left !important;">Reference</th>

            <th>Voucher</th>

            <th>Debit</th>

            <th>Credit</th>

            <th>Balance</th>

        </tr>

    </thead>

    <tbody>

        {{-- Opening Balance --}}
        <tr>

            <td>
                {{ request('from_date') ? \Carbon\Carbon::parse(request('from_date'))->format('d M Y') :  '-' }}
            </td>

            <td>
                <strong>Opening Balance</strong>
            </td>

            <td></td>
            <td></td>

            <td class="text-right"></td>

            <td class="text-right"></td>

            <td class="text-right">
                {{ number_format($openingBalance, 2) }}
            </td>

        </tr>

        @foreach($transactions as $row)

            @php

                $runningBalance += ($row->debit - $row->credit);

                $totalDebit += $row->debit;

                $totalCredit += $row->credit;

            @endphp

            <tr>

                <td style="white-space: nowrap;">
                    {{ \Carbon\Carbon::parse($row->trans_date)->format('d M Y') }}
                </td>

                <td>

                    {!! $row->narration !!}

                </td>

                <td>
                    {{ $row->voucher?->reference_number ?? '' }}
                </td>

                <td  style="white-space: nowrap;">

                    {{ $row->voucher_number }}

                </td>

                <td class="text-right">

                    {{ $row->debit > 0 ? number_format($row->debit,2) : '' }}

                </td>

                <td class="text-right">

                    {{ $row->credit > 0 ? number_format($row->credit,2) : '' }}

                </td>

                <td class="text-right">

                    {{ number_format($runningBalance,2) }}

                </td>

            </tr>

        @endforeach

        {{-- Totals --}}
        <tr>

            <td colspan="2"></td>
            <td colspan="2">

                <strong>Total</strong>
            </td>

            <td class="text-right">

                <strong>
                    {{ number_format($totalDebit,2) }}
                </strong>

            </td>

            <td class="text-right">

                <strong>
                    {{ number_format($totalCredit,2) }}
                </strong>

            </td>

            <td class="text-right">

                <strong>
                    {{ number_format($runningBalance,2) }}
                </strong>

            </td>

        </tr>

    </tbody>

</table>

<script>

    window.onload = function(){

        window.print();

    }

</script>

</body>
</html>