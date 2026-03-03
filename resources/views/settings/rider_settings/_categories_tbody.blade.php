@forelse($categories as $index => $category)
  <tr data-id="{{ $category->id }}">
    <td class="align-middle">
      <span class="drag-handle"><i class="ti ti-grip-vertical"></i></span>
    </td>
    <td class="align-middle">{{ $index + 1 }}</td>
    <td class="align-middle">{{ $category->label }}</td>
    <td class="align-middle">
      @if($category->slug)
        <span class="badge bg-label-info">{{ $category->slug }}</span>
      @else
        <span class="text-muted">—</span>
      @endif
    </td>
    <td class="align-middle">
      @if($category->is_system)
        <span class="badge bg-label-secondary">System</span>
      @else
        <span class="badge bg-label-success">Custom</span>
      @endif
    </td>
    <td class="text-end align-middle">
      <div class="btn-group btn-group-sm" role="group">
        <button type="button"
                class="btn btn-outline-secondary btn-icon btn-edit-category"
                data-id="{{ $category->id }}"
                data-label="{{ $category->label }}"
                data-bs-toggle="modal"
                data-bs-target="#editRiderCategoryModal">
          <i class="ti ti-edit"></i>
        </button>
        @if(!$category->is_system)
          <form method="POST"
                action="{{ route('settings-panel.rider-settings.destroy-category', $category->id) }}"
                class="d-inline"
                onsubmit="return confirm('Delete this category? Custom fields in this category must be moved or deleted first.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-icon">
              <i class="ti ti-trash"></i>
            </button>
          </form>
        @endif
      </div>
      @if($category->is_system)
        <span class="text-muted small ms-1">(system)</span>
      @endif
    </td>
  </tr>
@empty
  <tr>
    <td colspan="5" class="text-center text-muted py-4">No categories yet.</td>
  </tr>
@endforelse
