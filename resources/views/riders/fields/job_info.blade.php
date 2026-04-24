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
        {!! Form::label('fleet_supervisor', 'Fleet Supervisor:') !!}
        {!! Form::select('fleet_supervisor', Common::Dropdowns('fleet-supervisor'), null, ['class' => 'form-select', 'placeholder' => 'Select Fleet Supervisor', 'required']) !!}
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
</div>