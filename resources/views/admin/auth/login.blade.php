@php
    $pageConfigs = $pageConfigs ?? ['myLayout' => 'blank'];
@endphp

@extends('layouts.blankLayout')

@section('title', __('Admin Login'))

@section('content')
<div class="container">
  <div class="authentication-wrapper authentication-basic container-p-y" style=" max-width: 25%; margin: auto; ">
    <div class="authentication-inner py-4">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-4 mt-2">
            <a href="{{ url('/admin/companies') }}" class="app-brand-link">
              <span class="app-brand-logo">@include('_partials.macros',["height"=>60,"withbg"=>'fill: #fff;'])</span>
            </a>
          </div>

          @if($errors->any())
          <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
              <div>{{ $error }}</div>
            @endforeach
          </div>
          @endif

          <form id="adminLoginForm" class="mb-3" action="{{ route('admin.login.submit') }}" method="post">
            @csrf

            <div class="mb-3">
              <label for="email" class="form-label">{{ __('Email or Username') }}</label>
              <input type="text" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Enter your email or username') }}" autofocus>
              @error('email')
                <span class="error invalid-feedback">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="password">{{ __('Password') }}</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
              @error('password')
                <span class="error invalid-feedback">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-3">
              <button class="btn btn-primary d-grid w-100 mt-3" type="submit">{{ __('Sign in') }}</button>
            </div>
          </form>
          <p class="text-center text-muted">
            {{ __('For security, only administrators can access this portal.') }}
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

