@extends($layout ?? 'layouts.app', ['hideModuleTopBarSlider' => true])

@section('title', 'Edit Agreement – Settings')

@section('content')
@include('flash::message')

@php
$companySlug = request()->route('company_slug');
$groupLabel = $groups[$category->group_key]['label'] ?? $category->group_key;
$letterheadMargins = $letterheadMargins ?? $category->savedLetterheadMarginsMm();
$detectedLetterheadMargins = $detectedLetterheadMargins ?? null;
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
          <a href="{{ route('agreements.index', ['company_slug' => $companySlug, 'group' => $category->group_key]) }}" class="btn btn-outline-secondary btn-sm">
            Back
          </a>
        </div>
      </div>

      <div class="card-body">
        <form method="POST" action="{{ route('agreements.update-agreement', ['company_slug' => $companySlug, 'category' => $category->id]) }}" id="agreement-edit-form" enctype="multipart/form-data">
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
            <label class="form-label">Company letterhead</label>
            <p class="text-muted small mb-2">
              Upload a full A4 letterhead (210×297 mm) with your complete header and footer design. The image covers every page; agreement text is kept in a safe area below the header and above the footer automatically.
            </p>
            <input type="file" name="letterhead" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            @error('letterhead')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror

            <div class="mt-3">
              <label class="form-label small fw-semibold">Content safe area (mm)</label>
              <p class="text-muted small mb-2">Auto-calculated when you upload a letterhead. Increase top/bottom if any text touches your header or footer artwork on multi-page PDFs.</p>
              @if($detectedLetterheadMargins)
              <p class="text-muted small mb-2">
                Detected minimums from artwork:
                top {{ $detectedLetterheadMargins['top'] }},
                bottom {{ $detectedLetterheadMargins['bottom'] }},
                left {{ $detectedLetterheadMargins['left'] }},
                right {{ $detectedLetterheadMargins['right'] }} mm.
                PDFs use the larger of your values and these minimums.
              </p>
              @endif
              <div class="row g-2">
                <div class="col-6 col-md-3">
                  <label class="form-label small mb-1">Top</label>
                  <input type="number" step="0.5" min="10" max="110" name="letterhead_margins[top]" class="form-control form-control-sm"
                    value="{{ old('letterhead_margins.top', $letterheadMargins['top']) }}">
                </div>
                <div class="col-6 col-md-3">
                  <label class="form-label small mb-1">Bottom</label>
                  <input type="number" step="0.5" min="10" max="120" name="letterhead_margins[bottom]" class="form-control form-control-sm"
                    value="{{ old('letterhead_margins.bottom', $letterheadMargins['bottom']) }}">
                </div>
                <div class="col-6 col-md-3">
                  <label class="form-label small mb-1">Left</label>
                  <input type="number" step="0.5" min="8" max="50" name="letterhead_margins[left]" class="form-control form-control-sm"
                    value="{{ old('letterhead_margins.left', $letterheadMargins['left']) }}">
                </div>
                <div class="col-6 col-md-3">
                  <label class="form-label small mb-1">Right</label>
                  <input type="number" step="0.5" min="8" max="50" name="letterhead_margins[right]" class="form-control form-control-sm"
                    value="{{ old('letterhead_margins.right', $letterheadMargins['right']) }}">
                </div>
              </div>
            </div>

            @if($category->hasLetterhead())
            <div class="mt-3 p-3 border rounded bg-light">
              <div class="d-flex flex-wrap gap-3 align-items-start">
                <img src="{{ storage_url($category->letterhead_path) }}" alt="Letterhead preview"
                  style="max-width: 280px; max-height: 200px; object-fit: contain; border: 1px solid #dee2e6; background: #fff;">
                <div>
                  <p class="small text-muted mb-2">Current letterhead — shown on all PDFs for this agreement.</p>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remove_letterhead" value="1" id="removeLetterhead">
                    <label class="form-check-label text-danger" for="removeLetterhead">Remove letterhead</label>
                  </div>
                </div>
              </div>
            </div>
            @endif
          </div>

          <div class="mb-3">
            <label class="form-label">Contract template <span class="text-danger">*</span></label>
            <p class="text-muted small mb-2">
              Select the sample template style used when generating this contract. Edit its content below before saving.
            </p>
            <select name="contract_template_id" id="contract_template_id" class="form-select" required>
              @forelse($category->templates as $tpl)
              <option value="{{ $tpl->id }}"
                data-type="{{ $tpl->template_type }}"
                {{ (int) old('contract_template_id', $contractTemplateId) === (int) $tpl->id ? 'selected' : '' }}>
                {{ $tpl->template_name }}
                — {{ \App\Models\AgreementTemplate::TYPES[$tpl->template_type] ?? $tpl->template_type }}
              </option>
              @empty
              <option value="" disabled>No sample templates available</option>
              @endforelse
            </select>
            @error('contract_template_id')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

          @include('settings.agreements.partials.inline-template-editor')

          <div class="mb-3">
            <label class="form-label">Assigned Modules <span class="text-danger">*</span></label>
            <p class="text-muted small mb-2">Selected modules will show this agreement in their Agreements menu and contract options.</p>
            <div class="row g-2">
              @php $savedModules = $category->normalizedAssignedModules(); @endphp
              @foreach($modules as $moduleKey => $label)
              <div class="col-md-4 col-lg-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="assigned_modules[]" value="{{ $moduleKey }}"
                    id="mod_{{ $moduleKey }}"
                    {{ in_array($moduleKey, old('assigned_modules', $savedModules), true) ? 'checked' : '' }}>
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
            @error('assigned_modules.*')
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
            <a href="{{ route('agreements.index', ['company_slug' => $companySlug, 'group' => $category->group_key]) }}" class="btn btn-outline-secondary">
              Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var templateSelect = document.getElementById('contract_template_id');
    var editorWrap = document.getElementById('template-editor-wrap');
    var previewBase = @json(route('agreements.preview', ['company_slug' => $companySlug, 'id' => '__ID__']));
    var activeTemplateId = templateSelect ? templateSelect.value : null;

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
      var link = document.getElementById('btn-template-full-preview');
      if (!link || !id) {
        return;
      }
      link.href = previewBase.replace('__ID__', id);
      link.classList.remove('d-none');
    }

    if (templateSelect && editorWrap) {
      tinymce.init({
        selector: '#agreement-template-editor',
        height: 380,
        menubar: false,
        plugins: 'lists link table code fullscreen preview',
        toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | table link | code fullscreen',
        branding: false,
        promotion: false,
        content_style: 'body { font-family: Calibri, sans-serif; font-size: 11pt; line-height: 1.5; }',
        setup: function(editor) {
          editor.on('change keyup', function() {
            syncEditorToStore();
          });
        }
      }).then(function() {
        if (activeTemplateId) {
          loadTemplateIntoEditor(activeTemplateId);
          updatePreviewLink(activeTemplateId);
        }
      });

      templateSelect.addEventListener('change', function() {
        syncEditorToStore();
        activeTemplateId = templateSelect.value;
        loadTemplateIntoEditor(activeTemplateId);
        updatePreviewLink(activeTemplateId);
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

      var quickPreview = document.getElementById('btn-template-quick-preview');
      if (quickPreview) {
        quickPreview.addEventListener('click', function() {
          syncEditorToStore();
          var content = tinymce.get('agreement-template-editor') ?
            tinymce.get('agreement-template-editor').getContent() :
            '';
          var w = window.open('', '_blank');
          var primary = getComputedStyle(document.getElementById('template-content-panel')).getPropertyValue('--agreement-primary').trim() || '#2563eb';
          w.document.write('<html><head><style>body{font-family:Calibri,sans-serif;padding:24px;} .hdr{background:' + primary + ';color:#fff;padding:16px;margin:-24px -24px 20px;}</style></head><body>');
          w.document.write('<div class="hdr"><strong>Content preview</strong></div>');
          w.document.write(content);
          w.document.write('</body></html>');
          w.document.close();
        });
      }
    }
  });
</script>
@endpush