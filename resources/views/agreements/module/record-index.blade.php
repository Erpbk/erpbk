@extends($layout ?? 'layouts.app', ['hideModuleTopBarSlider' => true])

@section('title', $pageTitle)

@section('content')
@include('flash::message')

@php
  $companySlug = request()->route('company_slug');
  $indexParams = ['company_slug' => $companySlug, 'module' => $module, 'record' => $record];
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h4 class="card-title mb-0">{{ $pageTitle }}</h4>
        <p class="text-muted small mb-0 mt-1">
          Agreements assigned to this record. Management is available in the Agreements module.
        </p>
      </div>

      <div class="card-body">
        @if($assignedCount > 0)
        <form method="GET" action="{{ route('module-record-agreements.index', $indexParams) }}" class="row g-2 align-items-end mb-3">
          <div class="col-md-4">
            <label for="agreement-search" class="form-label mb-1">Search</label>
            <input type="text" id="agreement-search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Agreement name or code">
          </div>
          <div class="col-md-3">
            <label for="agreement-status" class="form-label mb-1">Status</label>
            <select id="agreement-status" name="status" class="form-control">
              <option value="">All</option>
              <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
              <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>
          <div class="col-md-5 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-search me-1"></i> Filter
            </button>
            @if($hasFilters)
            <a href="{{ route('module-record-agreements.index', $indexParams) }}" class="btn btn-outline-secondary">
              Clear
            </a>
            @endif
          </div>
        </form>
        @endif

        @if($assignedCount === 0)
        <div class="text-center py-5">
          <i class="ti ti-file-certificate display-5 text-muted d-block mb-3"></i>
          <p class="mb-0">No agreements assigned to this module</p>
        </div>
        @else
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Agreement Name</th>
                <th>Version</th>
                <th>Assigned Date</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($categories as $category)
              @php
                $contractTpl = $category->defaultTemplate;
                $templateId = $contractTpl?->id;
                $showParams = ['company_slug' => $companySlug, 'module' => $module, 'record' => $record, 'category' => $category->id];
              @endphp
              <tr>
                <td class="text-start">
                  <div class="fw-semibold">{{ $category->name }}</div>
                  <div class="text-muted small">
                    <code>{{ $category->agreement_code ?? $category->slug }}</code>
                  </div>
                </td>
                <td>
                  @if($contractTpl)
                  <span class="small">{{ $contractTpl->template_name }}</span>
                  <span class="badge bg-label-primary ms-1">
                    {{ \App\Models\AgreementTemplate::TYPES[$contractTpl->template_type] ?? $contractTpl->template_type }}
                  </span>
                  @else
                  <span class="text-muted small">—</span>
                  @endif
                </td>
                <td>{{ optional($category->created_at)->format('d M Y') ?: '—' }}</td>
                <td>
                  @if($category->status)
                  <span class="badge bg-label-success">Active</span>
                  @else
                  <span class="badge bg-label-secondary">Inactive</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    @if($templateId)
                    <a href="{{ route('module-record-agreements.show', $showParams) }}" class="btn btn-outline-info">
                      View
                    </a>
                    <a href="{{ route('module-record-agreements.download', $showParams) }}" class="btn btn-outline-secondary">
                      Download
                    </a>
                    @endif
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-muted text-center py-4">
                  @if($hasFilters)
                    No matching agreements.
                  @else
                    No agreements assigned to this module
                  @endif
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if(method_exists($categories, 'links'))
          {!! $categories->links('components.global-pagination') !!}
        @endif
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
