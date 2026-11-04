@php
$categories = $categories ?? collect();
$assignableStatuses = $assignableStatuses ?? collect();
$returnTo = $returnTo ?? '';
$companySlug = $companySlug ?? (request()->route('company_slug') ?? session('company_slug'));
$routePrefix = $routePrefix ?? 'settings-panel.visa-renewal-categories';
$embeddedManager = $embeddedManager ?? true;
$hideTable = $hideTable ?? false;
@endphp

@if(! $hideTable)
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <p class="text-muted small mb-0">Create a <strong>Visa Category</strong>, then assign existing shared tickets (or leave empty and assign later).</p>
    @can('visa_expense_account_creation_create')
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createVisaRenewalCategoryModal">
        <i class="ti ti-plus me-1"></i> Add Category
    </button>
    @endcan
</div>

@include('visa_renewal_categories.table', [
    'categories' => $categories,
    'routePrefix' => $routePrefix,
    'embeddedManager' => $embeddedManager,
    'returnTo' => $returnTo,
])
@endif

<div class="modal fade" id="createVisaRenewalCategoryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route($routePrefix . '.store', ['company_slug' => $companySlug]) }}">
                @csrf
                @if($returnTo)
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">Create Visa Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Select existing tickets to assign to this category. Tickets are shared — no new ticket rows are created.</p>
                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" class="form-control" required maxlength="255" placeholder="e.g. New Visa, 1st Renewal">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-control" min="1" placeholder="Auto if empty">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" id="create_visa_renewal_is_active" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="create_visa_renewal_is_active">Active</label>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Assign existing tickets</label>
                        @if($assignableStatuses->isEmpty())
                        <p class="text-muted small mb-0">No tickets exist yet. Create tickets after saving this category.</p>
                        @else
                        <div class="border rounded p-2" style="max-height: 220px; overflow:auto;">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($assignableStatuses as $status)
                                <label class="border rounded px-2 py-1 mb-0 d-inline-flex align-items-center gap-2" style="cursor:pointer;">
                                    <input type="checkbox" name="visa_status_ids[]" value="{{ $status->id }}" {{ $status->is_active ? '' : '' }}>
                                    <span>
                                        <code class="text-primary">{{ method_exists($status, 'ticketNumber') ? $status->ticketNumber() : ('T-'.str_pad((string)$status->id, 3, '0', STR_PAD_LEFT)) }}</code>
                                        {{ $status->name }}
                                        @if(! $status->is_active)<span class="badge bg-secondary ms-1">Inactive</span>@endif
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
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

<div class="modal fade" id="editVisaRenewalCategoryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="editVisaRenewalCategoryForm" action="#" data-update-base="{{ url('app/' . $companySlug . '/settings-panel/visa-renewal-categories') }}">
                @csrf
                @method('PUT')
                @if($returnTo)
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">Edit Visa Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Check or uncheck tickets to mass assign or remove them from this category. Tickets stay shared — no new rows are created.</p>
                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" id="edit_visa_renewal_name" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" id="edit_visa_renewal_display_order" class="form-control" min="1">
                    </div>
                    <div class="form-check mb-3" id="edit_visa_renewal_active_wrap">
                        <input type="checkbox" name="is_active" id="edit_visa_renewal_is_active" class="form-check-input" value="1">
                        <label class="form-check-label" for="edit_visa_renewal_is_active">Active</label>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Assigned tickets</label>
                        @if($assignableStatuses->isEmpty())
                        <p class="text-muted small mb-0">No tickets exist yet.</p>
                        @else
                        <div class="border rounded p-2" style="max-height: 220px; overflow:auto;">
                            <div class="d-flex flex-wrap gap-2" id="editVisaRenewalStatusChecks">
                                @foreach($assignableStatuses as $status)
                                <label class="border rounded px-2 py-1 mb-0 d-inline-flex align-items-center gap-2" style="cursor:pointer;">
                                    <input type="checkbox"
                                           name="visa_status_ids[]"
                                           value="{{ $status->id }}"
                                           class="js-edit-visa-renewal-status-check">
                                    <span>
                                        <code class="text-primary">{{ method_exists($status, 'ticketNumber') ? $status->ticketNumber() : ('T-'.str_pad((string)$status->id, 3, '0', STR_PAD_LEFT)) }}</code>
                                        {{ $status->name }}
                                        @if(! $status->is_active)<span class="badge bg-secondary ms-1">Inactive</span>@endif
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
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
