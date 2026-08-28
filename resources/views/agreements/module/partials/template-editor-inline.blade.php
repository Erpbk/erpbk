@php
  $companySlug = request()->route('company_slug');
  $styleLabel = \App\Models\AgreementTemplate::TYPES[$activeTemplate->template_type] ?? $activeTemplate->template_type;
  $pb = $pdfBranding ?? [];
  $primary = $pb['primary_color'] ?? '#2563eb';
  $secondary = $pb['secondary_color'] ?? '#1e3a8a';
  $onPrimary = $pb['text_on_primary'] ?? '#ffffff';
  $logoSrc = $pb['logo_src'] ?? ($pb['logo_url'] ?? null);
  $isContractTemplate = optional($category->defaultTemplate)->id === $activeTemplate->id;
  $editorMargins = $letterheadMargins ?? $category->resolvedLetterheadMarginsMm();
@endphp

<div class="card border-primary border shadow-none" id="template-editor-panel">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-0">Manage template</h5>
      <p class="text-muted small mb-0">{{ $activeTemplate->template_name }} · {{ $styleLabel }}</p>
    </div>
    <div class="d-flex flex-wrap gap-1">
      @canany(['agreements_view', 'agreements_generate', 'gn_settings'])
      <a href="{{ route('agreements.preview', ['company_slug' => $companySlug, 'id' => $activeTemplate->id]) }}"
        class="btn btn-sm btn-outline-info" target="_blank"><i class="ti ti-eye"></i> Preview</a>
      <a href="{{ route('agreements.preview-pdf', ['company_slug' => $companySlug, 'id' => $activeTemplate->id]) }}"
        class="btn btn-sm btn-outline-dark" target="_blank"><i class="ti ti-download"></i> PDF</a>
      @endcanany
      @if(!$isContractTemplate)
      @canany(['agreements_edit', 'agreements_manage_templates', 'gn_settings'])
      <form method="POST"
        action="{{ route('documents.agreements.assign-contract-template', ['company_slug' => $companySlug, 'category' => $category->id, 'template' => $activeTemplate->id]) }}"
        class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-success" title="Use this template when generating contracts">
          <i class="ti ti-link me-1"></i> Assign to contracts
        </button>
      </form>
      @endcanany
      @else
      <span class="badge bg-label-success align-self-center"><i class="ti ti-check me-1"></i> Contract template</span>
      @endif
    </div>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-lg-8">
        @canany(['agreements_edit', 'agreements_manage_templates', 'gn_settings'])
        <form method="POST"
          action="{{ route('documents.agreements.update-content', ['company_slug' => $companySlug, 'id' => $activeTemplate->id]) }}"
          id="agreement-template-form">
          @csrf
          @method('PUT')
          <div class="alert alert-info py-2 small mb-3">
            Edit the agreement body below. Style (Corporate / Premium) is fixed; assign this template to contracts using the button above.
          </div>
          <label class="form-label">Agreement content</label>
          <div class="agreement-word-editor"
            data-margin-top="{{ $editorMargins['top'] }}"
            data-margin-right="{{ $editorMargins['right'] }}"
            data-margin-bottom="{{ $editorMargins['bottom'] }}"
            data-margin-left="{{ $editorMargins['left'] }}">
            <textarea name="description" id="agreement-editor" rows="40" class="form-control">{{ old('description', $activeTemplate->description) }}</textarea>
          </div>
          <div class="mt-3 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i> Update template</button>
          </div>
        </form>
        @else
        <div class="border rounded p-3 bg-light small">
          {!! $activeTemplate->description !!}
        </div>
        @endcanany
      </div>
      <div class="col-lg-4">
        <div class="branding-panel mb-3" style="--agreement-primary: {{ $primary }}; --agreement-secondary: {{ $secondary }}; --agreement-on-primary: {{ $onPrimary }};">
          <div class="branding-panel-header rounded-top px-3 py-2 d-flex align-items-center gap-2"
            style="background: {{ $primary }}; color: {{ $onPrimary }};">
            @if(!empty($logoSrc))
            <img src="{{ $logoSrc }}" alt="" class="branding-logo" style="width:40px;height:40px;object-fit:contain;background:#fff;border-radius:4px;padding:3px;">
            @endif
            <div class="small fw-semibold">{{ $pb['name'] ?? config('app.name') }}</div>
          </div>
          <div class="border border-top-0 rounded-bottom p-2 small text-muted">PDF branding preview</div>
        </div>
        <div class="card">
          <div class="card-header py-2">
            <h6 class="mb-0 small">Placeholders</h6>
          </div>
          <div class="card-body p-2" style="max-height: 280px; overflow-y: auto;">
            @foreach($placeholders as $group => $items)
            <p class="small fw-semibold text-muted mb-1 mt-1">{{ $group ?: 'Fields' }}</p>
            @foreach($items as $ph)
            <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-start mb-1 placeholder-btn"
              data-placeholder="{{ $ph->placeholder }}" title="{{ $ph->description }}">
              <code class="small">{{ $ph->placeholder }}</code>
            </button>
            @endforeach
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@include('agreements.partials.tinymce-word-document')

@once
@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
@endpush
@endonce

@push('third_party_scripts')
<script>
(function() {
  var editorEl = document.getElementById('agreement-editor');
  if (!editorEl) {
    return;
  }
  if (typeof tinymce !== 'undefined' && !tinymce.get('agreement-editor') && window.erpbkAgreementWordEditor) {
    tinymce.init(window.erpbkAgreementWordEditor.config({
      selector: '#agreement-editor'
    }));
  }
  document.querySelectorAll('.placeholder-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var ph = btn.getAttribute('data-placeholder');
      var ed = typeof tinymce !== 'undefined' ? tinymce.get('agreement-editor') : null;
      if (ed) {
        ed.insertContent(ph);
      }
    });
  });
  var form = document.getElementById('agreement-template-form');
  if (form) {
    form.addEventListener('submit', function() {
      var ed = typeof tinymce !== 'undefined' ? tinymce.get('agreement-editor') : null;
      if (ed) {
        ed.save();
      }
    });
  }
})();
</script>
@endpush
