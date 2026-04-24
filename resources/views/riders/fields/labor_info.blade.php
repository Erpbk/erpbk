<div class="row">
    <div class="form-group col-sm-4">
        {!! Form::label('person_code', 'Person Code:') !!}
        {!! Form::text('person_code', null, ['class' => 'form-control', 'maxlength' => 50]) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('labor_card_number', 'Labor Card Number:') !!}
        {!! Form::text('labor_card_number', null, ['class' => 'form-control', 'maxlength' => 100]) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('labor_card_expiry', 'Labor Card Expiry:') !!}
        {!! Form::date('labor_card_expiry', null, ['class' => 'form-control','id'=>'labor_card_expiry']) !!}
    </div>
</div>
<div class="row">
    <div class="form-group col-sm-4">
        {!! Form::label('wps', 'Wps:') !!}
        {!! Form::select('wps', Common::Dropdowns('wps'), null, ['class' => 'form-select', 'placeholder' => 'Select wps']) !!}
    </div>
</div>
