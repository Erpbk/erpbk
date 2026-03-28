@php $pageConfigs = ['myLayout' => 'blank']; @endphp
@extends('layouts.blankLayout')

@section('title', __('Pending approval'))

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">
      <div class="card">
        <div class="card-body text-center">
          <div class="mb-4">
            <i class="ti ti-clock-hour-4 text-primary" style="font-size: 4rem;"></i>
          </div>
          <h4 class="mb-2">{{ __('Registration complete') }}</h4>
          <p class="text-muted mb-4">
            {{ __('Your company is pending admin approval. You will be able to access the system once an administrator approves your account. We will notify you by email when approved.') }}
          </p>
          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif
          @if(session('message'))
            <div class="alert alert-info">{{ session('message') }}</div>
          @endif
          <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            <a href="{{ route('company.find-login') }}" class="btn btn-outline-primary">{{ __('Sign in') }}</a>
            <a href="{{ url('/') }}" class="btn btn-primary">{{ __('Back to home') }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
