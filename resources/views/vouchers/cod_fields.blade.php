<input type="hidden" name="payment_from" value="{{ ga_id('COD_PARENT') }}" />
<input type="hidden" name="voucher_type" value="COD" />

<div class="row mt-0 mb-2">
    <div class="form-group col-md-3">
        <label>Date</label>
        <input type="date" name="trans_date" class="form-control" value="{{ date('Y-m-d') }}" required>
    </div>
    <div class="form-group col-md-2">
        <label>Billing Month</label>
        <input type="month" name="billing_month" class="form-control" value="{{ date('Y-m') }}" required>
    </div>
    <div class="form-group col-md-2">
        <label>Reference Number</label>
        <input type="text" name="reference_number" class="form-control" placeholder="Reference Number">
    </div>
    @include('vouchers._branch_field')
</div>

<h5>COD Voucher</h5>

@php
$rider_account = $rider->account ?? null;
$codAccounts = \App\Models\Accounts::where('parent_id', ga_id('COD_PARENT'))
->orderBy('name')
->pluck('name', 'id')
->toArray();
@endphp

{{-- Debit: rider account --}}
<div class="row">
    <div class="form-group col-md-3">
        <label>Rider Account</label>
        <input type="hidden" name="account_id[]" value="{{ $rider_account->id ?? '' }}" />
        <input type="text" class="form-control" value="{{ $rider_account->name ?? '' }}" disabled>
    </div>
    <div class="form-group col-md-4">
        <label>Narration</label>
        <textarea name="narration[]" class="form-control" rows="1" style="height: 40px !important;" required>COD Amount Received</textarea>
    </div>
    <div class="form-group col-md-2">
        <label>Amount (Dr)</label>
        <input type="number" step="any" name="dr_amount[]" class="form-control dr_amount main_amount" placeholder="COD Amount" required>
    </div>
</div>

{{-- Credit: COD account --}}
<div class="row mb-3">
    <div class="form-group col-md-3">
        <label>COD Account</label>
        {!! Form::select('account_id[]', $codAccounts, null, [
        'id' => 'cod_credit_account',
        'class' => 'form-control form-select select2',
        'required' => true,
        'placeholder' => 'Select Account',
        ]) !!}
    </div>
    <div class="form-group col-md-4">
        <label>Narration</label>
        <textarea name="narration[]" class="form-control" rows="1" style="height: 40px !important;" required>COD Amount Given to {{ $rider->name ?? 'Rider' }}</textarea>
    </div>
    <div class="form-group col-md-2">
        <label>Amount (Cr)</label>
        <input type="number" step="any" name="cr_amount[]" class="form-control cr_amount" placeholder="COD Amount" readonly required>
    </div>
</div>

<div class="row">
    <div class="col-md-5"></div>
    <div class="col-md-2 content-right mt-1">
        Total:&nbsp;<a href="javascript:void(0);" onclick="getTotal();" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a>
    </div>
    <div class="form-group col-md-2">
        <input type="number" class="form-control" id="total_dr" readonly placeholder="Total Dr">
    </div>
</div>

<script>
    window.getTotal = function getTotal() {
        var dr_sum = 0;
        $('.dr_amount').each(function() {
            if (!isNaN(this.value) && this.value.length !== 0) {
                dr_sum += parseFloat(this.value);
            }
        });
        $('#total_dr').val(dr_sum.toFixed(2));
    };

    $(document).ready(function() {
        var $codSelect = $('#cod_credit_account');
        if ($codSelect.length && $.fn.select2 && !$codSelect.hasClass('select2-hidden-accessible')) {
            $codSelect.select2({
                width: '100%',
                allowClear: true,
                placeholder: 'Select Account',
                dropdownParent: $('#modalTopbody').length ? $('#modalTopbody') : $(document.body)
            });
        }

        $('.main_amount').off('input.codSync change.codSync').on('input.codSync change.codSync', function() {
            $('.cr_amount').val($(this).val());
            getTotal();
        });

        $('#formajax').off('submit.codSync').on('submit.codSync', function() {
            $('.cr_amount').val($('.main_amount').val());
        });

        getTotal();
    });
</script>