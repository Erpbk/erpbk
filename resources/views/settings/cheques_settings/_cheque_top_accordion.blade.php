<div class="accordion" id="chequeTopAccordion">
  @forelse($chequeTopCategories as $category)
  <div class="accordion-item">
    <h2 class="accordion-header" id="chequeTopHeading{{ $category->id }}">
      <div class="d-flex align-items-center gap-2 p-2">
        <button class="accordion-button collapsed py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#chequeTopCollapse{{ $category->id }}" aria-expanded="false" aria-controls="chequeTopCollapse{{ $category->id }}">
          <span>{{ $category->name }}</span>
          <span class="badge bg-label-primary ms-2">{{ $category->options->count() }}</span>
        </button>
        <div class="d-flex align-items-center gap-1 pe-1">
          <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-cheque-top-category" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}" title="Edit category">
            <i class="ti ti-pencil"></i>
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger btn-delete-cheque-top-category" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}" title="Delete category">
            <i class="ti ti-trash"></i>
          </button>
        </div>
        <div class="d-flex align-items-center gap-2 pe-2 cheque-top-visibility-controls" data-category-id="{{ $category->id }}">
          <div class="form-check form-switch mb-0">
            <input class="form-check-input cheque-top-visibility-toggle" type="checkbox" id="chequeTopBar{{ $category->id }}" data-field="show_in_top_bar" {{ ($category->show_in_top_bar ?? true) ? 'checked' : '' }}>
            <label class="form-check-label text-nowrap" for="chequeTopBar{{ $category->id }}">Top Bar</label>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input cheque-top-visibility-toggle" type="checkbox" id="chequeTopView{{ $category->id }}" data-field="show_in_view_cards" {{ ($category->show_in_view_cards ?? false) ? 'checked' : '' }}>
            <label class="form-check-label text-nowrap" for="chequeTopView{{ $category->id }}">View Cards</label>
          </div>
        </div>
      </div>
    </h2>
    <div id="chequeTopCollapse{{ $category->id }}" class="accordion-collapse collapse" aria-labelledby="chequeTopHeading{{ $category->id }}" data-bs-parent="#chequeTopAccordion">
      <div class="accordion-body">
        <div class="d-flex justify-content-end mb-2">
          <button type="button" class="btn btn-sm btn-outline-primary btn-add-cheque-top-option" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}" data-bs-toggle="modal" data-bs-target="#addChequeTopOptionModal">
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
              <button type="button" class="btn btn-xs btn-outline-secondary btn-edit-cheque-top-option" data-option-id="{{ $option->id }}" data-option-name="{{ $option->name }}" title="Edit option">
                <i class="ti ti-pencil"></i>
              </button>
              <button type="button" class="btn btn-xs btn-outline-danger btn-delete-cheque-top-option" data-option-id="{{ $option->id }}" data-option-name="{{ $option->name }}" title="Delete option">
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
  <div class="text-muted small">No cheque Top categories yet. Add your first category to begin.</div>
  @endforelse
</div>

