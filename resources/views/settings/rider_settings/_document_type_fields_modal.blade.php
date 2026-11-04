{{-- Manage subtypes & fields for a document type --}}
<div class="modal fade" id="manageRiderDocumentTypeFieldsModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">
          Subtypes &amp; Fields
          <small class="text-muted ms-1" id="manageDocTypeTitle"></small>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-2">
        <input type="hidden" id="manageDocTypeId" value="">
        <div class="row g-3">
          <div class="col-lg-5">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="mb-0">Subtypes</h6>
              <button type="button" class="btn btn-sm btn-primary" id="btnAddDocSubtype">
                <i class="ti ti-plus me-1"></i>Add
              </button>
            </div>
            <div class="table-responsive border rounded">
              <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 28px;"></th>
                    <th>Label</th>
                    <th>Key</th>
                    <th>Fields</th>
                    <th class="text-end" style="width: 90px;"></th>
                  </tr>
                </thead>
                <tbody id="docSubtypesTbody"></tbody>
              </table>
            </div>
            <p class="text-muted small mt-2 mb-0">Use edit, delete, or drag to reorder sides. Related fields for this document are listed on the right.</p>
          </div>
          <div class="col-lg-7">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="mb-0">Fields <span class="text-muted fw-normal" id="manageSubtypeTitle"></span></h6>
              <button type="button" class="btn btn-sm btn-primary" id="btnAddDocField" disabled>
                <i class="ti ti-plus me-1"></i>Add field
              </button>
            </div>
            <div class="table-responsive border rounded">
              <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 28px;"></th>
                    <th>Label</th>
                    <th>Side</th>
                    <th>Type</th>
                    <th>Required</th>
                    <th>Rider column</th>
                    <th class="text-end" style="width: 90px;"></th>
                  </tr>
                </thead>
                <tbody id="docFieldsTbody">
                  <tr><td colspan="7" class="text-center text-muted py-4">No fields yet</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- Add/Edit subtype modal --}}
<div class="modal fade" id="editDocSubtypeModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" id="editDocSubtypeModalTitle">Add subtype</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditDocSubtype">
        <input type="hidden" id="editDocSubtypeId" value="">
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Key <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="editDocSubtypeKey" pattern="[a-z0-9_]+" maxlength="80" required placeholder="e.g. front">
          </div>
          <div class="mb-3">
            <label class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="editDocSubtypeLabel" maxlength="255" required placeholder="e.g. Emirates ID Front">
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="editDocSubtypeActive" value="1" checked>
            <label class="form-check-label" for="editDocSubtypeActive">Active</label>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add/Edit field modal --}}
<div class="modal fade" id="editDocFieldModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" id="editDocFieldModalTitle">Add field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditDocField">
        <input type="hidden" id="editDocFieldId" value="">
        <input type="hidden" id="editDocFieldSubtypeId" value="">
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Subtype / side <span class="text-danger">*</span></label>
            <select class="form-select" id="editDocFieldSubtypeSelect" required></select>
          </div>
          <div class="mb-3">
            <label class="form-label">Key <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="editDocFieldKey" pattern="[a-z0-9_]+" maxlength="80" required placeholder="e.g. emirates_id">
          </div>
          <div class="mb-3">
            <label class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="editDocFieldLabel" maxlength="255" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Input type <span class="text-danger">*</span></label>
            <select class="form-select" id="editDocFieldInputType" required>
              <option value="text">Text</option>
              <option value="textarea">Textarea</option>
              <option value="number">Number</option>
              <option value="date">Date</option>
              <option value="select">Select</option>
            </select>
          </div>
          <div class="mb-3" id="editDocFieldOptionsWrap" style="display:none;">
            <label class="form-label">Options (one per line)</label>
            <textarea class="form-control" id="editDocFieldOptions" rows="3" placeholder="Option A&#10;Option B"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Link to rider column</label>
            <select class="form-select" id="editDocFieldRiderColumn">
              <option value="">— None —</option>
            </select>
            <div class="form-text">Only linked fields sync to the rider profile on upload/edit.</div>
          </div>
          <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" id="editDocFieldRequired" value="1">
            <label class="form-check-label" for="editDocFieldRequired">Required</label>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="editDocFieldActive" value="1" checked>
            <label class="form-check-label" for="editDocFieldActive">Active</label>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
