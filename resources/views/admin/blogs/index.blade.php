@extends('layouts.app')
@section('title', __('Blogs'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm">
                <h3 class="text-white mb-0 fw-bold">{{ __('Blogs') }}</h3>
                <p class="text-white-50 mb-0">{{ __('Manage site blog posts') }}</p>
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
                    <a href="{{ route('admin.blogs.index') }}" class="btn btn-{{ !request('status') ? 'primary' : 'outline-primary' }} btn-sm">{{ __('All') }}</a>
                    <a href="{{ route('admin.blogs.index', ['status' => 'draft']) }}" class="btn btn-{{ request('status') === 'draft' ? 'warning' : 'outline-warning' }} btn-sm">{{ __('Draft') }}</a>
                    <a href="{{ route('admin.blogs.index', ['status' => 'published']) }}" class="btn btn-{{ request('status') === 'published' ? 'success' : 'outline-success' }} btn-sm">{{ __('Published') }}</a>
                </div>

                @if(auth('admin')->user()->hasPermission('blogs_create'))
                    <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary btn-sm">{{ __('Create Blog') }}</a>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Published At') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blogs as $blog)
                            <tr>
                                <td>{{ $blog->title }}</td>
                                <td>
                                    @if($blog->status === 'published')
                                        <span class="badge bg-success">{{ __('Published') }}</span>
                                    @else
                                        <span class="badge bg-warning">{{ __('Draft') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($blog->published_at)
                                        {{ \Carbon\Carbon::parse($blog->published_at)->format('M j, Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if(auth('admin')->user()->hasPermission('blogs_edit'))
                                        <a href="{{ route('admin.blogs.edit', $blog) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    @endif
                                    @if(auth('admin')->user()->hasPermission('blogs_delete'))
                                        <form action="{{ route('admin.blogs.destroy', $blog) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this blog?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">{{ __('No blogs found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $blogs->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

