<div class="row">
<div class="form-group col-md-2">
    <label for="exampleInputEmail1">Voucher Type</label>
    <select name="invoice_voucher_type" class="form-control form-control-sm pt" id="invoice_voucher_type" @isset($vouchers) disabled @endisset>
        <option value="">Select</option>
        <option value="5" @if(@$vouchers->invoice_voucher_type == 5) selected @endif>Rider PV</option>
        <option value="7" @if(@$vouchers->invoice_voucher_type == 7) selected @endif>Vendor PV</option>
    </select>
</div>
<div class="form-group col-md-2">
    <label for="exampleInputEmail1">Select Reason</label>
    <select name="reason" class="form-control form-control-sm select2" >
        <option value="">Select</option>
        {!! App\Helpers\CommonHelper::get_PaymentReason(@$vouchers->reason) !!}
    </select>
</div>
</div>
<div class="row" id="vendor_section" style="display: none">
<div class="form-group col-md-4">
    <label for="exampleInputEmail1">Select Vendor</label>
    <select name="VID" class="form-control form-control-sm select2" onchange="fetch_invoices(this.value)" @isset($vouchers) disabled @endisset>
        <option value="">Select</option>
        {!! \App\Models\Vendor::dropdown(@$vouchers->payment_to) !!}
    </select>
</div>
<div class="form-group col-md-2">
    <label>Balance</label>
    <input type="text" name="" class="form-control form-control-sm" id="vendor_balance" readonly placeholder="Balance Amount">
</div>
<div id="rider_invoices"></div>
</div>

<!--row-->
<div class="row bg-light" id="rider_section" style="display: none">
<div class="form-group col-md-4">
    <label for="exampleInputEmail1">Select Rider</label>
    <select name="RID" class="form-control form-control-sm select2"id="RID" onchange="fetch_invoices(this.value)" @isset($vouchers) disabled @endisset >
        <option value="">Select</option>
        {!! \App\Models\Rider::dropdown(@$vouchers->payment_to) !!}
    </select>
</div>
<div class="form-group col-md-2">
    <label>Rider Balance</label>
    <input type="text" name="riderBalance" class="form-control form-control-sm" id="riderBalance" readonly placeholder="Balance Amount">
</div>
{{-- <div class="form-group col-md-2">
    <label>Invoice Balance</label>
    <input type="hidden" name="inv_id" id="inv_id" value="" />
    <input type="text" name="riderInvoiceBalance" class="form-control form-control-sm" id="riderInvoiceBalance" readonly placeholder="Invoice Amount">
</div>
<div class="form-group col-md-2">
    <label>Narration</label>
    <textarea name="narration" id="narration" class="form-control form-control-sm narration" rows="10" placeholder="Narration" style="height: 40px !important;"></textarea>
</div>
<div class="form-group col-md-2">
    <label>Amount</label>
    <input type="text" name="amount" class="form-control form-control-sm" id="riderAmount" placeholder="Amount" step="any">
</div>
<div class="form-group col-md-1" style="padding-top: 21px;float:right;">
        <button type="button" class="btn btn-success btn-sm" id="addRiderRow" ><i class="fa fa-plus"></i> Add</button>
</div>--}}
<div id="rider_invoices"></div>

</div>
<table id="myTable" class="table order-list">
</table>
<table id="sumTable" class="table sum-row">
<tr>
    <td width="750">&nbsp;</td>
    <td id="TotalAmount"></td>
</tr>
</table>
<!--row-->
<div id="fetchRiderInv">
@isset($data)
<div class="row">
    <div class="col-md-4">
        Payment Account
    </div>
    <div class="col-md-4">
        Narration
    </div>
    <div class="col-md-2">
        Debit
    </div>
    <div class="col-md-2">
        Credit
    </div>
</div>
@foreach($data as $entry)

        <div class="row">
            <input type="hidden" name="id[]" value="{{$entry->trans_acc->riderDetail->id}}">
            <div class="form-group col-md-4">
                <br>
                @if($entry->trans_acc->riderDetail)
                   {{ @$entry->trans_acc->riderDetail->name.' ('.@$entry->trans_acc->riderDetail->rider_id.')'}}
                @else
                {{@$entry->trans_acc->Trans_Acc_Name}}
                @endif
            </div>
            <div class="form-group col-md-4">
                <textarea name="narration[]" class="form-control form-control-sm narration" rows="10" placeholder="Narration" style="height: 40px !important;">{{$entry->narration}}</textarea>
            </div>
           @if($entry->dr_cr == 1)
            <div class="form-group col-md-2">
                <input type="number" step="any" name="amount[]" value="{{$entry->amount}}" class="form-control form-control-sm" placeholder="Paid Amount" onchange="getTotal();">
            </div>
            @endif
            @if($entry->dr_cr == 2)
            <div class="form-group col-md-2">
            </div>
            <div class="form-group col-md-2">
                <input type="number" step="any" name="amount[]" value="{{$entry->amount}}" class="form-control form-control-sm" placeholder="Paid Amount" onchange="getTotal();">
            </div>
            @endif
        </div>
@endforeach
@endisset
</div>
