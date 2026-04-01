@extends('layouts.app')
@section('title', __('Testimonials'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm">
                <h3 class="text-white mb-0 fw-bold">{{ __('Testimonials') }}</h3>
                <p class="text-white-50 mb-0">{{ __('Manage site testimonials') }}</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex gap-2 mb-3 justify-content-between">
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.testimonials.index') }}" class="btn btn-{{ !request('status') ? 'primary' : 'outline-primary' }} btn-sm">{{ __('All') }}</a>
                    <a href="{{ route('admin.testimonials.index', ['status' => 'draft']) }}" class="btn btn-{{ request('status') === 'draft' ? 'warning' : 'outline-warning' }} btn-sm">{{ __('Draft') }}</a>
                    <a href="{{ route('admin.testimonials.index', ['status' => 'published']) }}" class="btn btn-{{ request('status') === 'published' ? 'success' : 'outline-success' }} btn-sm">{{ __('Published') }}</a>
                </div>

                @if(auth('admin')->user()->hasPermission('testimonials_create'))
                    <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary btn-sm">{{ __('Create Testimonial') }}</a>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Rating') }}</th>
                            <th>{{ __('Published At') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($testimonials as $testimonial)
                            <tr>
                                <td>{{ $testimonial->name }}</td>
                                <td>{{ $testimonial->title }}</td>
                                <td>
                                    @if($testimonial->status === 'published')
                                        <span class="badge bg-success">{{ __('Published') }}</span>
                                    @else
                                        <span class="badge bg-warning">{{ __('Draft') }}</span>
                                    @endif
                                </td>
                                <td>{{ $testimonial->rating ?? '-' }}</td>
                                <td>
                                    @if($testimonial->published_at)
                                        {{ \Carbon\Carbon::parse($testimonial->published_at)->format('M j, Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if(auth('admin')->user()->hasPermission('testimonials_edit'))
                                        <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    @endif
                                    @if(auth('admin')->user()->hasPermission('testimonials_delete'))
                                        <form action="{{ route('admin.testimonials.destroy', $testimonial) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this testimonial?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No testimonials found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $testimonials->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

