@extends($layout ?? 'layouts.app')

@push('third_party_stylesheets')
<style>
    .filter-sidebar {
        position: fixed;
        top: 0;
        right: -380px;
        width: 380px;
        max-width: 100%;
        height: 100%;
        background: #fff;
        box-shadow: -2px 0 12px rgba(0, 0, 0, .1);
        z-index: 1051;
        transition: right .3s ease;
        overflow-y: auto;
        border-left: 1px solid #dee2e6;
    }

    .filter-sidebar.open {
        right: 0;
    }

    .filter-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .4);
        z-index: 1050;
        opacity: 0;
        visibility: hidden;
        transition: opacity .2s, visibility .2s;
    }

    .filter-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #eee;
        background: #f8f9fa;
    }

    .filter-body {
        padding: 1rem;
    }

    .filter-sidebar .btn-close {
        box-shadow: none;
    }

    .visa-workspace {
        display: grid;
        grid-template-columns: minmax(280px, 340px) 1fr;
        gap: 1.25rem;
        align-items: start;
    }

    @media (max-width: 991.98px) {
        .visa-workspace {
            grid-template-columns: 1fr;
        }
    }

    .visa-panel {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 0.85rem;
        box-shadow: 0 1px 6px rgba(15, 23, 42, .05);
        overflow: hidden;
    }

    .visa-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid #eef1f4;
        background: linear-gradient(180deg, #fbfcfe 0%, #fff 100%);
    }

    .visa-panel-header h4 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 650;
    }

    .visa-panel-subtitle {
        margin: .2rem 0 0;
        color: #6c757d;
        font-size: .8rem;
    }

    .visa-cat-list {
        padding: .65rem;
        max-height: calc(100vh - 260px);
        overflow-y: auto;
    }

    .visa-cat-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        width: 100%;
        padding: .8rem .85rem;
        margin-bottom: .4rem;
        border: 1px solid transparent;
        border-radius: .65rem;
        background: #f8f9fb;
        color: inherit;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
        transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
    }

    .visa-cat-item:hover {
        background: #eef3fb;
        color: inherit;
    }

    .visa-cat-item.active {
        background: rgba(13, 110, 253, .08);
        border-color: rgba(13, 110, 253, .28);
        box-shadow: inset 3px 0 0 var(--bs-primary);
    }

    .visa-cat-item .visa-cat-icon {
        width: 36px;
        height: 36px;
        border-radius: .5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        color: var(--bs-primary);
        flex-shrink: 0;
    }

    .visa-cat-item.active .visa-cat-icon {
        background: var(--bs-primary);
        color: #fff;
    }

    .visa-cat-meta {
        min-width: 0;
        flex: 1;
    }

    .visa-cat-name {
        display: block;
        font-weight: 600;
        line-height: 1.2;
    }

    .visa-cat-count {
        font-size: .75rem;
        color: #6c757d;
    }

    .visa-empty {
        padding: 2.75rem 1.5rem;
        text-align: center;
    }

    .visa-empty-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f4f8;
        color: #6c757d;
        font-size: 1.75rem;
    }

    .visa-cat-item .visa-icon-btn {
        width: 30px;
        height: 30px;
        background: #fff;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')
@php
$visaRoute = $visaRoute ?? ((View::shared('settings_panel') ?? false) ? 'settings-panel.visa-statuses' : 'visa-statuses');
$showCategoryManager = true;
$visaRenewalCategories = $visaRenewalCategories ?? collect();
$selectedCategoryId = (int) ($selectedCategoryId ?? 0);
$selectedCategory = $selectedCategory ?? $visaRenewalCategories->firstWhere('id', $selectedCategoryId);
$visaRenewalCategoryReturnUrl = $visaRenewalCategoryReturnUrl
    ?? route($visaRoute . '.index') . ($selectedCategoryId ? ('?category_id=' . $selectedCategoryId) : '');
$addStatusUrl = $selectedCategoryId
    ? route($visaRoute . '.create') . '?category_id=' . $selectedCategoryId
    : route($visaRoute . '.create');
@endphp
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-8">
                <h1 class="mb-1">Visa Categories</h1>
                <p class="text-muted mb-0">Create a visa category first, then add statuses under that category. Expense tickets are generated only from the selected category’s statuses.</p>
            </div>
        </div>
    </div>
</section>

<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Visa Statuses</h5>
        <button type="button" class="btn-close" id="closeSidebar" aria-label="Close"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ request()->url() }}" method="GET">
            <input type="hidden" name="category_id" id="filter_category_id" value="{{ $selectedCategoryId }}">
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

<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="visa-workspace">
        <aside class="visa-panel">
            <div class="visa-panel-header">
                <div>
                    <h4><i class="ti ti-category me-1"></i> Visa Categories</h4>
                    <p class="visa-panel-subtitle mb-0">Select a category to manage its statuses</p>
                </div>
                @can('visa_expense_create')
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createVisaRenewalCategoryModal">
                    <i class="ti ti-plus"></i>
                </button>
                @endcan
            </div>
            <div class="visa-cat-list" id="visa-category-nav">
                @forelse($visaRenewalCategories as $category)
                <div
                    class="visa-cat-item {{ (int) $category->id === $selectedCategoryId ? 'active' : '' }}"
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
                        </span>
                        <span class="visa-cat-count">{{ (int) ($category->visa_statuses_count ?? 0) }} status{{ (int) ($category->visa_statuses_count ?? 0) === 1 ? '' : 'es' }}</span>
                    </span>
                    <span class="d-flex align-items-center gap-1">
                        @can('visa_expense_edit')
                        <button type="button" class="visa-icon-btn visa-icon-btn-edit js-visa-renewal-edit-btn"
                            title="Edit category"
                            data-bs-toggle="modal"
                            data-bs-target="#editVisaRenewalCategoryModal"
                            data-id="{{ $category->id }}"
                            data-name="{{ $category->name }}"
                            data-display-order="{{ $category->display_order }}"
                            data-is-default="{{ $category->is_default ? 1 : 0 }}"
                            data-is-active="{{ $category->is_active ? 1 : 0 }}"
                            onclick="event.preventDefault(); event.stopPropagation();">
                            <i class="ti ti-pencil"></i>
                        </button>
                        @endcan
                        @can('visa_expense_delete')
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
                    </span>
                </div>
                @empty
                <div class="visa-empty">
                    <div class="visa-empty-icon"><i class="ti ti-folder-plus"></i></div>
                    <h5 class="mb-1">No visa categories yet</h5>
                    <p class="text-muted small mb-3">Create a visa category first. Statuses can only be added after a category exists.</p>
                    @can('visa_expense_create')
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
                        {{ $selectedCategory ? $selectedCategory->name . ' statuses' : 'Visa Statuses' }}
                    </h4>
                    <p class="visa-panel-subtitle mb-0" id="visa-status-subtitle">
                        @if($selectedCategory)
                        Tickets for this category will be generated from these statuses only.
                        @else
                        Select a visa category to view and manage its statuses.
                        @endif
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm openFilterSidebar" @disabled(! $selectedCategoryId)>
                        <i class="fa fa-search me-1"></i> Filter
                    </button>
                    @can('visa_expense_create')
                    <a class="btn btn-primary btn-sm js-visa-status-add-btn {{ $selectedCategoryId ? '' : 'disabled' }}" href="{{ $addStatusUrl }}" id="visa-add-status-btn">
                        Add Status
                    </a>
                    @endcan
                </div>
            </div>
            <div class="card-body table-responsive px-2 py-0" id="table-data">
                @if($selectedCategoryId)
                @include('visa_statuses.table', [
                    'visaStatuses' => $visaStatuses,
                    'visaRoute' => $visaRoute,
                    'visaStatusReturnTo' => $visaRenewalCategoryReturnUrl,
                    'selectedCategoryId' => $selectedCategoryId,
                ])
                @else
                <div class="visa-empty">
                    <div class="visa-empty-icon"><i class="ti ti-list"></i></div>
                    <h5 class="mb-1">Select a visa category</h5>
                    <p class="text-muted small mb-0">Statuses are created against a specific category and cannot be duplicated within that category.</p>
                </div>
                @endif
            </div>
        </section>
    </div>
</div>

@include('visa_renewal_categories.settings_panel', [
    'categories' => collect(),
    'returnTo' => $visaRenewalCategoryReturnUrl,
    'embeddedManager' => true,
    'hideTable' => true,
])
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script type="text/javascript">
    var visaIndexUrl = "{{ request()->url() }}";
    var selectedCategoryId = "{{ $selectedCategoryId }}";

    function confirmDelete(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.style.display = 'none';
                var csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                var method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function deleteVisaStatusAjax(url, triggerBtn) {
        return fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    _method: 'DELETE'
                })
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return {
                        ok: response.ok,
                        data: data
                    };
                }).catch(function() {
                    return {
                        ok: response.ok,
                        data: {
                            success: false,
                            message: 'Invalid server response.'
                        }
                    };
                });
            })
            .then(function(result) {
                if (!result.ok || !result.data || result.data.success !== true) {
                    throw new Error((result.data && result.data.message) ? result.data.message : 'Delete failed.');
                }
                var row = triggerBtn ? triggerBtn.closest('tr[data-id]') : null;
                if (row) {
                    row.remove();
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted',
                    text: result.data.message || 'Visa status deleted successfully.',
                    timer: 1600,
                    showConfirmButton: false
                });
                return result.data;
            });
    }

    function initSortable() {
        var tbody = document.getElementById('visa-statuses-tbody');
        if (!tbody || tbody.querySelectorAll('tr[data-id]').length === 0) return;
        var reorderUrl = '{{ route($visaRoute . ".reorder") }}';
        var token = '{{ csrf_token() }}';
        if (window.visaStatusSortable) {
            window.visaStatusSortable.destroy();
        }
        window.visaStatusSortable = new Sortable(tbody, {
            handle: '.visa-drag-handle',
            animation: 150,
            ghostClass: 'table-warning',
            onEnd: function(evt) {
                var rows = tbody.querySelectorAll('tr[data-id]');
                var order = Array.from(rows).map(function(row) {
                    return row.getAttribute('data-id');
                });
                fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            order: order
                        })
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            var toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });
                            toast.fire({
                                icon: 'success',
                                title: 'Order saved.'
                            });
                            var idx = 1;
                            rows.forEach(function(row) {
                                var orderCell = row.cells[7];
                                if (orderCell) orderCell.textContent = idx++;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Could not save order.'
                            });
                        }
                    })
                    .catch(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not save order.'
                        });
                    });
            }
        });
    }

    function loadCategoryStatuses(categoryId, pushState) {
        $('#loading-overlay').show();
        selectedCategoryId = String(categoryId || '');
        $('#filter_category_id').val(selectedCategoryId);
        var url = visaIndexUrl + (selectedCategoryId ? ('?category_id=' + encodeURIComponent(selectedCategoryId)) : '');
        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                $('#table-data').html(data.tableData);
                if (data.addStatusUrl) {
                    $('#visa-add-status-btn').attr('href', data.addStatusUrl).removeClass('disabled');
                }
                $('.visa-cat-item').removeClass('active');
                $('.visa-cat-item[data-category-id="' + selectedCategoryId + '"]').addClass('active');
                var nameEl = $('.visa-cat-item.active .visa-cat-name').clone();
                nameEl.find('.badge').remove();
                var catName = $.trim(nameEl.text()) || 'Visa';
                $('#visa-status-heading').html('<i class="ti ti-list-check me-1"></i> ' + catName + ' statuses');
                $('#visa-status-subtitle').text('Tickets for this category will be generated from these statuses only.');
                if (pushState !== false) {
                    history.pushState(null, '', url);
                }
                initSortable();
                $('#loading-overlay').hide();
            },
            error: function() {
                $('#loading-overlay').hide();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initSortable();

        $(document).on('click', '.visa-cat-item', function(e) {
            if ($(e.target).closest('button').length) {
                return;
            }
            e.preventDefault();
            var categoryId = this.getAttribute('data-category-id');
            if (!categoryId) return;
            loadCategoryStatuses(categoryId, true);
        });

        $(document).on('click', '.js-visa-status-delete-btn', function(e) {
            e.preventDefault();
            var btn = this;
            var deleteUrl = btn.getAttribute('data-delete-url') || '';
            if (!deleteUrl) return;
            Swal.fire({
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                deleteVisaStatusAjax(deleteUrl, btn).catch(function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: (err && err.message) ? err.message : 'Could not delete visa status.'
                    });
                });
            });
        });

        $(document).on('click', '.openFilterSidebar', function(e) {
            e.preventDefault();
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
        });
        $('#closeSidebar, #filterOverlay').on('click', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });

        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            $('#loading-overlay').show();
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');

            var formData = $(this).serialize();
            var url = formData ? visaIndexUrl + '?' + formData : visaIndexUrl;

            $.ajax({
                url: url,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(data) {
                    $('#table-data').html(data.tableData);
                    history.pushState(null, '', url);
                    initSortable();
                    $('#loading-overlay').hide();
                },
                error: function() {
                    $('#loading-overlay').hide();
                }
            });
        });

        $('#category, #status, #is_required').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: 'Select'
        });
    });
</script>
@include('visa_renewal_categories.settings_script')
@endsection
