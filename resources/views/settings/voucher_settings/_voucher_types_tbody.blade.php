@forelse($voucherTypes as $idx => $vt)
<tr data-id="{{ $vt->id }}">
  <td class="align-middle">{{ $idx + 1 }}</td>
  <td class="align-middle"><code>{{ $vt->code }}</code></td>
  <td class="align-middle">{{ $vt->label }}</td>
  <td class="align-middle">
    @if(!empty($vt->module_labels))
    @foreach($vt->module_labels as $moduleLabel)
    <span class="badge bg-label-info me-1 mb-1">{{ $moduleLabel }}</span>
    @endforeach
    @else
    <span class="badge bg-label-warning">Not assigned</span>
    @endif
  </td>
  <td class="align-middle">
    @if($vt->is_active)
    <span class="badge bg-label-success">Active</span>
    @else
    <span class="badge bg-label-secondary">Inactive</span>
    @endif
  </td>
  <td class="align-middle text-end">
    <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-voucher-type" data-id="{{ $vt->id }}" data-code="{{ $vt->code }}" data-label="{{ $vt->label }}" data-active="{{ $vt->is_active ? '1' : '0' }}" data-modules='@json($vt->module_keys)' data-allow-edit-voucher="{{ $vt->allow_edit_in_voucher_module ? '1' : '0' }}" data-allow-delete-voucher="{{ $vt->allow_delete_in_voucher_module ? '1' : '0' }}" data-update-url="{{ route('settings-panel.voucher-settings.update-type', ['id' => $vt->id]) }}" title="Edit"><i class="ti ti-edit"></i></button>
    <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-voucher-type" data-id="{{ $vt->id }}" data-code="{{ $vt->code }}" data-label="{{ $vt->label }}" data-delete-url="{{ route('settings-panel.voucher-settings.destroy-type', ['id' => $vt->id]) }}" title="Delete"><i class="ti ti-trash"></i></button>
  </td>
</tr>
@empty
<tr>
  <td colspan="6" class="text-center text-muted py-4">No voucher types. Add one to show when creating a voucher.</td>
</tr>
@endforelse