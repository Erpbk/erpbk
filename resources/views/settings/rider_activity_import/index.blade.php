@extends('layouts.settingsPanelLayout')

@section('title', 'Activity Import Settings')

@push('third_party_stylesheets')
<style>
  .ais-page-header {
    margin-bottom: 1.25rem;
  }

  .ais-back-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #4b5563;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .ais-back-btn:hover {
    background: #f8fafc;
    color: #111827;
  }

  .ais-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
    display: flex;
    align-items: center;
    gap: .4rem;
  }

  .ais-subtitle {
    color: #6b7280;
    font-size: .875rem;
    margin: .2rem 0 0;
  }

  .ais-tabs .nav-link {
    color: #6b7280;
    font-weight: 600;
    border: 0;
    border-bottom: 2px solid transparent;
    padding: .7rem 1rem;
  }

  .ais-tabs .nav-link.active {
    color: #2563eb;
    background: transparent;
    border-bottom-color: #2563eb;
  }

  .ais-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
  }

  .ais-badge-success {
    background: #dcfce7;
    color: #166534;
    font-weight: 600;
    border-radius: 999px;
    padding: .35rem .75rem;
    font-size: .8rem;
  }

  .ais-badge-primary {
    background: #dbeafe;
    color: #1d4ed8;
    font-weight: 600;
    border-radius: 999px;
    padding: .35rem .75rem;
    font-size: .8rem;
  }

  .ais-badge-warning {
    background: #fef3c7;
    color: #92400e;
    font-weight: 600;
    border-radius: 999px;
    padding: .35rem .75rem;
    font-size: .8rem;
  }

  .ais-map-table {
    margin-bottom: 0;
  }

  .ais-map-table thead th {
    background: #1e293b;
    color: #fff;
    font-weight: 600;
    font-size: .8rem;
    letter-spacing: .02em;
    white-space: nowrap;
    border-color: #1e293b;
    padding: .7rem .75rem;
  }

  .ais-map-table tbody td {
    vertical-align: middle;
    padding: .65rem .75rem;
    border-color: #e5e7eb;
    background: #fff;
  }

  .ais-map-table tbody tr:hover td {
    background: #f8fafc;
  }

  .ais-drag-handle {
    cursor: grab;
    color: #94a3b8;
    font-size: 1.1rem;
  }

  .ais-drag-handle:active {
    cursor: grabbing;
  }

  .ais-required-wrap {
    display: flex;
    align-items: center;
    gap: .5rem;
    min-width: 92px;
  }

  .ais-required-wrap .form-check-input {
    width: 2.1rem;
    height: 1.15rem;
    cursor: pointer;
  }

  .ais-required-label {
    font-size: .8rem;
    font-weight: 600;
    color: #64748b;
  }

  .ais-required-wrap.is-yes .ais-required-label {
    color: #2563eb;
  }

  .ais-info-banner {
    background: #eff6ff;
    color: #1e40af;
    border-radius: 8px;
    padding: .7rem .9rem;
    font-size: .85rem;
  }

  .ais-help-link {
    color: #2563eb;
    font-size: .9rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
  }

  .ais-help-link:hover {
    color: #1d4ed8;
  }

  .ais-preview-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
    background: #fff;
    min-height: 280px;
  }

  .ais-preview-empty {
    min-height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #9ca3af;
    font-size: 1.05rem;
    font-weight: 500;
  }

  .ais-preview-empty:hover {
    background: #fafbfc;
  }

  .excel-preview-wrap {
    overflow: auto;
    max-height: 420px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
  }

  .excel-preview-table {
    border-collapse: collapse;
    font-size: 12px;
    min-width: 100%;
    margin: 0;
  }

  .excel-preview-table th,
  .excel-preview-table td {
    border: 1px solid #d1d5db;
    padding: 6px 10px;
    white-space: nowrap;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .excel-preview-table thead th {
    background: #1e293b;
    color: #fff;
    position: sticky;
    top: 0;
    z-index: 2;
    font-weight: 600;
  }

  .excel-preview-table .map-row th {
    background: #dbeafe;
    color: #1e40af;
    position: sticky;
    top: 31px;
    z-index: 1;
    font-weight: 600;
  }

  .excel-preview-table .skipped-row td {
    background: #f3f4f6;
    color: #9ca3af;
  }

  .excel-preview-table .mapped-col {
    background: #eff6ff;
  }

  .excel-preview-table .row-num {
    background: #f1f5f9;
    color: #94a3b8;
    font-weight: 600;
    text-align: center;
    min-width: 36px;
    pointer-events: none;
    cursor: default;
    user-select: none;
    opacity: .75;
  }

  .ais-sortable-ghost {
    opacity: .45;
  }
</style>
@endpush

@section('content')
@include('flash::message')
@php
$companySlug = request()->route('company_slug') ?? session('company_slug');
$importType = $importType ?? \App\Services\RiderActivities\RiderActivityImportMappingService::TYPE_RIDER;
$importTypeLabels = $importTypeLabels ?? \App\Services\RiderActivities\RiderActivityImportMappingService::importTypeLabels();
$excelColumnChoices = $excelColumnChoices ?? \App\Services\RiderActivities\RiderActivityImportMappingService::excelColumnChoices();
$indexUrl = route('settings-panel.rider-activity-import-settings.index', ['company_slug' => $companySlug]);
$storeUrl = route('settings-panel.rider-activity-import-settings.store', ['company_slug' => $companySlug]);
$previewUrl = route('settings-panel.rider-activity-import-settings.preview', ['company_slug' => $companySlug]);
$exportUrl = route('settings-panel.rider-activity-import-settings.export-template', ['company_slug' => $companySlug]);
$backUrl = route('settings-panel.index', ['company_slug' => $companySlug]);
@endphp

<div class="ais-page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
  <div class="d-flex align-items-start gap-3">
    <a href="{{ $backUrl }}" class="ais-back-btn mt-1" title="Back to settings">
      <i class="ti ti-arrow-left"></i>
    </a>
    <div>
      <h4 class="ais-title">
        Activity Import Settings
        <i class="ti ti-info-circle text-muted" style="font-size: 1rem;" data-bs-toggle="tooltip" title="Map each system field to an Excel column for the selected project."></i>
      </h4>
      <p class="ais-subtitle">Configure Excel column mappings for rider activities and live activities.</p>
    </div>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-outline-primary" id="ais-import-preview-btn">
      <i class="ti ti-eye me-1"></i> Import Preview
    </button>
    <button type="button" class="btn btn-outline-secondary" id="ais-export-template-btn">
      <i class="ti ti-download me-1"></i> Export Template
    </button>
    <div class="dropdown">
      <button class="btn btn-outline-secondary btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More">
        <i class="ti ti-dots-vertical"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <a class="dropdown-item" href="{{ url('sample/noon_activity_sample.xlsx') }}" download>
            <i class="ti ti-file-download me-2"></i> Download Noon sample
          </a>
        </li>
        <li>
          <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#aisMappingHelpModal">
            <i class="ti ti-help me-2"></i> How mapping works
          </button>
        </li>
      </ul>
    </div>
  </div>
</div>

<ul class="nav nav-tabs ais-tabs mb-4">
  @foreach($importTypeLabels as $typeKey => $typeLabel)
  <li class="nav-item">
    <a
      class="nav-link @if($importType === $typeKey) active @endif"
      href="{{ route('settings-panel.rider-activity-import-settings.index', ['company_slug' => $companySlug, 'import_type' => $typeKey, 'customer_id' => $selectedCustomerId]) }}">
      {{ $typeLabel }}
    </a>
  </li>
  @endforeach
</ul>

<div class="card ais-card mb-4">
  <div class="card-body">
    <form method="GET" action="{{ $indexUrl }}" class="row g-3 align-items-end mb-4" id="ais-project-form">
      <input type="hidden" name="import_type" value="{{ $importType }}">
      <div class="col-md-5">
        <label class="form-label" for="customer_id">Project</label>
        <select name="customer_id" id="customer_id" class="form-select" onchange="this.form.submit()">
          @foreach($customers as $customer)
          <option value="{{ $customer->id }}" @selected((int) $customer->id === (int) $selectedCustomerId)>
            {{ $customer->name }} (ID: {{ $customer->id }})
          </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-7 d-flex align-items-end pb-1">
        @if((int) $selectedCustomerId === (int) $defaultCustomerId)
        <span class="ais-badge-success"><i class="ti ti-check me-1"></i> Built-in import format active</span>
        @elseif(in_array((int) $selectedCustomerId, $configuredCustomerIds, true))
        <span class="ais-badge-primary">Custom mappings configured</span>
        @else
        <span class="ais-badge-warning">Not configured — import disabled until mappings are saved</span>
        @endif
      </div>
    </form>

    <form method="POST" action="{{ $storeUrl }}" id="ais-settings-form">
      @csrf
      <input type="hidden" name="customer_id" value="{{ $selectedCustomerId }}">
      <input type="hidden" name="import_type" value="{{ $importType }}">

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label" for="header_rows_to_skip">Header rows to skip</label>
          <select class="form-select" id="header_rows_to_skip" name="header_rows_to_skip" required>
            @for($i = 0; $i <= 10; $i++)
              <option value="{{ $i }}" @selected((int) old('header_rows_to_skip', $headerRowsToSkip)===$i)>{{ $i }}</option>
              @endfor
          </select>
          <small class="text-muted">Number of top rows to ignore before data (Noon default: 2).</small>
        </div>
        <div class="col-md-4 d-flex align-items-center pt-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $storedSetting?->is_active ?? true))>
            <label class="form-check-label" for="is_active">Import enabled for this project</label>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered ais-map-table" id="ais-mapping-table">
          <thead>
            <tr>
              <th style="width: 42px;"></th>
              <th style="width: 120px;">Required</th>
              <th>System Field</th>
              <th style="width: 180px;">Excel Column</th>
              <th style="width: 120px;">Noon Default</th>
              <th style="width: 110px;" class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody id="ais-mapping-tbody">
            @foreach($fieldLabels as $fieldKey => $fieldLabel)
            @php
            $currentValue = (int) old('column_mappings.' . $fieldKey, $columnMappings[$fieldKey] ?? $defaultMappings[$fieldKey]);
            $isRequired = !empty($requiredFields[$fieldKey]);
            $isLocked = in_array($fieldKey, ['date', 'rider_id'], true);
            @endphp
            <tr class="ais-map-row" data-field="{{ $fieldKey }}" data-label="{{ $fieldLabel }}" data-locked="{{ $isLocked ? '1' : '0' }}">
              <td class="text-center">
                <span class="ais-drag-handle" title="Drag to reorder"><i class="ti ti-grip-vertical"></i></span>
              </td>
              <td>
                <div class="ais-required-wrap {{ $isRequired ? 'is-yes' : '' }}">
                  <div class="form-check form-switch mb-0">
                    <input
                      class="form-check-input ais-required-toggle"
                      type="checkbox"
                      role="switch"
                      name="required_fields[]"
                      value="{{ $fieldKey }}"
                      @checked($isRequired)
                      @disabled($isLocked)
                      data-field="{{ $fieldKey }}">
                  </div>
                  <span class="ais-required-label">{{ $isRequired ? 'Yes' : 'No' }}</span>
                </div>
              </td>
              <td>
                <span class="fw-medium">{{ $fieldLabel }}</span>
                <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Maps this system field to an Excel column in the uploaded file."></i>
              </td>
              <td>
                <input type="hidden" name="column_mappings[{{ $fieldKey }}]" class="column-index-input" value="{{ $currentValue }}" data-field="{{ $fieldKey }}" @if($isLocked) required @endif>
                <select class="form-select form-select-sm excel-column-select" data-field="{{ $fieldKey }}">
                  @foreach($excelColumnChoices as $colIndex => $colLetter)
                  <option value="{{ $colIndex }}" @selected($currentValue===(int) $colIndex)>{{ $colLetter }}</option>
                  @endforeach
                </select>
              </td>
              <td class="text-muted">{{ $defaultMappings[$fieldKey] ?? '—' }}</td>
              <td class="text-center">
                <button type="button" class="btn btn-sm btn-icon text-primary ais-edit-row" title="Edit mapping">
                  <i class="ti ti-pencil"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon text-danger ais-delete-row" title="Remove field" @disabled($isLocked)>
                  <i class="ti ti-trash"></i>
                </button>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center gap-2 mt-3 mb-3">
        <button type="button" class="btn btn-outline-primary" id="ais-add-row-btn">
          <i class="ti ti-plus me-1"></i> Add Row (System Field)
        </button>
        <button type="button" class="btn btn-outline-success" id="ais-add-column-btn">
          <i class="ti ti-plus me-1"></i> Add Column (Excel Column)
        </button>
      </div>

      <div class="ais-info-banner mb-4">
        <i class="ti ti-info-circle me-1"></i>
        Drag and drop rows using the handle to reorder system fields.
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="ti ti-device-floppy me-1"></i> Save Import Settings
        </button>
        <button type="button" class="btn btn-outline-secondary" id="reset-defaults-btn">
          <i class="ti ti-refresh me-1"></i> Reset to Noon Defaults
        </button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="aisMappingHelpModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">How does column mapping work?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Each <strong>system field</strong> is mapped to an Excel column letter (A, B, C, …).</p>
        <ul class="mb-2">
          <li><strong>Header rows to skip</strong> ignores title rows at the top of the file before data starts. Noon files skip 2 rows.</li>
          <li>In the preview, mapped columns are highlighted and labeled with the system field name.</li>
          <li>Date and Rider ID are required. Other fields can be mapped or removed from this table.</li>
        </ul>
        <p class="mb-0 text-muted small">Column A = index 0, B = 1, C = 2, and so on. Import uses the saved mapping for the selected project.</p>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="aisEditRowModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit mapping</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ais-edit-field">
        <div class="mb-3">
          <label class="form-label">System Field</label>
          <input type="text" class="form-control" id="ais-edit-label" readonly>
        </div>
        <div class="mb-0">
          <label class="form-label" for="ais-edit-column">Excel Column</label>
          <select class="form-select" id="ais-edit-column"></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="ais-edit-save-btn">Save</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const defaultMappings = @json($defaultMappings);
    const fieldLabels = @json($fieldLabels);
    const requiredFields = @json($requiredFields);
    const staticRequiredFields = @json(\App\Services\RiderActivities\RiderActivityImportMappingService::requiredFields());
    const defaultHeaderRows = {{ (int) \App\Services\RiderActivities\RiderActivityImportMappingService::defaultHeaderRowsToSkip() }};
    const previewUrl = @json($previewUrl);
    const exportUrl = @json($exportUrl);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const tbody = document.getElementById('ais-mapping-tbody');
    const headerSkip = document.getElementById('header_rows_to_skip');
    const previewFileInput = document.getElementById('ais-preview-file');
    const previewEmpty = document.getElementById('ais-preview-empty');
    const previewGrid = document.getElementById('ais-preview-grid');
    const previewMeta = document.getElementById('ais-preview-meta');
    const editModalEl = document.getElementById('aisEditRowModal');
    const editModal = editModalEl && window.bootstrap ? new bootstrap.Modal(editModalEl) : null;

    const indexToLetter = (index) => {
      let n = parseInt(index, 10);
      if (Number.isNaN(n) || n < 0) {
        return '';
      }
      let letter = '';
      n += 1;
      while (n > 0) {
        n--;
        letter = String.fromCharCode(65 + (n % 26)) + letter;
        n = Math.floor(n / 26);
      }
      return letter;
    };

    const currentMaxIndex = () => {
      const firstSelect = document.querySelector('.excel-column-select');
      if (!firstSelect) {
        return 25;
      }
      const last = firstSelect.options[firstSelect.options.length - 1];
      return last ? parseInt(last.value, 10) : 25;
    };

    const rebuildColumnSelects = (maxIndex) => {
      document.querySelectorAll('.excel-column-select').forEach((select) => {
        const current = select.value;
        select.innerHTML = '';
        for (let i = 0; i <= maxIndex; i++) {
          const option = document.createElement('option');
          option.value = String(i);
          option.textContent = indexToLetter(i);
          if (String(i) === String(current)) {
            option.selected = true;
          }
          select.appendChild(option);
        }
      });
    };

    const syncSelectToHidden = (select) => {
      const field = select.dataset.field;
      const hidden = document.querySelector('.column-index-input[data-field="' + field + '"]');
      if (hidden) {
        hidden.value = select.value;
      }
    };

    document.querySelectorAll('.excel-column-select').forEach((select) => {
      select.addEventListener('change', function() {
        syncSelectToHidden(this);
        refreshPreviewMappingRow();
      });
    });

    document.querySelectorAll('.ais-required-toggle').forEach((toggle) => {
      toggle.addEventListener('change', function() {
        const wrap = this.closest('.ais-required-wrap');
        const label = wrap?.querySelector('.ais-required-label');
        if (this.checked) {
          wrap?.classList.add('is-yes');
          if (label) label.textContent = 'Yes';
        } else {
          wrap?.classList.remove('is-yes');
          if (label) label.textContent = 'No';
        }
      });
    });

    if (tbody && typeof Sortable !== 'undefined') {
      new Sortable(tbody, {
        handle: '.ais-drag-handle',
        animation: 150,
        ghostClass: 'ais-sortable-ghost'
      });
    }

    document.getElementById('reset-defaults-btn')?.addEventListener('click', function() {
      headerSkip.value = String(defaultHeaderRows);
      Object.keys(defaultMappings).forEach((field) => {
        const hidden = document.querySelector('.column-index-input[data-field="' + field + '"]');
        const select = document.querySelector('.excel-column-select[data-field="' + field + '"]');
        if (hidden) hidden.value = defaultMappings[field];
        if (select) select.value = String(defaultMappings[field]);
        const row = document.querySelector('.ais-map-row[data-field="' + field + '"]');
        if (row) row.classList.remove('d-none');

        const toggle = document.querySelector('.ais-required-toggle[data-field="' + field + '"]');
        if (toggle && !toggle.disabled) {
          const isRequired = !!staticRequiredFields[field];
          toggle.checked = isRequired;
          const wrap = toggle.closest('.ais-required-wrap');
          const label = wrap?.querySelector('.ais-required-label');
          if (isRequired) {
            wrap?.classList.add('is-yes');
            if (label) label.textContent = 'Yes';
          } else {
            wrap?.classList.remove('is-yes');
            if (label) label.textContent = 'No';
          }
        }
      });
      refreshPreviewMappingRow();
    });

    document.getElementById('ais-add-column-btn')?.addEventListener('click', function() {
      rebuildColumnSelects(currentMaxIndex() + 1);
    });

    document.getElementById('ais-add-row-btn')?.addEventListener('click', function() {
      const hiddenRows = Array.from(document.querySelectorAll('.ais-map-row.d-none'));
      if (!hiddenRows.length) {
        if (window.Swal) {
          Swal.fire({
            icon: 'info',
            title: 'All system fields are listed',
            text: 'Every import field is already in the table. Remove an optional field first if you want to add it again.'
          });
        }
        return;
      }
      hiddenRows[0].classList.remove('d-none');
      const restoredHidden = hiddenRows[0].querySelector('.column-index-input');
      if (restoredHidden) restoredHidden.disabled = false;
    });

    tbody?.addEventListener('click', function(event) {
      const deleteBtn = event.target.closest('.ais-delete-row');
      if (deleteBtn) {
        const row = deleteBtn.closest('.ais-map-row');
        if (!row || row.dataset.locked === '1') {
          return;
        }
        row.classList.add('d-none');
        const hidden = row.querySelector('.column-index-input');
        if (hidden) hidden.disabled = true;
        refreshPreviewMappingRow();
        return;
      }

      const editBtn = event.target.closest('.ais-edit-row');
      if (editBtn) {
        const row = editBtn.closest('.ais-map-row');
        if (!row) {
          return;
        }
        const field = row.dataset.field;
        const select = row.querySelector('.excel-column-select');
        document.getElementById('ais-edit-field').value = field;
        document.getElementById('ais-edit-label').value = row.dataset.label || fieldLabels[field] || field;
        const editSelect = document.getElementById('ais-edit-column');
        editSelect.innerHTML = select ? select.innerHTML : '';
        editSelect.value = select ? select.value : '0';
        editModal?.show();
      }
    });

    document.getElementById('ais-edit-save-btn')?.addEventListener('click', function() {
      const field = document.getElementById('ais-edit-field').value;
      const value = document.getElementById('ais-edit-column').value;
      const select = document.querySelector('.excel-column-select[data-field="' + field + '"]');
      if (select) {
        select.value = value;
        syncSelectToHidden(select);
      }
      editModal?.hide();
      refreshPreviewMappingRow();
    });

    const collectMappings = () => {
      const mappings = {};
      document.querySelectorAll('.ais-map-row:not(.d-none)').forEach((row) => {
        const field = row.dataset.field;
        const hidden = row.querySelector('.column-index-input');
        if (field && hidden) {
          mappings[field] = parseInt(hidden.value, 10);
        }
      });
      return mappings;
    };

    document.getElementById('ais-export-template-btn')?.addEventListener('click', function() {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = exportUrl;
      form.style.display = 'none';

      const csrf = document.createElement('input');
      csrf.type = 'hidden';
      csrf.name = '_token';
      csrf.value = csrfToken;
      form.appendChild(csrf);

      const addHidden = (name, value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
      };

      addHidden('customer_id', document.querySelector('#ais-settings-form [name="customer_id"]').value);
      addHidden('import_type', document.querySelector('#ais-settings-form [name="import_type"]').value);
      addHidden('header_rows_to_skip', headerSkip.value);

      const mappings = collectMappings();
      Object.keys(mappings).forEach((field) => {
        addHidden('column_mappings[' + field + ']', mappings[field]);
      });

      document.body.appendChild(form);
      form.submit();
      form.remove();
    });

    const openFilePicker = () => previewFileInput?.click();

    document.getElementById('ais-import-preview-btn')?.addEventListener('click', function() {
      document.getElementById('ais-file-preview-card')?.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
      openFilePicker();
    });
    document.getElementById('ais-choose-file-btn')?.addEventListener('click', openFilePicker);
    previewEmpty?.addEventListener('click', openFilePicker);

    document.getElementById('ais-clear-preview-btn')?.addEventListener('click', function() {
      previewFileInput.value = '';
      previewGrid.classList.add('d-none');
      previewGrid.innerHTML = '';
      previewEmpty.classList.remove('d-none');
      previewMeta.textContent = '';
      this.classList.add('d-none');
    });

    let lastPreviewRows = null;
    let lastPreviewCols = 0;

    const escapeHtml = (value) => String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');

    const renderPreviewGrid = (rows, columnCount) => {
      const mappings = collectMappings();
      const indexToField = {};
      Object.keys(mappings).forEach((field) => {
        indexToField[mappings[field]] = field;
      });

      const skip = parseInt(headerSkip.value, 10) || 0;
      const cols = Math.max(columnCount, 1);
      let html = '<div class="excel-preview-wrap"><table class="excel-preview-table"><thead><tr><th class="row-num"></th>';
      for (let c = 0; c < cols; c++) {
        html += '<th>' + escapeHtml(indexToLetter(c)) + '</th>';
      }
      html += '</tr><tr class="map-row"><th class="row-num">Map</th>';
      for (let c = 0; c < cols; c++) {
        const field = indexToField[c];
        html += '<th>' + (field ? escapeHtml(fieldLabels[field] || field) : '') + '</th>';
      }
      html += '</tr></thead><tbody>';

      (rows || []).forEach((row, rowIndex) => {
        const isSkipped = rowIndex < skip;
        html += '<tr class="' + (isSkipped ? 'skipped-row' : '') + '"><td class="row-num">' + (rowIndex + 1) + '</td>';
        for (let c = 0; c < cols; c++) {
          const mapped = !!indexToField[c];
          html += '<td class="' + (mapped && !isSkipped ? 'mapped-col' : '') + '">' + escapeHtml(row[c] ?? '') + '</td>';
        }
        html += '</tr>';
      });

      html += '</tbody></table></div>';
      previewGrid.innerHTML = html;
    };

    function refreshPreviewMappingRow() {
      if (lastPreviewRows) {
        renderPreviewGrid(lastPreviewRows, lastPreviewCols);
      }
    }

    headerSkip?.addEventListener('change', refreshPreviewMappingRow);

    previewFileInput?.addEventListener('change', function() {
      const file = this.files && this.files[0];
      if (!file) {
        return;
      }

      const formData = new FormData();
      formData.append('file', file);
      formData.append('header_rows_to_skip', headerSkip.value);

      previewEmpty.classList.remove('d-none');
      previewEmpty.textContent = 'Loading preview…';
      previewGrid.classList.add('d-none');

      fetch(previewUrl, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: formData
        })
        .then(async (response) => {
          const data = await response.json().catch(() => ({}));
          if (!response.ok) {
            throw new Error(data.message || (data.errors && data.errors.file && data.errors.file[0]) || 'Preview failed.');
          }
          return data;
        })
        .then((data) => {
          lastPreviewRows = data.rows || [];
          lastPreviewCols = data.column_count || (lastPreviewRows[0] ? lastPreviewRows[0].length : 0);
          previewEmpty.classList.add('d-none');
          previewEmpty.textContent = 'Select a File to Preview';
          previewGrid.classList.remove('d-none');
          previewMeta.textContent = (data.file_name || file.name) + (data.sheet_name ? ' · ' + data.sheet_name : '') + ' · showing ' + (data.preview_row_count || lastPreviewRows.length) + ' of ' + (data.row_count || lastPreviewRows.length) + ' rows';
          document.getElementById('ais-clear-preview-btn')?.classList.remove('d-none');
          renderPreviewGrid(lastPreviewRows, lastPreviewCols);
        })
        .catch((error) => {
          previewEmpty.textContent = 'Select a File to Preview';
          lastPreviewRows = null;
          if (window.Swal) {
            Swal.fire({
              icon: 'error',
              title: 'Preview failed',
              text: error.message
            });
          } else {
            alert(error.message);
          }
        });
    });

    if (window.bootstrap) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new bootstrap.Tooltip(el);
      });
    }
  });
</script>
@endpush