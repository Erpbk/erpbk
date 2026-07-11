          <div class="tab-pane fade" id="tab-license-status-management" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
              @can('license_expense_create')
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createLicenseExpenseStatusModal">
                <i class="ti ti-plus me-1"></i> Add New Status
              </button>
              @endcan
            </div>
            <div class="table-responsive">
              @include('license_statuses.table', [
              'licenseStatuses' => $licenseStatuses ?? collect(),
              'licenseRoute' => 'settings-panel.license-statuses',
              'embeddedLicenseStatusManager' => true,
              'licenseStatusReturnTo' => $licenseExpenseStatusSettingsReturnUrl
              ])
            </div>
          </div>

          <div class="tab-pane fade" id="tab-license-top" role="tabpanel">
            <div class="card border-0 shadow-none">
              <div class="card-body px-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                  <p class="text-muted small mb-0">Default category is <strong>License Expense Top Status</strong>. Add only the options you want on License Expense top cards.</p>
                </div>
                <form id="licenseExpenseTopAjaxForm" method="POST" action="{{ route('settings-panel.module-settings.update-license-top', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'module' => 'license_expense']) }}">
                  @csrf
                  <div class="accordion" id="licenseExpenseTopAccordion">
                    <div class="accordion-item">
                      <h2 class="accordion-header" id="licenseExpenseTopHeading">
                        <div class="d-flex align-items-center gap-2 p-2">
                          <button class="accordion-button py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#licenseExpenseTopCollapse" aria-expanded="true" aria-controls="licenseExpenseTopCollapse">
                            <span>License Expense Top Status</span>
                            <span class="badge bg-label-primary ms-2" id="licenseExpenseTopSelectedCount">{{ count((array)($selectedLicenseExpenseTopStatusIds ?? [])) }}</span>
                          </button>
                          <div class="module-top-visibility-controls">
                            <div class="form-check form-switch mb-0">
                              <input class="form-check-input module-top-visibility-toggle" type="checkbox" id="licenseExpenseTopEnabled" data-field="show_in_top_bar" {{ (!empty($licenseExpenseTopEnabled) ? 'checked' : '') }}>
                              <label class="form-check-label text-nowrap" for="licenseExpenseTopEnabled">Top Bar</label>
                            </div>
                          </div>
                        </div>
                      </h2>
                      <div id="licenseExpenseTopCollapse" class="accordion-collapse collapse show" aria-labelledby="licenseExpenseTopHeading" data-bs-parent="#licenseExpenseTopAccordion">
                        <div class="accordion-body">
                          <div class="d-flex justify-content-end mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-license-top-option" data-bs-toggle="modal" data-bs-target="#addLicenseExpenseTopOptionModal">
                              <i class="ti ti-plus me-1"></i> Add Status
                            </button>
                          </div>
                          @php
                          $selectedIds = collect((array)($selectedLicenseExpenseTopStatusIds ?? []))->map(fn($id)=>(int)$id)->all();
                          $selectedStatuses = collect($licenseStatuses ?? collect())
                          ->filter(fn($s) => in_array((int)$s->id, $selectedIds, true))
                          ->sortBy(fn($s) => array_search((int)$s->id, $selectedIds, true))
                          ->values();
                          @endphp
                          <ul class="list-group list-group-flush" id="licenseExpenseTopSelectedList">
                            @forelse($selectedStatuses as $status)
                            <li class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between" data-selected-id="{{ (int)$status->id }}">
                              <div class="d-flex align-items-center">
                                <span class="license-top-drag-handle me-2 text-muted" title="Drag to sort" style="cursor: grab;">
                                  <i class="ti ti-grip-vertical"></i>
                                </span>
                                <i class="ti ti-point-filled me-1 text-muted"></i>
                                <span>{{ $status->name }}</span>
                                <input type="hidden" name="status_ids[]" value="{{ (int)$status->id }}">
                              </div>
                              <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-xs btn-outline-danger js-remove-license-top-option" data-remove-id="{{ (int)$status->id }}" title="Remove option">
                                  <i class="ti ti-trash"></i>
                                </button>
                              </div>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted" id="licenseExpenseTopNoOptions">No options added yet.</li>
                            @endforelse
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="modal fade" id="addLicenseExpenseTopOptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Add License Expense Status Option</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="form-group">
                    <label class="form-label">License Expense Status</label>
                    <select id="licenseExpenseTopStatusSelect" class="form-select select2">
                      <option value="">Select</option>
                      @foreach(($licenseStatuses ?? collect()) as $status)
                      <option value="{{ (int)$status->id }}" data-name="{{ $status->name }}">{{ $status->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-primary" id="btnAddLicenseExpenseTopOption">Add Option</button>
                </div>
              </div>
            </div>
          </div>

          <div class="modal fade" id="createLicenseExpenseStatusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <form method="POST" action="{{ route('settings-panel.license-statuses.store', ['company_slug' => request()->route('company_slug') ?? session('company_slug')]) }}">
                  @csrf
                  <input type="hidden" name="return_to" value="{{ $licenseExpenseStatusSettingsReturnUrl }}">
                  <div class="modal-header">
                    <h5 class="modal-title">Create License Expense Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" class="form-control" required maxlength="255">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" maxlength="20">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                          <option value="Document">Document</option>
                          <option value="Permit">Permit</option>
                          <option value="License">License</option>
                          <option value="Insurance">Insurance</option>
                          <option value="Other" selected>Other</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Default Fee (AED)</label>
                        <input type="number" name="default_fee" class="form-control" min="0" step="0.01">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-control" min="1">
                      </div>
                      <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" maxlength="500"></textarea>
                      </div>
                      <div class="col-12 d-flex gap-4">
                        <div class="form-check">
                          <input type="checkbox" name="is_active" id="create_le_is_active" class="form-check-input" value="1" checked>
                          <label class="form-check-label" for="create_le_is_active">Active</label>
                        </div>
                        <div class="form-check">
                          <input type="checkbox" name="is_required" id="create_le_is_required" class="form-check-input" value="1">
                          <label class="form-check-label" for="create_le_is_required">Required</label>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="modal fade" id="editLicenseExpenseStatusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <form id="editLicenseExpenseStatusForm" method="POST" action="#">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="return_to" value="{{ $licenseExpenseStatusSettingsReturnUrl }}">
                  <div class="modal-header">
                    <h5 class="modal-title">Update License Expense Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" id="editLicenseExpenseStatusName" class="form-control" required maxlength="255">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" id="editLicenseExpenseStatusCode" class="form-control" maxlength="20">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category" id="editLicenseExpenseStatusCategory" class="form-select">
                          <option value="Document">Document</option>
                          <option value="Permit">Permit</option>
                          <option value="License">License</option>
                          <option value="Insurance">Insurance</option>
                          <option value="Other">Other</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Default Fee (AED)</label>
                        <input type="number" name="default_fee" id="editLicenseExpenseStatusDefaultFee" class="form-control" min="0" step="0.01">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" id="editLicenseExpenseStatusDisplayOrder" class="form-control" min="1">
                      </div>
                      <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="editLicenseExpenseStatusDescription" class="form-control" rows="3" maxlength="500"></textarea>
                      </div>
                      <div class="col-12 d-flex gap-4">
                        <div class="form-check">
                          <input type="checkbox" name="is_active" id="editLicenseExpenseStatusIsActive" class="form-check-input" value="1">
                          <label class="form-check-label" for="editLicenseExpenseStatusIsActive">Active</label>
                        </div>
                        <div class="form-check">
                          <input type="checkbox" name="is_required" id="editLicenseExpenseStatusIsRequired" class="form-check-input" value="1">
                          <label class="form-check-label" for="editLicenseExpenseStatusIsRequired">Required</label>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <div
            id="license-status-manager-config"
            data-edit-url-template="{{ route('settings-panel.license-statuses.update', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'license_status' => '__ID__']) }}"
            hidden></div>
