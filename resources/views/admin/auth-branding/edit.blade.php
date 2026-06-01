@extends('layouts.app')
@section('title', __('Login & Register Pages'))

@section('content')
@include('flash::message')
<div class="container-fluid px-4">
  <div class="row mb-4">
    <div class="col-12">
      <h4 class="mb-1">{{ __('Login & Register Page Branding') }}</h4>
      <p class="text-muted mb-0">{{ __('Upload logos and customize the public sign-in and registration pages.') }}</p>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <form action="{{ route('admin.auth-branding.update') }}" method="post" enctype="multipart/form-data">
            @csrf

            <div class="row g-4 mb-4">
              <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Login page logo') }}</label>
                @if($branding['login_logo_url'])
                  <div class="mb-2 p-3 bg-light rounded text-center">
                    <img src="{{ $branding['login_logo_url'] }}" alt="" style="max-height: 80px; max-width: 100%; object-fit: contain;">
                  </div>
                @endif
                <input type="file" name="login_logo" class="form-control" accept="image/*">
                <small class="text-muted">{{ __('PNG, JPG, WebP or SVG. Max 2 MB.') }}</small>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Register page logo') }}</label>
                @if($branding['register_logo_url'])
                  <div class="mb-2 p-3 bg-light rounded text-center">
                    <img src="{{ $branding['register_logo_url'] }}" alt="" style="max-height: 80px; max-width: 100%; object-fit: contain;">
                  </div>
                @endif
                <input type="file" name="register_logo" class="form-control" accept="image/*">
                <small class="text-muted">{{ __('Shown on company registration steps.') }}</small>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Left panel tagline') }}</label>
              <input type="text" name="tagline" class="form-control" value="{{ old('tagline', $branding['tagline']) }}" placeholder="{{ config('app.name') }}">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">{{ __('Background color') }}</label>
              <input type="color" name="bg_color" class="form-control form-control-color w-100" value="{{ old('bg_color', $branding['bg_color']) }}">
              <small class="text-muted">{{ __('Used when no background image is set, or as overlay base.') }}</small>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">{{ __('Background image') }}</label>
              @if($branding['bg_image_url'])
                <div class="mb-2 rounded overflow-hidden" style="max-height: 120px;">
                  <img src="{{ $branding['bg_image_url'] }}" alt="" class="w-100" style="object-fit: cover; max-height: 120px;">
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="remove_bg_image" value="1" id="remove_bg_image">
                  <label class="form-check-label" for="remove_bg_image">{{ __('Remove background image') }}</label>
                </div>
              @endif
              <input type="file" name="bg_image" class="form-control" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary">{{ __('Save branding') }}</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
