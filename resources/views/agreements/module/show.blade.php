@extends($layout ?? 'layouts.app', ['hideModuleTopBarSlider' => true])

@section('title', $category->name . ' — ' . $moduleLabel)

@section('content')
@include('flash::message')

@php
  $companySlug = request()->route('company_slug');
  $contractTemplateId = optional($category->defaultTemplate)->id;
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <a href="{{ route('module-agreements.index', ['company_slug' => $companySlug, 'module' => $module]) }}" class="text-muted small">
            <i class="ti ti-arrow-left"></i> Agreements
          </a>
          <h4 class="card-title mb-0 mt-1">{{ $category->name }}</h4>
          <p class="text-muted small mb-0">
            Code: <code>{{ $category->agreement_code ?? $category->slug }}</code>
            · {{ $category->templates->count() }} template(s)
          </p>
        </div>
      </div>
      @if($category->description)
      <div class="card-body border-top py-2">
        <p class="mb-0 text-muted small">{{ $category->description }}</p>
      </div>
      @endif
    </div>

    <div class="row g-3 mb-4">
      <div class="col-12">
        <h6 class="text-muted text-uppercase small mb-2">Templates</h6>
      </div>
      @forelse($category->templates as $template)
      @php
        $isContract = $contractTemplateId && (int) $contractTemplateId === (int) $template->id;
        $styleLabel = \App\Models\AgreementTemplate::TYPES[$template->template_type] ?? $template->template_type;
      @endphp
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 {{ $isContract ? 'border-primary shadow-sm' : 'border' }}">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h6 class="mb-0">{{ $template->template_name }}</h6>
              @if($isContract)
              <span class="badge bg-label-success">Contract</span>
              @endif
            </div>
            <p class="text-muted small mb-3">
              <span class="badge bg-label-primary">{{ $styleLabel }}</span>
            </p>
            <div class="d-flex flex-wrap gap-2 mt-auto">
              <a href="{{ route('module-agreements.templates.preview', ['company_slug' => $companySlug, 'module' => $module, 'template' => $template->id]) }}"
                class="btn btn-sm btn-outline-info" target="_blank">View</a>
              <a href="{{ route('module-agreements.templates.preview-pdf', ['company_slug' => $companySlug, 'module' => $module, 'template' => $template->id]) }}"
                class="btn btn-sm btn-outline-secondary">Download</a>
            </div>
          </div>
        </div>
      </div>
      @empty
      <div class="col-12">
        <p class="text-muted mb-0">No templates in this category yet.</p>
      </div>
      @endforelse
    </div>
  </div>
</div>
@endsection
