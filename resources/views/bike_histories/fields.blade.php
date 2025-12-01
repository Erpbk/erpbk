<!-- Bike Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('bike_id', 'Bike Id:') !!}
    {!! Form::number('bike_id', null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- Rider Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rider_id', 'Rider Id:') !!}
    {!! Form::number('rider_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Notes Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('notes', 'Notes:') !!}
    {!! Form::textarea('notes', null, ['class' => 'form-control', 'maxlength' => 65535, 'maxlength' => 65535]) !!}
</div>

<!-- Note Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('note_date', 'Note Date:') !!}
    {!! Form::text('note_date', null, ['class' => 'form-control','id'=>'note_date']) !!}
</div>

@push('page_scripts')
    <script type="text/javascript">
        $('#note_date').datepicker()
    </script>
@endpush

<!-- Warehouse Field -->
<div class="form-group col-sm-6">
    {!! Form::label('warehouse', 'Warehouse:') !!}
    {!! Form::text('warehouse', null, ['class' => 'form-control', 'maxlength' => 50, 'maxlength' => 50]) !!}
</div>

<!-- Contract Field -->
<div class="form-group col-sm-6">
    {!! Form::label('contract', 'Contract:') !!}
    {!! Form::text('contract', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>