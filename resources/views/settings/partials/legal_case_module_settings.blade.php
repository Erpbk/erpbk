          <div class="tab-pane fade" id="tab-legal-case-status-management" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
              @can('legal_case_create')
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createLegalCaseStatusModal">
                <i class="ti ti-plus me-1"></i> Add New Status
              </button>
              @endcan
            </div>
            <div class="table-responsive">
              @include('legal_case_statuses.table', [
              'legalCaseStatuses' => $legalCaseStatuses ?? collect(),
              'legalCaseRoute' => 'settings-panel.legal-case-statuses',
              'embeddedLegalCaseStatusManager' => true,
              'visaStatusReturnTo' => $legalCaseStatusSettingsReturnUrl
              ])
            </div>
          </div>

          <div class="tab-pane fade" id="tab-legal-case-top" role="tabpanel">
            <div class="card border-0 shadow-none">
              <div class="card-body px-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                  <p class="text-muted small mb-0">Default category is <strong>Legal Case Top Status</strong>. Add only the options you want on Legal Case top cards.</p>
                </div>
                <form id="legalCaseTopAjaxForm" method="POST" action="{{ route('settings-panel.module-settings.update-legal-case-top', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'module' => 'legal_case']) }}">
                  @csrf
                  <div class="accordion" id="legalCaseTopAccordion">
                    <div class="accordion-item">
                      <h2 class="accordion-header" id="legalCaseTopHeading">
                        <div class="d-flex align-items-center gap-2 p-2">
                          <button class="accordion-button py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#legalCaseTopCollapse" aria-expanded="true" aria-controls="legalCaseTopCollapse">
                            <span>Legal Case Top Status</span>
                            <span class="badge bg-label-primary ms-2" id="legalCaseTopSelectedCount">{{ count((array)($selectedLegalCaseTopStatusIds ?? [])) }}</span>
                          </button>
                          <div class="module-top-visibility-controls">
                            <div class="form-check form-switch mb-0">
                              <input class="form-check-input module-top-visibility-toggle" type="checkbox" id="legalCaseTopEnabled" data-field="show_in_top_bar" {{ (!empty($legalCaseTopEnabled) ? 'checked' : '') }}>
                              <label class="form-check-label text-nowrap" for="legalCaseTopEnabled">Top Bar</label>
                            </div>
                          </div>
                        </div>
                      </h2>
                      <div id="legalCaseTopCollapse" class="accordion-collapse collapse show" aria-labelledby="legalCaseTopHeading" data-bs-parent="#legalCaseTopAccordion">
                        <div class="accordion-body">
                          <div class="d-flex justify-content-end mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-legal-case-top-option" data-bs-toggle="modal" data-bs-target="#addLegalCaseTopOptionModal">
                              <i class="ti ti-plus me-1"></i> Add Status
                            </button>
                          </div>
                          @php
                          $selectedIds = collect((array)($selectedLegalCaseTopStatusIds ?? []))->map(fn($id)=>(int)$id)->all();
                          $selectedStatuses = collect($legalCaseStatuses ?? collect())
                          ->filter(fn($s) => in_array((int)$s->id, $selectedIds, true))
                          ->sortBy(fn($s) => array_search((int)$s->id, $selectedIds, true))
                          ->values();
                          @endphp
                          <ul class="list-group list-group-flush" id="legalCaseTopSelectedList">
                            @forelse($selectedStatuses as $status)
                            <li class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between" data-selected-id="{{ (int)$status->id }}">
                              <div class="d-flex align-items-center">
                                <span class="legal-case-top-drag-handle me-2 text-muted" title="Drag to sort" style="cursor: grab;">
                                  <i class="ti ti-grip-vertical"></i>
                                </span>
                                <i class="ti ti-point-filled me-1 text-muted"></i>
                                <span>{{ $status->name }}</span>
                                <input type="hidden" name="status_ids[]" value="{{ (int)$status->id }}">
                              </div>
                              <div class="d-flex align-items-center gap-1">
                                @canany(['legal_case_create', 'legal_case_delete'])
                                <button type="button" class="btn btn-xs btn-outline-danger js-remove-legal-case-top-option" data-remove-id="{{ (int)$status->id }}" title="Remove option">
                                  <i class="ti ti-trash"></i>
                                </button>
                                @endcanany
                              </div>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted" id="legalCaseTopNoOptions">No options added yet.</li>
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

          <div class="modal fade" id="addLegalCaseTopOptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Add Legal Case Status Option</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="form-group">
                    <label class="form-label">Legal Case Status</label>
                    <select id="legalCaseTopStatusSelect" class="form-select select2">
                      <option value="">Select</option>
                      @foreach(($legalCaseStatuses ?? collect()) as $status)
                      <option value="{{ (int)$status->id }}" data-name="{{ $status->name }}">{{ $status->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-primary" id="btnAddLegalCaseTopOption">Add Option</button>
                </div>
              </div>
            </div>
          </div>

          <div class="modal fade" id="createLegalCaseStatusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <form method="POST" action="{{ route('settings-panel.legal-case-statuses.store', ['company_slug' => request()->route('company_slug') ?? session('company_slug')]) }}">
                  @csrf
                  <input type="hidden" name="return_to" value="{{ $legalCaseStatusSettingsReturnUrl }}">
                  <div class="modal-header">
                    <h5 class="modal-title">Create Legal Case Status</h5>
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
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-control" min="1">
                      </div>
                      <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" maxlength="500"></textarea>
                      </div>
                      <div class="col-12 d-flex gap-4">
                        <div class="form-check">
                          <input type="checkbox" name="is_active" id="create_lc_is_active" class="form-check-input" value="1" checked>
                          <label class="form-check-label" for="create_lc_is_active">Active</label>
                        </div>
                        <div class="form-check">
                          <input type="checkbox" name="is_required" id="create_lc_is_required" class="form-check-input" value="1">
                          <label class="form-check-label" for="create_lc_is_required">Required</label>
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

          <div class="modal fade" id="editLegalCaseStatusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <form id="editLegalCaseStatusForm" method="POST" action="#">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="return_to" value="{{ $legalCaseStatusSettingsReturnUrl }}">
                  <div class="modal-header">
                    <h5 class="modal-title">Update Legal Case Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" id="editLegalCaseStatusName" class="form-control" required maxlength="255">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" id="editLegalCaseStatusCode" class="form-control" maxlength="20">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category" id="editLegalCaseStatusCategory" class="form-select">
                          <option value="Document">Document</option>
                          <option value="Permit">Permit</option>
                          <option value="License">License</option>
                          <option value="Insurance">Insurance</option>
                          <option value="Other">Other</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" id="editLegalCaseStatusDisplayOrder" class="form-control" min="1">
                      </div>
                      <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="editLegalCaseStatusDescription" class="form-control" rows="3" maxlength="500"></textarea>
                      </div>
                      <div class="col-12 d-flex gap-4">
                        <div class="form-check">
                          <input type="checkbox" name="is_active" id="editLegalCaseStatusIsActive" class="form-check-input" value="1">
                          <label class="form-check-label" for="editLegalCaseStatusIsActive">Active</label>
                        </div>
                        <div class="form-check">
                          <input type="checkbox" name="is_required" id="editLegalCaseStatusIsRequired" class="form-check-input" value="1">
                          <label class="form-check-label" for="editLegalCaseStatusIsRequired">Required</label>
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
            id="legal-case-status-manager-config"
            data-edit-url-template="{{ route('settings-panel.legal-case-statuses.update', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'legal_case_status' => '__ID__']) }}"