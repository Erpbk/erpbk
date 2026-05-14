@extends('layouts.app')
@section('title','Riders')

@push('third_party_stylesheets')
{{-- SortableJS for drag and drop functionality --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<style></style>
@endpush
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div>
        <!-- Enhanced Fleet Supervisor Accordion Section -->
        <div class="filter-tabs-section mb-4" id="filter-tabs-section">
            <div class="d-flex justify-content-between">
                @php
                $activeFiltersCount = count(request('rider_status', []));

                // Helper function to toggle rider status in URL
                if (!function_exists('toggleRiderStatus')) {
                function toggleRiderStatus($status) {
                $currentStatuses = request('rider_status', []);
                $newStatuses = $currentStatuses;

                if (in_array($status, $currentStatuses)) {
                // Remove the status
                $newStatuses = array_diff($currentStatuses, [$status]);
                } else {
                // Add the status
                $newStatuses[] = $status;
                }

                $queryParams = request()->query();
                $queryParams['rider_status'] = array_values($newStatuses);

                return request()->fullUrlWithQuery($queryParams);
                }
                }

                // Helper function to toggle balance filter in URL
                if (!function_exists('toggleBalanceFilter')) {
                function toggleBalanceFilter() {
                $queryParams = request()->query();


                return request()->fullUrlWithQuery($queryParams);
                }
                }
                @endphp


                <div class="filter-tabs">
                    @if($activeFiltersCount > 0)
                    <div class="filter-status">
                        <div class="filter-info">
                            <i class="ti ti-filter"></i>
                            <span>{{ $activeFiltersCount }} filter{{ $activeFiltersCount > 1 ? 's' : '' }} applied</span>
                            <a href="{{ route('riders.index') }}" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="ti ti-x"></i>
                                Clear All
                            </a>
                        </div>
                    </div>
                    @endif
                    <a href="{{ route('riders.index') }}" class="filter-tab {{ !request('rider_status') ? 'active' : '' }}">
                        <i class="ti ti-users"></i>
                        All Riders
                    </a>
                </div>
                <div class="fleet-supervisor-header-right d-flex align-items-center">
                    <div class="action-buttons">
                        <div class="action-dropdown-container">
                            <button class="action-dropdown-btn" id="addRiderDropdownBtn">
                                <i class="ti ti-plus"></i>
                                <span>Add Rider</span>
                                <i class="ti ti-chevron-down"></i>
                            </button>
                            <div class="action-dropdown-menu" id="addRiderDropdown">
                                @can('rider_create')
                                <a class="action-dropdown-item" href="{{ route('riders.create') }}">
                                    <i class="ti ti-user-plus"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Create New Rider</div>
                                        <div class="action-dropdown-item-desc">Add a new rider to the system</div>
                                    </div>
                                </a>
                                @endcan
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Today Attendance" data-action="{{ route('rider.attendance_import') }}">
                                    <i class="ti ti-calendar-check"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Import Today Attendance</div>
                                        <div class="action-dropdown-item-desc">Import attendance data for today</div>
                                    </div>
                                </a>
                                <!-- <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Keeta Rider Activities" data-action="{{ route('rider.keeta_activities_import') }}">
                                    <i class="ti ti-activity"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Import Keeta Activities</div>
                                        <div class="action-dropdown-item-desc">Import Keeta rider activity data</div>
                                    </div>
                                </a> -->
                                <a class="action-dropdown-item" href="{{ route('rider.exportRiders') }}">
                                    <i class="ti ti-file-export"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Export Riders</div>
                                        <div class="action-dropdown-item-desc">Export rider data to Excel</div>
                                    </div>
                                </a>
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Rider Vouchers" data-action="{{ route('riders.import_rider_vouchers', ['modal' => 1]) }}">
                                    <i class="ti ti-file-spreadsheet"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Import Rider Vouchers (Page)</div>
                                        <div class="action-dropdown-item-desc">Open import modal</div>
                                    </div>
                                </a>
                                <a class="action-dropdown-item openColumnControlSidebar" href="javascript:void(0);" data-size="sm" data-title="Column Control">
                                    <i class="ti ti-columns"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Column Control</div>
                                        <div class="action-dropdown-item-desc">Open column control modal</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="fleet-supervisor-section">
            <div class="fleet-supervisor-accordion expanded" id="fleetSupervisorAccordion">
                <div class="fleet-supervisor-slider-container">
                    <div class="slider-controls">
                        <button class="slider-btn prev-btn" id="prevBtn" type="button">
                            <i class="ti ti-chevron-left"></i>
                        </button>
                        <div class="slider-indicators" id="sliderIndicators"></div>
                        <button class="slider-btn next-btn" id="nextBtn" type="button">
                            <i class="ti ti-chevron-right"></i>
                        </button>
                    </div>
                    <div class="fleet-supervisor-cards slider-track" id="sliderTrack">
                        @php
                        $riderTopCategories = \App\Models\RiderTopCategory::with(['options' => function($q){
                        $q->where('is_active', 1)->orderBy('display_order')->orderBy('id');
                        }])->where('show_in_top_bar', 1)->orderBy('display_order')->orderBy('id')->get();
                        $slideIndex = 0;
                        $hasRiderTopOptionColumn = \Illuminate\Support\Facades\Schema::hasColumn('riders', 'rider_top_option_id');
                        @endphp

                        @foreach($riderTopCategories as $category)
                        @foreach($category->options as $option)
                        <div class="fleet-supervisor-card @if((int)request('rider_top_option_id') === (int)$option->id) active filtered @endif" data-slide="{{ $slideIndex++ }}" onclick="filterByRiderTopOption('{{ $option->id }}')">
                            <h3 class="fleet-supervisor-name">{{ $option->name }}</h3>
                            <div class="small text-muted mb-1">{{ $category->name }}</div>
                            <div class="fleet-supervisor-stats">
                                <div class="fleet-stat active @if((int)request('rider_top_option_id') === (int)$option->id && in_array('active', request('rider_status', []))) active-selected @endif" onclick="event.stopPropagation(); filterByRiderTopOptionStatus('{{ $option->id }}', 'active')">
                                    <i class="fleet-stat-icon ti ti-user-check"></i>
                                    <span class="fleet-stat-label">Active</span>
                                    <span class="fleet-stat-value">{{ $hasRiderTopOptionColumn ? \App\Models\Riders::where('rider_top_option_id', $option->id)->where('status', 1)->whereHas('bikes', function($q) { $q->where('warehouse', 'Active'); })->count() : 0 }}</span>
                                </div>
                                <div class="fleet-stat inactive @if((int)request('rider_top_option_id') === (int)$option->id && in_array('inactive', request('rider_status', []))) active-selected @endif" onclick="event.stopPropagation(); filterByRiderTopOptionStatus('{{ $option->id }}', 'inactive')">
                                    <i class="fleet-stat-icon ti ti-user-x"></i>
                                    <span class="fleet-stat-label">Inactive</span>
                                    <span class="fleet-stat-value">{{ $hasRiderTopOptionColumn ? \App\Models\Riders::where('rider_top_option_id', $option->id)->where(function($q) { $q->where('status', 3)->orWhereDoesntHave('bikes', function($bikeQuery) { $bikeQuery->where('warehouse', 'Active'); }); })->count() : 0 }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @endforeach

                    </div>
                </div>
            </div>
        </div>

        <!-- Fleet Supervisor Continuous Ticker Script -->
        <script>
            setTimeout(function() {
                if (typeof initFleetSupervisorSlider === 'function') {
                    initFleetSupervisorSlider();
                }
            }, 150);
        </script>
        <!-- Filter Tabs Section -->

        <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
            <div class="filter-header">
                <h5>Filter Riders</h5>
                <button type="button" class="btn-close" id="closeSidebar"></button>
            </div>
            <div class="filter-body" id="searchTopbody">
                <form id="filterForm" action="{{ route('riders.index') }}" method="GET">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="id">Rider Id</label>
                            <input type="number" name="rider_id" class="form-control" placeholder="Filter By Rider ID" value="{{ request('rider_id') }}">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="name">Rider Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Filter By Name" value="{{ request('name') }}">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="customer_id">Filter by Customer</label>
                            <select class="form-control " id="customer_id" name="customer_id">
                                @php
                                $customerIds = DB::table('riders')
                                ->whereNotNull('customer_id')
                                ->where('customer_id', '!=', '')
                                ->pluck('customer_id')
                                ->unique();

                                $customers = DB::table('customers')
                                ->whereIn('id', $customerIds)
                                ->select('id', 'name')
                                ->get();
                                @endphp
                                <option value="" selected>Select</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="attandence">Filter by Attandence</label>
                            <select class="form-control " id="attendance" name="attendance">
                                @php
                                $attandence = DB::table('riders')
                                ->whereNotNull('attendance')
                                ->where('attendance', '!=', '')
                                ->select('attendance')
                                ->distinct()
                                ->pluck('attendance');
                                @endphp
                                <option value="" selected>Select</option>
                                @foreach($attandence as $att)
                                <option value="{{ $att }}" {{ request('attandence') == $att ? 'selected' : '' }}>{{ $att }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="bike_assignment_status">Filter by Bike Assignment</label>
                            <select class="form-control " id="bike_assignment_status" name="bike_assignment_status">
                                <option value="" selected>Select</option>
                                <option value="Active" {{ request('bike_assignment_status') == 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ request('bike_assignment_status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12 form-group text-center">
                            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Filter Overlay -->
        <div id="filterOverlay" class="filter-overlay"></div>
</section>
{{-- Include Column Control Panel --}}
@php
use Illuminate\Support\Facades\Schema;
// Build column-control list from Rider Settings assignments only.
$riderColumns = Schema::getColumnListing('riders');
$riderColumnsSet = array_flip($riderColumns);

// Columns to always exclude from manual column control.
$exclude = ['id', 'email', 'created_at', 'updated_at', 'company_id', 'account_id'];
$excludedSet = array_flip($exclude);

// Assigned fixed fields (from Rider Settings -> Rider Fields).
$assignedFixedColumns = \App\Models\RiderFieldCategoryAssignment::query()
->orderBy('display_order')
->orderBy('id')
->pluck('field_key')
->filter(function ($key) use ($riderColumnsSet, $excludedSet) {
return isset($riderColumnsSet[$key]) && !isset($excludedSet[$key]);
})
->values()
->all();

// Assigned custom fields (category-wise moved from settings).
$assignedCustomFields = \App\Models\RiderCustomField::query()
->whereNotNull('category_id')
->orderBy('display_order')
->orderBy('id')
->get(['id', 'label']);

// Merge only assigned DB-backed fields (unique, ordered).
$dbColumns = array_values(array_unique($assignedFixedColumns));
$preferredOrder = [
'rider_id',
'name',
'fleet_supervisor',
'customer_id',
'attendance',
'status',
];

$columns = [];
$added = [];
$makeTitle = function ($key) {
$customTitles = [
'doj' => 'Date of Joining',
'recruiter_id' => 'Recruiter',
];
return $customTitles[$key] ?? ucwords(str_replace('_', ' ', $key));
};

// Add preferred DB columns first
foreach ($preferredOrder as $key) {
if (in_array($key, $dbColumns)) {
$columns[] = ['data' => $key, 'title' => $makeTitle($key)];
$added[$key] = true;
}
}

// Add remaining DB columns
foreach ($dbColumns as $key) {
if (empty($added[$key])) {
$columns[] = ['data' => $key, 'title' => $makeTitle($key)];
}
}

// Add assigned custom fields (stored in riders.custom_field_values JSON).
foreach ($assignedCustomFields as $cf) {
$columns[] = [
'data' => 'custom_field_values.' . $cf->id,
'title' => trim((string) $cf->label) !== '' ? $cf->label : ('Custom Field #' . $cf->id),
];
}

// 3) Append special/computed columns used in UI
$columns = array_merge($columns, [
['data' => 'bike', 'title' => 'Bike'],
['data' => 'orders_sum', 'title' => 'Orders'],
['data' => 'days', 'title' => 'Days'],
['data' => 'balance', 'title' => 'Balance'],
['data' => 'action', 'title' => 'Actions'],
// Keep last two fixed utility columns for search and control icons
['data' => 'search', 'title' => 'Search'],
['data' => 'control', 'title' => 'Control'],
]);

$tableColumns = $columns;
@endphp
@include('components.column-control-panel', [
'tableColumns' => $tableColumns,
'exportRoute' => route('rider.exportCustomizableRiders'),
'tableIdentifier' => 'riders_table'
])
<div class="content">
    @include('flash::message')
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i> Filter Riders</button>
        </div>
        <div class="card-body table-responsive px-2 py-0">
            <div class="riders-table-container">
                @include('riders.table', ['data' => $data, 'tableColumns' => $tableColumns])
            </div>
            <div class="filter-loading-overlay" style="display: none;">
                <div class="filter-loading-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Applying filters...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    function confirmDelete(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this! The rider will be moved to Recycle Bin.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        })
    }
    // Make filter tabs section sticky on scroll
    $(document).ready(function() {
        const filterTabsSection = document.getElementById('filter-tabs-section');
        const originalWidth = filterTabsSection.offsetWidth;
        const originalPosition = filterTabsSection.getBoundingClientRect();
        const parentElement = filterTabsSection.parentElement;
        const parentPadding = parseInt(window.getComputedStyle(parentElement).paddingLeft) || 0;

        window.addEventListener('scroll', function() {
            const parentRect = parentElement.getBoundingClientRect();
            if (parentRect.top < 0) {
                filterTabsSection.classList.add('filter-tabs-fixed');
                filterTabsSection.style.width = originalWidth + 'px';
                filterTabsSection.style.left = (parentRect.left + parentPadding) + 'px';
            } else {
                filterTabsSection.classList.remove('filter-tabs-fixed');
                filterTabsSection.style.width = '';
                filterTabsSection.style.left = '';
            }
        });
    });
    $(document).ready(function() {
        $('#customer_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Customer",
            allowClear: true, // ✅ cross icon enable
        });
        $('#bike_assignment_status').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true, // ✅ cross icon enable
            placeholder: "Filter By status",
        });
        $('#attendance').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Attandence",
            allowClear: true, // ✅ cross icon enable
        });
    });
</script>
<script type="text/javascript">
    $(document).ready(function() {
        // Filter sidebar functionality - open on hover
        $(document).on('mouseenter', '#openFilterSidebar, .openFilterSidebar', function(e) {
            e.preventDefault();
            console.log('Filter button hovered!'); // Debug line
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
            return false;
        });

        // Keep the original click handler for mobile devices
        $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
            e.preventDefault();
            console.log('Filter button clicked!'); // Debug line
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
            return false;
        });

        $('#closeSidebar, #filterOverlay').on('click', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });
        $('#filterForm').on('submit', function(e) {
            // Let the form submit naturally - no need to prevent default
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });
        // Quick search input (main) - redirect to URL with search parameter
        $('#quickSearch').on('keyup', function(e) {
            if (e.keyCode === 13 || $(this).val().length === 0) {
                const searchValue = $(this).val();
                const url = new URL(window.location);

                if (searchValue) {
                    url.searchParams.set('quick_search', searchValue);
                } else {
                    url.searchParams.delete('quick_search');
                }

                window.location.href = url.toString();
            }
        });

        // Quick search input (sidebar) - redirect to URL with search parameter
        $('#quickSearchSidebar').on('keyup', function(e) {
            if (e.keyCode === 13 || $(this).val().length === 0) {
                const searchValue = $(this).val();
                const url = new URL(window.location);

                if (searchValue) {
                    url.searchParams.set('quick_search', searchValue);
                } else {
                    url.searchParams.delete('quick_search');
                }

                window.location.href = url.toString();
            }
        });
    });
</script>
<script>
    $(document).ready(function() {
        // Fleet Supervisor Accordion Toggle
        $('#fleetSupervisorToggle').on('click', function() {
            const accordion = $('#fleetSupervisorAccordion');
            const toggle = $(this);

            if (accordion.hasClass('expanded')) {
                accordion.removeClass('expanded').addClass('collapsed');
                toggle.addClass('collapsed');
            } else {
                accordion.removeClass('collapsed').addClass('expanded');
                toggle.removeClass('collapsed');
            }
        });

        // Add Rider Dropdown Toggle
        $('#addRiderDropdownBtn').on('click', function(e) {
            e.stopPropagation();
            const dropdown = $('#addRiderDropdown');
            const btn = $(this);

            if (dropdown.hasClass('show')) {
                dropdown.removeClass('show');
                btn.removeClass('open');
            } else {
                // Close other dropdowns
                $('.action-dropdown-menu').removeClass('show');
                $('.action-dropdown-btn').removeClass('open');
                // Show this dropdown
                dropdown.addClass('show');
                btn.addClass('open');
            }
        });

        // Close dropdowns when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.action-dropdown-container').length) {
                $('.action-dropdown-menu').removeClass('show');
                $('.action-dropdown-btn').removeClass('open');
            }
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#filterSidebar').length) {
                $('#filterSidebar').removeClass('open');
            }
        });

        // Close on scroll/resize to avoid misaligned fixed menu
        $(window).on('scroll resize', function() {
            const dropdown = $('#addRiderDropdown');
            if (dropdown.hasClass('show')) {
                dropdown.removeClass('show');
                $('#addRiderDropdownBtn').removeClass('open');
            }
        });

        // Fleet supervisor and balance filter cards now use direct links - no JavaScript needed
    });

    // Rider top option filtering function - shows both active and inactive
    function filterByRiderTopOption(optionId) {
        const url = new URL(window.location);

        // Clear existing filters
        url.searchParams.delete('rider_top_option_id');
        url.searchParams.delete('rider_status');
        url.searchParams.delete('rider_status[]');

        // Set rider top option filter
        url.searchParams.set('rider_top_option_id', optionId);

        // Set both active and inactive status
        url.searchParams.append('rider_status[]', 'active');
        url.searchParams.append('rider_status[]', 'inactive');

        // Redirect to filtered URL
        window.location.href = url.toString();
    }

    // Rider top option status filtering function - toggle specific status
    function filterByRiderTopOptionStatus(optionId, status) {
        const url = new URL(window.location);
        const currentOptionId = url.searchParams.get('rider_top_option_id');
        const currentStatuses = url.searchParams.getAll('rider_status[]');

        // If clicking the same rider top option and same status, toggle it off
        if (currentOptionId === String(optionId) && currentStatuses.includes(status)) {
            // Remove this specific status
            const newStatuses = currentStatuses.filter(s => s !== status);
            url.searchParams.delete('rider_status[]');
            newStatuses.forEach(s => url.searchParams.append('rider_status[]', s));

            // If no statuses left, remove rider top option filter entirely
            if (newStatuses.length === 0) {
                url.searchParams.delete('rider_top_option_id');
            }
        } else {
            // Set rider top option and specific status
            url.searchParams.set('rider_top_option_id', optionId);
            url.searchParams.delete('rider_status[]');
            url.searchParams.set('rider_status[]', status);
        }

        // Redirect to filtered URL
        window.location.href = url.toString();
    }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.querySelector('#dataTableBuilder');
        if (!table) return;
        const headers = table.querySelectorAll('th.sorting');
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        headers.forEach((header, colIndex) => {
            header.addEventListener('click', () => {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isAsc = header.classList.contains('sorted-asc');

                // Clear previous sort classes
                headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));

                // Add new sort direction
                header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');

                // Sort logic
                rows.sort((a, b) => {
                    let aText = a.children[colIndex]?.textContent.trim().toLowerCase();
                    let bText = b.children[colIndex]?.textContent.trim().toLowerCase();

                    const aVal = isNaN(aText) ? aText : parseFloat(aText);
                    const bVal = isNaN(bText) ? bText : parseFloat(bText);

                    if (aVal < bVal) return isAsc ? 1 : -1;
                    if (aVal > bVal) return isAsc ? -1 : 1;
                    return 0;
                });

                // Re-append sorted rows
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });
    const copyTextEl = document.querySelector('.copy-text');
    if (copyTextEl) {
        copyTextEl.addEventListener('click', function() {
            const valueEl = this.querySelector('.copy-value');
            const icon = this.querySelector('i');
            if (!valueEl) return;
            const value = valueEl.textContent.trim();

            navigator.clipboard.writeText(value).then(() => {
                if (!icon) return;
                icon.classList.remove('fa-copy');
                icon.classList.add('fa-check');
                setTimeout(() => {
                    icon.classList.remove('fa-check');
                    icon.classList.add('fa-copy');
                }, 1500);
            });
        });
    }

    // Status filter functionality is now handled by direct URL links

    // Add CSS for enhanced interactions
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .ripple-effect {
                animation: ripple 0.6s ease-out;
            }
            
            @keyframes ripple {
                0% { transform: scale(1); }
                50% { transform: scale(1.02); }
                100% { transform: scale(1); }
            }
            
            .hover-effect {
                animation: hover-pulse 0.3s ease-out;
            }
            
            @keyframes hover-pulse {
                0% { transform: translateY(-2px); }
                100% { transform: translateY(-2px); }
            }
            
            .click-effect {
                animation: click-bounce 0.2s ease-out;
            }
            
            @keyframes click-bounce {
                0% { transform: scale(1); }
                50% { transform: scale(0.98); }
                100% { transform: scale(1); }
            }
            
            .select2-result-item {
                display: flex;
                align-items: center;
                padding: 8px 0;
            }
            
            .option-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .option-icon {
                font-size: 18px;
                filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
            }
            
            .option-text {
                font-weight: 500;
                color: #374151;
            }
            
            .select2-results__option--highlighted .option-text {
                color: white;
            }
            
            .select2-results__option[aria-selected=true] .option-text {
                color: white;
            }
            
            .custom-scrollbar {
                scrollbar-width: thin;
                scrollbar-color: #3b82f6 #f1f5f9;
            }
            
            /* Fleet supervisor active/inactive button highlighting */
            .fleet-stat.active-selected {
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                transform: scale(1.05);
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                border: 2px solid #10b981;
            }
            
            .fleet-stat.active-selected .fleet-stat-icon {
                color: white;
            }
            
            .fleet-stat.active-selected .fleet-stat-label {
                color: white;
                font-weight: 600;
            }
            
            .fleet-stat.active-selected .fleet-stat-value {
                color: white;
                font-weight: 600;
            }
            
            /* Default Active button styling */
            .fleet-stat.active {
                background: linear-gradient(135deg, #d1fae5, #a7f3d0);
                border: 1px solid #10b981;
                color: #065f46;
            }
            
            .fleet-stat.active .fleet-stat-icon {
                color: #10b981;
            }
            
            .fleet-stat.active .fleet-stat-label {
                color: #065f46;
                font-weight: 500;
            }
            
            .fleet-stat.active .fleet-stat-value {
                color: #065f46;
                font-weight: 600;
            }
            
            /* Default Inactive button styling */
            .fleet-stat.inactive {
                background: linear-gradient(135deg, #fee2e2, #fecaca);
                border: 1px solid #ef4444;
                color: #991b1b;
            }
            
            .fleet-stat.inactive .fleet-stat-icon {
                color: #ef4444;
            }
            
            .fleet-stat.inactive .fleet-stat-label {
                color: #991b1b;
                font-weight: 500;
            }
            
            .fleet-stat.inactive .fleet-stat-value {
                color: #991b1b;
                font-weight: 600;
            }
            
            .fleet-stat:hover {
                background: rgba(16, 185, 129, 0.1);
                transform: translateY(-2px);
                transition: all 0.3s ease;
            }
            
            .fleet-stat {
                cursor: pointer;
                transition: all 0.3s ease;
                border-radius: 8px;
                padding: 8px 12px;
                margin: 4px 0;
            }
            
            /* Fleet supervisor card filtered state */
            .fleet-supervisor-card.filtered {
                background: linear-gradient(135deg, #e0f2fe, #b3e5fc);
                border: 2px solid #29b6f6;
                box-shadow: 0 4px 15px rgba(41, 182, 246, 0.2);
                transform: scale(1.02);
            }
            
            .fleet-supervisor-card.filtered .fleet-supervisor-name {
                color: #0277bd;
                font-weight: 600;
            }
            
            .fleet-supervisor-card.filtered .fleet-stat {
                background: rgba(255, 255, 255, 0.8);
                border-radius: 6px;
            }

            /* Fix Add Rider dropdown positioning in header */
            .fleet-supervisor-header-right { position: relative; overflow: visible; }
            .action-dropdown-container { position: relative; display: inline-block; }
            .action-dropdown-btn { display: inline-flex; align-items: center; gap: 8px; }
            .action-dropdown-menu {
                position: absolute;
                top: calc(100% + 8px);
                right: 0;
                min-width: 260px;
                max-width: 320px;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                box-shadow: 0 8px 24px rgba(0,0,0,0.12);
                padding: 8px 0;
                z-index: 3000;
                display: none;
            }
            .action-dropdown-menu.show { display: block; }
            .action-dropdown-item { display: flex; align-items: flex-start; gap: 12px; padding: 10px 14px; color: #111827; text-decoration: none; }
            .action-dropdown-item:hover { background: #f3f4f6; }
            .action-dropdown-item i { color: #2563eb; margin-top: 2px; }
            .action-dropdown-item-text { font-weight: 600; }
            .action-dropdown-item-desc { font-size: 12px; color: #6b7280; }
        `)
        .appendTo('head');

    function initFleetSupervisorSlider() {
        const sliderTrack = document.getElementById('sliderTrack');
        if (!sliderTrack || sliderTrack.dataset.tickerInit === '1') return;

        const cards = Array.from(sliderTrack.querySelectorAll('.fleet-supervisor-card'));
        if (!cards.length) return;

        const container = sliderTrack.closest('.fleet-supervisor-slider-container');
        if (container) container.classList.add('ticker-mode');

        sliderTrack.dataset.tickerInit = '1';
        if (cards.length < 2) return;

        let intervalId = null;
        let isAnimating = false;
        const computedTrackStyle = window.getComputedStyle(sliderTrack);
        const gap = parseFloat(computedTrackStyle.columnGap || computedTrackStyle.gap || '16') || 16;

        function slideNextCard() {
            if (isAnimating) return;
            const firstCard = sliderTrack.querySelector('.fleet-supervisor-card');
            if (!firstCard) return;
            isAnimating = true;

            const shiftAmount = firstCard.offsetWidth + gap;
            sliderTrack.style.transition = 'transform 520ms ease';
            sliderTrack.style.transform = 'translateX(-' + shiftAmount + 'px)';

            window.setTimeout(function() {
                sliderTrack.style.transition = 'none';
                sliderTrack.style.transform = 'translateX(0)';
                sliderTrack.appendChild(firstCard);
                // Force reflow so next animation starts cleanly.
                void sliderTrack.offsetWidth;
                isAnimating = false;
            }, 540);
        }

        intervalId = window.setInterval(slideNextCard, 2600);
        sliderTrack.dataset.tickerIntervalId = String(intervalId);
    }

    // Initialize slider when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing slider...');
        setTimeout(initFleetSupervisorSlider, 100);
    });

    // Also try to initialize when window loads
    window.addEventListener('load', function() {
        console.log('Window loaded, initializing slider...');
        setTimeout(initFleetSupervisorSlider, 100);
    });

    // Enhanced notification function
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="ti ti-${type === 'success' ? 'check' : type === 'error' ? 'x' : 'info'}"></i>
                <span>${message}</span>
            </div>
        `;

        const colors = {
            success: '#10b981',
            error: '#ef4444',
            info: '#3b82f6'
        };

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${colors[type] || '#3b82f6'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease;
            max-width: 300px;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
</script>
@endsection