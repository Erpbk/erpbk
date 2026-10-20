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
            .voucher-posted-transactions,
            .no-print {
                display: none !important;
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
        @php $vf = static fn (string $f): bool => field_visible('voucher', $f); @endphp
        <table width="100%" style="font-family: sans-serif; margin-top: 20px;font-size: 12px">
            <tr>
                <td style="padding: 3px;width: 65%;text-align: left;"><strong>Voucher No</strong>: {{ $voucher->voucher_type . '-' . str_pad($voucher->id, '4', '0', STR_PAD_LEFT) }}</td>
                @if($vf('trans_date'))
                <th style="padding: 3px;width: 15%;text-align: left;">Voucher Date:</th>
                <td style="padding: 3px;width: 20%;text-align: left;">{{ $voucher->trans_date }}</td>
                @endif
            </tr>
            <tr>
                <td style="padding: 3px;width: 65%;text-align: left;"><strong>Voucher Type</strong>:{{$voucher_type}}</td>
                @if($vf('billing_month') && isset($voucher->billing_month))
                <th style="padding: 3px;width: 15%;text-align: left;"> Billing Month: </th>
                <td style="padding: 3px;width: 20%;text-align: left;">{{date('M-Y',strtotime($voucher->billing_month))}}</td>
                @endif
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
        {{-- Posted transactions: screen view only (excluded from print / PDF download) --}}
        @unless(request()->boolean('print') || request()->get('print'))
            @php
                $fin_detail = $voucher->voucher_type === 'RFV'
                    ? company_table('rta_fines')->where('id', $voucher->ref_id)->first()
                    : null;
            @endphp
            @include('vouchers.partials.posted_transactions', [
                'transactions' => $voucher->transactions,
                'voucherType' => $voucher->voucher_type,
                'finDetail' => $fin_detail,
                'showPostedTransactions' => true,
            ])
        @endunless
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