@extends($layout ?? 'layouts.app')

@section('title', $moduleLabel . ' — Agreement Categories')

@section('content')
@include('flash::message')

@php $companySlug = request()->route('company_slug'); @endphp

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title mb-0">{{ $moduleLabel }} — Agreement Categories</h4>
        <p class="text-muted small mb-0 mt-1">
          Select a category to view, edit, and manage its templates. Assign categories to this module in Settings → Agreements.
        </p>
      </div>
      <div class="card-body">
        <div class="row g-3">
          @forelse($categories as $category)
          <div class="col-md-6 col-lg-4">
            <a href="{{ route('module-agreements.show', ['company_slug' => $companySlug, 'module' => $module, 'category' => $category->id]) }}"
              class="card border h-100 text-body text-decoration-none agreement-category-card">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h5 class="mb-0">{{ $category->name }}</h5>
                  <i class="ti ti-folder text-primary fs-4"></i>
                </div>
                <p class="text-muted small mb-2">
                  <code>{{ $category->agreement_code ?? $category->slug }}</code>
                </p>
                @if($category->description)
                <p class="small text-muted flex-grow-1 mb-2">{{ \Illuminate\Support\Str::limit(strip_tags($category->description), 100) }}</p>
                @else
                <div class="flex-grow-1"></div>
                @endif
                <div class="d-flex flex-wrap gap-2 align-items-center mt-auto pt-2">
                  <span class="badge bg-label-primary">{{ $category->templates_count }} template(s)</span>
                  @if($category->defaultTemplate)
                  <span class="badge bg-label-success" title="Assigned contract template">
                    <i class="ti ti-check me-1"></i>{{ \Illuminate\Support\Str::limit($category->defaultTemplate->template_name, 28) }}
                  </span>
                  @endif
                </div>
              </div>
            </a>
          </div>
          @empty
          <div class="col-12">
            <p class="text-muted text-center py-4 mb-0">
              No agreement categories are assigned to {{ $moduleLabel }}.
              Configure them in Settings → Agreements.
            </p>
          </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>

@push('third_party_stylesheets')
<style>
  .agreement-category-card { transition: box-shadow .15s ease, border-color .15s ease; }
  .agreement-category-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); border-color: var(--bs-primary) !important; }
</style>
@endpush
@endsection
