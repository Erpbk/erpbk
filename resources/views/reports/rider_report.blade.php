@extends('layouts.app')
@section('title','Rider Report')
@section('content')
<style>
    .table tr:first-child>td {
        position: sticky;
        top: 0;
    }

    .filter-sidebar {
        position: fixed;
        top: 0;
        right: -360px;
        width: 360px;
        height: 100%;
        background: #fff;
        box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);
        transition: right .3s ease;
        z-index: 1111;
        overflow-y: auto;
    }

    .filter-sidebar.open {
        right: 0;
    }

    .filter-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .35);
        display: none;
        z-index: 1110;
    }

    .filter-overlay.show {
        display: block;
    }

    .filter-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border-bottom: 1px solid #eee;
    }

    .filter-body {
        padding: 12px 16px;
    }

    .btn-close {
        background: transparent;
        border: 0;
        font-size: 18px;
    }

    .card-header .actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .control-icon {
        cursor: pointer;
    }

    .sorted-asc:after {
        content: ' \25B2';
    }

    .sorted-desc:after {
        content: ' \25BC';
    }

    .loading-overlay {
        position: fixed;
        inset: 0;
        background: rgba(255, 255, 255, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
    }

    .filter-loading-overlay {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, .6);
    }

    .copy-text {
        cursor: pointer;
    }

    /* Totals cards */
    .totals-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
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

    .total-card .sub {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 2px;
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
</style>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h3>Rider Report</h3>
            <div class="actions">
                <button class="btn btn-sm btn-success exportToExcel action-btn"><i class="fa fa-file-excel"></i> Export</button>
            </div>
        </div>
    </section>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-12">
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
                // Utility columns for search/control (optional placeholders)
                ['data' => 'search', 'title' => 'Search'],
                ['data' => 'control', 'title' => 'Control'],
                ];
                @endphp
                @include('components.column-control-panel', [
                'tableColumns' => $tableColumns,
                'exportRoute' => route('rider.exportCustomizableRiders'),
                'tableIdentifier' => 'rider_report_table'
                ])

                <div class="card rounded-0">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title">
                            <h4 class="m-0">Report Table</h4>
                        </div>
                        <div class="card-search">
                            <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body table-responsive px-2 py-0" id="table-data">
                        <div id="totalsBar" class="mb-2" style="display:none;">
                            <div class="totals-cards">
                                <div class="total-card total-opening">
                                    <div class="label"><i class="fa fa-wallet"></i> Opening Balance</div>
                                    <div class="value" id="total_opening_balance">0.00</div>
                                </div>
                                <div class="total-card total-amount">
                                    <div class="label"><i class="fa fa-coins"></i> Total</div>
                                    <div class="value" id="total_amount">0.00</div>
                                </div>
                                <div class="total-card total-balance">
                                    <div class="label"><i class="fa fa-scale-balanced"></i> Balance (OB + Total)</div>
                                    <div class="value" id="total_b">0.00</div>
                                </div>
                                <div class="total-card total-debit">
                                    <div class="label"><i class="fa fa-arrow-down"></i> Debit Sum</div>
                                    <div class="value" id="total_debit_sum">0.00</div>
                                </div>
                                <div class="total-card total-credit">
                                    <div class="label"><i class="fa fa-arrow-up"></i> Credit Sum</div>
                                    <div class="value" id="total_credit_sum">0.00</div>
                                </div>
                            </div>
                        </div>
                        <table id="dataTableBuilder" class="table table-striped table-hover">
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
                                    <th>
                                        <a class="openFilterSidebar" href="javascript:void(0);" title="Filters">
                                            <i class="fa fa-filter"></i>
                                        </a>
                                    </th>
                                    <th>
                                        <a class="openColumnControlSidebar" href="javascript:void(0);" title="Column Control">
                                            <i class="fa fa-columns"></i>
                                        </a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="get_data"></tbody>
                        </table>
                        <div id="paginationLinks" class="mt-2"></div>
                        <div class="filter-loading-overlay text-center">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                    <div class="card-footer clearfix">
                        <div class="pagination-panel"></div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Filter Sidebar (same as Riders filters) -->
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
                    <label for="billing_month">Billing Month</label>
                    <input type="month" id="billing_month" name="billing_month" value="{{ request('billing_month') ?? date('Y-m') }}" class="form-control" />
                </div>


                <div class="col-md-12 form-group text-center">
                    <button type="button" class="btn btn-primary pull-right mt-3" onclick="get_data()"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                </div>
            </div>
        </form>
    </div>
    <div class="p-2"></div>
    <div class="p-2"></div>
    <div class="p-2"></div>
</div>
<!-- Filter Overlay -->
<div id="filterOverlay" class="filter-overlay"></div>
@endsection

@push('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="{{ URL::asset('export_excel/jquery.table2excel.js') }}"></script>
<script>
    $(document).ready(function() {
        // Filter sidebar toggle
        $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
            e.preventDefault();
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
            return false;
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
                // Clear quick search from URL when emptied
                const url = new URL(window.location);
                url.searchParams.delete('quick_search');
                window.history.pushState({}, '', url.toString());
                get_data();
            }
        });

        // Export button
        $(".exportToExcel").click(function() {
            $("#dataTableBuilder").table2excel({
                filename: "Rider_" + new Date().toISOString().replace(/[\-\:\.]/g, "") + ".xls",
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

        // Override global-pagination component behavior for AJAX
        // Use event delegation since pagination is dynamically loaded
        $(document).on('change', '#perPageSelect', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation(); // Stop the default pagination behavior

            let selectedValue = $(this).val();

            // Convert 'all' or '-1' to -1 for consistency
            if (selectedValue === 'all' || selectedValue === '-1') {
                selectedValue = '-1';
            }

            const url = new URL(window.location);

            // Set per_page parameter
            url.searchParams.set('per_page', selectedValue);

            // Reset to page 1 when changing per page
            url.searchParams.delete('page');

            // Update URL without reloading
            window.history.pushState({}, '', url.toString());

            // Trigger AJAX data reload
            get_data();

            return false;
        });
    });

    function get_data() {
        // Update URL with current filter values
        updateURLWithFilters();

        // Get per_page from URL
        const urlParams = new URLSearchParams(window.location.search);
        const perPage = urlParams.get('per_page') || '25';

        $('.filter-loading-overlay').show();
        $.ajax({
            url: "{{ url('reports/rider_report_data') }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name=\"csrf-token\"]').attr('content')
            },
            type: "POST",
            timeout: 120000, // 120 seconds for large datasets
            data: $('#filterForm').serialize() + '&quick_search=' + encodeURIComponent($('#quickSearch').val() || '') + '&per_page=' + encodeURIComponent(perPage),
            success: function(data) {
                try {
                    // Try to normalize both JSON and HTML responses
                    if (typeof data === 'string') {
                        try {
                            var parsed = JSON.parse(data);
                            data = parsed;
                        } catch (e) {
                            // Not JSON, assume it's pure HTML rows
                            $("#get_data").html(data);
                            $('#totalsBar').hide();
                            applyPagination();
                            $('.filter-loading-overlay').hide();
                            return;
                        }
                    }

                    // If we reach here, data should be an object with .data and totals
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
                    $('.filter-loading-overlay').hide();
                    if (window.ColumnController && typeof window.ColumnController.reapplySettings === 'function') {
                        setTimeout(function() {
                            window.ColumnController.reapplySettings();
                        }, 60);
                    }
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $('.filter-loading-overlay').hide();

                let errorMessage = 'Failed to load report data.';

                if (textStatus === 'timeout') {
                    errorMessage = 'Request timed out. The report is taking too long to load. Try reducing the number of records or contact support.';
                    console.error('Rider report timeout - Request exceeded 120 seconds');
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your internet connection and try again.';
                    console.error('Rider report network error');
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error occurred. Please try again or contact support.';
                    console.error('Rider report server error', xhr.status, xhr.responseText);
                } else {
                    console.error('Rider report load failed', xhr.status, textStatus, errorThrown);
                }

                if (!$('#get_data').children().length) {
                    $('#get_data').html('<tr><td colspan="16"><div class="alert alert-danger mb-0"><i class="fa fa-exclamation-triangle"></i> ' + errorMessage + '</div></td></tr>');
                }
            }
        });
    }

    // Handle clicks on pagination links (rendered from backend component)
    $(document).on('click', '#paginationLinks a', function(e) {
        e.preventDefault();
        var url = new URL($(this).attr('href'), window.location.origin);
        var page = url.searchParams.get('page') || 1;
        get_data_with_page(page);
    });

    function get_data_with_page(page) {
        // Update URL with page number while preserving filters
        const url = new URL(window.location);
        if (page && page != 1) {
            url.searchParams.set('page', page);
        } else {
            url.searchParams.delete('page');
        }
        window.history.pushState({}, '', url.toString());

        // Get per_page from URL
        const perPage = url.searchParams.get('per_page') || '25';

        $('.filter-loading-overlay').show();
        $.ajax({
            url: "{{ url('reports/rider_report_data') }}?page=" + encodeURIComponent(page),
            headers: {
                'X-CSRF-TOKEN': $('meta[name=\"csrf-token\"]').attr('content')
            },
            type: "POST",
            timeout: 120000, // 120 seconds for large datasets
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
                    $('.filter-loading-overlay').hide();
                    if (window.ColumnController && typeof window.ColumnController.reapplySettings === 'function') {
                        setTimeout(function() {
                            window.ColumnController.reapplySettings();
                        }, 60);
                    }
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $('.filter-loading-overlay').hide();

                let errorMessage = 'Failed to load report data.';

                if (textStatus === 'timeout') {
                    errorMessage = 'Request timed out. The report is taking too long to load. Try reducing the number of records or contact support.';
                    console.error('Rider report timeout - Request exceeded 120 seconds');
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your internet connection and try again.';
                    console.error('Rider report network error');
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error occurred. Please try again or contact support.';
                    console.error('Rider report server error', xhr.status, xhr.responseText);
                } else {
                    console.error('Rider report load failed', xhr.status, textStatus, errorThrown);
                }

                $('#get_data').html('<tr><td colspan="16"><div class="alert alert-danger mb-0"><i class="fa fa-exclamation-triangle"></i> ' + errorMessage + '</div></td></tr>');
            }
        });
    }

    // Function to update URL with current filter values
    function updateURLWithFilters() {
        const url = new URL(window.location);

        // Get all form values
        const designation = $('#designation').val();
        const vid = $('[name="VID"]').val();
        const bike_assignment_status = $('#bike_assignment_status').val();
        const billing_month = $('#billing_month').val();
        const quick_search = $('#quickSearch').val();

        // Preserve per_page if it exists
        const perPage = url.searchParams.get('per_page');

        // Clear existing filter parameters (but keep per_page)
        url.searchParams.delete('designation');
        url.searchParams.delete('VID');
        url.searchParams.delete('bike_assignment_status');
        url.searchParams.delete('billing_month');
        url.searchParams.delete('quick_search');

        // Add non-empty values to URL
        if (designation) url.searchParams.set('designation', designation);
        if (vid) url.searchParams.set('VID', vid);
        if (bike_assignment_status) url.searchParams.set('bike_assignment_status', bike_assignment_status);
        if (billing_month) url.searchParams.set('billing_month', billing_month);
        if (quick_search) url.searchParams.set('quick_search', quick_search);

        // Restore per_page if it existed
        if (perPage) url.searchParams.set('per_page', perPage);

        // Update URL without reloading the page
        window.history.pushState({}, '', url.toString());
    }

    // Function to load filters from URL parameters on page load
    function loadFiltersFromURL() {
        const url = new URL(window.location);

        // Get URL parameters
        const designation = url.searchParams.get('designation');
        const vid = url.searchParams.get('VID');
        const bike_assignment_status = url.searchParams.get('bike_assignment_status');
        const billing_month = url.searchParams.get('billing_month');
        const quick_search = url.searchParams.get('quick_search');

        // Set form values from URL
        if (designation) $('#designation').val(designation);
        if (vid) $('[name="VID"]').val(vid);
        if (bike_assignment_status) $('#bike_assignment_status').val(bike_assignment_status);
        if (billing_month) $('#billing_month').val(billing_month);
        if (quick_search) $('#quickSearch').val(quick_search);
    }
</script>
@endpush