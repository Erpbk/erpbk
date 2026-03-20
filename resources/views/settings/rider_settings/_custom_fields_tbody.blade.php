@forelse($customFields as $index => $field)
  <tr data-id="{{ $field->id }}">
    <td class="align-middle">
      <span class="drag-handle"><i class="ti ti-grip-vertical"></i></span>
    </td>
    <td class="align-middle">{{ $index + 1 }}</td>
    <td class="align-middle">
      {{ $field->label }}
      @if($field->help_text)
        <span class="d-block text-muted small">{{ $field->help_text }}</span>
      @endif
    </td>
    <td class="align-middle">
      <span class="badge bg-label-primary">
        {{ $field->category ? $field->category->label : '—' }}
      </span>
    </td>
    <td class="align-middle">
      <span class="badge bg-label-secondary">
        {{ $dataTypes[$field->data_type]['label'] ?? $field->data_type }}
      </span>
    </td>
    <td class="align-middle">
      {{ $field->is_mandatory ? 'Yes' : 'No' }}
    </td>
    <td class="text-end align-middle">
      <div class="btn-group btn-group-sm" role="group">
        <button type="button"
                class="btn btn-outline-secondary btn-icon"
                data-id="{{ $field->id }}"
                data-label="{{ $field->label }}"
                data-category-id="{{ $field->category_id }}"
                data-data-type="{{ $field->data_type }}"
                data-help-text="{{ $field->help_text }}"
                data-default-value="{{ $field->default_value }}"
                data-input-format="{{ $field->input_format }}"
                data-is-mandatory="{{ $field->is_mandatory ? 1 : 0 }}"
                data-prevent-duplicate="{{ $field->prevent_duplicate_values ? 1 : 0 }}"
                data-data-privacy='@json($field->data_privacy ?? [])'
                data-config='@json($field->config ?? [])'
                data-update-url="{{ route('settings-panel.rider-settings.update-field', $field->id) }}"
                data-bs-toggle="modal"
                data-bs-target="#editRiderFieldModal"
                onclick="
                  var f = document.getElementById('formEditRiderField');
                  if (f && this.dataset.updateUrl) f.action = this.dataset.updateUrl;
                  document.getElementById('editRiderFieldId').value = this.dataset.id;
                  document.getElementById('editRiderFieldLabel').value = this.dataset.label;
                  document.getElementById('editRiderFieldCategory').value = this.dataset.categoryId || '';
                  document.getElementById('editRiderFieldDataType').value = this.dataset.dataType;
                  document.getElementById('editRiderFieldHelpText').value = this.dataset.helpText || '';
                  document.getElementById('editRiderFieldDefaultValue').value = this.dataset.defaultValue || '';
                  document.getElementById('editRiderFieldInputFormat').value = this.dataset.inputFormat || '';
                  var privacy = {};
                  try { privacy = JSON.parse(this.dataset.dataPrivacy || '{}'); } catch(e) { privacy = {}; }
                  document.getElementById('editRiderFieldPii').checked = !!privacy.pii;
                  document.getElementById('editRiderFieldEphi').checked = !!privacy.ephi;
                  document.getElementById('editRiderPreventDupYes').checked = this.dataset.preventDuplicate === '1';
                  document.getElementById('editRiderPreventDupNo').checked = this.dataset.preventDuplicate !== '1';
                  document.getElementById('editRiderMandatoryYes').checked = this.dataset.isMandatory === '1';
                  document.getElementById('editRiderMandatoryNo').checked = this.dataset.isMandatory !== '1';
                  var cfgInput = document.getElementById('editRiderFieldConfigJson');
                  if (cfgInput) cfgInput.value = this.dataset.config || '{}';
                  if (typeof document.getElementById('editRiderFieldDataType').dispatchEvent === 'function') {
                    document.getElementById('editRiderFieldDataType').dispatchEvent(new Event('change'));
                  }
                ">
          <i class="ti ti-edit"></i>
        </button>
        <form method="POST"
              action="{{ route('settings-panel.rider-settings.destroy-field', $field->id) }}"
              onsubmit="return confirm('Are you sure you want to delete this custom field?');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-icon">
            <i class="ti ti-trash"></i>
          </button>
        </form>
      </div>
    </td>
  </tr>
@empty
  <tr>
    <td colspan="7" class="text-center text-muted py-4">
      No rider custom fields defined yet.
    </td>
  </tr>
@endforelse

<input type="hidden" id="editRiderFieldConfigJson" value="{}">

