@extends($layout ?? 'layouts.app', ['hideModuleTopBarSlider' => true])

@section('title', 'Edit Agreement – Settings')

@section('content')
@include('flash::message')

@php
$companySlug = request()->route('company_slug');
$groupLabel = $groups[$category->group_key]['label'] ?? $category->group_key;
$letterheadMargins = $letterheadMargins ?? $category->resolvedLetterheadMarginsMm();
@endphp

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h4 class="card-title mb-0">Edit Agreement</h4>
          <div class="text-muted small mt-1">Group: {{ $groupLabel }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a href="{{ route('agreements.index', ['company_slug' => $companySlug]) }}" class="btn btn-outline-secondary btn-sm">
            Back
          </a>
        </div>
      </div>

      <div class="card-body">
        <form method="POST" action="{{ route('agreements.update-agreement', ['company_slug' => $companySlug, 'category' => $category->id]) }}" id="agreement-edit-form">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-lg-6">
              <div class="mb-3">
                <label class="form-label">Agreement Name</label>
                <input type="text" name="agreement_name" class="form-control" value="{{ old('agreement_name', $category->name) }}" required>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="mb-3">
                <label class="form-label">Agreement Code</label>
                <input type="text" name="agreement_code" class="form-control" value="{{ old('agreement_code', $category->agreement_code ?? $category->slug) }}" required>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $category->description) }}</textarea>
          </div>

          <div class="mb-4">
            <label class="form-label">Page layout</label>
            <p class="text-muted small mb-2">
              Adjust top, bottom, left, and right spacing for the content safe area on each page. Defaults are calculated from the header and footer layout.
            </p>

            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="printWithLetterhead" checked>
              <label class="form-check-label" for="printWithLetterhead">Print with Letterhead</label>
              <div class="form-text">When enabled, preview, print, and PDF download include the letterhead (uploaded design, or the company header if none is uploaded). When disabled, the content safe area is still reserved for pre-printed paper.</div>
            </div>

            <div class="mt-3 mb-3">
              <label class="form-label small fw-semibold">Letterhead</label>
              <p class="text-muted small mb-2">
                Choose the page background for preview, print, and PDF. Default uses the company logo and contact details. None leaves the page blank behind the text.
              </p>
              @php
                $selectedLetterhead = (string) old('letterhead_id', $category->letterheadChoiceValue());
                $libraryLetterheads = $letterheads ?? collect();
              @endphp
              <div class="d-flex flex-wrap gap-2 align-items-stretch">
                <label class="border rounded p-2 d-flex flex-column {{ $selectedLetterhead === 'none' ? 'border-primary' : '' }}" style="cursor:pointer;width:max-content;max-width:11rem;">
                  <span>
                    <input type="radio" name="letterhead_id" value="none" class="form-check-input me-1" {{ $selectedLetterhead === 'none' ? 'checked' : '' }}>
                    <strong>None</strong>
                  </span>
                  <div class="small text-muted mt-1">No header or design</div>
                </label>
                <label class="border rounded p-2 d-flex flex-column {{ $selectedLetterhead === 'default' ? 'border-primary' : '' }}" style="cursor:pointer;width:max-content;max-width:11rem;">
                  <span>
                    <input type="radio" name="letterhead_id" value="default" class="form-check-input me-1" {{ $selectedLetterhead === 'default' ? 'checked' : '' }}>
                    <strong>Default</strong>
                  </span>
                  <div class="small text-muted mt-1">Company logo and info</div>
                </label>
                @foreach($libraryLetterheads as $letterhead)
                @php $thumb = $letterhead->publicUrl(); @endphp
                <label class="border rounded p-2 d-flex flex-column {{ $selectedLetterhead === (string) $letterhead->id ? 'border-primary' : '' }}" style="cursor:pointer;width:max-content;max-width:11rem;">
                  <span>
                    <input type="radio" name="letterhead_id" value="{{ $letterhead->id }}" class="form-check-input me-1" {{ $selectedLetterhead === (string) $letterhead->id ? 'checked' : '' }}>
                    <strong>{{ $letterhead->name }}</strong>
                  </span>
                  @if($thumb)
                  <img src="{{ $thumb }}" alt="" class="d-block mt-2 border rounded" style="height:90px;width:auto;max-width:100%;object-fit:contain;background:#f8fafc;">
                  @endif
                </label>
                @endforeach
              </div>
              <p class="small mb-0 mt-2">
                <a href="{{ route('settings-panel.module-settings.index', ['company_slug' => $companySlug, 'module' => 'agreements']) }}#tab-letterhead">Manage letterheads in Settings</a>
              </p>
              @error('letterhead_id')
              <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <div class="mt-3 mb-3">
              <label class="form-label small fw-semibold">Watermark</label>
              <p class="text-muted small mb-2">
                Optional mark centred on every page, behind the contract text. Default uses the company logo.
              </p>
              @php
                $selectedWatermark = (string) old('watermark_id', $category->watermarkChoiceValue());
                $libraryWatermarks = $watermarks ?? collect();
                $companyLogoSrc = $pdfBranding['logo_src'] ?? ($pdfBranding['logo_url'] ?? null);
              @endphp
              <div class="d-flex flex-wrap gap-2 align-items-stretch">
                <label class="border rounded p-2 d-flex flex-column {{ $selectedWatermark === 'none' ? 'border-primary' : '' }}" style="cursor:pointer;width:max-content;max-width:11rem;">
                  <span>
                    <input type="radio" name="watermark_id" value="none" class="form-check-input me-1" {{ $selectedWatermark === 'none' ? 'checked' : '' }}>
                    <strong>None</strong>
                  </span>
                  <div class="small text-muted mt-1">No watermark</div>
                </label>
                <label class="border rounded p-2 d-flex flex-column {{ $selectedWatermark === 'default' ? 'border-primary' : '' }}" style="cursor:pointer;width:max-content;max-width:11rem;">
                  <span>
                    <input type="radio" name="watermark_id" value="default" class="form-check-input me-1" {{ $selectedWatermark === 'default' ? 'checked' : '' }}>
                    <strong>Default</strong>
                  </span>
                  <div class="small text-muted mt-1">Company logo</div>
                  @if($companyLogoSrc)
                  <img src="{{ $companyLogoSrc }}" alt="" class="d-block mt-2 border rounded" style="height:90px;width:auto;max-width:100%;object-fit:contain;background:#f8fafc;">
                  @endif
                </label>
                @foreach($libraryWatermarks as $watermark)
                @php $thumb = $watermark->publicUrl(); @endphp
                <label class="border rounded p-2 d-flex flex-column {{ $selectedWatermark === (string) $watermark->id ? 'border-primary' : '' }}" style="cursor:pointer;width:max-content;max-width:11rem;">
                  <span>
                    <input type="radio" name="watermark_id" value="{{ $watermark->id }}" class="form-check-input me-1" {{ $selectedWatermark === (string) $watermark->id ? 'checked' : '' }}>
                    <strong>{{ $watermark->name }}</strong>
                  </span>
                  @if($thumb)
                  <img src="{{ $thumb }}" alt="" class="d-block mt-2 border rounded" style="height:90px;width:auto;max-width:100%;object-fit:contain;background:#f8fafc;">
                  @endif
                </label>
                @endforeach
              </div>
              <p class="small mb-0 mt-2">
                <a href="{{ route('settings-panel.module-settings.index', ['company_slug' => $companySlug, 'module' => 'agreements']) }}#watermarks">Manage watermarks in Settings</a>
              </p>
              @error('watermark_id')
              <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <div class="mt-2">
              <label class="form-label small fw-semibold">Page size</label>
              @php
                $letterheadLayout = $letterheadLayout ?? app(\App\Services\Agreements\AgreementLetterheadLayout::class);
                $pageSizeCatalog = $letterheadLayout->pageSizeCatalog();
                $pageSizeKey = old('letterhead_margins.page_size', $letterheadLayout->resolvedPageSize($category)['key']);
              @endphp
              <select name="letterhead_margins[page_size]" class="form-select form-select-sm" style="max-width: 220px;">
                @foreach($pageSizeCatalog as $sizeKey => $size)
                <option value="{{ $sizeKey }}" @selected($pageSizeKey === $sizeKey)>
                  {{ $size['label'] }} ({{ rtrim(rtrim(number_format($size['width_mm'], 1, '.', ''), '0'), '.') }} × {{ rtrim(rtrim(number_format($size['height_mm'], 1, '.', ''), '0'), '.') }} mm)
                </option>
                @endforeach
              </select>
              <div class="form-text">Editor, preview, print, and PDF download all use this paper size. Default is A4.</div>
            </div>

            <div class="mt-2">
              <label class="form-label small fw-semibold">Content safe area</label>
              <div class="row g-2 mb-2">
                <div class="col-6 col-md-3">
                  <label class="form-label small mb-1">Top</label>
                  <input type="number" step="0.5" min="30" max="100" name="letterhead_margins[top]" class="form-control form-control-sm"
                    value="{{ old('letterhead_margins.top', $letterheadMargins['top']) }}">
                </div>
                <div class="col-6 col-md-3">
                  <label class="form-label small mb-1">Bottom</label>
                  <input type="number" step="0.5" min="0" max="50" name="letterhead_margins[bottom]" class="form-control form-control-sm"
                    value="{{ old('letterhead_margins.bottom', $letterheadMargins['bottom']) }}">
                </div>
                <div class="col-6 col-md-3">
                  <label class="form-label small mb-1">Left</label>
                  <input type="number" step="0.5" min="5" max="50" name="letterhead_margins[left]" class="form-control form-control-sm"
                    value="{{ old('letterhead_margins.left', $letterheadMargins['left']) }}">
                </div>
                <div class="col-6 col-md-3">
                  <label class="form-label small mb-1">Right</label>
                  <input type="number" step="0.5" min="5" max="50" name="letterhead_margins[right]" class="form-control form-control-sm"
                    value="{{ old('letterhead_margins.right', $letterheadMargins['right']) }}">
                </div>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Contract template <span class="text-danger">*</span></label>
            <p class="text-muted small mb-2">
              Select the template used when generating this contract. Edit its content below before saving.
            </p>
            <select name="contract_template_id" id="contract_template_id" class="form-select" required>
              @forelse($category->templates as $tpl)
              <option value="{{ $tpl->id }}"
                {{ (int) old('contract_template_id', $contractTemplateId) === (int) $tpl->id ? 'selected' : '' }}>
                {{ $tpl->template_name }}
              </option>
              @empty
              <option value="" disabled>No templates available</option>
              @endforelse
            </select>
            @error('contract_template_id')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
            @canany(['agreements_view', 'gn_settings'])
            <p class="small mb-0 mt-2">
              <a href="{{ route('agreements.templates', ['company_slug' => $companySlug, 'category' => $category->id]) }}">Manage templates</a>
              to create, duplicate, or edit named templates for this agreement.
            </p>
            @endcanany
          </div>

          @include('settings.agreements.partials.inline-template-editor')

          <div class="mb-3">
            <label class="form-label">Module <span class="text-danger">*</span></label>
            <p class="text-muted small mb-2">This agreement appears in the Action menu of the selected module.</p>
            <div class="row g-2">
              @php $savedModule = old('assigned_modules', $category->normalizedAssignedModules()[0] ?? ''); @endphp
              @foreach($modules as $moduleKey => $label)
              <div class="col-md-4 col-lg-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="assigned_modules" value="{{ $moduleKey }}"
                    id="mod_{{ $moduleKey }}"
                    {{ $savedModule === $moduleKey ? 'checked' : '' }}
                    required>
                  <label class="form-check-label" for="mod_{{ $moduleKey }}">
                    {{ $label }}
                  </label>
                </div>
              </div>
              @endforeach
            </div>
            @error('assigned_modules')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="status" value="1" id="statusSwitch"
                  {{ old('status', $category->status) ? 'checked' : '' }}>
                <label class="form-check-label" for="statusSwitch">Agreement enabled</label>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-device-floppy me-1"></i> Save Agreement
            </button>
            <a href="{{ route('agreements.index', ['company_slug' => $companySlug]) }}" class="btn btn-outline-secondary">
              Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@include('agreements.partials.print-letterhead-dialog')

@endsection

@push('third_party_scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var templateSelect = document.getElementById('contract_template_id');
    var editorWrap = document.getElementById('template-editor-wrap');
    var previewBase = @json(route('agreements.preview', ['company_slug' => $companySlug, 'id' => '__ID__']));
    var pdfBase = @json(route('agreements.preview-pdf', ['company_slug' => $companySlug, 'id' => '__ID__']));
    var letterheadStorageKey = 'agreement-print-letterhead-{{ $category->id }}';
    var letterheadToggle = document.getElementById('printWithLetterhead');
    var printDialog = document.getElementById('letterhead-print-dialog');
    var printCancelBtn = document.getElementById('letterhead-print-cancel');
    var activeTemplateId = templateSelect ? templateSelect.value : null;

    function useLetterhead() {
      return !letterheadToggle || letterheadToggle.checked;
    }

    function letterheadQuery() {
      return 'letterhead=' + (useLetterhead() ? '1' : '0');
    }

    function restoreLetterheadPreference() {
      if (!letterheadToggle) {
        return;
      }
      try {
        var saved = localStorage.getItem(letterheadStorageKey);
        if (saved === '0' || saved === '1') {
          letterheadToggle.checked = saved === '1';
        }
      } catch (e) {}
    }

    function persistLetterheadPreference() {
      if (!letterheadToggle) {
        return;
      }
      try {
        localStorage.setItem(letterheadStorageKey, useLetterhead() ? '1' : '0');
      } catch (e) {}
    }

    restoreLetterheadPreference();

    if (letterheadToggle) {
      letterheadToggle.addEventListener('change', function() {
        persistLetterheadPreference();
        updateOutputLinks(activeTemplateId);
      });
    }

    function storeFieldFor(id) {
      return document.getElementById('template_content_' + id);
    }

    function syncEditorToStore() {
      if (!activeTemplateId || !tinymce.get('agreement-template-editor')) {
        return;
      }
      var field = storeFieldFor(activeTemplateId);
      if (field) {
        field.value = tinymce.get('agreement-template-editor').getContent();
      }
    }

    function loadTemplateIntoEditor(id) {
      var field = storeFieldFor(id);
      if (!field || !tinymce.get('agreement-template-editor')) {
        return;
      }
      tinymce.get('agreement-template-editor').setContent(field.value || '');
    }

    function updatePreviewLink(id) {
      var link = document.getElementById('btn-template-preview');
      var pdfLink = document.getElementById('btn-template-download-pdf');
      var printBtn = document.getElementById('btn-template-print');
      if (!id) {
        if (link) link.classList.add('d-none');
        if (pdfLink) pdfLink.classList.add('d-none');
        if (printBtn) printBtn.classList.add('d-none');
        return;
      }

      var previewUrl = previewBase.replace('__ID__', id) + '?' + letterheadQuery();
      var pdfUrl = pdfBase.replace('__ID__', id) + '?' + letterheadQuery();

      if (link) {
        link.href = previewUrl;
        link.classList.remove('d-none');
      }
      if (pdfLink) {
        pdfLink.href = pdfUrl;
        pdfLink.classList.remove('d-none');
      }
      if (printBtn) {
        printBtn.classList.remove('d-none');
      }
    }

    function updateOutputLinks(id) {
      updatePreviewLink(id);
    }

    function openPrintPreview(useLetterhead) {
      if (!activeTemplateId) {
        return;
      }
      var url = previewBase.replace('__ID__', activeTemplateId) +
        '?letterhead=' + (useLetterhead ? '1' : '0') +
        '&autoprint=1';
      window.open(url, '_blank');
    }

    function showPrintDialog() {
      if (!activeTemplateId) {
        return;
      }

      if (!printDialog || typeof printDialog.showModal !== 'function') {
        var useLetterhead = window.confirm('Print with letterhead?\n\nOK = with letterhead\nCancel = without letterhead');
        openPrintPreview(useLetterhead);
        return;
      }

      printDialog.showModal();
    }

    if (printDialog) {
      printDialog.addEventListener('close', function() {
        var choice = printDialog.returnValue;
        if (choice !== 'with' && choice !== 'without') {
          return;
        }
        openPrintPreview(choice === 'with');
      });
    }

    if (printCancelBtn && printDialog) {
      printCancelBtn.addEventListener('click', function() {
        printDialog.close('cancel');
      });
    }

    if (templateSelect && editorWrap && window.erpbkAgreementWordEditor) {
      tinymce.init(window.erpbkAgreementWordEditor.config({
        selector: '#agreement-template-editor',
        setup: function(editor) {
          editor.on('change keyup', function() {
            syncEditorToStore();
          });
        }
      })).then(function() {
        if (activeTemplateId) {
          loadTemplateIntoEditor(activeTemplateId);
          updateOutputLinks(activeTemplateId);
        }
      });

      templateSelect.addEventListener('change', function() {
        syncEditorToStore();
        activeTemplateId = templateSelect.value;
        loadTemplateIntoEditor(activeTemplateId);
        updateOutputLinks(activeTemplateId);
      });

      document.querySelectorAll('.placeholder-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var ph = btn.getAttribute('data-placeholder');
          if (tinymce.get('agreement-template-editor')) {
            tinymce.get('agreement-template-editor').insertContent(ph);
            syncEditorToStore();
          }
        });
      });

      document.getElementById('agreement-edit-form').addEventListener('submit', function() {
        syncEditorToStore();
        if (tinymce.get('agreement-template-editor')) {
          tinymce.get('agreement-template-editor').save();
        }
      });

      var printBtn = document.getElementById('btn-template-print');
      if (printBtn) {
        printBtn.addEventListener('click', showPrintDialog);
      }
    }
  });
</script>
@endpush