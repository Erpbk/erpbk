<script src="{{ asset('js/modal_custom.js') }}"></script>

<!-- Frist Name Field -->
<div class="form-group col-sm-4">
    {!! Form::label('first_name', 'Frist Name:') !!}
    {!! Form::text('first_name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<!-- Last Name Field -->
<div class="form-group col-sm-4">
    {!! Form::label('last_name', 'Last Name:') !!}
    {!! Form::text('last_name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>



<!-- Phone Field -->
<div class="form-group col-sm-4">
    {!! Form::label('phone', 'Phone:') !!}
    {!! Form::text('phone', null, ['class' => 'form-control', 'maxlength' => 50, 'maxlength' => 50]) !!}
</div>
{{-- <!-- Country Field -->
<div class="form-group col-sm-4">
    {!! Form::label('country', 'Country:') !!}
    {!! Form::select('country', $countries, $country??\App\Helpers\IConstants::COUNTRY, ['class' => 'form-control form-select select2 ']) !!}
</div>

<!-- Cities Field -->
<div class="form-group col-sm-4">
    {!! Form::label('city', 'City:') !!}
    {!! Form::select('city', $cities,null ,['class' => 'select2 form-select ','id'=>'cities']) !!}
</div> --}}
<!-- Address Field -->
<div class="form-group col-sm-4">
    {!! Form::label('address', 'Address:') !!}
    {!! Form::text('address', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>



@isset($roles)
<div class="form-group col-sm-4">

  {!! Form::label('roles', 'Role:') !!}
  {!! Form::select('roles', $roles, $userRole??null, ['class' => 'form-control form-select select2 ']) !!}

</div>
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
</div> --}}
<!-- Department Field -->
<div class="form-group col-sm-4">
  {!! Form::label('department_id', 'Department:') !!}
  {!! Form::select('department_id', $departments,null ,['class' => 'select2 form-select ','id'=>'department']) !!}
</div>
<!-- Email Field -->
<div class="form-group col-sm-4">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
{{-- @endisset --}}

<!-- password Field -->
<div class="form-group col-sm-4">
    {!! Form::label('password', 'Password:') !!}

<div class="input-group" id="show_hide_password">
    {!! Form::password('password', ['class' => 'form-control',  'maxlength' => 255, 'maxlength' => 255]) !!}
    <div class="input-group-text">
        <a href="#" role="button" class="text-dark"><i class="ti ti-eye-off" aria-hidden="true"></i></a>
    </div>
</div>
</div>

<div class="form-group col-sm-4">
    {!! Form::label('password_confirmation', 'Confirm Password:') !!}
    <div class="input-group" id="show_hide_confirm_password">
        {!! Form::password('password_confirmation', ['class' => 'form-control',  'maxlength' => 255, 'maxlength' => 255]) !!}
        <div class="input-group-text">
            <a href="#" role="button" class="text-dark"><i class="ti ti-eye-off" aria-hidden="true"></i></a>
        </div>
    </div>
</div>

    @isset($user)
    <em>NOTE: If you dont want to change password leave it blank.</em>
    @endisset
    @endisset

    <!-- Bio Field -->
<div class="form-group col-sm-12">
    {!! Form::label('bio', 'Bio:') !!}
    {!! Form::textarea('bio', null, ['class' => 'form-control', 'rows' => 4]) !!}
</div>

@isset($roles)
<!-- Status Field -->
<div class="form-group col-sm-6 mt-3">
  <div class="form-check">
     <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($user->status) checked @endisset />
     <label for="status" class="pt-0">Is Active</label>

  </div>
</div>
@endisset
