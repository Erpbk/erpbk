<input type="hidden" name="payment_from" value="{{\App\Helpers\HeadAccount::ADVANCE_LOAN}}" />

@php
$rider_account = \App\Models\Accounts::where('ref_id', $rider->id)->where('account_type', 'Liability')->first();
if (!$rider_account) {
// Fallback: try to find any account for this rider
$rider_account = \App\Models\Accounts::where('ref_id', $rider->id)->first();
}
@endphp
<div class="row">
    <div class="form-group col-md-3">
        <label for="exampleInputEmail1">Select Account</label>
        <input type="hidden" name="account_id[]" value="{{ $rider_account->id ?? '' }}" />
        {!! Form::select('account_id[]', $accounts, $rider_account->id ?? null, ['class' => 'form-select form-select-sm select2' , 'disabled' => true]) !!}
    </div>
    <div class="form-group col-md-4">
        <label>Narration</label>
        <textarea name="narration[]" class="form-control" rows="10" placeholder="Advance Loan Received" style="height: 40px !important;">Advance Loan Received</textarea>
    </div>
    <div class="form-group col-md-2">
        <label>Amount</label>
        <input type="number" step="any" name="dr_amount[]" id="first-dr-amount" class="form-control dr_amount" placeholder="Loan Amount" onchange="getTotal(); syncSecondRowAmount();" required>
    </div>
    {{-- <div class="form-group col-md-2">
            <label>Cr Amount</label>
            <input type="number" step="any" name="cr_amount[]" class="form-control cr_amount" placeholder="Paid Amount" onchange="getTotal();">
        </div> --}}
</div>
<div id="rows-container" class="mb-3" style="width: 100%;">
    @isset($data)
    @foreach($data as $entry)
    <div class="row">
        <div class="form-group col-md-3">
            <label for="exampleInputEmail1">Select Account</label>
            {!! Form::select('account_id[]', $accounts, $entry->account_id??null, ['class' => 'form-control form-select select2 ']) !!}
        </div>
        <div class="form-group col-md-4">
            <label>Narration</label>
            <textarea name="narration[]" class="form-control " rows="10" placeholder="Narration" style="height: 40px !important;">{{$entry->narration}}</textarea>
        </div>
        <div class="form-group col-md-2">
            <label>Amount</label>
            <input type="number" step="any" name="dr_amount[]" value="{{$entry->debit}}" class="form-control  dr_amount" onchange="getTotal();" placeholder="Paid Amount">
        </div>
    </div>
    @endforeach
    @else
    <!-- Second row for credit account (Advance Loan account) -->
    <div class="row">
        <div class="form-group col-md-3">
            <label for="exampleInputEmail1">Select Account</label>
            {!! Form::select('account_id[]', $bank_accounts, null, ['class' => 'form-select form-select-sm select2', ]) !!}
        </div>
        <div class="form-group col-md-4">
            <label>Narration</label>
            <textarea name="narration[]" class="form-control" rows="10" placeholder="Advance Loan Given" style="height: 40px !important;">Advance Loan Given to {{ $rider->name }}</textarea>
        </div>
        <div class="form-group col-md-2">
            <label>Amount</label>
            <input type="number" step="any" name="cr_amount[]" id="second-cr-amount" class="form-control cr_amount" placeholder="Loan Amount" onchange="getTotal();" readonly required>
        </div>
    </div>
    @endisset
</div>



<script>
    $(document).ready(function() {
        // Add row functionality
        $('#add-row-btn').on('click', function() {
            addNewRow();
        });

        // Remove row functionality
        $(document).on('click', '.btn-remove-row', function() {
            if (!$(this).prop('disabled')) {
                $(this).closest('.row').remove();
                updateDeleteButtons();
                getTotal();
            }
        });

        // Sync second row credit amount with first row debit amount (for advance loan)
        function syncSecondRowAmount() {
            var firstAmount = $('#first-dr-amount').val();
            if (firstAmount) {
                $('#second-cr-amount').val(firstAmount);
            }
        }

        // Sync on input/change
        $(document).on('input change', '#first-dr-amount', function() {
            syncSecondRowAmount();
        });

        // Ensure sync before form submission
        $('form').on('submit', function() {
            syncSecondRowAmount();
        });

        // Initialize delete buttons state
        updateDeleteButtons();
    });

    function addNewRow() {
        var rowHtml = `
        <div class="row">
            <div class="form-group col-md-3">
                <label for="exampleInputEmail1">Select Account</label>
                {!! Form::select('account_id[]', $accounts, null, ['class' => 'form-control form-select select2']) !!}
            </div>
            <div class="form-group col-md-4">
                <label>Narration</label>
                <textarea name="narration[]" class="form-control" rows="10" placeholder="Narration" style="height: 40px !important;"></textarea>
            </div>
            <div class="form-group col-md-2">
                <label>Amount</label>
                <input type="number" step="any" name="dr_amount[]" class="form-control dr_amount" placeholder="Amount" onchange="getTotal();" required>
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm btn-remove-row">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    `;

        $('#rows-container').append(rowHtml);
        updateDeleteButtons();
        getTotal();
    }

    function updateDeleteButtons() {
        var rows = $('#rows-container .row');

        // Enable/disable delete buttons based on row count
        rows.each(function(index) {
            var deleteBtn = $(this).find('.btn-remove-row');

            if (rows.length <= 2) {
                // If only 2 rows (first debit + last credit), disable all delete buttons
                deleteBtn.prop('disabled', true);
            } else {
                // If more than 2 rows, enable delete buttons for middle rows only
                if (index === 0 || index === rows.length - 1) {
                    deleteBtn.prop('disabled', true);
                } else {
                    deleteBtn.prop('disabled', false);
                }
            }
        });
    }
</script>