@php
$pb = $pdfBranding ?? [];
$primary = $pb['primary_color'] ?? '#2563eb';
$secondary = $pb['secondary_color'] ?? '#1e3a8a';
$onPrimary = $pb['text_on_primary'] ?? '#ffffff';
$logoSrc = $pb['logo_src'] ?? ($pb['logo_url'] ?? null);
$selectedTemplateId = (int) old('contract_template_id', $contractTemplateId ?? 0);
@endphp

<div class="mb-3" id="template-content-panel" style="--agreement-primary: {{ $primary }}; --agreement-secondary: {{ $secondary }}; --agreement-on-primary: {{ $onPrimary }};">
  <label class="form-label">Template content</label>
  <p class="text-muted small mb-2">
    Customize the contract body, clauses, layout, and placeholders for the selected template. Changes are saved with the agreement.
  </p>

  <div class="row g-3">
    <div class="col-lg-8">
      @foreach($category->templates as $tpl)
      <textarea name="template_contents[{{ $tpl->id }}]" id="template_content_{{ $tpl->id }}" class="d-none template-content-store">{{ old('template_contents.'.$tpl->id, $tpl->description) }}</textarea>
      @endforeach

      <div id="template-editor-wrap" class="{{ $category->templates->isEmpty() ? 'd-none' : '' }}">
        <textarea id="agreement-template-editor" rows="16" class="form-control"></textarea>
        <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-template-quick-preview">
            <i class="ti ti-eye me-1"></i> Quick preview
          </button>
          <a href="{{ $selectedTemplateId ? route('agreements.preview', ['company_slug' => request()->route('company_slug'), 'id' => $selectedTemplateId]) : '#' }}"
            class="btn btn-outline-info btn-sm {{ $selectedTemplateId ? '' : 'd-none' }}" id="btn-template-full-preview" target="_blank">
            <i class="ti ti-file-text me-1"></i> Full PDF preview
          </a>
        </div>
      </div>

      @if($category->templates->isEmpty())
      <div class="alert alert-warning py-2 small mb-0">
        No sample templates are available for this agreement yet.
      </div>
      @endif
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
            <div class="small opacity-75">Agreement PDF uses your uploaded letterhead</div>
          </div>
        </div>
        <div class="card-body py-3 px-3">
          <p class="small text-muted mb-0">
            Upload a letterhead above to replace the default header and footer on generated contracts.
          </p>
        </div>
      </div>

      <div class="card">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Dynamic placeholders</h6>
          <span class="badge bg-label-primary">Click to insert</span>
        </div>
        <div class="card-body p-2" style="max-height: 42vh; overflow-y: auto;">
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
</div>

@once
@push('third_party_stylesheets')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tinymce@6/skins/ui/oxide/skin.min.css">
<style>
  .branding-panel {
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e4e6ef;
  }
  .branding-panel-header {
    background: var(--agreement-primary);
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
  .branding-logo-fallback {
    width: 48px;
    height: 48px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
    background: rgba(255, 255, 255, .2);
  }
  .color-swatch {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid rgba(0, 0, 0, .08);
    display: inline-block;
  }
  .placeholder-btn code { font-size: 0.75rem; }
</style>
@endpush
@endonce
