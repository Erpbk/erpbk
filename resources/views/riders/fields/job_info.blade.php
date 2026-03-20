<div class="row">
    <div class="form-group col-sm-4">
        {!! Form::label('emirate_id', 'Emirate ID:',['class'=>'required']) !!}
        {!! Form::text('emirate_id', null, ['class' => 'form-control', 'required', 'id' => 'emirate_id', 'placeholder' => '784-2000-6871718-8', 'oninput' => 'formatEmirateId(this)', 'maxlength' => '18']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('emirate_exp', 'Emirate Expiry:',['class'=>'required']) !!}
        {!! Form::date('emirate_exp', null, ['class' => 'form-control','id'=>'emirate_exp','required']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('fleet_supervisor', 'Fleet Supervisor:',['class'=>'required']) !!}
        {!! Form::select('fleet_supervisor', Common::Dropdowns('fleet-supervisor'), null, ['class' => 'form-select', 'placeholder' => 'Select Fleet Supervisor', 'required']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('salary_model', 'Salary Model:',['class'=>'required']) !!}
        {!! Form::select('salary_model', Common::Dropdowns('salary-model'), null, ['class' => 'form-select', 'placeholder' => 'Select Salary Model', 'required']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('VID', 'Vendor:',['class'=>'required']) !!}
        {!! Form::select('VID', App\Models\Vendors::dropdown(), null, ['class' => 'form-select', 'required']) !!}
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
    <div class="form-group col-sm-4">
        <datalist id="sponsorOptions"><option value="Express Fast Delivery Service"></datalist>
        {!! Form::label('visa_sponsor', 'Visa Sponsor:') !!}
        {!! Form::text('visa_sponsor', null, ['class' => 'form-control', 'placeholder' => 'Enter Visa Sponsor', 'maxlength' => 50, 'list' => 'sponsorOptions']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('visa_occupation', 'Visa Occupation:',['class'=>'required']) !!}
        {!! Form::text('visa_occupation', null, ['class' => 'form-control', 'placeholder' => 'Enter Visa Occupation','maxlength' => 50, 'required' ]) !!}
    </div>
    <div class="form-group col-sm-4">
        <label>VAT</label>
        <div class="form-check">
            <input type="hidden" name="vat" value="2" />
            <input type="checkbox" name="vat" id="vat" class="form-check-input" value="1" @isset($riders) @if($riders->vat == 1) checked @endif @endisset/>
            <label for="vat" class="pt-0">Apply on Invoice</label>
        </div>
    </div>
</div>
