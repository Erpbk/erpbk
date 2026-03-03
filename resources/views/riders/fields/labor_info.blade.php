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
        {!! Form::label('insurance', 'Insurance:') !!}
        {!! Form::select('insurance', Common::Dropdowns('insurance'), null, ['class' => 'form-select', 'placeholder' => 'Select insurance']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('insurance_expiry', 'Insurance Expiry:') !!}
        {!! Form::date('insurance_expiry', null, ['class' => 'form-control','id'=>'insurance_expiry']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('policy_no', 'Policy No:') !!}
        {!! Form::text('policy_no', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
    </div>
</div>
<div class="row">
    <div class="form-group col-sm-4">
        {!! Form::label('wps', 'Wps:') !!}
        {!! Form::select('wps', Common::Dropdowns('wps'), null, ['class' => 'form-select', 'placeholder' => 'Select wps']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('c3_card', 'Salary Card:') !!}
        {!! Form::select('c3_card', Common::Dropdowns('c3-card'), null, ['class' => 'form-select', 'placeholder' => 'Select Sallary Type']) !!}
    </div>
</div>
