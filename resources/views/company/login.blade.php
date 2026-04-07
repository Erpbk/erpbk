@php $pageConfigs = ['myLayout' => 'blank']; @endphp
@extends('layouts.blankLayout')

@section('title', __('Login') . ' - ' . ($company->name ?? ''))

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
<style>
  .company-brand { max-height: 60px; max-width: 200px; object-fit: contain; }
  .company-primary { color: {{ $company->primary_color ?? '#696cff' }}; }
</style>
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-4 mt-2">
            <a href="{{ route('company.login-form', ['company_slug' => $companySlug]) }}" class="app-brand-link gap-2 d-flex flex-column align-items-center">
              @if($company->logo)
                <img src="{{ asset('storage/' . $company->logo) }}" alt="{{ $company->name }}" class="company-brand">
              @else
                <span class="app-brand-text demo text-body fw-bold company-primary">{{ $company->name }}</span>
              @endif
            </a>
          </div>
          <h4 class="mb-1 pt-2">{{ __('Welcome to :name', ['name' => $company->name]) }}</h4>
          <p class="mb-4">{{ __('Sign in to your account') }}</p>

          @if($errors->any())
            <div class="alert alert-danger">
              @foreach($errors->all() as $e) {{ $e }} @endforeach
            </div>
          @endif

          <form action="{{ route('company.login', ['company_slug' => $companySlug]) }}" method="post" class="mb-3">
            @csrf
            <div class="mb-3">
              <label for="email" class="form-label">{{ __('Email or Username') }}</label>
              <input type="text" class="form-control" id="email" name="email" value="{{ old('email') }}" autofocus>
            </div>
            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="password">{{ __('Password') }}</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
            </div>
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                <label class="form-check-label" for="remember">{{ __('Remember Me') }}</label>
              </div>
            </div>
            <button type="submit" class="btn btn-primary d-grid w-100">{{ __('Sign in') }}</button>
          </form>

          <p class="text-center mb-1">
            <a href="{{ route('company.find-login') }}">{{ __('Different company?') }}</a>
          </p>
          <p class="text-center">
            <a href="{{ url('/') }}">{{ __('Back to home') }}</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
