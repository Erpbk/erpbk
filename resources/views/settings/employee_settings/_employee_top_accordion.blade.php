<div class="accordion" id="employeeTopAccordion">
  @forelse($employeeTopCategories as $category)
  <div class="accordion-item">
    <h2 class="accordion-header" id="employeeTopHeading{{ $category->id }}">
      <div class="d-flex align-items-center gap-2 p-2">
        <button class="accordion-button collapsed py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#employeeTopCollapse{{ $category->id }}" aria-expanded="false" aria-controls="employeeTopCollapse{{ $category->id }}">
          <span>{{ $category->name }}</span>
          <span class="badge bg-label-primary ms-2">{{ $category->options->count() }}</span>
        </button>
        <div class="d-flex align-items-center gap-1 pe-1">
          <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-employee-top-category" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}" title="Edit category">
            <i class="ti ti-pencil"></i>
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger btn-delete-employee-top-category" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}" title="Delete category">
            <i class="ti ti-trash"></i>
          </button>
        </div>
        <div class="d-flex align-items-center gap-2 pe-2 employee-top-visibility-controls" data-category-id="{{ $category->id }}">
          <div class="form-check form-switch mb-0">
            <input class="form-check-input employee-top-visibility-toggle" type="checkbox" id="employeeTopBar{{ $category->id }}" data-field="show_in_top_bar" {{ ($category->show_in_top_bar ?? true) ? 'checked' : '' }}>
            <label class="form-check-label text-nowrap" for="employeeTopBar{{ $category->id }}">Top Bar</label>
          </div>
          <div class="form-check form-switch mb-0">
            <input class="form-check-input employee-top-visibility-toggle" type="checkbox" id="employeeTopView{{ $category->id }}" data-field="show_in_view_cards" {{ ($category->show_in_view_cards ?? false) ? 'checked' : '' }}>
            <label class="form-check-label text-nowrap" for="employeeTopView{{ $category->id }}">View Cards</label>
          </div>
        </div>
      </div>
    </h2>
    <div id="employeeTopCollapse{{ $category->id }}" class="accordion-collapse collapse" aria-labelledby="employeeTopHeading{{ $category->id }}" data-bs-parent="#employeeTopAccordion">
      <div class="accordion-body">
        <div class="d-flex justify-content-end mb-2">
          <button type="button" class="btn btn-sm btn-outline-primary btn-add-employee-top-option" data-category-id="{{ $category->id }}" data-category-name="{{ $category->name }}" data-bs-toggle="modal" data-bs-target="#addEmployeeTopOptionModal">
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
              <button type="button" class="btn btn-xs btn-outline-secondary btn-edit-employee-top-option" data-option-id="{{ $option->id }}" data-option-name="{{ $option->name }}" title="Edit option">
                <i class="ti ti-pencil"></i>
              </button>
              <button type="button" class="btn btn-xs btn-outline-danger btn-delete-employee-top-option" data-option-id="{{ $option->id }}" data-option-name="{{ $option->name }}" title="Delete option">
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