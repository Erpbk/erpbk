<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:',['class'=>'required']) !!}
    {!! Form::text('name', null, ['class' => 'form-control','maxlength' => 255, 'required']) !!}
</div>

<!-- Contact Number Field -->
<div class="form-group col-sm-6">
  {!! Form::label('contact_number', 'Contact Number:',['class'=>'required']) !!}
  {!! Form::text('contact_number', null, ['class' => 'form-control', 'maxlength' => 100, 'required']) !!}
</div>
<!-- Company Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('company_name', 'Company Name:') !!}
    {!! Form::text('company_name', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<!-- Company Email Field -->
<div class="form-group col-sm-6">
    {!! Form::label('company_email', 'Company Email:') !!}
    {!! Form::text('company_email', null, ['class' => 'form-control', 'maxlength' => 100]) !!}
</div>


<!-- Address Field -->
<div class="form-group col-sm-12">
    {!! Form::label('address', 'Address:') !!}
    {!! Form::text('address', null, ['class' => 'form-control', 'maxlength' => 200]) !!}
</div>

<!-- Tax Number Field -->
<div class="form-group col-sm-6">
    {!! Form::label('tax_number', 'Tax Number:',['class'=>'required']) !!}
    {!! Form::text('tax_number', null, ['class' => 'form-control', 'maxlength' => 100, 'required']) !!}
</div>


<!-- Tax Percentage Field -->
<div class="form-group col-sm-6">
    {!! Form::label('tax_percentage', 'Tax Percentage:',['class'=>'required']) !!}
    {!! Form::number('tax_percentage', null, ['class' => 'form-control','step'=>'any', 'required']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6 mt-3">
  <label>Status</label>
  <div class="form-check">
    <input type="hidden" name="status" value="2"/>
     <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($customers) @if($customers->status == 1) checked @endif @else checked  @endisset/>
     <label for="status" class="pt-0">Is Active</label>

  </div>
</div>
