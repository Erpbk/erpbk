@extends($layout ?? 'layouts.app')

@section('title', 'Rider Inventory Items')

@section('content')
@php
$itemRoute = $itemRoute ?? ((View::shared('settings_panel') ?? false) ? 'settings-panel.rider-inventory-items' : 'rider-inventory-items');
$companySlug = request()->route('company_slug') ?? session('company_slug');
$returnTo = url()->current();
@endphp
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Rider Inventory Items</h1>
            </div>
            <div class="col-sm-6">
                @can('riders_inventory_create')
                <button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#createInventoryItemModal">
                    <i class="ti ti-plus me-1"></i> Add New Item
                </button>
                @endcan
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    @include('adminlte-templates::common.errors')

    <div class="card">
        <div class="card-body">
            <div id="inventoryItemsTableWrapper">
                @include('rider_inventory_items.table', [
                    'items' => $items,
                    'itemRoute' => $itemRoute,
                    'embeddedInventoryItemManager' => true,
                ])
            </div>
        </div>
    </div>
</div>

@can('riders_inventory_create')
<div class="modal fade" id="createInventoryItemModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route($itemRoute . '.store', ['company_slug' => $companySlug]) }}" id="createInventoryItemForm">
                @csrf
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                <div class="modal-header">
                    <h5 class="modal-title">Add Rider Inventory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required" for="create_item_name">Item Name</label>
                            <input type="text" name="name" id="create_item_name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="create_item_price">Item Price</label>
                            <input type="number" name="item_price" id="create_item_price" class="form-control @error('item_price') is-invalid @enderror" value="{{ old('item_price', '0.00') }}" min="0" step="0.01" required>
                            @error('item_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="create_display_order">Display Order</label>
                            <input type="number" name="display_order" id="create_display_order" class="form-control @error('display_order') is-invalid @enderror" value="{{ old('display_order') }}" min="1">
                            @error('display_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="create_is_active" class="form-check-input" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="create_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@can('riders_inventory_edit')
<div class="modal fade" id="editInventoryItemModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editInventoryItemForm" method="POST" action="#">
                @csrf
                @method('PUT')
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Rider Inventory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required" for="edit_item_name">Item Name</label>
                            <input type="text" name="name" id="edit_item_name" class="form-control" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="edit_item_price">Item Price</label>
                            <input type="number" name="item_price" id="edit_item_price" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="edit_display_order">Display Order</label>
                            <input type="number" name="display_order" id="edit_display_order" class="form-control" min="1">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" value="1">
                                <label class="form-check-label" for="edit_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<div id="inventory-item-manager-config"
    data-edit-url-template="{{ route($itemRoute . '.update', ['company_slug' => $companySlug, 'rider_inventory_item' => '__ID__']) }}"
    data-reorder-url="{{ route($itemRoute . '.reorder', ['company_slug' => $companySlug]) }}">
</div>
@endsection

@push('page_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const config = document.getElementById('inventory-item-manager-config');
    const reorderUrl = config ? config.getAttribute('data-reorder-url') : '';
    const editUrlTemplate = config ? config.getAttribute('data-edit-url-template') : '';
    const tbody = document.getElementById('rider-inventory-items-tbody');

    if (tbody && typeof Sortable !== 'undefined' && reorderUrl) {
        Sortable.create(tbody, {
            handle: '.visa-drag-handle',
            animation: 150,
            onEnd: function () {
                const order = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function (row) {
                    return row.getAttribute('data-id');
                });
                fetch(reorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ order: order })
                });
            }
        });
    }

    document.querySelectorAll('.js-inventory-item-delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Delete this inventory item?')) return;
            document.getElementById(btn.getAttribute('data-delete-form-id')).submit();
        });
    });

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.js-inventory-item-edit-btn');
        if (!editBtn) return;

        const form = document.getElementById('editInventoryItemForm');
        if (!form) return;

        form.action = editUrlTemplate.replace('__ID__', String(editBtn.dataset.id || ''));
        document.getElementById('edit_item_name').value = editBtn.dataset.name || '';
        document.getElementById('edit_item_price').value = editBtn.dataset.itemPrice || '0';
        document.getElementById('edit_display_order').value = editBtn.dataset.displayOrder || '';
        document.getElementById('edit_is_active').checked = String(editBtn.dataset.isActive || '0') === '1';
    });

    @if($errors->any() && (old('_method') === 'PUT' || session('open_edit_item_id')))
    const editModalEl = document.getElementById('editInventoryItemModal');
    if (editModalEl && typeof bootstrap !== 'undefined') {
        const editId = @json(session('open_edit_item_id'));
        if (editId && editUrlTemplate) {
            const form = document.getElementById('editInventoryItemForm');
            form.action = editUrlTemplate.replace('__ID__', String(editId));
            document.getElementById('edit_item_name').value = @json(old('name'));
            document.getElementById('edit_item_price').value = @json(old('item_price', '0'));
            document.getElementById('edit_display_order').value = @json(old('display_order', ''));
            document.getElementById('edit_is_active').checked = {{ old('is_active') ? 'true' : 'false' }};
        }
        bootstrap.Modal.getOrCreateInstance(editModalEl).show();
    }
    @elseif($errors->any())
    const createModalEl = document.getElementById('createInventoryItemModal');
    if (createModalEl && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(createModalEl).show();
    }
    @endif

    @if(session('open_create_modal'))
    const openCreate = document.getElementById('createInventoryItemModal');
    if (openCreate && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(openCreate).show();
    }
    @endif

    @if(session('open_edit_item_id') || request()->filled('open_edit'))
    const openEdit = document.getElementById('editInventoryItemModal');
    const openEditId = @json(session('open_edit_item_id') ?? (int) request('open_edit'));
    if (openEdit && openEditId && typeof bootstrap !== 'undefined') {
        const trigger = document.querySelector('.js-inventory-item-edit-btn[data-id="' + openEditId + '"]');
        if (trigger) trigger.click();
        else bootstrap.Modal.getOrCreateInstance(openEdit).show();
    }
    @endif
});
</script>
@endpush
