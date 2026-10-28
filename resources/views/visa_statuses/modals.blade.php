@php
$visaRoute = $visaRoute ?? ((View::shared('settings_panel') ?? false) ? 'settings-panel.visa-statuses' : 'visa-statuses');
$companySlug = $companySlug ?? (request()->route('company_slug') ?? session('company_slug'));
$visaRenewalCategories = $visaRenewalCategories ?? collect();
$returnTo = $returnTo ?? '';
$selectedCategoryId = (int) ($selectedCategoryId ?? 0);
$storeParams = array_filter(['company_slug' => $companySlug ?: null]);
$updateBase = $companySlug
    ? url('app/' . $companySlug . '/settings-panel/visa-statuses')
    : url('visa-statuses');
@endphp

<div class="modal fade" id="createVisaStatusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route($visaRoute . '.store', $storeParams) }}" id="createVisaStatusForm">
                @csrf
                @if($returnTo)
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">Create Visa Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">Show this ticket in these Visa Categories</label>
                            <div class="d-flex flex-wrap gap-2" id="createVisaStatusCategoryChecks">
                                @forelse($visaRenewalCategories as $visaCategoryOption)
                                <label class="border rounded px-2 py-1 mb-0 d-inline-flex align-items-center gap-2" style="cursor:pointer;">
                                    <input type="checkbox"
                                           name="visa_renewal_category_ids[]"
                                           value="{{ $visaCategoryOption->id }}"
                                           class="js-create-visa-cat-check"
                                           {{ $selectedCategoryId === (int) $visaCategoryOption->id ? 'checked' : '' }}>
                                    <span>{{ $visaCategoryOption->name }}{{ $visaCategoryOption->is_default ? ' (Default)' : '' }}</span>
                                </label>
                                @empty
                                <span class="text-muted small">Create a visa category first</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" class="form-control" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" maxlength="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
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
                            <label class="form-label">Display Order (master)</label>
                            <input type="number" name="display_order" class="form-control" min="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" maxlength="500"></textarea>
                        </div>
                        <div class="col-12 d-flex gap-4">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="create_visa_status_is_active" class="form-check-input" value="1" checked>
                                <label class="form-check-label" for="create_visa_status_is_active">Active</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="is_required" id="create_visa_status_is_required" class="form-check-input" value="1">
                                <label class="form-check-label" for="create_visa_status_is_required">Required</label>
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
            <form id="editVisaStatusForm" method="POST" action="#" data-update-base="{{ $updateBase }}">
                @csrf
                @method('PUT')
                @if($returnTo)
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">Edit Visa Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">Show this ticket in these Visa Categories</label>
                            <div class="d-flex flex-wrap gap-2" id="editVisaStatusCategoryChecks">
                                @foreach($visaRenewalCategories as $visaCategoryOption)
                                <label class="border rounded px-2 py-1 mb-0 d-inline-flex align-items-center gap-2" style="cursor:pointer;">
                                    <input type="checkbox"
                                           name="visa_renewal_category_ids[]"
                                           value="{{ $visaCategoryOption->id }}"
                                           class="js-edit-visa-cat-check"
                                           data-category-id="{{ $visaCategoryOption->id }}">
                                    <span>{{ $visaCategoryOption->name }}{{ $visaCategoryOption->is_default ? ' (Default)' : '' }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" id="editVisaStatusName" class="form-control" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" id="editVisaStatusCode" class="form-control" maxlength="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
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
                            <label class="form-label">Display Order (master)</label>
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
    data-edit-url-template="{{ route($visaRoute . '.update', array_filter(['company_slug' => $companySlug ?: null, 'visa_status' => '__ID__'])) }}"
    hidden></div>
