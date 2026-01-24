<script src="{{ asset('js/modal_custom.js') }}"></script>
@php
if(isset($vouchers->voucher_type)){
$voucherType = $vouchers->voucher_type;
}else{
// Prefer explicitly provided $vt from parent include/view, fallback to request('vt')
$voucherType = isset($vt) ? $vt : request('vt');
}
@endphp
<input type="hidden" name="v_trans_code" value="{{@$vouchers->trans_code??0}}">
<input type="hidden" name="voucher_type" id="voucher_type" value="{{$voucherType}}">

<div class="row mt-0 mb-2">

    <div class="form-group col-md-3">
        <label for="exampleInputEmail1">Date</label>

        <input type="date" name="trans_date" class="form-control " placeholder="Transaction Date" value="@isset($vouchers->trans_date){{date('Y-m-d',strtotime($vouchers->trans_date)) }}@else{{date('Y-m-d')}}@endisset">
    </div>

    {{-- <div class="form-group col-md-3">
                <label for="exampleInputEmail1">Posting Date</label>
                <input  name="posting_date" class="form-control  date" placeholder="Posting Date" value="{{ date('Y-m-d') }}">
</div> --}}

@if(in_array($voucherType,['LV']))
<div class="form-group col-md-3">
    <label for="exampleInputEmail1">Bank/Cash A/C</label>
    {!! Form::select('payment_from',\App\Models\Accounts::dropdown(\App\Helpers\HeadAccount::BANK),null ,['class' => 'form-control select2 ','id'=>'payment_from']) !!}
</div>
@endif
{{-- @if($voucherType==9)
            <input type="hidden" name="payment_from" value="811" /><!--Sim Bike and Vendor Charges Account ID-->
            @endif
           --}}
@if($voucherType != 'AL')
<div class="form-group col-md-2">
    <label for="exampleInputEmail1">Payment Type</label>
    {!! Form::select('payment_type',App\Helpers\Account::payment_type_list(),null ,['class' => 'form-select form-select-sm select2 ','id'=>'payment_type']) !!}


</div>
@endif
<div class="form-group col-md-2">
    <label for="exampleInputEmail1">Billing Month</label>
    <input type="month" name="billing_month" class="form-control " required>
</div>
<div class="form-group col-md-2">
        <label for="reference_number">Reference Number</label>
        <input type="text" name="reference_number" class="form-control" id="reference_number" value="@isset($voucher->reference_number){{$voucher->reference_number}}@endisset" placeholder="Reference Number">
    </div>

</div>
<div class="scrollbar">

    @php
    $vtLabel = \App\Helpers\General::VoucherType($voucherType);
    if (is_array($vtLabel)) {
    // Try common keys; fallback to JSON string to avoid htmlspecialchars on array
    $vtLabel = $vtLabel['label'] ?? ($vtLabel['name'] ?? json_encode($vtLabel));
    }
    @endphp
    <h5>{{ $vtLabel }}</h5>

    @if($voucherType == 'JV' || $voucherType == 'RFV')
    @php($accounts = \App\Models\Accounts::dropdown(null))
    @include("vouchers.default_fields")
    @endif

    @if($voucherType == 'AL')
    @php($accounts = \App\Models\Accounts::dropdown(null))
    @include("vouchers.loan_fields", ['bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
    @endif

    @if($voucherType == 'COD')
    @php($accounts = \App\Models\Accounts::dropdown(null))
    @include("vouchers.cod_fields", ['bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
    @endif

    @if($voucherType == 'PENALTY')
    @php($accounts = \App\Models\Accounts::dropdown(null))
    @include("vouchers.penalty_fields", ['bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
    @endif

    @if($voucherType == 'VL')
    @php($accounts = \App\Models\Accounts::dropdown(null))
    @include("vouchers.visaloan_fields", ['bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
    @endif

    @if($voucherType == 'PAYMENT')
    @php($accounts = \App\Models\Accounts::dropdown(null))
    @include("vouchers.payment_fields", ['bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
    @endif

    {{-- @if($voucherType == 5)
            <h5>Invoice Voucher</h5>
            @include("vouchers.invoice_fields")
            @endif
            @if($voucherType == 9)
            <h5>Vendor Charges Voucher</h5>
            @include("vouchers.sim_fields")
            @endif
            @if(in_array($voucherType,[10,11,8,12,13,14,15,16]))

            @include("vouchers.default_fields")
            @endif --}}
</div>
{{-- @if($voucherType == 11)
            <h5>Fuel Voucher</h5>
            @include("vouchers.fuel_fields")
            @endif
            @if($voucherType == 8)
            <h5>RTA Fine Voucher</h5>
            @include("vouchers.rta_fields")
            @endif
            @if($voucherType == 12)
            <h5>Advance Issue Voucher</h5>
            @include("vouchers.default_fields")
            @endif
            @if($voucherType == 13)
            <h5>Advance Repay Voucher</h5>
            @include("vouchers.default_fields")
            @endif --}}


<div class="row">
    <div class="col-md-5"></div>
    <div class="col-md-2 content-right mt-1">Total:&nbsp;<a href="javascript:void(0);" onclick="getTotal();" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a></div>
    <div class="form-group col-md-2">
        <input type="number" class="form-control " id="total_dr" readonly placeholder="Total Dr">
    </div>
</div>

</div>

<script>
    /*  $(document).ready(function() {
  $('#datepicker').datepicker({
    format: "mm-yyyy", // Output format
    minView: 1, // Start with month view
    startView: 1, // Open in month view
  });
}); */






    $(document).ready(function() {
        var base_url = $('#base_url').val();
        getTotal();





        var counter = 0;
        $("#RID").on("change", function() {
            var id = $('#RID').val();
            var type = 5;

            $.get(base_url + '/get_invoice_balance?id=' + id + '&type=' + type).done(function(data) {
                $("#riderInvoiceBalance").val(data.invoice_balance);
                $("#riderBalance").val(data.balance);
                $("#inv_id").val(data.inv_id);
            });
        });

        $("#addRiderRow").on("click", function() {
            var item_id = $('#RID').val();
            var item_name = $('#RID option:selected').text();
            var item_price = $('#riderAmount').val();
            var narration = $('#narration').val();
            var inv_id = $('#inv_id').val();
            var invoice = '';
            if (item_price != '' && item_id != '') {

                var newRow = '<tr style="padding:5px;"><td width="200"><label>' + item_name + '</label><input type="hidden" name="id[]" value="' + item_id + '" /><input type="hidden" name="inv_id[]" value="' + inv_id + '" /></td>';
                newRow += '<td width="200"><input type="text" name="narration[]"  value="' + narration + '" class="form-control " /></td>';
                newRow += '<td width="100"><input type="number" step="any" name="amount[]"  value="' + item_price + '" step="any" class="form-control  amount" onchange="getTotal();" /></td>';
                newRow += '<td width="100"><input type="button" class="ibtnDel btn btn-md btn-xs btn-danger "  value="Delete"></td></tr>';

                $("table.order-list").append(newRow);
                $('#riderAmount').val('');
                $('#narration').val('');
                $('#RID option:selected').text('select');
                $('#RID').val(0);
                $('#riderBalance').val('');
                counter++;

            } else {
                alert("Select Rider and Amount");
            }
            getTotal();
        });


        $("table.order-list").on("click", ".ibtnDel", function(event) {
            $(this).closest("tr").remove();
            counter -= 1;
            getTotal();
        });
    });

    $(".cr_amount").on("focus keyup change", function() {
        getTotal();
    });
    $(".dr_amount").on("focus keyup change", function() {
        getTotal();
    });
    $(".amount").on("focus keyup change", function() {
        getTotal();
    });

    function getTotal() {
        var cr_sum = 0;
        var dr_sum = 0;
        //iterate through each textboxes and add the values
        $(".cr_amount").each(function() {
            //add only if the value is number
            if (!isNaN(this.value) && this.value.length != 0) {
                cr_sum += parseFloat(this.value);
            }
        });
        //iterate through each textboxes and add the values
        $(".dr_amount").each(function() {
            //add only if the value is number
            if (!isNaN(this.value) && this.value.length != 0) {
                dr_sum += parseFloat(this.value);
            }
        });
        $(".amount").each(function() {
            //add only if the value is number
            if (!isNaN(this.value) && this.value.length != 0) {
                cr_sum += parseFloat(this.value);
            }
        });
        //.toFixed() method will roundoff the final sum to 2 decimal places
        $("#total_cr").val(cr_sum.toFixed(2));
        $("#total_dr").val(dr_sum.toFixed(2));
    }

    $(document).on("change", "#invoice_voucher_type", function() {
        let thisVal = $(this).val();

        if (thisVal == 5) {
            $("#vendor_section").hide();
            $("#rider_section").show();
            $("table.order-list").html('');
        } else {
            $("#vendor_section").show();
            $("#rider_section").hide();
            $("table.order-list").html('');
        }
        getTotal();
    });

    function fetch_invoices(g) {
        let id = g;
        var vt = $("#invoice_voucher_type").val();
        $.ajax({
            url: "{{ url('fetch_invoices') }}/" + id + '/' + vt,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                $("table.order-list").html(data.htmlData);
                $("#riderBalance").val(data.rider_balance);
                $("#vendor_balance").val(data.vendor_balance);

            }
        });
    }
</script>