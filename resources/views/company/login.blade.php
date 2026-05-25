@php $authPage = 'login'; @endphp
@extends('layouts.authSplit')

@section('title', __('Login'))

@section('auth-form')
<h4 class="mb-1">{{ __('Welcome back') }}</h4>
<p class="text-muted mb-4">{{ __('Sign in with your email and password') }}</p>

@if($errors->any())
  <div class="alert alert-danger">
    @foreach($errors->all() as $e)
      <div>{{ $e }}</div>
    @endforeach
  </div>
@endif

<form action="{{ route('company.login.submit') }}" method="post" class="mb-3">
  @csrf
  <div class="mb-3">
    <label for="email" class="form-label">{{ __('Email') }}</label>
    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="{{ __('you@company.com') }}" required autofocus autocomplete="email">
    @error('email')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
  <div class="mb-3 form-password-toggle">
    <div class="d-flex justify-content-between align-items-center">
      <label class="form-label mb-0" for="password">{{ __('Password') }}</label>
    </div>
    <div class="input-group input-group-merge mt-1">
      <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required autocomplete="current-password">
      <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
    </div>
    @error('password')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  </div>
  <div class="mb-4">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
      <label class="form-check-label" for="remember">{{ __('Remember me') }}</label>
    </div>
  </div>
  <button type="submit" class="btn btn-primary d-grid w-100">{{ __('Sign in') }}</button>
</form>

<p class="text-center text-muted small mb-2">{{ __('Forgot your password? Contact your administrator.') }}</p>
<p class="text-center mb-0">
  <span class="text-muted">{{ __("Don't have an account?") }}</span>
  <a href="{{ route('company.register') }}">{{ __('Register your company') }}</a>
</p>
@endsection
