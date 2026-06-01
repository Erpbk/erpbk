{{-- Per-company module menu icons — Tabler online library picker + optional image upload. --}}
@php
$moduleMenuKey = $moduleMenuKey ?? ($moduleKey ?? '');
$menuDropdownContext = $moduleMenuKey !== ''
  ? \App\Support\MenuDropdownRegistry::contextForModuleKey($moduleMenuKey)
  : null;
$iconKeys = $menuDropdownContext
  ? array_merge(
      [['key' => $menuDropdownContext['parent_key'], 'label' => 'Main dropdown heading', 'default' => $menuDropdownContext['parent_default']]],
      array_map(fn ($c) => ['key' => $c['key'], 'label' => 'Dropdown item: ' . $c['default'], 'default' => $c['default']], $menuDropdownContext['children'])
    )
  : [['key' => $moduleMenuKey, 'label' => 'Module icon', 'default' => $defaultLabel ?? $moduleMenuKey]];
$libraryLabel = config('menu_icons.library.provider_label', 'Tabler Icons');
$libraryUrl = config('menu_icons.library.provider_url', 'https://tabler.io/icons');
$searchUrl = route('settings-panel.menu-icons.library-search', ['company_slug' => request()->route('company_slug') ?? session('company_slug')]);
$saveUrl = route('settings-panel.menu-icons.library-save', ['company_slug' => request()->route('company_slug') ?? session('company_slug')]);
$uploadUrl = route('settings-panel.menu-icons.library-upload', ['company_slug' => request()->route('company_slug') ?? session('company_slug')]);
$csrf = csrf_token();
@endphp
<div class="card border mt-4" id="module-menu-icons-card">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h6 class="mb-0">Menu icons</h6>
      <small class="text-muted">Search {{ $libraryLabel }} — saved per company, updates sidebar instantly.</small>
    </div>
    <a href="{{ $libraryUrl }}" target="_blank" rel="noopener" class="small">{{ $libraryLabel }} ↗</a>
  </div>
  <div class="card-body">
    <div class="row g-4">
      @foreach($iconKeys as $iconRow)
      @php
      $iconKey = $iconRow['key'];
      $iconData = \App\Models\Settings::getMenuIcon($iconKey);
      $isImage = ($iconData['type'] ?? 'class') === 'image' && !empty($iconData['url']);
      $currentClass = $isImage ? '' : ($iconData['class'] ?? config('menu_icons.defaults.' . $iconKey, 'ti-adjustments-alt'));
      @endphp
      <div class="col-md-6">
        <div class="border rounded p-3 h-100 module-icon-field-card" data-menu-key="{{ $iconKey }}">
          <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
            <div>
              <div class="fw-semibold">{{ $iconRow['label'] }}</div>
              <code class="small text-muted">{{ $iconKey }}</code>
            </div>
            <span class="module-icon-preview border rounded p-2 bg-light" data-preview-key="{{ $iconKey }}">
              @include('layouts.partials.module_menu_icon', ['key' => $iconKey])
            </span>
          </div>
          @if($isImage)
          <div class="alert alert-info py-2 small mb-2">Using uploaded image. Pick a library icon or reset to replace it.</div>
          @else
          <div class="small text-muted mb-2">Current: <code class="module-icon-current-class">{{ $currentClass }}</code></div>
          @endif
          <div class="d-flex flex-wrap gap-2 mb-2">
            <button type="button"
              class="btn btn-sm btn-primary btn-open-icon-picker"
              data-menu-key="{{ $iconKey }}"
              data-field-label="{{ $iconRow['label'] }}">
              <i class="ti ti-icons"></i> Choose icon
            </button>
            <button type="button"
              class="btn btn-sm btn-outline-secondary btn-reset-module-icon"
              data-menu-key="{{ $iconKey }}">
              Reset default
            </button>
          </div>
          <details class="small">
            <summary class="text-muted cursor-pointer">Upload custom image (optional)</summary>
            <div class="mt-2">
              <input type="file"
                class="form-control form-control-sm module-icon-file-input"
                data-menu-key="{{ $iconKey }}"
                accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml">
            </div>
          </details>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>

{{-- Icon picker modal --}}
<div class="modal fade" id="moduleIconPickerModal" tabindex="-1" aria-labelledby="moduleIconPickerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="moduleIconPickerModalLabel">Choose icon</h5>
          <div class="small text-muted" id="moduleIconPickerSubtitle"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="ti ti-search"></i></span>
          <input type="text" class="form-control" id="moduleIconPickerSearch" placeholder="Search icons (e.g. fine, bike, user, receipt)..." autocomplete="off">
        </div>
        <div id="moduleIconPickerLoading" class="text-center text-muted py-4 d-none">
          <div class="spinner-border spinner-border-sm" role="status"></div> Loading icons…
        </div>
        <div id="moduleIconPickerEmpty" class="text-center text-muted py-4 d-none">No icons match your search.</div>
        <div id="moduleIconPickerGrid" class="module-icon-picker-grid"></div>
      </div>
      <div class="modal-footer">
        <span class="small text-muted me-auto">Powered by <a href="{{ $libraryUrl }}" target="_blank" rel="noopener">{{ $libraryLabel }}</a></span>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@once
@push('page-scripts')
<style>
  .module-icon-picker-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(88px, 1fr));
    gap: 8px;
    max-height: 360px;
    overflow-y: auto;
  }
  .module-icon-picker-item {
    border: 1px solid var(--bs-border-color);
    border-radius: 8px;
    padding: 10px 6px;
    text-align: center;
    cursor: pointer;
    background: var(--bs-body-bg);
    transition: border-color 0.15s, background 0.15s;
  }
  .module-icon-picker-item:hover,
  .module-icon-picker-item.is-selected {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.08);
  }
  .module-icon-picker-item i {
    font-size: 1.5rem;
    display: block;
    margin-bottom: 4px;
  }
  .module-icon-picker-item span {
    display: block;
    font-size: 0.65rem;
    line-height: 1.2;
    color: var(--bs-secondary-color);
    word-break: break-word;
  }
  .module-icon-preview .menu-icon-slot {
    font-size: 1.25rem;
  }
</style>
<script>
(function() {
  if (window.__moduleIconPickerInit) return;

  var searchUrl = @json($searchUrl);
  var saveUrl = @json($saveUrl);
  var uploadUrl = @json($uploadUrl);
  var csrf = @json($csrf);

  function getModal() {
    var modalEl = document.getElementById('moduleIconPickerModal');
    if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
      return null;
    }
    return bootstrap.Modal.getOrCreateInstance(modalEl);
  }

  function initModuleIconPicker() {
    if (window.__moduleIconPickerInit) return true;
    if (!document.getElementById('moduleIconPickerModal')) return false;
    if (!getModal()) return false;
    window.__moduleIconPickerInit = true;
    var activeMenuKey = null;
    var searchTimer = null;

    function el(id) { return document.getElementById(id); }

    function applyIconHtml(menuKey, html) {
      function patchDoc(doc) {
        if (!doc) return;
        doc.querySelectorAll('[data-menu-icon-key="' + menuKey + '"]').forEach(function(slot) {
          slot.innerHTML = html;
        });
      }
      patchDoc(document);
      if (window.opener && !window.opener.closed) {
        try { patchDoc(window.opener.document); } catch (e) { /* cross-origin */ }
      }
      var card = document.querySelector('.module-icon-field-card[data-menu-key="' + menuKey + '"]');
      if (card) {
        var code = card.querySelector('.module-icon-current-class');
        if (code && html.indexOf('menu-icon-custom') === -1) {
          var m = html.match(/ti\s+(ti-[a-z0-9-]+)/);
          if (m) code.textContent = m[1];
        }
        var alert = card.querySelector('.alert-info');
        if (alert && html.indexOf('menu-icon-custom') === -1) alert.remove();
      }
    }

    function toast(msg, type) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({ toast: true, position: 'top-end', icon: type || 'success', title: msg, showConfirmButton: false, timer: 2200 });
      }
    }

    function renderGrid(icons) {
      var grid = el('moduleIconPickerGrid');
      var emptyEl = el('moduleIconPickerEmpty');
      if (!grid || !emptyEl) return;
      grid.innerHTML = '';
      if (!icons || !icons.length) {
        emptyEl.classList.remove('d-none');
        return;
      }
      emptyEl.classList.add('d-none');
      icons.forEach(function(icon) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'module-icon-picker-item';
        btn.title = icon.class;
        btn.innerHTML = '<i class="ti ' + icon.class + '"></i><span>' + icon.label + '</span>';
        btn.addEventListener('click', function() {
          saveIcon(activeMenuKey, icon.class);
        });
        grid.appendChild(btn);
      });
    }

    function loadIcons(q) {
      var grid = el('moduleIconPickerGrid');
      var loadingEl = el('moduleIconPickerLoading');
      var emptyEl = el('moduleIconPickerEmpty');
      if (!grid || !loadingEl || !emptyEl) return;
      loadingEl.classList.remove('d-none');
      emptyEl.classList.add('d-none');
      grid.innerHTML = '';
      fetch(searchUrl + '?q=' + encodeURIComponent(q || ''), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          loadingEl.classList.add('d-none');
          renderGrid(data.icons || []);
        })
        .catch(function() {
          loadingEl.classList.add('d-none');
          emptyEl.classList.remove('d-none');
          emptyEl.textContent = 'Could not load icon library.';
        });
    }

    function saveIcon(menuKey, iconClass, reset) {
      var body = new FormData();
      body.append('_token', csrf);
      body.append('menu_key', menuKey);
      if (reset) {
        body.append('reset', '1');
      } else {
        body.append('icon_class', iconClass);
      }
      fetch(saveUrl, {
        method: 'POST',
        body: body,
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
        .then(function(res) {
          if (!res.ok || !res.data.success) {
            toast((res.data && res.data.message) || 'Could not save icon.', 'error');
            return;
          }
          applyIconHtml(menuKey, res.data.html);
          toast(res.data.message || 'Icon updated.');
          var modal = getModal();
          if (modal) modal.hide();
        })
        .catch(function() { toast('Could not save icon.', 'error'); });
    }

    function openPicker(btn) {
      var modal = getModal();
      if (!modal) {
        console.error('Module icon picker: Bootstrap Modal is not available.');
        return;
      }
      activeMenuKey = btn.getAttribute('data-menu-key');
      var titleEl = el('moduleIconPickerModalLabel');
      var subtitleEl = el('moduleIconPickerSubtitle');
      if (titleEl) titleEl.textContent = 'Choose icon';
      if (subtitleEl) subtitleEl.textContent = btn.getAttribute('data-field-label') || activeMenuKey;
      var searchInput = el('moduleIconPickerSearch');
      if (searchInput) {
        searchInput.value = '';
      }
      loadIcons('');
      modal.show();
      var pickerModalEl = el('moduleIconPickerModal');
      if (searchInput && pickerModalEl) {
        pickerModalEl.addEventListener('shown.bs.modal', function focusSearch() {
          searchInput.focus();
          pickerModalEl.removeEventListener('shown.bs.modal', focusSearch);
        });
      }
    }

    var searchInput = el('moduleIconPickerSearch');
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        var q = this.value;
        searchTimer = setTimeout(function() { loadIcons(q); }, 280);
      });
    }

    document.addEventListener('click', function(ev) {
      var openBtn = ev.target.closest('.btn-open-icon-picker');
      if (openBtn && document.getElementById('module-menu-icons-card') && document.getElementById('module-menu-icons-card').contains(openBtn)) {
        ev.preventDefault();
        openPicker(openBtn);
        return;
      }
      var resetBtn = ev.target.closest('.btn-reset-module-icon');
      if (resetBtn && document.getElementById('module-menu-icons-card') && document.getElementById('module-menu-icons-card').contains(resetBtn)) {
        var key = resetBtn.getAttribute('data-menu-key');
        if (!key || !confirm('Reset this menu icon to the system default?')) return;
        saveIcon(key, '', true);
      }
    });

    document.addEventListener('change', function(ev) {
      var input = ev.target.closest('.module-icon-file-input');
      if (!input || !document.getElementById('module-menu-icons-card') || !document.getElementById('module-menu-icons-card').contains(input)) return;
      var key = input.getAttribute('data-menu-key');
      var file = input.files && input.files[0];
      if (!key || !file) return;
      var body = new FormData();
      body.append('_token', csrf);
      body.append('menu_key', key);
      body.append('icon_image', file);
      fetch(uploadUrl, {
        method: 'POST',
        body: body,
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
        .then(function(res) {
          if (!res.ok || !res.data.success) {
            toast((res.data && res.data.message) || 'Upload failed.', 'error');
            return;
          }
          applyIconHtml(key, res.data.html);
          toast('Image icon saved.');
          input.value = '';
        })
        .catch(function() { toast('Upload failed.', 'error'); });
    });

    return true;
  }

  function bootModuleIconPicker() {
    if (initModuleIconPicker()) return;
    window.addEventListener('load', function onLoad() {
      if (!window.__moduleIconPickerInit) initModuleIconPicker();
    }, { once: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootModuleIconPicker);
  } else {
    bootModuleIconPicker();
  }
})();
</script>
@endpush
@endonce
