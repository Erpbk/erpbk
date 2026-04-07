@extends('layouts.app')
@section('title', __('Admin Dashboard'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm">
                <h3 class="text-white mb-0 fw-bold">{{ __('Admin Dashboard') }}</h3>
                <p class="text-white-50 mb-0">{{ __('Overview of admin panel data') }}</p>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">{{ __('Companies') }}</h6>
                    <h4 class="mb-0">{{ $stats['companies_total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">{{ __('Pending Companies') }}</h6>
                    <h4 class="mb-0 text-warning">{{ $stats['companies_pending'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">{{ __('Admin Users') }}</h6>
                    <h4 class="mb-0">{{ $stats['admin_users_total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">{{ __('Blogs') }}</h6>
                    <h4 class="mb-0">{{ $stats['blogs_total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">{{ __('Testimonials') }}</h6>
                    <h4 class="mb-0">{{ $stats['testimonials_total'] }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

