<!-- Supplier Info Section -->

     <!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Supplier Name:', ['class' => 'required']) !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'required']) !!}
</div>

<!-- Phone Field -->
<div class="form-group col-sm-6">
    {!! Form::label('phone', 'Phone:', ['class' => 'required']) !!}
    {!! Form::text('phone', null, ['class' => 'form-control', 'maxlength' => 100, 'required']) !!}
</div>

<!-- Email Field -->
<div class="form-group col-sm-6">
    {!! Form::label('email', 'Email:', ['class' => 'required']) !!}
    {!! Form::email('email', null, ['class' => 'form-control', 'maxlength' => 100, 'required']) !!}
</div>

<!-- Address Field -->
<div class="form-group col-sm-6">
    {!! Form::label('company Name', 'Company_name:', ['class' => 'required']) !!}
    {!! Form::text('company_name', null, ['class' => 'form-control', 'maxlength' => 200, 'required']) !!}
</div>
<!-- Address Field -->
<div class="form-group col-sm-6">
    {!! Form::label('address', 'Address:', ['class' => 'required']) !!}
    {!! Form::text('address', null, ['class' => 'form-control', 'maxlength' => 200, 'required']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6 mt-3">
    <label>Status</label>
    <div class="form-check">
        <input type="hidden" name="status" value="2"/>
        <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($supplier) @if($supplier->status == 'Active') checked @endif @else checked @endisset/>
        <label for="status" class="pt-0">Is Active</label>
    </div>
</div>
