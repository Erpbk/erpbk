@forelse($customFields as $idx => $cf)
<tr data-id="{{ $cf->id }}">
  <td class="align-middle"><i class="ti ti-grip-vertical drag-handle" aria-hidden="true"></i></td>
  <td class="align-middle">{{ $idx + 1 }}</td>
  <td class="align-middle">{{ $cf->label }}</td>
  <td class="align-middle"><span class="badge bg-label-info">{{ $dataTypes[$cf->data_type]['label'] ?? $cf->data_type }}</span></td>
  <td class="align-middle">{{ $cf->is_mandatory ? 'Yes' : 'No' }}</td>
  <td class="align-middle text-end">
    <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-custom-field" data-id="{{ $cf->id }}" data-label="{{ $cf->label }}" data-type="{{ $cf->data_type }}" data-mandatory="{{ $cf->is_mandatory ? '1' : '0' }}" data-config="{{ json_encode($cf->config ?? []) }}" title="Edit"><i class="ti ti-edit"></i></button>
    <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-custom-field" data-id="{{ $cf->id }}" data-label="{{ $cf->label }}" title="Delete"><i class="ti ti-trash"></i></button>
  </td>
</tr>
@empty
<tr>
  <td colspan="6" class="text-center text-muted py-4">No custom fields yet. Click “Add New Field” to create one.</td>
</tr>
@endforelse
