@extends('layouts.app')
@section('title', __('Notification channels') . ': ' . $company->name)

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div>
                <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Companies') }}</a>
                <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-outline-secondary btn-sm">{{ __('Company details') }}</a>
                <a href="{{ route('admin.companies.modules.edit', $company) }}" class="btn btn-outline-secondary btn-sm">{{ __('Modules') }}</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">{{ __('Notification channels for') }} {{ $company->name }}</h5>
            <p class="text-muted small mb-0">{{ __('Only enabled channels appear for company admins when configuring notification rules.') }}</p>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.companies.notification-channels.update', $company) }}" method="post">
                @csrf
                @method('PUT')
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width:3rem">{{ __('On') }}</th>
                                <th>{{ __('Channel') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allChannels as $channel)
                                <tr>
                                    <td>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" name="enabled[]" value="{{ $channel }}" id="ch_{{ $channel }}" @checked(in_array($channel, $enabled, true))>
                                        </div>
                                    </td>
                                    <td>
                                        <label class="mb-0 fw-medium" for="ch_{{ $channel }}">{{ $labels[$channel] ?? $channel }}</label>
                                        <div class="text-muted small"><code>{{ $channel }}</code></div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
