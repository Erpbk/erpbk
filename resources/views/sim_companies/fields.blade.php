<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'required' => true]) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('company_contact', 'Company contact:') !!}
    {!! Form::text('company_contact', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<div class="form-group col-sm-12">
    {!! Form::label('address', 'Address:') !!}
    {!! Form::textarea('address', null, ['class' => 'form-control', 'rows' => 2, 'maxlength' => 500]) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('branch_id', 'Branch:',['class'=>'required']) !!}
    {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-select select2']) !!}
</div>
<div class="mt-2 col-sm-12 alert alert-warning">Select <b>All</b> in Branch if this company applies to all branches.</div>

<div class="form-group col-sm-4 mt-3">
    <label>Status</label>
    <div class="form-check">
        <input type="hidden" name="status" value="2" />
        <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($simCompany) @if($simCompany->status == 1) checked @endif @else checked @endisset/>
        <label for="status" class="pt-0">Is Active</label>
    </div>
</div>
