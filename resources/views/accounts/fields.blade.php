<script src="{{ asset('js/modal_custom.js') }}"></script>

<!-- Account Type Field -->
<div class="form-group col-sm-6">
  {!! Form::label('account_type', 'Account Type:') !!}
  {!! Form::select('account_type', App\Helpers\Accounts::AccountTypes(),null, ['class' => 'form-control form-select select2']) !!}
</div>
<div class="form-group col-sm-6"></div>
<!-- Account Name Field -->
<div class="form-group col-sm-6">
  {!! Form::label('name', 'Account Name:') !!}
  {!! Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 100, 'maxlength' => 100]) !!}
</div>
<div class="form-group col-sm-6"></div>
<!-- Account Code Field -->
@if(Route::currentRouteName() == 'accounts.edit' && isset($accounts->id))
<div class="form-group col-sm-6">
  {!! Form::label('account_code', 'Account Code:') !!}
  {!! Form::text('account_code', $accounts->account_code, ['class' => 'form-control']) !!}
</div>
@endif

<!-- Parent Account Id Field -->
<div class="form-group col-sm-8">
  {!! Form::label('parent_id', 'Parent Account:') !!}
  <select name="parent_id" class="form-control form-select select2">
    <option value="">Select</option>
    {!! App\Helpers\Accounts::dropdown($parents,$accounts->parent_id??null) !!}
  </select>
  {{-- {!! Form::select('parent_account_id', $parents,null, ['class' => 'form-control form-select select2']) !!} --}}
</div>

<!-- Opening Balance Field -->
<div class="form-group col-sm-6">
  {!! Form::label('opening_balance', 'Opening Balance:') !!}
  {!! Form::number('opening_balance', null, ['class' => 'form-control','step'=>'any']) !!}
</div>

<div class="form-group col-sm-6"></div>
<!-- Status Field -->
<div class="form-group col-sm-6">
  <label>Status</label>
  <div class="form-check">
    <input type="hidden" name="status" value="2" />
    <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($accounts) @if($accounts->status == 1) checked @endif @else checked @endisset/>
    <label for="status" class="pt-0">Is Active</label>

  </div>
</div>

<div class="form-group col-sm-12">
  {!! Form::label('notes', 'Notes:') !!}
  {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 4]) !!}

</div>
{{-- <div class="form-check form-switch mb-2">
  <input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
  <label class="form-check-label" for="flexSwitchCheckDefault">Default switch checkbox input</label>
</div> --}}