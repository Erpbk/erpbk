@php
$authPage = 'login';
$branding = \App\Support\AuthBranding::forPage('login');
@endphp
@extends('layouts.authSplit')

@section('title', __('Pending approval'))

@section('auth-form')
<div class="text-center">
  <div class="mb-4">
    <i class="ti ti-clock-hour-4 text-primary" style="font-size: 3.5rem;"></i>
  </div>
  <h4 class="mb-2">{{ __('Registration complete') }}</h4>
  <p class="text-muted mb-4">
    {{ __('Your company is pending admin approval. You will be able to sign in once an administrator approves your account.') }}
  </p>
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('message'))
    <div class="alert alert-info">{{ session('message') }}</div>
  @endif
  <a href="{{ route('company.login') }}" class="btn btn-primary w-100">{{ __('Go to sign in') }}</a>
</div>
@endsection
