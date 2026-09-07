@php
$visaRoute = $visaRoute ?? ((View::shared('settings_panel') ?? false) ? 'settings-panel.visa-statuses' : 'visa-statuses');
$embeddedVisaStatusManager = $embeddedVisaStatusManager ?? false;
$selectedCategoryId = (int) ($selectedCategoryId ?? 0);
$visaStatusReturnTo = $visaStatusReturnTo ?? ($selectedCategoryId ? (route($visaRoute . '.index') . '?category_id=' . $selectedCategoryId) : null);
@endphp
<style>
    .visa-row-actions {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }
    .visa-icon-btn {
        width: 34px;
        height: 34px;
        padding: 0;
        border-radius: .5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        background: #f4f6f9;
        color: #5e6278;
        line-height: 1;
        text-decoration: none;
        transition: background .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .visa-icon-btn i {
        font-size: 1.05rem;
    }
    .visa-icon-btn:hover {
        text-decoration: none;
        box-shadow: 0 1px 4px rgba(15, 23, 42, .08);
    }
    .visa-icon-btn-edit {
        background: #eef4ff;
        border-color: #d6e4ff;
        color: #3b82f6;
    }
    .visa-icon-btn-edit:hover {
        background: #3b82f6;
        border-color: #3b82f6;
        color: #fff;
    }
    .visa-icon-btn-ok {
        background: #e8f8ef;
        border-color: #c6edd6;
        color: #198754;
    }
    .visa-icon-btn-ok:hover {
        background: #198754;
        border-color: #198754;
        color: #fff;
    }
    .visa-icon-btn-mute {
        background: #fff6e8;
        border-color: #ffe2b8;
        color: #d97706;
    }
    .visa-icon-btn-mute:hover {
        background: #d97706;
        border-color: #d97706;
        color: #fff;
    }
    .visa-icon-btn-delete {
        background: #fff1f2;
        border-color: #ffd6db;
        color: #dc3545;
    }
    .visa-icon-btn-delete:hover {
        background: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }
</style>
<div class="table-responsive">
    <table class="table table-striped" id="visa-statuses-table">
        <thead>
            <tr>
                <th style="width: 32px;" class="text-center" title="Drag to reorder"></th>
                <th class="sorting">Code</th>
                <th class="sorting">Name</th>
                <th class="sorting">Type</th>
                <th class="sorting">Default Fee</th>
                <th class="sorting">Required</th>
                <th class="sorting">Status</th>
                <th class="sorting">Display Order</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="visa-statuses-tbody">
            @forelse($visaStatuses as $status)
            <tr data-id="{{ $status->id }}" data-category-id="{{ (int) $status->visa_renewal_category_id }}">
                <td class="text-center visa-drag-handle" style="cursor: grab; user-select: none;">
                    <i class="ti ti-grip-vertical ti-sm text-muted"></i>
                </td>
                <td>{{ $status->code ?? 'N/A' }}</td>
                <td>{{ $status->name }}</td>
                <td>{{ $status->category ?: '—' }}</td>
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
                    <div class="visa-row-actions">
                        @can('visa_expense_edit')
                        @if($embeddedVisaStatusManager)
                        <button
                            type="button"
                            class="visa-icon-btn visa-icon-btn-edit js-visa-status-edit-btn"
                            title="Edit"
                            data-bs-toggle="modal"
                            data-bs-target="#editVisaStatusModal"
                            data-id="{{ $status->id }}"
                            data-code="{{ $status->code }}"
                            data-name="{{ $status->name }}"
                            data-category="{{ $status->category }}"
                            data-visa-renewal-category-id="{{ (int) $status->visa_renewal_category_id }}"
                            data-default-fee="{{ $status->default_fee }}"
                            data-description="{{ $status->description }}"
                            data-is-required="{{ $status->is_required ? 1 : 0 }}"
                            data-is-active="{{ $status->is_active ? 1 : 0 }}"
                            data-display-order="{{ $status->display_order }}">
                            <i class="ti ti-pencil"></i>
                        </button>
                        @else
                        <a href="{{ route($visaRoute . '.edit', $status->id) }}" class="visa-icon-btn visa-icon-btn-edit" title="Edit">
                            <i class="ti ti-pencil"></i>
                        </a>
                        @endif
                        <a href="{{ route($visaRoute . '.toggle-active', $status->id) . ($visaStatusReturnTo ? ('?return_to=' . urlencode($visaStatusReturnTo)) : '') }}"
                            class="visa-icon-btn {{ $status->is_active ? 'visa-icon-btn-mute' : 'visa-icon-btn-ok' }}"
                            title="{{ $status->is_active ? 'Deactivate' : 'Activate' }}">
                            <i class="ti ti-{{ $status->is_active ? 'player-pause' : 'player-play' }}"></i>
                        </a>
                        @endcan
                        @can('visa_expense_delete')
                        <button
                            type="button"
                            class="visa-icon-btn visa-icon-btn-delete js-visa-status-delete-btn"
                            title="Delete"
                            data-delete-url="{{ route($visaRoute . '.destroy', $status->id) . ($visaStatusReturnTo ? ('?return_to=' . urlencode($visaStatusReturnTo)) : '') }}"
                            data-delete-form-id="delete-form-{{ $status->id }}">
                            <i class="ti ti-trash"></i>
                        </button>
                        <form id="delete-form-{{ $status->id }}" action="{{ route($visaRoute . '.destroy', $status->id) . ($visaStatusReturnTo ? ('?return_to=' . urlencode($visaStatusReturnTo)) : '') }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center text-muted py-5">
                    <div class="py-3">
                        <i class="ti ti-list-off mb-2" style="font-size: 1.75rem;"></i>
                        <div>No visa statuses in this category.</div>
                        <div class="small">Add statuses here before generating visa expense tickets for this category.</div>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>