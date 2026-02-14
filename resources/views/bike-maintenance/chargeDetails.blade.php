<strong class="text-danger">Following Transactions will take place. Review Carefully</strong>
<table class="dataTable table mt-4" id="dataTableBuilder">
    <thead>
        <tr>
            <th style="text-align: left !important;">Account</th>
            <th>Description</th>
            <th>Dr</th>
            <th>Cr</th>
        </tr>
    </thead>
    <tbody>
        @if($data['total'] > 0)
            @if($data['rider_amount'] > 0)
                <tr>
                    <td style="text-align: left;">{{ $data['rider_account'] }}</td>
                    <td>{{ $data['description'] }}</td>
                    <td>{{ $data['rider_amount'] }}</td>
                    <td></td>
                </tr>
            @endif
            @if($data['company_amount'] > 0)
                <tr>
                    <td style="text-align: left;">{{ $data['company_account'] }}</td>
                    <td>{{ $data['description'] }}</td>
                    <td>{{ $data['company_amount'] }}</td>
                    <td></td>
                </tr>
            @endif
            <tr>
                <td style="text-align: left;">{{ $data['garage_account'] }}</td>
                <td>{{ $data['description'] }}</td>
                <td></td>
                <td>{{ $data['total'] }}</td>
            </tr>
            <tr style="font-weight: bold; border-top: 2px solid black !important; border-bottom: 2px solid black !important;">
                <td></td>
                <td>Total</td>
                <td>{{ $data['total'] }}</td>
                <td>{{ $data['total'] }}</td>
            </tr>
            @if($data['vat_amount'] > 0)
                <tr>
                    <td class="text-warning text-start">Below <b>VAT</b> transaction will also be recorded</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td style="text-align: left;">{{ $data['vat_account'] }}</td>
                    <td>{{ $data['description'] }}</td>
                    <td>{{ $data['vat_amount'] }}</td>
                    <td></td>
                </tr>
            @endif
        @endif
    </tbody>
</table>
@if(!$data['missing'])
    <div class="mt-4 text-end">
        <form method="POST" action="{{ route('bike-maintenance.chargeInvoice', $maintenance) }}" style="display: inline;" id="formajax">
            @csrf
            <label>Billing Month</label>
            <input type="month" name="billing_month"/>
             
            <button type="submit" class="btn btn-primary">
                Confirm
            </button>
        </form>
    </div>
@else
    <div class="mt-4">Encountered following Errors Cannot Charge Bill.</div>
    <ul>
        @foreach ($data['missing'] as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <div class="text-muted">Contact Admin</div>
@endif
