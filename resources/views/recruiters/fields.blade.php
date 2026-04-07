<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<!-- Email Field -->
<div class="form-group col-sm-6">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
</div>

<!-- Contact Number Field -->
<div class="form-group col-sm-6">
    {!! Form::label('contact_number', 'Contact Number:') !!}
    {!! Form::text('contact_number', null, ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
</div>

<!-- Address Field -->
<div class="form-group col-sm-6">
    {!! Form::label('address', 'Address:') !!}
    {!! Form::text('address', null, ['class' => 'form-control', 'maxlength' => 200, 'maxlength' => 200]) !!}
</div>

<!-- Branch Field -->
<div class="form-group col-sm-6">
    {!! Form::label('branch_id', 'Branch:',['class'=>'required']) !!}
    {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-select select2']) !!}
</div>
<div class="mt-4 col-sm-12 alert alert-warning">Select <b>'All'</b> option in Branch list if this recruiter will be used by all or multiple company branches</div>


<!-- Status Field -->
<div class="form-group col-sm-4 mt-3">
    <label>Status</label>
    <div class="form-check">
        <input type="hidden" name="status" value="2" />
        <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($recruiters) @if($recruiters->status == 1) checked @endif @else checked @endisset/>
        <label for="status" class="pt-0">Is Active</label>

    </div>
</div>