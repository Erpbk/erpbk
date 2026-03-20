<div class="row">
    <div class="form-group col-sm-4">
        {!! Form::label('license_no', 'License No:',['class'=>'required']) !!}
        {!! Form::text('license_no', null, ['class' => 'form-control', 'maxlength' => 50]) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('license_expiry', 'License Expiry:',['class'=>'required']) !!}
        {!! Form::date('license_expiry', null, ['class' => 'form-control','id'=>'license_expiry']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('road_permit', 'Road Permit:') !!}
        {!! Form::text('road_permit', null, ['class' => 'form-control', 'placeholder' => 'Enter Road Permit No.','maxlength' => 50 ]) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('road_permit_expiry', 'Road Permit Expiry:') !!}
        {!! Form::date('road_permit_expiry', null, ['class' => 'form-control','id'=>'road_permit_expiry']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('visa_status', 'Visa Status:') !!}
        {!! Form::select('visa_status', Common::Dropdowns('visa-status'), null, ['class' => 'form-select', 'placeholder' => 'Select Visa Status']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('passport_handover', 'Passport Handover:',['class'=>'required']) !!}
        {!! Form::select('passport_handover', Common::Dropdowns('passport-handover'), null, ['class' => 'form-select', 'placeholder' => 'Select Passport Handover']) !!}
    </div>
</div>
