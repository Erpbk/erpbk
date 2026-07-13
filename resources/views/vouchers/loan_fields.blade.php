<input type="hidden" name="payment_from" value="{{ ga_id('ADVANCE_LOAN') }}" />

@php
    $rider_account = $rider->account;
@endphp

{{-- Debit: rider liability account --}}
<div class="row">
    <div class="form-group col-md-3">
        <label>Rider Account</label>
        <input type="hidden" name="account_id[]" value="{{ $rider_account->id ?? '' }}" />
        <input type="text" class="form-control" value="{{ $rider_account->name ?? '' }}" disabled>
    </div>
    <div class="form-group col-md-4">
        <label>Narration</label>
        <textarea name="narration[]" class="form-control" rows="1" style="height: 40px !important;" required>Advance Loan Received</textarea>
    </div>
    <div class="form-group col-md-2">
        <label>Amount (Dr)</label>
        <input type="number" step="any" name="dr_amount[]" class="form-control dr_amount main_amount" placeholder="Loan Amount" required>
    </div>
</div>

{{-- Credit: bank/cash paying the loan --}}
<div class="row mb-3">
    <div class="form-group col-md-3">
        <label>Bank / Cash Account</label>
        {!! Form::select('account_id[]', $bank_accounts ?? \App\Models\Accounts::bankAccountsDropdown(), null, [
            'id' => 'al_bank_account',
            'class' => 'form-control form-select select2',
            'required' => true,
        ]) !!}
    </div>
    <div class="form-group col-md-4">
        <label>Narration</label>
        <textarea name="narration[]" class="form-control" rows="1" style="height: 40px !important;" required>Advance Loan Given to {{ $rider->name }}</textarea>
    </div>
    <div class="form-group col-md-2">
        <label>Amount (Cr)</label>
        <input type="number" step="any" name="cr_amount[]" class="form-control cr_amount" placeholder="Loan Amount" readonly required>
    </div>
</div>

<script>
    $(document).ready(function() {
        var $bankSelect = $('#al_bank_account');
        if ($bankSelect.length && $.fn.select2 && !$bankSelect.hasClass('select2-hidden-accessible')) {
            $bankSelect.select2({
                width: '100%',
                allowClear: true,
                dropdownParent: $('#modalTopbody').length ? $('#modalTopbody') : $(document.body)
            });
        }

        $('.main_amount').off('input.alSync change.alSync').on('input.alSync change.alSync', function() {
            $('.cr_amount').val($(this).val());
            if (typeof getTotal === 'function') {
                getTotal();
            }
        });

        $('#formajax').off('submit.alSync').on('submit.alSync', function() {
            $('.cr_amount').val($('.main_amount').val());
        });
    });
</script>
