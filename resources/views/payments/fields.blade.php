<div class="row mb-2">
    <!-- reference Field -->
    <div class="form-group col-md-2">
        {!! Form::label('reference', 'Reference:') !!}
        {!! Form::text('reference', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
    </div>

    <!-- Payment Type Field -->
    <div class="form-group col-md-2">
        {!! Form::label('amount_type', 'Amount Type:') !!}
        {!! Form::select('amount_type',
        ['' => 'Select', 'Cash' => 'Cash', 'Online' => 'Online', 'Cheque' => 'Cheque', 'Credit' => 'Credit'],
        old('amount_type', isset($payment) ? $payment->amount_type : ''),
        ['class' => 'form-control select2']
        ) !!}
    </div>

    <!-- Voucher Attachment Field -->
    <div class="form-group col-md-2">
        {!! Form::label('attachment', 'Attachment:') !!}
        {!! Form::file('attachment', ['class' => 'form-control']) !!}
    </div>

    <!-- Date of Payment Field -->
    <div class="form-group col-md-2">
        {!! Form::label('date_of_payment', 'Date of Payment:') !!}
        {!! Form::date('date_of_payment', null, ['class' => 'form-control']) !!}
    </div>

    @if(!request()->has('leasing_company_id') && !(isset($payment) && $payment->leasing_company_id))
    <div class="form-group col-md-2">
        {!! Form::label('date_of_invoice', 'Date of Invoice:') !!}
        {!! Form::date('date_of_invoice', null, ['class' => 'form-control']) !!}
    </div>
    @endif

    <!-- Billing Month Field -->
    <div class="form-group col-md-2">
        {!! Form::label('billing_month', 'Billing Month:') !!}
        {!! Form::month('billing_month', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
    </div>


</div>

<div class="scrollbar">
    @if(!isset($transactions))
    <div id="row-container">
        <h5 class="my-1">Payment Voucher</h5>
        <div class="row">
            @if(request()->has('leasing_company_id') || (isset($payment) && $payment->leasing_company_id))
            @php
            $leasingCompany = \App\Models\LeasingCompanies::find(request('leasing_company_id') ?? $payment->leasing_company_id);
            @endphp
            <div class="form-group col-md-3">
                {!! Form::label('leasing_company', 'Leasing Company:') !!}
                {!! Form::hidden('leasing_company_id', $leasingCompany->id ?? '')!!}
                {!! Form::text('leasing-company-name', $leasingCompany->name ?? '-', ['class' => 'form-control', 'readonly' => true]) !!}
            </div>
            @else
            <div class="form-group col-md-3">
                {!! Form::label('bank', 'Account:') !!}
                {!! Form::hidden('bank_id', $bank->id ?? '')!!}
                {!! Form::text('bank-name', $bank->account->account_code.'-'.$bank->account->name, ['class' => 'form-control', 'readonly' => true]) !!}
            </div>
            @endif
            <div class="form-group col-md-4">
                {!! Form::label('description', 'Narration') !!}
                {!! Form::textarea('description', null, ['class' => 'form-control', 'rows'=>10, 'placeholder' =>'Narration', 'style' => "height: 40px !important;"]) !!}
            </div>
            <div class="form-group col-md-2">
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('amount', 'Cr Amount:') !!}
                {!! Form::number('amount', null, ['class' => 'form-control cr_amount', 'step' => 'any']) !!}
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-3">
                {!! Form::label('account', 'Account:') !!}
                <select name="account_id[]" class="form-control account-select select2">
                    <option value="">Select</option>
                    @foreach(\App\Models\Accounts::where('status', 1)->get() as $payee)
                    <option value="{{ $payee->id }}">{{ $payee->account_code.'-'.$payee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-4">
                {!! Form::label('narration', 'Narration') !!}
                {!! Form::textarea('narration[]', null, ['class' => 'form-control', 'rows'=>10, 'placeholder' =>'Narration', 'style' => "height: 40px !important;"]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('amount', 'Dr Amount:') !!}
                {!! Form::number('dr_amount[]', null, ['class' => 'form-control dr_amount', 'step' => 'any']) !!}
            </div>
            <div class="form-group col-md-2">
            </div>
        </div>
    </div>
    @else
    <div id="row-container">
        <h5 class="my-1">Payment Voucher</h5>
        @foreach ($transactions as $index => $transaction)
        @if($index == 0)
        <div class="row">
            @if(isset($leasingCompany) || (isset($payment) && $payment->leasing_company_id))
            <div class="form-group col-md-3">
                {!! Form::label('leasing_company', 'Leasing Company:') !!}
                {!! Form::hidden('leasing_company_id', $leasingCompany->id ?? $payment->leasing_company_id ?? '')!!}
                {!! Form::text('leasing-company-name', $leasingCompany->name ?? $payment->leasingCompany->name ?? '-', ['class' => 'form-control', 'readonly' => true]) !!}
            </div>
            @else
            <div class="form-group col-md-3">
                {!! Form::label('bank', 'Account:') !!}
                {!! Form::hidden('bank_id', $bank->id ?? $payment->bank_id ?? '')!!}
                {!! Form::text('bank-name', $bank->account->account_code.'-'.$bank->account->name, ['class' => 'form-control', 'readonly' => true]) !!}
            </div>
            @endif
            <div class="form-group col-md-4">
                {!! Form::label('description', 'Narration') !!}
                {!! Form::textarea('description', null, ['class' => 'form-control', 'rows'=>10, 'placeholder' =>'Narration', 'style' => "height: 40px !important;"]) !!}
            </div>
            <div class="form-group col-md-2">
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('amount', 'Cr Amount:') !!}
                {!! Form::number('amount', null, ['class' => 'form-control cr_amount', 'step' => 'any']) !!}
            </div>
        </div>
        @else
        <div class="row">
            <div class="form-group col-md-3">
                {!! Form::label('account', 'Account:') !!}
                <select name="account_id[]" class="form-control account-select select2">
                    <option value="">Select</option>
                    @foreach(\App\Models\Accounts::where('status', 1)->get() as $payee)
                    <option value="{{ $payee->id }}" {{ old('payer_account_id',  $transaction->account_id) == $payee->id ? 'selected' : '' }}>{{ $payee->account_code.'-'.$payee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-4">
                {!! Form::label('narration', 'Narration') !!}
                {!! Form::textarea('narration[]', $transaction->narration, ['class' => 'form-control', 'rows'=>10, 'placeholder' =>'Narration', 'style' => "height: 40px !important;"]) !!}
            </div>
            <div class="form-group col-md-2">
                {!! Form::label('amount', 'Dr Amount:') !!}
                {!! Form::number('dr_amount[]', $transaction->debit, ['class' => 'form-control dr_amount', 'step' => 'any']) !!}
            </div>
            <div class="form-group col-md-2">
            </div>
        </div>
        @endif
        @endforeach
    </div>
    @endif
    <button type="button" id="add-row" class="btn btn-success btn-sm mt-3 mb-3">Add New</button>
</div>

<div class="row">
    <div class="col-md-5 text-danger" id="total-error"></div>
    <div class="col-md-2 content-right mt-1">Total:&nbsp;<a href="javascript:void(0);" onclick="getTotal();" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a></div>
    <div class="form-group col-md-2">
        <input type="number" class="form-control " id="total_dr" readonly placeholder="Total Dr">
    </div>
    <div class="form-group col-md-2">
        <input type="number" class="form-control " id="total_cr" readonly placeholder="Total Cr">
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize all select2 elements
        initializeSelect2();

        // Event delegation for dynamically added elements
        $(document).on('focus keyup change', '.cr_amount, .dr_amount', function() {
            getTotal();
            $("#total_dr, #total_cr").removeClass('is-invalid');
        });

        $(document).on('click', '.remove-row', function(e) {
            e.preventDefault();
            $(this).closest('.row').remove();
            getTotal();
        });

        // Add row button click
        $('#add-row').click(addNewRow);

        // Initial calculation
        getTotal();

        $('form').on('submit', function(e) {
            if (!validateReceiptForm()) {
                e.preventDefault(); // Stop form submission
                return false;
            }
        });

    }); // End of $(document).ready

    function validateReceiptForm() {
        var totalDr = parseFloat($("#total_dr").val()) || 0;
        var totalCr = parseFloat($("#total_cr").val()) || 0;

        // Calculate with precision to avoid floating point issues
        if (Math.abs(totalDr - totalCr) > 0.01) { // Allow 0.01 difference for rounding
            // Show error message
            $('#total-error').text('Error: Total debit and credit must be same.');


            // Highlight the totals
            $("#total_dr, #total_cr").addClass('is-invalid');

            return false; // Prevent form submission
        }

        // Additional validation: Check if at least one row has amount
        var hasAmount = false;
        $(".cr_amount, .dr_amount").each(function() {
            var val = parseFloat($(this).val()) || 0;
            if (val > 0) {
                hasAmount = true;
            }
        });

        if (!hasAmount) {

            $('#total-error').text('Cannot Submit Form without Any Amounts');
            return false;
        }

        // Remove error styling
        $("#total_dr, #total_cr").removeClass('is-invalid');
        return true; // Allow form submission
    }

    function addNewRow() {
        const newRow = $(`
        <div class="row">
            <div class="form-group col-md-3">
                <label for="account">Account:</label>
                <select name="account_id[]" class="form-control account-select">
                    <option value="">Select</option>
                    @foreach(\App\Models\Accounts::where('status', 1)->get() as $payee)
                    <option value="{{ $payee->id }}" >{{ $payee->account_code.'-'.$payee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="narration">Narration</label>
                <textarea name="narration[]" class="form-control" rows="1" placeholder="Narration" style="height: 40px !important;"></textarea>
            </div>
            <div class="form-group col-md-2">
                <label for="amount">Dr Amount:</label>
                <input type="number" name="dr_amount[]" class="form-control dr_amount" step="any">
            </div>
            <div class="form-group col-md-2">
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <a href="javascript:void(0);" class="text-danger remove-row"><i class="fa fa-trash"></i></a>
            </div>
        </div>
    `);

        // Append to container
        $('#row-container').append(newRow);

        // Initialize select2 for the new row's select element
        newRow.find('.account-select').select2({
            dropdownParent: $('#formajax'),
            allowClear: true
        });

        // Calculate total
        getTotal();
    }

    function initializeSelect2() {
        // Initialize all existing select2 elements
        $('.select2').select2({
            dropdownParent: $('#formajax'),
            allowClear: true
        });
    }

    function getTotal() {
        var cr_sum = 0;
        var dr_sum = 0;

        // Iterate through each cr_amount textbox and add the values
        $(".cr_amount").each(function() {
            // Add only if the value is a number
            if (!isNaN(this.value) && this.value.length != 0) {
                cr_sum += parseFloat(this.value);
            }
        });

        // Iterate through each dr_amount textbox and add the values
        $(".dr_amount").each(function() {
            // Add only if the value is a number
            if (!isNaN(this.value) && this.value.length != 0) {
                dr_sum += parseFloat(this.value);
            }
        });

        // .toFixed() method will round off the final sum to 2 decimal places
        $("#total_cr").val(cr_sum.toFixed(2));
        $("#total_dr").val(dr_sum.toFixed(2));
    }
</script>