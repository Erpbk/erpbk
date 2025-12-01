<!-- Rider Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rider_id', 'Rider Id:') !!}
    {!! Form::number('rider_id', null, ['class' => 'form-control']) !!}
</div>

<!-- D Rider Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('d_rider_id', 'D Rider Id:') !!}
    {!! Form::number('d_rider_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:') !!}
    {!! Form::text('date', null, ['class' => 'form-control','id'=>'date']) !!}
</div>

@push('page_scripts')
    <script type="text/javascript">
        $('#date').datepicker()
    </script>
@endpush

<!-- Shift Field -->
<div class="form-group col-sm-6">
    {!! Form::label('shift', 'Shift:') !!}
    {!! Form::text('shift', null, ['class' => 'form-control', 'maxlength' => 50, 'maxlength' => 50]) !!}
</div>

<!-- Attendance Field -->
<div class="form-group col-sm-6">
    {!! Form::label('attendance', 'Attendance:') !!}
    {!! Form::text('attendance', null, ['class' => 'form-control', 'maxlength' => 50, 'maxlength' => 50]) !!}
</div>

<!-- Cdm Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cdm_id', 'Cdm Id:') !!}
    {!! Form::number('cdm_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Day Field -->
<div class="form-group col-sm-6">
    {!! Form::label('day', 'Day:') !!}
    {!! Form::text('day', null, ['class' => 'form-control', 'maxlength' => 20, 'maxlength' => 20]) !!}
</div>