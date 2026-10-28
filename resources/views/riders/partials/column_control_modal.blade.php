{{-- Demo-styled Column Control modal (same persistence/export as column-control-panel) --}}
@php
$tableId = $tableId ?? 'dataTableBuilder';
$tableColumns = $tableColumns ?? [];
$exportRoute = $exportRoute ?? null;
$tableIdentifier = $tableIdentifier ?? 'riders_table';
$fixedColumnsCount = (int) ($fixedColumnsCount ?? 1);
$explicitModuleKey = $moduleKey ?? null;
$moduleSettingsMap = config('module_settings_sync.table_identifier_to_module', []);
$resolvedModuleKey = $explicitModuleKey ?: ($moduleSettingsMap[$tableIdentifier] ?? null);

if ($resolvedModuleKey && class_exists(\App\Models\ModuleFieldCategoryAssignment::class)) {
    $assignmentRows = \App\Models\ModuleFieldCategoryAssignment::query()
        ->where('module_key', $resolvedModuleKey)
        ->get()
        ->keyBy('field_key');

    if ($assignmentRows->isNotEmpty()) {
        $tableColumns = array_values(array_filter(array_map(function ($column) use ($assignmentRows, $resolvedModuleKey) {
            $columnKey = $column['data'] ?? $column['key'] ?? null;
            if (!$columnKey || in_array($columnKey, ['search', 'control', 'action'], true)) {
                return $column;
            }

            $assignment = $assignmentRows->get($columnKey);
            if (!$assignment) {
                return $column;
            }

            if (!(bool) $assignment->is_visible) {
                if ($resolvedModuleKey === 'riders_list' && in_array($columnKey, ['status', 'customer_id', 'company_contact', 'designation'], true)) {
                    $effectiveLabel = trim((string) ($assignment->display_label ?: $assignment->field_label ?: ''));
                    if ($effectiveLabel !== '') {
                        $column['title'] = $columnKey === 'customer_id' && strcasecmp($effectiveLabel, 'Customer') === 0
                            ? 'Project'
                            : $effectiveLabel;
                    } elseif ($columnKey === 'customer_id') {
                        $column['title'] = 'Project';
                    } elseif ($columnKey === 'company_contact') {
                        $column['title'] = 'Company Contact';
                    } elseif ($columnKey === 'designation') {
                        $column['title'] = 'Designation';
                    }
                    return $column;
                }
                return null;
            }

            $effectiveLabel = trim((string) ($assignment->display_label ?: $assignment->field_label ?: ''));
            if ($effectiveLabel !== '') {
                $column['title'] = ($resolvedModuleKey === 'riders_list' && $columnKey === 'customer_id' && strcasecmp($effectiveLabel, 'Customer') === 0)
                    ? 'Project'
                    : $effectiveLabel;
            }

            return $column;
        }, $tableColumns)));
    }
}

$controllableColumns = [];
foreach ($tableColumns as $index => $column) {
    $columnKey = $column['data'] ?? $column['key'] ?? $index;
    if (in_array($columnKey, ['search', 'control', 'action'], true)
        || str_contains((string) $columnKey, 'search')
        || str_contains((string) $columnKey, 'control')) {
        continue;
    }
    $controllableColumns[] = [
        'index' => $index,
        'key' => $columnKey,
        'title' => $column['title'] ?? $column['name'] ?? ('Column ' . ($index + 1)),
    ];
}
@endphp

<div class="rl-ov" id="columnControlOverlay" hidden>
  <div class="rl-modal sm" id="columnControlSidebar" role="dialog" aria-labelledby="colT" onclick="event.stopPropagation()">
    <div class="mh">
      <h3 id="colT">Column Control</h3>
      <button type="button" class="x" id="closeColumnControlSidebar" aria-label="Close">×</button>
    </div>
    <div class="mb">
      <div>
        <div class="sect">Column visibility</div>
        <div class="rl-note">Tick to show, untick to hide. Drag rows to change order.</div>
      </div>

      <div class="cols" id="columnList">
        @foreach($controllableColumns as $col)
        <label class="colrow column-item" draggable="false" data-column-index="{{ $col['index'] }}" data-column-key="{{ $col['key'] }}">
          <span class="grip drag-handle" title="Drag to reorder">⋮⋮</span>
          <input class="column-visibility-checkbox" type="checkbox" checked
                 data-column-index="{{ $col['index'] }}" id="cc_{{ $col['key'] }}">
          <span class="col-label">{{ $col['title'] }}</span>
          <code>{{ $col['key'] }}</code>
        </label>
        @endforeach
      </div>

      <div class="sect">Quick actions</div>
      <div class="row3">
        <button type="button" class="rl-ghost" id="showAllColumns">Show All</button>
        <button type="button" class="rl-ghost" id="hideAllColumns">Hide All</button>
        <button type="button" class="rl-ghost" id="resetColumns">Reset to Default</button>
      </div>

      @if($exportRoute)
      <div class="sect">Export options</div>
      <div class="row3">
        <button type="button" class="exp e-x export-btn" data-format="excel">Export to Excel</button>
        <button type="button" class="exp e-c export-btn" data-format="csv">Export to CSV</button>
        <button type="button" class="exp e-p export-btn" data-format="pdf">Export to PDF</button>
      </div>
      <div class="rl-note">Only ticked columns are exported, in the current order, for all riders. Status exports as Status + Status Days; Name includes a profile link.</div>
      @endif

      <span id="visibleCount" hidden>{{ count($controllableColumns) }}</span>
      <span id="totalCount" hidden>{{ count($controllableColumns) }}</span>
    </div>
  </div>
</div>

<style>
  .rl-ov .cols {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: min(320px, 40vh);
    overflow-y: auto;
    padding-right: 2px;
  }
  .rl-ov .colrow {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border: 1px solid #e0ecff;
    border-radius: 9px;
    background: #f8fbff;
    cursor: default;
    margin: 0;
    font-size: 13px;
    color: #1e293b;
  }
  .rl-ov .colrow.sortable-ghost,
  .rl-ov .colrow.drag {
    opacity: 0.4;
  }
  .rl-ov .colrow.sortable-chosen {
    border-color: #3b3fd8;
  }
  .rl-ov .colrow .grip {
    color: #64748b;
    letter-spacing: -2px;
    cursor: grab;
    user-select: none;
    flex: none;
  }
  .rl-ov .colrow .grip:active {
    cursor: grabbing;
  }
  .rl-ov .colrow .col-label {
    flex: 1;
    min-width: 0;
  }
  .rl-ov .colrow code {
    margin-left: auto;
    font-size: 11px;
    color: #64748b;
    flex: none;
  }
  .rl-ov .colrow input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #3b3fd8;
    flex: none;
    margin: 0;
    cursor: pointer;
  }
  .rl-ov .row3 .rl-ghost {
    min-height: 34px;
    height: auto;
    padding: 8px 10px !important;
    white-space: nowrap;
  }
  .table th.column-hidden,
  .table td.column-hidden {
    display: none !important;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const ColumnController = {
    tableId: @json($tableId),
    tableIdentifier: @json($tableIdentifier),
    exportRoute: @json($exportRoute),
    fixedColumnsCount: {{ $fixedColumnsCount }},
    columns: @json($tableColumns),
    isInitialLoad: true,
    isDemoModal: true,

    init() {
      this.setupEventListeners();
      this.initializeSortable();
      setTimeout(() => {
        this.loadUserSettings();
        this.initializeDropdowns();
      }, 100);
    },

    setupEventListeners() {
      document.addEventListener('click', (e) => {
        if (e.target.closest('.openColumnControlSidebar')) {
          e.preventDefault();
          this.openSidebar();
        }
      });

      document.getElementById('closeColumnControlSidebar')?.addEventListener('click', () => this.closeSidebar());
      document.getElementById('columnControlOverlay')?.addEventListener('click', (e) => {
        if (e.target.id === 'columnControlOverlay') this.closeSidebar();
      });

      document.querySelectorAll('#columnList .column-visibility-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
          this.isInitialLoad = false;
          this.toggleColumnVisibility(e.target.dataset.columnIndex, e.target.checked);
        });
      });

      document.getElementById('showAllColumns')?.addEventListener('click', () => this.showAllColumns());
      document.getElementById('hideAllColumns')?.addEventListener('click', () => this.hideAllColumns());
      document.getElementById('resetColumns')?.addEventListener('click', () => this.resetColumns());

      document.querySelectorAll('#columnControlSidebar .export-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const format = e.currentTarget.dataset.format;
          this.exportTable(format);
        });
      });
    },

    initializeSortable() {
      if (typeof Sortable === 'undefined') return;
      const columnList = document.getElementById('columnList');
      if (!columnList) return;
      new Sortable(columnList, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: () => {
          this.isInitialLoad = false;
          this.reorderColumns();
        }
      });
    },

    openSidebar() {
      const ov = document.getElementById('columnControlOverlay');
      if (ov) ov.hidden = false;
    },

    closeSidebar() {
      const ov = document.getElementById('columnControlOverlay');
      if (ov) ov.hidden = true;
    },

    toggleColumnVisibility(columnIndex, isVisible, persist = true) {
      const table = document.getElementById(this.tableId);
      if (!table) return;
      const headerCells = Array.from(table.querySelectorAll('thead th'));
      const fixed = this.fixedColumnsCount || 0;
      const totalDataColumns = Math.max(0, headerCells.length - fixed);
      const item = document.querySelector(`#columnList .column-item[data-column-index="${columnIndex}"]`);
      const key = item?.dataset.columnKey;
      let targetIndex = headerCells.findIndex(th => th.getAttribute('data-k') === key);
      if (targetIndex < 0) targetIndex = parseInt(columnIndex, 10);

      if (targetIndex >= 0 && targetIndex < totalDataColumns && headerCells[targetIndex]) {
        headerCells[targetIndex].classList.toggle('column-hidden', !isVisible);
      }
      table.querySelectorAll('tbody tr').forEach(row => {
        if (targetIndex >= 0 && targetIndex < totalDataColumns && row.children[targetIndex]) {
          row.children[targetIndex].classList.toggle('column-hidden', !isVisible);
        }
      });

      this.updateColumnStats();
      if (persist && !this.isInitialLoad) this.saveSettings();
    },

    reorderColumns() {
      this.applyColumnOrder();
      if (!this.isInitialLoad) this.saveSettings();
    },

    applyColumnOrder() {
      const table = document.getElementById(this.tableId);
      if (!table) return;
      const keys = Array.from(document.querySelectorAll('#columnList .column-item')).map(item => item.dataset.columnKey);
      const headerRow = table.querySelector('thead tr');
      if (!headerRow) return;
      const originalHeaders = Array.from(headerRow.children);
      const fixed = this.fixedColumnsCount || 0;
      const dataHeaders = fixed > 0 ? originalHeaders.slice(0, -fixed) : originalHeaders.slice();
      const actionHeaders = fixed > 0 ? originalHeaders.slice(-fixed) : [];

      const reordered = [];
      keys.forEach(k => {
        const th = dataHeaders.find(h => h.getAttribute('data-k') === k);
        if (th && !reordered.includes(th)) reordered.push(th);
      });
      dataHeaders.forEach(h => {
        if (!reordered.includes(h)) reordered.push(h);
      });

      const oldIndexByHeader = new Map(dataHeaders.map((h, i) => [h, i]));
      const permutation = reordered.map(h => oldIndexByHeader.get(h)).filter(i => i !== undefined);

      headerRow.innerHTML = '';
      reordered.forEach(cell => headerRow.appendChild(cell));
      actionHeaders.forEach(cell => headerRow.appendChild(cell));

      table.querySelectorAll('tbody tr').forEach(row => {
        const cells = Array.from(row.children);
        const dataCells = fixed > 0 ? cells.slice(0, -fixed) : cells.slice();
        const actionCells = fixed > 0 ? cells.slice(-fixed) : [];
        const reorderedCells = permutation.map(i => dataCells[i]).filter(Boolean);
        dataCells.forEach(c => {
          if (!reorderedCells.includes(c)) reorderedCells.push(c);
        });
        row.innerHTML = '';
        reorderedCells.forEach(c => row.appendChild(c));
        actionCells.forEach(c => row.appendChild(c));
      });
    },

    showAllColumns() {
      this.isInitialLoad = false;
      document.querySelectorAll('#columnList .column-visibility-checkbox').forEach(checkbox => {
        checkbox.checked = true;
        this.toggleColumnVisibility(checkbox.dataset.columnIndex, true);
      });
    },

    hideAllColumns() {
      this.isInitialLoad = false;
      document.querySelectorAll('#columnList .column-visibility-checkbox').forEach((checkbox, index) => {
        const shouldHide = index !== 0;
        checkbox.checked = !shouldHide;
        this.toggleColumnVisibility(checkbox.dataset.columnIndex, !shouldHide);
      });
    },

    resetColumns() {
      fetch(@json(route('user-table-settings.reset')), {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({ table_identifier: this.tableIdentifier })
      })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            if (window.rlToast) window.rlToast('Settings reset successfully');
            setTimeout(() => location.reload(), 600);
          } else if (window.rlToast) window.rlToast('Failed to reset settings');
        })
        .catch(() => { if (window.rlToast) window.rlToast('Error resetting settings'); });
    },

    updateColumnStats() {
      const visibleCount = document.querySelectorAll('#columnList .column-visibility-checkbox:checked').length;
      const totalCount = document.querySelectorAll('#columnList .column-visibility-checkbox').length;
      const v = document.getElementById('visibleCount');
      const t = document.getElementById('totalCount');
      if (v) v.textContent = visibleCount;
      if (t) t.textContent = totalCount;
    },

    exportTable(format) {
      if (!this.exportRoute) return;
      const visibleColumns = [];
      const columnOrder = [];
      document.querySelectorAll('#columnList .column-item').forEach(item => {
        const checkbox = item.querySelector('.column-visibility-checkbox');
        const columnKey = item.dataset.columnKey;
        if (!columnKey) return;
        columnOrder.push(columnKey);
        if (checkbox?.checked) visibleColumns.push(columnKey);
      });
      const url = new URL(this.exportRoute, window.location.origin);
      url.searchParams.set('format', format);
      url.searchParams.set('visible_columns', JSON.stringify(visibleColumns));
      url.searchParams.set('column_order', JSON.stringify(columnOrder));
      const currentUrl = new URL(window.location);
      ['rider_id', 'name', 'fleet_supervisor', 'status', 'emirate_hub', 'quick_search', 'rider_top_option_id'].forEach(param => {
        const value = currentUrl.searchParams.get(param);
        if (value) url.searchParams.set(param, value);
      });
      window.location.href = url.toString();
    },

    saveSettings() {
      const settings = { visible_columns: [], column_order: [] };
      document.querySelectorAll('#columnList .column-item').forEach(item => {
        const checkbox = item.querySelector('.column-visibility-checkbox');
        const columnKey = item.dataset.columnKey;
        if (!columnKey) return;
        settings.column_order.push(columnKey);
        if (checkbox?.checked) settings.visible_columns.push(columnKey);
      });
      this.saveToDatabase(settings);
    },

    saveToDatabase(settings) {
      fetch(@json(route('user-table-settings.save')), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          table_identifier: this.tableIdentifier,
          visible_columns: settings.visible_columns,
          column_order: settings.column_order
        })
      })
        .then(r => r.json())
        .then(data => {
          if (data.success && window.rlToast) window.rlToast('Column settings saved');
          else if (!data.success && window.rlToast) window.rlToast('Failed to save column settings');
        })
        .catch(() => { if (window.rlToast) window.rlToast('Error saving column settings'); });
    },

    loadUserSettings() {
      fetch(@json(route('user-table-settings.get')) + '?table_identifier=' + encodeURIComponent(this.tableIdentifier), {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      })
        .then(r => r.json())
        .then(data => {
          if (data.success && data.data && Array.isArray(data.data.visible_columns)) {
            this.applyUserSettings(data.data);
          } else {
            this.applyDefaultSettings();
          }
        })
        .catch(() => this.applyDefaultSettings());
    },

    applyUserSettings(settings) {
      try {
        const availableColumnKeys = Array.from(document.querySelectorAll('#columnList .column-item'))
          .map(item => item.dataset.columnKey).filter(Boolean);
        if (!settings.visible_columns.some(k => availableColumnKeys.includes(k)) && availableColumnKeys.length) {
          this.applyDefaultSettings();
          return;
        }
        document.querySelectorAll('#columnList .column-visibility-checkbox').forEach(checkbox => {
          const columnKey = checkbox.closest('.column-item')?.dataset.columnKey;
          const isVisible = settings.visible_columns.includes(columnKey);
          checkbox.checked = isVisible;
          this.toggleColumnVisibility(checkbox.dataset.columnIndex, isVisible, false);
        });
        if (Array.isArray(settings.column_order) && settings.column_order.length) {
          this.applyColumnOrderFromSettings(settings.column_order);
        }
        this.updateColumnStats();
        setTimeout(() => { this.isInitialLoad = false; }, 500);
      } catch (e) {
        this.applyDefaultSettings();
      }
    },

    applyDefaultSettings() {
      document.querySelectorAll('#columnList .column-visibility-checkbox').forEach(checkbox => {
        checkbox.checked = true;
        this.toggleColumnVisibility(checkbox.dataset.columnIndex, true, false);
      });
      this.updateColumnStats();
      setTimeout(() => { this.isInitialLoad = false; }, 500);
    },

    applyColumnOrderFromSettings(columnOrder) {
      const columnList = document.getElementById('columnList');
      if (!columnList) return;
      const itemsMap = new Map();
      Array.from(columnList.querySelectorAll('.column-item')).forEach(item => {
        itemsMap.set(item.dataset.columnKey, item);
      });
      columnList.innerHTML = '';
      columnOrder.forEach(columnKey => {
        const item = itemsMap.get(columnKey);
        if (item) {
          columnList.appendChild(item);
          itemsMap.delete(columnKey);
        }
      });
      itemsMap.forEach(item => columnList.appendChild(item));
      this.applyColumnOrder();
    },

    reapplySettings() {
      this.isInitialLoad = true;
      this.loadUserSettings();
      this.initializeDropdowns();
    },

    initializeDropdowns() {
      setTimeout(() => {
        if (typeof bootstrap === 'undefined' || !bootstrap.Dropdown) return;
        document.querySelectorAll('#' + this.tableId + ' [data-bs-toggle="dropdown"]').forEach(element => {
          const existing = bootstrap.Dropdown.getInstance(element);
          if (existing) existing.dispose();
          new bootstrap.Dropdown(element, {
            popperConfig(defaultConfig) {
              return {
                ...defaultConfig,
                strategy: 'fixed',
                modifiers: [
                  ...(defaultConfig.modifiers || []),
                  { name: 'preventOverflow', options: { boundary: 'viewport' } },
                ],
              };
            },
          });
        });
      }, 200);
    },
  };

  ColumnController.init();
  window.ColumnController = ColumnController;
});
</script>
