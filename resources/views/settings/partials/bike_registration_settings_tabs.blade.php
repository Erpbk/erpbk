@php
$companySlug = request()->route('company_slug') ?? session('company_slug');
@endphp

<div class="tab-pane fade" id="tab-br-statuses" role="tabpanel">
  <p class="text-muted">Create registration statuses, default fees, and active flags (same pattern as Visa Expense statuses).</p>
  <a href="{{ route('settings-panel.bike-registration-statuses.index', ['company_slug' => $companySlug]) }}" class="btn btn-primary btn-sm mb-3">
    <i class="ti ti-list-details me-1"></i> Open registration status manager
  </a>
  <div class="table-responsive border rounded p-2 bg-light">
    @include('bike_registration_statuses.table', [
    'bikeRegistrationStatuses' => $bikeRegistrationStatusesForSettings ?? collect(),
    'bikeRegistrationRoute' => 'settings-panel.bike-registration-statuses',
    ])
  </div>
</div>

<div class="tab-pane fade" id="tab-bike-registration-top" role="tabpanel">
  <div class="card border-0 shadow-none">
    <div class="card-body px-0">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <p class="text-muted small mb-0">Default category is <strong>Bike Registration Top Status</strong>. Add only the options you want on Bike Registration account listing top cards.</p>
      </div>
      <form id="bikeRegistrationTopAjaxForm" method="POST" action="{{ route('settings-panel.module-settings.update-bike-registration-top', ['company_slug' => $companySlug, 'module' => 'bike_registration']) }}">
        @csrf
        <div class="accordion" id="bikeRegistrationTopAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header" id="bikeRegistrationTopHeading">
              <div class="d-flex align-items-center gap-2 p-2">
                <button class="accordion-button py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#bikeRegistrationTopCollapse" aria-expanded="true" aria-controls="bikeRegistrationTopCollapse">
                  <span>Bike Registration Top Status</span>
                  <span class="badge bg-label-primary ms-2" id="bikeRegistrationTopSelectedCount">{{ count((array)($selectedBikeRegistrationTopStatusIds ?? [])) }}</span>
                </button>
                <div class="form-check form-switch mb-0" style="display: inline-flex; align-items: center; gap: 0.4rem;padding: 0.35rem 0.6rem;">
                  <input style="width: 2rem; height: 1.1rem; margin: 0; cursor: pointer;" class="form-check-input rider-top-visibility-toggle" type="checkbox" id="bikeRegistrationTopEnabled" data-field="show_in_top_bar" {{ (!empty($bikeRegistrationTopEnabled) ? 'checked' : '') }}>
                  <label style="font-size: 0.78rem; font-weight: 500; color: #5f6b7a; margin-top: 0; cursor: pointer;" class="form-check-label text-nowrap" for="bikeRegistrationTopEnabled">Top Bar</label>
                </div>
              </div>
            </h2>
            <div id="bikeRegistrationTopCollapse" class="accordion-collapse collapse show" aria-labelledby="bikeRegistrationTopHeading" data-bs-parent="#bikeRegistrationTopAccordion">
              <div class="accordion-body">
                <div class="d-flex justify-content-end mb-2">
                  <button type="button" class="btn btn-sm btn-outline-primary btn-add-bike-registration-top-option" data-bs-toggle="modal" data-bs-target="#addBikeRegistrationTopOptionModal">
                    <i class="ti ti-plus me-1"></i> Add Status
                  </button>
                </div>
                @php
                $selectedBrTopIds = collect((array)($selectedBikeRegistrationTopStatusIds ?? []))->map(fn ($id) => (int) $id)->all();
                $selectedBikeRegistrationTopStatuses = collect($bikeRegistrationStatusesForSettings ?? collect())
                ->filter(fn ($s) => in_array((int) $s->id, $selectedBrTopIds, true))
                ->sortBy(fn ($s) => array_search((int) $s->id, $selectedBrTopIds, true))
                ->values();
                @endphp
                <ul class="list-group list-group-flush" id="bikeRegistrationTopSelectedList">
                  @forelse($selectedBikeRegistrationTopStatuses as $status)
                  <li class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between" data-selected-id="{{ (int) $status->id }}">
                    <div class="d-flex align-items-center">
                      <span class="bike-registration-top-drag-handle me-2 text-muted" title="Drag to sort" style="cursor: grab;">
                        <i class="ti ti-grip-vertical"></i>
                      </span>
                      <i class="ti ti-point-filled me-1 text-muted"></i>
                      <span>{{ $status->name }}</span>
                      <input type="hidden" name="status_ids[]" value="{{ (int) $status->id }}">
                    </div>
                    <div class="d-flex align-items-center gap-1">
                      <button type="button" class="btn btn-xs btn-outline-danger js-remove-bike-registration-top-option" data-remove-id="{{ (int) $status->id }}" title="Remove option">
                        <i class="ti ti-trash"></i>
                      </button>
                    </div>
                  </li>
                  @empty
                  <li class="list-group-item px-0 py-2 text-muted" id="bikeRegistrationTopNoOptions">No options added yet.</li>
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

<div class="modal fade" id="addBikeRegistrationTopOptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Registration Status Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Registration status</label>
          <select id="bikeRegistrationTopStatusSelect" class="form-select br-registration-top-modal-select" name="bike_registration_top_status_pick">
            <option value="">Select</option>
            @foreach(($bikeRegistrationStatusesForSettings ?? collect()) as $status)
            <option value="{{ (int) $status->id }}" data-name="{{ $status->name }}">{{ $status->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnAddBikeRegistrationTopOption">Add Option</button>
      </div>
    </div>
  </div>
</div>