@php
  $topBarTabLabel = $topBarTabLabel ?? 'Module Top';
  $topBarColumnField = $topBarColumnField ?? 'db_column';
  $topBarColumnLabel = $topBarColumnLabel ?? 'Database Column';
  $topBarSelectableColumns = $topBarSelectableColumns ?? [];
@endphp

{{-- Add Top Category modal --}}
<div class="modal fade" id="addRiderTopCategoryModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add {{ $topBarTabLabel }} Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddRiderTopCategory">
        @csrf
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">{{ $topBarColumnLabel }} <span class="text-danger">*</span></label>
            <select name="{{ $topBarColumnField }}" id="addRiderTopCategoryColumn" class="form-select select2" data-placeholder="Select column" required>
              <option value="">Select column</option>
              @foreach($topBarSelectableColumns as $columnKey => $columnLabel)
              <option value="{{ $columnKey }}">{{ $columnLabel }} ({{ $columnKey }})</option>
              @endforeach
            </select>
            <div class="form-text">Choose the database column that supplies values for this category's options.</div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addRiderTopCategorySubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add Top Option modal --}}
<div class="modal fade" id="addRiderTopOptionModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add {{ $topBarTabLabel }} Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddRiderTopOption">
        @csrf
        <input type="hidden" name="category_id" id="addRiderTopOptionCategoryId">
        <div class="modal-body pt-0">
          <div class="mb-2 text-muted small">
            Category: <strong id="addRiderTopOptionCategoryName">-</strong>
          </div>
          <div class="mb-2 text-muted small">
            Source Column: <strong id="addRiderTopOptionColumnName">-</strong>
          </div>
          <div class="mb-3">
            <label class="form-label d-block">Selection Mode</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="selection_mode" id="riderTopOptionModeSingle" value="single" checked>
                <label class="form-check-label" for="riderTopOptionModeSingle">Single Select</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="selection_mode" id="riderTopOptionModeMultiple" value="multiple">
                <label class="form-check-label" for="riderTopOptionModeMultiple">Multiple Select</label>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Add Option Value(s) <span class="text-danger">*</span></label>
            <div id="addRiderTopOptionRows" class="d-flex flex-column gap-2"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addRiderTopOptionRowBtn">Add Option</button>
            <div class="form-text">Values are loaded from the selected category column. You can add one or more items.</div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addRiderTopOptionSubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Top Category modal --}}
<div class="modal fade" id="editRiderTopCategoryModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit {{ $topBarTabLabel }} Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditRiderTopCategory">
        @csrf
        <input type="hidden" name="id" id="editRiderTopCategoryId">
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Category Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="editRiderTopCategoryName" class="form-control" maxlength="255" required>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="editRiderTopCategorySubmitBtn">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Top Option modal --}}
<div class="modal fade" id="editRiderTopOptionModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit {{ $topBarTabLabel }} Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditRiderTopOption">
        @csrf
        <input type="hidden" name="id" id="editRiderTopOptionId">
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Option Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="editRiderTopOptionName" class="form-control" maxlength="255" required>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="editRiderTopOptionSubmitBtn">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
