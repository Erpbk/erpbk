@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts.blankLayout')

@section('title', 'Login')
<link rel="apple-touch-icon" href="{{asset('assets/img/apple-touch.png')}}">

@section('vendor-style')
<!-- Vendor -->
<link rel="stylesheet" href="{{asset('assets/vendor/libs/@form-validation/umd/styles/index.min.css')}}" />
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-auth.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/@form-validation/umd/bundle/popular.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/@form-validation/umd/plugin-auto-focus/index.min.js')}}"></script>
@endsection

@section('page-script')
{{-- <script src="{{asset('assets/js/pages-auth.js')}}"></script>
--}}@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">
      <!-- Login -->
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center mb-4 mt-2">
            <a href="{{route('home')}}" class="app-brand-link gap-2">
              <span class="app-brand-logo">@include('_partials.macros',["height"=>60,"withbg"=>'fill: #fff;'])</span>
              {{-- <span class="app-brand-text demo text-body fw-bold ms-1">{{config('variables.templateName')}}</span>
              --}} </a>
          </div>
          <!-- /Logo -->
          {{-- <h4 class="mb-1 pt-2">Welcome to {{env('APP_NAME')}}! </h4>
          <p class="mb-4">Please sign-in to your account and start the adventure</p> --}}
          @if($errors->any())
          <div class="alert alert-danger">

            @foreach ($errors->all() as $error)
            {{ $error }}
            @endforeach

          </div>
          @endif

          <form id="formAuthentication" class="mb-3" action="{{url('/login')}}" method="post">

            @csrf
            <div class="mb-3">
              <label for="email" class="form-label">Email or Username</label>
              <input type="text" class="form-control" id="email-username" name="email" value="{{ old('email') }}" placeholder="Enter your email or username" autofocus>
              @error('email')
              <span class="error invalid-feedback">{{ $message }}</span>
              @enderror
            </div>
            <div class="mb-3 form-password-toggle">
              <div class="d-flex justify-content-between">
                <label class="form-label" for="password">Password</label>
                {{-- <a href="{{ route('password.request') }}">
                <small>Forgot Password?</small>
                </a> --}}
              </div>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control  @error('password') is-invalid @enderror" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                @error('password')
                <span class="error invalid-feedback">{{ $message }}</span>
                @enderror
              </div>
            </div>
            <div class="mb-3">
              <div class="form-check">
                <label class="form-check-label" for="remember-me">
                  <input class="form-check-input" type="checkbox" id="remember-me" name="remember" />
                  Remember Me
                </label>
              </div>
            </div>
            <div class="mb-3 ">
              <button class="btn btn-primary d-grid w-100 mt-5" type="submit">Sign in</button>
            </div>
          </form>

          <p class="text-center">
            <span>Forgot Password? Please contact the administrator to reset.</span>
            {{-- <span>New on our platform?</span>
            <a href="{{url('/register')}}">
            <span>Register Now</span>
            </a> --}}
          </p>

          {{-- <div class="divider my-4">
            <div class="divider-text">Visit us online</div>
          </div>

          <div class="d-flex justify-content-center">
            <a href="#" class="btn btn-icon btn-label-facebook me-3">
             <i class="fa-solid fa-globe"></i>
            </a>
            <a href="#" class="btn btn-icon btn-label-google-plus me-3">
              <i class="tf-icons fa-brands fa-youtube fs-5"></i>
            </a>
            <a href="#" class="btn btn-icon btn-label-facebook me-3">
              <i class="tf-icons fa-brands fa-facebook-f fs-5"></i>
            </a>



            <a href="#" class="btn btn-icon btn-label-twitter">
              <i class="tf-icons fa-brands fa-linkedin fs-5"></i>
            </a>
          </div> --}}
        </div>
      </div>
      <!-- /Register -->
    </div>
  </div>
</div>
@endsection