@extends('layouts.app')
@section('title','Rider Report')

@push('third_party_stylesheets')
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/employees-demo.css') }}">
<link rel="stylesheet" href="{{ asset('css/rider-report-demo.css') }}?v={{ @filemtime(public_path('css/rider-report-demo.css')) ?: '1' }}">
@endpush

@section('content')
<style>
    .loading-overlay {
        position: fixed;
        inset: 0;
        background: rgba(255, 255, 255, 0.9);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
    }
    .loading-overlay.show { display: flex; }
    .rr-demo .badge {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 10px;
        border-radius: 999px;
    }
    .rr-demo .bg-label-success { background-color: #dcfce7; color: #15803d; }
    .rr-demo .bg-label-danger { background-color: #fee2e2; color: #dc2626; }
    .rr-demo .bg-label-secondary { background-color: #eceff3; color: #6b7280; }
</style>

<div class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>

@php
$tableColumns = [
    ['data' => 'id', 'title' => 'ID'],
    ['data' => 'name', 'title' => 'Name'],
    ['data' => 'status', 'title' => 'Status'],
    ['data' => 'emirates', 'title' => 'Emirates'],
    ['data' => 'designation', 'title' => 'Designation'],
    ['data' => 'person_code', 'title' => 'Person Code'],
    ['data' => 'wps', 'title' => 'WPS'],
    ['data' => 'project', 'title' => 'Project'],
    ['data' => 'billing_month', 'title' => 'Billing Month'],
    ['data' => 'total_amount', 'title' => 'Invoice'],
    ['data' => 'vendor_charges', 'title' => 'Vendor Charges'],
    ['data' => 'cod', 'title' => 'COD'],
    ['data' => 'rta_fine', 'title' => 'RTA Fine'],
    ['data' => 'salik_fee', 'title' => 'Salik FEE'],
    ['data' => 'fuel', 'title' => 'Fuel'],
    ['data' => 'ma', 'title' => 'MA'],
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

<div class="rr-demo">
    <div class="rr-topbar">
        <div class="rr-topbar-inner">
            <strong>Rider Report</strong>
        </div>
    </div>

    <section class="rr-stats" id="totalsBar">
        <div class="rr-cards">
            <div class="rr-card c-riders">
                <div class="label">Riders</div>
                <div class="value" id="sum_riders_count">0</div>
                <div class="sub" id="sum_project_label">All Projects</div>
            </div>
            <div class="rr-card c-amount">
                <div class="label">Total Amount</div>
                <div class="value" id="sum_total_amount">0.00</div>
                <div class="sub"></div>
            </div>
            <div class="rr-card c-ded">
                <div class="label">Total Deductions</div>
                <div class="value" id="sum_total_deductions">0.00</div>
                <div class="sub"></div>
            </div>
            <div class="rr-card c-pay">
                <div class="label">Payable</div>
                <div class="value" id="sum_total_payable">0.00</div>
                <div class="sub" id="sum_payable_split"></div>
            </div>
            <div class="rr-card c-paid">
                <div class="label">Paid Amount</div>
                <div class="value" id="sum_total_paid">0.00</div>
                <div class="sub"></div>
            </div>
            <div class="rr-card c-bal">
                <div class="label">Balance</div>
                <div class="value" id="sum_total_balance">0.00</div>
                <div class="sub" id="sum_balance_split"></div>
            </div>
        </div>
    </section>

    <div class="rr-toolbar">
        <div class="rr-drop">
            <button type="button" class="rr-btn" id="reportActionsBtn">Actions ▾</button>
            <div class="rr-menu" id="reportActionsDropdown">
                <a class="exportToExcel" href="javascript:void(0);">
                    Export to Excel
                    <small>Download report as Excel file</small>
                </a>
                <a class="openColumnControlSidebar" href="javascript:void(0);">
                    Column Control
                    <small>Show/hide table columns</small>
                </a>
            </div>
        </div>
        <button type="button" class="rr-btn icon" id="copyBtn" title="Copy table" aria-label="Copy table">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        </button>
        <input type="text" id="quickSearch" name="quick_search" placeholder="Quick Search..." value="{{ request('quick_search') }}" aria-label="Quick search">
        <button type="button" class="rr-btn openFilterSidebar" id="openFilterSidebar">▾ Filters</button>
    </div>

    @include('components.column-control-panel', [
        'tableColumns' => $tableColumns,
        'exportRoute' => route('rider.exportCustomizableRiders'),
        'tableIdentifier' => 'rider_report_table'
    ])

    <div class="emp-hv" id="riderHv" role="tooltip" aria-hidden="true"></div>

    <div class="rr-wrap" id="table-data">
        <table id="dataTableBuilder" class="table">
            <thead>
                <tr>
                    <th title="ID">ID</th>
                    <th title="Name">Name</th>
                    <th title="Status">Status</th>
                    <th title="Emirates">Emirates</th>
                    <th title="Designation">Designation</th>
                    <th title="Person Code">Person Code</th>
                    <th title="WPS">WPS</th>
                    <th title="Project">Project</th>
                    <th title="Billing Month">Billing Month</th>
                    <th title="Invoice">Invoice</th>
                    <th title="Vendor Charges">Vendor Charges</th>
                    <th title="COD">COD</th>
                    <th title="RTA Fine">RTA Fine</th>
                    <th title="Salik FEE">Salik</th>
                    <th title="Fuel">Fuel</th>
                    <th title="Maintenance">MA</th>
                    <th title="Visa Installment">Visa Inst.</th>
                    <th title="Journal Voucher">JV</th>
                    <th title="Advance">Advance</th>
                    <th title="Penalty">Penalty</th>
                    <th title="Incentive">Incentive</th>
                    <th title="Previous Balance">Previous</th>
                    <th title="Payable">Payable</th>
                    <th title="Paid Amount">Paid</th>
                    <th title="Balance">Balance</th>
                </tr>
            </thead>
            <tbody id="get_data"></tbody>
        </table>
    </div>
    <div id="paginationLinks"></div>
</div>

<!-- Filter Sidebar -->
<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <div>
            <h5>Filter Riders</h5>
            <p class="rr-f-sub">Filter the report</p>
        </div>
        <button type="button" id="closeSidebar" aria-label="Close">&times;</button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm">
            <div class="form-group">
                <label for="rider_id">Rider</label>
                {!! Form::select('rider_id', \App\Models\Riders::dropdown(), request('rider_id'), ['class' => 'form-control form-select select2', 'id' => 'rider_id']) !!}
            </div>
            <div class="form-group">
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
            <div class="form-group">
                <label for="customer_id">Project</label>
                {!! Form::select('customer_id', \App\Models\Customers::dropdown(), request('customer_id'), ['class' => 'form-control form-select select2', 'id' => 'customer_id']) !!}
            </div>
            <div class="form-group">
                <label for="bike_assignment_status">Filter by Status</label>
                <select class="form-control form-select select2" id="bike_assignment_status" name="bike_assignment_status">
                    <option value="" selected>Select</option>
                    @foreach(($riderStatusFilterOptions ?? []) as $statusOption)
                    <option value="{{ $statusOption['value'] }}" {{ request('bike_assignment_status') == $statusOption['value'] ? 'selected' : '' }}>{{ $statusOption['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="wps_status">Filter by WPS Status</label>
                <select class="form-control form-select select2" id="wps_status" name="wps_status">
                    <option value="" selected>Select</option>
                    <option value="WPS" {{ request('wps_status') == 'WPS' ? 'selected' : '' }}>WPS</option>
                    <option value="NON/WPS" {{ request('wps_status') == 'NON/WPS' ? 'selected' : '' }}>NON/WPS</option>
                </select>
            </div>
            <div class="form-group">
                <label for="billing_month">Billing Month</label>
                <input type="month" id="billing_month" name="billing_month" value="{{ request('billing_month', date('Y-m')) }}" class="form-control" />
            </div>
            <button type="button" class="rr-apply" id="applyFiltersBtn">Apply Filters</button>
        </form>
    </div>
</div>

<!-- Filter Overlay -->
<div id="filterOverlay" class="filter-overlay"></div>
@endsection

@push('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="{{ URL::asset('export_excel/jquery.table2excel.js') }}?v={{ @filemtime(public_path('export_excel/jquery.table2excel.js')) ?: '1' }}"></script>
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
            if (!$(e.target).closest('.rr-drop').length) {
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

        $('#closeSidebar, #filterOverlay').on('click', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });

        $('#applyFiltersBtn').on('click', function() {
            get_data();
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
            $('#reportActionsDropdown').removeClass('show');
        });

        // Copy visible table (demo-style)
        $('#copyBtn').on('click', async function() {
            const btn = this;
            const table = document.getElementById('dataTableBuilder');
            if (!table) return;
            const headers = Array.from(table.querySelectorAll('thead th')).filter(th => !th.classList.contains('column-hidden'));
            const rows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.querySelector('.alert'));
            const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const cellText = (td) => {
                const clone = td.cloneNode(true);
                return (clone.innerText || '').replace(/\s+/g, ' ').trim();
            };
            const head = headers.map(h => (h.textContent || '').replace(/[⇅↑↓]/g, '').trim());
            const body = rows.map(tr => headers.map(th => {
                const key = th.dataset.columnKey;
                const td = key
                    ? tr.querySelector(`td[data-column-key="${key}"]`)
                    : tr.children[Array.from(th.parentNode.children).indexOf(th)];
                return td ? cellText(td) : '';
            }));
            const text = [head, ...body].map(r => r.join('\t')).join('\n');
            const html = '<table border="1"><tr>' + head.map(h => '<th>' + esc(h) + '</th>').join('') + '</tr>' +
                body.map(r => '<tr>' + r.map(c => '<td>' + esc(c) + '</td>').join('') + '</tr>').join('') + '</table>';
            const done = (ok) => {
                btn.classList.add(ok ? 'ok' : 'bad');
                setTimeout(() => btn.classList.remove('ok', 'bad'), 1600);
            };
            try {
                await navigator.clipboard.write([
                    new ClipboardItem({
                        'text/html': new Blob([html], { type: 'text/html' }),
                        'text/plain': new Blob([text], { type: 'text/plain' })
                    })
                ]);
                done(true);
            } catch (_) {
                try {
                    await navigator.clipboard.writeText(text);
                    done(true);
                } catch (__) {
                    done(false);
                }
            }
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

                controller.toggleColumnVisibility = function(_columnIndex, _isVisible, persist) {
                    scheduleApply();
                    this.updateColumnStats();
                    if (persist !== false && !this.isInitialLoad) {
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

    function toneValue($el, value) {
        $el.toggleClass('pos', value > 0).toggleClass('neg', value < 0);
    }

    function updateTotalsBar(data) {
        if (typeof data.total_amount === 'undefined') {
            return;
        }
        const payable = parseFloat(data.total_payable || 0);
        const balance = parseFloat(data.total_balance || 0);
        $('#sum_riders_count').text(parseInt(data.riders_count || 0, 10).toLocaleString());
        $('#sum_total_amount').text(formatMoney(data.total_amount));
        $('#sum_total_deductions').text(formatMoney(data.total_deductions));
        $('#sum_total_payable').text(formatMoney(payable));
        toneValue($('#sum_total_payable'), payable);
        $('#sum_total_paid').text(formatMoney(data.total_paid));
        $('#sum_total_balance').text(formatMoney(balance));
        toneValue($('#sum_total_balance'), balance);

        const projectName = ($('#customer_id option:selected').text() || '').trim();
        const projectVal = $('#customer_id').val();
        $('#sum_project_label').text(projectVal ? projectName : 'All Projects');
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
                    $('#get_data').html('<tr><td colspan="25"><div class="alert alert-danger mb-0"><i class="ti ti-alert-triangle"></i> ' + errorMessage + '</div></td></tr>');
                }
            }
        });
    }

    // Name hover card (photo → ID → name) — same pattern as employees list
    (function () {
        const tip = document.getElementById('riderHv');
        if (!tip) return;
        let hideT;
        const show = (a) => {
            clearTimeout(hideT);
            const photo = a.dataset.hvPhoto || '';
            const initials = a.dataset.hvInitials || '';
            const av = photo
                ? `<img class="ava" src="${photo}" alt="">`
                : `<div class="ava ph">${initials}</div>`;
            tip.innerHTML = `<div class="emp-hv-top">${av}<div class="idpill">${a.dataset.hvId || ''}</div><div class="nm">${a.dataset.hvName || ''}</div></div>`;
            tip.classList.add('show');
            tip.setAttribute('aria-hidden', 'false');
            const r = a.getBoundingClientRect();
            const tw = tip.offsetWidth || 230;
            const th = tip.offsetHeight || 220;
            let left = r.left;
            let top = r.bottom + 8;
            if (left + tw > window.innerWidth - 12) left = window.innerWidth - tw - 12;
            if (top + th > window.innerHeight - 12) top = r.top - th - 8;
            tip.style.left = Math.max(8, left) + 'px';
            tip.style.top = Math.max(8, top) + 'px';
        };
        const hide = () => {
            hideT = setTimeout(() => {
                tip.classList.remove('show');
                tip.setAttribute('aria-hidden', 'true');
            }, 120);
        };
        document.addEventListener('mouseover', (e) => {
            const a = e.target.closest('#dataTableBuilder .emp-name-link');
            if (a) show(a);
        });
        document.addEventListener('mouseout', (e) => {
            const a = e.target.closest('#dataTableBuilder .emp-name-link');
            if (a && !e.relatedTarget?.closest?.('.emp-hv')) hide();
        });
        tip.addEventListener('mouseenter', () => clearTimeout(hideT));
        tip.addEventListener('mouseleave', hide);
    })();

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

                $('#get_data').html('<tr><td colspan="24"><div class="alert alert-danger mb-0"><i class="ti ti-alert-triangle"></i> ' + errorMessage + '</div></td></tr>');
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