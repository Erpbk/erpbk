@php
$authPage = 'register';
$branding = $branding ?? \App\Support\AuthBranding::forPage('register');
@endphp
@extends('layouts.authSplit')

@section('title', __('Company Registration'))

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/umd/styles/index.min.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/@form-validation/umd/bundle/popular.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js') }}"></script>
@endsection

@section('auth-form')
<h4 class="mb-1">{{ __('Create your company') }}</h4>
<p class="mb-4 text-muted">{{ __('Step 1 — Account details') }}</p>

@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0 ps-3">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<form id="companyRegisterForm" action="{{ route('company.register.step1.submit') }}" method="post">
  @csrf
  <div class="mb-3">
    <label for="name" class="form-label">{{ __('Company name') }} <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
  </div>
  <div class="mb-3">
    <label for="email" class="form-label">{{ __('Email address') }} <span class="text-danger">*</span></label>
    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
    @error('email')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
  <div class="mb-3">
    <label for="country_id" class="form-label">{{ __('Country') }} <span class="text-danger">*</span></label>
    <select class="form-select select2" id="country_id" name="country_id" required data-placeholder="{{ __('Select country') }}">
      <option value="">{{ __('Select country') }}</option>
      @foreach($countries ?? [] as $id => $name)
        <option value="{{ $id }}" {{ old('country_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
      @endforeach
    </select>
  </div>
  <div class="mb-3">
    <label for="phone" class="form-label">{{ __('Phone number') }} <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" required>
  </div>
  <div class="mb-3 form-password-toggle">
    <label class="form-label" for="password">{{ __('Password') }} <span class="text-danger">*</span></label>
    <div class="input-group input-group-merge">
      <input type="password" id="password" class="form-control" name="password" required minlength="8" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
      <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
    </div>
    <small class="text-muted">{{ __('Minimum 8 characters') }}</small>
  </div>
  <div class="mb-4 form-password-toggle">
    <label class="form-label" for="password_confirmation">{{ __('Confirm password') }} <span class="text-danger">*</span></label>
    <div class="input-group input-group-merge">
      <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" required placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
      <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
    </div>
  </div>
  <button type="submit" class="btn btn-primary d-grid w-100" id="btnSubmit">{{ __('Continue') }}</button>
</form>

<p class="text-center mt-4 mb-0">
  <a href="{{ route('company.login') }}">{{ __('Already have an account? Sign in') }}</a>
</p>

<div class="modal fade" id="otpModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Verify your email') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3">{{ __('We sent a 6-digit code to your email. Enter it below.') }}</p>
        <form id="otpForm">
          @csrf
          <div class="mb-3">
            <label for="otp" class="form-label">{{ __('Verification code') }}</label>
            <input type="text" class="form-control form-control-lg text-center" id="otp" name="otp" maxlength="6" pattern="[0-9]{6}" placeholder="000000" autocomplete="one-time-code">
            <div id="otpError" class="invalid-feedback"></div>
          </div>
          <button type="submit" class="btn btn-primary w-100">{{ __('Verify') }}</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('auth-page-script')
<script>
  (function() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
      $('#country_id').select2({ placeholder: '{{ __("Select country") }}', allowClear: true, width: '100%' });
    }
  })();
  document.getElementById('companyRegisterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.textContent = '{{ __("Sending...") }}';
    var token = form.querySelector('input[name="_token"]');
    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token ? token.value : ''
      }
    })
    .then(function(r) {
      if (r.status === 422) {
        return r.json().then(function(data) {
          var msg = (data.errors && data.errors.email && data.errors.email[0]) || data.message || '{{ __("Please fix the errors and try again.") }}';
          alert(msg);
          return { success: false };
        });
      }
      if (r.status === 419) {
        alert('{{ __("Session expired. Please refresh the page and try again.") }}');
        return { success: false };
      }
      return r.json();
    })
    .then(function(data) {
      if (data && data.success) {
        new bootstrap.Modal(document.getElementById('otpModal')).show();
        document.getElementById('otp').value = '';
        document.getElementById('otp').focus();
      }
    })
    .catch(function() { alert('{{ __("Network error. Try again.") }}'); })
    .finally(function() {
      btn.disabled = false;
      btn.textContent = '{{ __("Continue") }}';
    });
  });

  document.getElementById('otpForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var otp = document.getElementById('otp').value;
    var errEl = document.getElementById('otpError');
    if (otp.length !== 6) {
      errEl.textContent = '{{ __("Enter 6 digits") }}';
      document.getElementById('otp').classList.add('is-invalid');
      return;
    }
    document.getElementById('otp').classList.remove('is-invalid');
    errEl.textContent = '';
    var token = this.querySelector('input[name="_token"]');
    fetch('{{ route("company.register.otp.verify") }}', {
      method: 'POST',
      body: new FormData(this),
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token ? token.value : ''
      }
    })
    .then(r => r.json())
    .then(data => {
      if (data.success && data.redirect) {
        window.location.href = data.redirect;
      } else {
        document.getElementById('otp').classList.add('is-invalid');
        errEl.textContent = data.message || '{{ __("Invalid or expired OTP.") }}';
      }
    });
  });
</script>
@endpush
