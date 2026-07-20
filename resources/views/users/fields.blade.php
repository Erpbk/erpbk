

@php
    $superAdminEditOnly = isset($user) && !empty($isSuperAdmin);
@endphp

@if(!$superAdminEditOnly)
<!-- Frist Name Field -->
<div class="form-group col-sm-4">
    {!! Form::label('first_name', 'Frist Name:', ['class' => 'required']) !!}
    {!! Form::text('first_name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<!-- Last Name Field -->
<div class="form-group col-sm-4">
    {!! Form::label('last_name', 'Last Name:') !!}
    {!! Form::text('last_name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<!-- Branches Field  -->
@php
    $selectedBranchIds = old('branch_ids', isset($user) ? $user->branch_ids : []);
    if (! is_array($selectedBranchIds)) {
        $selectedBranchIds = json_decode((string) ($selectedBranchIds ?? '[]'), true) ?: [];
    }
@endphp
<div class="form-group col-sm-4">
    {!! Form::label('branch_ids', 'Branches:',['class' => 'required']) !!}
    <select class="form-select select2" 
            name="branch_ids[]" 
            id="branch_ids"
            multiple >
        <option value="all">All</option>
        @foreach($branches as $id => $name)
            <option value="{{ $id }}" 
                {{ in_array($id, $selectedBranchIds) ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </select>
    <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
</div>



{{--<!-- Phone Field -->
<div class="form-group col-sm-4">
    {!! Form::label('phone', 'Phone:') !!}
    {!! Form::text('phone', null, ['class' => 'form-control', 'maxlength' => 50, 'maxlength' => 50]) !!}
</div>
 <!-- Country Field -->
<div class="form-group col-sm-4">
    {!! Form::label('country', 'Country:') !!}
    {!! Form::select('country', $countries, $country??\App\Helpers\IConstants::COUNTRY, ['class' => 'form-control form-select select2 ']) !!}
</div>

<!-- Cities Field -->
<div class="form-group col-sm-4">
    {!! Form::label('city', 'City:') !!}
    {!! Form::select('city', $cities,null ,['class' => 'select2 form-select ','id'=>'cities']) !!}
</div> 
<!-- Address Field -->
<div class="form-group col-sm-4">
    {!! Form::label('address', 'Address:') !!}
    {!! Form::text('address', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div> --}}



@isset($roles)
<div class="form-group col-sm-4">

  {!! Form::label('roles', 'Role:', ['class' => 'required']) !!}
  <select class="form-control form-select select2" name="roles">
    <option value="">Select Role</option>
    @foreach ($roles as $role)
        <option value="{{ $role }}" @if(($userRole??null) && $role===$userRole) selected @endif>{{ $role?? '-' }}</option>
    @endforeach
  </select>

</div>
@endisset
{{-- @isset($user)
<div class="form-group col-sm-4">
  {!! Form::label('username', 'Username:') !!}
  {!! Form::text('username', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'disabled' => 'disabled']) !!}
</div>
<!-- Email Field -->
<div class="form-group col-sm-4">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'disabled' => 'disabled']) !!}
</div>
@else --}}
{{-- <div class="form-group col-sm-4">
  {!! Form::label('username', 'Username:') !!}
  {!! Form::text('username', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div> 
<!-- Department Field -->
<div class="form-group col-sm-4">
  {!! Form::label('department_id', 'Department:') !!}
  {!! Form::select('department_id', $departments,null ,['class' => 'select2 form-select ','id'=>'department']) !!}
</div>--}}
<!-- Employee Field -->
<div class="form-group col-sm-4">
  {!! Form::label('employee', 'Employee:') !!}
  <select class="form-control form-select select2" name="employee_id">
    <option value="">Select Related Employee</option>
    @foreach ($employees as $employee)
        <option value="{{ $employee->id }}" @if(($user->employee??null) && $user->employee->id===$employee->id) selected @endif>{{ $employee->employee_id .'  '. $employee->name }}</option>
    @endforeach
  </select>
</div>
<!-- Email Field -->
<div class="form-group col-sm-4">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
@else
<div class="form-group col-sm-12">
    <p class="text-muted mb-0">Only the password can be changed for Super Admin users.</p>
</div>
@endif

<!-- password Field -->
<div class="form-group col-sm-4 form-password-toggle">
    {!! Form::label('password', 'Password:', ['class' => isset($user) ? '' : 'required']) !!}
    <div class="input-group input-group-merge">
        {!! Form::password('password', ['class' => 'form-control', 'maxlength' => 255]) !!}
        <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
    </div>
</div>

<div class="form-group col-sm-4 form-password-toggle">
    {!! Form::label('password_confirmation', 'Confirm Password:', ['class' => isset($user) ? '' : 'required']) !!}
    <div class="input-group input-group-merge">
        {!! Form::password('password_confirmation', ['class' => 'form-control', 'maxlength' => 255]) !!}
        <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
    </div>
</div>

@isset($user)
<em class="text-warning mt-2">NOTE: If you dont want to change password leave it blank.</em>
@endisset

@if(!$superAdminEditOnly)
@isset($roles)
<!-- Status Field -->
<div class="form-group col-sm-6 mt-3">
  <div class="form-check">
     <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($user->status) checked @endisset />
     <label for="status" class="pt-0">Is Active</label>

  </div>
</div>
@endisset
@endif

@if(!$superAdminEditOnly && auth()->user()->isAdmin() && isset($emailAccounts))
<div class="form-group col-sm-12 mt-2">
  {!! Form::label('email_account_ids', 'Assigned Email Accounts:') !!}
  <select class="form-control form-select select2" name="email_account_ids[]" multiple data-placeholder="Select email accounts">
    @foreach($emailAccounts as $account)
    <option value="{{ $account->id }}" {{ in_array((int) $account->id, array_map('intval', (array) ($assignedEmailAccountIds ?? [])), true) ? 'selected' : '' }}>
      {{ $account->displayLabel() }}{{ $account->isActive() ? '' : ' (Inactive)' }}
    </option>
    @endforeach
  </select>
  <small class="text-muted">Users can send rider emails only from active accounts assigned here.</small>
</div>
@endif

<script>
$(document).ready(function() {
    $('.select2').select2({
        dropdownParent: $('#modalTopbody'),
        allowClear: true
    });
});
</script>