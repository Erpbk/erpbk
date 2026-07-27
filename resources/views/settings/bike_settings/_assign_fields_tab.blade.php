@php
$showAssignFieldsTab = request()->query('tab') === 'assign-fields';
$assignFields = $assignFieldAssignments ?? collect();
$assignBuiltinFields = $assignFields->where('kind', '!=', 'custom');
$assignCustomFields = $assignFields->where('kind', 'custom');
@endphp

<div class="tab-pane fade {{ $showAssignFieldsTab ? 'show active' : '' }}" id="tab-bike-assign-fields" role="tabpanel">
  <div class="d-flex justify-content-end align-items-center mb-3">
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBikeAssignFieldModal">
      <i class="ti ti-plus me-1"></i> Add Custom Field
    </button>
  </div>

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <p class="text-muted small mb-0">
      Built-in fields are assign/return modal inputs (status, rider, date, notes, etc.).
      Custom fields here appear only on assign modals—not under <b>Bike Fields</b>.
    </p>
  </div>

  <div class="modal fade" id="addBikeAssignFieldModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title">Add New Assign Field</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form id="formAddBikeAssignField" action="{{ route($settingsRoutePrefix . '.store-assign-field', $settingsRouteParams) }}" method="POST">
          @csrf
          <div class="modal-body pt-0">
            <div class="row g-3 align-items-end">
              <div class="col-md-4">
                <label class="form-label">Label</label>
                <input type="text" name="label" class="form-control" required maxlength="255">
              </div>

              <div class="col-md-3">
                <label class="form-label">Data Type</label>
                <select name="data_type" id="addBikeAssignFieldDataType" class="form-select" required>
                  @foreach($dataTypes as $typeKey => $spec)
                  <option value="{{ $typeKey }}">{{ $spec['label'] }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label d-block">Show on modals</label>
                <div class="form-check">
                  <input type="hidden" name="show_on_active" value="0">
                  <input type="checkbox" name="show_on_active" value="1" class="form-check-input" id="assignFieldShowActive" checked>
                  <label class="form-check-label" for="assignFieldShowActive">Assign modal</label>
                </div>
                <div class="form-check">
                  <input type="hidden" name="show_on_change" value="0">
                  <input type="checkbox" name="show_on_change" value="1" class="form-check-input" id="assignFieldShowChange">
                  <label class="form-check-label" for="assignFieldShowChange">Return modal</label>
                </div>
              </div>
                      <input type="hidden" name="is_mandatory" value="0">

              <div class="col-md-12">
                <label class="form-label">Help Text</label>
                <input type="text" name="help_text" class="form-control" maxlength="1000">
              </div>

              <div class="col-md-6">
                <label class="form-label">Default Value</label>
                <input type="text" name="default_value" class="form-control" maxlength="500">
              </div>

              <div class="col-md-6" id="addBikeAssignFieldOptionsWrap">
                <label class="form-label">Dropdown Options (one per line)</label>
                <input type="hidden" name="config_options" id="addBikeAssignFieldConfigOptionsHidden" value="">
                <div id="addBikeAssignFieldOptionsRows" class="d-flex flex-column gap-2"></div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addBikeAssignFieldOptionRowBtn">Add Option</button>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" type="submit">Add custom field</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @include('settings.bike_settings._assign_field_edit_modal')

  <div class="table-responsive">
    <table class="table table-hover bike-settings-table mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 48px;" class="text-center" title="{{ __('Drag to reorder') }}"></th>
          <th>Field</th>
          <th>Modals</th>
          <th>Type</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>

      @if($assignBuiltinFields->isNotEmpty())
      <tbody class="bike-assign-fields-builtin-sortable-tbody" id="bikeAssignFieldsBuiltinSortable">
        @foreach($assignBuiltinFields as $row)
        @php
        $fieldLabel = $row->resolvedLabel();
        $keyLabel = $row->field_key ?? '—';
        $builtinConfigOptions = '';
        if (is_array($row->input_config ?? null) && isset($row->input_config['options'])) {
            $builtinConfigOptions = (string) $row->input_config['options'];
        }
        $updateAssignUrl = route($settingsRoutePrefix . '.update-assign-field-item', array_merge($settingsRouteParams, ['id' => $row->id]));
        $assignEditPayload = [
            'kind' => 'built-in',
            'update_url' => $updateAssignUrl,
            'field_key' => $row->field_key,
            'display_label' => $row->display_label ?: $fieldLabel,
            'input_type' => $row->input_type ?? 'text',
            'is_visible' => (bool) ($row->is_visible ?? true),
            'is_required' => (bool) ($row->is_required ?? false),
            'show_on_active' => (bool) $row->show_on_active,
            'show_on_change' => (bool) $row->show_on_change,
            'input_config_options' => $builtinConfigOptions,
        ];
        @endphp
        <tr data-assign-field-id="{{ $row->id }}">
          <td class="align-middle"><span class="drag-handle cursor-grab text-muted" title="{{ __('Drag to reorder') }}"><i class="ti ti-grip-vertical"></i></span></td>
          <td class="align-middle">
            <span class="fw-semibold">{{ $fieldLabel }}</span>
            <span class="text-muted ms-1">({{ $keyLabel }})</span>
            <span class="badge bg-label-secondary ms-1">Built-in</span>
          </td>
          <td class="align-middle">
            <div class="d-flex flex-wrap gap-3 mt-1">
              <div class="form-check form-switch mb-0">
                <input type="checkbox" class="form-check-input bike-assign-modal-toggle" data-id="{{ $row->id }}" data-prop="show_on_active" {{ $row->show_on_active ? 'checked' : '' }}>
                <label class="form-check-label small">Assign</label>
              </div>
              <div class="form-check form-switch mb-0">
                <input type="checkbox" class="form-check-input bike-assign-modal-toggle" data-id="{{ $row->id }}" data-prop="show_on_change" {{ $row->show_on_change ? 'checked' : '' }}>
                <label class="form-check-label small">Return</label>
              </div>
            </div>
          </td>
          <td class="align-middle">
            <span class="badge bg-label-info">{{ $row->input_type ?? 'virtual' }}</span>
          </td>
          <td class="align-middle text-end">
            <button type="button"
              class="btn btn-sm btn-outline-primary btn-edit-bike-assign-field"
              data-bs-toggle="modal"
              data-bs-target="#editBikeAssignFieldModal"
              data-assign-payload='@json($assignEditPayload)'
              title="Edit assign field">
              <i class="ti ti-pencil"></i>
            </button>
          </td>
        </tr>
        @endforeach
      </tbody>
      @endif

      @if($assignCustomFields->isNotEmpty())
      <tbody class="bike-assign-fields-custom-sortable-tbody" id="bikeAssignFieldsCustomSortable">
        @foreach($assignCustomFields as $row)
        @php
        $fieldLabel = $row->resolvedLabel();
        $cf = $row->customField;
        $isReq = (bool) ($row->is_required ?? $cf?->is_mandatory ?? false);
        $customConfigOptions = '';
        if ($cf && is_array($cf->config ?? null) && isset($cf->config['options'])) {
            $customConfigOptions = (string) $cf->config['options'];
        }
        $updateAssignUrl = route($settingsRoutePrefix . '.update-assign-field-item', array_merge($settingsRouteParams, ['id' => $row->id]));
        $assignEditPayload = [
            'kind' => 'custom',
            'update_url' => $updateAssignUrl,
            'label' => $cf?->label ?? $row->display_label ?? $fieldLabel,
            'help_text' => $cf?->help_text ?? '',
            'data_type' => $cf?->data_type ?? 'text',
            'is_mandatory' => (bool) ($cf?->is_mandatory ?? false),
            'default_value' => $cf?->default_value ?? '',
            'input_format' => $cf?->input_format ?? '',
            'config_options' => $customConfigOptions,
            'is_visible' => (bool) ($row->is_visible ?? true),
            'is_required' => $isReq,
            'show_on_active' => (bool) $row->show_on_active,
            'show_on_change' => (bool) $row->show_on_change,
        ];
        @endphp
        <tr class="table-light" data-assign-field-id="{{ $row->id }}">
          <td class="align-middle"><span class="drag-handle cursor-grab text-muted"><i class="ti ti-grip-vertical"></i></span></td>
          <td class="align-middle">
            <span class="fw-semibold">{{ $fieldLabel }}</span>
            <span class="badge bg-label-secondary ms-1">Custom</span>
            @if($cf)
            <span class="text-muted ms-1">({{ $cf->data_type }})</span>
            @endif
          </td>
          <td class="align-middle">
            <div class="d-flex flex-wrap gap-3">
              <div class="form-check form-switch mb-0">
                <input type="checkbox" class="form-check-input bike-assign-modal-toggle" data-id="{{ $row->id }}" data-prop="show_on_active" {{ $row->show_on_active ? 'checked' : '' }}>
                <label class="form-check-label small">Assign</label>
              </div>
              <div class="form-check form-switch mb-0">
                <input type="checkbox" class="form-check-input bike-assign-modal-toggle" data-id="{{ $row->id }}" data-prop="show_on_change" {{ $row->show_on_change ? 'checked' : '' }}>
                <label class="form-check-label small">Return</label>
              </div>
            </div>
          </td>
          <td class="align-middle">
            @if($cf)
            <span class="badge bg-label-info">{{ $cf->data_type }}</span>
            @else
            <span class="text-muted">—</span>
            @endif
          </td>
          <td class="align-middle text-end">
            <button type="button"
              class="btn btn-sm btn-outline-primary btn-edit-bike-assign-field"
              data-bs-toggle="modal"
              data-bs-target="#editBikeAssignFieldModal"
              data-assign-payload='@json($assignEditPayload)'
              title="Edit custom field">
              <i class="ti ti-pencil"></i>
            </button>
            <form action="{{ route($settingsRoutePrefix . '.destroy-assign-field', array_merge($settingsRouteParams, ['id' => $row->id])) }}" method="POST" class="d-inline ms-1" onsubmit="return confirm('Delete this assign custom field?');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete custom field">
                <i class="ti ti-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
      @endif

      @if($assignFields->isEmpty())
      <tbody>
        <tr>
          <td colspan="5" class="text-center text-muted py-3">No assign fields configured yet.</td>
        </tr>
      </tbody>
      @endif
    </table>
  </div>
</div>

<script>
  (function() {
    var assignUpdateUrl = @json(route($settingsRoutePrefix . '.update-assign-field', $settingsRouteParams));
    var assignReorderUrl = @json(route($settingsRoutePrefix . '.reorder-assign-fields', $settingsRouteParams));
    var csrf = @json(csrf_token());

    function postAssignField(payload) {
      var body = new URLSearchParams();
      body.append('_token', csrf);
      Object.keys(payload).forEach(function(k) {
        if (payload[k] !== undefined && payload[k] !== null) {
          body.append(k, String(payload[k]));
        }
      });
      return fetch(assignUpdateUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: body.toString(),
      }).then(function(r) {
        return r.json().then(function(data) {
          return r.ok ? data : Promise.reject(data);
        });
      });
    }

    document.addEventListener('change', function(e) {
      var toggle = e.target.closest('.bike-assign-modal-toggle');
      if (!toggle) return;
      var prop = toggle.dataset.prop;
      var id = toggle.dataset.id;
      if (!prop || !id) return;
      var payload = {
        id: id
      };
      payload[prop] = toggle.checked ? '1' : '0';
      postAssignField(payload).catch(function() {
        toggle.checked = !toggle.checked;
      });
    });

    function initAssignFieldSortable(tbodyId) {
      if (typeof Sortable === 'undefined') return;
      var tbody = document.getElementById(tbodyId);
      if (!tbody || tbody.dataset.sortableInit === '1') return;
      new Sortable(tbody, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function() {
          var builtin = document.getElementById('bikeAssignFieldsBuiltinSortable');
          var custom = document.getElementById('bikeAssignFieldsCustomSortable');
          var order = [];
          [builtin, custom].forEach(function(tb) {
            if (!tb) return;
            Array.from(tb.querySelectorAll('tr[data-assign-field-id]')).forEach(function(tr) {
              order.push(parseInt(tr.getAttribute('data-assign-field-id'), 10));
            });
          });
          fetch(assignReorderUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
            },
            body: JSON.stringify({
              ordered_ids: order,
              _token: csrf
            }),
          });
        },
      });
      tbody.dataset.sortableInit = '1';
    }

    function initAll() {
      initAssignFieldSortable('bikeAssignFieldsBuiltinSortable');
      initAssignFieldSortable('bikeAssignFieldsCustomSortable');
    }

    document.addEventListener('DOMContentLoaded', initAll);
    var assignTabBtn = document.getElementById('tab-bike-assign-fields-btn');
    if (assignTabBtn) {
      assignTabBtn.addEventListener('shown.bs.tab', function() {
        setTimeout(initAll, 50);
        if (window.history && window.history.replaceState) {
          var url = new URL(window.location.href);
          url.searchParams.set('tab', 'assign-fields');
          url.searchParams.delete('active_category_id');
          window.history.replaceState({}, '', url.toString());
        }
      });
    }
  })();
</script>