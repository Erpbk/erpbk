@php $pageConfigs = ['myLayout' => 'blank']; @endphp
@extends('layouts.blankLayout')

@section('title', __('Company Registration'))

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/umd/styles/index.min.css') }}" />
@endsection

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/@form-validation/umd/bundle/popular.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js') }}"></script>
@endsection

@section('content')
<div class="container-xl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-4 mt-2">
            <a href="{{ url('/') }}" class="app-brand-link gap-2">
              <span class="app-brand-text demo text-body fw-bold">{{ config('app.name') }}</span>
            </a>
          </div>
          <h4 class="mb-1 pt-2">{{ __('Company Registration') }}</h4>
          <p class="mb-4">{{ __('Step 1: Create your company account') }}</p>

          @if(session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
          @endif
          @if($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
          </div>
          @endif

          <form id="companyRegisterForm" class="mb-3" action="{{ route('company.register.step1.submit') }}" method="post">
            @csrf
            <div class="mb-3">
              <label for="name" class="form-label">{{ __('Company Name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">{{ __('Email Address') }} <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
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
              <label for="phone" class="form-label">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" required>
            </div>
            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="password">{{ __('Password') }} <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control" name="password" required minlength="8" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
              <small class="text-muted">{{ __('Min 8 characters') }}</small>
            </div>
            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="password_confirmation">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" required placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
            </div>
            <button type="submit" class="btn btn-primary d-grid w-100" id="btnSubmit">{{ __('Continue') }}</button>
          </form>

          <p class="text-center mb-1">
            <a href="{{ route('company.find-login') }}">{{ __('Already have an account? Sign in') }}</a>
          </p>
          <p class="text-center">
            <a href="{{ url('/') }}">{{ __('Back to home') }}</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- OTP Modal --}}
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

@section('page-script')
<script>
  (function() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
      $('#country_id').select2({
        placeholder: '{{ __("Select country") }}',
        allowClear: true,
        width: '100%'
      });
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
          'X-CSRF-TOKEN': token ? token.value : (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        }
      })
      .then(function(r) {
        if (r.status === 419) {
          return r.text().then(function() { return { success: false, message: '{{ __("Session expired. Please refresh the page and try again.") }}' }; });
        }
        return r.json();
      })
      .then(function(data) {
        if (data.success) {
          var modal = new bootstrap.Modal(document.getElementById('otpModal'));
          modal.show();
          document.getElementById('otp').value = '';
          document.getElementById('otp').focus();
        } else {
          alert(data.message || '{{ __("Something went wrong.") }}');
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
    var form = this;
    var otp = document.getElementById('otp').value;
    var errEl = document.getElementById('otpError');
    if (otp.length !== 6) {
      errEl.textContent = '{{ __("Enter 6 digits") }}';
      document.getElementById('otp').classList.add('is-invalid');
      return;
    }
    document.getElementById('otp').classList.remove('is-invalid');
    errEl.textContent = '';
    var token = form.querySelector('input[name="_token"]');
    fetch('{{ route("company.register.otp.verify") }}', {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': token ? token.value : (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
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
      })
      .catch(() => {
        errEl.textContent = '{{ __("Network error.") }}';
        document.getElementById('otp').classList.add('is-invalid');
      });
  });
</script>
@endsection