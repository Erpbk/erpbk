@php
$previewPrefix = $previewPrefix ?? 'ais-preview';
$previewUrl = $previewUrl ?? route('rider.activities_import_preview');
$fieldLabels = $fieldLabels ?? \App\Services\RiderActivities\RiderActivityImportMappingService::fieldLabels();
$previewConfigs = $previewConfigs ?? [];
$fileInputName = $fileInputName ?? 'file';
$fileInputRequired = $fileInputRequired ?? true;
@endphp

<style>
  .ais-file-preview-help {
    color: #2563eb;
    font-size: .9rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    margin-bottom: .75rem;
  }
  .ais-file-preview-help:hover {
    color: #1d4ed8;
  }
  .ais-file-preview-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
    background: #fff;
    min-height: 260px;
  }
  .ais-file-preview-card .card-body {
    padding: 1.1rem 1.25rem 1.25rem;
  }
  .ais-file-preview-empty {
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #9ca3af;
    font-size: 1.05rem;
    font-weight: 500;
    border-radius: 8px;
  }
  .ais-file-preview-empty:hover {
    background: #fafbfc;
  }
  .ais-file-preview-wrap {
    overflow: auto;
    max-height: 360px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
  }
  .ais-file-preview-table {
    border-collapse: collapse;
    font-size: 12px;
    min-width: 100%;
    margin: 0;
  }
  .ais-file-preview-table th,
  .ais-file-preview-table td {
    border: 1px solid #d1d5db;
    padding: 6px 10px;
    white-space: nowrap;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .ais-file-preview-table thead th {
    background: #1e293b;
    color: #fff;
    position: sticky;
    top: 0;
    z-index: 2;
    font-weight: 600;
  }
  .ais-file-preview-table .map-row th {
    background: #dbeafe;
    color: #1e40af;
    position: sticky;
    top: 31px;
    z-index: 1;
    font-weight: 600;
  }
  .ais-file-preview-table .skipped-row td {
    background: #f3f4f6;
    color: #9ca3af;
  }
  .ais-file-preview-table .mapped-col {
    background: #eff6ff;
  }
  .ais-file-preview-table .row-num {
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
</style>

<a href="javascript:void(0);" class="ais-file-preview-help" id="{{ $previewPrefix }}-help">
  <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white" style="width: 20px; height: 20px; font-size: 12px;">?</span>
  How does column mapping work?
</a>

<div class="ais-file-preview-card card mb-3" id="{{ $previewPrefix }}-card">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h5 class="mb-0 fw-semibold" style="color: #1a2b48;">File Preview</h5>
      <button type="button" class="btn btn-sm btn-outline-primary" id="{{ $previewPrefix }}-choose-btn">
        <i class="ti ti-upload me-1"></i> Select File
      </button>
    </div>
    <input
      type="file"
      name="{{ $fileInputName }}"
      id="{{ $previewPrefix }}-file"
      class="d-none"
      accept=".csv,.xlsx,.xls"
      @if($fileInputRequired) required @endif>
    <div id="{{ $previewPrefix }}-empty" class="ais-file-preview-empty">
      Select a File to Preview
    </div>
    <div id="{{ $previewPrefix }}-grid" class="d-none"></div>
  </div>
</div>

<script>
(function() {
  const prefix = @json($previewPrefix);
  const previewUrl = @json($previewUrl);
  const fieldLabels = @json($fieldLabels);
  const previewConfigs = @json($previewConfigs);
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const fileInput = document.getElementById(prefix + '-file');
  const emptyEl = document.getElementById(prefix + '-empty');
  const gridEl = document.getElementById(prefix + '-grid');
  const chooseBtn = document.getElementById(prefix + '-choose-btn');
  const helpBtn = document.getElementById(prefix + '-help');
  if (!fileInput || !emptyEl || !gridEl) {
    return;
  }

  const form = fileInput.closest('form');
  let lastRows = null;
  let lastCols = 0;

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

  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

  const currentConfig = () => {
    const customerId = form?.querySelector('[name="customer_id"]')?.value;
    return previewConfigs[customerId] || previewConfigs[String(customerId)] || {
      header_rows_to_skip: 2,
      column_mappings: {}
    };
  };

  const renderGrid = (rows, columnCount) => {
    const config = currentConfig();
    const mappings = config.column_mappings || {};
    const skip = parseInt(config.header_rows_to_skip, 10) || 0;
    const indexToField = {};
    Object.keys(mappings).forEach((field) => {
      indexToField[parseInt(mappings[field], 10)] = field;
    });
    const cols = Math.max(columnCount, 1);
    let html = '<div class="ais-file-preview-wrap"><table class="ais-file-preview-table"><thead><tr><th class="row-num"></th>';
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
    gridEl.innerHTML = html;
  };

  const openPicker = () => fileInput.click();
  chooseBtn?.addEventListener('click', openPicker);
  emptyEl.addEventListener('click', openPicker);

  form?.querySelector('[name="customer_id"]')?.addEventListener('change', function() {
    if (lastRows) {
      renderGrid(lastRows, lastCols);
    }
  });

  helpBtn?.addEventListener('click', function(e) {
    e.preventDefault();
    const text = 'Each system field is mapped to an Excel column (A, B, C, …) in Activity Import Settings. Header rows to skip ignores title rows at the top of the file. In this preview, mapped columns are labeled and highlighted.';
    if (window.Swal) {
      Swal.fire({
        icon: 'info',
        title: 'How does column mapping work?',
        text: text,
        confirmButtonText: 'OK'
      });
    } else {
      alert(text);
    }
  });

  fileInput.addEventListener('change', function() {
    const file = this.files && this.files[0];
    if (!file) {
      return;
    }

    const formData = new FormData();
    formData.append('file', file);

    emptyEl.classList.remove('d-none');
    emptyEl.textContent = 'Loading preview…';
    gridEl.classList.add('d-none');

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
        lastRows = data.rows || [];
        lastCols = data.column_count || (lastRows[0] ? lastRows[0].length : 0);
        emptyEl.classList.add('d-none');
        emptyEl.textContent = 'Select a File to Preview';
        gridEl.classList.remove('d-none');
        renderGrid(lastRows, lastCols);
      })
      .catch((error) => {
        emptyEl.textContent = 'Select a File to Preview';
        lastRows = null;
        fileInput.value = '';
        if (window.Swal) {
          Swal.fire({ icon: 'error', title: 'Preview failed', text: error.message });
        } else {
          alert(error.message);
        }
      });
  });
})();
</script>
