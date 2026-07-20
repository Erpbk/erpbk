@php
$licenseRoute = $licenseRoute ?? ((View::shared('settings_panel') ?? false) ? 'settings-panel.license-statuses' : 'license-statuses');
$embeddedLicenseStatusManager = $embeddedLicenseStatusManager ?? false;
$licenseStatusReturnTo = $licenseStatusReturnTo ?? null;
@endphp
<div class="table-responsive">
    <table class="table table-striped" id="license-statuses-table">
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
        <tbody id="license-statuses-tbody">
            @forelse($licenseStatuses as $status)
            <tr data-id="{{ $status->id }}">
                <td class="text-center visa-drag-handle" style="cursor: grab; user-select: none;">
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
                        @can('license_expense_edit')
                        @if($embeddedLicenseStatusManager)
                        <button
                            type="button"
                            class="btn btn-sm btn-primary js-visa-status-edit-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editLicenseStatusModal"
                            data-id="{{ $status->id }}"
                            data-code="{{ $status->code }}"
                            data-name="{{ $status->name }}"
                            data-category="{{ $status->category }}"
                            data-default-fee="{{ $status->default_fee }}"
                            data-description="{{ $status->description }}"
                            data-is-required="{{ $status->is_required ? 1 : 0 }}"
                            data-is-active="{{ $status->is_active ? 1 : 0 }}"
                            data-display-order="{{ $status->display_order }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        @else
                        <a href="{{ route($licenseRoute . '.edit', $status->id) }}" class='btn btn-sm btn-primary'>
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                        <a href="{{ route($licenseRoute . '.toggle-active', $status->id) . ($licenseStatusReturnTo ? ('?return_to=' . urlencode($licenseStatusReturnTo)) : '') }}" class='btn btn-sm btn-{{ $status->is_active ? 'warning' : 'success' }}' title="{{ $status->is_active ? 'Deactivate' : 'Activate' }}">
                            <i class="fas fa-{{ $status->is_active ? 'ban' : 'check' }}"></i>
                        </a>
                        @endcan
                        @can('license_expense_delete')
                        <button
                            type="button"
                            class="btn btn-sm btn-danger js-visa-status-delete-btn"
                            data-delete-url="{{ route($licenseRoute . '.destroy', $status->id) . ($licenseStatusReturnTo ? ('?return_to=' . urlencode($licenseStatusReturnTo)) : '') }}"
                            data-delete-form-id="delete-form-{{ $status->id }}">
                            <i class="fas fa-trash"></i>
                        </button>
                        <form id="delete-form-{{ $status->id }}" action="{{ route($licenseRoute . '.destroy', $status->id) . ($licenseStatusReturnTo ? ('?return_to=' . urlencode($licenseStatusReturnTo)) : '') }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center text-muted py-4">No License Statuses found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
