@extends('layouts.app')
@section('title', __('Add WhatsApp Template'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm flex-grow-1 me-3">
                <h3 class="text-white mb-0 fw-bold">{{ __('Add WhatsApp Template') }}</h3>
            </div>
            <a href="{{ route('admin.whatsapp-templates.index') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Back') }}</a>
        </div>
    </div>
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.whatsapp-templates.store') }}">
                @csrf
                @include('admin.whatsapp_templates._form', ['injectionHints' => $injectionHints])
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    <a href="{{ route('admin.whatsapp-templates.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
