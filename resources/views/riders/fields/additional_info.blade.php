<div class="row">
    <div class="form-group col-sm-4">
        {!! Form::label('rider_reference', 'Rider Reference:',['class'=>'required']) !!}
        {!! Form::text('rider_reference', null, ['class' => 'form-control', 'required']) !!}
    </div>
    <div class="form-group col-sm-12">
        {!! Form::label('other_details', 'Other Details:') !!}
        {!! Form::textarea('other_details', null, ['class' => 'form-control', 'rows' => 2]) !!}
    </div>
</div>
