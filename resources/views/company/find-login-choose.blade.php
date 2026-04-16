@php $pageConfigs = ['myLayout' => 'blank']; @endphp
@extends('layouts.blankLayout')

@section('title', __('Choose your company'))

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
            <a href="{{ route('company.find-login') }}" class="app-brand-link gap-2">
              <span class="app-brand-text demo text-body fw-bold">{{ config('app.name') }}</span>
            </a>
          </div>
          <h4 class="mb-1 pt-2">{{ __('Multiple companies match') }}</h4>
          <p class="mb-4 text-muted">{{ __('Pick the one you want to sign in to.') }}</p>

          <div class="list-group list-group-flush mb-4">
            @foreach($companies as $company)
              <a href="{{ route('company.login-form', ['company_slug' => $company->slug]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                <span class="fw-medium">{{ $company->name }}</span>
                <span class="ti ti-chevron-right text-muted"></span>
              </a>
            @endforeach
          </div>

          <p class="text-center mb-0">
            <a href="{{ route('company.find-login') }}">{{ __('Try a different name') }}</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
