@php $pageConfigs = ['myLayout' => 'blank']; @endphp
@extends('layouts.blankLayout')

@section('title', __('Company details'))

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">
      <div class="card">
        <div class="card-body">
          <h4 class="mb-1">{{ __('Company details') }}</h4>
          <p class="mb-4">{{ __('Step 3: Add your company address and tax information.') }}</p>

          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('company.register.details.submit') }}" method="post">
            @csrf
            <div class="mb-3">
              <label for="country" class="form-label">{{ __('Country') }}</label>
              <input type="text" class="form-control" id="country" name="country" value="{{ $step1['country'] ?? '' }}" readonly>
            </div>
            <div class="mb-3">
              <label for="city" class="form-label">{{ __('City') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="city" name="city" value="{{ old('city') }}" required>
            </div>
            <div class="mb-3">
              <label for="address" class="form-label">{{ __('Full Address') }} <span class="text-danger">*</span></label>
              <textarea class="form-control" id="address" name="address" rows="2" required>{{ old('address') }}</textarea>
            </div>

            <div class="border rounded p-3 mb-4">
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="is_taxpayer" name="is_taxpayer" value="1" {{ old('is_taxpayer') ? 'checked' : '' }}>
                <label class="form-check-label" for="is_taxpayer">{{ __('Are you a Taxpayer?') }}</label>
              </div>
              <div id="taxpayerFields" style="display: {{ old('is_taxpayer') ? 'block' : 'none' }};">
                <div class="mb-3">
                  <label for="ntn_number" class="form-label">{{ __('NTN Number') }}</label>
                  <input type="text" class="form-control" id="ntn_number" name="ntn_number" value="{{ old('ntn_number') }}">
                </div>
                <div class="mb-3">
                  <label for="tax_registration_date" class="form-label">{{ __('Tax Registration Date') }}</label>
                  <input type="date" class="form-control" id="tax_registration_date" name="tax_registration_date" value="{{ old('tax_registration_date') }}">
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">{{ __('Complete registration') }}</button>
          </form>

          <p class="text-center mt-3">
            <a href="{{ route('company.register.otp') }}">{{ __('Back') }}</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('is_taxpayer').addEventListener('change', function() {
  document.getElementById('taxpayerFields').style.display = this.checked ? 'block' : 'none';
});
</script>
@endsection
