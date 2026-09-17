@php
  $companySlug = $companySlug ?? (request()->route('company_slug') ?? session('company_slug'));
  $moduleKey = $moduleKey ?? ($profile->key ?? 'sim_invoices');
  $embedded = ! empty($embedded);
  $storeUrl = route('settings-panel.excel-import-mappings.store', ['company_slug' => $companySlug, 'module' => $moduleKey]);
  if ($embedded) {
      $indexUrl = route('settings-panel.module-settings.index', [
          'company_slug' => $companySlug,
          'module' => $moduleKey,
          'tab' => 'import-mappings',
      ]);
  } else {
      $indexUrl = route('settings-panel.excel-import-mappings.index', [
          'company_slug' => $companySlug,
          'module' => $moduleKey,
      ]);
  }
  $scopeChangeBase = $indexUrl . (str_contains($indexUrl, '?') ? '&' : '?');
  $columnMappings = $resolved['column_mappings'] ?? [];
  $dynamicMappings = $resolved['dynamic_mappings'] ?? [];
  $formId = $formId ?? 'excelImportMappingForm';
  $fieldLabels = collect($profile->fields ?? [])->mapWithKeys(fn ($f) => [$f['key'] => $f['label']])->all();
  $headerRowsToSkip = (int) old('header_rows_to_skip', $resolved['header_rows_to_skip'] ?? $profile->defaultHeaderRowsToSkip);
  $helpModalId = $formId . '_helpModal';
  $previewCardId = $formId . '_previewCard';
@endphp

<style>
  .eim-ais .ais-subtitle { color: #6b7280; font-size: .875rem; margin: .2rem 0 0; }
  .eim-ais .ais-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
  }
  .eim-ais .ais-badge-success {
    background: #dcfce7; color: #166534; font-weight: 600;
    border-radius: 999px; padding: .35rem .75rem; font-size: .8rem; display: inline-block;
  }
  .eim-ais .ais-badge-warning {
    background: #fef3c7; color: #92400e; font-weight: 600;
    border-radius: 999px; padding: .35rem .75rem; font-size: .8rem; display: inline-block;
  }
  .eim-ais .ais-map-table { margin-bottom: 0; }
  .eim-ais .ais-map-table thead th {
    background: #1e293b !important;
    color: #fff !important;
    font-weight: 600; font-size: .8rem;
    letter-spacing: .02em; white-space: nowrap; border-color: #1e293b; padding: .7rem .75rem;
  }
  .eim-ais .ais-map-table tbody td {
    vertical-align: middle; padding: .65rem .75rem; border-color: #e5e7eb; background: #fff;
  }
  .eim-ais .ais-map-table tbody tr:hover td { background: #f8fafc; }
  .eim-ais .ais-info-banner {
    background: #eff6ff; color: #1e40af; border-radius: 8px; padding: .7rem .9rem; font-size: .85rem;
  }
  .eim-ais .ais-preview-card {
    border: 1px solid #e5e7eb; border-radius: 12px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .06); background: #fff; min-height: 280px;
  }
  .eim-ais .ais-preview-empty {
    min-height: 220px; display: flex; flex-direction: column; align-items: center;
    justify-content: center; cursor: pointer; color: #9ca3af; font-size: 1.05rem; font-weight: 500;
  }
  .eim-ais .ais-preview-empty:hover { background: #fafbfc; }
  .eim-ais .excel-preview-wrap {
    overflow: auto; max-height: 420px; border: 1px solid #e5e7eb; border-radius: 8px;
  }
  .eim-ais .excel-preview-table {
    border-collapse: collapse; font-size: 12px; min-width: 100%; margin: 0;
  }
  .eim-ais .excel-preview-table th,
  .eim-ais .excel-preview-table td {
    border: 1px solid #d1d5db; padding: 6px 10px; white-space: nowrap;
    max-width: 180px; overflow: hidden; text-overflow: ellipsis;
  }
  .eim-ais .excel-preview-table thead th {
    background: #1e293b !important;
    color: #fff !important;
    position: sticky; top: 0; z-index: 2; font-weight: 600;
  }
  .eim-ais .excel-preview-table .map-row th {
    background: #dbeafe; color: #1e40af; position: sticky; top: 31px; z-index: 1; font-weight: 600;
  }
  .eim-ais .excel-preview-table .skipped-row td { background: #f3f4f6; color: #9ca3af; }
  .eim-ais .excel-preview-table .mapped-col { background: #eff6ff; }
  .eim-ais .excel-preview-table .row-num {
    background: #f1f5f9; color: #94a3b8; font-weight: 600; text-align: center;
    min-width: 36px; pointer-events: none; cursor: default; user-select: none; opacity: .75;
  }
</style>

<div class="eim-ais">
  <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-3">
    <div>
      <p class="ais-subtitle mb-0">
        Configure Excel column mappings
        @if($profile->hasScope())
          per {{ strtolower($profile->scope['label'] ?? 'scope') }}.
        @else
          for this module.
        @endif
        Saved settings only prefill the import form; import still uses submitted form values.
      </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <button type="button" class="btn btn-outline-primary" id="{{ $formId }}_loadPreviewBtn">
        <i class="ti ti-eye me-1"></i> Import Preview
      </button>
      <button type="button" class="btn btn-outline-secondary d-none" id="{{ $formId }}_clearPreviewBtn">
        Clear Preview
      </button>
      <button type="button" class="btn btn-outline-secondary btn-icon" data-bs-toggle="modal" data-bs-target="#{{ $helpModalId }}" title="How mapping works">
        <i class="ti ti-help"></i>
      </button>
    </div>
  </div>

  {{-- Preview-only file picker: not inside the form and has no name, so it is never submitted. --}}
  <input type="file" id="{{ $formId }}_previewFile" class="d-none" accept=".xlsx,.xls,.csv" tabindex="-1" aria-hidden="true">

  <div class="card ais-card mb-4">
    <div class="card-body">
      <form method="POST" action="{{ $storeUrl }}" id="{{ $formId }}">
        @csrf
        <input type="hidden" name="return_to" value="{{ $embedded ? 'module_settings' : 'standalone' }}">

        @if($profile->hasScope())
          <div class="row g-3 align-items-end mb-4">
            <div class="col-md-5">
              <label class="form-label" for="{{ $formId }}_scope_id">{{ $profile->scope['label'] ?? 'Scope' }} <span class="text-danger">*</span></label>
              <select name="scope_id" id="{{ $formId }}_scope_id" class="form-select" required
                      onchange="window.location='{{ $scopeChangeBase }}scope_id='+encodeURIComponent(this.value)">
                @foreach($scopes as $scope)
                  <option value="{{ $scope->id }}" @selected((int)$selectedScopeId === (int)$scope->id)>
                    {{ $scope->name }}
                    @if(in_array((int)$scope->id, $configuredScopeIds ?? [], true)) (configured)@endif
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-7 d-flex align-items-end pb-1">
              @if(!empty($resolved['configured']))
                <span class="ais-badge-success"><i class="ti ti-check me-1"></i> Custom mappings configured</span>
              @else
                <span class="ais-badge-warning">Not configured — save mappings to prefill import</span>
              @endif
            </div>
          </div>
        @else
          <div class="mb-4">
            @if(!empty($resolved['configured']))
              <span class="ais-badge-success"><i class="ti ti-check me-1"></i> Custom mappings configured</span>
            @else
              <span class="ais-badge-warning">Not configured — save mappings to prefill import</span>
            @endif
          </div>
        @endif

        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label class="form-label" for="{{ $formId }}_header_rows_to_skip">Header rows to skip</label>
            <input type="number" min="0" max="20" class="form-control" id="{{ $formId }}_header_rows_to_skip"
                   name="header_rows_to_skip" value="{{ $headerRowsToSkip }}" required>
            <small class="text-muted">Top rows to ignore before data. Prefills the import form; form value still wins on submit.</small>
          </div>
          <div class="col-md-4 d-flex align-items-center pt-3">
            <div class="form-check">
              <input type="hidden" name="is_active" value="0">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" id="{{ $formId }}_is_active"
                     @checked(old('is_active', true))>
              <label class="form-check-label" for="{{ $formId }}_is_active">Import mappings active</label>
            </div>
          </div>
        </div>

        <div class="table-responsive mb-3">
          <table class="table table-bordered ais-map-table" id="{{ $formId }}_fieldsTable">
            <thead>
              <tr>
                <th style="width: 120px;">Required</th>
                <th>System Field</th>
                <th style="width: 220px;">Excel Column</th>
              </tr>
            </thead>
            <tbody>
              @foreach($profile->fields as $field)
                <tr class="eim-field-row" data-field="{{ $field['key'] }}">
                  <td>
                    <span class="fw-semibold text-primary">{{ !empty($field['required']) ? 'Yes' : 'No' }}</span>
                  </td>
                  <td>
                    <span class="fw-medium">{{ $field['label'] }}</span>
                    <i class="ti ti-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Maps this system field to an Excel column in the uploaded file."></i>
                  </td>
                  <td>
                    <select name="column_mappings[{{ $field['key'] }}]"
                            class="form-select form-select-sm eim-col-select"
                            data-field="{{ $field['key'] }}"
                            @if(!empty($field['required'])) required @endif>
                      <option value="">Select column…</option>
                      @foreach($excelColumnChoices as $col => $label)
                        <option value="{{ $col }}" @selected((int)($columnMappings[$field['key']] ?? 0) === (int)$col)>
                          {{ $label }}
                        </option>
                      @endforeach
                    </select>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        @if($profile->supportsDynamicMappings())
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-semibold">{{ $profile->dynamic['label'] ?? 'Charge Columns' }}</h6>
            <button type="button" class="btn btn-outline-success btn-sm" id="{{ $formId }}_addDynamicMapRow">
              <i class="ti ti-plus me-1"></i> Add Charge Column
            </button>
          </div>
          <div class="table-responsive mb-2">
            <table class="table table-bordered ais-map-table" id="{{ $formId }}_itemsTable">
              <thead>
                <tr>
                  <th>Item</th>
                  <th style="width: 180px;">Excel Column</th>
                  <th style="width: 140px;">Rate</th>
                  <th style="width: 90px;" class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody id="{{ $formId }}_dynamicMapRows"></tbody>
            </table>
          </div>
          <small class="text-muted d-block mb-3">Sheet cell = quantity. Rate is applied to every imported row for that item.</small>
        @endif

        <div class="ais-info-banner mb-4">
          <i class="ti ti-info-circle me-1"></i>
          Load an Excel file with <strong>Import Preview</strong> to see columns and map them visually. The preview file is view-only and is not saved with mappings.
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary" @disabled($profile->hasScope() && (int)($selectedScopeId ?? 0) < 1)>
            <i class="ti ti-device-floppy me-1"></i> Save Import Settings
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="card ais-preview-card mb-2" id="{{ $previewCardId }}">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <strong>File Preview</strong>
        <small class="text-muted" id="{{ $formId }}_previewMeta"></small>
      </div>
      <div id="{{ $formId }}_previewError" class="alert alert-danger py-2 mb-2" style="display:none;">
        <i class="ti ti-alert-triangle me-1"></i> Could not read the selected file.
      </div>
      <div id="{{ $formId }}_previewEmpty" class="ais-preview-empty">
        <i class="ti ti-file-spreadsheet mb-2" style="font-size: 2rem;"></i>
        Select a File to Preview
      </div>
      <div id="{{ $formId }}_previewGrid" class="d-none"></div>
    </div>
  </div>
</div>

<div class="modal fade" id="{{ $helpModalId }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">How does column mapping work?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Each <strong>system field</strong> is mapped to an Excel column letter (A, B, C, …).</p>
        <ul class="mb-2">
          <li><strong>Header rows to skip</strong> ignores title rows at the top of the file before data starts.</li>
          <li>Map <strong>SIM Number</strong>, then add item columns. Sheet cells for items are <strong>quantity</strong>.</li>
          <li>In the preview, mapped columns are highlighted and labeled on the Map row. Skipped header rows appear grayed out.</li>
          <li>Saved settings prefill the SIM invoice import form; import always uses the values submitted on that form.</li>
        </ul>
        <p class="mb-0 text-muted small">Column A = 1, B = 2, C = 3, and so on.</p>
      </div>
    </div>
  </div>
</div>

@push('page-scripts')
<script src="{{ asset('assets/vendor/libs/xlsx/xlsx.full.min.js') }}"></script>
<script>
(function () {
  var formId = @json($formId);
  var previewCardId = @json($previewCardId);
  var items = @json($dynamicItems ?? []);
  var columnChoices = @json($excelColumnChoices ?? []);
  var existing = @json($dynamicMappings ?? []);
  var fieldLabels = @json($fieldLabels);
  var supportsDynamic = @json($profile->supportsDynamicMappings());
  var PREVIEW_ROWS = 20;
  var MAX_COLS = Math.max.apply(null, Object.keys(columnChoices).map(function (k) {
    return parseInt(k, 10);
  }).concat([1]));

  function boot() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }
    var $ = window.jQuery;
    var $form = $('#' + formId);
    var $file = $('#' + formId + '_previewFile');
    var $loadBtn = $('#' + formId + '_loadPreviewBtn');
    var $clearBtn = $('#' + formId + '_clearPreviewBtn');
    var $empty = $('#' + formId + '_previewEmpty');
    var $grid = $('#' + formId + '_previewGrid');
    var $meta = $('#' + formId + '_previewMeta');
    var $error = $('#' + formId + '_previewError');
    var $headerSkip = $('#' + formId + '_header_rows_to_skip');
    var $rows = $('#' + formId + '_dynamicMapRows');
    var $addBtn = $('#' + formId + '_addDynamicMapRow');
    var $previewCard = $('#' + previewCardId);

    if (!$form.length || $loadBtn.data('eimPreviewBound')) {
      return;
    }
    $loadBtn.data('eimPreviewBound', 1);

    var previewActive = false;
    var headers = [];
    var colCount = 0;
    var lastRows = [];
    var rowIndex = 0;

    function escapeHtml(text) {
      return $('<div>').text(text == null ? '' : text).html();
    }

    function truncate(text, len) {
      text = String(text == null ? '' : text);
      return text.length > len ? text.slice(0, len) + '…' : text;
    }

    function colLetter(n) {
      var s = '';
      n = parseInt(n, 10) || 0;
      while (n > 0) {
        var m = (n - 1) % 26;
        s = String.fromCharCode(65 + m) + s;
        n = Math.floor((n - 1) / 26);
      }
      return s;
    }

    function defaultColLabel(col) {
      return columnChoices[col] || columnChoices[String(col)] || ('Column ' + colLetter(col));
    }

    function previewColLabel(col) {
      var base = colLetter(col);
      var h = String(headers[col - 1] || '').trim();
      if (previewActive && h) {
        return base + ' — ' + truncate(h, 28);
      }
      return defaultColLabel(col);
    }

    function colOptions(selected) {
      var html = '<option value="">Select column…</option>';
      Object.keys(columnChoices).forEach(function (col) {
        html += '<option value="' + col + '"' + (parseInt(selected, 10) === parseInt(col, 10) ? ' selected' : '') + '>'
          + escapeHtml(previewColLabel(col)) + '</option>';
      });
      return html;
    }

    function refreshAllColSelects() {
      $form.find('.eim-col-select, .eim-item-col-select').each(function () {
        var $sel = $(this);
        var current = $sel.val();
        $sel.html(colOptions(current));
      });
    }

    function collectMappedCols() {
      var map = {};
      $form.find('.eim-col-select').each(function () {
        var col = parseInt($(this).val(), 10);
        var field = $(this).data('field');
        if (!col || !field) return;
        if (!map[col]) map[col] = [];
        map[col].push(fieldLabels[field] || field);
      });
      $form.find('.eim-item-row').each(function () {
        var col = parseInt($(this).find('.eim-item-col-select').val(), 10);
        if (!col) return;
        var itemName = $(this).find('.eim-item-select option:selected').text() || 'Item';
        if (!$(this).find('.eim-item-select').val()) {
          itemName = 'Item';
        }
        if (!map[col]) map[col] = [];
        map[col].push(itemName);
      });
      return map;
    }

    function renderPreview(rows) {
      if (!previewActive || !colCount) return;
      var mapped = collectMappedCols();
      var skip = parseInt($headerSkip.val(), 10) || 0;
      var html = '<div class="excel-preview-wrap"><table class="excel-preview-table"><thead><tr><th class="row-num"></th>';
      for (var c = 1; c <= colCount; c++) {
        html += '<th>' + escapeHtml(colLetter(c)) + '</th>';
      }
      html += '</tr><tr class="map-row"><th class="row-num">Map</th>';
      for (var m = 1; m <= colCount; m++) {
        var labels = mapped[m] || [];
        html += '<th>' + escapeHtml(labels.length ? labels.map(function (l) { return truncate(l, 16); }).join(', ') : '') + '</th>';
      }
      html += '</tr></thead><tbody>';
      (rows || []).forEach(function (row, rowIndex) {
        var isSkipped = rowIndex < skip;
        html += '<tr class="' + (isSkipped ? 'skipped-row' : '') + '"><td class="row-num">' + (rowIndex + 1) + '</td>';
        for (var i = 0; i < colCount; i++) {
          var colNum = i + 1;
          var isMapped = !!(mapped[colNum] && mapped[colNum].length);
          html += '<td class="' + (isMapped && !isSkipped ? 'mapped-col' : '') + '">'
            + escapeHtml(row[i] != null ? row[i] : '') + '</td>';
        }
        html += '</tr>';
      });
      html += '</tbody></table></div>';
      $grid.html(html);
    }

    function showPreview(fileName, rows) {
      previewActive = true;
      lastRows = rows;
      $error.hide();
      $meta.text(fileName + ' · showing ' + rows.length + ' row(s) · view only');
      $empty.addClass('d-none');
      $grid.removeClass('d-none');
      $clearBtn.removeClass('d-none');
      refreshAllColSelects();
      renderPreview(rows);
    }

    function clearPreview(showErr) {
      previewActive = false;
      headers = [];
      colCount = 0;
      lastRows = [];
      $file.val('');
      $meta.text('');
      $grid.addClass('d-none').empty();
      $empty.removeClass('d-none');
      $clearBtn.addClass('d-none');
      $error.toggle(!!showErr);
      refreshAllColSelects();
    }

    function openPicker() {
      $file.trigger('click');
    }

    $loadBtn.on('click', function (e) {
      e.preventDefault();
      if ($previewCard.length) {
        $previewCard[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      openPicker();
    });
    $empty.on('click', openPicker);
    $clearBtn.on('click', function (e) {
      e.preventDefault();
      clearPreview(false);
    });

    $file.on('change', function () {
      var file = this.files && this.files[0];
      if (!file) {
        clearPreview(false);
        return;
      }
      if (typeof XLSX === 'undefined') {
        clearPreview(true);
        return;
      }
      var reader = new FileReader();
      reader.onload = function (e) {
        try {
          var workbook = XLSX.read(new Uint8Array(e.target.result), { type: 'array', sheetRows: PREVIEW_ROWS + 1 });
          var sheet = workbook.Sheets[workbook.SheetNames[0]];
          var rows = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });
          colCount = 0;
          rows.forEach(function (row) { colCount = Math.max(colCount, row.length); });
          colCount = Math.min(colCount, MAX_COLS);
          if (!rows.length || !colCount) {
            throw new Error('Empty sheet');
          }
          headers = (rows[0] || []).slice(0, colCount);
          showPreview(file.name, rows.slice(0, PREVIEW_ROWS));
        } catch (err) {
          clearPreview(true);
        }
      };
      reader.onerror = function () { clearPreview(true); };
      reader.readAsArrayBuffer(file);
    });

    $form.on('change', '.eim-col-select, .eim-item-col-select, .eim-item-select', function () {
      if (previewActive) {
        renderPreview(lastRows);
      }
    });
    $headerSkip.on('change', function () {
      if (previewActive) {
        renderPreview(lastRows);
      }
    });

    if (supportsDynamic && $rows.length && $addBtn.length && !$addBtn.data('eimBound')) {
      $addBtn.data('eimBound', 1);

      function itemOptions(selected) {
        var html = '<option value="">Select item…</option>';
        items.forEach(function (item) {
          html += '<option value="' + item.id + '" data-price="' + item.price + '"'
            + (parseInt(selected, 10) === parseInt(item.id, 10) ? ' selected' : '')
            + '>' + escapeHtml(item.name) + '</option>';
        });
        return html;
      }

      function addRow(opts) {
        opts = opts || {};
        var idx = rowIndex++;
        var rate = opts.rate != null && opts.rate !== '' ? opts.rate : 1;
        var html = ''
          + '<tr class="eim-item-row" data-index="' + idx + '">'
          + '  <td>'
          + '    <select name="dynamic_mappings[' + idx + '][item_id]" class="form-select form-select-sm eim-item-select">' + itemOptions(opts.item_id) + '</select>'
          + '  </td>'
          + '  <td>'
          + '    <select name="dynamic_mappings[' + idx + '][col]" class="form-select form-select-sm eim-item-col-select">' + colOptions(opts.col) + '</select>'
          + '  </td>'
          + '  <td>'
          + '    <input type="number" step="any" name="dynamic_mappings[' + idx + '][rate]" class="form-control form-control-sm eim-item-rate" value="' + rate + '">'
          + '  </td>'
          + '  <td class="text-center">'
          + '    <button type="button" class="btn btn-sm btn-icon text-danger eim-remove-row" title="Remove"><i class="ti ti-trash"></i></button>'
          + '  </td>'
          + '</tr>';
        $rows.append(html);
        if (previewActive) {
          renderPreview(lastRows);
        }
      }

      $addBtn.on('click', function (e) {
        e.preventDefault();
        addRow();
      });
      $rows.on('click', '.eim-remove-row', function () {
        $(this).closest('.eim-item-row').remove();
        if (previewActive) {
          renderPreview(lastRows);
        }
      });
      $rows.on('change', '.eim-item-select', function () {
        var price = $(this).find('option:selected').data('price');
        var $rate = $(this).closest('.eim-item-row').find('.eim-item-rate');
        if (price != null && price !== '') {
          $rate.val(price);
        } else if ($rate.val() === '' || $rate.val() == null) {
          $rate.val(1);
        }
      });

      if (existing.length) {
        existing.forEach(function (row) { addRow(row); });
      } else {
        addRow();
      }
    }

    if (window.bootstrap) {
      document.querySelectorAll('.eim-ais [data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
</script>
@endpush
