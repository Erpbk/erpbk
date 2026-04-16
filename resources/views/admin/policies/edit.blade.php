@extends('layouts.app')
@section('title', $moduleLabel)

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12 d-flex align-items-center justify-content-between gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">← {{ __('Back') }}</a>
            <div class="text-muted small">{{ __('Admin module') }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            @php
                $action = $page->key === 'privacy_policy'
                    ? route('admin.privacy-policy.update')
                    : route('admin.terms-conditions.update');
            @endphp

            <form method="POST" action="{{ $action }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">{{ __('Title') }}</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $page->title) }}" required>
                    @error('title')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Content') }}</label>
                    <textarea name="content" class="form-control" rows="12" required>{{ old('content', $page->content) }}</textarea>
                    @error('content')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection

