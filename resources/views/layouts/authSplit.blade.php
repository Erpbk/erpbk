@php
$pageConfigs = ['myLayout' => 'blank'];
$branding = $branding ?? \App\Support\AuthBranding::forPage($authPage ?? 'login');
$bgStyle = 'background-color: ' . e($branding['bg_color'] ?? '#1e3a5f') . ';';
if (!empty($branding['bg_image_url'])) {
$bgStyle .= " background-image: url('" . e($branding['bg_image_url']) . "'); background-size: cover; background-position: center;";
}
@endphp
@extends('layouts.blankLayout')

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
<style>
  .auth-split {
    min-height: 100vh;
    display: flex;
    flex-wrap: wrap;
  }

  .auth-split-brand {
    flex: 1 1 42%;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2.5rem;
    color: #fff;
    position: relative;
    overflow: hidden;
  }

  .auth-split-brand::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(145deg, rgba(0, 0, 0, .35) 0%, rgba(0, 0, 0, .15) 50%, rgba(255, 255, 255, .05) 100%);
    pointer-events: none;
  }

  .auth-split-brand-inner {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 360px;
  }

  .auth-split-logo {
    max-height: 120px;
    max-width: 280px;
    width: auto;
    object-fit: contain;
    margin-bottom: 1.5rem;
    filter: drop-shadow(0 8px 24px rgba(0, 0, 0, .25));
  }

  .auth-split-tagline {
    font-size: 1.125rem;
    font-weight: 500;
    opacity: .92;
    line-height: 1.5;
    margin: 0;
  }

  .auth-split-form-wrap {
    flex: 1 1 10%;
    min-width: 320px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.5rem;
    background: #f4f6fb;
  }

  .auth-split-card {
    width: 100%;
    max-width: 440px;
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 4px 24px rgba(15, 23, 42, .08), 0 1px 3px rgba(15, 23, 42, .06);
    padding: 2.25rem 2rem;
  }

  .auth-split-card h4 {
    font-weight: 600;
    color: #1e293b;
  }

  .auth-split-card .form-label {
    font-weight: 500;
    color: #475569;
  }

  .auth-split-card .btn-primary {
    padding: .65rem 1rem;
    font-weight: 600;
    border-radius: .5rem;
  }

  @media (max-width: 991.98px) {
    .auth-split-brand {
      flex: 1 1 100%;
      min-height: 200px;
      padding: 2rem 1.5rem;
    }

    .auth-split-logo {
      max-height: 72px;
    }
  }
</style>
@stack('auth-page-style')
@endsection

@section('content')
<div class="auth-split">
  <aside class="auth-split-brand" style="{{ $bgStyle }}">
    <div class="auth-split-brand-inner">
      @if(!empty($branding['logo_url']))
      <img src="{{ $branding['logo_url'] }}" alt="{{ config('app.name') }}" class="auth-split-logo">
      @else
      <div class="mb-4">@include('_partials.macros', ['height' => 72, 'withbg' => 'fill: #fff;'])</div>
      @endif
      @if(!empty($branding['tagline']))
      <p class="auth-split-tagline">{{ $branding['tagline'] }}</p>
      @endif
    </div>
  </aside>
  <div class="auth-split-form-wrap">
    <div class="auth-split-card">
      @yield('auth-form')
    </div>
  </div>
</div>
@endsection

@section('page-script')
@stack('auth-page-script')
@endsection