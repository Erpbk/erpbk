@extends($layout ?? 'layouts.app', ['hideModuleTopBarSlider' => true])

@section('title', $category->name . ' — Agreements')

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
          <a href="{{ route('documents.agreements.index', ['company_slug' => $companySlug, 'group' => $category->group_key]) }}" class="text-muted small">
            <i class="ti ti-arrow-left"></i> All agreements
          </a>
          <h4 class="card-title mb-0 mt-1">{{ $category->name }}</h4>
          <p class="text-muted small mb-0">
            Code: <code>{{ $category->agreement_code ?? $category->slug }}</code>
            · {{ $category->templates->count() }} template(s)
          </p>
        </div>
        @canany(['agreements_edit', 'gn_settings'])
        <a href="{{ route('documents.agreements.edit-agreement', ['company_slug' => $companySlug, 'category' => $category->id]) }}" class="btn btn-outline-primary btn-sm">
          Edit agreement
        </a>
        @endcanany
      </div>
      @if($category->description)
      <div class="card-body border-top py-2">
        <p class="mb-0 text-muted small">{{ $category->description }}</p>
      </div>
      @endif
    </div>

    <div class="row g-3 mb-4">
      <div class="col-12">
        <h6 class="text-muted text-uppercase small mb-2">Templates in this category</h6>
      </div>
      @forelse($category->templates as $template)
      @php
        $isActive = $activeTemplate && (int) $activeTemplate->id === (int) $template->id;
        $isContract = $contractTemplateId && (int) $contractTemplateId === (int) $template->id;
      @endphp
      <div class="col-md-6 col-lg-4">
        <div class="card h-100 {{ $isActive ? 'border-primary shadow-sm' : 'border' }}">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h6 class="mb-0">{{ $template->template_name }}</h6>
              @if($isContract)
              <span class="badge bg-label-success">Contract</span>
              @endif
            </div>
            <div class="d-flex flex-wrap gap-2 mt-auto">
              <a href="{{ route('documents.agreements.manage-category', ['company_slug' => $companySlug, 'category' => $category->id, 'template' => $template->id]) }}#template-editor-panel"
                class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="ti ti-settings me-1"></i> {{ $isActive ? 'Managing' : 'Manage' }}
              </a>
              @canany(['agreements_view', 'agreements_generate', 'gn_settings'])
              <a href="{{ route('agreements.preview', ['company_slug' => $companySlug, 'id' => $template->id]) }}"
                class="btn btn-sm btn-outline-info" target="_blank">Preview</a>
              @endcanany
            </div>
          </div>
        </div>
      </div>
      @empty
      <div class="col-12">
        <p class="text-muted">No templates in this category yet.</p>
      </div>
      @endforelse
    </div>

    @if($activeTemplate)
    @include('agreements.module.partials.template-editor-inline', [
      'category' => $category,
      'activeTemplate' => $activeTemplate,
      'placeholders' => $placeholders,
      'pdfBranding' => $pdfBranding,
    ])
    @endif
  </div>
</div>

@if($activeTemplate)
@push('third_party_scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#template-editor-panel') {
      var panel = document.getElementById('template-editor-panel');
      if (panel) {
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }
  });
</script>
@endpush
@endif
@endsection
