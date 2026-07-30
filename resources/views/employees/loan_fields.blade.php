<script src="{{ asset('js/modal_custom.js') }}"></script>
@php
if(isset($vouchers->voucher_type)){
$voucherType = $vouchers->voucher_type;
}else{
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

@if(in_array($voucherType,['LV']))
<div class="form-group col-md-3">
    <label for="exampleInputEmail1">Bank/Cash A/C</label>
    {!! Form::select('payment_from',\App\Models\Accounts::dropdown(ga_id('BANK')),null ,['class' => 'form-control select2 ','id'=>'payment_from']) !!}
</div>
@endif
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
@include('vouchers._branch_field')

</div>
<div class="scrollbar">

    @php
    $vtLabel = \App\Helpers\General::VoucherType($voucherType);
    if (is_array($vtLabel)) {
    $vtLabel = $vtLabel['label'] ?? ($vtLabel['name'] ?? json_encode($vtLabel));
    }
    @endphp
    <h5>{{ $vtLabel }}</h5>

    @if($voucherType == 'AL')
    @php($accounts = \App\Models\Accounts::dropdown(null))
    @include("vouchers.loan_fields", ['employee' => $employee, 'bank_accounts' => $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown()])
    @endif

</div>

<div class="row">
    <div class="col-md-5"></div>
    <div class="col-md-2 content-right mt-1">Total:&nbsp;<a href="javascript:void(0);" onclick="getTotal();" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a></div>
    <div class="form-group col-md-2">
        <input type="number" class="form-control " id="total_dr" readonly placeholder="Total Dr">
    </div>
</div>

</div>

<script>
    $(document).ready(function() {
        getTotal();

        $(".cr_amount").on("focus keyup change", function() {
            getTotal();
        });
        $(".dr_amount").on("focus keyup change", function() {
            getTotal();
        });
        $(".amount").on("focus keyup change", function() {
            getTotal();
        });
    });

    function getTotal() {
        var cr_sum = 0;
        var dr_sum = 0;
        $(".cr_amount").each(function() {
            if (!isNaN(this.value) && this.value.length != 0) {
                cr_sum += parseFloat(this.value);
            }
        });
        $(".dr_amount").each(function() {
            if (!isNaN(this.value) && this.value.length != 0) {
                dr_sum += parseFloat(this.value);
            }
        });
        $(".amount").each(function() {
            if (!isNaN(this.value) && this.value.length != 0) {
                cr_sum += parseFloat(this.value);
            }
        });
        $("#total_cr").val(cr_sum.toFixed(2));
        $("#total_dr").val(dr_sum.toFixed(2));
    }
</script>
