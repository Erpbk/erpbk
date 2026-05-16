@extends('layouts.settingsPanelLayout')

@section('title', $pageTitle ?? 'Bike Settings – Site Settings')

@section('content')
@include('flash::message')

<style>
  /* Keep Select2 dropdown above Bootstrap modal/backdrop in this page. */
  .select2-container--open {
    z-index: 99999 !important;
  }

  #addVisaExpenseTopOptionModal+.select2-container--open,
  .modal .select2-container--open {
    z-index: 99999 !important;
  }

  .modal .select2-dropdown,
  .modal .select2-results {
    z-index: 99999 !important;
  }

  #addVisaExpenseTopOptionModal .modal-content,
  #addVisaExpenseTopOptionModal .modal-body {
    overflow: visible !important;
  }
  @if(!empty($showBikeRegistrationExtras) && $showBikeRegistrationExtras)
  .select2-container--open.br-registration-top-select2-wrap {
    z-index: 1060;
  }
  @endif

  .bike-top-visibility-controls {
    background: #f8f9fb;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 0.35rem 0.6rem;
  }

  .bike-top-visibility-controls .form-check {
    min-height: auto;
    margin-bottom: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
  }

  .bike-top-visibility-controls .form-check-input {
    width: 2rem;
    height: 1.1rem;
    margin: 0;
    cursor: pointer;
  }

  .bike-top-visibility-controls .form-check-label {
    font-size: 0.78rem;
    font-weight: 500;
    color: #5f6b7a;
    margin-top: 0;
    cursor: pointer;
  }
</style>

@php
$activeCategoryId = (int) (request()->query('active_category_id', 0));
$showAssignFieldsTab = request()->query('tab') === 'assign-fields';
$showBikeFieldsMainTab = request()->query->has('active_category_id') && !$showAssignFieldsTab;
$settingsRoutePrefix = $settingsRoutePrefix ?? 'settings-panel.bike-settings';
$settingsRouteParams = $settingsRouteParams ?? [];
$settingsHeading = $settingsHeading ?? 'Bike Settings';
$settingsFieldsTabLabel = $settingsFieldsTabLabel ?? 'Bike Fields';
$settingsEntityName = $settingsEntityName ?? 'bike';
$fixedFieldSourceTable = $fixedFieldSourceTable ?? 'bike_field_category_assignments';
$customFieldSourceTable = $customFieldSourceTable ?? 'bike_custom_fields';
$isRiderInvoicesModule = in_array(($moduleKey ?? ''), ['invoices', 'customer_invoices'], true);
$riderInvoiceAccountTree = $riderInvoiceAccountTree ?? [];
$riderInvoiceAssignments = $riderInvoiceAssignments ?? ['debit' => [], 'credit' => []];
$canManageAccountAssigning = auth()->check() && auth()->user()->hasAnyRole(['admin', 'Administrator', 'Super Admin']);
$moduleSchemaFieldKeys = $moduleSchemaFieldKeys ?? [];
$showVisaStatusManagementTab = ($moduleKey ?? '') === 'visa_expense';
$visaStatusSettingsReturnUrl = route('settings-panel.module-settings.index', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'module' => 'visa_expense']) . '#tab-visa-status-management';
$showBikeRegistrationExtras = !empty($showBikeRegistrationExtras);
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h4 class="card-title mb-0">{{ $settingsHeading }}</h4>
          <p class="text-muted small mb-0 mt-1">
            @if($showBikeRegistrationExtras)
              Configure registration statuses, top bar cards, fixed/custom fields on <code>bike_registrations</code>, categories, and document types.
            @elseif(($moduleKey ?? '') === 'bike_list')
              Configure Vehicle Top cards for the Vehicles module, fixed/custom fields, categories, and document types.
            @else
              Configure fixed/custom fields and document types.
            @endif
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="bikeSettingsMainTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($showBikeFieldsMainTab || $showAssignFieldsTab) ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
              General
            </button>
          </li>
          @if($showBikeRegistrationExtras)
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-br-statuses" type="button" role="tab">
              Registration statuses
            </button>
          </li>
          @endif
          @if($showVisaStatusManagementTab)
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-visa-status-management" type="button" role="tab">
              Visa Status Management
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-visa-expense-top" type="button" role="tab">
              Visa Expense Top
            </button>
          </li>
          @endif
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">
              Categories
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $showBikeFieldsMainTab ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-bike-fields" type="button" role="tab">
              {{ $settingsFieldsTabLabel }}
            </button>
          </li>
          @if(($moduleKey ?? '') === 'bike_list')
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $showAssignFieldsTab ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-bike-assign-fields" type="button" role="tab" id="tab-bike-assign-fields-btn">
              Bike Assigning Fields
            </button>
          </li>
          @endif
          @if(($moduleKey ?? '') === 'sims')
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $showAssignFieldsTab ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-sim-assign-fields" type="button" role="tab" id="tab-sim-assign-fields-btn">
              SIM Assigning Fields
            </button>
          </li>
          @endif
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button" role="tab">
              Documents
            </button>
          </li>
          @if(($moduleKey ?? '') === 'bike_list')
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-vehicle-top" type="button" role="tab" id="tab-vehicle-top-btn">
              Vehicle top
            </button>
          </li>
          @endif
          @if($showBikeRegistrationExtras)
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bike-registration-top" type="button" role="tab" id="tab-bike-registration-top-btn">
              Top bar
            </button>
          </li>
          @endif
        </ul>

        <div class="tab-content">
          {{-- Tab: General --}}
          <div class="tab-pane fade {{ ($showBikeFieldsMainTab || $showAssignFieldsTab) ? '' : 'show active' }}" id="tab-general" role="tabpanel">
            <form action="{{ route($settingsRoutePrefix . '.store-module-label', $settingsRouteParams) }}" method="POST" class="row g-3 align-items-end">
              @csrf
              <div class="col-md-6">
                <label class="form-label">Name in menu</label>
                <input type="text" name="module_label" class="form-control"
                  value="{{ old('module_label', $moduleLabel ?? $settingsHeading) }}"
                  placeholder="{{ $settingsHeading }}" maxlength="100" required>
              </div>
              <div class="col-md-6 text-end">
                <button class="btn btn-primary" type="submit">Save name</button>
              </div>
            </form>
          </div>

          @if($showBikeRegistrationExtras)
            @include('settings.partials.bike_registration_settings_tabs')
          @endif

          @if($showVisaStatusManagementTab)
          <div class="tab-pane fade" id="tab-visa-status-management" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
              @can('visaexpense_create')
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createVisaStatusModal">
                <i class="ti ti-plus me-1"></i> Add New Status
              </button>
              @endcan
            </div>
            <div class="table-responsive">
              @include('visa_statuses.table', [
              'visaStatuses' => $visaStatuses ?? collect(),
              'visaRoute' => 'settings-panel.visa-statuses',
              'embeddedVisaStatusManager' => true,
              'visaStatusReturnTo' => $visaStatusSettingsReturnUrl
              ])
            </div>
          </div>

          <div class="tab-pane fade" id="tab-visa-expense-top" role="tabpanel">
            <div class="card border-0 shadow-none">
              <div class="card-body px-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                  <p class="text-muted small mb-0">Default category is <strong>Visa Expense Top Status</strong>. Add only the options you want on Visa Expense top cards.</p>
                </div>
                <form id="visaExpenseTopAjaxForm" method="POST" action="{{ route('settings-panel.module-settings.update-visa-expense-top', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'module' => 'visa_expense']) }}">
                  @csrf
                  <div class="accordion" id="visaExpenseTopAccordion">
                    <div class="accordion-item">
                      <h2 class="accordion-header" id="visaExpenseTopHeading">
                        <div class="d-flex align-items-center gap-2 p-2">
                          <button class="accordion-button py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#visaExpenseTopCollapse" aria-expanded="true" aria-controls="visaExpenseTopCollapse">
                            <span>Visa Expense Top Status</span>
                            <span class="badge bg-label-primary ms-2" id="visaExpenseTopSelectedCount">{{ count((array)($selectedVisaExpenseTopStatusIds ?? [])) }}</span>
                          </button>
                          <div class="form-check form-switch mb-0" style="display: inline-flex; align-items: center; gap: 0.4rem;padding: 0.35rem 0.6rem;">
                            <input style="width: 2rem; height: 1.1rem; margin: 0; cursor: pointer;" class="form-check-input rider-top-visibility-toggle" type="checkbox" id="visaExpenseTopEnabled" data-field="show_in_top_bar" {{ (!empty($visaExpenseTopEnabled) ? 'checked' : '') }}>
                            <label style="font-size: 0.78rem; font-weight: 500; color: #5f6b7a; margin-top: 0; cursor: pointer;" class="form-check-label text-nowrap" for="visaExpenseTopEnabled">Top Bar</label>
                          </div>
                        </div>
                      </h2>
                      <div id="visaExpenseTopCollapse" class="accordion-collapse collapse show" aria-labelledby="visaExpenseTopHeading" data-bs-parent="#visaExpenseTopAccordion">
                        <div class="accordion-body">
                          <div class="d-flex justify-content-end mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-visa-expense-top-option" data-bs-toggle="modal" data-bs-target="#addVisaExpenseTopOptionModal">
                              <i class="ti ti-plus me-1"></i> Add Status
                            </button>
                          </div>
                          @php
                          $selectedIds = collect((array)($selectedVisaExpenseTopStatusIds ?? []))->map(fn($id)=>(int)$id)->all();
                          $selectedStatuses = collect($visaStatuses ?? collect())
                          ->filter(fn($s) => in_array((int)$s->id, $selectedIds, true))
                          ->sortBy(fn($s) => array_search((int)$s->id, $selectedIds, true))
                          ->values();
                          @endphp
                          <ul class="list-group list-group-flush" id="visaExpenseTopSelectedList">
                            @forelse($selectedStatuses as $status)
                            <li class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between" data-selected-id="{{ (int)$status->id }}">
                              <div class="d-flex align-items-center">
                                <span class="visa-expense-top-drag-handle me-2 text-muted" title="Drag to sort" style="cursor: grab;">
                                  <i class="ti ti-grip-vertical"></i>
                                </span>
                                <i class="ti ti-point-filled me-1 text-muted"></i>
                                <span>{{ $status->name }}</span>
                                <input type="hidden" name="status_ids[]" value="{{ (int)$status->id }}">
                              </div>
                              <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-xs btn-outline-danger js-remove-visa-expense-top-option" data-remove-id="{{ (int)$status->id }}" title="Remove option">
                                  <i class="ti ti-trash"></i>
                                </button>
                              </div>
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-2 text-muted" id="visaExpenseTopNoOptions">No options added yet.</li>
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

          <div class="modal fade" id="addVisaExpenseTopOptionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Add Visa Status Option</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="form-group">
                    <label class="form-label">Visa Status</label>
                    <select id="visaExpenseTopStatusSelect" class="form-select select2">
                      <option value="">Select</option>
                      @foreach(($visaStatuses ?? collect()) as $status)
                      <option value="{{ (int)$status->id }}" data-name="{{ $status->name }}">{{ $status->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-primary" id="btnAddVisaExpenseTopOption">Add Option</button>
                </div>
              </div>
            </div>
          </div>

          <div class="modal fade" id="createVisaStatusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <form method="POST" action="{{ route('settings-panel.visa-statuses.store', ['company_slug' => request()->route('company_slug') ?? session('company_slug')]) }}">
                  @csrf
                  <input type="hidden" name="return_to" value="{{ $visaStatusSettingsReturnUrl }}">
                  <div class="modal-header">
                    <h5 class="modal-title">Create Visa Status</h5>
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
                        <label class="form-label">Default Fee</label>
                        <input type="number" name="default_fee" class="form-control" min="0" step="0.01" value="0.00">
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
                          <input type="checkbox" name="is_active" id="create_visa_is_active" class="form-check-input" value="1" checked>
                          <label class="form-check-label" for="create_visa_is_active">Active</label>
                        </div>
                        <div class="form-check">
                          <input type="checkbox" name="is_required" id="create_visa_is_required" class="form-check-input" value="1">
                          <label class="form-check-label" for="create_visa_is_required">Required</label>
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

          <div class="modal fade" id="editVisaStatusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <form id="editVisaStatusForm" method="POST" action="#">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="return_to" value="{{ $visaStatusSettingsReturnUrl }}">
                  <div class="modal-header">
                    <h5 class="modal-title">Update Visa Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" id="editVisaStatusName" class="form-control" required maxlength="255">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" id="editVisaStatusCode" class="form-control" maxlength="20">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category" id="editVisaStatusCategory" class="form-select">
                          <option value="Document">Document</option>
                          <option value="Permit">Permit</option>
                          <option value="License">License</option>
                          <option value="Insurance">Insurance</option>
                          <option value="Other">Other</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Default Fee</label>
                        <input type="number" name="default_fee" id="editVisaStatusDefaultFee" class="form-control" min="0" step="0.01">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" id="editVisaStatusDisplayOrder" class="form-control" min="1">
                      </div>
                      <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="editVisaStatusDescription" class="form-control" rows="3" maxlength="500"></textarea>
                      </div>
                      <div class="col-12 d-flex gap-4">
                        <div class="form-check">
                          <input type="checkbox" name="is_active" id="editVisaStatusIsActive" class="form-check-input" value="1">
                          <label class="form-check-label" for="editVisaStatusIsActive">Active</label>
                        </div>
                        <div class="form-check">
                          <input type="checkbox" name="is_required" id="editVisaStatusIsRequired" class="form-check-input" value="1">
                          <label class="form-check-label" for="editVisaStatusIsRequired">Required</label>
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
            id="visa-status-manager-config"
            data-edit-url-template="{{ route('settings-panel.visa-statuses.update', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'visa_status' => '__ID__']) }}"
            hidden></div>
          @endif

          {{-- Tab: Categories --}}
          <div class="tab-pane fade" id="tab-categories" role="tabpanel">
            <div class="card mb-4">
              <div class="card-body">
                <form action="{{ route($settingsRoutePrefix . '.store-category', $settingsRouteParams) }}" method="POST" class="row g-3 align-items-end">
                  @csrf
                  <div class="col-md-8">
                    <label class="form-label">New category label</label>
                    <input type="text" name="label" class="form-control" required maxlength="255" placeholder="e.g. Safety / Location / etc.">
                  </div>
                  <div class="col-md-4 text-end">
                    <button class="btn btn-primary" type="submit">Add category</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead>
                  <tr>
                    <th style="width: 35%;">Label</th>
                    <th style="width: 15%;">System</th>
                    <th style="width: 50%;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($categories as $cat)
                  <tr data-category-row-id="{{ $cat->id }}">
                    <td><span class="js-category-label">{{ $cat->label }}</span></td>
                    <td>{!! $cat->is_system ? '<span class="badge bg-secondary">Yes</span>' : '<span class="badge bg-light text-dark border">No</span>' !!}</td>
                    <td>
                      @if(!$cat->is_system)
                      <form action="{{ route($settingsRoutePrefix . '.update-category', array_merge($settingsRouteParams, ['id' => $cat->id])) }}" method="POST" class="d-inline-flex gap-2 align-items-center js-ajax-category-update-form" data-category-id="{{ $cat->id }}">
                        @csrf
                        @method('PUT')
                        <input type="text" name="label" value="{{ $cat->label }}" required maxlength="255" class="form-control form-control-sm" style="max-width: 260px">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="ti ti-pencil"></i></button>
                      </form>

                      <form action="{{ route($settingsRoutePrefix . '.destroy-category', array_merge($settingsRouteParams, ['id' => $cat->id])) }}" method="POST" class="d-inline ms-2 js-ajax-category-delete-form" data-category-id="{{ $cat->id }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit"><i class="ti ti-trash"></i></button>
                      </form>
                      @else
                      <span class="text-muted">Not editable</span>
                      @endif
                    </td>
                  </tr>
                  @endforeach
                  @if($categories->isEmpty())
                  <tr>
                    <td colspan="3" class="text-center text-muted py-3">No categories configured.</td>
                  </tr>
                  @endif
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tab: Bike Fields --}}
          <div class="tab-pane fade {{ $showBikeFieldsMainTab ? 'show active' : '' }}" id="tab-bike-fields" role="tabpanel">
            <div class="d-flex justify-content-end align-items-center mb-3">
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBikeFieldModal">
                <i class="ti ti-plus me-1"></i> Add Custom Field
              </button>
            </div>

            <div class="modal fade" id="addBikeFieldModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                  <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Add New Bike Field</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>

                  <form id="formAddBikeField" action="{{ route($settingsRoutePrefix . '.store-field', $settingsRouteParams) }}" method="POST">
                    @csrf
                    <div class="modal-body pt-0">
                      <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                          <label class="form-label">Label</label>
                          <input type="text" name="label" class="form-control" required maxlength="255">
                        </div>

                        <div class="col-md-3">
                          <label class="form-label">Data Type</label>
                          <select name="data_type" class="form-select" required>
                            @foreach($dataTypes as $typeKey => $spec)
                            <option value="{{ $typeKey }}">{{ $spec['label'] }}</option>
                            @endforeach
                          </select>
                        </div>

                        <div class="col-md-3">
                          <label class="form-label">Category</label>
                          <select name="category_id" class="form-select">
                            <option value="">Unassigned</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->label }}</option>
                            @endforeach
                          </select>
                        </div>

                        <div class="col-md-2">
                          <div class="form-check mt-4">
                            <input type="hidden" name="is_mandatory" value="0">
                            <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" id="bikeFieldIsMandatoryModal">
                            <label class="form-check-label" for="bikeFieldIsMandatoryModal">Mandatory</label>
                          </div>
                        </div>

                        <div class="col-md-12">
                          <label class="form-label">Help Text</label>
                          <input type="text" name="help_text" class="form-control" maxlength="1000">
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Default Value</label>
                          <input type="text" name="default_value" class="form-control" maxlength="500">
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Dropdown Options (one per line)</label>
                          <input type="hidden" name="config_options" id="addBikeFieldConfigOptionsHidden" value="">
                          <div id="addBikeFieldOptionsRows" class="d-flex flex-column gap-2"></div>
                          <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addBikeFieldOptionRowBtn">Add Option</button>
                        </div>
                      </div>
                    </div>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button class="btn btn-primary" type="submit">Add custom field</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <p class="text-muted small mb-0">
                Fixed fields come from <code>{{ $fixedFieldSourceTable }}</code>. Custom fields come from <code>{{ $customFieldSourceTable }}</code>.
                <b>All Fields</b> automatically lists this module's database fields, and category tabs show only this module's own assignments.
              </p>
            </div>

            <ul class="nav nav-tabs nav-tabs-rider-fields mb-3" id="bikeFieldsCategoryTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeCategoryId === 0 ? 'active' : '' }}"
                  id="bike-fields-all-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#bike-fields-all-pane"
                  type="button"
                  role="tab">
                  All Fields
                  <span class="badge bg-label-primary ms-1">{{ count($fixedAssignments ?? []) + count($customFields ?? []) }}</span>
                </button>
              </li>

              @foreach($categories as $cat)
              @php
              $fixedCount = count($fixedAssignmentsByCategory[$cat->id] ?? collect());
              $customCount = count(($customFieldsByCategory[$cat->id] ?? collect()));
              $tabActive = $activeCategoryId === (int)$cat->id;
              @endphp
              <li class="nav-item" role="presentation">
                <button class="nav-link {{ $tabActive ? 'active' : '' }}"
                  id="bike-cat-{{ $cat->id }}-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#bike-field-cat-{{ $cat->id }}"
                  type="button"
                  role="tab">
                  {{ $cat->label }}
                  <span class="badge bg-label-info ms-1">{{ $fixedCount + $customCount }}</span>
                </button>
              </li>
              @endforeach
            </ul>

            <div class="tab-content">
              {{-- All Fields --}}
              <div class="tab-pane fade {{ $activeCategoryId === 0 ? 'show active' : '' }}"
                id="bike-fields-all-pane" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-hover bike-settings-table mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 48px;" class="text-center" title="{{ __('Drag to reorder') }}"></th>
                        <th>Field</th>
                        <th>Current category</th>
                        <th class="text-center">Required</th>
                        <th class="text-center">Show in form</th>
                        <th>Move to category</th>
                        <th class="text-end">Actions</th>
                      </tr>
                    </thead>
                    @php
                    $fixedList = $fixedAssignments ?? collect();
                    $fixedOffset = 0;
                    @endphp
                    @if($fixedList->isNotEmpty())
                    <tbody class="bike-fields-all-fixed-sortable-tbody">
                      @foreach($fixedList as $rowIndex => $row)
                      @php
                      $fieldLabel = $row->display_label ? $row->display_label : \App\Models\BikeCustomField::humanizeFieldKey($row->field_key);
                      $categoryLabel = $row->category?->label ?? '';
                      $inputOptions = '';
                      if (is_array($row->input_config ?? null) && isset($row->input_config['options'])) {
                      $inputOptions = (string) $row->input_config['options'];
                      }
                      $isSchemaLocked = in_array($row->field_key, $moduleSchemaFieldKeys, true);
                      @endphp
                      <tr data-bike-field-key="{{ $row->field_key }}">
                        <td class="align-middle"><span class="drag-handle cursor-grab text-muted" title="{{ __('Drag to reorder') }}"><i class="ti ti-grip-vertical"></i></span></td>
                        <td class="align-middle">
                          <span class="fw-semibold">{{ $fieldLabel }}</span>
                          <span class="text-muted ms-1">({{ $row->field_key }})</span>
                          @if($isSchemaLocked)
                          <span class="badge bg-label-secondary ms-1">Database</span>
                          @endif
                        </td>
                        <td class="align-middle">
                          <span class="badge bg-label-info">{{ $categoryLabel }}</span>
                        </td>
                        <td class="align-middle text-center">
                          <div class="form-check form-switch d-inline-block mb-0">
                            <input type="checkbox"
                              class="form-check-input bike-field-required-toggle"
                              data-field-key="{{ $row->field_key }}"
                              data-category-id="{{ $row->category_id }}"
                              data-display-label="{{ $row->display_label }}"
                              data-input-type="{{ $row->input_type }}"
                              data-input-config-options="{{ $inputOptions }}"
                              data-is-visible-current="{{ ($row->is_visible ?? true) ? 1 : 0 }}"
                              {{ ($row->is_required ?? false) ? 'checked' : '' }}
                              title="Require this value when the field is shown on add/edit forms">
                          </div>
                        </td>
                        <td class="align-middle text-center">
                          <div class="form-check form-switch d-inline-block mb-0">
                            <input type="checkbox"
                              class="form-check-input bike-field-visibility-toggle"
                              data-field-key="{{ $row->field_key }}"
                              data-category-id="{{ $row->category_id }}"
                              data-display-label="{{ $row->display_label }}"
                              data-input-type="{{ $row->input_type }}"
                              data-input-config-options="{{ $inputOptions }}"
                              data-is-required-current="{{ ($row->is_required ?? false) ? 1 : 0 }}"
                              {{ ($row->is_visible ?? true) ? 'checked' : '' }}
                              title="Show this field on add/edit forms when checked">
                          </div>
                        </td>
                        <td class="align-middle">
                          <form action="{{ route($settingsRoutePrefix . '.update-field-assignment', $settingsRouteParams) }}" method="POST" class="d-flex gap-2 align-items-center flex-wrap">
                            @csrf
                            <input type="hidden" name="field_key" value="{{ $row->field_key }}">
                            <input type="hidden" name="display_label" value="{{ $row->display_label }}">
                            <input type="hidden" name="is_visible" value="{{ ($row->is_visible ?? true) ? 1 : 0 }}">
                            <input type="hidden" name="is_required" value="{{ ($row->is_required ?? false) ? 1 : 0 }}">
                            <input type="hidden" name="input_type" value="{{ $row->input_type }}">
                            <input type="hidden" name="input_config_options" value="{{ $inputOptions }}">

                            <select name="category_id" class="form-select form-select-sm" style="width: 180px;">
                              <option value="">Keep current</option>
                              @foreach($categories as $dst)
                              <option value="{{ $dst->id }}" {{ (int)$row->category_id === (int)$dst->id ? 'selected' : '' }}>
                                {{ $dst->label }}
                              </option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Move</button>
                          </form>
                        </td>
                        <td class="align-middle text-end">
                          @php
                          $fixedInputOptions = '';
                          if (is_array($row->input_config ?? null) && isset($row->input_config['options'])) {
                          $fixedInputOptions = (string) $row->input_config['options'];
                          }
                          @endphp
                          <button type="button"
                            class="btn btn-sm btn-outline-primary btn-edit-bike-fixed-field"
                            data-bs-toggle="modal"
                            data-bs-target="#editBikeFixedFieldModal"
                            data-field-key="{{ $row->field_key }}"
                            data-field-label="{{ $row->display_label }}"
                            data-is-visible="{{ ($row->is_visible ?? true) ? 1 : 0 }}"
                            data-is-required="{{ ($row->is_required ?? false) ? 1 : 0 }}"
                            data-input-type="{{ $row->input_type ?? 'text' }}"
                            data-input-config-options='@json($fixedInputOptions)'
                            data-category-id="{{ $row->category_id ?? '' }}"
                            data-schema-locked="{{ $isSchemaLocked ? '1' : '0' }}"
                            title="Edit fixed field">
                            <i class="ti ti-pencil"></i>
                          </button>
                        </td>
                      </tr>
                      @endforeach
                    </tbody>
                    @endif

                    @if(($customFields ?? collect())->isNotEmpty())
                    <tbody class="bike-fields-all-custom-sortable-tbody">
                      @foreach(($customFields ?? collect()) as $customIndex => $customField)
                      @php
                      $cat = $customField->category;
                      $catLabel = $cat?->label ?? 'Unassigned';
                      @endphp
                      <tr class="table-light" data-custom-field-id="{{ $customField->id }}">
                        <td class="align-middle"><span class="drag-handle cursor-grab text-muted" title="{{ __('Drag to reorder') }}"><i class="ti ti-grip-vertical"></i></span></td>
                        <td class="align-middle">
                          <span class="fw-semibold">{{ $customField->label }}</span>
                          <span class="badge bg-label-secondary ms-1">Custom</span>
                        </td>
                        <td class="align-middle">
                          @if($cat)
                          <span class="badge bg-label-info">{{ $catLabel }}</span>
                          @else
                          <span class="badge bg-label-warning">Unassigned</span>
                          @endif
                        </td>
                        @include('settings.bike_settings._bike_custom_field_row_flags', ['customField' => $customField])
                        <td class="align-middle">
                          <form action="{{ route($settingsRoutePrefix . '.assign-custom-field-category', array_merge($settingsRouteParams, ['id' => $customField->id])) }}" method="POST" class="d-flex gap-2 align-items-center flex-wrap">
                            @csrf
                            <select name="category_id" class="form-select form-select-sm" style="width: 180px;">
                              <option value="">Unassigned</option>
                              @foreach($categories as $dst)
                              <option value="{{ $dst->id }}" {{ (int)($customField->category_id ?? 0) === (int)$dst->id ? 'selected' : '' }}>
                                {{ $dst->label }}
                              </option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Move</button>
                          </form>
                        </td>
                        <td class="align-middle text-end">
                          @php
                          $customConfigOptions = '';
                          if (is_array($customField->config ?? null) && isset($customField->config['options'])) {
                          $customConfigOptions = (string) $customField->config['options'];
                          }
                          @endphp
                          <button type="button"
                            class="btn btn-sm btn-outline-primary btn-edit-bike-custom-field"
                            data-bs-toggle="modal"
                            data-bs-target="#editBikeCustomFieldModal"
                            data-field-id="{{ $customField->id }}"
                            data-field-label="{{ $customField->label }}"
                            data-help-text="{{ $customField->help_text }}"
                            data-data-type="{{ $customField->data_type }}"
                            data-is-mandatory="{{ $customField->is_mandatory ? 1 : 0 }}"
                            data-is-visible="{{ ($customField->is_visible ?? true) ? 1 : 0 }}"
                            data-default-value="{{ $customField->default_value }}"
                            data-input-format="{{ $customField->input_format }}"
                            data-config-options='@json($customConfigOptions)'
                            data-update-url="{{ route($settingsRoutePrefix . '.update-field', array_merge($settingsRouteParams, ['id' => $customField->id])) }}"
                            data-category-id="{{ $customField->category_id ?? '' }}"
                            title="Edit custom field">
                            <i class="ti ti-pencil"></i>
                          </button>
                          <form action="{{ route($settingsRoutePrefix . '.destroy-field', array_merge($settingsRouteParams, ['id' => $customField->id])) }}"
                            method="POST"
                            class="d-inline ms-1"
                            onsubmit="return confirm('Delete this {{ $settingsEntityName }} custom field?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete custom field">
                              <i class="ti ti-trash"></i>
                            </button>
                          </form>
                        </td>
                      </tr>
                      @endforeach
                    </tbody>
                    @endif
                    @if(($fixedList ?? collect())->isEmpty() && ($customFields ?? collect())->isEmpty())
                    <tbody>
                      <tr>
                        <td colspan="7" class="text-center text-muted py-3">No bike fields configured yet.</td>
                      </tr>
                    </tbody>
                    @endif
                  </table>
                </div>
              </div>

              {{-- Category tabs: fixed + custom --}}
              @foreach($categories as $cat)
              @php
              $fixedRows = $fixedAssignmentsByCategory[$cat->id] ?? collect();
              $customRows = $customFieldsByCategory[$cat->id] ?? collect();
              $isActive = $activeCategoryId === (int)$cat->id;
              @endphp

              <div class="tab-pane fade {{ $isActive ? 'show active' : '' }}"
                id="bike-field-cat-{{ $cat->id }}" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-hover bike-settings-table mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 60px;"></th>
                        <th>Field</th>
                        <th class="text-center">Required</th>
                        <th class="text-center">Show in form</th>
                        <th>Move to category</th>
                        <th class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bike-fields-sortable-tbody" data-category-id="{{ $cat->id }}">
                      @foreach($fixedRows as $rowIndex => $row)
                      @php
                      $fieldLabel = $row->display_label ? $row->display_label : \App\Models\BikeCustomField::humanizeFieldKey($row->field_key);
                      $inputOptions = '';
                      if (is_array($row->input_config ?? null) && isset($row->input_config['options'])) {
                      $inputOptions = (string) $row->input_config['options'];
                      }
                      $isSchemaLocked = in_array($row->field_key, $moduleSchemaFieldKeys, true);
                      @endphp
                      <tr data-bike-field-key="{{ $row->field_key }}">
                        <td class="align-middle"><span class="drag-handle cursor-grab"><i class="ti ti-grip-vertical"></i></span></td>
                        <td class="align-middle">
                          <span class="fw-semibold">{{ $fieldLabel }}</span>
                          <span class="text-muted ms-1">({{ $row->field_key }})</span>
                          @if($isSchemaLocked)
                          <span class="badge bg-label-secondary ms-1">Database</span>
                          @endif
                        </td>
                        <td class="align-middle text-center">
                          <div class="form-check form-switch d-inline-block mb-0">
                            <input type="checkbox"
                              class="form-check-input bike-field-required-toggle"
                              data-field-key="{{ $row->field_key }}"
                              data-category-id="{{ $row->category_id }}"
                              data-display-label="{{ $row->display_label }}"
                              data-input-type="{{ $row->input_type }}"
                              data-input-config-options="{{ $inputOptions }}"
                              data-is-visible-current="{{ ($row->is_visible ?? true) ? 1 : 0 }}"
                              {{ ($row->is_required ?? false) ? 'checked' : '' }}
                              title="Require this value when the field is shown on add/edit forms">
                          </div>
                        </td>
                        <td class="align-middle text-center">
                          <div class="form-check form-switch d-inline-block mb-0">
                            <input type="checkbox"
                              class="form-check-input bike-field-visibility-toggle"
                              data-field-key="{{ $row->field_key }}"
                              data-category-id="{{ $row->category_id }}"
                              data-display-label="{{ $row->display_label }}"
                              data-input-type="{{ $row->input_type }}"
                              data-input-config-options="{{ $inputOptions }}"
                              data-is-required-current="{{ ($row->is_required ?? false) ? 1 : 0 }}"
                              {{ ($row->is_visible ?? true) ? 'checked' : '' }}
                              title="Show this field on add/edit forms when checked">
                          </div>
                        </td>
                        <td class="align-middle">
                          <form action="{{ route($settingsRoutePrefix . '.update-field-assignment', $settingsRouteParams) }}" method="POST" class="d-flex gap-2 align-items-center flex-wrap">
                            @csrf
                            <input type="hidden" name="field_key" value="{{ $row->field_key }}">
                            <input type="hidden" name="display_label" value="{{ $row->display_label }}">
                            <input type="hidden" name="is_visible" value="{{ ($row->is_visible ?? true) ? 1 : 0 }}">
                            <input type="hidden" name="is_required" value="{{ ($row->is_required ?? false) ? 1 : 0 }}">
                            <input type="hidden" name="input_type" value="{{ $row->input_type }}">
                            <input type="hidden" name="input_config_options" value="{{ $inputOptions }}">

                            <select name="category_id" class="form-select form-select-sm" style="width: 180px;">
                              <option value="">Keep current</option>
                              @foreach($categories as $dst)
                              <option value="{{ $dst->id }}" {{ (int)$cat->id === (int)$dst->id ? 'selected' : '' }}>{{ $dst->label }}</option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Move</button>
                          </form>
                        </td>
                        <td class="align-middle text-end">
                          @php
                          $fixedInputOptions = '';
                          if (is_array($row->input_config ?? null) && isset($row->input_config['options'])) {
                          $fixedInputOptions = (string) $row->input_config['options'];
                          }
                          @endphp
                          <button type="button"
                            class="btn btn-sm btn-outline-primary btn-edit-bike-fixed-field"
                            data-bs-toggle="modal"
                            data-bs-target="#editBikeFixedFieldModal"
                            data-field-key="{{ $row->field_key }}"
                            data-field-label="{{ $row->display_label }}"
                            data-is-visible="{{ ($row->is_visible ?? true) ? 1 : 0 }}"
                            data-is-required="{{ ($row->is_required ?? false) ? 1 : 0 }}"
                            data-input-type="{{ $row->input_type ?? 'text' }}"
                            data-input-config-options='@json($fixedInputOptions)'
                            data-category-id="{{ $row->category_id ?? '' }}"
                            data-schema-locked="{{ $isSchemaLocked ? '1' : '0' }}"
                            title="Edit fixed field">
                            <i class="ti ti-pencil"></i>
                          </button>
                        </td>
                      </tr>
                      @endforeach

                      @foreach($customRows as $customIndex => $customField)
                      <tr class="table-light" data-id="{{ $customField->id }}">
                        <td class="align-middle"></td>
                        <td class="align-middle">
                          <span class="fw-semibold">{{ $customField->label }}</span>
                          <span class="badge bg-label-secondary ms-1">Custom</span>
                        </td>
                        @include('settings.bike_settings._bike_custom_field_row_flags', ['customField' => $customField])
                        <td class="align-middle">
                          <form action="{{ route($settingsRoutePrefix . '.assign-custom-field-category', array_merge($settingsRouteParams, ['id' => $customField->id])) }}" method="POST" class="d-flex gap-2 align-items-center flex-wrap">
                            @csrf
                            <select name="category_id" class="form-select form-select-sm" style="width: 180px;">
                              <option value="">Unassigned</option>
                              @foreach($categories as $dst)
                              <option value="{{ $dst->id }}" {{ (int)($customField->category_id ?? 0) === (int)$dst->id ? 'selected' : '' }}>
                                {{ $dst->label }}
                              </option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Move</button>
                          </form>
                        </td>
                        <td class="align-middle text-end">
                          @php
                          $customConfigOptions = '';
                          if (is_array($customField->config ?? null) && isset($customField->config['options'])) {
                          $customConfigOptions = (string) $customField->config['options'];
                          }
                          @endphp
                          <button type="button"
                            class="btn btn-sm btn-outline-primary btn-edit-bike-custom-field"
                            data-bs-toggle="modal"
                            data-bs-target="#editBikeCustomFieldModal"
                            data-field-id="{{ $customField->id }}"
                            data-field-label="{{ $customField->label }}"
                            data-help-text="{{ $customField->help_text }}"
                            data-data-type="{{ $customField->data_type }}"
                            data-is-mandatory="{{ $customField->is_mandatory ? 1 : 0 }}"
                            data-is-visible="{{ ($customField->is_visible ?? true) ? 1 : 0 }}"
                            data-default-value="{{ $customField->default_value }}"
                            data-input-format="{{ $customField->input_format }}"
                            data-config-options='@json($customConfigOptions)'
                            data-update-url="{{ route($settingsRoutePrefix . '.update-field', array_merge($settingsRouteParams, ['id' => $customField->id])) }}"
                            data-category-id="{{ $customField->category_id ?? '' }}"
                            title="Edit custom field">
                            <i class="ti ti-pencil"></i>
                          </button>
                          <form action="{{ route($settingsRoutePrefix . '.destroy-field', array_merge($settingsRouteParams, ['id' => $customField->id])) }}"
                            method="POST"
                            class="d-inline ms-1"
                            onsubmit="return confirm('Delete this {{ $settingsEntityName }} custom field?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete custom field">
                              <i class="ti ti-trash"></i>
                            </button>
                          </form>
                        </td>
                      </tr>
                      @endforeach

                      @if($fixedRows->isEmpty() && $customRows->isEmpty())
                      <tr>
                        <td colspan="6" class="text-center text-muted py-3">No fields in this category.</td>
                      </tr>
                      @endif
                    </tbody>
                  </table>
                </div>
              </div>
              @endforeach
            </div>
          </div>
          {{-- /tab-bike-fields: assign modal fields are configured in the next tab only --}}

          @if(($moduleKey ?? '') === 'bike_list')
          @include('settings.bike_settings._assign_fields_tab')
          @endif

          @if(($moduleKey ?? '') === 'sims')
          @include('settings.sim_settings._assign_fields_tab')
          @endif

          {{-- Edit Bike Fixed Field Modal --}}
          <div class="modal fade" id="editBikeFixedFieldModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                  <h5 class="modal-title">Edit bike fixed field</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="formEditBikeFixedField" action="{{ route($settingsRoutePrefix . '.update-field-assignment', $settingsRouteParams) }}" method="POST">
                  @csrf
                  <div class="modal-body pt-0">
                    <div class="row g-3 align-items-end">
                      <div class="col-md-4">
                        <label class="form-label">Field key</label>
                        <input type="text" name="field_key" id="editBikeFixedFieldKey" class="form-control" readonly>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Display label</label>
                        <input type="text" name="display_label" id="editBikeFixedDisplayLabel" class="form-control">
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="editBikeFixedCategoryId" class="form-select" required>
                          @foreach($categories as $cat)
                          <option value="{{ $cat->id }}">{{ $cat->label }}</option>
                          @endforeach
                        </select>
                      </div>

                      <div class="col-md-4">
                        <div class="form-check mt-4">
                          <input type="hidden" name="is_visible" value="0">
                          <input type="checkbox" name="is_visible" value="1" id="editBikeFixedIsVisible" class="form-check-input">
                          <label class="form-check-label" for="editBikeFixedIsVisible">Visible</label>
                        </div>
                      </div>

                      <div class="col-md-4">
                        <div class="form-check mt-4">
                          <input type="hidden" name="is_required" value="0">
                          <input type="checkbox" name="is_required" value="1" id="editBikeFixedIsRequired" class="form-check-input">
                          <label class="form-check-label" for="editBikeFixedIsRequired">Required</label>
                        </div>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Input type</label>
                        <select name="input_type" id="editBikeFixedInputType" class="form-select">
                          @foreach(['text','textarea','number','decimal','date','datetime','dropdown','checkbox'] as $t)
                          <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                          @endforeach
                        </select>
                      </div>

                      <div class="col-md-12" id="editBikeFixedInputPreviewWrap">
                        <label class="form-label">Preview</label>
                        <div id="editBikeFixedInputPreview"></div>
                      </div>

                      <div class="col-md-12" id="editBikeFixedOptionsWrap">
                        <label class="form-label">Dropdown options (one per line)</label>
                        <input type="hidden" name="input_config_options" id="editBikeFixedInputConfigOptionsHidden" value="">
                        <div id="editBikeFixedOptionsRows" class="d-flex flex-column gap-2"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="editBikeFixedOptionRowBtn">Add Option</button>
                      </div>
                    </div>
                  </div>

                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save fixed field</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          {{-- Edit Bike Custom Field Modal --}}
          <div class="modal fade" id="editBikeCustomFieldModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                  <h5 class="modal-title">Edit bike custom field</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="formEditBikeCustomField" action="#" method="POST">
                  @csrf
                  @method('PUT')

                  <div class="modal-body pt-0">
                    <div class="row g-3 align-items-end">
                      <div class="col-md-6">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" id="editBikeCustomLabel" class="form-control" required maxlength="255">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Help text</label>
                        <input type="text" name="help_text" id="editBikeCustomHelpText" class="form-control" maxlength="1000">
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Data type</label>
                        <select name="data_type" id="editBikeCustomDataType" class="form-select" required>
                          @foreach($dataTypes as $typeKey => $spec)
                          <option value="{{ $typeKey }}">{{ $spec['label'] }}</option>
                          @endforeach
                        </select>
                      </div>

                      <div class="col-md-4">
                        <div class="form-check mt-4">
                          <input type="hidden" name="is_mandatory" value="0">
                          <input type="checkbox" name="is_mandatory" value="1" id="editBikeCustomIsMandatory" class="form-check-input">
                          <label class="form-check-label" for="editBikeCustomIsMandatory">Mandatory</label>
                        </div>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="editBikeCustomCategoryId" class="form-select">
                          <option value="">Unassigned</option>
                          @foreach($categories as $cat)
                          <option value="{{ $cat->id }}">{{ $cat->label }}</option>
                          @endforeach
                        </select>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Default value</label>
                        <input type="text" name="default_value" id="editBikeCustomDefaultValue" class="form-control" maxlength="500">
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Input format</label>
                        <input type="text" name="input_format" id="editBikeCustomInputFormat" class="form-control" maxlength="100">
                      </div>

                      <div class="col-md-12">
                        <label class="form-label">Config options (dropdown: one per line)</label>
                        <input type="hidden" name="config_options" id="editBikeCustomConfigOptionsHidden" value="">
                        <div id="editBikeCustomOptionsRows" class="d-flex flex-column gap-2"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="editBikeCustomOptionRowBtn">Add Option</button>
                      </div>
                    </div>
                  </div>

                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save custom field</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          {{-- Tab: Documents --}}
          <div class="tab-pane fade" id="tab-docs" role="tabpanel">
            <div class="card mb-4">
              <div class="card-body d-flex justify-content-end">
                <button type="button"
                  class="btn btn-primary"
                  data-bs-toggle="modal"
                  data-bs-target="#addModuleDocumentTypeModal">
                  <i class="ti ti-plus me-1"></i>
                  Add document type
                </button>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead>
                  <tr>
                    <th style="width: 40px;"></th>
                    <th style="width: 70px;">#</th>
                    <th>Key</th>
                    <th>Type</th>
                    <th>Labels</th>
                    <th>Status</th>
                    <th class="text-end" style="width: 140px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($documentTypes as $index => $doc)
                  <tr data-id="{{ $doc->id }}">
                    <td class="align-middle">
                      <span class="drag-handle cursor-grab"><i class="ti ti-grip-vertical"></i></span>
                    </td>
                    <td class="align-middle">{{ $index + 1 }}</td>
                    <td class="align-middle"><code>{{ $doc->key }}</code></td>
                    <td class="align-middle">
                      @if($doc->type === 'single')
                      <span class="badge bg-label-info">Single</span>
                      @else
                      <span class="badge bg-label-primary">Dual</span>
                      @endif
                    </td>
                    <td class="align-middle">
                      @if($doc->type === 'single')
                      {{ $doc->label ?: '—' }}
                      @else
                      <span class="text-muted small">Front: {{ $doc->front_label ?? '—' }}</span><br>
                      <span class="text-muted small">Back: {{ $doc->back_label ?? '—' }}</span>
                      @endif
                    </td>
                    <td class="align-middle">
                      @if($doc->is_active)
                      <span class="badge bg-label-success">Active</span>
                      @else
                      <span class="badge bg-label-secondary">Inactive</span>
                      @endif
                    </td>
                    <td class="text-end align-middle">
                      <div class="btn-group btn-group-sm" role="group">
                        <button type="button"
                          class="btn btn-outline-secondary btn-icon btn-edit-module-document-type"
                          data-id="{{ $doc->id }}"
                          data-key="{{ $doc->key }}"
                          data-type="{{ $doc->type }}"
                          data-label="{{ $doc->label }}"
                          data-front-label="{{ $doc->front_label }}"
                          data-back-label="{{ $doc->back_label }}"
                          data-update-url="{{ route($settingsRoutePrefix . '.update-document-type', array_merge($settingsRouteParams, ['id' => $doc->id])) }}"
                          data-bs-toggle="modal"
                          data-bs-target="#editModuleDocumentTypeModal">
                          <i class="ti ti-edit"></i>
                        </button>
                        <form method="POST"
                          action="{{ route($settingsRoutePrefix . '.destroy-document-type', array_merge($settingsRouteParams, ['id' => $doc->id])) }}"
                          class="d-inline"
                          onsubmit="return confirm('Delete this document type?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-outline-danger btn-icon">
                            <i class="ti ti-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="7" class="text-center text-muted py-4">No document types yet. Add one to define required module documents.</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="modal fade" id="editModuleDocumentTypeModal" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form id="formEditModuleDocumentType" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Document Type</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label">Key</label>
                        <input type="text" id="editModuleDocKey" name="key" class="form-control" required maxlength="80">
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select id="editModuleDocType" name="type" class="form-select" required>
                          <option value="single">Single</option>
                          <option value="dual">Dual</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" id="editModuleDocLabel" name="label" class="form-control" maxlength="255">
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Front Label (dual)</label>
                        <input type="text" id="editModuleDocFrontLabel" name="front_label" class="form-control" maxlength="255">
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Back Label (dual)</label>
                        <input type="text" id="editModuleDocBackLabel" name="back_label" class="form-control" maxlength="255">
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <div class="modal fade" id="addModuleDocumentTypeModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Add Document Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <form id="formAddModuleDocumentType">
                    @csrf
                    <div class="modal-body pt-0">
                      <div class="mb-3">
                        <label class="form-label">Key <span class="text-danger">*</span></label>
                        <input type="text" name="key" id="addModuleDocTypeKey" class="form-control" placeholder="e.g. photo, passport" pattern="[a-z0-9_]+" maxlength="80" required>
                        <div class="form-text">Lowercase letters, numbers, underscores. Used to match uploaded file names.</div>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" id="addModuleDocTypeType" class="form-select" required>
                          <option value="single">Single (one file)</option>
                          <option value="dual">Dual (front + back page)</option>
                        </select>
                      </div>
                      <div class="mb-3" id="addModuleDocTypeSingleWrap">
                        <label class="form-label">Label <span class="text-danger">*</span></label>
                        <input type="text" name="label" id="addModuleDocTypeLabel" class="form-control" placeholder="e.g. Profile Photo" maxlength="255">
                      </div>
                      <div id="addModuleDocTypeDualWrap" style="display: none;">
                        <div class="mb-3">
                          <label class="form-label">Front / First page label <span class="text-danger">*</span></label>
                          <input type="text" name="front_label" id="addModuleDocTypeFrontLabel" class="form-control" placeholder="e.g. Passport ( First Page )" maxlength="255">
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Back / Second page label <span class="text-danger">*</span></label>
                          <input type="text" name="back_label" id="addModuleDocTypeBackLabel" class="form-control" placeholder="e.g. Passport ( Second Page )" maxlength="255">
                        </div>
                      </div>
                      <div class="mb-0">
                        <div class="form-check">
                          <input type="checkbox" name="is_active" id="addModuleDocTypeActive" class="form-check-input" value="1" checked>
                          <label class="form-check-label" for="addModuleDocTypeActive">Active</label>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-primary" id="addModuleDocumentTypeSubmitBtn">Save</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>


          </div>

          @if(($moduleKey ?? '') === 'bike_list')
          <div class="tab-pane fade" id="tab-vehicle-top" role="tabpanel">
            @include('settings.bike_settings._vehicle_top_tab_content')
          </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

<style>
  .account-assigning-pane .js-rider-invoice-account-assignment+.select2-container {
    margin-bottom: 0.75rem;
  }

  .account-assigning-pane .account-hier-bullet {
    flex-shrink: 0;
    font-size: 1.05rem;
    line-height: 1.25;
    width: 0.65rem;
    text-align: center;
  }

  .account-assigning-pane .select2-results__group {
    font-weight: 600;
    font-size: 0.95rem;
    padding-top: 0.35rem;
  }

  .account-assigning-pane .select2-results__option--selectable .account-hier-option {
    margin-left: 0.15rem;
  }
</style>
@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
  (function() {
    function showBikeCategoryAjaxMessage(type, message) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: type,
          title: type === 'success' ? 'Success' : 'Error',
          text: message || (type === 'success' ? 'Done.' : 'Request failed.'),
        });
        return;
      }
      alert(message || (type === 'success' ? 'Done.' : 'Request failed.'));
    }

    function submitBikeCategoryAjaxForm(form) {
      var submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;
      var formData = new FormData(form);

      return fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          }
        })
        .then(function(response) {
          return response.json().catch(function() {
            return {};
          }).then(function(data) {
            if (!response.ok || data.success === false) {
              return Promise.reject(data);
            }
            return data;
          });
        })
        .finally(function() {
          if (submitBtn) submitBtn.disabled = false;
        });
    }

    document.addEventListener('submit', function(e) {
      var updateForm = e.target.closest('.js-ajax-category-update-form');
      if (updateForm) {
        e.preventDefault();
        submitBikeCategoryAjaxForm(updateForm)
          .then(function(data) {
            var categoryId = updateForm.dataset.categoryId || '';
            var row = document.querySelector('tr[data-category-row-id="' + categoryId + '"]');
            var labelInput = updateForm.querySelector('input[name="label"]');
            var newLabel = (data && data.category && data.category.label) ? data.category.label : (labelInput ? labelInput.value : '');
            if (row) {
              var labelEl = row.querySelector('.js-category-label');
              if (labelEl) labelEl.textContent = newLabel;
            }
            showBikeCategoryAjaxMessage('success', (data && data.message) ? data.message : 'Category updated.');
          })
          .catch(function(error) {
            showBikeCategoryAjaxMessage('error', (error && error.message) ? error.message : 'Could not update category.');
          });
        return;
      }

      var deleteForm = e.target.closest('.js-ajax-category-delete-form');
      if (!deleteForm) return;
      e.preventDefault();

      var proceed = function() {
        submitBikeCategoryAjaxForm(deleteForm)
          .then(function(data) {
            var categoryId = deleteForm.dataset.categoryId || '';
            var row = document.querySelector('tr[data-category-row-id="' + categoryId + '"]');
            if (row) row.remove();
            showBikeCategoryAjaxMessage('success', (data && data.message) ? data.message : 'Category deleted.');
          })
          .catch(function(error) {
            showBikeCategoryAjaxMessage('error', (error && error.message) ? error.message : 'Could not delete category.');
          });
      };

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning',
          title: 'Delete category?',
          text: 'This action cannot be undone.',
          showCancelButton: true,
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel',
        }).then(function(result) {
          if (result.isConfirmed) proceed();
        });
      } else if (confirm('Delete this category?')) {
        proceed();
      }
    });
  })();

  (function() {
    const meta = document.getElementById('riderInvoiceAssigningMeta');
    const isAccountAssigningAvailable = !!meta && meta.dataset.enabled === 'true';
    if (!isAccountAssigningAvailable) {
      return;
    }

    let initialAssignments = {
      debit: {},
      credit: {},
    };
    try {
      const parsed = JSON.parse(meta.dataset.initialAssignments || '{}');
      if (parsed && typeof parsed === 'object') {
        initialAssignments = {
          debit: (parsed.debit && typeof parsed.debit === 'object') ? parsed.debit : {},
          credit: (parsed.credit && typeof parsed.credit === 'object') ? parsed.credit : {},
        };
      }
    } catch (error) {
      initialAssignments = {
        debit: {},
        credit: {},
      };
    }
    const form = document.getElementById('riderInvoiceAccountAssigningForm');
    if (!form) {
      return;
    }

    const state = {
      debit: {},
      credit: {},
    };

    function hasSelect2() {
      return !!(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2);
    }

    function cleanupSelect2Orphans(element) {
      if (!element || !element.parentNode) return;
      Array.from(element.parentNode.querySelectorAll(':scope > span.select2')).forEach(function(orphan) {
        orphan.remove();
      });
    }

    function initAssignmentSelect2(element) {
      if (!element || !hasSelect2()) {
        return;
      }
      const $el = window.jQuery(element);
      const $tabPane = $el.closest('.tab-pane');
      cleanupSelect2Orphans(element);

      if ($el.data('select2')) {
        $el.select2('destroy');
      }

      const placeholder = element.getAttribute('data-placeholder') || 'Select account';
      $el.select2({
        width: '100%',
        dropdownParent: $tabPane.length ? $tabPane : undefined,
        placeholder: placeholder,
        allowClear: false,
        closeOnSelect: !element.multiple,
        templateResult: function(data) {
          if (!data.id) {
            return data.text;
          }
          const $opt = window.jQuery(data.element);
          if (!$opt.length || !$opt.attr('data-parent-id')) {
            return data.text;
          }
          const isParentOption = String($opt.attr('data-is-parent') || '') === '1';
          if (isParentOption) {
            return data.text;
          }
          const $wrap = window.jQuery('<span class="account-hier-option d-flex align-items-start gap-2 py-1"></span>');
          $wrap.append(window.jQuery('<span class="account-hier-bullet text-muted pt-1" aria-hidden="true">•</span>'));
          $wrap.append(window.jQuery('<span class="account-hier-option-text"></span>').text(data.text));
          return $wrap;
        },
        templateSelection: function(data) {
          return data.text;
        },
      });
    }

    function getParentRows(side) {
      return document.getElementById(side + 'ParentRows');
    }

    function syncHiddenInputs() {
      const debitInput = document.getElementById('debitAssignmentsInput');
      const creditInput = document.getElementById('creditAssignmentsInput');
      if (debitInput) debitInput.value = JSON.stringify(state.debit);
      if (creditInput) creditInput.value = JSON.stringify(state.credit);
    }

    function readRowSelection(select) {
      if (!select || !select.value) {
        return {
          parentId: 0,
          childId: 0
        };
      }
      const opt = select.options[select.selectedIndex];
      const childId = Number(select.value);
      const parentId = opt && opt.getAttribute('data-parent-id') ? Number(opt.getAttribute('data-parent-id')) : 0;
      return {
        parentId: parentId,
        childId: childId
      };
    }

    function applyAssignmentSelectionToState(side, select) {
      const prevParentId = Number(select.dataset.assignedParentId || 0);
      const cur = readRowSelection(select);

      if (prevParentId > 0 && (prevParentId !== cur.parentId || cur.childId === 0)) {
        delete state[side][prevParentId];
      }

      if (cur.parentId > 0 && cur.childId > 0) {
        state[side][cur.parentId] = [cur.childId];
        select.dataset.assignedParentId = String(cur.parentId);
      } else {
        delete select.dataset.assignedParentId;
      }

      syncHiddenInputs();
      refreshDisabledAssignmentOptions(side);
    }

    function refreshDisabledAssignmentOptions(side) {
      const parentRows = getParentRows(side);
      if (!parentRows) return;

      const rows = Array.from(parentRows.querySelectorAll('.account-parent-row'));
      const rowSelections = rows.map(function(row) {
        const sel = row.querySelector('.js-rider-invoice-account-assignment');
        return readRowSelection(sel);
      });

      rows.forEach(function(row, idx) {
        const select = row.querySelector('.js-rider-invoice-account-assignment');
        if (!select) return;

        const parentsUsedElsewhere = new Set();
        const childrenUsedElsewhere = new Set();
        rowSelections.forEach(function(s, j) {
          if (j === idx || s.childId === 0) {
            return;
          }
          parentsUsedElsewhere.add(s.parentId);
          childrenUsedElsewhere.add(s.childId);
        });

        Array.from(select.options).forEach(function(option) {
          if (!option.value) return;
          const p = Number(option.getAttribute('data-parent-id') || 0);
          const c = Number(option.value);
          const conflictParent = parentsUsedElsewhere.has(p);
          const conflictChild = childrenUsedElsewhere.has(c);
          option.disabled = conflictParent || conflictChild;
        });

        if (hasSelect2()) {
          window.jQuery(select).trigger('change.select2');
        }
      });
    }

    function refreshRemoveButtons(side) {
      const parentRows = getParentRows(side);
      if (!parentRows) return;
      const rows = Array.from(parentRows.querySelectorAll('.account-parent-row'));
      rows.forEach(function(row) {
        const removeBtn = row.querySelector('.js-remove-parent-row');
        if (!removeBtn) return;
        removeBtn.classList.toggle('d-none', rows.length <= 1);
      });
    }

    function removeExtraEmptyRows(side) {
      const parentRows = getParentRows(side);
      if (!parentRows) return;
      const rows = Array.from(parentRows.querySelectorAll('.account-parent-row'));
      if (rows.length <= 1) return;

      rows.slice(1).forEach(function(row) {
        const select = row.querySelector('.js-rider-invoice-account-assignment');
        const val = select ? String(select.value || '').trim() : '';
        if (val === '') {
          if (hasSelect2() && select && window.jQuery(select).data('select2')) {
            window.jQuery(select).select2('destroy');
          }
          row.remove();
        }
      });

      refreshRemoveButtons(side);
    }

    function bindAssignmentRow(side, row) {
      const select = row.querySelector('.js-rider-invoice-account-assignment');
      const removeBtn = row.querySelector('.js-remove-parent-row');
      if (!select) return;

      if (hasSelect2()) {
        window.jQuery(select).off('change');
      }

      initAssignmentSelect2(select);

      window.jQuery(select).on('change', function() {
        applyAssignmentSelectionToState(side, select);
      });

      if (removeBtn) {
        removeBtn.addEventListener('click', function() {
          const prevParentId = Number(select.dataset.assignedParentId || 0);
          if (prevParentId > 0) {
            delete state[side][prevParentId];
            syncHiddenInputs();
          }
          if (hasSelect2()) {
            window.jQuery(select).select2('destroy');
          }
          row.remove();
          refreshRemoveButtons(side);
          refreshDisabledAssignmentOptions(side);
        });
      }
    }

    function addAssignmentRow(side) {
      const parentRows = getParentRows(side);
      if (!parentRows) return null;

      const firstRow = parentRows.querySelector('.account-parent-row');
      if (!firstRow) return null;

      const templateSelect = firstRow.querySelector('.js-rider-invoice-account-assignment');
      if (!templateSelect) return null;

      const row = document.createElement('div');
      row.className = 'd-flex align-items-start gap-2 account-parent-row';
      row.innerHTML = '' +
        '<div class="flex-grow-1">' +
        '  <select class="form-select js-rider-invoice-account-assignment" data-side="' + side + '">' +
        templateSelect.innerHTML +
        '  </select>' +
        '</div>' +
        '<button type="button" class="btn btn-outline-danger btn-sm js-remove-parent-row mt-1" title="Remove row">' +
        '  <i class="ti ti-trash"></i>' +
        '</button>';

      const select = row.querySelector('.js-rider-invoice-account-assignment');
      if (!select) return null;

      select.setAttribute('data-placeholder', templateSelect.getAttribute('data-placeholder') || '');

      if (hasSelect2()) {
        const firstSelect = firstRow.querySelector('.js-rider-invoice-account-assignment');
        if (firstSelect && window.jQuery(firstSelect).data('select2')) {
          window.jQuery(firstSelect).select2('destroy');
        }
      }

      select.value = '';
      delete select.dataset.assignedParentId;
      parentRows.appendChild(row);

      bindAssignmentRow(side, row);
      refreshRemoveButtons(side);
      refreshDisabledAssignmentOptions(side);

      if (hasSelect2()) {
        const firstSelect = firstRow.querySelector('.js-rider-invoice-account-assignment');
        if (firstSelect) {
          initAssignmentSelect2(firstSelect);
        }
      }

      return row;
    }

    ['debit', 'credit'].forEach(function(side) {
      const parentRows = getParentRows(side);
      if (!parentRows) return;

      const firstRow = parentRows.querySelector('.account-parent-row');
      if (firstRow) {
        bindAssignmentRow(side, firstRow);
      }

      const addButton = document.querySelector('.js-add-parent-row[data-side="' + side + '"]');
      if (addButton) {
        addButton.addEventListener('click', function() {
          addAssignmentRow(side);
        });
      }

      refreshRemoveButtons(side);
      refreshDisabledAssignmentOptions(side);
    });

    ['debit', 'credit'].forEach(function(side) {
      const sideAssignments = initialAssignments[side] || {};
      const parentIds = Object.keys(sideAssignments).map(Number).filter(function(id) {
        return id > 0;
      });

      if (!parentIds.length) return;

      const parentRows = getParentRows(side);
      if (!parentRows) return;
      const firstRow = parentRows.querySelector('.account-parent-row');
      if (!firstRow) return;

      const firstSelect = firstRow.querySelector('.js-rider-invoice-account-assignment');
      const firstChildId = (sideAssignments[parentIds[0]] || [])[0];
      if (firstSelect && firstChildId) {
        firstSelect.value = String(firstChildId);
        applyAssignmentSelectionToState(side, firstSelect);
        if (hasSelect2()) {
          window.jQuery(firstSelect).trigger('change.select2');
        }
      }

      parentIds.slice(1).forEach(function(parentId) {
        const childId = (sideAssignments[parentId] || [])[0];
        const row = addAssignmentRow(side);
        if (!row || !childId) return;
        const sel = row.querySelector('.js-rider-invoice-account-assignment');
        sel.value = String(childId);
        applyAssignmentSelectionToState(side, sel);
        if (hasSelect2()) {
          window.jQuery(sel).trigger('change.select2');
        }
      });

      refreshDisabledAssignmentOptions(side);
    });

    ['debit', 'credit'].forEach(function(side) {
      removeExtraEmptyRows(side);
    });

    form.addEventListener('submit', function(event) {
      const debitHasSelection = Object.values(state.debit).some(function(children) {
        return Array.isArray(children) && children.length > 0;
      });
      const creditHasSelection = Object.values(state.credit).some(function(children) {
        return Array.isArray(children) && children.length > 0;
      });

      if (!debitHasSelection || !creditHasSelection) {
        event.preventDefault();
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Select at least one child account on both debit and credit sides.',
          });
        }
      }
    });

    document.querySelectorAll('[data-bs-target="#tab-account-assigning"]').forEach(function(tabBtn) {
      tabBtn.addEventListener('shown.bs.tab', function() {
        ['debit', 'credit'].forEach(function(side) {
          const parentRows = getParentRows(side);
          if (!parentRows) return;
          parentRows.querySelectorAll('.js-rider-invoice-account-assignment').forEach(function(sel) {
            initAssignmentSelect2(sel);
          });
          refreshDisabledAssignmentOptions(side);
        });
      });
    });
  })();

  function bikeSafeJsonParse(value, fallback) {
    try {
      if (value === undefined || value === null) return fallback;
      return JSON.parse(value);
    } catch (e) {
      return fallback;
    }
  }

  function bikeParseOptionLines(raw) {
    return String(raw || '')
      .split(/\r?\n/)
      .map(function(s) {
        return s.trim();
      })
      .filter(function(s) {
        return s.length > 0;
      });
  }

  function bikeSyncOptionsToHidden(container, hiddenInput) {
    if (!container || !hiddenInput) return;
    const items = Array.prototype.slice.call(container.querySelectorAll('input[type="text"]'))
      .map(function(el) {
        return (el.value || '').trim();
      })
      .filter(function(v) {
        return v.length > 0;
      });
    hiddenInput.value = items.join('\n');
  }

  function bikeCreateOptionRow(container, hiddenInput, initialValue) {
    const row = document.createElement('div');
    row.className = 'd-flex align-items-center gap-2';

    const rowInput = document.createElement('input');
    rowInput.type = 'text';
    rowInput.className = 'form-control';
    rowInput.placeholder = 'Option value';
    rowInput.value = initialValue || '';
    rowInput.addEventListener('input', function() {
      bikeSyncOptionsToHidden(container, hiddenInput);
    });

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-sm btn-outline-danger';
    removeBtn.textContent = 'Remove';
    removeBtn.addEventListener('click', function() {
      row.remove();
      bikeSyncOptionsToHidden(container, hiddenInput);
    });

    row.appendChild(rowInput);
    row.appendChild(removeBtn);
    container.appendChild(row);
    bikeSyncOptionsToHidden(container, hiddenInput);
  }

  function bikeRenderOptionRows(container, hiddenInput, rawOptions) {
    if (!container || !hiddenInput) return;
    container.innerHTML = '';
    const items = bikeParseOptionLines(rawOptions);
    if (!items.length) {
      bikeCreateOptionRow(container, hiddenInput, '');
      return;
    }
    items.forEach(function(item) {
      bikeCreateOptionRow(container, hiddenInput, item);
    });
  }

  function bikeRenderFixedInputPreview(inputType) {
    var preview = document.getElementById('editBikeFixedInputPreview');
    if (!preview) return;
    var type = String(inputType || 'text').toLowerCase();
    var html = '';
    if (type === 'textarea') {
      html = '<textarea class="form-control" rows="3" placeholder="Sample textarea"></textarea>';
    } else if (type === 'number' || type === 'decimal') {
      html = '<input type="number" class="form-control" placeholder="0">';
    } else if (type === 'date') {
      html = '<input type="date" class="form-control">';
    } else if (type === 'datetime') {
      html = '<input type="datetime-local" class="form-control">';
    } else if (type === 'dropdown') {
      html = '<select class="form-select"><option value="">Select option</option></select>';
    } else if (type === 'checkbox') {
      html = '<div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="editBikeFixedPreviewCheck"><label class="form-check-label" for="editBikeFixedPreviewCheck">Sample checkbox</label></div>';
    } else {
      html = '<input type="text" class="form-control" placeholder="Sample text">';
    }
    preview.innerHTML = html;
  }

  function bikeToggleFixedDropdownOptions(inputType) {
    var type = String(inputType || 'text').toLowerCase();
    var wrap = document.getElementById('editBikeFixedOptionsWrap');
    if (!wrap) return;
    wrap.style.display = (type === 'dropdown') ? '' : 'none';
  }

  function bikeUpdateFixedTypeUI(inputType) {
    bikeRenderFixedInputPreview(inputType);
    bikeToggleFixedDropdownOptions(inputType);
  }

  function bikeInitOptionRowButtons() {
    // Add modal (new custom field)
    const addBtn = document.getElementById('addBikeFieldOptionRowBtn');
    const rows = document.getElementById('addBikeFieldOptionsRows');
    const hidden = document.getElementById('addBikeFieldConfigOptionsHidden');
    if (addBtn && rows && hidden) {
      addBtn.addEventListener('click', function() {
        bikeCreateOptionRow(rows, hidden, '');
      });
    }

    // Add modal (assign custom field)
    const assignAddBtn = document.getElementById('addBikeAssignFieldOptionRowBtn');
    const assignRows = document.getElementById('addBikeAssignFieldOptionsRows');
    const assignHidden = document.getElementById('addBikeAssignFieldConfigOptionsHidden');
    if (assignAddBtn && assignRows && assignHidden) {
      assignAddBtn.addEventListener('click', function() {
        bikeCreateOptionRow(assignRows, assignHidden, '');
      });
    }
    var assignTypeSelect = document.getElementById('addBikeAssignFieldDataType');
    var assignOptionsWrap = document.getElementById('addBikeAssignFieldOptionsWrap');
    function toggleAssignAddOptions() {
      if (!assignTypeSelect || !assignOptionsWrap) return;
      assignOptionsWrap.style.display = String(assignTypeSelect.value || '').toLowerCase() === 'dropdown' ? '' : 'none';
    }
    if (assignTypeSelect) {
      assignTypeSelect.addEventListener('change', toggleAssignAddOptions);
      toggleAssignAddOptions();
    }
    var assignForm = document.getElementById('formAddBikeAssignField');
    if (assignForm && assignRows && assignHidden) {
      assignForm.addEventListener('submit', function() {
        bikeSyncOptionsToHidden(assignRows, assignHidden);
      });
    }

    // Edit fixed field modal
    const editFixedAddBtn = document.getElementById('editBikeFixedOptionRowBtn');
    const editFixedRows = document.getElementById('editBikeFixedOptionsRows');
    const editFixedHidden = document.getElementById('editBikeFixedInputConfigOptionsHidden');
    if (editFixedAddBtn && editFixedRows && editFixedHidden) {
      editFixedAddBtn.addEventListener('click', function() {
        bikeCreateOptionRow(editFixedRows, editFixedHidden, '');
      });
    }
    var editFixedTypeSelect = document.getElementById('editBikeFixedInputType');
    if (editFixedTypeSelect) {
      editFixedTypeSelect.addEventListener('change', function() {
        bikeUpdateFixedTypeUI(editFixedTypeSelect.value);
      });
    }

    // Edit custom field modal
    const editCustomAddBtn = document.getElementById('editBikeCustomOptionRowBtn');
    const editCustomRows = document.getElementById('editBikeCustomOptionsRows');
    const editCustomHidden = document.getElementById('editBikeCustomConfigOptionsHidden');
    if (editCustomAddBtn && editCustomRows && editCustomHidden) {
      editCustomAddBtn.addEventListener('click', function() {
        bikeCreateOptionRow(editCustomRows, editCustomHidden, '');
      });
    }

    const editAssignBuiltinOptBtn = document.getElementById('editAssignBuiltinOptionRowBtn');
    const editAssignBuiltinOptRows = document.getElementById('editAssignBuiltinOptionsRows');
    const editAssignBuiltinOptHidden = document.getElementById('editAssignBuiltinConfigOptionsHidden');
    if (editAssignBuiltinOptBtn && editAssignBuiltinOptRows && editAssignBuiltinOptHidden) {
      editAssignBuiltinOptBtn.addEventListener('click', function() {
        bikeCreateOptionRow(editAssignBuiltinOptRows, editAssignBuiltinOptHidden, '');
      });
    }

    const editAssignCustomOptBtn = document.getElementById('editAssignCustomOptionRowBtn');
    const editAssignCustomOptRows = document.getElementById('editAssignCustomOptionsRows');
    const editAssignCustomOptHidden = document.getElementById('editAssignCustomConfigOptionsHidden');
    if (editAssignCustomOptBtn && editAssignCustomOptRows && editAssignCustomOptHidden) {
      editAssignCustomOptBtn.addEventListener('click', function() {
        bikeCreateOptionRow(editAssignCustomOptRows, editAssignCustomOptHidden, '');
      });
    }

    var editAssignInputType = document.getElementById('editAssignInputType');
    if (editAssignInputType && typeof bikeToggleAssignBuiltinOptions === 'function') {
      editAssignInputType.addEventListener('change', bikeToggleAssignBuiltinOptions);
    }
    var editAssignCustomDataType = document.getElementById('editAssignCustomDataType');
    if (editAssignCustomDataType && typeof bikeToggleAssignCustomOptions === 'function') {
      editAssignCustomDataType.addEventListener('change', bikeToggleAssignCustomOptions);
    }

    var editAssignForm = document.getElementById('formEditBikeAssignField');
    if (editAssignForm) {
      editAssignForm.addEventListener('submit', function() {
        var isCustom = document.getElementById('editAssignCustomSection') &&
          document.getElementById('editAssignCustomSection').style.display !== 'none';
        if (isCustom && editAssignCustomOptRows && editAssignCustomOptHidden) {
          bikeSyncOptionsToHidden(editAssignCustomOptRows, editAssignCustomOptHidden);
        } else if (editAssignBuiltinOptRows && editAssignBuiltinOptHidden) {
          bikeSyncOptionsToHidden(editAssignBuiltinOptRows, editAssignBuiltinOptHidden);
        }
      });
    }
  }

  bikeInitOptionRowButtons();

  document.addEventListener('show.bs.modal', function(e) {
    const modalId = e.target && e.target.id ? e.target.id : null;
    const btn = e.relatedTarget;
    if (!modalId || !btn) return;

    // Fixed field edit
    if (modalId === 'editBikeFixedFieldModal') {
      const fieldKey = btn.dataset.fieldKey || '';
      document.getElementById('editBikeFixedFieldKey').value = fieldKey;
      document.getElementById('editBikeFixedDisplayLabel').value = btn.dataset.fieldLabel || '';

      const categoryId = btn.dataset.categoryId || '';
      const catSelect = document.getElementById('editBikeFixedCategoryId');
      if (categoryId && catSelect.querySelector('option[value="' + categoryId + '"]')) {
        catSelect.value = categoryId;
      }

      var visEl = document.getElementById('editBikeFixedIsVisible');
      var reqEl = document.getElementById('editBikeFixedIsRequired');
      if (visEl) {
        visEl.checked = String(btn.dataset.isVisible) === '1';
        visEl.disabled = false;
      }
      if (reqEl) {
        reqEl.checked = String(btn.dataset.isRequired) === '1';
        reqEl.disabled = false;
      }
      document.getElementById('editBikeFixedInputType').value = btn.dataset.inputType || 'text';
      bikeUpdateFixedTypeUI(btn.dataset.inputType || 'text');

      const configOptionsRaw = btn.dataset.inputConfigOptions;
      const configOptions = bikeSafeJsonParse(configOptionsRaw, '');
      bikeRenderOptionRows(
        document.getElementById('editBikeFixedOptionsRows'),
        document.getElementById('editBikeFixedInputConfigOptionsHidden'),
        configOptions || ''
      );
    }

    // Custom field edit
    if (modalId === 'editBikeCustomFieldModal') {
      const updateUrl = btn.dataset.updateUrl || '#';
      const form = document.getElementById('formEditBikeCustomField');
      if (form && updateUrl && updateUrl !== '#') {
        form.action = updateUrl;
      }

      document.getElementById('editBikeCustomLabel').value = btn.dataset.fieldLabel || '';
      document.getElementById('editBikeCustomHelpText').value = btn.dataset.helpText || '';
      document.getElementById('editBikeCustomDataType').value = btn.dataset.dataType || '';
      document.getElementById('editBikeCustomIsMandatory').checked = String(btn.dataset.isMandatory) === '1';

      const categoryId = btn.dataset.categoryId || '';
      const catSelect = document.getElementById('editBikeCustomCategoryId');
      if (categoryId !== '' && catSelect.querySelector('option[value="' + categoryId + '"]')) {
        catSelect.value = categoryId;
      } else {
        catSelect.value = '';
      }

      document.getElementById('editBikeCustomDefaultValue').value = btn.dataset.defaultValue || '';
      document.getElementById('editBikeCustomInputFormat').value = btn.dataset.inputFormat || '';

      const configOptionsRaw = btn.dataset.configOptions;
      const configOptions = bikeSafeJsonParse(configOptionsRaw, '');
      bikeRenderOptionRows(
        document.getElementById('editBikeCustomOptionsRows'),
        document.getElementById('editBikeCustomConfigOptionsHidden'),
        configOptions || ''
      );
    }

  });

  document.addEventListener('DOMContentLoaded', function() {
    var editFixedTypeSelect = document.getElementById('editBikeFixedInputType');
    if (editFixedTypeSelect) {
      bikeUpdateFixedTypeUI(editFixedTypeSelect.value || 'text');
    }
  });

  function bikeSyncFieldToggles(fieldKey, type, value) {
    const selector = type === 'required' ?
      '.bike-field-required-toggle[data-field-key="' + fieldKey + '"]' :
      '.bike-field-visibility-toggle[data-field-key="' + fieldKey + '"]';
    document.querySelectorAll(selector).forEach(function(el) {
      el.checked = !!value;
    });
  }

  function bikeUpdateFieldToggle(toggleEl, changedKey, changedValue) {
    const csrf = '{{ csrf_token() }}';
    const fieldKey = toggleEl.dataset.fieldKey || '';
    const categoryId = toggleEl.dataset.categoryId || '';

    const payload = new URLSearchParams();
    payload.append('_token', csrf);
    payload.append('field_key', fieldKey);
    payload.append('category_id', categoryId);
    payload.append('display_label', toggleEl.dataset.displayLabel || '');
    payload.append('input_type', toggleEl.dataset.inputType || '');
    payload.append('input_config_options', toggleEl.dataset.inputConfigOptions || '');

    const isRequired = changedKey === 'is_required' ?
      changedValue :
      Number(toggleEl.dataset.isRequiredCurrent || 0);
    const isVisible = changedKey === 'is_visible' ?
      changedValue :
      Number(toggleEl.dataset.isVisibleCurrent || 1);

    payload.append('is_required', String(isRequired));
    payload.append('is_visible', String(isVisible));

    return fetch("{{ route($settingsRoutePrefix . '.update-field-assignment', $settingsRouteParams) }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: payload.toString(),
    }).then(function(response) {
      return response.json().then(function(data) {
        return response.ok ? data : Promise.reject(data);
      });
    });
  }

  if (!window.__bikeCustomFieldToggleChangeBound) {
    window.__bikeCustomFieldToggleChangeBound = true;

    document.addEventListener('change', function(e) {
      var toggle = e.target.closest('.bike-custom-required-toggle, .bike-custom-visibility-toggle');
      if (!toggle) return;

      var customFieldId = toggle.getAttribute('data-id');
      var updateUrl = toggle.getAttribute('data-update-url');
      if (!customFieldId || !updateUrl) return;

      var csrf = '{{ csrf_token() }}';
      var fieldRequiredToggles = document.querySelectorAll('.bike-custom-required-toggle[data-id="' + customFieldId + '"]');
      var fieldVisibleToggles = document.querySelectorAll('.bike-custom-visibility-toggle[data-id="' + customFieldId + '"]');
      var isMandatory = fieldRequiredToggles.length ? (fieldRequiredToggles[0].checked ? 1 : 0) : 0;
      var isVisible = fieldVisibleToggles.length ? (fieldVisibleToggles[0].checked ? 1 : 0) : 1;
      var originalChecked = toggle.checked;

      toggle.disabled = true;

      var formBody = new URLSearchParams();
      formBody.append('_token', csrf);
      formBody.append('is_mandatory', String(isMandatory));
      formBody.append('is_visible', String(isVisible));

      fetch(updateUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: formBody.toString(),
        })
        .then(function(r) {
          return r.json().then(function(data) {
            return r.ok ? data : Promise.reject(data);
          });
        })
        .then(function(data) {
          fieldRequiredToggles.forEach(function(el) {
            el.checked = !!data.is_mandatory;
            el.setAttribute('data-is-visible-current', data.is_visible ? '1' : '0');
          });
          fieldVisibleToggles.forEach(function(el) {
            el.checked = !!data.is_visible;
            el.setAttribute('data-is-mandatory-current', data.is_mandatory ? '1' : '0');
          });
        })
        .catch(function() {
          toggle.checked = !originalChecked;
        })
        .finally(function() {
          toggle.disabled = false;
        });
    });
  }

  if (!window.__moduleFieldToggleChangeBound) {
    window.__moduleFieldToggleChangeBound = true;

    document.addEventListener('change', function(e) {
      const toggle = e.target.closest('.bike-field-required-toggle, .bike-field-visibility-toggle');
      if (!toggle) return;
      if (toggle.dataset.updating === '1') return;

      const isRequiredToggle = toggle.classList.contains('bike-field-required-toggle');
      const changedKey = isRequiredToggle ? 'is_required' : 'is_visible';
      const changedType = isRequiredToggle ? 'required' : 'visibility';
      const originalChecked = !toggle.checked;

      toggle.dataset.updating = '1';
      toggle.disabled = true;

      bikeUpdateFieldToggle(toggle, changedKey, toggle.checked ? 1 : 0)
        .then(function(data) {
          const isRequired = Number(data.is_required ? 1 : 0);
          const isVisible = Number(data.is_visible ? 1 : 0);

          document.querySelectorAll('.bike-field-required-toggle[data-field-key="' + toggle.dataset.fieldKey + '"]')
            .forEach(function(el) {
              el.dataset.isRequiredCurrent = String(isRequired);
            });

          document.querySelectorAll('.bike-field-visibility-toggle[data-field-key="' + toggle.dataset.fieldKey + '"]')
            .forEach(function(el) {
              el.dataset.isVisibleCurrent = String(isVisible);
            });

          if (isRequiredToggle) {
            toggle.checked = !!isRequired;
            bikeSyncFieldToggles(toggle.dataset.fieldKey, changedType, isRequired);
          } else {
            toggle.checked = !!isVisible;
            bikeSyncFieldToggles(toggle.dataset.fieldKey, changedType, isVisible);
          }
        })
        .catch(function() {
          toggle.checked = originalChecked;
        })
        .finally(function() {
          toggle.disabled = false;
          delete toggle.dataset.updating;
        });
    });
  }

  function initBikeFieldSortables() {
    if (typeof Sortable === 'undefined') return;
    document.querySelectorAll('.bike-fields-sortable-tbody').forEach(function(tbody) {
      if (tbody.dataset.sortableInit === '1') return;
      var categoryId = tbody.getAttribute('data-category-id');
      if (!categoryId) return;

      new Sortable(tbody, {
        handle: '.drag-handle',
        draggable: 'tr[data-bike-field-key]',
        animation: 150,
        ghostClass: 'table-warning',
        onEnd: function() {
          var order = Array.from(tbody.querySelectorAll('tr[data-bike-field-key]')).map(function(tr) {
            return tr.getAttribute('data-bike-field-key');
          });
          fetch("{{ route($settingsRoutePrefix . '.reorder-field-assignments', $settingsRouteParams) }}", {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              category_id: parseInt(categoryId, 10),
              order: order
            })
          });
        }
      });
      tbody.dataset.sortableInit = '1';
    });
  }

  function initBikeFieldAllTabSortables() {
    if (typeof Sortable === 'undefined') return;
    var reorderAllFixedUrl = "{{ route($settingsRoutePrefix . '.reorder-field-assignments-all', $settingsRouteParams) }}";
    var reorderAllCustomUrl = "{{ route($settingsRoutePrefix . '.reorder-all-custom-fields', $settingsRouteParams) }}";

    document.querySelectorAll('.bike-fields-all-fixed-sortable-tbody').forEach(function(tbody) {
      if (tbody.dataset.sortableInit === '1') return;
      new Sortable(tbody, {
        handle: '.drag-handle',
        draggable: 'tr[data-bike-field-key]',
        animation: 150,
        ghostClass: 'table-warning',
        onEnd: function() {
          var order = Array.from(tbody.querySelectorAll('tr[data-bike-field-key]')).map(function(tr) {
            return tr.getAttribute('data-bike-field-key');
          });
          fetch(reorderAllFixedUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              order: order
            })
          });
        }
      });
      tbody.dataset.sortableInit = '1';
    });

    document.querySelectorAll('.bike-fields-all-custom-sortable-tbody').forEach(function(tbody) {
      if (tbody.dataset.sortableInitAllCustom === '1') return;
      new Sortable(tbody, {
        handle: '.drag-handle',
        draggable: 'tr[data-custom-field-id]',
        animation: 150,
        ghostClass: 'table-warning',
        onEnd: function() {
          var order = Array.from(tbody.querySelectorAll('tr[data-custom-field-id]')).map(function(tr) {
            return parseInt(tr.getAttribute('data-custom-field-id'), 10);
          });
          fetch(reorderAllCustomUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              order: order
            })
          });
        }
      });
      tbody.dataset.sortableInitAllCustom = '1';
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    initBikeFieldSortables();
    initBikeFieldAllTabSortables();
  });
  document.querySelectorAll('#bikeFieldsCategoryTabs [data-bs-toggle="tab"]').forEach(function(tabBtn) {
    tabBtn.addEventListener('shown.bs.tab', function() {
      setTimeout(function() {
        initBikeFieldSortables();
        initBikeFieldAllTabSortables();
      }, 50);
    });
  });
</script>
<script>
  var visaStatusConfig = document.getElementById('visa-status-manager-config');
  if (visaStatusConfig) {
    var visaStatusSortableInstance = null;

    function initVisaStatusSortable() {
      if (typeof Sortable === 'undefined') return;
      var tbody = document.getElementById('visa-statuses-tbody');
      if (!tbody || tbody.querySelectorAll('tr[data-id]').length === 0) return;

      if (visaStatusSortableInstance) {
        visaStatusSortableInstance.destroy();
      }

      visaStatusSortableInstance = new Sortable(tbody, {
        handle: '.visa-drag-handle',
        animation: 150,
        ghostClass: 'table-warning',
        onEnd: function() {
          var order = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(row) {
            return row.getAttribute('data-id');
          });

          fetch("{{ route('settings-panel.visa-statuses.reorder', ['company_slug' => request()->route('company_slug') ?? session('company_slug')]) }}", {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              order: order
            })
          }).then(function(response) {
            return response.json();
          }).then(function(data) {
            if (!data.success && typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Could not save order.'
              });
            }
          }).catch(function() {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not save order.'
              });
            }
          });
        }
      });
    }

    function deleteVisaStatusAjax(url, triggerBtn) {
      return fetch(url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          _method: 'DELETE'
        })
      }).then(function(response) {
        return response.json().then(function(data) {
          return {
            ok: response.ok,
            status: response.status,
            data: data
          };
        }).catch(function() {
          return {
            ok: response.ok,
            status: response.status,
            data: {
              success: false,
              message: 'Invalid server response.'
            }
          };
        });
      }).then(function(result) {
        if (!result.ok || !result.data || result.data.success !== true) {
          throw new Error((result.data && result.data.message) ? result.data.message : 'Delete failed.');
        }
        var row = triggerBtn ? triggerBtn.closest('tr[data-id]') : null;
        if (row) {
          row.remove();
        }
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Deleted',
            text: result.data.message || 'Visa status deleted successfully.',
            timer: 1600,
            showConfirmButton: false
          });
        }
        return result.data;
      });
    }

    function confirmDelete(url, formId, triggerBtn) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to revert this!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then(function(result) {
          if (!result.isConfirmed) return;
          deleteVisaStatusAjax(url, triggerBtn).catch(function(err) {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: (err && err.message) ? err.message : 'Could not delete visa status.'
            });
          });
        });
        return;
      }
      if (confirm('Are you sure?')) {
        deleteVisaStatusAjax(url, triggerBtn).catch(function() {
          window.location.href = url;
        });
      }
    }

    document.addEventListener('click', function(e) {
      var deleteBtn = e.target.closest('.js-visa-status-delete-btn');
      if (deleteBtn) {
        confirmDelete(
          deleteBtn.getAttribute('data-delete-url') || '',
          deleteBtn.getAttribute('data-delete-form-id') || '',
          deleteBtn
        );
        return;
      }

      var editBtn = e.target.closest('.js-visa-status-edit-btn');
      if (!editBtn) return;

      var editUrlTemplate = visaStatusConfig.getAttribute('data-edit-url-template') || '';
      var form = document.getElementById('editVisaStatusForm');
      if (!form) return;
      form.action = editUrlTemplate.replace('__ID__', String(editBtn.dataset.id || ''));

      document.getElementById('editVisaStatusName').value = editBtn.dataset.name || '';
      document.getElementById('editVisaStatusCode').value = editBtn.dataset.code || '';
      document.getElementById('editVisaStatusCategory').value = editBtn.dataset.category || 'Other';
      document.getElementById('editVisaStatusDefaultFee').value = editBtn.dataset.defaultFee || 0;
      document.getElementById('editVisaStatusDisplayOrder').value = editBtn.dataset.displayOrder || '';
      document.getElementById('editVisaStatusDescription').value = editBtn.dataset.description || '';
      document.getElementById('editVisaStatusIsRequired').checked = String(editBtn.dataset.isRequired || '0') === '1';
      document.getElementById('editVisaStatusIsActive').checked = String(editBtn.dataset.isActive || '0') === '1';
    });

    document.addEventListener('DOMContentLoaded', function() {
      initVisaStatusSortable();
      var targetHash = window.location.hash;
      if (targetHash === '#tab-visa-status-management' || targetHash === '#tab-visa-expense-top') {
        var visaTabBtn = document.querySelector('[data-bs-target="' + targetHash + '"]');
        if (visaTabBtn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
          bootstrap.Tab.getOrCreateInstance(visaTabBtn).show();
        } else if (visaTabBtn) {
          visaTabBtn.click();
        }
      }

      var topForm = document.getElementById('visaExpenseTopAjaxForm');
      var topCount = document.getElementById('visaExpenseTopSelectedCount');
      var topToggle = document.getElementById('visaExpenseTopEnabled');
      var topList = document.getElementById('visaExpenseTopSelectedList');
      var topModalSelect = document.getElementById('visaExpenseTopStatusSelect');
      var topAddBtn = document.getElementById('btnAddVisaExpenseTopOption');

      function refreshVisaExpenseTopCount() {
        if (!topList || !topCount) return;
        var count = topList.querySelectorAll('li[data-selected-id]').length;
        topCount.textContent = String(count);
        var emptyRow = document.getElementById('visaExpenseTopNoOptions');
        if (count === 0) {
          if (!emptyRow) {
            var li = document.createElement('li');
            li.className = 'list-group-item px-0 py-2 text-muted';
            li.id = 'visaExpenseTopNoOptions';
            li.textContent = 'No options added yet.';
            topList.appendChild(li);
          }
        } else if (emptyRow) {
          emptyRow.remove();
        }
      }

      function saveVisaExpenseTopAjax() {
        if (!topForm) return;
        var fd = new FormData(topForm);
        var isChecked = document.getElementById('visaExpenseTopEnabled')?.checked;
        fd.set('show_in_top_bar', isChecked ? '1' : '0');
        if (topToggle) topToggle.disabled = true;
        if (topModalSelect) topModalSelect.disabled = true;
        if (topAddBtn) topAddBtn.disabled = true;

        fetch(topForm.action, {
            method: 'POST',
            body: fd,
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json'
            }
          })
          .then(function(r) {
            return r.json().then(function(d) {
              return {
                ok: r.ok,
                data: d
              };
            });
          })
          .then(function(result) {
            if (topToggle) topToggle.disabled = false;
            if (topModalSelect) topModalSelect.disabled = false;
            if (topAddBtn) topAddBtn.disabled = false;
            if (!result.ok || !result.data || result.data.success !== true) {
              throw new Error((result.data && result.data.message) ? result.data.message : 'Could not save.');
            }
            refreshVisaExpenseTopCount();
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: result.data.message || 'Updated successfully.',
                timer: 1400,
                showConfirmButton: false
              });
            }
          })
          .catch(function(err) {
            if (topToggle) topToggle.disabled = false;
            if (topModalSelect) topModalSelect.disabled = false;
            if (topAddBtn) topAddBtn.disabled = false;
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: (err && err.message) ? err.message : 'Could not save.'
              });
            }
          });
      }

      if (topToggle) {
        topToggle.addEventListener('change', function() {
          saveVisaExpenseTopAjax();
        });
      }
      if (topList) {
        if (typeof Sortable !== 'undefined') {
          new Sortable(topList, {
            handle: '.visa-expense-top-drag-handle',
            draggable: 'li[data-selected-id]',
            animation: 150,
            ghostClass: 'table-warning',
            onEnd: function() {
              refreshVisaExpenseTopCount();
              saveVisaExpenseTopAjax();
            }
          });
        }
        topList.addEventListener('click', function(e) {
          var removeBtn = e.target.closest('.js-remove-visa-expense-top-option');
          if (!removeBtn) return;
          var row = removeBtn.closest('li[data-selected-id]');
          if (row) {
            row.remove();
            refreshVisaExpenseTopCount();
            saveVisaExpenseTopAjax();
          }
        });
      }

      function initVisaExpenseTopModalSelect2() {
        if (!(typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) || !topModalSelect) return;
        var $select = jQuery(topModalSelect);
        if ($select.hasClass('select2-hidden-accessible')) {
          $select.select2('destroy');
        }
        $select.select2({
          dropdownParent: jQuery('#addVisaExpenseTopOptionModal'),
          placeholder: 'Select visa status',
          allowClear: true,
          width: '100%'
        });
      }
      initVisaExpenseTopModalSelect2();
      var addVisaExpenseTopModal = document.getElementById('addVisaExpenseTopOptionModal');
      if (addVisaExpenseTopModal) {
        addVisaExpenseTopModal.addEventListener('shown.bs.modal', function() {
          initVisaExpenseTopModalSelect2();
          if (typeof jQuery !== 'undefined') {
            jQuery(topModalSelect).select2('open');
          }
        });
      }
      if (topAddBtn && topModalSelect && topList) {
        topAddBtn.addEventListener('click', function() {
          var selectedId = parseInt(topModalSelect.value || '0', 10);
          if (!selectedId) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'warning',
                title: 'Select status',
                text: 'Please select a visa status first.'
              });
            }
            return;
          }
          if (topList.querySelector('li[data-selected-id="' + selectedId + '"]')) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'info',
                title: 'Already added',
                text: 'This status is already added.'
              });
            }
            return;
          }
          var selectedOption = topModalSelect.options[topModalSelect.selectedIndex];
          var name = selectedOption ? (selectedOption.getAttribute('data-name') || selectedOption.text || 'Status') : 'Status';
          var li = document.createElement('li');
          li.className = 'list-group-item px-0 py-2 d-flex align-items-center justify-content-between';
          li.setAttribute('data-selected-id', String(selectedId));
          li.innerHTML =
            '<div class="d-flex align-items-center">' +
            '<span class="visa-expense-top-drag-handle me-2 text-muted" title="Drag to sort" style="cursor: grab;"><i class="ti ti-grip-vertical"></i></span>' +
            '<i class="ti ti-point-filled me-1 text-muted"></i>' +
            '<span>' + name + '</span>' +
            '<input type="hidden" name="status_ids[]" value="' + selectedId + '">' +
            '</div>' +
            '<div class="d-flex align-items-center gap-1">' +
            '<button type="button" class="btn btn-xs btn-outline-danger js-remove-visa-expense-top-option" data-remove-id="' + selectedId + '" title="Remove option"><i class="ti ti-trash"></i></button>' +
            '</div>';
          topList.appendChild(li);
          refreshVisaExpenseTopCount();
          if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) {
            jQuery(topModalSelect).val('').trigger('change');
          } else {
            topModalSelect.value = '';
          }
          var modalEl = document.getElementById('addVisaExpenseTopOptionModal');
          if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
          }
          saveVisaExpenseTopAjax();
        });
      }
      refreshVisaExpenseTopCount();
    });

    var visaStatusTabBtn = document.querySelector('[data-bs-target="#tab-visa-status-management"]');
    if (visaStatusTabBtn) {
      visaStatusTabBtn.addEventListener('shown.bs.tab', function() {
        setTimeout(initVisaStatusSortable, 50);
      });
    }
  }

  @if($showBikeRegistrationExtras)
  @include('settings.partials.bike_registration_top_bar_script')
  @endif

  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-edit-module-document-type');
    if (!btn) return;

    const form = document.getElementById('formEditModuleDocumentType');
    if (!form) return;

    form.action = btn.dataset.updateUrl || '#';
    document.getElementById('editModuleDocKey').value = btn.dataset.key || '';
    document.getElementById('editModuleDocType').value = btn.dataset.type || 'single';
    document.getElementById('editModuleDocLabel').value = btn.dataset.label || '';
    document.getElementById('editModuleDocFrontLabel').value = btn.dataset.frontLabel || '';
    document.getElementById('editModuleDocBackLabel').value = btn.dataset.backLabel || '';
  });

  var addModuleDocTypeType = document.getElementById('addModuleDocTypeType');
  if (addModuleDocTypeType) {
    addModuleDocTypeType.addEventListener('change', function() {
      var isDual = this.value === 'dual';
      document.getElementById('addModuleDocTypeSingleWrap').style.display = isDual ? 'none' : 'block';
      document.getElementById('addModuleDocTypeDualWrap').style.display = isDual ? 'block' : 'none';
    });
  }

  var formAddModuleDocumentType = document.getElementById('formAddModuleDocumentType');
  if (formAddModuleDocumentType) {
    formAddModuleDocumentType.addEventListener('submit', function(e) {
      e.preventDefault();
      var form = this;
      var fd = new FormData(form);
      fd.set('is_active', form.querySelector('#addModuleDocTypeActive').checked ? '1' : '0');
      var btn = document.getElementById('addModuleDocumentTypeSubmitBtn');
      if (btn) btn.disabled = true;

      fetch("{{ route($settingsRoutePrefix . '.store-document-type', $settingsRouteParams) }}", {
          method: 'POST',
          body: fd,
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(r) {
          if (!r.ok) {
            return r.json().then(function(d) {
              return {
                _httpError: true,
                status: r.status,
                data: d
              };
            }).catch(function() {
              return {
                _httpError: true,
                status: r.status
              };
            });
          }
          return r.json();
        })
        .then(function(data) {
          if (btn) btn.disabled = false;
          if (data._httpError) {
            var msg = (data.data && data.data.message) || 'Server error.';
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: msg
            });
            return;
          }

          if (data.success) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
              var m = bootstrap.Modal.getInstance(document.getElementById('addModuleDocumentTypeModal'));
              if (m) m.hide();
            }
            form.reset();
            document.getElementById('addModuleDocTypeSingleWrap').style.display = 'block';
            document.getElementById('addModuleDocTypeDualWrap').style.display = 'none';
            document.getElementById('addModuleDocTypeActive').checked = true;
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: data.message || 'Document type added.'
              }).then(function() {
                window.location.reload();
              });
            } else {
              window.location.reload();
            }
          } else {
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not save.'
            });
          }
        })
        .catch(function() {
          if (btn) btn.disabled = false;
          if (typeof Swal !== 'undefined') Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Could not save document type.'
          });
        });
    });
  }
</script>
@if(($moduleKey ?? '') === 'bike_list')
@include('settings.bike_settings._bike_top_page_script')
@endif
@endsection