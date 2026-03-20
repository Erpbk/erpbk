@extends('layouts.app')
@section('title', __('Company modules') . ': ' . $company->name)

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div>
                <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Companies') }}</a>
                <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-outline-secondary btn-sm">{{ __('Company details') }}</a>
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
            <h5 class="mb-0">{{ __('ERP modules for') }} {{ $company->name }}</h5>
            <p class="text-muted small mb-0">{{ __('Uncheck a module to hide it from this company’s sidebar. Custom titles override the default menu name for that module.') }}</p>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.companies.modules.update', $company) }}" method="post">
                @csrf
                @method('PUT')

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width:3rem">{{ __('On') }}</th>
                                <th>{{ __('Module') }}</th>
                                <th>{{ __('Custom menu title') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($moduleDefinitions as $moduleKey => $meta)
                                @php
                                    $isOn = !in_array($moduleKey, $disabled, true);
                                    $primaryKey = $meta['primary_label_key'] ?? $moduleKey;
                                    $currentLabel = $labelOverrides[$primaryKey] ?? '';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" name="enabled[]" value="{{ $moduleKey }}" id="mod_{{ $moduleKey }}" @checked($isOn)>
                                        </div>
                                    </td>
                                    <td>
                                        <label class="mb-0 fw-medium" for="mod_{{ $moduleKey }}">{{ __($meta['label'] ?? $moduleKey) }}</label>
                                        <div class="text-muted small"><code>{{ $moduleKey }}</code></div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="labels[{{ $moduleKey }}]" value="{{ old('labels.'.$moduleKey, $currentLabel) }}" placeholder="{{ $meta['label'] ?? $moduleKey }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
