<div class="accordion" id="riderTopAccordion">
  @forelse($riderTopCategories as $category)
  <div class="accordion-item">
    <h2 class="accordion-header" id="riderTopHeading{{ $category->id }}">
      <div class="d-flex align-items-center gap-2 p-2">
        <button class="accordion-button collapsed py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#riderTopCollapse{{ $category->id }}" aria-expanded="false" aria-controls="riderTopCollapse{{ $category->id }}">
          <span>{{ $category->name }}</span>
          <span class="badge bg-label-primary ms-2">{{ $category->options->count() }}</span>
        </button>
        <div class="d-flex align-items-center gap-1 pe-1">
          <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-rider-top-category" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}" title="Edit category">
            <i class="ti ti-pencil"></i>
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger btn-delete-rider-top-category" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}" title="Delete category">
            <i class="ti ti-trash"></i>
          </button>
        </div>
        <div class="d-flex align-items-center gap-2 pe-2 rider-top-visibility-controls" data-category-id="{{ $category->id }}">
          <div class="form-check form-switch mb-0">
            <input class="form-check-input rider-top-visibility-toggle" type="checkbox" id="riderTopBar{{ $category->id }}" data-field="show_in_top_bar" {{ ($category->show_in_top_bar ?? true) ? 'checked' : '' }}>
            <label class="form-check-label text-nowrap" for="riderTopBar{{ $category->id }}">Top Bar</label>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input rider-top-visibility-toggle" type="checkbox" id="riderTopView{{ $category->id }}" data-field="show_in_view_cards" {{ ($category->show_in_view_cards ?? false) ? 'checked' : '' }}>
            <label class="form-check-label text-nowrap" for="riderTopView{{ $category->id }}">View Cards</label>
          </div>
        </div>
      </div>
    </h2>
    <div id="riderTopCollapse{{ $category->id }}" class="accordion-collapse collapse" aria-labelledby="riderTopHeading{{ $category->id }}" data-bs-parent="#riderTopAccordion">
      <div class="accordion-body">
        <div class="d-flex justify-content-end mb-2">
          <button type="button" class="btn btn-sm btn-outline-primary btn-add-rider-top-option" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}" data-bs-toggle="modal" data-bs-target="#addRiderTopOptionModal">
            <i class="ti ti-plus me-1"></i> Add Option
          </button>
        </div>
        @if($category->options->isEmpty())
        <p class="text-muted mb-0">No options added yet.</p>
        @else
        <ul class="list-group list-group-flush">
          @foreach($category->options as $option)
          <li class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
              <i class="ti ti-point-filled me-1 text-muted"></i>
              <span>{{ $option->name }}</span>
            </div>
            <div class="d-flex align-items-center gap-1">
              <button type="button" class="btn btn-xs btn-outline-secondary btn-edit-rider-top-option" data-option-id="{{ $option->id }}" data-option-name="{{ $option->name }}" title="Edit option">
                <i class="ti ti-pencil"></i>
              </button>
              <button type="button" class="btn btn-xs btn-outline-danger btn-delete-rider-top-option" data-option-id="{{ $option->id }}" data-option-name="{{ $option->name }}" title="Delete option">
                <i class="ti ti-trash"></i>
              </button>
            </div>
          </li>
          @endforeach
        </ul>
        @endif
      </div>
    </div>
  </div>
  @empty
  <div class="text-muted small">No Rider Top categories yet. Add your first category to begin.</div>
  @endforelse
</div>
