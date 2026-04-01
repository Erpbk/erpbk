@extends('layouts.app')
@section('title', __('Edit Testimonial'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('admin.testimonials.index') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Back to list') }}</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.testimonials.update', $testimonial) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $testimonial->name) }}" required>
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Title') }}</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $testimonial->title) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('Content') }}</label>
                    <textarea name="content" class="form-control" rows="8" required>{{ old('content', $testimonial->content) }}</textarea>
                    @error('content')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('Rating') }}</label>
                        <input type="number" name="rating" class="form-control" min="1" max="5" value="{{ old('rating', $testimonial->rating) }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select" required>
                            <option value="draft" {{ old('status',$testimonial->status) === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                            <option value="published" {{ old('status',$testimonial->status) === 'published' ? 'selected' : '' }}>{{ __('Published') }}</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ __('Published At') }}</label>
                        <input type="date" name="published_at" class="form-control"
                               value="{{ old('published_at', $testimonial->published_at ? \Carbon\Carbon::parse($testimonial->published_at)->format('Y-m-d') : '') }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection

