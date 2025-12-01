<script src="{{ asset('js/modal_custom.js') }}"></script>
<!-- Trip Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('trip_date', 'Trip Date:' , ['class' => 'readonly']) !!}
    {!! Form::date('trip_date', $data->trip_date, ['class' => 'form-control', 'readonly']) !!}
</div>

<!-- Trip Time Field -->
<div class="form-group col-sm-6">
    {!! Form::label('trip_time', 'Trip Time:', ['class' => 'readonly']) !!}
    {!! Form::time('trip_time', $data->trip_time, ['class' => 'form-control', 'readonly']) !!}
</div>

<!-- Billing Month Field -->
<div class="form-group col-sm-6">
    {!! Form::label('billing_month', 'Billing Month:', ['class' => 'readonly']) !!}
    {!! Form::date('billing_month', $data->billing_month, ['class' => 'form-control', 'readonly']) !!}
</div>


<!-- Ticket No Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ticket_no', 'Ticket No:', ['class' => 'readonly']) !!}
    {!! Form::text('ticket_no', $data->ticket_no, ['class' => 'form-control', 'maxlength' => 50, 'readonly']) !!}
</div>

<!-- Rider Id Field -->
<div class="form-group col-sm-6">
    <label class="readonly">Bike:</label>
    <select class="form select select2" readonly id="bike_id" name="bike_id" readonly>
        <option value=""></option>
        @foreach(DB::table('bikes')->where('status', 1)->orderBy('id', 'desc')->get() as $b)
        @php
        $company = DB::table('leasing_companies')->where('id', $b->company)->first();
        @endphp
        <option value="{{ $b->id }}" @if($data->bike_id == $b->id) selected @endif>
            {{ $b->plate }} - {{ $company ? $company->name : 'N/A' }}
        </option>
        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="readonly">Debit Account:</label>
    <select class="form select select2" id="rta_account_id" name="rta_account_id" readonly>
        <option value=""></option>
        @foreach(DB::table('accounts')->where('status' , 1)->get() as $a)
        <option value="{{ $a->id }}" @if($data->rta_account_id == $a->id) selected @endif>{{ $a->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="required">Credit Account:</label>
    <select class="form select select2" id="account_id" name="account" required>
        <option value=""></option>
        @php
        $bank = DB::table('accounts')->where('id', 994)->first();
        $cash = DB::table('accounts')->where('id', 1643)->first();
        @endphp

        @foreach(DB::table('accounts')
        ->where('status', 1)
        ->whereIn('parent_id', [$bank->id, $cash->id])
        ->orderBy('id', 'asc')
        ->get() as $acc)
        <option value="{{ $acc->id }}">
            {{ $acc->name }}
        </option>
        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="required">Attachment</label>
    <input type="file" name="attach_file" class="form-control" required>
</div>

<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('service_charges', 'Service Charges:', ['class' => 'readonly']) !!}
    {!! Form::number('service_charges', $data->service_charges, ['class' => 'form-control','step'=>'any', 'readonly']) !!}
</div>
<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:', ['class' => 'readonly']) !!}
    {!! Form::text('', 'AED ' . number_format($data->amount, 2), ['class' => 'form-control', 'readonly']) !!}
</div>


<!-- Detail Field -->
<div class="form-group col-sm-12">
    {!! Form::label('detail', 'Detail:', ['class' => 'readonly']) !!}
    {!! Form::textarea('detail', $data->detail, ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'readonly']) !!}
</div>