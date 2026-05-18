{{-- Edit Cheque Status modal --}}
<div class="modal fade" id="editchequeStatusModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit Cheque Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditchequeStatus">
        <input type="hidden" name="id" id="editchequeStatusId">
        @csrf
        @method('PUT')
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label" for="editchequeStatusName">Status Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="editchequeStatusName" class="form-control" maxlength="255" required>
          </div>
          <div class="mb-3">
            <input type="hidden" name="show_in_top_bar" value="0">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="show_in_top_bar" id="editchequeStatusTopBar" value="1">
              <label class="form-check-label" for="editchequeStatusTopBar">Show in Top Bar</label>
            </div>
          </div>
          <div class="mb-0">
            <input type="hidden" name="show_in_view_cards" value="0">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="show_in_view_cards" id="editchequeStatusViewCard" value="1">
              <label class="form-check-label" for="editchequeStatusViewCard">Show in View Card</label>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

