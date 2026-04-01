<script src="{{ asset('js/modal_custom.js') }}"></script>

<!-- Reference Number -->
<div class="form-group col-sm-6">
    {!! Form::label('reference_number', 'Reference Number:') !!}
    {!! Form::text('reference_number', $rtaFines->reference_number ?? '' , ['class' => 'form-control','step'=>'any']) !!}
</div>

<!-- Trip Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('trip_date', 'Trip Date:' , ['class' => 'required']) !!}
    {!! Form::date('trip_date', $rtaFines->trip_date ?? 'null', ['class' => 'form-control', 'required']) !!}
</div>

<!-- Trip Time Field -->
<div class="form-group col-sm-6">
    {!! Form::label('trip_time', 'Trip Time:', ['class' => 'required']) !!}
    {!! Form::time('trip_time', $rtaFines->trip_time ?? 'null', ['class' => 'form-control', 'required']) !!}
</div>

<!-- Billing Month Field -->
<div class="form-group col-sm-6">
    {!! Form::label('billing_month', 'Billing Month:', ['class' => 'required']) !!}
    {!! Form::month('billing_month', isset($rtaFines) && $rtaFines->billing_month ? \Carbon\Carbon::parse($rtaFines->billing_month)->format('Y-m') : null, ['class' => 'form-control' , 'required']) !!}
</div>


<!-- Ticket No Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ticket_no', 'Ticket No:', ['class' => 'required']) !!}
    {!! Form::text('ticket_no', $rtaFines->ticket_no ?? '', ['class' => 'form-control', 'maxlength' => 50, 'required']) !!}
</div>

<!-- Rider Id Field -->
<div class="form-group col-sm-6">
    <label class="">Bike:</label>
    <select class="form select select2" required onchange="selectbike(this.value)" id="bike_id" name="bike_id">
        <option value=""></option>
        @foreach(DB::table('bikes')->where('status', 1)->orderBy('id', 'desc')->get() as $b)
        @php
        $company = DB::table('leasing_companies')->where('id', $b->company)->first();
        @endphp
        <option value="{{ $b->id }}" {{ (isset($rtaFines) && $rtaFines->bike_id == $b->id) ? 'selected' : '' }}>
            {{ $b->plate }} - {{ $company ? $company->name : 'N/A' }}
        </option>
        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="">Debit Account:</label>
    <select class="form select select2" required id="debit_account" name="debit_account">
        <option value=""></option>
        @if(isset($rtaFines))
        @php
        $bikes = DB::table('bikes')->where('id', $rtaFines->bike_id)->first();
        $rider = $bikes ? DB::table('riders')->where('id', $rtaFines->rider_id)->first() : null;
        @endphp

        @foreach(DB::table('riders')->get() as $r)
        @php
        $account = DB::table('accounts')
        ->where('ref_id', $r->id)
        ->where('ref_name', 'Rider')
        ->first();
        @endphp

        @if($account)
        <option value="{{ $account->ref_id }}"
            {{ isset($rtaFines, $rtaFines->bike_id, $rider) && $r->id == $rider->id ? 'selected' : '' }}>
            {{ $r->rider_id }} - {{ $r->name ?? 'N/A' }}
        </option>
        @endif
        @endforeach
        @endif
    </select>
</div>
<div class="form-group col-sm-6">
    {!! Form::label('credit_account', 'Credit Account:') !!}
    <input type="hidden" name="rta_account_id" value="{{ $data->id }}">
    {!! Form::text('credit_account', $data->id.'-'.$data->name, ['class' => 'form-control','step'=>'any', 'readonly'=>true]) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('attachment', 'Attachment:', ['class' => '']) !!}
    {!! Form::file('attachment', ['class' => 'form-control', '']) !!}
</div>
<!-- Admin Charges Field -->
<div class="form-group col-sm-6">
    {!! Form::label('admin_fee', 'Admin Charges:', ['class' => '']) !!}
    {!! Form::number('admin_fee', $data->admin_charges ?? '', ['class' => 'form-control','step'=>'any', 'readonly']) !!}
</div>
<!-- Service Charges Field -->
<div class="form-group col-sm-6">
    {!! Form::label('service_charges', 'Service Charges:', ['class' => '']) !!}
    {!! Form::number('service_charges', $data->account_tax ?? '', ['class' => 'form-control','step'=>'any']) !!}
</div>
<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:', ['class' => 'required']) !!}
    {!! Form::number('amount', $rtaFines->amount ?? '', ['class' => 'form-control','step'=>'any', 'required']) !!}
</div>
<!-- Vat Field -->
<div class="form-group col-sm-6">
    {!! Form::label('vat', 'VAT:') !!}
    {!! Form::number('vat', $rtaFines->vat ?? '', ['class' => 'form-control','step'=>'any']) !!}
</div>

<!-- Detail Field -->
<div class="form-group col-sm-12">
    {!! Form::label('detail', 'Detail:', ['class' => 'required']) !!}
    {!! Form::textarea('detail', $rtaFines->detail ?? '', ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'required']) !!}
</div>
<script type="text/javascript">
    function selectbike(id) {
        if (id) {
            $.ajax({
                type: 'get',
                url: '{{ url("rtaFines/getrider") }}/' + id,
                success: function(res) {
                    $('#debit_account').html(res);
                }
            });
        } else {
            $('#debit_account').html('required');
        }
    }
</script>