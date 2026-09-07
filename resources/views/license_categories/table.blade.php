@php
$routePrefix = $routePrefix ?? 'settings-panel.license-categories';
$embeddedManager = $embeddedManager ?? false;
$returnTo = $returnTo ?? null;
@endphp
<div class="table-responsive">
    <table class="table table-striped" id="license-categories-table">
        <thead>
            <tr>
                <th style="width: 32px;" class="text-center" title="Drag to reorder"></th>
                <th>Name</th>
                <th>Display Order</th>
                <th>Default</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="license-categories-tbody">
            @forelse($categories as $category)
            <tr data-id="{{ $category->id }}">
                <td class="text-center license-category-drag-handle" style="cursor: grab; user-select: none;">
                    <i class="ti ti-grip-vertical ti-sm text-muted"></i>
                </td>
                <td>{{ $category->name }}</td>
                <td>{{ $category->display_order }}</td>
                <td>
                    @if($category->is_default)
                    <span class="badge bg-label-primary">Default</span>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <span class="badge bg-{{ $category->is_active ? 'success' : 'danger' }}">
                        {{ $category->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>
                    <div class="visa-row-actions">
                        @can('license_expense_edit')
                        @if($embeddedManager)
                        <button type="button"
                            class="visa-icon-btn visa-icon-btn-edit js-license-category-edit-btn"
                            title="Edit"
                            data-bs-toggle="modal"
                            data-bs-target="#editLicenseCategoryModal"
                            data-id="{{ $category->id }}"
                            data-name="{{ $category->name }}"
                            data-display-order="{{ $category->display_order }}"
                            data-is-default="{{ $category->is_default ? 1 : 0 }}"
                            data-is-active="{{ $category->is_active ? 1 : 0 }}">
                            <i class="ti ti-pencil"></i>
                        </button>
                        @endif
                        @endcan
                        @can('license_expense_delete')
                        @if(!$category->is_default)
                        <button type="button"
                            class="visa-icon-btn visa-icon-btn-delete js-license-category-delete-btn"
                            title="Delete"
                            data-delete-url="{{ route($routePrefix . '.destroy', $category->id) . ($returnTo ? ('?return_to=' . urlencode($returnTo)) : '') }}">
                            <i class="ti ti-trash"></i>
                        </button>
                        @endif
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">No license categories found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
