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
@endphp

<style>
  .eim-badge { border-radius: 999px; padding: .35rem .75rem; font-size: .8rem; font-weight: 600; display: inline-block; }
  .eim-badge-ok { background: #dcfce7; color: #166534; }
  .eim-badge-warn { background: #fef3c7; color: #92400e; }
  .eim-item-row { border: 1px solid #e9ecef; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; background: #fafbfc; }
  .eim-subtitle { color: #6b7280; font-size: .875rem; }
  #{{ $formId }}_previewTable td,
  #{{ $formId }}_previewTable th { white-space: nowrap; font-size: 12px; padding: 4px 8px; vertical-align: middle; }
  #{{ $formId }}_previewTable thead th { background-color: #f8f9fa; position: sticky; top: 0; z-index: 5; }
  #{{ $formId }}_previewTable .col-head.mapped { background-color: #eaf3ff; }
  #{{ $formId }}_previewTable .map-badges { min-height: 18px; margin-top: 2px; }
  #{{ $formId }}_previewTable .map-badges .badge { font-size: 10px; font-weight: 500; margin: 0 2px 2px 0; display: inline-block; }
  #{{ $formId }}_previewEmpty { min-height: 280px; display: flex; align-items: center; justify-content: center; color: #6c757d; font-size: 1.05rem; font-weight: 500; cursor: pointer; }
</style>

<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2">
  <div>
    <p class="eim-subtitle mb-0">
      Save Excel column mappings
      @if($profile->hasScope())
        per {{ strtolower($profile->scope['label'] ?? 'scope') }}.
      @else
        for this module.
      @endif
      Import forms still submit their own mapping fields; saved settings only prefill them.
    </p>
  </div>
  <div class="d-flex align-items-center gap-2 flex-wrap">
    @if(!empty($resolved['configured']))
      <span class="eim-badge eim-badge-ok">Configured</span>
    @else
      <span class="eim-badge eim-badge-warn">Not configured</span>
    @endif
    <button type="button" class="btn btn-outline-primary btn-sm" id="{{ $formId }}_loadPreviewBtn">
      <i class="fas fa-file-excel"></i> Load Excel File
    </button>
    <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="{{ $formId }}_clearPreviewBtn">
      Clear Preview
    </button>
  </div>
</div>

{{-- Preview-only file picker: not inside the form and has no name, so it is never submitted. --}}
<input type="file" id="{{ $formId }}_previewFile" class="d-none" accept=".xlsx,.xls,.csv" tabindex="-1" aria-hidden="true">

<form method="POST" action="{{ $storeUrl }}" id="{{ $formId }}">
  @csrf
  <input type="hidden" name="return_to" value="{{ $embedded ? 'module_settings' : 'standalone' }}">

  @if($profile->hasScope())
    <div class="row mb-4">
      <div class="col-md-5 form-group">
        <label class="fw-semibold">{{ $profile->scope['label'] ?? 'Scope' }} <span class="text-danger">*</span></label>
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
      <div class="col-md-3 form-group">
        <label class="fw-semibold">Header rows to skip</label>
        <input type="number" min="0" max="20" name="header_rows_to_skip" class="form-control"
               value="{{ old('header_rows_to_skip', $resolved['header_rows_to_skip'] ?? $profile->defaultHeaderRowsToSkip) }}">
        <small class="text-muted">Used when this company is selected on the SIM invoice import form (form value still wins on submit).</small>
      </div>
      <div class="col-md-3 form-group d-flex align-items-end">
        <div class="form-check mb-2">
          <input type="hidden" name="is_active" value="0">
          <input class="form-check-input" type="checkbox" name="is_active" value="1" id="{{ $formId }}_is_active"
                 @checked(old('is_active', true))>
          <label class="form-check-label" for="{{ $formId }}_is_active">Active</label>
        </div>
      </div>
    </div>
  @endif

  <div class="row">
    <div class="col-lg-7 mb-3">
      <div class="card border mb-0">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <strong>File Preview <small class="text-muted" id="{{ $formId }}_previewFileName"></small></strong>
          <span class="small text-muted">View only — not saved with mappings</span>
        </div>
        <div class="card-body p-2">
          <div id="{{ $formId }}_previewError" class="alert alert-danger py-2 mb-2" style="display:none;">
            <i class="fas fa-exclamation-triangle"></i> Could not read the selected file.
          </div>
          <div id="{{ $formId }}_previewEmpty">Click “Load Excel File” to preview columns</div>
          <div id="{{ $formId }}_previewTableWrap" class="table-responsive" style="max-height: 480px; display:none;">
            <table class="table table-sm table-bordered mb-0" id="{{ $formId }}_previewTable"></table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5 mb-3">
      <h5 class="mb-3">Required Columns</h5>
      @foreach($profile->fields as $field)
        <div class="form-group mb-3">
          <label>
            {{ $field['label'] }}
            @if(!empty($field['required']))<span class="text-danger">*</span>@endif
          </label>
          <select name="column_mappings[{{ $field['key'] }}]" class="form-select eim-col-select" data-field="{{ $field['key'] }}"
                  @if(!empty($field['required'])) required @endif>
            <option value="">Select column…</option>
            @foreach($excelColumnChoices as $col => $label)
              <option value="{{ $col }}" @selected((int)($columnMappings[$field['key']] ?? 0) === (int)$col)>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>
      @endforeach

      @if($profile->supportsDynamicMappings())
        <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
          <h5 class="mb-0">{{ $profile->dynamic['label'] ?? 'Dynamic Columns' }}</h5>
          <button type="button" class="btn btn-sm btn-success" id="{{ $formId }}_addDynamicMapRow">
            <i class="fas fa-plus"></i> Add Item Column
          </button>
        </div>
        <div id="{{ $formId }}_dynamicMapRows"></div>
        <small class="text-muted d-block mb-3">Sheet cell = quantity. Rate is applied to every imported row for that item.</small>
      @endif
    </div>
  </div>

  <div class="text-end mt-3">
    <button type="submit" class="btn btn-primary" @disabled($profile->hasScope() && (int)($selectedScopeId ?? 0) < 1)>
      <i class="fas fa-save"></i> Save Mappings
    </button>
  </div>
</form>

@push('page-scripts')
<script src="{{ asset('assets/vendor/libs/xlsx/xlsx.full.min.js') }}"></script>
<script>
(function () {
  var formId = @json($formId);
  var items = @json($dynamicItems ?? []);
  var columnChoices = @json($excelColumnChoices ?? []);
  var existing = @json($dynamicMappings ?? []);
  var fieldLabels = @json($fieldLabels);
  var supportsDynamic = @json($profile->supportsDynamicMappings());
  var PREVIEW_ROWS = 20;
  var MAX_COLS = Math.max.apply(null, Object.keys(columnChoices).map(function (k) { return parseInt(k, 10); }).concat([1]));

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
    var $wrap = $('#' + formId + '_previewTableWrap');
    var $table = $('#' + formId + '_previewTable');
    var $fileName = $('#' + formId + '_previewFileName');
    var $error = $('#' + formId + '_previewError');
    var $rows = $('#' + formId + '_dynamicMapRows');
    var $addBtn = $('#' + formId + '_addDynamicMapRow');

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
        if (!map[col]) map[col] = [];
        map[col].push(itemName);
      });
      return map;
    }

    function renderPreview(rows) {
      if (!previewActive || !colCount) return;
      var mapped = collectMappedCols();
      var html = '<thead><tr>';
      for (var c = 1; c <= colCount; c++) {
        var labels = mapped[c] || [];
        html += '<th class="col-head' + (labels.length ? ' mapped' : '') + '" data-col="' + c + '">'
          + '<div>' + escapeHtml(colLetter(c)) + '</div>'
          + '<div class="map-badges">';
        labels.forEach(function (label) {
          html += '<span class="badge bg-primary">' + escapeHtml(truncate(label, 16)) + '</span>';
        });
        html += '</div></th>';
      }
      html += '</tr></thead><tbody>';
      rows.forEach(function (row) {
        html += '<tr>';
        for (var i = 0; i < colCount; i++) {
          html += '<td>' + escapeHtml(row[i] != null ? row[i] : '') + '</td>';
        }
        html += '</tr>';
      });
      html += '</tbody>';
      $table.html(html);
    }

    function showPreview(fileName, rows) {
      previewActive = true;
      lastRows = rows;
      $error.hide();
      $fileName.text('— ' + fileName);
      $empty.hide();
      $wrap.show();
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
      $fileName.text('');
      $table.empty();
      $wrap.hide();
      $empty.css('display', 'flex');
      $clearBtn.addClass('d-none');
      $error.toggle(!!showErr);
      refreshAllColSelects();
    }

    function openPicker() {
      $file.trigger('click');
    }

    $loadBtn.on('click', function (e) {
      e.preventDefault();
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
        var rate = opts.rate != null ? opts.rate : '';
        var html = ''
          + '<div class="eim-item-row" data-index="' + idx + '">'
          + '  <div class="row g-2 align-items-end">'
          + '    <div class="col-5 form-group mb-0">'
          + '      <label class="small mb-1">Item</label>'
          + '      <select name="dynamic_mappings[' + idx + '][item_id]" class="form-select form-select-sm eim-item-select">' + itemOptions(opts.item_id) + '</select>'
          + '    </div>'
          + '    <div class="col-3 form-group mb-0">'
          + '      <label class="small mb-1">Qty Col</label>'
          + '      <select name="dynamic_mappings[' + idx + '][col]" class="form-select form-select-sm eim-item-col-select">' + colOptions(opts.col) + '</select>'
          + '    </div>'
          + '    <div class="col-3 form-group mb-0">'
          + '      <label class="small mb-1">Rate</label>'
          + '      <input type="number" step="any" name="dynamic_mappings[' + idx + '][rate]" class="form-control form-control-sm eim-item-rate" value="' + (rate === '' ? '' : rate) + '">'
          + '    </div>'
          + '    <div class="col-1 text-end pb-1">'
          + '      <button type="button" class="btn btn-sm btn-link text-danger eim-remove-row p-0" title="Remove"><i class="fas fa-trash"></i></button>'
          + '    </div>'
          + '  </div>'
          + '</div>';
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
        if (price != null && price !== '') {
          $(this).closest('.eim-item-row').find('.eim-item-rate').val(price);
        }
      });

      if (existing.length) {
        existing.forEach(function (row) { addRow(row); });
      } else {
        addRow();
      }
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
