@extends($layout ?? 'layouts.app')
@section('title', 'Recycle Bin')

@push('styles')
<style>
    .empty-state-icon .icon-wrapper {
        animation: pulse-scale 2s ease-in-out infinite;
    }

    @keyframes pulse-scale {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    .empty-state-icon .icon-wrapper:hover {
        transform: scale(1.1) !important;
        transition: transform 0.3s ease;
    }

    .btn-lg {
        transition: all 0.3s ease;
    }

    .btn-lg:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6) !important;
    }

    .cascade-arrow {
        transition: transform 0.3s ease;
    }

    tr:hover .cascade-arrow {
        transform: translateX(5px);
        color: #667eea !important;
    }

    /* Riders-style table improvements */
    .table-striped tbody tr {
        transition: all 0.2s ease;
    }

    .table-striped tbody tr:hover {
        background-color: #f8f9fa !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .dataTable thead th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        padding: 12px 8px;
    }

    .dataTable tbody td {
        vertical-align: middle;
        padding: 12px 8px;
    }

    .avatar-wrapper {
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .dropdown-menu {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid #e0e0e0;
    }

    .dropdown-item:hover {
        background-color: #f5f5f5;
    }

    .badge {
        padding: 6px 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
@php $trashRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.trash' : 'trash'; @endphp
<div class="container-fluid">
    <!-- Header -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">
                        <i class="fa fa-trash-o text-danger"></i> Recycle Bin
                    </h4>
                    <p class="text-muted mb-0">
                        <small>View and restore deleted records from all modules</small>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-info fs-6">
                        <i class="fa fa-database"></i> {{ $totalCount }} Total Items
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route($trashRoute . '.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Module</label>
                    <select name="module" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ $currentModule == 'all' ? 'selected' : '' }}>All Modules</option>
                        @foreach($modules as $key => $config)
                        <option value="{{ $key }}" {{ $currentModule == $key ? 'selected' : '' }}>
                            {{ $config['name'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search in deleted records..." value="{{ $searchQuery }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('flash::message')

    {{-- DEBUG: Temporary - Remove after testing --}}
    @if(config('app.debug'))
    <!-- <div class="alert alert-info">
        <strong>Debug Info (only visible in debug mode):</strong>
        @foreach($trashedRecords as $debugItem)
        <div class="mt-2">
            <strong>{{ $debugItem['module_name'] }} #{{ $debugItem['id'] }}</strong><br>
            - caused_by: {{ $debugItem['caused_by'] ? 'YES (' . class_basename($debugItem['caused_by']->primary_model) . ' #' . $debugItem['caused_by']->primary_id . ')' : 'NO' }}<br>
            - cascaded_to: {{ $debugItem['cascaded_to'] ? count($debugItem['cascaded_to']) . ' records' : 'NONE' }}
            @if($debugItem['cascaded_to'] && count($debugItem['cascaded_to']) > 0)
            <br>&nbsp;&nbsp;└─
            @foreach($debugItem['cascaded_to'] as $c)
            {{ class_basename($c->related_model) }} #{{ $c->related_id }}{{ !$loop->last ? ', ' : '' }}
            @endforeach
            @endif
        </div>
        @endforeach
    </div> -->
    @endif

    <!-- Deleted Records -->
    @if(count($trashedRecords) > 0)
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                @php
                // Check if all records are from the same module and if a module-specific table view exists
                $firstModule = count($trashedRecords) > 0 ? $trashedRecords[0]['module'] : null;
                $allSameModule = count($trashedRecords) > 0 && collect($trashedRecords)->every(function($item) use ($firstModule) {
                return isset($item['module']) && $item['module'] == $firstModule;
                });
                // Use currentModule if filtering by specific module, otherwise use firstModule
                $moduleToCheck = ($currentModule != 'all' && $currentModule) ? $currentModule : $firstModule;
                $moduleTableView = $moduleToCheck && $allSameModule ? 'trash.' . $moduleToCheck . '_table' : null;
                $moduleTableViewExists = $moduleTableView && view()->exists($moduleTableView);
                @endphp
                @if($moduleTableViewExists)
                {{-- Render module-specific table that mirrors the original module structure --}}
                @include($moduleTableView, [
                'trashedRecords' => $trashedRecords,
                'totalCount' => $totalCount,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'modules' => $modules,
                'currentModule' => $currentModule,
                'tableColumns' => $tableColumns ?? []
                ])
                @else
                {{-- Generic table for other modules --}}
                <table class="table table-striped dataTable no-footer" id="dataTableBuilder">
                    <thead class="text-center">
                        <tr role="row">
                            <th title="Date" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending">Date (Deleted)</th>
                            <th title="Record ID" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Record ID: activate to sort column ascending">Record ID</th>
                            @php
                            // Get the first record to determine display columns
                            $firstRecord = count($trashedRecords) > 0 ? $trashedRecords[0] : null;
                            $displayColumns = $firstRecord && isset($firstRecord['display_columns']) ? $firstRecord['display_columns'] : [];
                            $maxColumns = min(5, count($displayColumns)); // Show up to 5 display columns
                            // If no records, set default columns
                            if ($maxColumns == 0) {
                            $maxColumns = 3; // Default to 3 columns
                            $displayColumns = ['name', 'email', 'contact_number']; // Placeholder columns
                            }
                            @endphp
                            @for($i = 0; $i < $maxColumns; $i++)
                                @if(isset($displayColumns[$i]))
                                <th title="{{ ucfirst(str_replace('_', ' ', $displayColumns[$i])) }}" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="{{ ucfirst(str_replace('_', ' ', $displayColumns[$i])) }}: activate to sort column ascending">{{ ucfirst(str_replace('_', ' ', $displayColumns[$i])) }}</th>
                                @endif
                                @endfor
                                <th title="File" class="sorting_disabled" rowspan="1" colspan="1" aria-label="File">File</th>
                                <th title="Deleted By" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Deleted By: activate to sort column ascending">Deleted By</th>
                                <th title="Actions" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        @if(count($trashedRecords) > 0)
                        @foreach($trashedRecords as $item)
                        <tr class="text-center">
                            <!-- Date (Deleted) -->
                            <td>{{ \App\Helpers\Common::DateFormat($item['deleted_at']) }}</td>

                            <!-- Record ID (clickable like Voucher ID) -->
                            <td>
                                <a href="javascript:void(0);" @if($item['module']=='vouchers' ) data-action="{{ route($trashRoute . '.show', ['vouchers', $item['id']]) }}" data-title="Voucher Details - {{ $item['id'] }} (Deleted)" data-size="xl" @endif class="text-primary show-modal">{{ $item['module_name'] }} #{{ $item['id'] }}</a>
                            </td>

                            <!-- Display Columns -->
                            @for($i = 0; $i < $maxColumns; $i++)
                                @if(isset($displayColumns[$i]))
                                <td>
                                @php
                                $column = $displayColumns[$i];
                                // Check if this record has the same display columns structure
                                $hasColumn = isset($item['display_columns']) && in_array($column, $item['display_columns']);
                                $value = $hasColumn && isset($item['record']->$column) ? $item['record']->$column : null;
                                @endphp
                                @if($value)
                                @if($column == 'billing_month')
                                {{ strtoupper(date('M-y', strtotime($value))) }}
                                @elseif(is_numeric($value) && (strpos($column, 'amount') !== false || strpos($column, 'price') !== false || strpos($column, 'balance') !== false || strpos($column, 'cost') !== false))
                                <span class="text-end">{{ number_format($value, 2) }}</span>
                                @elseif(strpos($column, 'status') !== false || strpos($column, 'type') !== false)
                                @if($value == 1 || $value == '1' || strtolower($value) == 'active')
                                <span class="badge bg-success">Active</span>
                                @elseif($value == 0 || $value == '0' || strtolower($value) == 'inactive')
                                <span class="badge bg-danger">Inactive</span>
                                @else
                                <span class="badge bg-primary">{{ $value }}</span>
                                @endif
                                @else
                                {{ $value }}
                                @endif
                                @else
                                <span class="text-muted">-</span>
                                @endif
                                </td>
                                @endif
                                @endfor
                                <!-- File -->
                                <td>
                                    @if($item['module']=='vouchers' )
                                    <a href="{{ url('storage/' . $item['record']->attach_file) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fa fa-file"></i> View
                                    </a>
                                    @endif
                                </td>
                                <!-- Deleted By -->
                                <td>
                                    @if($item['deleted_by_user'])
                                    {{ \App\Helpers\Common::UserName($item['deleted_by_user']->id) }}
                                    @else
                                    <span class="badge bg-label-secondary">
                                        <i class="fa fa-cog"></i> System
                                    </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td style="position: relative;">
                                    <div class="dropdown">
                                        <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $item['id'] }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $item['id'] }}" style="z-index: 1050;">
                                            @if($item['can_restore'])
                                            <a href="javascript:void(0);" class="dropdown-item waves-effect restore-item" data-form-id="restore-form-{{ $item['module'] }}-{{ $item['id'] }}">
                                                <i class="fa fa-undo text-success my-1"></i> Restore
                                            </a>
                                            <form id="restore-form-{{ $item['module'] }}-{{ $item['id'] }}" action="{{ route($trashRoute . '.restore', [$item['module'], $item['id']]) }}" method="POST" style="display: none;">
                                                @csrf
                                            </form>
                                            @endif

                                            @if($item['can_force_delete'])
                                            <a href="javascript:void(0);" class="dropdown-item waves-effect delete-item" data-form-id="delete-form-{{ $item['module'] }}-{{ $item['id'] }}">
                                                <i class="fa fa-trash-o text-danger my-1"></i> Delete Forever
                                            </a>
                                            <form id="delete-form-{{ $item['module'] }}-{{ $item['id'] }}" action="{{ route($trashRoute . '.force-destroy', [$item['module'], $item['id']]) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="{{ 2 + $maxColumns + 2 }}" class="text-center">
                                <div class="py-4">
                                    <i class="fa fa-info-circle text-muted"></i>
                                    <p class="text-muted mb-0">No deleted records found</p>
                                </div>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    @if(!$moduleTableViewExists)
    <script>
        // Initialize Bootstrap dropdowns when this content is loaded
        (function() {
            console.log('Generic trash table content loaded, initializing dropdowns');

            // Wait for Bootstrap to be available
            var attempts = 0;
            var maxAttempts = 10;

            function tryInitialize() {
                attempts++;

                if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                    // Initialize Bootstrap 5 dropdowns for this content
                    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                    var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                        try {
                            return new bootstrap.Dropdown(dropdownToggleEl);
                        } catch (e) {
                            console.warn('Failed to initialize dropdown in table:', e);
                            return null;
                        }
                    }).filter(Boolean);

                    console.log('Dropdowns initialized in generic trash table:', dropdownList.length);
                } else if (attempts < maxAttempts) {
                    console.log('Bootstrap not ready in table, retrying...', attempts);
                    setTimeout(tryInitialize, 100);
                } else {
                    console.warn('Bootstrap dropdown initialization failed in table after', maxAttempts, 'attempts');
                }
            }

            // Use DOMContentLoaded or run immediately if DOM is already loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(tryInitialize, 100);
                });
            } else {
                setTimeout(tryInitialize, 100);
            }
        })();
    </script>
    @endif
</div>

<!-- Pagination (only show for generic table, module-specific tables have their own pagination) -->
@php
$firstModule = count($trashedRecords) > 0 ? $trashedRecords[0]['module'] : null;
$allSameModule = count($trashedRecords) > 0 && collect($trashedRecords)->every(function($item) use ($firstModule) {
return isset($item['module']) && $item['module'] == $firstModule;
});
$moduleTableView = $firstModule && $allSameModule ? 'trash.' . $firstModule . '_table' : null;
$moduleTableViewExists = $moduleTableView && view()->exists($moduleTableView);
@endphp
@if(!$moduleTableViewExists)
<div class="card mt-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <!-- Left side: Records info and Show entries dropdown -->
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="text-muted">
                    Showing {{ count($trashedRecords) }} of {{ $totalCount }} entries
                </span>
                <div class="d-flex align-items-center gap-2">
                    <label for="perPageSelect" class="form-label mb-0">Show:</label>
                    <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;">
                        @php
                        // Normalize perPage for comparison (handle both string and integer)
                        $currentPerPage = (string)$perPage;
                        @endphp
                        <option value="10" {{ $currentPerPage === '10' ? 'selected' : '' }}>10</option>
                        <option value="20" {{ ($currentPerPage === '20' || ($currentPerPage !== '10' && $currentPerPage !== '50' && $currentPerPage !== '100' && $currentPerPage !== 'all' && is_numeric($currentPerPage))) ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $currentPerPage === '50' ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $currentPerPage === '100' ? 'selected' : '' }}>100</option>
                        <option value="all" {{ $currentPerPage === 'all' ? 'selected' : '' }}>All ({{ $totalCount }})</option>
                    </select>
                </div>
            </div>

            <!-- Right side: Pagination -->
            @if($totalPages > 1)
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ route($trashRoute . '.index', array_merge(request()->except('page'), ['page' => $currentPage - 1])) }}">
                            Previous
                        </a>
                    </li>

                    @for($i = 1; $i <= $totalPages; $i++)
                        <li class="page-item {{ $currentPage == $i ? 'active' : '' }}">
                        <a class="page-link" href="{{ route($trashRoute . '.index', array_merge(request()->except('page'), ['page' => $i])) }}">
                            {{ $i }}
                        </a>
                        </li>
                        @endfor

                        <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ route($trashRoute . '.index', array_merge(request()->except('page'), ['page' => $currentPage + 1])) }}">
                                Next
                            </a>
                        </li>
                </ul>
            </nav>
            @endif
        </div>
    </div>
</div>
<script>
    // Handle "Show entries" dropdown change - inline script to ensure it runs after element is rendered
    (function() {
        function setupPerPageSelect() {
            const perPageSelect = document.getElementById('perPageSelect');
            if (perPageSelect && !perPageSelect.dataset.listenerAttached) {
                perPageSelect.dataset.listenerAttached = 'true';
                perPageSelect.addEventListener('change', function() {
                    const perPage = this.value;
                    const url = new URL(window.location.href);

                    // Update or add per_page parameter
                    url.searchParams.set('per_page', perPage);

                    // Reset to page 1 when changing per_page
                    url.searchParams.set('page', '1');

                    // Redirect to new URL
                    window.location.href = url.toString();
                });
            }
        }

        // Try immediately and also on DOM ready
        setupPerPageSelect();
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupPerPageSelect);
        } else {
            setTimeout(setupPerPageSelect, 100);
        }
    })();
</script>
@endif

@else
<!-- Empty State -->
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <div class="empty-state-icon mb-4">
            <div class="icon-wrapper mx-auto" style="width: 120px; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);">
                <i class="fa fa-check-circle text-white" style="font-size: 4rem;"></i>
            </div>
        </div>

        <h3 class="mt-4 mb-3" style="color: #2d3748; font-weight: 600;">
            @if($currentModule != 'all')
            No Deleted {{ ucfirst($currentModule) }} Found
            @elseif($searchQuery)
            No Results Found
            @else
            Recycle Bin is Empty
            @endif
        </h3>

        <p class="text-muted mb-4" style="font-size: 1.1rem; max-width: 500px; margin: 0 auto;">
            @if($currentModule != 'all')
            There are no deleted {{ $currentModule }} in the recycle bin. All {{ $currentModule }} records are active.
            @elseif($searchQuery)
            No deleted records match your search query "<strong>{{ $searchQuery }}</strong>". Try different keywords or clear the search.
            @else
            Great! Your recycle bin is completely empty. All records are active and no deletions have been made recently.
            @endif
        </p>

        @if($currentModule != 'all' || $searchQuery)
        <div class="mt-4">
            <a href="{{ route($trashRoute . '.index') }}" class="btn btn-primary btn-lg px-5 py-3" style="border-radius: 50px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
                <i class="fa fa-refresh me-2"></i> View All Deleted Records
            </a>
        </div>
        @else
        <div class="mt-4 p-4 bg-light rounded" style="max-width: 600px; margin: 0 auto;">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-info-circle text-primary me-2 mt-1"></i>
                        <div>
                            <strong>Soft Delete Protection</strong>
                            <p class="text-muted small mb-0">Deleted records are stored here for 90 days before permanent removal.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-undo text-success me-2 mt-1"></i>
                        <div>
                            <strong>Easy Recovery</strong>
                            <p class="text-muted small mb-0">Restore deleted records with one click anytime.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-shield text-warning me-2 mt-1"></i>
                        <div>
                            <strong>Data Safety</strong>
                            <p class="text-muted small mb-0">Protected from accidental permanent deletion.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-history text-info me-2 mt-1"></i>
                        <div>
                            <strong>Full Audit Trail</strong>
                            <p class="text-muted small mb-0">Track who deleted what and when.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endif

<!-- Info Box -->
<div class="alert alert-info mt-3">
    <i class="fa fa-info-circle"></i>
    <strong>How it works:</strong>
    <ul class="mb-0 mt-2">
        <li>Deleted records are kept in the recycle bin for 90 days (configurable)</li>
        <li>You can restore any record with the <span class="badge bg-success">Restore</span> button</li>
        <li>Permanently deleted records cannot be recovered</li>
        <li>Only users with proper permissions can see and manage deleted records</li>
        <li><strong>Cascade Info Column:</strong> Shows which deletion caused this record to be deleted, or what other records were deleted because of this one</li>
    </ul>
</div>
</div>
@endsection

@section('page-script')
<script>
    (function() {
        // Wait for DOM to be ready
        function init() {
            // Auto-submit form when module filter changes
            const moduleSelect = document.querySelector('select[name="module"]');
            if (moduleSelect) {
                moduleSelect.addEventListener('change', function() {
                    this.form?.submit();
                });
            }
        }

        // Run when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            // DOM is already ready
            init();
        }

        async function handleActionClick(event, selector, confirmMessage) {
            const trigger = event.target.closest(selector);
            if (!trigger) return;
            event.preventDefault();
            event.stopPropagation();

            const formId = trigger.dataset.formId;
            if (!formId) return;
            const form = document.getElementById(formId);
            if (!form) return;

            const ok = confirm(confirmMessage);
            if (!ok) return;

            const action = form.getAttribute('action');
            const method = (form.getAttribute('method') || 'POST').toUpperCase();
            const formData = new FormData(form);

            // Respect method spoofing
            const override = formData.get('_method');
            const fetchMethod = override ? override.toUpperCase() : method;

            // Show loading indicator
            const originalText = trigger.innerHTML;
            trigger.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
            trigger.disabled = true;

            try {
                const response = await fetch(action, {
                    method: fetchMethod === 'GET' ? 'POST' : fetchMethod,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': formData.get('_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json',
                    },
                    body: fetchMethod === 'GET' ? null : formData,
                });

                let data = {};
                try {
                    data = await response.json();
                } catch (e) {
                    // If response is not JSON, try to get text
                    const text = await response.text();
                    if (response.ok) {
                        data = {
                            success: true,
                            message: 'Action completed successfully.'
                        };
                    } else {
                        data = {
                            success: false,
                            message: text || 'Action failed. Please try again.'
                        };
                    }
                }

                if (!response.ok || data.success === false) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.message || 'Action failed. Please try again.');
                    } else {
                        alert(data.message || 'Action failed. Please try again.');
                    }
                    trigger.innerHTML = originalText;
                    trigger.disabled = false;
                    return;
                }

                // Show success message
                if (typeof toastr !== 'undefined') {
                    toastr.success(data.message || 'Action completed successfully.');
                } else {
                    alert(data.message || 'Action completed successfully.');
                }

                // Reload the page to refresh the table
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } catch (err) {
                console.error('Error:', err);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Action failed. Please try again.');
                } else {
                    alert('Action failed. Please try again.');
                }
                trigger.innerHTML = originalText;
                trigger.disabled = false;
            }

        }

        // Handle restore and delete clicks
        document.addEventListener('click', function(event) {
            if (event.target.closest('.restore-item')) {
                handleActionClick(event, '.restore-item', 'Are you sure you want to restore this record?');
            }
            if (event.target.closest('.delete-item')) {
                handleActionClick(event, '.delete-item', 'WARNING! This will PERMANENTLY delete this record. This action CANNOT be undone! Are you absolutely sure?');
            }
        });
    })();
</script>
@endsection