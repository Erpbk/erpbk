@php
$chequeTopSelectableColumns = $chequeTopSelectableColumns ?? [];
@endphp

<div class="modal fade" id="addChequeTopCategoryModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Cheque Top Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formaddChequeTopCategory">
        @csrf
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Cheque column <span class="text-danger">*</span></label>
            <select name="cheque_column" id="addChequeTopCategoryColumn" class="form-select select2" data-placeholder="Select column" required>
              <option value="">Select column</option>
              @foreach($chequeTopSelectableColumns as $columnKey => $columnLabel)
              <option value="{{ $columnKey }}">{{ $columnLabel }} ({{ $columnKey }})</option>
              @endforeach
            </select>
            <div class="form-text">Distinct values in this column become pick options. Some columns are excluded from this list.</div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addChequeTopCategorySubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="addChequeTopOptionModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Cheque Top Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formaddChequeTopOption">
        @csrf
        <input type="hidden" name="category_id" id="addChequeTopOptionCategoryId">
        <div class="modal-body pt-0">
          <div class="mb-2 text-muted small">
            Category: <strong id="addChequeTopOptionCategoryName">-</strong>
          </div>
          <div class="mb-2 text-muted small">
            Source column: <strong id="addChequeTopOptionColumnName">-</strong>
          </div>
          <div class="mb-3">
            <label class="form-label d-block">Selection mode</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="selection_mode" id="chequeTopOptionModeSingle" value="single" checked>
                <label class="form-check-label" for="chequeTopOptionModeSingle">Single select</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="selection_mode" id="chequeTopOptionModeMultiple" value="multiple">
                <label class="form-check-label" for="chequeTopOptionModeMultiple">Multiple select</label>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Option value(s) <span class="text-danger">*</span></label>
            <div id="addChequeTopOptionRows" class="d-flex flex-column gap-2"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addChequeTopOptionRowBtn">Add option</button>
            <div class="form-text">Options match the cheque form for this column: related tables (riders, branches, leasing companies, etc.), central dropdown keys, or lines configured in cheque Settings. Values stored on the Cheque are the underlying IDs or codes; labels are shown here for easier selection.</div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addChequeTopOptionSubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editChequeTopCategoryModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit Cheque Top Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formeditChequeTopCategory">
        @csrf
        <input type="hidden" name="id" id="editChequeTopCategoryId">
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Category name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="editChequeTopCategoryName" class="form-control" maxlength="255" required>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="editChequeTopCategorySubmitBtn">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editChequeTopOptionModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit Cheque Top Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formeditChequeTopOption">
        @csrf
        <input type="hidden" name="id" id="editChequeTopOptionId">
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Option name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="editChequeTopOptionName" class="form-control" maxlength="255" required>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="editChequeTopOptionSubmitBtn">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>


