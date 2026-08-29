@extends($layout ?? 'layouts.app')

@section('title', 'Edit Template — ' . $category->name)

@push('third_party_stylesheets')
<style>
  .branding-panel {
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e4e6ef;
  }

  .branding-panel-header {
    background: var(--agreement-primary, #2563eb);
    color: var(--agreement-on-primary, #fff);
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .branding-logo {
    width: 48px;
    height: 48px;
    object-fit: contain;
    background: #fff;
    border-radius: 6px;
    padding: 4px;
  }

  .color-swatch {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    display: inline-block;
    border: 1px solid rgba(0, 0, 0, .1);
  }
</style>
@endpush

@section('content')
@include('flash::message')

@php
$companySlug = request()->route('company_slug');
$pb = $pdfBranding ?? [];
$primary = $pb['primary_color'] ?? '#2563eb';
$secondary = $pb['secondary_color'] ?? '#1e3a8a';
$onPrimary = $pb['text_on_primary'] ?? '#ffffff';
$logoSrc = $pb['logo_src'] ?? ($pb['logo_url'] ?? null);
$styleLabel = $template->template_type === 'premium' ? 'Modern Premium' : 'Corporate Professional';
@endphp

<div class="row" style="--agreement-primary: {{ $primary }}; --agreement-secondary: {{ $secondary }}; --agreement-on-primary: {{ $onPrimary }};">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <a href="{{ route('module-agreements.index', ['company_slug' => $companySlug, 'module' => $module]) }}" class="text-muted small">
            <i class="ti ti-arrow-left"></i> Sample templates
          </a>
          <h4 class="card-title mb-0 mt-1">Edit agreement content</h4>
          <p class="text-muted small mb-0">{{ $template->template_name }} · {{ $styleLabel }}</p>
        </div>
        <div class="d-flex gap-1">
          <a href="{{ route('module-agreements.templates.preview', ['company_slug' => $companySlug, 'module' => $module, 'template' => $template->id]) }}"
            class="btn btn-sm btn-outline-info" target="_blank"><i class="ti ti-eye"></i> Preview</a>
          <a href="{{ route('module-agreements.templates.preview-pdf', ['company_slug' => $companySlug, 'module' => $module, 'template' => $template->id]) }}"
            class="btn btn-sm btn-outline-dark" target="_blank"><i class="ti ti-file-type-pdf"></i> PDF</a>
        </div>
      </div>
      <div class="card-body">
        <form method="POST"
          action="{{ route('module-agreements.templates.update', ['company_slug' => $companySlug, 'module' => $module, 'template' => $template->id]) }}"
          id="agreement-template-form">
          @csrf
          @method('PUT')

          <div class="alert alert-info py-2 small">
            System template style cannot be changed here. Only the agreement body content is editable for this module.
          </div>

          <div class="mb-3">
            <label class="form-label">Agreement content</label>
            <div class="agreement-word-editor">
              <textarea name="description" id="agreement-editor" rows="40" class="form-control">{{ old('description', $template->description) }}</textarea>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i> Save content</button>
            <a href="{{ route('module-agreements.index', ['company_slug' => $companySlug, 'module' => $module]) }}"
              class="btn btn-label-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="branding-panel mb-3">
      <div class="branding-panel-header">
        @if(!empty($logoSrc))
        <img src="{{ $logoSrc }}" alt="" class="branding-logo">
        @else
        <div class="branding-logo-fallback">{{ strtoupper(mb_substr($pb['name'] ?? 'C', 0, 1)) }}</div>
        @endif
        <div>
          <div class="fw-semibold">{{ $pb['name'] ?? config('app.name') }}</div>
          <div class="small opacity-75">PDF branding preview</div>
        </div>
      </div>
      <div class="card-body py-3 px-3">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="color-swatch" style="background:{{ $primary }};"></span>
          <span class="color-swatch" style="background:{{ $secondary }};"></span>
          <span class="small text-muted ms-1">Company theme colors</span>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Dynamic placeholders</h6>
        <span class="badge bg-label-primary">Click to insert</span>
      </div>
      <div class="card-body p-2" style="max-height: 52vh; overflow-y: auto;">
        @foreach($placeholders as $group => $items)
        <p class="small fw-semibold text-muted mb-1 mt-2">{{ $group ?: 'Fields' }}</p>
        @foreach($items as $ph)
        <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-start mb-1 placeholder-btn"
          data-placeholder="{{ $ph->placeholder }}" title="{{ $ph->description }}">
          <code>{{ $ph->placeholder }}</code>
          <span class="d-block text-muted" style="font-size:0.7rem;">{{ $ph->description }}</span>
        </button>
        @endforeach
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection

@include('agreements.partials.tinymce-word-document')

@push('third_party_scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    tinymce.init(window.erpbkAgreementWordEditor.config({
      selector: '#agreement-editor'
    }));

    document.querySelectorAll('.placeholder-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var ph = btn.getAttribute('data-placeholder');
        if (tinymce.get('agreement-editor')) {
          tinymce.get('agreement-editor').insertContent(ph);
        }
      });
    });

    document.getElementById('agreement-template-form').addEventListener('submit', function() {
      if (tinymce.get('agreement-editor')) {
        tinymce.get('agreement-editor').save();
      }
    });
  });
</script>
@endpush