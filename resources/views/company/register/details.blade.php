@php
$authPage = 'register';
$branding = \App\Support\AuthBranding::forPage('register');
@endphp
@extends('layouts.authSplit')

@section('title', __('Company details'))

@section('auth-form')
<h4 class="mb-1">{{ __('Company details') }}</h4>
<p class="mb-4 text-muted">{{ __('Step 3 — Address and tax information') }}</p>

@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0 ps-3">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<form action="{{ route('company.register.details.submit') }}" method="post">
  @csrf
  <div class="mb-3">
    <label for="country" class="form-label">{{ __('Country') }}</label>
    <input type="text" class="form-control" id="country" name="country" value="{{ old('country', $step1['country'] ?? '') }}" readonly>
  </div>
  <div class="mb-3">
    <label for="city" class="form-label">{{ __('City') }} <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="city" name="city" value="{{ old('city') }}" required>
  </div>
  <div class="mb-3">
    <label for="address" class="form-label">{{ __('Full address') }} <span class="text-danger">*</span></label>
    <textarea class="form-control" id="address" name="address" rows="3" required>{{ old('address') }}</textarea>
  </div>
  <div class="mb-3">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="is_taxpayer" name="is_taxpayer" value="1" {{ old('is_taxpayer') ? 'checked' : '' }}>
      <label class="form-check-label" for="is_taxpayer">{{ __('Registered taxpayer') }}</label>
    </div>
  </div>
  <div id="taxFields" class="{{ old('is_taxpayer') ? '' : 'd-none' }}">
    <div class="mb-3">
      <label for="ntn_number" class="form-label">{{ __('NTN number') }}</label>
      <input type="text" class="form-control" id="ntn_number" name="ntn_number" value="{{ old('ntn_number') }}">
    </div>
    <div class="mb-3">
      <label for="tax_registration_date" class="form-label">{{ __('Tax registration date') }}</label>
      <input type="date" class="form-control" id="tax_registration_date" name="tax_registration_date" value="{{ old('tax_registration_date') }}">
    </div>
  </div>
  <button type="submit" class="btn btn-primary d-grid w-100">{{ __('Complete registration') }}</button>
</form>
@endsection

@push('auth-page-script')
<script>
  document.getElementById('is_taxpayer')?.addEventListener('change', function() {
    document.getElementById('taxFields').classList.toggle('d-none', !this.checked);
  });
</script>
@endpush
