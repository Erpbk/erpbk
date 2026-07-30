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

    /* Keep all totals cards on one row */
    #totalsBar .totals-cards {
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 8px;
    }

    #totalsBar .total-card {
        flex: 1 1 0;
        min-width: 0;
    }

    /* Totals footer row */
    #dataTableBuilder tbody tr.total-row,
    #dataTableBuilder tbody tr.total-row td,
    #dataTableBuilder tbody tr.total-row th {
        font-weight: 700 !important;
        color: #000 !important;
        background-color: #f3f4f6 !important;
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
        $tableColumns = [
        ['data' => 'id', 'title' => 'ID'],
        ['data' => 'name', 'title' => 'Name'],
        ['data' => 'status', 'title' => 'Status'],
        ['data' => 'emirates', 'title' => 'Emirates'],
        ['data' => 'designation', 'title' => 'Designation'],
        ['data' => 'project', 'title' => 'Project'],
        ['data' => 'billing_month', 'title' => 'Billing Month'],
        ['data' => 'total_amount', 'title' => 'Invoice'],
        ['data' => 'vendor_charges', 'title' => 'Vendor Charges'],
        ['data' => 'cod', 'title' => 'COD'],
        ['data' => 'rta_fine', 'title' => 'RTA Fine'],
        ['data' => 'salik_fee', 'title' => 'Salik FEE'],
        ['data' => 'fuel', 'title' => 'Fuel'],
        ['data' => 'visa_installment', 'title' => 'Visa Installment'],
        ['data' => 'jv', 'title' => 'JV'],
        ['data' => 'advance', 'title' => 'Advance'],
        ['data' => 'penalty', 'title' => 'Penalty'],
        ['data' => 'incentive', 'title' => 'Incentive'],
        ['data' => 'previous_balance', 'title' => 'Previous Balance'],
        ['data' => 'payable', 'title' => 'Payable'],
        ['data' => 'paid_amount', 'title' => 'Paid Amount'],
        ['data' => 'balance', 'title' => 'Balance'],
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
                        <div class="total-card total-blue">
                            <div class="label"><i class="ti ti-users"></i> Riders</div>
                            <div class="value" id="sum_riders_count">0</div>
                        </div>
                        <div class="total-card total-green">
                            <div class="label"><i class="ti ti-coins"></i> Total Amount</div>
                            <div class="value" id="sum_total_amount">0.00</div>
                        </div>
                        <div class="total-card total-4">
                            <div class="label"><i class="ti ti-plus"></i> Total Additions</div>
                            <div class="value" id="sum_total_additions">0.00</div>
                        </div>
                        <div class="total-card total-red">
                            <div class="label"><i class="ti ti-minus"></i> Total Deductions</div>
                            <div class="value" id="sum_total_deductions">0.00</div>
                        </div>
                        <div class="total-card total-3">
                            <div class="label"><i class="ti ti-wallet"></i> Payable</div>
                            <div class="value" id="sum_total_payable">0.00</div>
                        </div>
                        <div class="total-card total-4">
                            <div class="label"><i class="ti ti-cash"></i> Paid Amount</div>
                            <div class="value" id="sum_total_paid">0.00</div>
                        </div>
                        <div class="total-card total-1">
                            <div class="label"><i class="ti ti-scale-balanced"></i> Balance</div>
                            <div class="value" id="sum_total_balance">0.00</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive" id="table-data">
                    <table id="dataTableBuilder" class="table table-hover">
                        <thead>
                            <tr>
                                <th title="ID">ID</th>
                                <th title="Name">Name</th>
                                <th title="Status">Status</th>
                                <th title="Emirates">Emirates</th>
                                <th title="Designation">Designation</th>
                                <th title="Project">Project</th>
                                <th title="Billing Month">Billing Month</th>
                                <th title="Invoice" style="text-align: center;">Invoice</th>
                                <th title="Vendor Charges" style="text-align: center;">Vendor Charges</th>
                                <th title="COD" style="text-align: center;">COD</th>
                                <th title="RTA Fine" style="text-align: center;">RTA Fine</th>
                                <th title="Salik FEE" style="text-align: center;">Salik</th>
                                <th title="Fuel" style="text-align: center;">Fuel</th>
                                <th title="Visa Installment" style="text-align: center;">Visa Inst.</th>
                                <th title="Journal Voucher" style="text-align: center;">JV</th>
                                <th title="Advance" style="text-align: center;">Advance</th>
                                <th title="Penalty" style="text-align: center;">Penalty</th>
                                <th title="Incentive" style="text-align: center;">Incentive</th>
                                <th title="Previous Balance" style="text-align: center;">Previous</th>
                                <th title="Payable" style="text-align: center;">Payable</th>
                                <th title="Paid Amount" style="text-align: center;">Paid</th>
                                <th title="Balance" style="text-align: center;">Balance</th>
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
<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Riders</h5>
        <button type="button" class="btn-close" id="closeSidebar">&times;</button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="rider_id">Rider</label>
                    {!! Form::select('rider_id', \App\Models\Riders::dropdown(), request('rider_id'), ['class' => 'form-control form-select select2', 'id' => 'rider_id']) !!}
                </div>
                <div class="form-group col-md-12">
                    <label for="designation">Filter by Designation</label>
                    <select class="form-control form-select select2" id="designation" name="designation">
                        @php
                        $emiratedesignation = company_table('riders')->whereNotNull('designation')->where('designation', '!=', '')->select('designation')->distinct()->pluck('designation');
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($emiratedesignation as $des)
                        <option value="{{ $des }}" {{ request('designation') == $des ? 'selected' : '' }}>{{ $des }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="customer_id">Project</label>
                    {!! Form::select('customer_id', \App\Models\Customers::dropdown(), request('customer_id'), ['class' => 'form-control form-select select2', 'id' => 'customer_id']) !!}
                </div>
                <div class="form-group col-md-12">
                    <label for="bike_assignment_status">Filter by Status</label>
                    <select class="form-control form-select select2" id="bike_assignment_status" name="bike_assignment_status">
                        <option value="" selected>Select</option>
                        <option value="Active" {{ request('bike_assignment_status') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('bike_assignment_status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="wps_status">Filter by WPS Status</label>
                    <select class="form-control form-select select2" id="wps_status" name="wps_status">
                        <option value="" selected>Select</option>
                        <option value="WPS" {{ request('wps_status') == 'WPS' ? 'selected' : '' }}>WPS</option>
                        <option value="NON/WPS" {{ request('wps_status') == 'NON/WPS' ? 'selected' : '' }}>NON/WPS</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="from_month">From Month</label>
                    <input type="month" id="from_month" name="from_month" value="{{ request('from_month', request('billing_month', date('Y-m'))) }}" class="form-control" />
                </div>
                <div class="form-group col-md-12">
                    <label for="to_month">To Month</label>
                    <input type="month" id="to_month" name="to_month" value="{{ request('to_month', request('billing_month', date('Y-m'))) }}" class="form-control" />
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
        // Init Select2 on filter selects (dropdownParent must be searchTopbody)
        $('#filterSidebar select.select2').each(function() {
            var $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                width: '100%',
                allowClear: true,
                placeholder: 'Select',
                dropdownParent: $('#searchTopbody')
            });
        });

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

    /**
     * The shared column-control panel moves cells by their original position, so a
     * second pass (for example after the tbody is re-rendered by AJAX) permutes an
     * already-permuted header and leaves it out of sync with the data. Here every
     * cell is tagged with its column key, so ordering and visibility can be applied
     * repeatedly and always produce the same layout for header and body alike.
     */
    window.RiderReportColumns = (function() {
        const COLUMN_KEYS = @json(array_values(array_column($tableColumns, 'data')));
        let scheduled = null;

        function getTable() {
            return document.getElementById('dataTableBuilder');
        }

        function tagCells(table) {
            table.querySelectorAll('thead th').forEach(function(th, index) {
                if (!th.dataset.columnKey && COLUMN_KEYS[index]) {
                    th.dataset.columnKey = COLUMN_KEYS[index];
                }
            });

            // Freshly rendered rows always arrive in the canonical column order.
            table.querySelectorAll('tbody tr').forEach(function(row) {
                if (row.children.length !== COLUMN_KEYS.length) {
                    return;
                }
                Array.from(row.children).forEach(function(cell, index) {
                    if (!cell.dataset.columnKey) {
                        cell.dataset.columnKey = COLUMN_KEYS[index];
                    }
                });
            });
        }

        function readLayout() {
            const items = document.querySelectorAll('#columnList .column-item');
            const order = [];
            const hidden = [];

            items.forEach(function(item) {
                const key = item.dataset.columnKey;
                if (!key || COLUMN_KEYS.indexOf(key) === -1) {
                    return;
                }
                order.push(key);
                const checkbox = item.querySelector('.column-visibility-checkbox');
                if (checkbox && !checkbox.checked) {
                    hidden.push(key);
                }
            });

            COLUMN_KEYS.forEach(function(key) {
                if (order.indexOf(key) === -1) {
                    order.push(key);
                }
            });

            return {
                order: order,
                hidden: hidden
            };
        }

        function apply() {
            const table = getTable();
            if (!table) {
                return;
            }

            tagCells(table);

            const layout = readLayout();
            const rows = [table.querySelector('thead tr')].concat(
                Array.from(table.querySelectorAll('tbody tr'))
            );

            rows.forEach(function(row) {
                if (!row) {
                    return;
                }

                const cells = new Map();
                Array.from(row.children).forEach(function(cell) {
                    if (cell.dataset.columnKey) {
                        cells.set(cell.dataset.columnKey, cell);
                    }
                });

                // Skip rows that are not a plain one-cell-per-column row (e.g. messages).
                if (cells.size !== row.children.length) {
                    return;
                }

                const ordered = document.createDocumentFragment();
                layout.order.forEach(function(key) {
                    const cell = cells.get(key);
                    if (!cell) {
                        return;
                    }
                    cell.classList.toggle('column-hidden', layout.hidden.indexOf(key) !== -1);
                    ordered.appendChild(cell);
                });

                row.appendChild(ordered);
            });
        }

        function scheduleApply() {
            if (scheduled) {
                return;
            }
            scheduled = window.requestAnimationFrame(function() {
                scheduled = null;
                apply();
            });
        }

        function patchController() {
            const controller = window.ColumnController;
            if (!controller) {
                return false;
            }

            if (!controller.riderReportPatched) {
                controller.riderReportPatched = true;

                controller.applyColumnOrder = function() {
                    apply();
                };

                controller.toggleColumnVisibility = function() {
                    scheduleApply();
                    this.updateColumnStats();
                    if (!this.isInitialLoad) {
                        this.saveSettings();
                    }
                };
            }

            apply();
            return true;
        }

        // Tag the header immediately so no later pass can key cells off an
        // already re-ordered DOM.
        if (getTable()) {
            tagCells(getTable());
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (patchController()) {
                return;
            }
            let attempts = 0;
            const timer = setInterval(function() {
                if (patchController() || ++attempts > 40) {
                    clearInterval(timer);
                }
            }, 50);
        });

        return {
            apply: apply
        };
    })();

    function formatMoney(value) {
        return parseFloat(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function updateTotalsBar(data) {
        if (typeof data.total_amount !== 'undefined') {
            $('#sum_riders_count').text(parseInt(data.riders_count || 0, 10).toLocaleString());
            $('#sum_total_amount').text(formatMoney(data.total_amount));
            $('#sum_total_additions').text(formatMoney(data.total_additions));
            $('#sum_total_deductions').text(formatMoney(data.total_deductions));
            $('#sum_total_payable').text(formatMoney(data.total_payable));
            $('#sum_total_paid').text(formatMoney(data.total_paid));
            $('#sum_total_balance').text(formatMoney(data.total_balance));
            $('#totalsBar').show();
        } else {
            $('#totalsBar').hide();
        }
    }

    function get_data() {
        updateURLWithFilters();

        const fromMonth = $('#from_month').val();
        const toMonth = $('#to_month').val();
        if (fromMonth && toMonth && fromMonth > toMonth) {
            if (typeof toastr !== 'undefined') {
                toastr.error('From Month cannot be after To Month.');
            } else {
                alert('From Month cannot be after To Month.');
            }
            return;
        }

        const urlParams = new URLSearchParams(window.location.search);
        const perPage = urlParams.get('per_page') || '25';

        $('#loading-overlay').addClass('show');

        $.ajax({
            url: "{{ route('reports.rider_report_data') }}",
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
                    updateTotalsBar(data);

                    if (data.paginationLinks) {
                        $('#paginationLinks').html(data.paginationLinks);
                    }
                } finally {
                    $('#loading-overlay').removeClass('show');
                    window.RiderReportColumns.apply();
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
                    $('#get_data').html('<tr><td colspan="22"><div class="alert alert-danger mb-0"><i class="ti ti-alert-triangle"></i> ' + errorMessage + '</div></td></tr>');
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
            url: "{{ route('reports.rider_report_data') }}?page=" + encodeURIComponent(page),
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
                    // Keep overall stats bar unchanged on page navigation
                    if (data.paginationLinks) {
                        $('#paginationLinks').html(data.paginationLinks);
                    }
                } finally {
                    $('#loading-overlay').removeClass('show');
                    window.RiderReportColumns.apply();
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

                $('#get_data').html('<tr><td colspan="22"><div class="alert alert-danger mb-0"><i class="ti ti-alert-triangle"></i> ' + errorMessage + '</div></td></tr>');
            }
        });
    }

    function updateURLWithFilters() {
        const url = new URL(window.location);

        const rider_id = $('#rider_id').val();
        const designation = $('#designation').val();
        const customer_id = $('#customer_id').val();
        const bike_assignment_status = $('#bike_assignment_status').val();
        const wps_status = $('#wps_status').val();
        const from_month = $('#from_month').val();
        const to_month = $('#to_month').val();
        const quick_search = $('#quickSearch').val();

        const perPage = url.searchParams.get('per_page');

        url.searchParams.delete('rider_id');
        url.searchParams.delete('designation');
        url.searchParams.delete('customer_id');
        url.searchParams.delete('VID');
        url.searchParams.delete('bike_assignment_status');
        url.searchParams.delete('wps_status');
        url.searchParams.delete('billing_month');
        url.searchParams.delete('from_month');
        url.searchParams.delete('to_month');
        url.searchParams.delete('quick_search');

        if (rider_id) url.searchParams.set('rider_id', rider_id);
        if (designation) url.searchParams.set('designation', designation);
        if (customer_id) url.searchParams.set('customer_id', customer_id);
        if (bike_assignment_status) url.searchParams.set('bike_assignment_status', bike_assignment_status);
        if (wps_status) url.searchParams.set('wps_status', wps_status);
        if (from_month) url.searchParams.set('from_month', from_month);
        if (to_month) url.searchParams.set('to_month', to_month);
        if (quick_search) url.searchParams.set('quick_search', quick_search);

        if (perPage) url.searchParams.set('per_page', perPage);

        window.history.pushState({}, '', url.toString());
    }

    function loadFiltersFromURL() {
        const url = new URL(window.location);

        const rider_id = url.searchParams.get('rider_id');
        const designation = url.searchParams.get('designation');
        const customer_id = url.searchParams.get('customer_id');
        const bike_assignment_status = url.searchParams.get('bike_assignment_status');
        const wps_status = url.searchParams.get('wps_status');
        const from_month = url.searchParams.get('from_month') || url.searchParams.get('billing_month');
        const to_month = url.searchParams.get('to_month') || url.searchParams.get('billing_month');
        const quick_search = url.searchParams.get('quick_search');

        if (rider_id) $('#rider_id').val(rider_id).trigger('change');
        if (designation) $('#designation').val(designation).trigger('change');
        if (customer_id) $('#customer_id').val(customer_id).trigger('change');
        if (bike_assignment_status) $('#bike_assignment_status').val(bike_assignment_status).trigger('change');
        if (wps_status) $('#wps_status').val(wps_status).trigger('change');
        if (from_month) $('#from_month').val(from_month);
        if (to_month) $('#to_month').val(to_month);
        if (quick_search) $('#quickSearch').val(quick_search);
    }
</script>
@endpush