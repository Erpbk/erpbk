<script src="{{ asset('js/modal_custom.js') }}"></script>
<div class="form-group col-sm-6">
    {!! Form::label('transaction_id', 'Transaction ID:') !!}
    {!! Form::text('transaction_id', $salik->transaction_id ?? null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('transaction_post_date', 'Transaction Post Date:') !!}
    {!! Form::date('transaction_post_date',
    isset($salik->transaction_post_date) ? \Carbon\Carbon::parse($salik->transaction_post_date)->format('Y-m-d') : null,
    ['class' => 'form-control']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('trip_date', 'Trip Date:') !!}
    {!! Form::date('trip_date', isset($salik->trip_date) ? \Carbon\Carbon::parse($salik->trip_date)->format('Y-m-d') : null, ['class' => 'form-control', 'id' => 'trip_date_create']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('trip_time', 'Trip Time:') !!}
    {!! Form::time('trip_time', isset($salik->trip_time) ? \Carbon\Carbon::parse($salik->trip_time)->format('H:i') : null, ['class' => 'form-control']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('billing_month', 'Billing Month:') !!}
    {!! Form::month('billing_month', isset($salik->billing_month) ? \Carbon\Carbon::parse($salik->billing_month)->format('Y-m') : null, ['class' => 'form-control', 'id' => 'billing_month']) !!}
</div>
<!-- Rider Id Field -->
<div class="form-group col-sm-6">
    <label class="">Bike:</label>
    <select class="form select select2" required id="bike_id_create" name="bike_id">
        <option value=""></option>
        @php
        $bikes = DB::table('bikes')->get();
        @endphp
        @foreach($bikes as $b)
        @php
        $company = DB::table('leasing_companies')->where('id', $b->company)->first();
        @endphp
        <option @if(isset($salik) && $b->id == $salik->bike_id) selected @endif value="{{ $b->id }}">
            {{ $b->plate }} - {{ $company ? $company->name : 'N/A' }}
        </option>

        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="">Debit Account:</label>
    <select class="form select select2" required id="debit_account" name="rider_id">
        <option value=""></option>
        @if(isset($salik))
        @foreach(DB::table('riders')->where('status', 1)->get() as $r)
        <option value="{{ $r->id }}"
            @if($r->id == $salik->rider_id) selected @endif>
            {{ $r->rider_id }} - {{ $r->name ?? 'N/A' }}
        </option>
        @endforeach
        @endif

    </select>
</div>
<div class="form-group col-sm-6">
    <label class="">Credit Account:</label>
    <select class="form select select2" required id="salik_account_id" name="salik_account_id">
        <option value=""></option>
        @foreach(DB::table('accounts')->where('status' , 1)->where('id', $data->id)->get() as $a)
        <option value="{{ $a->id }}" @if($data->id == $a->id) selected @endif>{{ $a->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    {!! Form::label('toll_gate', 'Toll Gate:') !!}
    {!! Form::text('toll_gate', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('direction', 'Direction:') !!}
    {!! Form::text('direction', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('tag_number', 'Tag Number:') !!}
    {!! Form::text('tag_number', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('admin_fee', 'Admin Charges:', ['class' => '']) !!}
    {!! Form::number('admin_fee', $data->admin_charges ?? 0, ['class' => 'form-control','step'=>'0.01', 'readonly']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:') !!}
    {!! Form::number('amount', null, ['class' => 'form-control']) !!}
</div>
<!-- Detail Field -->
<div class="form-group col-sm-12">
    {!! Form::label('details', 'Detail:', ['class' => 'required']) !!}
    {!! Form::textarea('details', $salik->details ?? '', ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'required']) !!}
</div>
<script type="text/javascript">
    $(document).ready(function() {
        function updateRiderSelect() {
            var bike_id = $('#bike_id_create').val();
            var trip_date = $('#trip_date_create').val();
            if (bike_id && trip_date) {
                $.ajax({
                    url: '{{ route("salik.getriderbybikedate") }}',
                    type: 'POST',
                    data: {
                        bike_id: bike_id,
                        trip_date: trip_date,
                        _token: '{{ csrf_token() }}' // POST mai zaroori hai
                    },
                    success: function(response) {
                        var $select = $('#debit_account');
                        $select.empty();
                        if (response.options.length > 0) {
                            $select.append('<option value="">Select Rider</option>');
                            response.options.forEach(function(opt) {
                                $select.append('<option value="' + opt.value + '">' + opt.label + '</option>');
                            });
                        } else {
                            $select.append('<option value="">No rider found for this bike and date</option>');
                        }
                        if ($select.hasClass('select2')) {
                            $select.val('').trigger('change');
                        }
                    }
                });

            }
        }
        $('#bike_id_create, #trip_date_create').on('change', updateRiderSelect);
    });
</script>