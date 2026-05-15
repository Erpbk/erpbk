@forelse($employeeStatusOptions as $idx => $statusOption)
<tr data-id="{{ $statusOption->id }}" data-status-name="{{ $statusOption->name }}">
  <td class="align-middle">{{ $idx + 1 }}</td>
  <td class="align-middle">
    <span class="fw-semibold employee-status-name">{{ $statusOption->name }}</span>
  </td>
  <td class="align-middle text-center">
    <div class="form-check form-switch d-inline-block mb-0">
      <input type="checkbox"
        class="form-check-input employee-status-top-bar-toggle"
        id="employee-status-top-{{ $statusOption->id }}"
        data-id="{{ $statusOption->id }}"
        data-update-url="{{ route('settings-panel.employee-settings.update-employee-status', ['id' => $statusOption->id]) }}"
        {{ ($statusOption->show_in_top_bar ?? true) ? 'checked' : '' }}
        title="Show in top bar">
      <label class="form-check-label visually-hidden" for="employee-status-top-{{ $statusOption->id }}">Top Bar</label>
    </div>
  </td>
  <td class="align-middle text-center">
    <div class="form-check form-switch d-inline-block mb-0">
      <input type="checkbox"
        class="form-check-input employee-status-view-card-toggle"
        id="employee-status-view-{{ $statusOption->id }}"
        data-id="{{ $statusOption->id }}"
        data-update-url="{{ route('settings-panel.employee-settings.update-employee-status', ['id' => $statusOption->id]) }}"
        {{ ($statusOption->show_in_view_cards ?? true) ? 'checked' : '' }}
        title="Show in view card">
      <label class="form-check-label visually-hidden" for="employee-status-view-{{ $statusOption->id }}">View Card</label>
    </div>
  </td>
  <td class="text-end align-middle">
    <div class="btn-group btn-group-sm" role="group">
      <button type="button"
        class="btn btn-outline-secondary mx-2 btn-edit-employee-status"
        data-id="{{ $statusOption->id }}"
        data-name="{{ $statusOption->name }}"
        data-show-in-top-bar="{{ ($statusOption->show_in_top_bar ?? true) ? '1' : '0' }}"
        data-show-in-view-cards="{{ ($statusOption->show_in_view_cards ?? true) ? '1' : '0' }}"
        data-bs-toggle="modal"
        data-bs-target="#editEmployeeStatusModal">
        <i class="ti ti-edit"></i>
      </button>
      <form method="POST"
        action="{{ route('settings-panel.employee-settings.destroy-employee-status', ['id' => $statusOption->id]) }}"
        class="d-inline"
        onsubmit="return confirm('Delete this status? It will be removed from employees too.');">
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
  <td colspan="5" class="text-center text-muted py-4">No employee statuses configured yet.</td>
</tr>
@endforelse