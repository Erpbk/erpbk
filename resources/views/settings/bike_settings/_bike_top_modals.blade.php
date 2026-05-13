@php
$bikeTopSelectableColumns = $bikeTopSelectableColumns ?? [];
@endphp

<div class="modal fade" id="addBikeTopCategoryModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Vehicle Top Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddBikeTopCategory">
        @csrf
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Vehicle column <span class="text-danger">*</span></label>
            <select name="bike_column" id="addBikeTopCategoryColumn" class="form-select select2" data-placeholder="Select column" required>
              <option value="">Select column</option>
              @foreach($bikeTopSelectableColumns as $columnKey => $columnLabel)
              <option value="{{ $columnKey }}">{{ $columnLabel }} ({{ $columnKey }})</option>
              @endforeach
            </select>
            <div class="form-text">Distinct values in this column become pick options. Some columns are excluded from this list.</div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addBikeTopCategorySubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="addBikeTopOptionModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Vehicle Top Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddBikeTopOption">
        @csrf
        <input type="hidden" name="category_id" id="addBikeTopOptionCategoryId">
        <div class="modal-body pt-0">
          <div class="mb-2 text-muted small">
            Category: <strong id="addBikeTopOptionCategoryName">-</strong>
          </div>
          <div class="mb-2 text-muted small">
            Source column: <strong id="addBikeTopOptionColumnName">-</strong>
          </div>
          <div class="mb-3">
            <label class="form-label d-block">Selection mode</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="selection_mode" id="bikeTopOptionModeSingle" value="single" checked>
                <label class="form-check-label" for="bikeTopOptionModeSingle">Single select</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="selection_mode" id="bikeTopOptionModeMultiple" value="multiple">
                <label class="form-check-label" for="bikeTopOptionModeMultiple">Multiple select</label>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Option value(s) <span class="text-danger">*</span></label>
            <div id="addBikeTopOptionRows" class="d-flex flex-column gap-2"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addBikeTopOptionRowBtn">Add option</button>
            <div class="form-text">Options match the bike form for this column: related tables (riders, branches, leasing companies, etc.), central dropdown keys, or lines configured in Bike Settings. Values stored on the vehicle are the underlying IDs or codes; labels are shown here for easier selection.</div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addBikeTopOptionSubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editBikeTopCategoryModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit Vehicle Top Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditBikeTopCategory">
        @csrf
        <input type="hidden" name="id" id="editBikeTopCategoryId">
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Category name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="editBikeTopCategoryName" class="form-control" maxlength="255" required>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="editBikeTopCategorySubmitBtn">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editBikeTopOptionModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit Vehicle Top Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditBikeTopOption">
        @csrf
        <input type="hidden" name="id" id="editBikeTopOptionId">
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Option name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="editBikeTopOptionName" class="form-control" maxlength="255" required>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="editBikeTopOptionSubmitBtn">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
