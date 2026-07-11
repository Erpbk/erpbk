@php
$bikeRegistrationRoute = $bikeRegistrationRoute ?? ((View::shared('settings_panel') ?? false) ? 'settings-panel.bike-registration-statuses' : 'bike-registration-statuses');
@endphp
<div class="table-responsive">
    <table class="table table-striped" id="bike-registration-statuses-table">
        <thead>
            <tr>
                <th style="width: 32px;" class="text-center" title="Drag to reorder"></th>
                <th class="sorting">Code</th>
                <th class="sorting">Name</th>
                <th class="sorting">Category</th>
                <th class="sorting">Default Fee</th>
                <th class="sorting">Required</th>
                <th class="sorting">Status</th>
                <th class="sorting">Display Order</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="bike-registration-statuses-tbody">
            @forelse($bikeRegistrationStatuses as $status)
            <tr data-id="{{ $status->id }}">
                <td class="text-center bike-reg-drag-handle" style="cursor: grab; user-select: none;">
                    <i class="ti ti-grip-vertical ti-sm text-muted"></i>
                </td>
                <td>{{ $status->code ?? 'N/A' }}</td>
                <td>{{ $status->name }}</td>
                <td>{{ $status->category }}</td>
                <td>{{ number_format($status->default_fee, 2) }}</td>
                <td>
                    <span class="badge bg-{{ $status->is_required ? 'primary' : 'secondary' }}">
                        {{ $status->is_required ? 'Yes' : 'No' }}
                    </span>
                </td>
                <td>
                    <span class="badge bg-{{ $status->is_active ? 'success' : 'danger' }}">
                        {{ $status->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>{{ $status->display_order }}</td>
                <td>
                    <div class='btn-group'>
                        @can('bikes_registration_edit')
                        <a href="{{ route($bikeRegistrationRoute . '.edit', $status->id) }}" class='btn btn-sm btn-primary'>
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="{{ route($bikeRegistrationRoute . '.toggle-active', $status->id) }}" class='btn btn-sm btn-{{ $status->is_active ? 'warning' : 'success' }}' title="{{ $status->is_active ? 'Deactivate' : 'Activate' }}">
                            <i class="fas fa-{{ $status->is_active ? 'ban' : 'check' }}"></i>
                        </a>
                        @endcan
                        @can('bikes_registration_delete')
                        <form action="{{ route($bikeRegistrationRoute . '.destroy', $status->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this status?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center text-muted py-4">No registration statuses found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
