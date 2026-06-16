@php
$itemRoute = $itemRoute ?? ((View::shared('settings_panel') ?? false) ? 'settings-panel.rider-inventory-items' : 'rider-inventory-items');
$embeddedInventoryItemManager = $embeddedInventoryItemManager ?? false;
$companySlug = request()->route('company_slug') ?? session('company_slug');
@endphp
<div class="table-responsive">
    <table class="table table-striped" id="rider-inventory-items-table">
        <thead>
            <tr>
                <th style="width: 32px;" class="text-center" title="Drag to reorder"></th>
                <th>Item Name</th>
                <th>Item Price</th>
                <th>Status</th>
                <th>Display Order</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="rider-inventory-items-tbody">
            @forelse($items as $item)
            <tr data-id="{{ $item->id }}">
                <td class="text-center visa-drag-handle" style="cursor: grab; user-select: none;">
                    <i class="ti ti-grip-vertical ti-sm text-muted"></i>
                </td>
                <td>{{ $item->name }}</td>
                <td>{{ number_format((float) $item->item_price, 2) }}</td>
                <td>
                    <span class="badge bg-{{ $item->is_active ? 'success' : 'danger' }}">
                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>{{ $item->display_order }}</td>
                <td>
                    <div class="btn-group">
                        @can('riderinventory_edit')
                        @if($embeddedInventoryItemManager)
                        <button type="button"
                            class="btn btn-sm btn-primary js-inventory-item-edit-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editInventoryItemModal"
                            data-id="{{ $item->id }}"
                            data-name="{{ $item->name }}"
                            data-item-price="{{ $item->item_price }}"
                            data-display-order="{{ $item->display_order }}"
                            data-is-active="{{ $item->is_active ? 1 : 0 }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        @else
                        <a href="{{ route($itemRoute . '.edit', ['company_slug' => $companySlug, 'rider_inventory_item' => $item->id]) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                        <a href="{{ route($itemRoute . '.toggle-active', ['company_slug' => $companySlug, 'id' => $item->id]) }}" class="btn btn-sm btn-{{ $item->is_active ? 'warning' : 'success' }}" title="{{ $item->is_active ? 'Deactivate' : 'Activate' }}">
                            <i class="fas fa-{{ $item->is_active ? 'ban' : 'check' }}"></i>
                        </a>
                        @endcan
                        @can('riderinventory_delete')
                        <button type="button" class="btn btn-sm btn-danger js-inventory-item-delete-btn" data-delete-form-id="delete-inventory-item-{{ $item->id }}">
                            <i class="fas fa-trash"></i>
                        </button>
                        <form id="delete-inventory-item-{{ $item->id }}" action="{{ route($itemRoute . '.destroy', ['company_slug' => $companySlug, 'rider_inventory_item' => $item->id]) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">No inventory items found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
