@extends('layouts.app')
@section('title','Rider Report')

@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
@endpush

@section('content')
<style>
    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        inset: 0;
        background: rgba(255, 255, 255, 0.9);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
    }

    .loading-overlay.show {
        display: flex;
    }

    /* Totals Cards */
    .totals-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
    }

    .total-card {
        flex: 1 1 220px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-left-width: 6px;
        border-radius: 10px;
        padding: 12px 14px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    .total-card .label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .total-card .value {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
    }

    .total-opening {
        border-left-color: #3b82f6;
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.06), rgba(59, 130, 246, 0.02));
    }

    .total-amount {
        border-left-color: #10b981;
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.06), rgba(16, 185, 129, 0.02));
    }

    .total-balance {
        border-left-color: #8b5cf6;
        background: linear-gradient(180deg, rgba(139, 92, 246, 0.06), rgba(139, 92, 246, 0.02));
    }

    .total-debit {
        border-left-color: #f59e0b;
        background: linear-gradient(180deg, rgba(245, 158, 11, 0.06), rgba(245, 158, 11, 0.02));
    }

    .total-credit {
        border-left-color: #ef4444;
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.06), rgba(239, 68, 68, 0.02));
    }

    /* Filter Tabs Section */
    .filter-tabs-section {
        margin-bottom: 1rem;
    }

    .filter-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        text-decoration: none;
        color: #6b7280;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .filter-tab:hover {
        background: #f9fafb;
        color: #111827;
        border-color: #d1d5db;
    }

    .filter-tab.active {
        background: #3b82f6;
        color: #fff;
        border-color: #3b82f6;
    }

    /* Action Dropdown */
    .action-dropdown-container {
        position: relative;
        display: inline-block;
    }

    .action-dropdown-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        position: relative;
        overflow: hidden;
    }

    .action-dropdown-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .action-dropdown-btn:hover::before {
        width: 300px;
        height: 300px;
    }

    .action-dropdown-btn:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        transform: translateY(-2px);
    }

    .action-dropdown-btn:active {
        transform: translateY(0px);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }

    .action-dropdown-btn i {
        transition: transform 0.3s ease;
        position: relative;
        z-index: 1;
    }

    .action-dropdown-btn:hover i {
        transform: scale(1.1) rotate(5deg);
    }

    .action-dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 8px;
        min-width: 280px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        display: none;
        z-index: 1000;
        overflow: hidden;
    }

    .action-dropdown-menu.show {
        display: block;
    }

    .action-dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        text-decoration: none;
        color: #374151;
        transition: all 0.2s;
        border-bottom: 1px solid #f3f4f6;
    }

    .action-dropdown-item:last-child {
        border-bottom: none;
    }

    .action-dropdown-item:hover {
        background: #f9fafb;
        color: #1f2937;
    }

    .action-dropdown-item i {
        font-size: 20px;
        color: #3b82f6;
    }

    .action-dropdown-item-text {
        font-weight: 600;
        font-size: 14px;
    }

    .action-dropdown-item-desc {
        font-size: 12px;
        color: #9ca3af;
    }

    /* Card Header */
    .card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e5e7eb;
        padding: 16px 20px;
        border-radius: 12px 12px 0 0 !important;
    }

    .card-title h4 {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }

    .card-search input {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 8px 16px;
        min-width: 280px;
        transition: all 0.2s;
    }

    .card-search input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Table Styles */
    .table-responsive {
        max-height: calc(100vh - 250px);
        overflow: auto;
    }

    #dataTableBuilder {
        font-size: 14px;
    }

    #dataTableBuilder thead th {
        background: #f8f9fa;
        color: #374151;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 2px solid #e5e7eb;
        padding: 12px 8px;
    }

    #dataTableBuilder tbody tr {
        transition: all 0.2s;
    }

    #dataTableBuilder tbody tr:hover {
        background: #f9fafb;
    }

    #dataTableBuilder tbody td {
        padding: 10px 8px;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
    }

    #dataTableBuilder tbody td a {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    #dataTableBuilder tbody td a:hover {
        color: #2563eb;
        text-decoration: underline;
    }

    .badge {
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 12px;
    }

    .bg-label-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .bg-label-danger {
        background-color: #fee2e2;
        color: #991b1b;
    }
</style>

<div class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>

<section class="content-header">
    <div>
        <!-- Filter Tabs Section -->
        <div class="filter-tabs-section">
            <div class="d-flex justify-content-between align-items-center">
                <div class="filter-tabs">
                    <a href="{{ route('reports.rider_report') }}" class="filter-tab active">
                        <i class="ti ti-report"></i>
                        Rider Report
                    </a>
                </div>
                <div class="d-flex align-items-center">
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="reportActionsBtn">
                            <i class="ti ti-settings"></i>
                            <span>Actions</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="reportActionsDropdown">
                            <a class="action-dropdown-item exportToExcel" href="javascript:void(0);">
                                <i class="ti ti-file-excel"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Export to Excel</div>
                                    <div class="action-dropdown-item-desc">Download report as Excel file</div>
                                </div>
                            </a>
                            <a class="action-dropdown-item openColumnControlSidebar" href="javascript:void(0);">
                                <i class="ti ti-columns"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Column Control</div>
                                    <div class="action-dropdown-item-desc">Show/hide table columns</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
        // Define table columns for column control (report-specific)
        $tableColumns = [
        ['data' => 'id', 'title' => '#'],
        ['data' => 'name', 'title' => 'Name'],
        ['data' => 'vendor', 'title' => 'Vendor'],
        ['data' => 'designation', 'title' => 'Designation'],
        ['data' => 'person_code', 'title' => 'Person Code'],
        ['data' => 'labor_card', 'title' => 'Labor Card'],
        ['data' => 'bike', 'title' => 'Bike'],
        ['data' => 'wps', 'title' => 'WPS'],
        ['data' => 'status', 'title' => 'Status'],
        ['data' => 'balance_forward', 'title' => 'Balance Forward'],
        ['data' => 'amount', 'title' => 'Amount'],
        ['data' => 'balance', 'title' => 'Balance'],
        ['data' => 'sub_total', 'title' => 'Sub Total'],
        ['data' => 'total', 'title' => 'Total'],
        ];
        @endphp
        @include('components.column-control-panel', [
        'tableColumns' => $tableColumns,
        'exportRoute' => route('rider.exportCustomizableRiders'),
        'tableIdentifier' => 'rider_report_table'
        ])

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
                <div class="card-Filters">
                    <button class="btn action-dropdown-btn openFilterSidebar"> <i class="fa fa-filter"></i> Filters</button>
                </div>
            </div>
            <div class="card-body px-2 py-0">
                <div id="totalsBar" style="display:none;">
                    <div class="totals-cards">
                        <div class="total-card total-opening">
                            <div class="label"><i class="ti ti-wallet"></i> Opening Balance</div>
                            <div class="value" id="total_opening_balance">0.00</div>
                        </div>
                        <div class="total-card total-amount">
                            <div class="label"><i class="ti ti-coins"></i> Total</div>
                            <div class="value" id="total_amount">0.00</div>
                        </div>
                        <div class="total-card total-balance">
                            <div class="label"><i class="ti ti-scale-balanced"></i> Balance (OB + Total)</div>
                            <div class="value" id="total_b">0.00</div>
                        </div>
                        <div class="total-card total-debit">
                            <div class="label"><i class="ti ti-arrow-down"></i> Debit Sum</div>
                            <div class="value" id="total_debit_sum">0.00</div>
                        </div>
                        <div class="total-card total-credit">
                            <div class="label"><i class="ti ti-arrow-up"></i> Credit Sum</div>
                            <div class="value" id="total_credit_sum">0.00</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive" id="table-data">
                    <table id="dataTableBuilder" class="table table-hover">
                        <thead>
                            <tr>
                                <th title="#">#</th>
                                <th title="Name">Name</th>
                                <th title="Vendor">Vendor</th>
                                <th title="Designation">Designation</th>
                                <th title="Person Code">Person Code</th>
                                <th title="Labor Card">Labor Card</th>
                                <th title="Bike">Bike</th>
                                <th title="WPS">WPS</th>
                                <th title="Status">Status</th>
                                <th title="Balance Forward" style="text-align: right;">Balance Forward</th>
                                <th title="Amount" style="text-align: right;">Amount</th>
                                <th title="Balance" style="text-align: right;">Balance</th>
                                <th title="Sub Total" style="text-align: right;">Sub Total</th>
                                <th title="Total" style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody id="get_data"></tbody>
                    </table>
                </div>
                <div id="paginationLinks" class="mt-2"></div>
            </div>
            <div class="card-footer clearfix">
                <div class="pagination-panel"></div>
            </div>
        </div>
    </div>
</section>

<!-- Filter Sidebar -->
<div id="filterSidebar" class="filter-sidebar">
    <div class="filter-header">
        <h5>Filter Riders</h5>
        <button type="button" class="btn-close" id="closeSidebar">&times;</button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="designation">Filter by Designation</label>
                    <select class="form-control" id="designation" name="designation">
                        @php
                        $emiratedesignation = DB::table('riders')->whereNotNull('designation')->where('designation', '!=', '')->select('designation')->distinct()->pluck('designation');
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($emiratedesignation as $des)
                        <option value="{{ $des }}" {{ request('designation') == $des ? 'selected' : '' }}>{{ $des }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="VID">Vendor</label>
                    {!! Form::select('VID', \App\Models\Vendors::dropdown(), request('VID'), ['class' => 'form-control form-select']) !!}
                </div>
                <div class="form-group col-md-12">
                    <label for="bike_assignment_status">Filter by Status</label>
                    <select class="form-control form-select" id="bike_assignment_status" name="bike_assignment_status">
                        <option value="" selected>Select</option>
                        <option value="Active" {{ request('bike_assignment_status') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('bike_assignment_status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="wps_status">Filter by WPS Status</label>
                    <select class="form-control form-select" id="wps_status" name="wps_status">
                        <option value="" selected>Select</option>
                        <option value="WPS" {{ request('wps_status') == 'WPS' ? 'selected' : '' }}>WPS</option>
                        <option value="NON/WPS" {{ request('wps_status') == 'NON/WPS' ? 'selected' : '' }}>NON/WPS</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="billing_month">Billing Month</label>
                    <input type="month" id="billing_month" name="billing_month" value="{{ request('billing_month') ?? date('Y-m') }}" class="form-control" />
                </div>
                <div class="col-md-12 form-group text-center">
                    <button type="button" class="btn btn-primary w-100 mt-3" onclick="get_data()">
                        <i class="ti ti-filter mx-2"></i> Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Filter Overlay -->
<div id="filterOverlay" class="filter-overlay"></div>
@endsection

@push('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="{{ URL::asset('export_excel/jquery.table2excel.js') }}"></script>
<script>
    $(document).ready(function() {
        // Action dropdown toggle
        $('#reportActionsBtn').on('click', function(e) {
            e.stopPropagation();
            $('#reportActionsDropdown').toggleClass('show');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.action-dropdown-container').length) {
                $('#reportActionsDropdown').removeClass('show');
            }
        });

        // Filter sidebar toggle
        $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
            e.preventDefault();
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
            return false;
        });

        // Hover to open sidebar with delay
        let hoverTimeout;
        $('.openFilterSidebar').on('mouseenter', function() {
            hoverTimeout = setTimeout(function() {
                $('#filterSidebar').addClass('open');
                $('#filterOverlay').addClass('show');
            }, 300); // 300ms delay before opening
        });

        $('.openFilterSidebar').on('mouseleave', function() {
            clearTimeout(hoverTimeout);
        });

        // Keep sidebar open when hovering over it
        $('#filterSidebar').on('mouseenter', function() {
            clearTimeout(hoverTimeout);
        });

        $('#closeSidebar, #filterOverlay').on('click', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });

        // Quick search
        $('#quickSearch').on('keyup', function(e) {
            if (e.keyCode === 13) {
                get_data();
            } else if ($(this).val().length === 0) {
                const url = new URL(window.location);
                url.searchParams.delete('quick_search');
                window.history.pushState({}, '', url.toString());
                get_data();
            }
        });

        // Export button
        $(".exportToExcel").click(function() {
            $("#dataTableBuilder").table2excel({
                filename: "Rider_Report_" + new Date().toISOString().replace(/[\-\:\.]/g, "") + ".xls",
                fileext: ".xls",
                exclude: ".noExl",
                exclude_img: true,
                exclude_links: true,
                exclude_inputs: true,
                preserveColors: true,
            });
        });

        // Load filters from URL on page load
        loadFiltersFromURL();

        // Initial load
        get_data();

        // Per page select handler
        $(document).on('change', '#perPageSelect', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            let selectedValue = $(this).val();
            if (selectedValue === 'all' || selectedValue === '-1') {
                selectedValue = '-1';
            }

            const url = new URL(window.location);
            url.searchParams.set('per_page', selectedValue);
            url.searchParams.delete('page');
            window.history.pushState({}, '', url.toString());
            get_data();
            return false;
        });
    });

    function get_data() {
        updateURLWithFilters();

        const urlParams = new URLSearchParams(window.location.search);
        const perPage = urlParams.get('per_page') || '25';

        $('#loading-overlay').addClass('show');

        $.ajax({
            url: "{{ url('reports/rider_report_data') }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            timeout: 120000,
            data: $('#filterForm').serialize() + '&quick_search=' + encodeURIComponent($('#quickSearch').val() || '') + '&per_page=' + encodeURIComponent(perPage),
            success: function(data) {
                try {
                    if (typeof data === 'string') {
                        try {
                            data = JSON.parse(data);
                        } catch (e) {
                            $("#get_data").html(data);
                            $('#totalsBar').hide();
                            $('#loading-overlay').removeClass('show');
                            return;
                        }
                    }

                    $("#get_data").html(data.data || '');

                    if (typeof data.opening_balance_total !== 'undefined') {
                        $('#total_opening_balance').text(parseFloat(data.opening_balance_total).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                        $('#total_amount').text(parseFloat(data.total).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                        $('#total_b').text(parseFloat(data.b_total).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                        $('#total_debit_sum').text(parseFloat(data.total_debit_sum).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                        $('#total_credit_sum').text(parseFloat(data.total_credit_sum).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                        $('#totalsBar').show();
                    } else {
                        $('#totalsBar').hide();
                    }

                    if (data.paginationLinks) {
                        $('#paginationLinks').html(data.paginationLinks);
                    }
                } finally {
                    $('#loading-overlay').removeClass('show');
                    if (window.ColumnController && typeof window.ColumnController.reapplySettings === 'function') {
                        setTimeout(function() {
                            window.ColumnController.reapplySettings();
                        }, 60);
                    }
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $('#loading-overlay').removeClass('show');

                let errorMessage = 'Failed to load report data.';

                if (textStatus === 'timeout') {
                    errorMessage = 'Request timed out. The report is taking too long to load. Try reducing the number of records or contact support.';
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your internet connection and try again.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error occurred. Please try again or contact support.';
                }

                if (!$('#get_data').children().length) {
                    $('#get_data').html('<tr><td colspan="16"><div class="alert alert-danger mb-0"><i class="ti ti-alert-triangle"></i> ' + errorMessage + '</div></td></tr>');
                }
            }
        });
    }

    // Handle pagination links
    $(document).on('click', '#paginationLinks a', function(e) {
        e.preventDefault();
        var url = new URL($(this).attr('href'), window.location.origin);
        var page = url.searchParams.get('page') || 1;
        get_data_with_page(page);
    });

    function get_data_with_page(page) {
        const url = new URL(window.location);
        if (page && page != 1) {
            url.searchParams.set('page', page);
        } else {
            url.searchParams.delete('page');
        }
        window.history.pushState({}, '', url.toString());

        const perPage = url.searchParams.get('per_page') || '25';

        $('#loading-overlay').addClass('show');

        $.ajax({
            url: "{{ url('reports/rider_report_data') }}?page=" + encodeURIComponent(page),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            timeout: 120000,
            data: $('#filterForm').serialize() + '&quick_search=' + encodeURIComponent($('#quickSearch').val() || '') + '&per_page=' + encodeURIComponent(perPage),
            dataType: "JSON",
            success: function(data) {
                try {
                    if (typeof data === 'string') {
                        try {
                            data = JSON.parse(data);
                        } catch (e) {
                            $("#get_data").html(data);
                            $('#totalsBar').hide();
                            $('#paginationLinks').empty();
                            return;
                        }
                    }
                    $("#get_data").html(data.data || '');
                    if (typeof data.opening_balance_total !== 'undefined') {
                        $('#total_opening_balance').text(parseFloat(data.opening_balance_total).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                        $('#total_amount').text(parseFloat(data.total).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                        $('#total_b').text(parseFloat(data.b_total).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                        $('#total_debit_sum').text(parseFloat(data.total_debit_sum).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                        $('#total_credit_sum').text(parseFloat(data.total_credit_sum).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                        $('#totalsBar').show();
                    } else {
                        $('#totalsBar').hide();
                    }
                    if (data.paginationLinks) {
                        $('#paginationLinks').html(data.paginationLinks);
                    }
                } finally {
                    $('#loading-overlay').removeClass('show');
                    if (window.ColumnController && typeof window.ColumnController.reapplySettings === 'function') {
                        setTimeout(function() {
                            window.ColumnController.reapplySettings();
                        }, 60);
                    }
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $('#loading-overlay').removeClass('show');

                let errorMessage = 'Failed to load report data.';

                if (textStatus === 'timeout') {
                    errorMessage = 'Request timed out. The report is taking too long to load.';
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your internet connection.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error occurred. Please try again.';
                }

                $('#get_data').html('<tr><td colspan="16"><div class="alert alert-danger mb-0"><i class="ti ti-alert-triangle"></i> ' + errorMessage + '</div></td></tr>');
            }
        });
    }

    function updateURLWithFilters() {
        const url = new URL(window.location);

        const designation = $('#designation').val();
        const vid = $('[name="VID"]').val();
        const bike_assignment_status = $('#bike_assignment_status').val();
        const wps_status = $('#wps_status').val();
        const billing_month = $('#billing_month').val();
        const quick_search = $('#quickSearch').val();

        const perPage = url.searchParams.get('per_page');

        url.searchParams.delete('designation');
        url.searchParams.delete('VID');
        url.searchParams.delete('bike_assignment_status');
        url.searchParams.delete('wps_status');
        url.searchParams.delete('billing_month');
        url.searchParams.delete('quick_search');

        if (designation) url.searchParams.set('designation', designation);
        if (vid) url.searchParams.set('VID', vid);
        if (bike_assignment_status) url.searchParams.set('bike_assignment_status', bike_assignment_status);
        if (wps_status) url.searchParams.set('wps_status', wps_status);
        if (billing_month) url.searchParams.set('billing_month', billing_month);
        if (quick_search) url.searchParams.set('quick_search', quick_search);

        if (perPage) url.searchParams.set('per_page', perPage);

        window.history.pushState({}, '', url.toString());
    }

    function loadFiltersFromURL() {
        const url = new URL(window.location);

        const designation = url.searchParams.get('designation');
        const vid = url.searchParams.get('VID');
        const bike_assignment_status = url.searchParams.get('bike_assignment_status');
        const wps_status = url.searchParams.get('wps_status');
        const billing_month = url.searchParams.get('billing_month');
        const quick_search = url.searchParams.get('quick_search');

        if (designation) $('#designation').val(designation);
        if (vid) $('[name="VID"]').val(vid);
        if (bike_assignment_status) $('#bike_assignment_status').val(bike_assignment_status);
        if (wps_status) $('#wps_status').val(wps_status);
        if (billing_month) $('#billing_month').val(billing_month);
        if (quick_search) $('#quickSearch').val(quick_search);
    }
</script>
@endpush