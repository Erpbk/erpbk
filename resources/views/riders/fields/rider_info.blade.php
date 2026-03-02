<div class="row">
    <div class="form-group col-sm-4">
        {!! Form::label('rider_id', 'Rider ID:',['class'=>'required']) !!}
        {!! Form::text('rider_id', null, ['class' => 'form-control','required', 'id' => 'rider_id_field']) !!}
        <div class="invalid-feedback" id="rider_id_error" style="display: none;"></div>
        @error('rider_id')<span class="text-danger">{{ $message }}</span>@enderror
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('name', 'Name:',['class'=>'required']) !!}
        {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 191, 'required']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('doj', 'Date Of Joining:',['class'=>'required']) !!}
        {!! Form::date('doj', null, ['class' => 'form-control','id'=>'doj','required']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('personal_contact', 'Personal Contact:') !!}
        {!! Form::tel('personal_contact', null, ['class' => 'form-control', 'placeholder' => '05XXXXXXXX', 'maxlength' => 10]) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('personal_email', 'Personal Email:',['class'=>'required']) !!}
        {!! Form::email('personal_email', null, ['class' => 'form-control', 'placeholder' => 'Enter Email ID','maxlength' => 191, 'required']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('nationality', 'Nationality:',['class'=>'required']) !!}
        {!! Form::select('nationality', App\Models\Countries::list()->toArray(), null, ['class' => 'form-control form-select select2', 'required', 'placeholder' => 'Select Nationality']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('passport', 'Passport:',['class'=>'required']) !!}
        {!! Form::text('passport', null, ['class' => 'form-control', 'maxlength' => 50]) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('passport_expiry', 'Passport Expiry:',['class'=>'required']) !!}
        {!! Form::date('passport_expiry', null, ['class' => 'form-control','id'=>'passport_expiry']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('ethnicity', 'Ethnicity:') !!}
        {!! Form::select('ethnicity', Common::Dropdowns('ethnicity'), null, ['class' => 'form-select', 'placeholder' => 'Select Ethnicity']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('dob', 'Date Of Birth:') !!}
        {!! Form::date('dob', null, ['class' => 'form-control','id'=>'dob']) !!}
    </div>
</div>
