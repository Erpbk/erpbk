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
@can('riders_rider_view')
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
                                <span>Riders Actions</span>
                                <i class="ti ti-chevron-down"></i>
                            </button>
                            <div class="action-dropdown-menu" id="addRiderDropdown">
                                @can('riders_rider_create')
                                <a class="action-dropdown-item" href="{{ route('riders.create') }}">
                                    <i class="ti ti-user-plus"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Create New Rider</div>
                                        <div class="action-dropdown-item-desc">Add a new rider to the system</div>
                                    </div>
                                </a>
                                @endcan
                                @can('riders_attendance_create')
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Today Attendance" data-action="{{ route('rider.attendance_import') }}">
                                    <i class="ti ti-calendar-check"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Import Today Attendance</div>
                                        <div class="action-dropdown-item-desc">Import attendance data for today</div>
                                    </div>
                                </a>
                                @endcan
                                <!-- <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Keeta Rider Activities" data-action="{{ route('rider.keeta_activities_import') }}">
                                    <i class="ti ti-activity"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Import Keeta Activities</div>
                                        <div class="action-dropdown-item-desc">Import Keeta rider activity data</div>
                                    </div>
                                </a> -->
                                @can('riders_export_data_create')
                                <a class="action-dropdown-item" href="{{ route('rider.exportRiders') }}">
                                    <i class="ti ti-file-export"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Export Riders</div>
                                        <div class="action-dropdown-item-desc">Export rider data to Excel</div>
                                    </div>
                                </a>
                                @endcan
                                @can('riders_voucher_create')
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Rider Vouchers" data-action="{{ route('riders.import_rider_vouchers', ['modal' => 1]) }}">
                                    <i class="ti ti-file-spreadsheet"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Import Rider Vouchers (Page)</div>
                                        <div class="action-dropdown-item-desc">Open import modal</div>
                                    </div>
                                </a>
                                @endcan
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


        <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
            <div class="filter-header">
                <h5>Filter Riders</h5>
                <button type="button" class="btn-close" id="closeSidebar"></button>
            </div>
            <div class="filter-body" id="searchTopbody">
                <form id="filterForm" action="{{ route('riders.index') }}" method="GET" data-rfp-skip-lock="1">
                    {{-- Preserve top-bar filters when applying sidebar filters --}}
                    @if(request()->filled('rider_top_option_id'))
                    <input type="hidden" name="rider_top_option_id" value="{{ request('rider_top_option_id') }}">
                    @endif
                    @foreach((array) request('rider_status', []) as $statusKey)
                    @if($statusKey !== null && $statusKey !== '')
                    <input type="hidden" name="rider_status[]" value="{{ $statusKey }}">
                    @endif
                    @endforeach
                    @if(request()->filled('quick_search'))
                    <input type="hidden" name="quick_search" value="{{ request('quick_search') }}">
                    @endif
                    <div class="row">
                        @fieldVisible('rider', 'rider_id')
                        <div class="form-group col-md-12">
                            <label for="id">Rider Id</label>
                            <input type="number" name="rider_id" class="form-control" placeholder="Filter By Rider ID" value="{{ request('rider_id') }}">
                        </div>
                        @endfieldVisible
                        @fieldVisible('rider', 'name')
                        <div class="form-group col-md-12">
                            <label for="name">Rider Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Filter By Name" value="{{ request('name') }}">
                        </div>
                        @endfieldVisible
                        @fieldVisible('rider', 'customer_id')
                        <div class="form-group col-md-12">
                            <label for="customer_id">Filter by Project</label>
                            <select class="form-control " id="customer_id" name="customer_id">
                                @php
                                $customerIds = company_table('riders')
                                ->whereNotNull('customer_id')
                                ->where('customer_id', '!=', '')
                                ->pluck('customer_id')
                                ->unique();

                                $customers = company_table('customers')
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
                        @endfieldVisible
                        @fieldVisible('rider', 'attendance')
                        <div class="form-group col-md-12">
                            <label for="attandence">Filter by Attandence</label>
                            <select class="form-control " id="attendance" name="attendance">
                                @php
                                $attandence = company_table('riders')
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
                        @endfieldVisible
                        <div class="form-group col-md-12">
                            <label for="bike_assignment_status">Filter by Status</label>
                            <select class="form-control " id="bike_assignment_status" name="bike_assignment_status">
                                <option value="" selected>Select</option>
                                <option value="Active" {{ request('bike_assignment_status') == 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ request('bike_assignment_status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                @foreach(($riderTopStatusFilterOptions ?? []) as $statusOption)
                                <option value="{{ $statusOption['value'] }}" {{ request('bike_assignment_status') == $statusOption['value'] ? 'selected' : '' }}>
                                    {{ $statusOption['label'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @fieldVisible('rider', 'fleet_supervisor')
                        <div class="form-group col-md-12">
                            <label for="fleet_supervisor">Filter by Supervisor</label>
                            <select class="form-control " id="fleet_supervisor" name="fleet_supervisor">
                                @php
                                $supervisors = company_table('riders')->select('fleet_supervisor')->distinct()->pluck('fleet_supervisor')->toArray();
                                @endphp
                                <option value="" selected>Select</option>
                                @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor }}" {{ request('fleet_supervisor') == $supervisor ? 'selected' : '' }}>{{ $supervisor }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endfieldVisible
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
{{-- Column list from RidersController::buildRidersIndexTableColumns() ($tableColumns) --}}
@include('components.column-control-panel', [
'tableColumns' => $tableColumns ?? [],
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
                @include('riders.table', ['data' => $data, 'tableColumns' => $tableColumns ?? []])
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
@else
<div class="card">
    <div class="card-body">
        <h5>You are not authorized to access this page</h5>
    </div>
</div>
@endcan
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
    $(document).ready(function() {
        $('#customer_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Project",
            allowClear: true, // ✅ cross icon enable
        });
        $('#bike_assignment_status').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true, // ✅ cross icon enable
            placeholder: "Filter By Bike/Top Status",
        });
        $('#attendance').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Attandence",
            allowClear: true, // ✅ cross icon enable
        });
        $('#fleet_supervisor').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true, // ✅ cross icon enable
            placeholder: "Filter By Supervisor",
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
<script>
/* ── Excel-like column copy ── */
(function () {
  'use strict';

  var COL_HEAD_SEL  = '#dataTableBuilder thead th.sorting';
  var COL_SELECTED  = 'riders-col-selected';
  var HEAD_SELECTED = 'riders-col-head-selected';

  /* ── styles ── */
  var css = `
    #dataTableBuilder thead th.${HEAD_SELECTED} {
      background: #1e3a8a !important;
      color: #ffffff !important;
      position: relative;
    }
    #dataTableBuilder thead th.${HEAD_SELECTED}::after {
      content: "Ctrl+C";
      position: absolute;
      bottom: 2px;
      right: 6px;
      font-size: 9px;
      font-weight: 400;
      opacity: .7;
      letter-spacing: .03em;
    }
    #dataTableBuilder td.${COL_SELECTED} {
      background: #dbeafe !important;
    }
    #dataTableBuilder thead th.sorting:not(.${HEAD_SELECTED}):hover {
      cursor: copy;
    }
    /* toast */
    #rdrs-col-toast {
      position: fixed;
      bottom: 36px;
      left: 50%;
      transform: translateX(-50%) translateY(12px);
      background: #1e293b;
      color: #fff;
      padding: 9px 20px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      z-index: 10000;
      display: flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 4px 18px rgba(0,0,0,.22);
      opacity: 0;
      pointer-events: none;
      transition: opacity .22s, transform .22s;
    }
    #rdrs-col-toast.rdrs-toast-show {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }
    /* context menu */
    #rdrs-col-ctx {
      position: fixed;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      box-shadow: 0 6px 20px rgba(0,0,0,.14);
      z-index: 10001;
      display: none;
      min-width: 190px;
      padding: 4px 0;
      user-select: none;
    }
    #rdrs-col-ctx.rdrs-ctx-show { display: block; }
    #rdrs-col-ctx button {
      display: flex; align-items: center; gap: 9px;
      width: 100%; padding: 8px 14px;
      border: none; background: none; cursor: pointer;
      font-size: 13px; color: #111827; text-align: left;
    }
    #rdrs-col-ctx button:hover { background: #f3f4f6; }
    #rdrs-col-ctx .ctx-divider { border-top: 1px solid #f0f0f0; margin: 3px 0; }
  `;
  var styleEl = document.createElement('style');
  styleEl.textContent = css;
  document.head.appendChild(styleEl);

  /* ── toast ── */
  var toast = document.createElement('div');
  toast.id = 'rdrs-col-toast';
  toast.innerHTML = '<i class="ti ti-check" style="font-size:15px;color:#4ade80;"></i><span id="rdrs-col-toast-msg"></span>';
  document.body.appendChild(toast);
  var toastTimer;
  function showToast(msg) {
    document.getElementById('rdrs-col-toast-msg').textContent = msg;
    toast.classList.add('rdrs-toast-show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('rdrs-toast-show'); }, 2400);
  }

  /* ── context menu ── */
  var ctx = document.createElement('div');
  ctx.id = 'rdrs-col-ctx';
  ctx.innerHTML = [
    '<button id="rdrs-ctx-copy"><i class="ti ti-copy"></i> Copy column (Ctrl+C)</button>',
    '<div class="ctx-divider"></div>',
    '<button id="rdrs-ctx-deselect"><i class="ti ti-x"></i> Deselect column (Esc)</button>',
  ].join('');
  document.body.appendChild(ctx);

  /* ── state ── */
  var selectedColIdx = -1;

  /* ── helpers ── */
  function getTable() { return document.querySelector('#dataTableBuilder'); }

  function clearSelection() {
    var t = getTable();
    if (!t) return;
    t.querySelectorAll('.' + HEAD_SELECTED).forEach(function (el) { el.classList.remove(HEAD_SELECTED); });
    t.querySelectorAll('.' + COL_SELECTED).forEach(function (el) { el.classList.remove(COL_SELECTED); });
    selectedColIdx = -1;
  }

  function selectColumn(colIdx) {
    var t = getTable();
    if (!t) return;
    clearSelection();
    selectedColIdx = colIdx;

    /* header */
    var allThs = t.querySelectorAll('thead th');
    if (allThs[colIdx]) allThs[colIdx].classList.add(HEAD_SELECTED);

    /* body cells */
    t.querySelectorAll('tbody tr').forEach(function (row) {
      var cell = row.children[colIdx];
      if (cell) cell.classList.add(COL_SELECTED);
    });
  }

  function getCellText(cell) {
    /* prefer links, then raw text; collapse whitespace */
    var link = cell.querySelector('a');
    var raw = (link ? link.textContent : cell.textContent).replace(/\s+/g, ' ').trim();
    return raw;
  }

  function buildColumnText(colIdx) {
    var t = getTable();
    if (!t) return '';
    var lines = [];
    t.querySelectorAll('tbody tr').forEach(function (row) {
      var cell = row.children[colIdx];
      lines.push(cell ? getCellText(cell) : '');
    });
    return lines.join('\n');
  }

  function doCopy(colIdx) {
    if (colIdx < 0) return;
    var text = buildColumnText(colIdx);
    var rowCount = text.split('\n').length;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        showToast(rowCount + ' row' + (rowCount !== 1 ? 's' : '') + ' copied to clipboard');
      }).catch(function () { fallbackCopy(text, rowCount); });
    } else {
      fallbackCopy(text, rowCount);
    }
  }

  function fallbackCopy(text, rowCount) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px;opacity:0;';
    document.body.appendChild(ta);
    ta.focus(); ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
    showToast(rowCount + ' row' + (rowCount !== 1 ? 's' : '') + ' copied to clipboard');
  }

  function hideCtx() { ctx.classList.remove('rdrs-ctx-show'); }

  function showCtx(x, y) {
    ctx.style.left = x + 'px';
    ctx.style.top  = y + 'px';
    ctx.classList.add('rdrs-ctx-show');
  }

  /* ── wire up after DOM ready ── */
  document.addEventListener('DOMContentLoaded', function () {
    var t = getTable();
    if (!t) return;

    /* header click → select column */
    t.querySelectorAll('thead th.sorting').forEach(function (th) {
      th.addEventListener('click', function () {
        var allThs = Array.from(t.querySelectorAll('thead th'));
        var colIdx = allThs.indexOf(th);
        if (selectedColIdx === colIdx) {
          clearSelection();
        } else {
          selectColumn(colIdx);
        }
      });
    });

    /* right-click on selected header or cell → context menu */
    t.addEventListener('contextmenu', function (e) {
      if (selectedColIdx < 0) return;
      var th = e.target.closest('.' + HEAD_SELECTED);
      var td = e.target.closest('.' + COL_SELECTED);
      if (!th && !td) return;
      e.preventDefault();
      /* keep menu inside viewport */
      var mx = Math.min(e.clientX, window.innerWidth - 200);
      var my = Math.min(e.clientY, window.innerHeight - 100);
      showCtx(mx, my);
    });

    /* context menu actions */
    document.getElementById('rdrs-ctx-copy').addEventListener('click', function () {
      hideCtx();
      doCopy(selectedColIdx);
    });
    document.getElementById('rdrs-ctx-deselect').addEventListener('click', function () {
      hideCtx();
      clearSelection();
    });

    /* Ctrl+C / Meta+C → copy selected column */
    document.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
        if (selectedColIdx < 0) return;
        var sel = window.getSelection ? window.getSelection() : null;
        if (sel && sel.toString().length > 0) return; /* let normal copy win */
        e.preventDefault();
        doCopy(selectedColIdx);
      }
      if (e.key === 'Escape') {
        hideCtx();
        clearSelection();
      }
    });

    /* click outside table or context menu → deselect */
    document.addEventListener('click', function (e) {
      if (ctx.contains(e.target)) return;
      if (!t.contains(e.target)) clearSelection();
      if (!ctx.contains(e.target)) hideCtx();
    });
  });
}());
</script>
@endsection