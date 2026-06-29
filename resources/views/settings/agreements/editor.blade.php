@extends($layout ?? 'layouts.app', ['hideModuleTopBarSlider' => true])

@section('title', ($template->exists ? 'Edit' : 'New') . ' Template – ' . $category->name)

@push('third_party_stylesheets')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tinymce@6/skins/ui/oxide/skin.min.css">
<style>
  .agreement-style-card {
    border: 2px solid #e4e6ef;
    border-radius: 10px;
    padding: 12px;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
    height: 100%;
  }

  .agreement-style-card:hover {
    border-color: #b4b9ca;
  }

  .agreement-style-card.active {
    border-color: var(--agreement-primary, #2563eb);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
  }

  .style-preview-corporate {
    height: 56px;
    border-radius: 6px;
    background: linear-gradient(180deg, var(--agreement-primary) 0%, var(--agreement-primary) 38%, #fff 38%);
    border: 1px solid #e2e8f0;
    margin-bottom: 8px;
  }

  .style-preview-premium {
    height: 56px;
    border-radius: 6px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-left: 5px solid var(--agreement-primary);
    margin-bottom: 8px;
    position: relative;
  }

  .style-preview-premium::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--agreement-secondary);
    margin-left: 5px;
  }

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

  .placeholder-btn code {
    font-size: 0.75rem;
  }
</style>
@endpush

@section('content')
@include('flash::message')
@php
$pb = $pdfBranding ?? [];
$primary = $pb['primary_color'] ?? '#2563eb';
$secondary = $pb['secondary_color'] ?? '#1e3a8a';
$onPrimary = $pb['text_on_primary'] ?? '#ffffff';
$logoSrc = $pb['logo_src'] ?? ($pb['logo_url'] ?? null);
$selectedType = old('template_type', $template->template_type ?? 'corporate');
@endphp

<div class="row" style="--agreement-primary: {{ $primary }}; --agreement-secondary: {{ $secondary }}; --agreement-on-primary: {{ $onPrimary }};">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <a href="{{ route('agreements.templates', ['company_slug' => request()->route('company_slug'), 'category' => $category->id]) }}" class="text-muted small">
            <i class="ti ti-arrow-left"></i> {{ $category->name }}
          </a>
          <h4 class="card-title mb-0 mt-1">{{ $template->exists ? 'Edit' : 'Create' }} Agreement Template</h4>
        </div>
        @if($template->exists)
        <div class="d-flex gap-1">
          <a href="{{ route('agreements.preview', ['company_slug' => request()->route('company_slug'), 'id' => $template->id]) }}"
            class="btn btn-sm btn-outline-info" target="_blank"><i class="ti ti-eye"></i> Preview</a>
          <a href="{{ route('agreements.preview-pdf', ['company_slug' => request()->route('company_slug'), 'id' => $template->id]) }}"
            class="btn btn-sm btn-outline-dark" target="_blank"><i class="ti ti-file-type-pdf"></i> PDF</a>
        </div>
        @endif
      </div>
      <div class="card-body">
        <form method="POST"
          action="{{ $template->exists
                ? route('agreements.update', ['company_slug' => request()->route('company_slug'), 'id' => $template->id])
                : route('agreements.store', ['company_slug' => request()->route('company_slug'), 'category' => $category->id]) }}"
          id="agreement-template-form">
          @csrf
          @if($template->exists) @method('PUT') @endif

          <div class="mb-3">
            <label class="form-label">Template name <span class="text-danger">*</span></label>
            <input type="text" name="template_name" class="form-control" required
              value="{{ old('template_name', $template->template_name) }}"
              placeholder="e.g. Standard Rider Contract 2026">
          </div>

          <div class="mb-4">
            <label class="form-label d-block mb-2">PDF design style</label>
            <input type="hidden" name="template_type" id="template_type_input" value="{{ $selectedType }}">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="agreement-style-card {{ $selectedType === 'corporate' ? 'active' : '' }}" data-style="corporate">
                  <div class="style-preview-corporate"></div>
                  <strong>Style 1 — Corporate</strong>
                  <p class="text-muted small mb-0 mt-1">Colored header band, official ref badge, structured tables, classic signatures.</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="agreement-style-card {{ $selectedType === 'premium' ? 'active' : '' }}" data-style="premium">
                  <div class="style-preview-premium"></div>
                  <strong>Style 2 — Premium Modern</strong>
                  <p class="text-muted small mb-0 mt-1">Side accent stripe, card header with logo frame, highlighted clause panel.</p>
                </div>
              </div>
            </div>
            <p class="text-muted small mt-2 mb-0">
              <i class="ti ti-palette"></i> Colors and logo are taken from your <a href="{{ route('settings-panel.company', ['company_slug' => request()->route('company_slug')]) }}">Company Details</a>.
            </p>
          </div>

          <div class="mb-3">
            <label class="form-label">Agreement content</label>
            <textarea name="description" id="agreement-editor" rows="18" class="form-control">{{ old('description', $template->description) }}</textarea>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="status" value="1" id="statusSwitch"
                  {{ old('status', $template->status ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="statusSwitch">Template enabled</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="defaultSwitch"
                  {{ old('is_default', $template->is_default) ? 'checked' : '' }}>
                <label class="form-check-label" for="defaultSwitch">Set as default for this category</label>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i> Save template</button>
            <button type="button" class="btn btn-outline-secondary" id="btn-preview-before-save"><i class="ti ti-eye me-1"></i> Quick preview</button>
            <a href="{{ route('agreements.templates', ['company_slug' => request()->route('company_slug'), 'category' => $category->id]) }}"
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
          <span class="color-swatch" style="background:{{ $primary }};" title="Primary"></span>
          <span class="color-swatch" style="background:{{ $secondary }};" title="Secondary"></span>
          <span class="color-swatch" style="background:{{ $pb['primary_light'] ?? '#eef2ff' }};" title="Light tint"></span>
          <span class="small text-muted ms-1">Dynamic theme colors</span>
        </div>
        @if(!empty($pb['address']))
        <p class="small text-muted mb-1"><i class="ti ti-map-pin"></i> {{ $pb['address'] }}</p>
        @endif
        @if(!empty($pb['phone']))
        <p class="small text-muted mb-0"><i class="ti ti-phone"></i> {{ $pb['phone'] }}</p>
        @endif
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

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
      selector: '#agreement-editor',
      height: 420,
      menubar: false,
      plugins: 'lists link table code fullscreen preview',
      toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | table link | code fullscreen',
      branding: false,
      promotion: false,
      content_style: 'body { font-family: Calibri, sans-serif; font-size: 11pt; line-height: 1.5; }'
    });

    document.querySelectorAll('.agreement-style-card').forEach(function(card) {
      card.addEventListener('click', function() {
        document.querySelectorAll('.agreement-style-card').forEach(function(c) {
          c.classList.remove('active');
        });
        card.classList.add('active');
        document.getElementById('template_type_input').value = card.getAttribute('data-style');
      });
    });

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

    document.getElementById('btn-preview-before-save').addEventListener('click', function() {
      if (tinymce.get('agreement-editor')) {
        tinymce.get('agreement-editor').save();
      }
      var w = window.open('', '_blank');
      var primary = getComputedStyle(document.querySelector('.row')).getPropertyValue('--agreement-primary').trim() || '#2563eb';
      w.document.write('<html><head><style>body{font-family:Calibri,sans-serif;padding:24px;} .hdr{background:' + primary + ';color:#fff;padding:16px;margin:-24px -24px 20px;}</style></head><body>');
      w.document.write('<div class="hdr"><strong>Content preview</strong> (full PDF layout uses Style 1 or 2 after save)</div>');
      w.document.write(document.getElementById('agreement-editor').value);
      w.document.write('</body></html>');
      w.document.close();
    });
  });
</script>
@endpush