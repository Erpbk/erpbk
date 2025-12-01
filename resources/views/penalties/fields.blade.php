<!-- Rider Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rider_id', 'Rider:') !!}
    {!! Form::select('rider_id', $riders->pluck('name', 'id')->prepend('Select Rider', ''), null, ['class' => 'form-control select2', 'required' => true]) !!}
</div>

<!-- Transaction Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('transaction_date', 'Transaction Date:') !!}
    {!! Form::date('transaction_date', null, ['class' => 'form-control', 'required' => true]) !!}
</div>

<!-- Transaction Time Field -->
<div class="form-group col-sm-6">
    {!! Form::label('transaction_time', 'Transaction Time:') !!}
    {!! Form::time('transaction_time', null, ['class' => 'form-control']) !!}
</div>

<!-- Billing Month Field -->
<div class="form-group col-sm-6">
    {!! Form::label('billing_month', 'Billing Month:') !!}
    {!! Form::month('billing_month', null, ['class' => 'form-control']) !!}
</div>

<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:') !!}
    {!! Form::number('amount', null, ['class' => 'form-control', 'step' => '0.01', 'min' => '0', 'required' => true]) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {!! Form::select('status', ['pending' => 'Pending', 'paid' => 'Paid', 'unpaid' => 'Unpaid'], null, ['class' => 'form-control']) !!}
</div>

<!-- Description Field -->
<div class="form-group col-sm-12">
    {!! Form::label('description', 'Description:') !!}
    {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('penalties.index') }}" class="btn btn-default">Cancel</a>
</div>