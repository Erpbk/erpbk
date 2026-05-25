@php
$authPage = 'register';
$branding = \App\Support\AuthBranding::forPage('register');
@endphp
@extends('layouts.authSplit')

@section('title', __('Verify email'))

@section('auth-form')
<h4 class="mb-1">{{ __('Verify your email') }}</h4>
<p class="mb-4 text-muted">{{ __('Enter the 6-digit code sent to your email.') }}</p>

@if($errors->any())
  <div class="alert alert-danger">
    @foreach($errors->all() as $e) {{ $e }} @endforeach
  </div>
@endif

<form action="{{ route('company.register.otp.verify') }}" method="post">
  @csrf
  <div class="mb-4">
    <label for="otp" class="form-label">{{ __('Verification code') }}</label>
    <input type="text" class="form-control form-control-lg text-center" id="otp" name="otp" maxlength="6" pattern="[0-9]{6}" placeholder="000000" autofocus autocomplete="one-time-code">
  </div>
  <button type="submit" class="btn btn-primary d-grid w-100 mb-3">{{ __('Verify') }}</button>
</form>

<p class="text-center mb-0">
  <a href="{{ route('company.register') }}">{{ __('Back to registration') }}</a>
</p>
@endsection
