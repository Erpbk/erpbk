@extends('layouts.app')
@section('title', __('Edit WhatsApp Number'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm flex-grow-1 me-3">
                <h3 class="text-white mb-0 fw-bold">{{ __('Edit WhatsApp Number') }}</h3>
            </div>
            <a href="{{ route('admin.whatsapp-numbers.index') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Back') }}</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.whatsapp-numbers.update', $number) }}">
                @csrf
                @method('PUT')
                @include('admin.whatsapp_numbers._form', [
                    'isEdit' => true,
                    'number' => $number,
                    'companies' => $companies,
                ])
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                    <a href="{{ route('admin.whatsapp-numbers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">{{ __('Send test template') }}</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.whatsapp-numbers.test-template', $number) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required" for="to_phone">{{ __('To phone') }}</label>
                        <input type="text" name="to_phone" id="to_phone" class="form-control" required maxlength="40" placeholder="+923…">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="template_name">{{ __('Template name') }}</label>
                        <input type="text" name="template_name" id="template_name" class="form-control" required maxlength="128">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="template_language">{{ __('Language') }}</label>
                        <input type="text" name="template_language" id="template_language" class="form-control" value="en" maxlength="16">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100">{{ __('Send test') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
