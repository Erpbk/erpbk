<form id="formajax" action="{{ route('riders.dropdown-options.store', ['company_slug' => request()->route('company_slug')]) }}" method="POST">
  @csrf
  <input type="hidden" name="reload" value="1">
  <input type="hidden" name="field_key" value="{{ $fieldKey ?? '' }}">
  <input type="hidden" name="custom_field_id" value="{{ $customFieldId ?? '' }}">
  <div class="modal-body">
    <div class="mb-2 text-muted small">{{ $fieldLabel ?? 'Field' }} - add new option</div>
    <div class="mb-3">
      <label class="form-label">Option value <span class="text-danger">*</span></label>
      <input type="text" class="form-control" name="option_value" required maxlength="255" autofocus>
    </div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-primary save_rec">Save</button>
    <button type="button" class="btn btn-primary loader" style="display:none;" disabled>
      <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
      Saving...
    </button>
  </div>
</form>

