@php $pageConfigs = ['myLayout' => 'blank']; @endphp
@extends('layouts.blankLayout')

@section('title', __('Verify email'))

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">
      <div class="card">
        <div class="card-body">
          <h4 class="mb-1">{{ __('Verify your email') }}</h4>
          <p class="mb-4">{{ __('Enter the 6-digit code sent to your email.') }}</p>

          @if($errors->any())
            <div class="alert alert-danger">
              @foreach($errors->all() as $e) {{ $e }} @endforeach
            </div>
          @endif

          <form action="{{ route('company.register.otp.verify') }}" method="post">
            @csrf
            <div class="mb-3">
              <label for="otp" class="form-label">{{ __('Verification code') }}</label>
              <input type="text" class="form-control form-control-lg text-center" id="otp" name="otp" maxlength="6" pattern="[0-9]{6}" placeholder="000000" autofocus autocomplete="one-time-code">
            </div>
            <button type="submit" class="btn btn-primary w-100">{{ __('Verify') }}</button>
          </form>

          <p class="text-center mt-3">
            <a href="{{ route('company.register') }}">{{ __('Start over') }}</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
