@php
$categories = $categories ?? collect();
$returnTo = $returnTo ?? '';
$companySlug = $companySlug ?? (request()->route('company_slug') ?? session('company_slug'));
$routePrefix = $routePrefix ?? 'settings-panel.visa-renewal-categories';
$embeddedManager = $embeddedManager ?? true;
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <p class="text-muted small mb-0">Default category is <strong>New Visa</strong>. Add renewal stages (e.g. 1st Renewal, 2nd Renewal) in sequence order.</p>
    @can('visa_expense_create')
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

<div class="modal fade" id="createVisaRenewalCategoryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route($routePrefix . '.store', ['company_slug' => $companySlug]) }}">
                @csrf
                @if($returnTo)
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">Create Renewal Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" class="form-control" required maxlength="255" placeholder="e.g. 1st Renewal">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-control" min="1" placeholder="Auto if empty">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="create_visa_renewal_is_active" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="create_visa_renewal_is_active">Active</label>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editVisaRenewalCategoryForm" action="">
                @csrf
                @method('PUT')
                @if($returnTo)
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title">Edit Renewal Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" id="edit_visa_renewal_name" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" id="edit_visa_renewal_display_order" class="form-control" min="1">
                    </div>
                    <div class="form-check" id="edit_visa_renewal_active_wrap">
                        <input type="checkbox" name="is_active" id="edit_visa_renewal_is_active" class="form-check-input" value="1">
                        <label class="form-check-label" for="edit_visa_renewal_is_active">Active</label>
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
