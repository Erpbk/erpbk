@extends('layouts.app')
@section('title', __('Agreement Settings'))

@section('content')
@php
  $sourceOptionGroups = $sourceOptionGroups ?? [];
  $groupLabels = $groupLabels ?? ['General'];
@endphp
<div class="container-fluid px-4">
    <div class="row mb-3">
        <div class="col-12">
            <h4 class="mb-1">{{ __('Agreement Settings') }}</h4>
            <p class="text-muted small mb-0">{{ __('Choose which modules can be assigned to agreements, and manage placeholders per module.') }}</p>
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

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ __('Assignable modules') }}</h5>
            <p class="text-muted small mb-0">{{ __('Checked modules appear in the company agreement assignment list (also filtered by each company’s enabled modules).') }}</p>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('admin.agreement-settings.modules.update') }}">
                @csrf
                @method('PUT')
                <div class="row g-2">
                    @foreach($assignableModules as $module)
                        <div class="col-md-4 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="modules[]" value="{{ $module['module_key'] }}"
                                    id="mod-{{ $module['module_key'] }}"
                                    {{ $module['enabled'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="mod-{{ $module['module_key'] }}">{{ $module['label'] }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">{{ __('Save modules') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div>
                <h5 class="mb-0">{{ __('Placeholders by module') }}</h5>
            </div>
            <form method="get" action="{{ route('admin.agreement-settings.index') }}" class="d-flex gap-2 align-items-center">
                <label class="small text-muted mb-0" for="module">{{ __('Module') }}</label>
                <select name="module" id="module" class="form-select form-select-sm" style="min-width:12rem" onchange="this.form.submit()">
                    @foreach($moduleOptions as $key => $label)
                        <option value="{{ $key }}" {{ $selectedModule === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive mb-4">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Token') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Group') }}</th>
                            <th>{{ __('Source') }}</th>
                            <th>{{ __('Order') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($placeholders as $ph)
                            <tr>
                                <td><code>{{ $ph->placeholder }}</code></td>
                                <td>{{ $ph->description }}</td>
                                <td>{{ $ph->group_label }}</td>
                                <td><code>{{ $ph->source_key }}</code></td>
                                <td>{{ $ph->sort_order }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-ph-{{ $ph->id }}">{{ __('Edit') }}</button>
                                    <form method="post" action="{{ route('admin.agreement-settings.placeholders.destroy', $ph) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete this placeholder?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="collapse" id="edit-ph-{{ $ph->id }}">
                                <td colspan="6">
                                    <form method="post" action="{{ route('admin.agreement-settings.placeholders.update', $ph) }}" class="row g-2 align-items-end">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="module_key" value="{{ $selectedModule }}">
                                        <div class="col-md-2">
                                            <input type="text" name="placeholder" class="form-control form-control-sm js-placeholder-token" value="{{ $ph->placeholder }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="description" class="form-control form-control-sm" value="{{ $ph->description }}">
                                        </div>
                                        <div class="col-md-2">
                                            <select name="group_label" class="form-select form-select-sm" required>
                                                @foreach($groupLabels as $groupLabel)
                                                    <option value="{{ $groupLabel }}" {{ $ph->group_label === $groupLabel ? 'selected' : '' }}>{{ $groupLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="source_key" class="form-select form-select-sm js-placeholder-source" required>
                                                @php
                                                    $allSourceKeys = collect($sourceOptionGroups)->flatMap(fn ($g) => array_keys($g['options'] ?? []))->all();
                                                @endphp
                                                @if($ph->source_key && ! in_array($ph->source_key, $allSourceKeys, true))
                                                    <option value="{{ $ph->source_key }}" selected>{{ $ph->source_key }} (current)</option>
                                                @endif
                                                @foreach($sourceOptionGroups as $group)
                                                    <optgroup label="{{ $group['label'] }}">
                                                        @foreach($group['options'] as $sk => $sl)
                                                            <option value="{{ $sk }}" {{ $ph->source_key === $sk ? 'selected' : '' }}>{{ $sl }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $ph->sort_order }}">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-sm btn-primary">{{ __('Save') }}</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted text-center py-3">{{ __('No placeholders for this module yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h6 class="mb-2">{{ __('Add placeholder') }}</h6>
            <form method="post" action="{{ route('admin.agreement-settings.placeholders.store') }}" class="row g-2 align-items-end" id="add-placeholder-form">
                @csrf
                <input type="hidden" name="module_key" value="{{ $selectedModule }}">
                <div class="col-md-2">
                    <label class="form-label small">{{ __('Token') }}</label>
                    <input type="text" name="placeholder" class="form-control form-control-sm js-placeholder-token" placeholder="{field_name}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ __('Description') }}</label>
                    <input type="text" name="description" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('Group') }}</label>
                    <select name="group_label" class="form-select form-select-sm" required>
                        @foreach($groupLabels as $groupLabel)
                            <option value="{{ $groupLabel }}" {{ $groupLabel === 'General' ? 'selected' : '' }}>{{ $groupLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ __('Source') }}</label>
                    <select name="source_key" class="form-select form-select-sm js-placeholder-source" required>
                        @foreach($sourceOptionGroups as $group)
                            <optgroup label="{{ $group['label'] }}">
                                @foreach($group['options'] as $sk => $sl)
                                    <option value="{{ $sk }}">{{ $sl }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small">{{ __('Order') }}</label>
                    <input type="number" name="sort_order" class="form-control form-control-sm" value="0">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Add') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('third_party_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  function suggestTokenFromSource(source) {
    if (!source || source.indexOf('.') === -1) {
      return '';
    }
    var parts = source.split('.');
    var relation = parts[0];
    var field = parts.slice(1).join('_');
    return '{' + relation + '_' + field + '}';
  }

  document.querySelectorAll('.js-placeholder-source').forEach(function (select) {
    select.addEventListener('change', function () {
      var form = select.closest('form');
      if (!form) return;
      var tokenInput = form.querySelector('.js-placeholder-token');
      if (!tokenInput) return;
      var suggested = suggestTokenFromSource(select.value);
      if (!suggested) return;
      if (!tokenInput.value || tokenInput.dataset.autoFilled === '1') {
        tokenInput.value = suggested;
        tokenInput.dataset.autoFilled = '1';
      }
      var groupSelect = form.querySelector('select[name="group_label"]');
      if (groupSelect && select.value.indexOf('.') !== -1) {
        var relatedOption = Array.prototype.find.call(groupSelect.options, function (o) { return o.value === 'Related'; });
        if (relatedOption) {
          groupSelect.value = 'Related';
        }
      }
    });
  });

  document.querySelectorAll('.js-placeholder-token').forEach(function (input) {
    input.addEventListener('input', function () {
      input.dataset.autoFilled = '0';
    });
  });
});
</script>
@endpush
