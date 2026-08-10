@php
$defaultPrincipal = number_format((float) $installment->principal_amount, 2, '.', '');
$defaultInterest = number_format((float) $installment->interest_amount, 2, '.', '');
$showLateCharges = $isOverdue || (float) $defaultLateCharges > 0;
$defaultTotal = number_format(
    (float) $installment->principal_amount + (float) $installment->interest_amount + ($showLateCharges ? (float) $defaultLateCharges : 0),
    2,
    '.',
    ''
);
$lateChargesNarrationDefault = 'Late payment charges — '.$defaultNarration;
@endphp
{!! Form::open(['route' => ['loanInstallments.pay', $installment->id], 'method' => 'post', 'id' => 'formajax']) !!}
<div class="card-body">

    <div class="row mb-3">
        <div class="form-group col-md-4">
            {!! Form::label('payment_date', 'Payment Date', ['class' => 'required']) !!}
            {!! Form::date('payment_date', $defaultDate, ['class' => 'form-control', 'id' => 'payment_date', 'required' => true]) !!}
        </div>
        <div class="form-group col-md-4">
            {!! Form::label('paying_bank_id', 'Paying Bank Account', ['class' => 'required']) !!}
            {!! Form::select('paying_bank_id', $bankAccountLabels, $defaultBankId, ['class' => 'form-select select2', 'id' => 'paying_bank_id', 'required' => true]) !!}
        </div>
    </div>

    <div class="form-group mb-3">
        {!! Form::label('narration', 'Default Narration (apply to all rows)') !!}
        {!! Form::textarea('narration', $defaultNarration, ['class' => 'form-control', 'id' => 'default_narration', 'rows' => 3]) !!}
        <small class="text-muted">Editing this updates all ledger row narrations below.</small>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Ledger Entries</h6>
        @if(! $isOverdue)
        <button type="button" class="btn btn-sm btn-outline-warning" id="add-late-charges-btn" @if($showLateCharges) style="display:none;" @endif>
            <i class="fa fa-plus me-1"></i> Add late payment charges
        </button>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0" id="loan-ledger-preview">
            <thead class="table-light text-center">
                <tr>
                    <th style="width:22%">Account</th>
                    <th style="width:38%">Narration</th>
                    <th style="width:15%">Dr</th>
                    <th style="width:15%">Cr</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="align-middle">{{ $loanPayableLabel }}</td>
                    <td>{!! Form::text('loan_payable_narration', $defaultNarration, ['class' => 'form-control form-control-sm ledger-narration']) !!}</td>
                    <td>{!! Form::number('principal_amount', $defaultPrincipal, ['class' => 'form-control form-control-sm text-end loan-dr-input', 'id' => 'principal_amount', 'step' => '0.01', 'min' => '0', 'required' => true]) !!}</td>
                    <td><input type="text" class="form-control form-control-sm text-end bg-light" value="0.00" readonly tabindex="-1"></td>
                </tr>
                <tr id="interest-ledger-row" @if((float) $installment->interest_amount <= 0) class="d-none" @endif>
                    <td class="align-middle">{{ $interestAccountLabel }}</td>
                    <td>{!! Form::text('interest_narration', $defaultNarration, ['class' => 'form-control form-control-sm ledger-narration']) !!}</td>
                    <td>{!! Form::number('interest_amount', $defaultInterest, ['class' => 'form-control form-control-sm text-end loan-dr-input', 'id' => 'interest_amount', 'step' => '0.01', 'min' => '0', 'required' => true]) !!}</td>
                    <td><input type="text" class="form-control form-control-sm text-end bg-light" value="0.00" readonly tabindex="-1"></td>
                </tr>
                <tr id="late-charges-ledger-row" @if(! $showLateCharges) class="d-none" @endif>
                    <td class="align-middle">{{ $lateChargesAccountLabel }}</td>
                    <td>{!! Form::text('late_charges_narration', $lateChargesNarrationDefault, ['class' => 'form-control form-control-sm ledger-narration', 'id' => 'late_charges_narration']) !!}</td>
                    <td>{!! Form::number('late_payment_charges', $showLateCharges ? $defaultLateCharges : '0.00', ['class' => 'form-control form-control-sm text-end loan-dr-input', 'id' => 'late_payment_charges', 'step' => '0.01', 'min' => '0']) !!}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <input type="text" class="form-control form-control-sm text-end bg-light" value="0.00" readonly tabindex="-1">
                            @if(! $isOverdue)
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="remove-late-charges-btn" title="Remove late charges">&times;</button>
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="align-middle" id="bank-account-label">{{ $bankAccountLabels[$defaultBankId] ?? 'Bank' }}</td>
                    <td>{!! Form::text('bank_narration', $defaultNarration, ['class' => 'form-control form-control-sm ledger-narration']) !!}</td>
                    <td><input type="text" class="form-control form-control-sm text-end bg-light" value="0.00" readonly tabindex="-1"></td>
                    <td>{!! Form::number('total_amount', $defaultTotal, ['class' => 'form-control form-control-sm text-end', 'id' => 'total_amount', 'step' => '0.01', 'min' => '0.01', 'required' => true]) !!}</td>
                </tr>
            </tbody>
            <tfoot class="table-light">
                <tr class="text-end fw-semibold">
                    <td colspan="2">Totals</td>
                    <td id="ledger-total-dr">{{ number_format((float) $defaultTotal, 2) }}</td>
                    <td id="ledger-total-cr">{{ number_format((float) $defaultTotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <small class="text-muted d-block mt-2">Voucher type: BL — Bank Loan. Total Dr must equal Total Cr.</small>
    @if($isOverdue)
    <small class="text-warning d-block mt-1"><i class="fa fa-exclamation-triangle me-1"></i>This installment is overdue — late payment charges row is included.</small>
    @endif
</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Post Payment', ['class' => 'btn btn-primary']) !!}
</div>
{!! Form::close() !!}

<script>
$(document).ready(function() {
    const bankLabels = @json($bankAccountLabels);
    const isOverdue = @json((bool) $isOverdue);
    const lateChargesNarrationTemplate = @json($lateChargesNarrationDefault);

    $('#paying_bank_id').select2({
        dropdownParent: $('#modalTopbody'),
        placeholder: 'Select paying bank',
        allowClear: false
    });

    function parseAmount(value) {
        const n = parseFloat(value);
        return isNaN(n) ? 0 : n;
    }

    function formatAmount(value) {
        return parseAmount(value).toFixed(2);
    }

    function lateChargesVisible() {
        return ! $('#late-charges-ledger-row').hasClass('d-none');
    }

    function syncTotals() {
        const principal = parseAmount($('#principal_amount').val());
        const interest = parseAmount($('#interest_amount').val());
        const lateCharges = lateChargesVisible() ? parseAmount($('#late_payment_charges').val()) : 0;
        const total = parseAmount($('#total_amount').val());
        const totalDr = principal + interest + lateCharges;

        if (interest > 0) {
            $('#interest-ledger-row').removeClass('d-none');
        } else {
            $('#interest-ledger-row').addClass('d-none');
        }

        $('#ledger-total-dr').text(formatAmount(totalDr));
        $('#ledger-total-cr').text(formatAmount(total));

        const mismatch = Math.abs(totalDr - total) > 0.01;
        $('#ledger-total-dr, #ledger-total-cr').toggleClass('text-danger', mismatch);
    }

    function syncDefaultNarration() {
        const text = $('#default_narration').val();
        $('.ledger-narration').not('#late_charges_narration').val(text);
        if (lateChargesVisible()) {
            $('#late_charges_narration').val('Late payment charges — ' + text);
        }
    }

    function syncBankLabel() {
        const bankId = $('#paying_bank_id').val();
        $('#bank-account-label').text(bankLabels[bankId] || 'Bank');
    }

    function showLateChargesRow() {
        $('#late-charges-ledger-row').removeClass('d-none');
        $('#add-late-charges-btn').hide();
        if (! $('#late_charges_narration').val()) {
            $('#late_charges_narration').val('Late payment charges — ' + $('#default_narration').val());
        }
        syncTotals();
    }

    function hideLateChargesRow() {
        if (isOverdue) return;
        $('#late-charges-ledger-row').addClass('d-none');
        $('#late_payment_charges').val('0.00');
        $('#add-late-charges-btn').show();
        const principal = parseAmount($('#principal_amount').val());
        const interest = parseAmount($('#interest_amount').val());
        $('#total_amount').val(formatAmount(principal + interest));
        syncTotals();
    }

    $('.loan-dr-input').on('input', function() {
        const principal = parseAmount($('#principal_amount').val());
        const interest = parseAmount($('#interest_amount').val());
        const lateCharges = lateChargesVisible() ? parseAmount($('#late_payment_charges').val()) : 0;
        $('#total_amount').val(formatAmount(principal + interest + lateCharges));
        syncTotals();
    });

    $('#total_amount').on('input', syncTotals);
    $('#default_narration').on('input', syncDefaultNarration);
    $('#paying_bank_id').on('change', syncBankLabel);
    $('#add-late-charges-btn').on('click', showLateChargesRow);
    $('#remove-late-charges-btn').on('click', hideLateChargesRow);

    syncTotals();
    syncBankLabel();
});
</script>
