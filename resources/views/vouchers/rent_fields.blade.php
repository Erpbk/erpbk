<div class="bg-light" style="border-radius:5px;padding:10px;">

<div class="row" >
<div class="form-group col-md-2">
    <label for="exampleInputEmail1">Select Bike</label>
    <select name="ref_id" class="form-control form-control-sm select2">
        <option value="">Select</option>
        {!! \App\Models\Bike::dropdown(@$vouchers->ref_id) !!}
    </select>
</div>
<div class="form-group col-md-3">
    <label>Lease Company</label>
    <select name="lease_company" class="form-control form-control-sm select2">
        <option value="">Select Company</option>
        {!! \App\Models\LeaseCompany::dropdown(@$vouchers->lease_company) !!}
    </select>
</div>
<div class="form-group col-md-3">
    <label>Vendor</label>
    <select name="vendor_id" class="form-control form-control-sm select2">
        <option value="">Select Vendor</option>
        {!! \App\Models\Vendor::dropdown(@$vouchers->vendor_id) !!}
    </select>
</div>

{{--  <div class="form-group col-md-4">
    <label for="exampleInputEmail1">Company</label>
    <select name="CID" class="form-control form-control-sm select2" onchange="fetch_invoices(this.value)">
        <option value="19">Etislat </option>
        <option value="20">Duo </option>
    </select>
</div> --}}
{{-- <div class="form-group col-md-4">
    <label>Narration</label>
    <textarea name="sim_narration" id="narration" class="form-control form-control-sm narration" rows="10" placeholder="Narration" style="height: 40px !important;"></textarea>
</div>
<div class="form-group col-md-4">
    <label>Charges Amount</label>
    <input type="text" name="amount" placeholder="0.00" class="form-control form-control-sm dr_amount">
</div> --}}
</div>

@isset($data)
<div id="fetchRiderInv" class="bg-white">
   
    <div class="row ">
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
                    <input type="number" step="any" name="amount[]" value="{{$entry->amount}}" class="form-control form-control-sm" placeholder="Paid Amount">
                </div>
                @endif
                @if($entry->dr_cr == 2)
                <div class="form-group col-md-2">
                </div>
                <div class="form-group col-md-2">
                    <input type="number" step="any" name="amount[]" value="{{$entry->amount}}" class="form-control form-control-sm" placeholder="Paid Amount">
                </div>
                @endif
            </div>
    @endforeach
   
    </div>
@else
<div class="row" >
    <div class="form-group col-md-4">
        <label for="exampleInputEmail1">Select Rider</label>
        <select name="RID" class="form-control form-control-sm select2" id="RID" >
            <option value="">Select</option>
            {!! \App\Models\Rider::dropdown(@$vouchers->payment_to) !!}
        </select>
    </div>

    <div class="form-group col-md-4">
        <label>Narration</label>
        <textarea name="narration" id="narration" class="form-control form-control-sm narration" rows="10" placeholder="Narration" style="height: 40px !important;"></textarea>
    </div>
    <div class="form-group col-md-2">
        <label>Amount</label>
        <input type="text" name="amount" class="form-control form-control-sm" id="riderAmount" placeholder="Amount" step="any" onchange="getTotal();">
    </div>
    <div class="form-group col-md-2" style="padding-top: 21px;float:right;">
        <button type="button" class="btn btn-success btn-sm" id="addRiderRow" ><i class="fa fa-plus"></i> Add Row</button> 
    </div>
    <div id="rider_invoices"></div>

</div>
</div>
<table id="myTable" class="table order-list">
</table>

@endisset