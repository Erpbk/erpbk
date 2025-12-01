<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="row mt-0 mb-2">
    <div class="form-group col-md-3">
        <label for="exampleInputEmail1">Date</label>
        <input type="date" name="trans_date" class="form-control " placeholder="Transaction Date" value="{{ date('Y-m-d') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="exampleInputEmail1">Billing Month</label>
        <input type="month" name="billing_month" class="form-control " value="{{ date('Y-m-01') }}" required>
    </div>
</div>
<div class="scrollbar">
    <h5>Vendor Charges Voucher</h5>

    @php
    $rider_account = null;
    if ($rider && $rider->id) {
    $rider_account = \App\Models\Accounts::where('ref_id', $rider->id)->where('account_type', 'Liability')->first();
    if (!$rider_account) {
    // Fallback: try to find any account for this rider
    $rider_account = \App\Models\Accounts::where('ref_id', $rider->id)->first();
    }
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
            <textarea name="narration[]" class="form-control" rows="10" placeholder="Vendor Charges Applied" style="height: 40px !important;">Vendor Charges Applied</textarea>
        </div>
        <div class="form-group col-md-2">
            <label>Amount (Dr)</label>
            <input type="number" step="any" name="dr_amount[]" class="form-control dr_amount main_amount" placeholder="Vendor Charges Amount" onchange="getTotal();" required>
        </div>
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
                <input type="number" step="any" name="dr_amount[]" value="{{$entry->debit}}" class="form-control  dr_amount" onchange="getTotal();" placeholder="Vendor Charges Amount">
            </div>
        </div>
        @endforeach
        @else
        <!-- Second row for credit account (Vendor Charges account) -->
        <div class="row">
            <div class="form-group col-md-3">
                <label for="exampleInputEmail1">Select Account</label>
                {!! Form::select('account_id[]', $accounts, \App\Helpers\HeadAccount::VENDOR_CHARGES_ACCOUNT, ['class' => 'form-select form-select-sm select2']) !!}
            </div>
            <div class="form-group col-md-4">
                <label>Narration</label>
                <textarea name="narration[]" class="form-control" rows="10" placeholder="Vendor Charges from rider" style="height: 40px !important;">Vendor Charges from {{ $rider->name ?? 'Rider' }}</textarea>
            </div>
            <div class="form-group col-md-2">
                <label>Amount (Cr)</label>
                <input type="number" step="any" name="cr_amount[]" class="form-control cr_amount" placeholder="Vendor Charges Amount" onchange="getTotal();" required readonly>
            </div>
        </div>
        @endisset
    </div>

    <div class="row">
        <div class="col-md-5"></div>
        <div class="col-md-2 content-right mt-1">Total:&nbsp;<a href="javascript:void(0);" onclick="getTotal();" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i></a></div>
        <div class="form-group col-md-2">
            <input type="number" class="form-control " id="total_dr" readonly placeholder="Total Dr">
        </div>
    </div>

    <script>
        $(document).ready(function() {
            var base_url = $('#base_url').val();
            getTotal();

            // Auto-copy amount from first field to second field
            $('.main_amount').on('input', function() {
                var amount = $(this).val();
                // Copy to the cr_amount field (credit account)
                $('.cr_amount').val(amount);
                getTotal();
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
        });
    </script>