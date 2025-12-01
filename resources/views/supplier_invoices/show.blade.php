<!doctype html>
<html style="height: 100%;box-sizing: border-box;">
<head>
    <meta charset="utf-8">
    <title>SupplierID: {{$supplierInvoice->supplier->id}} Month: {{date('M-Y',strtotime($supplierInvoice->billing_month))}}</title>
    <style>
        .page-footer, .page-footer-space {}
        .page-footer { position: relative; bottom: 0; width: 100%; left: 0; }
        .headerDiv { position: relative; width: 33.33%; float: left; min-height: 1px; }
        #btns { position: relative; bottom: 20px; }
        .pcontainer { position: relative; height: 100%; }
        hr { margin-bottom: 2px; margin-top: 2px; }

        @media print {
            #btns { display: none; }
            @page { margin: 0 0.10cm; margin-top: 10px; }
            html, body { padding: 20px; margin: 0; }
            #pnumber:after {
                counter-increment: page;
                content: "Page " counter(page);
            }
            .page-footer { position: absolute; }
        }
    </style>
</head>

<body>
<div style="position: relative; min-height: 100%; height: 100%;">
    @include('_partials.header')

    <table width="100%" style="font-family: sans-serif; font-size: 10px;">
        <tr>
            <td>
                <table style="text-align: left;">
                    <tr>
                        <th>Invoice Type:</th>
                        <td>Supplier Invoice</td>
                    </tr>
                    <tr>
                        <th>Invoice #:</th>
                        <td>{{ $supplierInvoice->inv_id }}</td>
                    </tr>
                    <tr>
                        <th>Invoice Date:</th>
                        <td>{{ $supplierInvoice->created_at->format("Y-m-d h:i A") }}</td>
                    </tr>
                    <tr>
                        <th>Billing Month:</th>
                        <td>{{ date('M-Y', strtotime($supplierInvoice->billing_month)) }}</td>
                    </tr>
                </table>
            </td>

        </tr>

        <tr>
            <td colspan="2" style="text-align: center; border-top: 1px solid #000;">
                <b>Supplier Detail</b>
            </td>
        </tr>

        <tr>
            <td>
                <table style="text-align: left;">
                    <tr>
                        <th>Supplier ID:</th>
                        <td>{{ $supplierInvoice->supplier->id }}</td>
                    </tr>
                    <tr>
                        <th>Supplier Name:</th>
                        <td>{{ $supplierInvoice->supplier->name }}</td>
                    </tr>
                    <tr>
                        <th>Company Name:</th>
                        <td>{{ $supplierInvoice->supplier->company_name }}</td>
                    </tr>
                    <tr>
                        <th>Contact:</th>
                        <td>{{ $supplierInvoice->supplier->phone }}</td>
                    </tr>
                    <tr>
                        <th>Description:</th>
                        <td>{{ $supplierInvoice->descriptions }}</td>
                    </tr>
                </table>
            </td>
           
        </tr>
    </table>

    <table style="width: 100%; font-family: sans-serif; text-align: center; border: 1px solid #000; border-collapse: collapse; margin-top: 5px; font-size: 10px;">
        <thead>
            <tr>
                <th>#</th>
                <th style="border: 1px solid #000; padding: 5px;">Item Description</th>
                <th style="border: 1px solid #000; padding: 5px;">Qty</th>
                <th style="border: 1px solid #000; padding: 5px;">Rate</th>
                <th style="border: 1px solid #000; padding: 5px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; $total_qty = 0; @endphp
            @foreach($supplierInvoice->items as $key => $val)
                @php
                    $total += $val->amount;
                    $total_qty += $val->qty;
                @endphp
                <tr>
                    <td style="padding: 5px; border: 1px solid;">{{ $key + 1 }}</td>
                    <td style="padding: 5px; border: 1px solid; text-align: left;">
                        {{ $val->supplierInv_item }}
                        {{ \App\Models\Items::where('id', $val->item_id)->value('name') }}
                    </td>
                    <td style="padding: 5px; border: 1px solid; text-align: center;">{{ $val->qty }}</td>
                    <td style="padding: 5px; border: 1px solid;">{{ $val->rate }}</td>
                    <td style="padding: 5px; border: 1px solid; text-align: right;">{{ $val->amount }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-top: 1px solid #000;">
                <td colspan="2" style="padding: 5px; text-align: right; font-weight: bold;">Total Orders:</td>
                <td style="padding: 5px; text-align: center; font-weight: bold;">{{ $total_qty }}</td>
                <td style="padding: 5px; text-align: right; font-weight: bold;">Sub Total:</td>
                <td style="padding: 5px; text-align: right; font-weight: bold;">{{ \App\Helpers\Account::show_bal_format($total) }}</td>
            </tr>
        </tfoot>
    </table>

    <table style="width: 100%; font-family: sans-serif; text-align: center; border: 1px solid #000; border-collapse: collapse; font-size: 10px; border-top: 0;">
        <tr>
            <td style="width: 75%; text-align: left; padding: 5px;">
                <b>Notes</b><br />
                {{ $supplierInvoice->notes }}
            </td>
            <th style="padding: 5px; text-align: right;">Total:</th>
            <th style="padding: 5px; text-align: right;">AED {{ \App\Helpers\Account::show_bal_format($total) }}</th>
        </tr>
    </table>

</div>
</body>
</html>
