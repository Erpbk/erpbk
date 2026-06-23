@php
$routePrefix = $routePrefix ?? 'settings-panel.visa-renewal-categories';
$embeddedManager = $embeddedManager ?? false;
$returnTo = $returnTo ?? null;
@endphp
<div class="table-responsive">
    <table class="table table-striped" id="visa-renewal-categories-table">
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
        <tbody id="visa-renewal-categories-tbody">
            @forelse($categories as $category)
            <tr data-id="{{ $category->id }}">
                <td class="text-center visa-renewal-drag-handle" style="cursor: grab; user-select: none;">
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
                    <div class="btn-group">
                        @can('visaexpense_edit')
                        @if($embeddedManager)
                        <button type="button"
                            class="btn btn-sm btn-primary js-visa-renewal-edit-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editVisaRenewalCategoryModal"
                            data-id="{{ $category->id }}"
                            data-name="{{ $category->name }}"
                            data-display-order="{{ $category->display_order }}"
                            data-is-default="{{ $category->is_default ? 1 : 0 }}"
                            data-is-active="{{ $category->is_active ? 1 : 0 }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        @endif
                        @endcan
                        @can('visaexpense_delete')
                        @if(!$category->is_default)
                        <button type="button"
                            class="btn btn-sm btn-danger js-visa-renewal-delete-btn"
                            data-delete-url="{{ route($routePrefix . '.destroy', $category->id) . ($returnTo ? ('?return_to=' . urlencode($returnTo)) : '') }}">
                            <i class="fas fa-trash"></i>
                        </button>
                        @endif
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">No renewal categories found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
