@php $pageConfigs = ['myLayout' => 'blank']; @endphp
@extends('layouts.blankLayout')

@section('title', __('Company sign in'))

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-4 mt-2">
            <a href="{{ url('/') }}" class="app-brand-link gap-2">
              <span class="app-brand-text demo text-body fw-bold">{{ config('app.name') }}</span>
            </a>
          </div>
          <h4 class="mb-1 pt-2">{{ __('Sign in to your company') }}</h4>
          <p class="mb-4 text-muted">{{ __('Enter your registered company name. We will take you to the correct login page.') }}</p>

          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
              </ul>
            </div>
          @endif

          <form class="mb-3" action="{{ route('company.find-login.submit') }}" method="post">
            @csrf
            <div class="mb-3">
              <label for="company_name" class="form-label">{{ __('Company name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="company_name" name="company_name" value="{{ old('company_name') }}" required autofocus autocomplete="organization">
            </div>
            <button type="submit" class="btn btn-primary d-grid w-100">{{ __('Continue') }}</button>
          </form>

          <p class="text-center mb-0">
            <a href="{{ route('company.register') }}">{{ __('Register a new company') }}</a>
            <span class="text-muted px-1">·</span>
            <a href="{{ url('/') }}">{{ __('Back to home') }}</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
