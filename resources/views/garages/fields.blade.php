<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<!-- Contact Person Field -->
<div class="form-group col-sm-6">
    {!! Form::label('contact_person', 'Contact Person:') !!}
    {!! Form::text('contact_person', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<!-- Contact Number Field -->
<div class="form-group col-sm-6">
    {!! Form::label('contact_number', 'Contact Number:') !!}
    {!! Form::text('contact_number', null, ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
</div>

<!-- Address Field -->
<div class="form-group col-sm-6">
    {!! Form::label('address', 'Address:') !!}
    {!! Form::text('address', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
<!-- Garage type: choose on create; fixed after save -->
<div class="form-group col-sm-6">
    {!! Form::label('garage_type', 'Garage type:') !!}
    @if(isset($garages))
    <select class="form-select" disabled>
        <option value="internal" {{ ($garages->garage_type ?? 'external') === 'internal' ? 'selected' : '' }}>Internal </option>
        <option value="external" {{ ($garages->garage_type ?? 'external') === 'external' ? 'selected' : '' }}>External</option>
    </select>
    <small class="text-muted d-block mt-1">Garage type cannot be changed after creation (linked chart account).</small>
    @else
    {!! Form::select('garage_type', [
    'internal' => 'Internal',
    'external' => 'External',
    ], old('garage_type', 'external'), ['class' => 'form-select', 'required' => true]) !!}
    @endif
</div>
<!-- Branch Field -->
<div class="form-group col-sm-6">
    {!! Form::label('branch_id', 'Company Branch:',['class'=>'required']) !!}
    {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-select select2']) !!}
</div>
<div class="mt-4 col-sm-12 alert alert-warning">Select <b>'All'</b> option in Branch list if this Garage will be used by all or multiple company branches</div>

<!-- Detail Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('detail', 'Detail:') !!}
    {!! Form::textarea('detail', null, ['class' => 'form-control', 'maxlength' => 65535, 'rows' => 3]) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6 mt-3">
    <label>Status</label>
    <div class="form-check">
        <input type="hidden" name="status" value="2" />
        <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($garages) @if($garages->status == 1) checked @endif @else checked @endisset/>
        <label for="status" class="pt-0">Is Active</label>

    </div>
</div>