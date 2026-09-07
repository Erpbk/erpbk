@extends($layout ?? 'layouts.app', ['hideModuleTopBarSlider' => true])

@section('title', 'Agreements')

@section('content')
@include('flash::message')

@php
  $companySlug = request()->route('company_slug');
  $filters = $filters ?? ['module' => '', 'name' => '', 'status' => ''];
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h4 class="card-title mb-0">Agreements</h4>
          <p class="text-muted small mb-0 mt-1">
            Create agreements and assign each to one module. Template editing is module-side only.
          </p>
        </div>
        @canany(['agreements_create', 'gn_settings'])
        <a class="btn btn-primary btn-sm" href="{{ route('agreements.create-agreement', ['company_slug' => $companySlug]) }}">
          <i class="ti ti-plus me-1"></i> New Agreement
        </a>
        @endcanany
      </div>

      <div class="card-body">
        <form method="get" action="{{ route('agreements.index', ['company_slug' => $companySlug]) }}" class="row g-2 align-items-end mb-3">
          <div class="col-md-3">
            <label class="form-label small mb-1" for="filter-module">Module</label>
            <select name="module" id="filter-module" class="form-select form-select-sm">
              <option value="">All modules</option>
              @foreach($modules as $moduleKey => $label)
                <option value="{{ $moduleKey }}" {{ ($filters['module'] ?? '') === $moduleKey ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-1" for="filter-name">Agreement name</label>
            <input type="text" name="name" id="filter-name" class="form-control form-control-sm"
              value="{{ $filters['name'] ?? '' }}" placeholder="Name or code">
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-1" for="filter-status">Status</label>
            <select name="status" id="filter-status" class="form-select form-select-sm">
              <option value="" {{ ($filters['status'] ?? '') === '' || ($filters['status'] ?? null) === null ? 'selected' : '' }}>All</option>
              <option value="1" {{ (string) ($filters['status'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
              <option value="0" {{ (string) ($filters['status'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('agreements.index', ['company_slug' => $companySlug]) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Agreement</th>
                <th>Agreement Code</th>
                <th>Module</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($categories as $category)
              <tr>
                <td class="text-start">
                  <div class="fw-semibold">{{ $category->name }}</div>
                </td>
                <td>
                  <code>{{ $category->agreement_code ?? $category->slug }}</code>
                </td>
                <td>
                  @php $assignedModules = $category->normalizedAssignedModules(); @endphp
                  @if(!empty($assignedModules))
                  <span class="badge bg-label-secondary">{{ $modules[$assignedModules[0]] ?? $assignedModules[0] }}</span>
                  @else
                  <span class="text-muted small">—</span>
                  @endif
                </td>
                <td>
                  @if($category->status)
                  <span class="badge bg-label-success">Active</span>
                  @else
                  <span class="badge bg-label-secondary">Inactive</span>
                  @endif
                </td>
                <td class="text-end" style="position: relative;">
                  @php $previewTemplate = $category->contractTemplate(); @endphp
                  <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $category->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                      <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $category->id }}" style="z-index: 1050;">
                      @canany(['agreements_view', 'gn_settings'])
                        @if($previewTemplate)
                        <a href="{{ route('agreements.preview', ['company_slug' => $companySlug, 'id' => $previewTemplate->id]) }}" class="dropdown-item waves-effect" target="_blank" rel="noopener">
                          <i class="ti ti-eye my-1"></i> View
                        </a>
                        @else
                        <span class="dropdown-item text-muted" title="Add a template before previewing.">
                          <i class="ti ti-eye my-1"></i> View
                        </span>
                        @endif
                      @endcanany

                      @canany(['agreements_view', 'agreements_manage_templates', 'gn_settings'])
                      <a href="{{ route('agreements.templates', ['company_slug' => $companySlug, 'category' => $category->id]) }}" class="dropdown-item waves-effect">
                        <i class="ti ti-file-text my-1"></i> Templates
                      </a>
                      @endcanany

                      @canany(['agreements_edit', 'gn_settings'])
                      <a href="{{ route('agreements.edit-agreement', ['company_slug' => $companySlug, 'category' => $category->id]) }}" class="dropdown-item waves-effect">
                        <i class="ti ti-edit my-1"></i> Edit
                      </a>
                      @endcanany

                      @canany(['agreements_edit', 'gn_settings'])
                      <form method="POST" action="{{ route('agreements.toggle-agreement-status', ['company_slug' => $companySlug, 'category' => $category->id]) }}">
                        @csrf
                        @foreach(['module', 'name', 'status'] as $filterKey)
                          @if(($filters[$filterKey] ?? '') !== '' && ($filters[$filterKey] ?? null) !== null)
                            <input type="hidden" name="{{ $filterKey }}" value="{{ $filters[$filterKey] }}">
                          @endif
                        @endforeach
                        <button type="submit" class="dropdown-item waves-effect">
                          <i class="ti ti-power my-1"></i> {{ $category->status ? 'Deactivate' : 'Activate' }}
                        </button>
                      </form>
                      @endcanany

                      @canany(['agreements_delete', 'gn_settings'])
                      <form method="POST" action="{{ route('agreements.destroy-agreement', ['company_slug' => $companySlug, 'category' => $category->id]) }}" onsubmit="return confirm('Delete this agreement? This will also delete its templates.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item waves-effect text-danger">
                          <i class="ti ti-trash my-1"></i> Delete
                        </button>
                      </form>
                      @endcanany
                    </div>
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-muted text-center py-4">No agreements found.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
