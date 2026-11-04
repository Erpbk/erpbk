@php
$visaRoute = $visaRoute ?? ((View::shared('settings_panel') ?? false) ? 'settings-panel.visa-statuses' : 'visa-statuses');
$embeddedInModuleSettings = !empty($embeddedInModuleSettings);
$companySlugForVisa = request()->route('company_slug') ?? session('company_slug');
$visaRenewalCategories = $visaRenewalCategories ?? collect();
$selectedCategoryId = (int) ($selectedCategoryId ?? 0);
$selectedCategory = $selectedCategory ?? $visaRenewalCategories->firstWhere('id', $selectedCategoryId);
$viewMode = ($viewMode ?? 'category') === 'all' ? 'all' : 'category';
$moduleSettingsVisaUrl = $companySlugForVisa
    ? route('settings-panel.module-settings.index', ['company_slug' => $companySlugForVisa, 'module' => 'visa_expense'])
    : '';
$visaPageReturnUrl = $visaRenewalCategoryReturnUrl
    ?? ($embeddedInModuleSettings && $moduleSettingsVisaUrl
        ? ($moduleSettingsVisaUrl . ($viewMode === 'all'
            ? '?visa_view=all'
            : ($selectedCategoryId ? ('?category_id=' . $selectedCategoryId) : '')) . '#tab-visa-status-management')
        : (route($visaRoute . '.index') . ($viewMode === 'all'
            ? '?view=all'
            : ($selectedCategoryId ? ('?category_id=' . $selectedCategoryId) : ''))));
$visaRenewalCategoryReturnUrl = $visaPageReturnUrl;
$indexUrl = $indexUrl ?? $visaPageReturnUrl;
$visaAjaxIndexUrl = $visaAjaxIndexUrl ?? route($visaRoute . '.index', array_filter(['company_slug' => $companySlugForVisa ?: null]));
$categoriesViewUrl = $embeddedInModuleSettings && $moduleSettingsVisaUrl
    ? ($moduleSettingsVisaUrl . ($selectedCategoryId ? ('?category_id=' . $selectedCategoryId) : '') . '#tab-visa-status-management')
    : (route($visaRoute . '.index') . ($selectedCategoryId ? ('?category_id=' . $selectedCategoryId) : ''));
$allTicketsViewUrl = $embeddedInModuleSettings && $moduleSettingsVisaUrl
    ? ($moduleSettingsVisaUrl . '?visa_view=all#tab-visa-status-management')
    : (route($visaRoute . '.index') . '?view=all');
@endphp
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>

@if(!$embeddedInModuleSettings)
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-8">
                <h1 class="mb-1">Visa Categories</h1>
            </div>
        </div>
    </div>
</section>
@endif

<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Visa Statuses</h5>
        <button type="button" class="btn-close" id="closeSidebar" aria-label="Close"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ request()->url() }}" method="GET">
            <input type="hidden" name="category_id" id="filter_category_id" value="{{ $selectedCategoryId }}">
            @if(($viewMode ?? 'category') === 'all')
            <input type="hidden" name="view" value="all">
            @endif
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="code">Code</label>
                    <input type="text" name="code" class="form-control" placeholder="Filter by Code" value="{{ request('code') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="name">Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Filter by Name" value="{{ request('name') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="category">Type</label>
                    <select class="form-control" id="category" name="category">
                        <option value="">All</option>
                        <option value="Document" {{ request('category') == 'Document' ? 'selected' : '' }}>Document</option>
                        <option value="Permit" {{ request('category') == 'Permit' ? 'selected' : '' }}>Permit</option>
                        <option value="License" {{ request('category') == 'License' ? 'selected' : '' }}>License</option>
                        <option value="Insurance" {{ request('category') == 'Insurance' ? 'selected' : '' }}>Insurance</option>
                        <option value="Other" {{ request('category') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="is_required">Required</label>
                    <select class="form-control" id="is_required" name="is_required">
                        <option value="">All</option>
                        <option value="1" {{ request('is_required') === '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ request('is_required') === '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="filterOverlay" class="filter-overlay"></div>

@php $isAllTicketsView = ($viewMode ?? 'category') === 'all'; @endphp
<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="visa-view-tabs" role="tablist" aria-label="Ticket views">
        <a href="{{ $categoriesViewUrl }}"
           class="visa-view-tab {{ ! $isAllTicketsView ? 'active' : '' }}">Categories</a>
        <a href="{{ $allTicketsViewUrl }}"
           class="visa-view-tab {{ $isAllTicketsView ? 'active' : '' }}">All Tickets</a>
    </div>

    @if($isAllTicketsView)
    <div class="visa-workspace visa-workspace-all" id="visa-all-view">
        <section class="visa-panel">
            <div class="visa-panel-header">
                <div>
                    <h4 id="visa-status-heading"><i class="ti ti-list-check me-1"></i> All Tickets</h4>
                    <p class="visa-panel-subtitle mb-0" id="visa-status-subtitle">Every ticket exists only once here. Assign categories when creating or editing.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm openFilterSidebar">
                        <i class="fa fa-search me-1"></i> Filter
                    </button>
                    @can('visa_expense_entries_create')
                    <button type="button"
                            class="btn btn-primary btn-sm js-visa-status-add-btn"
                            id="visa-add-status-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#createVisaStatusModal">
                        <i class="ti ti-plus me-1"></i> Add Ticket
                    </button>
                    @endcan
                </div>
            </div>
            <div class="card-body table-responsive px-2 py-0" id="table-data">
                @include('visa_statuses.table', [
                    'visaStatuses' => $visaStatuses,
                    'visaRoute' => $visaRoute,
                    'visaStatusReturnTo' => $indexUrl ?? $visaRenewalCategoryReturnUrl,
                    'selectedCategoryId' => $selectedCategoryId,
                    'viewMode' => 'all',
                    'visaRenewalCategories' => $visaRenewalCategories,
                    'useVisaStatusModals' => true,
                ])
            </div>
        </section>
    </div>
    @else
    <div class="visa-workspace" id="visa-cat-view">
        <aside class="visa-panel">
            <div class="visa-panel-header">
                <div>
                    <h4><i class="ti ti-category me-1"></i> Visa Categories</h4>
                    <p class="visa-panel-subtitle mb-0">Select a category to manage assigned tickets</p>
                </div>
                @can('visa_expense_entries_create')
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createVisaRenewalCategoryModal">
                    <i class="ti ti-plus"></i>
                </button>
                @endcan
            </div>
            <div class="visa-cat-list" id="visa-category-nav">
                @forelse($visaRenewalCategories as $category)
                @php $categoryPendingDeletion = record_is_pending_deletion($category); @endphp
                <div
                    class="visa-cat-item {{ (int) $category->id === $selectedCategoryId ? 'active' : '' }}{{ $categoryPendingDeletion ? ' opacity-75' : '' }}"
                    data-category-id="{{ $category->id }}"
                    data-id="{{ $category->id }}"
                    data-name="{{ $category->name }}"
                    data-display-order="{{ $category->display_order }}"
                    data-is-default="{{ $category->is_default ? 1 : 0 }}"
                    data-is-active="{{ $category->is_active ? 1 : 0 }}"
                    role="button"
                    tabindex="0">
                    <span class="visa-cat-icon"><i class="ti ti-folder"></i></span>
                    <span class="visa-cat-meta">
                        <span class="visa-cat-name">
                            {{ $category->name }}
                            @if($category->is_default)
                            <span class="badge bg-label-primary ms-1">Default</span>
                            @endif
                            @if(! $category->is_active)
                            <span class="badge bg-label-secondary ms-1">Inactive</span>
                            @endif
                            @if($categoryPendingDeletion)
                            @include('delete_requests._pending_badge', ['model' => $category])
                            @endif
                        </span>
                        <span class="visa-cat-count">{{ (int) ($category->visa_statuses_count ?? 0) }} ticket{{ (int) ($category->visa_statuses_count ?? 0) === 1 ? '' : 's' }}</span>
                    </span>
                    <span class="d-flex align-items-center gap-1" onclick="event.stopPropagation();">
                        @if(!$categoryPendingDeletion)
                        @can('visa_expense_entries_edit')
                        <button type="button" class="visa-icon-btn visa-icon-btn-edit js-visa-renewal-edit-btn"
                            title="Edit category"
                            data-bs-toggle="modal"
                            data-bs-target="#editVisaRenewalCategoryModal"
                            data-id="{{ $category->id }}"
                            data-name="{{ $category->name }}"
                            data-display-order="{{ $category->display_order }}"
                            data-is-default="{{ $category->is_default ? 1 : 0 }}"
                            data-is-active="{{ $category->is_active ? 1 : 0 }}"
                            data-status-ids="{{ $category->relationLoaded('visaStatuses') ? $category->visaStatuses->pluck('id')->implode(',') : '' }}"
                            onclick="event.preventDefault(); event.stopPropagation();">
                            <i class="ti ti-pencil"></i>
                        </button>
                        @endcan
                        @can('visa_expense_entries_delete')
                        @if(! $category->is_default)
                        <button type="button"
                            class="visa-icon-btn visa-icon-btn-delete js-visa-renewal-delete-btn"
                            title="Delete category"
                            data-delete-url="{{ route('settings-panel.visa-renewal-categories.destroy', $category->id) . '?return_to=' . urlencode($visaRenewalCategoryReturnUrl) }}"
                            onclick="event.preventDefault(); event.stopPropagation();">
                            <i class="ti ti-trash"></i>
                        </button>
                        @endif
                        @endcan
                        @endif
                    </span>
                </div>
                @empty
                <div class="visa-empty">
                    <div class="visa-empty-icon"><i class="ti ti-folder-plus"></i></div>
                    <h5 class="mb-1">No visa categories yet</h5>
                    <p class="text-muted small mb-3">Create a visa category first. Tickets can be assigned after a category exists.</p>
                    @can('visa_expense_entries_create')
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createVisaRenewalCategoryModal">
                        Create Visa Category
                    </button>
                    @endcan
                </div>
                @endforelse
            </div>
        </aside>

        <section class="visa-panel">
            <div class="visa-panel-header">
                <div>
                    <h4 id="visa-status-heading">
                        <i class="ti ti-list-check me-1"></i>
                        {{ $selectedCategory ? $selectedCategory->name . ' tickets' : 'Visa Tickets' }}
                    </h4>
                    <p class="visa-panel-subtitle mb-0" id="visa-status-subtitle">
                        @if($selectedCategory)
                        Tickets for this category are generated from these statuses only. Edit the category to assign or unassign tickets.
                        @else
                        Select a visa category to view and manage its tickets.
                        @endif
                    </p>
                </div>
            </div>
            <div class="card-body table-responsive px-2 py-0" id="table-data">
                @if($selectedCategoryId)
                @include('visa_statuses.table', [
                    'visaStatuses' => $visaStatuses,
                    'visaRoute' => $visaRoute,
                    'visaStatusReturnTo' => $indexUrl ?? $visaRenewalCategoryReturnUrl,
                    'selectedCategoryId' => $selectedCategoryId,
                    'viewMode' => 'category',
                    'visaRenewalCategories' => $visaRenewalCategories,
                    'useVisaStatusModals' => true,
                ])
                @else
                <div class="visa-empty">
                    <div class="visa-empty-icon"><i class="ti ti-list"></i></div>
                    <h5 class="mb-1">Select a visa category</h5>
                    <p class="text-muted small mb-0">Tickets are shared across categories. Assign them when creating a category or editing a ticket.</p>
                </div>
                @endif
            </div>
        </section>
    </div>
    @endif
</div>

@include('visa_statuses.modals', [
    'visaRoute' => $visaRoute,
    'visaRenewalCategories' => $visaRenewalCategories,
    'selectedCategoryId' => $selectedCategoryId,
    'returnTo' => $indexUrl ?? $visaRenewalCategoryReturnUrl,
])

@include('visa_renewal_categories.settings_panel', [
    'categories' => collect(),
    'assignableStatuses' => $assignableStatuses ?? collect(),
    'returnTo' => $visaRenewalCategoryReturnUrl,
    'embeddedManager' => true,
    'hideTable' => true,
])

@php
$visaStatusEditUrlTemplate = $companySlugForVisa
    ? url('app/' . $companySlugForVisa . '/settings-panel/visa-statuses/__ID__')
    : url('visa-statuses/__ID__');
@endphp
<div id="visa-status-manager-config"
     data-edit-url-template="{{ $visaStatusEditUrlTemplate }}"
     hidden></div>
