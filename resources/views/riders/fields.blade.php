<!-- Rider Info Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white fs-5 fw-bold p-4">Rider Info</div>
    <div class="card-body">
        <div class="row">
            <!-- Rider ID -->
            <div class="form-group col-sm-4">
                {!! Form::label('rider_id', 'Rider ID:',['class'=>'required']) !!}
                {!! Form::text('rider_id', null, ['class' => 'form-control','required', 'id' => 'rider_id_field']) !!}
                <div class="invalid-feedback" id="rider_id_error" style="display: none;"></div>
                @error('rider_id')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            {{-- <!-- Courier ID -->
            <div class="form-group col-sm-4">
                {!! Form::label('courier_id', 'Courier ID:') !!}
                {!! Form::number('courier_id', null, ['class' => 'form-control', 'id' => 'courier_id_field']) !!}
                <div class="invalid-feedback" id="courier_id_error" style="display: none;"></div>
                @error('courier_id')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div> --}}

        <!-- Name -->
        <div class="form-group col-sm-4">
            {!! Form::label('name', 'Name:',['class'=>'required']) !!}
            {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 191, 'required']) !!}
        </div>

        <!-- Date of Joining -->
        <div class="form-group col-sm-4">
            {!! Form::label('doj', 'Date Of Joining:',['class'=>'required']) !!}
            {!! Form::date('doj', null, ['class' => 'form-control','id'=>'doj','required']) !!}
        </div>

        <!-- Rider Contact -->
        <div class="form-group col-sm-4">
            {!! Form::label('personal_contact', 'Personal Contact:') !!}
            {!! Form::tel('personal_contact', null, ['class' => 'form-control', 'placeholder' => '05XXXXXXXX', 'maxlength' => 10]) !!}
        </div>

        <!-- Personal Email -->
        <div class="form-group col-sm-4">
            {!! Form::label('personal_email', 'Personal Email:',['class'=>'required']) !!}
            {!! Form::email('personal_email', null, ['class' => 'form-control', 'placeholder' => 'Enter Email ID','maxlength' => 191, 'required']) !!}
        </div>

        <!-- Nationality -->
        <div class="form-group col-sm-4">
            {!! Form::label('nationality', 'Nationality:',['class'=>'required']) !!}
            {!! Form::select('nationality',
            App\Models\Countries::list()->toArray(),
            null,
            [
            'class' => 'form-control form-select select2',
            'required',
            'placeholder' => 'Select Nationality'
            ]) !!}
        </div>

        <!-- Passport -->
        <div class="form-group col-sm-4">
            {!! Form::label('passport', 'Passport:',['class'=>'required']) !!}
            {!! Form::text('passport', null, ['class' => 'form-control', 'maxlength' => 50]) !!}
        </div>

        <!-- Passport Expiry -->
        <div class="form-group col-sm-4">
            {!! Form::label('passport_expiry', 'Passport Expiry:',['class'=>'required']) !!}
            {!! Form::date('passport_expiry', null, ['class' => 'form-control','id'=>'passport_expiry']) !!}
        </div>

        <!-- Ethnicity -->
        <div class="form-group col-sm-4">
            {!! Form::label('ethnicity', 'Ethnicity:') !!}
            {!! Form::select('ethnicity',
            Common::Dropdowns('ethnicity'),
            null,
            [
            'class' => 'form-select',
            'placeholder' => 'Select Ethnicity'
            ]) !!}
        </div>
        <!-- Date of Birth -->
        <div class="form-group col-sm-4">
            {!! Form::label('dob', 'Date Of Birth:') !!}
            {!! Form::date('dob', null, ['class' => 'form-control','id'=>'dob']) !!}
        </div>
    </div>
</div>
</div>

<!-- Visa Info Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white fs-5 fw-bold p-4">Visa Info</div>
    <div class="card-body">
        <div class="row">
            <!-- License No -->
            <div class="form-group col-sm-4">
                {!! Form::label('license_no', 'License No:',['class'=>'required']) !!}
                {!! Form::text('license_no', null, ['class' => 'form-control', 'maxlength' => 50]) !!}
            </div>
            <!-- License Expiry -->
            <div class="form-group col-sm-4">
                {!! Form::label('license_expiry', 'License Expiry:',['class'=>'required']) !!}
                {!! Form::date('license_expiry', null, ['class' => 'form-control','id'=>'license_expiry']) !!}
            </div>

            <!-- Road Permit -->
            <div class="form-group col-sm-4">
                {!! Form::label('road_permit', 'Road Permit:') !!}
                {!! Form::text('road_permit', null, ['class' => 'form-control', 'placeholder' => 'Enter Road Permit No.','maxlength' => 50 ]) !!}
            </div>

            <!-- Road Permit Expiry -->
            <div class="form-group col-sm-4">
                {!! Form::label('road_permit_expiry', 'Road Permit Expiry:') !!}
                {!! Form::date('road_permit_expiry', null, ['class' => 'form-control','id'=>'road_permit_expiry']) !!}
            </div>

            <!-- Visa Status -->
            <div class="form-group col-sm-4">
                {!! Form::label('visa_status', 'Visa Status:') !!}
                {!! Form::select('visa_status',
                Common::Dropdowns('visa-status'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select Visa Status'
                ]) !!}
            </div>

            <!-- Passport Handover -->
            <div class="form-group col-sm-4">
                {!! Form::label('passport_handover', 'Passport Handover:',['class'=>'required']) !!}
                {!! Form::select('passport_handover',
                Common::Dropdowns('passport-handover'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select Passport Handover'
                ]) !!}
            </div>
        </div>
    </div>
</div>

<!-- Job Info Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white fs-5 fw-bold p-4">Job Info</div>
    <div class="card-body">
        <div class="row">

            {{-- <!-- Mashreq ID -->
            <div class="form-group col-sm-4">
                {!! Form::label('mashreq_id', 'Mashreq Id:') !!}
                {!! Form::text('mashreq_id', null, ['class' => 'form-control', 'maxlength' => 191]) !!}
            </div> --}}

            <!-- Emirate ID -->
            <div class="form-group col-sm-4">
                {!! Form::label('emirate_id', 'Emirate ID:',['class'=>'required']) !!}
                <!-- {!! Form::text('emirate_id', null, ['class' => 'form-control','id'=>'emirate_id','placeholder' => '784-2000-6871718-8', 'required']) !!} -->
                {!! Form::text('emirate_id', null, [
                'class' => 'form-control',
                'required',
                'id' => 'emirate_id',
                'placeholder' => '784-2000-6871718-8',
                'oninput' => 'formatEmirateId(this)',
                'maxlength' => '18'
                ]) !!}
            </div>

            <!-- Emirate Expiry -->
            <div class="form-group col-sm-4">
                {!! Form::label('emirate_exp', 'Emirate Expiry:',['class'=>'required']) !!}
                {!! Form::date('emirate_exp', null, ['class' => 'form-control','id'=>'emirate_exp','required']) !!}
            </div>

            {{-- <!-- Branded Plate No -->
            <div class="form-group col-sm-4">
                {!! Form::label('branded_plate_no', 'Branded Plate No:') !!}
                {!! Form::text('branded_plate_no', null, ['class' => 'form-control', 'maxlength' => 191]) !!}
            </div> --}}

            <!-- Fleet Supervisor -->
            <div class="form-group col-sm-4">
                {!! Form::label('fleet_supervisor', 'Fleet Supervisor:',['class'=>'required']) !!}
                {!! Form::select('fleet_supervisor',
                Common::Dropdowns('fleet-supervisor'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select Fleet Supervisor',
                'required'
                ]) !!}
            </div>

            <!-- Salary Model -->
            <div class="form-group col-sm-4">
                {!! Form::label('salary_model', 'Salary Model:',['class'=>'required']) !!}
                {!! Form::select('salary_model',
                Common::Dropdowns('salary-model'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select Salary Model',
                'required'
                ]) !!}
            </div>

            <!-- Vendor -->
            <div class="form-group col-sm-4">
                {!! Form::label('VID', 'Vendor:',['class'=>'required']) !!}
                {!! Form::select('VID',App\Models\Vendors::dropdown(),null,
                ['class' => 'form-select', 'required']) !!}
            </div>
            <div class="form-group col-sm-4">
                <label>Recruiter</label>
                <select name="recruiter_id" class="form-select">
                    <option value="">Select Recruiter</option>
                    @foreach(DB::table('recruiters')->where('status', 1)->get() as $key => $value)
                    <option value="{{ $value->id }}" {{ isset($riders) && $riders->recruiter_id == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Visa Sponsor -->
            <div class="form-group col-sm-4">
                <datalist id="sponsorOptions">
                    <option value="Express Fast Delivery Service">
                </datalist>
                {!! Form::label('visa_sponsor', 'Visa Sponsor:') !!}
                {!! Form::text('visa_sponsor', null, ['class' => 'form-control', 'placeholder' => 'Enter Visa Sponsor', 'maxlength' => 50, 'list' => 'sponsorOptions']) !!}
            </div>
            <!-- Visa Occupation -->
            <div class="form-group col-sm-4">
                {!! Form::label('visa_occupation', 'Visa Occupation:',['class'=>'required']) !!}
                {!! Form::text('visa_occupation', null, ['class' => 'form-control', 'placeholder' => 'Enter Visa Occupation','maxlength' => 50, 'required' ]) !!}
            </div>
            <!-- VAT -->
            <div class="form-group col-sm-4">
                <label>VAT</label>
                <div class="form-check">
                    <input type="hidden" name="vat" value="2" />
                    <input type="checkbox" name="vat" id="vat" class="form-check-input" value="1" @isset($riders) @if($riders->vat == 1) checked @endif @else @endisset/>
                    <label for="vat" class="pt-0">Apply on Invoice</label>

                </div>
            </div>
        </div>
    </div>
</div>


<!-- Labor Info Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white fs-5 fw-bold p-4">Labor Info</div>
    <div class="card-body">
        <div class="row">
            <!-- Person Code -->
            <div class="form-group col-sm-4">
                {!! Form::label('person_code', 'Person Code:') !!}
                {!! Form::text('person_code', null, ['class' => 'form-control', 'maxlength' => 50]) !!}
            </div>

            <!-- Labor Card Number -->
            <div class="form-group col-sm-4">
                {!! Form::label('labor_card_number', 'Labor Card Number:') !!}
                {!! Form::text('labor_card_number', null, ['class' => 'form-control', 'maxlength' => 100]) !!}
            </div>

            <!-- Labor Card Expiry -->
            <div class="form-group col-sm-4">
                {!! Form::label('labor_card_expiry', 'Labor Card Expiry:') !!}
                {!! Form::date('labor_card_expiry', null, ['class' => 'form-control','id'=>'labor_card_expiry']) !!}
            </div>
        </div>

        <div class="row">
            <!-- Insurance -->
            <div class="form-group col-sm-4">
                {!! Form::label('insurance', 'Insurance:') !!}
                {!! Form::select('insurance',
                Common::Dropdowns('insurance'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select insurance'
                ]) !!}
            </div>

            <!-- Insurance Expiry -->
            <div class="form-group col-sm-4">
                {!! Form::label('insurance_expiry', 'Insurance Expiry:') !!}
                {!! Form::date('insurance_expiry', null, ['class' => 'form-control','id'=>'insurance_expiry']) !!}
            </div>

            <!-- Policy No -->
            <div class="form-group col-sm-4">
                {!! Form::label('policy_no', 'Policy No:') !!}
                {!! Form::text('policy_no', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
            </div>
        </div>

        <div class="row">
            <!-- Wps -->
            <div class="form-group col-sm-4">
                {!! Form::label('wps', 'Wps:') !!}
                {!! Form::select('wps',
                Common::Dropdowns('wps'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select wps'
                ]) !!}
            </div>

            <!-- C3 Card -->
            <div class="form-group col-sm-4">
                {!! Form::label('c3_card', 'Salary Card:') !!}
                {!! Form::select('c3_card',
                Common::Dropdowns('c3-card'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select Sallary Type'
                ]) !!}
            </div>
        </div>
    </div>
</div>

<!-- Additional Information Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white fs-5 fw-bold p-4">Additional Information</div>
    <div class="card-body">
        <div class="row">
            <!-- Rider Reference -->
            <div class="form-group col-sm-4">
                {!! Form::label('rider_reference', 'Rider Reference:',['class'=>'required']) !!}
                {!! Form::text('rider_reference', null, ['class' => 'form-control', 'required']) !!}
            </div>
            <!-- Other Details -->
            <div class="form-group col-sm-12">
                {!! Form::label('other_details', 'Other Details:') !!}
                {!! Form::textarea('other_details', null, ['class' => 'form-control', 'rows' => 2]) !!}
            </div>
        </div>
    </div>
</div>