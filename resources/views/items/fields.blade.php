<script src="{{ asset('js/modal_custom.js') }}"></script>

<!-- Name Field -->
<div class="form-group col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('customer_id', 'Customer:') !!}
{!! Form::select('customer_id', App\Models\Customers::dropdown(), null, ['class' => 'form-control select2']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('supplier_id', 'Supplier:') !!}
{!! Form::select('supplier_id', App\Models\Supplier::dropdown(), null, ['class' => 'form-control select2']) !!}
</div>
<!-- Price Field -->
<div class="form-group col-sm-6">
    {!! Form::label('price', 'Price:') !!}
    {!! Form::number('price', null, ['class' => 'form-control','step'=>'any']) !!}
</div>

<!-- Cost Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cost', 'Cost:') !!}
    {!! Form::number('cost', null, ['class' => 'form-control','step'=>'any']) !!}
</div>
<!-- Cost Field -->
<div class="form-group col-sm-6">
  {!! Form::label('code', 'Code:') !!}
  {!! Form::text('code', null, ['class' => 'form-control']) !!}
</div>
<!-- Cost Field -->
<div class="form-group col-sm-6">
  {!! Form::label('barcode', 'Barcode:') !!}
  {!! Form::text('barcode', null, ['class' => 'form-control']) !!}
</div>

<!-- Vat Field -->
<div class="form-group col-sm-6">
    {!! Form::label('vat', 'VAT(%):') !!}
    {!! Form::number('vat', null, ['class' => 'form-control','step'=>'any']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6 mt-3">
  <label>Status</label>
  <div class="form-check">
    <input type="hidden" name="status" value="2"/>
     <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($items) @if($items->status == 1) checked @endif @else checked  @endisset/>
     <label for="status" class="pt-0">Is Active</label>

  </div>
</div>

<!-- Detail Field -->
<div class="form-group col-sm-12">
  {!! Form::label('detail', 'Detail:') !!}
  {!! Form::textarea('detail', null, ['class' => 'form-control','rows'=>3]) !!}
</div>
