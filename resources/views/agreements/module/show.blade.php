@extends($layout ?? 'layouts.app')

@section('title', $category->name . ' — ' . $moduleLabel)

@section('content')
@include('flash::message')

@php $companySlug = request()->route('company_slug'); @endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <a href="{{ route('module-agreements.index', ['company_slug' => $companySlug, 'module' => $module]) }}" class="text-muted small">
            <i class="ti ti-arrow-left"></i> {{ $moduleLabel }} Agreements
          </a>
          <h4 class="card-title mb-0 mt-1">{{ $category->name }}</h4>
          <p class="text-muted small mb-0">Code: <code>{{ $category->agreement_code ?? $category->slug }}</code></p>
        </div>
      </div>
      @if($category->description)
      <div class="card-body border-bottom">
        <p class="mb-0 text-muted">{{ $category->description }}</p>
      </div>
      @endif
    </div>

    <div class="row g-3">
      @foreach($category->templates as $template)
      <div class="col-md-6">
        <div class="card border h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h5 class="mb-0">{{ $template->template_name }}</h5>
              @if($template->is_default)
              <span class="badge bg-label-success">Default</span>
              @endif
            </div>
            <p class="text-muted small mb-3">
              Style: {{ $template->template_type === 'premium' ? 'Modern Premium' : 'Corporate Professional' }}
            </p>
            <div class="d-flex flex-wrap gap-2">
              <a href="{{ route('module-agreements.templates.edit', ['company_slug' => $companySlug, 'module' => $module, 'template' => $template->id]) }}"
                class="btn btn-primary btn-sm">
                <i class="ti ti-edit me-1"></i> Edit content
              </a>
              <a href="{{ route('module-agreements.templates.preview', ['company_slug' => $companySlug, 'module' => $module, 'template' => $template->id]) }}"
                class="btn btn-outline-info btn-sm" target="_blank">Preview</a>
              <a href="{{ route('module-agreements.templates.preview-pdf', ['company_slug' => $companySlug, 'module' => $module, 'template' => $template->id]) }}"
                class="btn btn-outline-dark btn-sm" target="_blank">PDF</a>
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
