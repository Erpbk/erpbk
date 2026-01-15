<!-- reference Field -->
<div class="form-group col-sm-6">
  {!! Form::label('reference', 'Reference:') !!}
  {!! Form::text('reference', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<!-- Payment Type Field -->
<div class="form-group col-sm-6">
  {!! Form::label('amount_type', 'Amount Type:') !!}
  {!! Form::select('amount_type', 
      ['' => 'Select', 'Cash' => 'Cash', 'Online' => 'Online', 'Cheque' => 'Cheque', 'Credit' => 'Credit'], 
      old('amount_type', isset($receipt) ? $receipt->amount_type : ''), 
      ['class' => 'form-control select2']
  ) !!}
</div>

<!-- Bank ID Field -->
@if(!isset($bank) && !isset($receipt))
  <div class="form-group col-sm-6">
    {!! Form::label('bank_id', 'Recieving Account:') !!}
    <select name="bank_id" id="bank_id" class="form-control select2">
      <option value="">Select</option>
      @foreach(\App\Models\Banks::where('status', 1)->get() as $bank)
      <option value="{{ $bank->id }}" {{ old('bank_id', isset($receipt) ? $receipt->bank_id : '') == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
      @endforeach
    </select>
  </div>
@else
  <div class="form-group col-sm-6">
    {!! Form::label('bank_id', 'Recieved By:') !!}
    {!! Form::hidden('bank_id', $bank->id ?? $receipt->bank_id ?? '')!!}
    {!! Form::text('bank-name', $bank->name ?? $receipt->bank->name ?? '-', ['class' => 'form-control', 'readonly' => true]) !!}
  </div>
@endif

<div class="form-group col-sm-6">
  {!! Form::label('payer_account_id', 'Payer Account:') !!}
  <select name="payer_account_id" id="payer_account_id" class="form-control select2">
    <option value="">Select</option>
    @foreach(\App\Models\Accounts::where('status', 1)->get() as $payer)
    <option value="{{ $payer->id }}" {{ old('payer_account_id', isset($receipt) ? $receipt->payer_account_id : '') == $payer->id ? 'selected' : '' }}>{{ $payer->name }}</option>
    @endforeach
  </select>
</div>

<!-- Amount Field -->
<div class="form-group col-sm-6">
  {!! Form::label('amount', 'Amount:') !!}
  {!! Form::number('amount', null, ['class' => 'form-control', 'step' => '0.01']) !!}
</div>
<!-- Date of Receipt Field -->
<div class="form-group col-sm-6">
  {!! Form::label('date_of_receipt', 'Date of Receipt:') !!}
  {!! Form::date('date_of_receipt', null, ['class' => 'form-control']) !!}
</div>
<!-- Billing Month Field -->
<div class="form-group col-sm-6">
  {!! Form::label('billing_month', 'Billing Month:') !!}
  {!! Form::month('billing_month', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>
<!-- Voucher Attachment Field -->
<div class="form-group col-sm-6">
  {!! Form::label('attachment', 'Attachment:') !!}
  {!! Form::file('attachment', ['class' => 'form-control']) !!}
</div>
<!-- Description Field -->
<div class="form-group col-sm-12">
  {!! Form::label('description', 'Description:') !!}
  {!! Form::textarea('description', null, ['class' => 'form-control','rows'=>2]) !!}
</div>
<!-- Status Field -->
<div class="form-group col-sm-6 mt-3">
  <label>Status</label>
  <div class="form-check">
    <input type="hidden" name="status" value="0" />
    <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($receipt) @if($receipt->status == 1) checked @endif @else checked @endisset/>
    <label for="status" class="pt-0">Is Active</label>
  </div>
</div>

<script type="text/javascript">
  function selectHeadAccount(id) {
    if (id) {
      $.ajax({
        type: 'get',
        url: '{{ url("receipts/headbytype") }}/' + id,
        success: function(res) {
          $('#head_account_id').html(res);
        }
      });
    } else {
      $('#head_account_id').html('<option value="">Select</option>');
    }
  }
</script>
<script type="text/javascript">
  function selectAccount(id) {
    if (id) {
      $.ajax({
        type: 'get',
        url: '{{ url("receipts/byparent") }}/' + id,
        success: function(res) {
          $('#account_id').html(res);
        }
      });
    } else {
      $('#account_id').html('<option value="">Select</option>');
    }
  }
  $(document).ready(function() {
    $('.select2').select2({
      dropdownParent: $('#formajax'),
            allowClear: true
    });
  });
</script>
