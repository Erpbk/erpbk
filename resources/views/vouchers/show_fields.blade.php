<!doctype html>
<html style="height: 100%;box-sizing: border-box;">

<head>
    @php

    $voucher_type = App\Helpers\General::VoucherType($voucher->voucher_type);
    $i=0;
    @endphp
    <meta charset="utf-8">
    <title>{{$voucher_type}} # {{$voucher->voucher_type.'-'.str_pad($voucher->id,4,"0",STR_PAD_LEFT)}}</title>
    <style>
        .page-footer,
        .page-footer-space {
            /*height: 39px;*/
        }

        .page-footer {
            position: relative;
            bottom: 0;
            width: 100%;
            left: 0;
        }

        .headerDiv {
            position: relative;
            width: 33.33%;
            float: left;
            min-height: 1px;
        }

        #btns {
            position: relative;
            bottom: 20px;
        }

        /*.footer{
            position: absolute;bottom: 0;height: 39px;
        }*/
        .pcontainer {
            position: relative;
            height: 100%;
        }

        hr {
            margin-bottom: 2px;
            margin-top: 2px;
        }

        @media print {
            #btns {
                display: none;
            }

            @page {
                margin: 0 0.10cm;
                margin-top: 10px;
            }

            html,
            body {
                padding: 20px;
                margin: 0;
            }

            #pnumber:after {
                counter-increment: page;
                content: "Page " counter(page);
            }

            .page-footer {
                position: absolute;
            }

        }

        @media print {
            .print-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                font-size: 12px;
                border-top: 1px solid #000;
                padding-top: 5px;
                display: flex;
                justify-content: space-between;
            }
        }
    </style>
</head>


<body style="">
    <div style="position: relative;min-height: 100%;height: 100%;">
        @include('_partials.header')
        @isset($voucher)
        <table width="100%" style="font-family: sans-serif; margin-top: 20px;font-size: 12px">
            <tr>
                <td style="padding: 3px;width: 65%;text-align: left;"><strong>Voucher No</strong>: {{ $voucher->voucher_type . '-' . str_pad($voucher->id, '4', '0', STR_PAD_LEFT) }}</td>
                <th style="padding: 3px;width: 15%;text-align: left;">Voucher Date:</th>
                <td style="padding: 3px;width: 20%;text-align: left;">{{ $voucher->trans_date }}</td>
            </tr>
            <tr>
                <td style="padding: 3px;width: 65%;text-align: left;"><strong>Voucher Type</strong>:{{$voucher_type}}</td>
                @isset($voucher->billing_month)
                <th style="padding: 3px;width: 15%;text-align: left;"> Billing Month: </th>
                <td style="padding: 3px;width: 20%;text-align: left;">{{date('M-Y',strtotime($voucher->billing_month))}}</td>
                @endisset
            </tr>
            <tr>
                <td style="padding: 3px;width: 65%;text-align: left;"><strong>Created By</strong>: {{ Auth::user()->where('id', $voucher->Created_By)->first()->name ?? 'N/A' }}</td>
                <th style="padding: 3px;width: 15%;text-align: left;">Creation Date:</th>
                <td style="padding: 3px;width: 20%;text-align: left;">{{ Illuminate\Support\Carbon::parse($voucher->created_at)->format('d-M-Y ') }}</td>
            </tr>
            <tr>
                <td style="padding: 3px;width: 65%;text-align: left;">&nbsp;</td>
            </tr>
        </table>
        <table style="width: 100%; font-family: sans-serif;text-align: left;border: 1px solid #000; border-collapse: collapse; margin-top: 20px;font-size: 12px;">
            <thead>
                <tr style="border: 1px solid #000;">
                    <th style="border: 1px solid #000; padding: 10px;width: 20px;">Sr</th>
                    <th style="border: 1px solid #000; padding: 10px;">Account Name</th>
                    <th style="border: 1px solid #000; padding: 10px;">Particulars</th>
                    <th style="border: 1px solid #000; padding: 10px;text-align: center;">Debit</th>
                    <th style="border: 1px solid #000; padding: 10px;text-align: center;">Credit</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalD = 0;
                $totalC = 0;
                $fin_detail = DB::Table('rta_fines')->where('id' , $voucher->ref_id)->first();
                @endphp
                @foreach($voucher->transactions as $item)
                <tr>
                    <td style="padding: 5px;border:1px solid;text-align:center;">{{ $i+=1 }}</td>
                    <td style="padding: 5px;border:1px solid">
                        {{@$item->account->account_code}}-{{@$item->account->name}}
                    </td>
                    @if($voucher->voucher_type == 'RFV')
                    <td style="padding: 5px;border:1px solid;text-align: left">{{ $item->narration }}
                        <b>Ticket No:</b>{{$fin_detail->ticket_no ?? ''}},<b>Bike No:</b>{{ $fin_detail->plate_no ?? '' }}, @if ($fin_detail && $fin_detail->trip_date) {{ \Carbon\Carbon::parse($fin_detail->trip_date)->format('d M Y') }} @else N/A @endif
                    </td>
                    @else
                    <td style="padding: 5px;border:1px solid;text-align: left">{{ $item->narration }}</td>
                    @endif
                    <td style="padding:5px;border:1px solid;text-align: center;">{{ $item->debit }}</td>
                    <td style="padding:5px;border:1px solid;text-align: center;">{{ $item->credit }}</td>
                </tr>
                @php
                $totalD+=$item->debit;
                $totalC+=$item->credit;
                @endphp
                @endforeach
            </tbody>
            <tfoot>
                <tr style="border-top: 1px solid #000;">
                    <td colspan="2" style="padding: 10px;text-align: center;"></td>
                    <th style="padding: 10px;text-align: right;">Sub Total:</th>
                    <th style="padding: 10px;text-align: center;">{{ \App\Helpers\Account::show_bal_format($totalD) }}</th>
                    <th style="padding: 10px;text-align: center;">{{ \App\Helpers\Account::show_bal_format($totalC) }}</th>
                </tr>
                <tr style="border-top: 1px solid #000; background-color: #dfdfdf;">
                    <td colspan="2" style="padding: 10px;text-align: center;"></td>
                    <th style="padding: 10px;text-align: right;">Total:</th>
                    <th style="padding: 10px;text-align: center;">AED{{ \App\Helpers\Account::show_bal_format($totalD) }}</th>
                    <th style="padding: 10px;text-align: center;">AED{{ \App\Helpers\Account::show_bal_format($totalC) }}</th>
                </tr>
            </tfoot>
        </table>
        <div id="btns" style="margin-top: 50px">
            <button class="btn btn-sm btn-outline-danger" type="button" onClick="window.print()"><i class="fa fa-file-pdf-o"></i> Print</button>
        </div>
        @else
        <div class="text-danger">No Voucher found</div>
        @endisset
        <div class="print-footer">
            <span class="left">Printed date: {{ now()->format('d-M-Y') }}</span>
            <span class="right">Printed by: {{ auth()->user()->name ?? 'System' }}</span>
        </div>


    </div>
    @if(request()->get('print'))
    <script>window.onload = function() { window.print(); };</script>
    @endif
</body>

</html>