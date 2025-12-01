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
<!-- Toll Gate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('toll_gate', 'Toll Gate:', ['class' => 'readonly']) !!}
    {!! Form::text('toll_gate', $data->toll_gate, ['class' => 'form-control', 'readonly']) !!}
</div>
<!-- Direction Field -->
<div class="form-group col-sm-6">
    {!! Form::label('direction', 'Direction:', ['class' => 'readonly']) !!}
    {!! Form::text('direction', $data->direction, ['class' => 'form-control', 'readonly']) !!}
</div>
<!-- Tag Number Field -->
<div class="form-group col-sm-6">
    {!! Form::label('tag_number', 'Tag Number:', ['class' => 'readonly']) !!}
    {!! Form::text('tag_number', $data->tag_number, ['class' => 'form-control', 'readonly']) !!}
</div>
<!-- Plate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('plate', 'Plate:', ['class' => 'readonly']) !!}
    {!! Form::text('plate', $data->plate, ['class' => 'form-control', 'readonly']) !!}
</div>
<!-- Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:', ['class' => 'readonly']) !!}
    {!! Form::text('amount', 'AED ' . number_format($data->amount, 2), ['class' => 'form-control', 'readonly']) !!}
</div>
<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:', ['class' => 'readonly']) !!}
    {!! Form::text('status', $data->status, ['class' => 'form-control', 'readonly']) !!}
</div>