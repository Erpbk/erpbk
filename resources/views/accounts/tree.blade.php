@extends('layouts.app')
@section('title','Chart of Accounts')
@section('content')
@push('third_party_stylesheets')
<style>
    /* Right-side sliding ledger panel */
    #ledgerSlidePanel {
        position: fixed;
        top: 0;
        right: 0;
        width: 60%;
        max-width: 95vw;
        height: 100%;
        background: #fff;
        box-shadow: -2px 0 16px rgba(0, 0, 0, 0.12);
        z-index: 10500;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    #ledgerSlidePanel.open {
        transform: translateX(0);
    }

    #ledgerSlidePanel .ledger-panel-header {
        flex-shrink: 0;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8f9fa;
    }

    #ledgerSlidePanel .ledger-panel-header h6 {
        margin: 0;
        font-weight: 600;
    }

    #ledgerSlidePanel .ledger-panel-close {
        width: 32px;
        height: 32px;
        padding: 0;
        border: none;
        background: transparent;
        color: #6c757d;
        cursor: pointer;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    #ledgerSlidePanel .ledger-panel-close:hover {
        background: #e9ecef;
        color: #212529;
    }

    #ledgerSlidePanel .ledger-panel-body {
        flex: 1;
        overflow: auto;
        padding: 1rem;
    }

    #ledgerSlidePanel .ledger-placeholder {
        color: #6c757d;
        text-align: center;
        padding: 2rem 1rem;
    }

    /* Chart table */
    #chartAccountsTable {
        table-layout: fixed;
    }

    #chartAccountsTable thead th {
        font-weight: 600;
        white-space: nowrap;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    #chartAccountsTable .chart-account-row.child-row.expanded {
        display: table-row !important;
    }

    #chartAccountsTable .chart-account-row.child-row {
        display: none;
    }

    #chartAccountsTable .chart-account-row:hover {
        background-color: #f8f9fa;
    }

    #chartAccountsTable .tree-lines {
        position: relative;
        display: inline-block;
        width: 20px;
        vertical-align: middle;
    }

    #chartAccountsTable .tree-lines::before {
        content: '';
        position: absolute;
        left: 9px;
        top: 0;
        bottom: 0;
        width: 1px;
        background: #dee2e6;
    }

    #chartAccountsTable .tree-lines .tree-joint {
        position: absolute;
        left: 9px;
        top: 50%;
        width: 10px;
        height: 1px;
        background: #dee2e6;
    }

    #chartAccountsTable th.col-hidden,
    #chartAccountsTable td.col-hidden {
        display: none !important;
    }

    .chart-table-filter-bar {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-bottom: none;
        padding: 0.75rem 1rem;
        border-radius: 0.25rem 0.25rem 0 0;
    }

    /* Ledger panel: scrollable table */
    #ledgerSlidePanel .ledger-table-scroll {
        max-height: 50vh;
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }

    #ledgerSlidePanel .ledger-table thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 1;
    }
</style>
@endpush
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Chart of Accounts</h3>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-primary float-end action-btn show-modal"
                    href="javascript:void(0);" data-action="{{ route('accounts.create') }}" data-size="lg" data-title="New Account">
                    Add New
                </a>
            </div>
        </div>
    </div>
</section>
<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="container-fluid p-0">
            {{-- Filter bar: quick search + column control --}}
            <div class="chart-table-filter-bar d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <label class="form-label mb-0 text-nowrap small text-muted">Quick search</label>
                    <input type="text" id="chartQuickSearch" class="form-control form-control-sm" placeholder="Search by name, code, type..." style="max-width: 280px;" autocomplete="off">
                </div>
                <div class="dropdown">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" id="chartColumnToggle" data-bs-toggle="dropdown" aria-expanded="false" title="Show/hide columns">
                        <i class="fa fa-columns me-1"></i> Columns
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" id="chartColumnMenu" onclick="event.stopPropagation()">
                        <li class="dropdown-header small">Show columns</li>
                        <li><label class="dropdown-item d-flex align-items-center gap-2 mb-0 cursor-pointer"><input type="checkbox" class="chart-col-toggle form-check-input" data-col="account-name" disabled> Account Name</label></li>
                        <li><label class="dropdown-item d-flex align-items-center gap-2 mb-0 cursor-pointer"><input type="checkbox" class="chart-col-toggle form-check-input" data-col="account-code"> Account Code</label></li>
                        <li><label class="dropdown-item d-flex align-items-center gap-2 mb-0 cursor-pointer"><input type="checkbox" class="chart-col-toggle form-check-input" data-col="account-type"> Account Type</label></li>
                        <li><label class="dropdown-item d-flex align-items-center gap-2 mb-0 cursor-pointer"><input type="checkbox" class="chart-col-toggle form-check-input" data-col="parent-account"> Parent Account</label></li>
                        <li><label class="dropdown-item d-flex align-items-center gap-2 mb-0 cursor-pointer"><input type="checkbox" class="chart-col-toggle form-check-input" data-col="status"> Status</label></li>
                        <li><label class="dropdown-item d-flex align-items-center gap-2 mb-0 cursor-pointer"><input type="checkbox" class="chart-col-toggle form-check-input" data-col="documents"> Documents</label></li>
                        <li><label class="dropdown-item d-flex align-items-center gap-2 mb-0 cursor-pointer"><input type="checkbox" class="chart-col-toggle form-check-input" data-col="actions" disabled> Actions</label></li>
                    </ul>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="chartAccountsTable">
                    <thead>
                        <tr>
                            <th data-col="account-name" style="width: 410px;">Account Name</th>
                            <th data-col="account-code" class="text-nowrap" style="width: 520px;">Account Code</th>
                            <th data-col="account-type" class="text-nowrap" style="width: 540px;">Account Type</th>
                            <th data-col="parent-account" class="text-nowrap" style="width: 240px;">Parent Account</th>
                            <th data-col="status" class="text-nowrap" style="width: 90px;">Status</th>
                            <th data-col="actions" class="text-end text-nowrap" style="width: 240px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="chartAccountsTbody">
                        @php
                        $typesInData = $accounts->pluck('account_type')->unique()->filter()->sort()->values();
                        @endphp
                        @foreach ($typesInData as $typeValue)
                        @php
                        $rootsOfType = $accounts->where('account_type', $typeValue)->values();
                        @endphp
                        @if($rootsOfType->count() > 0)
                        <tr class="table-light type-header-row" data-account-type="{{ $typeValue }}">
                            <td colspan="7" class="fw-bold py-2">{{ $typeValue }}</td>
                        </tr>
                        @foreach ($rootsOfType as $account)
                        @include('accounts.account_table_row', ['account' => $account, 'depth' => 0])
                        @endforeach
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Right-side sliding ledger panel --}}
<div id="ledgerSlidePanel">
    <div class="ledger-panel-header">
        <h6>Ledger</h6>
        <button type="button" class="ledger-panel-close" id="ledgerPanelClose" title="Close" aria-label="Close"><i class="fa fa-times"></i></button>
    </div>
    <div class="ledger-panel-body">
        <div class="ledger-placeholder" id="chartLedgerPlaceholder">Select an account to view its ledger</div>
        <div id="chartLedgerContent" style="display: none;"></div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const detailUrl = '{{ url("accounts/detail") }}';
        const panel = document.getElementById('ledgerSlidePanel');
        const placeholder = document.getElementById('chartLedgerPlaceholder');
        const content = document.getElementById('chartLedgerContent');
        const closeBtn = document.getElementById('ledgerPanelClose');

        function openLedgerPanel() {
            panel.classList.add('open');
        }

        function closeLedgerPanel() {
            panel.classList.remove('open');
        }

        closeBtn.addEventListener('click', function() {
            closeLedgerPanel();
        });

        // Expand/collapse: checkbox toggles child rows in table
        document.getElementById('chartAccountsTbody').addEventListener('click', function(e) {
            var checkbox = e.target.closest('.tree-expand-check');
            if (!checkbox) return;
            e.stopPropagation();
            var row = checkbox.closest('tr.chart-account-row');
            if (!row) return;
            var accountId = row.getAttribute('data-account-id');
            if (!accountId) return;
            var childRows = document.querySelectorAll('#chartAccountsTbody tr.chart-account-row.child-row[data-parent-id="' + accountId + '"]');
            var isExpanded = row.classList.toggle('expanded');
            childRows.forEach(function(r) {
                r.style.display = isExpanded ? 'table-row' : 'none';
            });
            checkbox.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        });

        // Quick search filter
        var quickSearchEl = document.getElementById('chartQuickSearch');
        if (quickSearchEl) {
            quickSearchEl.addEventListener('input', function() {
                var q = (this.value || '').toLowerCase().trim();
                var tbody = document.getElementById('chartAccountsTbody');
                if (!tbody) return;
                var typeRows = tbody.querySelectorAll('tr.type-header-row');
                var dataRows = tbody.querySelectorAll('tr.chart-account-row');
                if (!q) {
                    dataRows.forEach(function(r) {
                        var depth = parseInt(r.getAttribute('data-depth'), 10) || 0;
                        var parentId = r.getAttribute('data-parent-id');
                        if (depth === 0) {
                            r.style.display = 'table-row';
                            r.classList.remove('filter-hidden');
                        } else {
                            var parent = parentId ? document.querySelector('tr.chart-account-row[data-account-id="' + parentId + '"]') : null;
                            var parentVisible = !parent || (parent.style.display !== 'none' && !parent.classList.contains('filter-hidden'));
                            var parentExpanded = parent && parent.classList.contains('expanded');
                            r.style.display = (parentVisible && parentExpanded) ? 'table-row' : 'none';
                            r.classList.remove('filter-hidden');
                        }
                    });
                    typeRows.forEach(function(tr) {
                        tr.style.display = 'table-row';
                    });
                    return;
                }
                dataRows.forEach(function(r) {
                    var search = (r.getAttribute('data-search') || '').toLowerCase();
                    var match = search.indexOf(q) !== -1;
                    r.classList.toggle('filter-hidden', !match);
                    if (match) r.style.display = 'table-row';
                });
                dataRows.forEach(function(r) {
                    if (r.classList.contains('filter-hidden')) return;
                    var depth = parseInt(r.getAttribute('data-depth'), 10) || 0;
                    var parentId = r.getAttribute('data-parent-id');
                    if (depth > 0 && parentId) {
                        var parent = document.querySelector('tr.chart-account-row[data-account-id="' + parentId + '"]');
                        if (parent && parent.classList.contains('filter-hidden')) {
                            parent.classList.remove('filter-hidden');
                            parent.style.display = 'table-row';
                            if (!parent.classList.contains('expanded')) {
                                parent.classList.add('expanded');
                                var childRows = document.querySelectorAll('#chartAccountsTbody tr.chart-account-row.child-row[data-parent-id="' + parentId + '"]');
                                childRows.forEach(function(cr) {
                                    cr.style.display = 'table-row';
                                });
                                var cb = parent.querySelector('.tree-expand-check');
                                if (cb) cb.setAttribute('aria-expanded', 'true');
                            }
                        }
                    }
                });
                dataRows.forEach(function(r) {
                    if (r.classList.contains('filter-hidden')) r.style.display = 'none';
                    else if (r.classList.contains('child-row')) {
                        var parentId = r.getAttribute('data-parent-id');
                        var parent = parentId ? document.querySelector('tr.chart-account-row[data-account-id="' + parentId + '"]') : null;
                        var show = parent && parent.classList.contains('expanded') && !parent.classList.contains('filter-hidden');
                        r.style.display = show ? 'table-row' : 'none';
                    }
                });
                typeRows.forEach(function(tr) {
                    var typeVal = (tr.getAttribute('data-account-type') || '').toLowerCase();
                    var typeMatch = typeVal.indexOf(q) !== -1;
                    var next = tr.nextElementSibling;
                    var hasVisible = false;
                    while (next && !next.classList.contains('type-header-row')) {
                        if (next.classList.contains('chart-account-row') && !next.classList.contains('filter-hidden') && next.style.display !== 'none') {
                            hasVisible = true;
                            break;
                        }
                        next = next.nextElementSibling;
                    }
                    tr.style.display = (typeMatch || hasVisible) ? 'table-row' : 'none';
                });
            });
        }

        // Column visibility: load from localStorage and apply
        var COL_STORAGE_KEY = 'chartAccountsVisibleColumns';

        function getStoredColumns() {
            try {
                var s = localStorage.getItem(COL_STORAGE_KEY);
                if (s) return JSON.parse(s);
            } catch (e) {}
            return {
                'account-code': true,
                'account-type': true,
                'parent-account': true,
                'status': true,
                'documents': true
            };
        }

        function setStoredColumns(obj) {
            try {
                localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(obj));
            } catch (e) {}
        }

        function applyColumnVisibility() {
            var visible = getStoredColumns();
            document.querySelectorAll('#chartAccountsTable [data-col]').forEach(function(cell) {
                var col = cell.getAttribute('data-col');
                if (col === 'account-name' || col === 'actions') return;
                if (visible[col] === false) cell.classList.add('col-hidden');
                else cell.classList.remove('col-hidden');
            });
            document.querySelectorAll('.chart-col-toggle:not([disabled])').forEach(function(cb) {
                var col = cb.getAttribute('data-col');
                cb.checked = visible[col] !== false;
            });
        }
        applyColumnVisibility();
        document.getElementById('chartColumnMenu').addEventListener('change', function(e) {
            var cb = e.target.closest('.chart-col-toggle');
            if (!cb || cb.disabled) return;
            var col = cb.getAttribute('data-col');
            var visible = getStoredColumns();
            visible[col] = cb.checked;
            setStoredColumns(visible);
            document.querySelectorAll('#chartAccountsTable [data-col="' + col + '"]').forEach(function(cell) {
                if (cb.checked) cell.classList.remove('col-hidden');
                else cell.classList.add('col-hidden');
            });
        });

        function loadLedgerIntoPanel(id) {
            if (!id) return;
            placeholder.style.display = 'none';
            content.style.display = 'block';
            content.innerHTML = '<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x text-muted"></i></div>';
            openLedgerPanel();
            fetch(detailUrl + '/' + id, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    content.innerHTML = data.html || '<p class="text-muted">Unable to load ledger.</p>';
                    var closeInContent = content.querySelector('.chart-detail-close');
                    if (closeInContent) {
                        closeInContent.addEventListener('click', function() {
                            closeLedgerPanel();
                        });
                    }
                })
                .catch(function() {
                    content.innerHTML = '<p class="text-danger">Failed to load ledger.</p>';
                });
        }

        document.querySelectorAll('.view-ledger').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.stopPropagation();
                loadLedgerIntoPanel(this.getAttribute('data-id'));
            });
        });

        // Ledger AJAX pagination (no page reload)
        var ledgerEntriesUrl = '{{ url("accounts/detail") }}';
        document.addEventListener('click', function(e) {
            var prevBtn = e.target.closest('#chartLedgerContent .ledger-page-prev');
            var nextBtn = e.target.closest('#chartLedgerContent .ledger-page-next');
            var btn = prevBtn || nextBtn;
            if (!btn || btn.disabled) return;
            e.preventDefault();
            var paginationEl = document.getElementById('ledgerPagination');
            if (!paginationEl) return;
            var accountId = paginationEl.getAttribute('data-account-id');
            var currency = paginationEl.getAttribute('data-currency') || 'bcy';
            var perPage = paginationEl.getAttribute('data-per-page') || '25';
            var page = btn.getAttribute('data-page');
            if (!page || !accountId) return;
            var tbody = document.getElementById('ledgerTableBody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>';
            fetch(ledgerEntriesUrl + '/' + accountId + '/ledger-entries?page=' + page + '&per_page=' + perPage + '&currency=' + currency, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    if (tbody) tbody.innerHTML = data.html || '<tr><td colspan="6" class="text-center text-muted py-3">No entries.</td></tr>';
                    var p = data.pagination;
                    if (!p || !paginationEl) return;
                    var infoEl = paginationEl.querySelector('.ledger-pagination-info');
                    if (infoEl) infoEl.textContent = 'Showing ' + (p.from || 0) + '–' + (p.to || 0) + ' of ' + (p.total || 0);
                    var pageInfoEl = paginationEl.querySelector('.ledger-page-info');
                    if (pageInfoEl) pageInfoEl.textContent = 'Page ' + p.current_page + ' of ' + p.last_page;
                    var prev = paginationEl.querySelector('.ledger-page-prev');
                    var next = paginationEl.querySelector('.ledger-page-next');
                    if (prev) {
                        prev.disabled = p.current_page <= 1;
                        prev.setAttribute('data-page', p.current_page - 1);
                    }
                    if (next) {
                        next.disabled = p.current_page >= p.last_page;
                        next.setAttribute('data-page', p.current_page + 1);
                    }
                })
                .catch(function() {
                    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Failed to load.</td></tr>';
                });
        });

        // Lock toggle (in dropdown)
        document.addEventListener('click', function(e) {
            var lock = e.target.closest('.lock-toggle');
            if (lock) {
                e.preventDefault();
                e.stopPropagation();
                var accountId = lock.getAttribute('data-account-id');
                var url = '{{ url("accounts/accounts") }}/' + accountId + '/toggle-lock';
                fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            lock.innerHTML = (data.is_locked ? '<i class="fa fa-unlock me-2"></i> Unlock' : '<i class="fa fa-lock me-2"></i> Lock');
                            var row = document.querySelector('tr.chart-account-row[data-account-id="' + accountId + '"]');
                            if (row) {
                                var lockIcon = row.querySelector('.tree-lock i');
                                if (lockIcon) lockIcon.className = 'fas ' + (data.is_locked ? 'fa-lock text-secondary' : 'fa-unlock text-success');
                            }
                        }
                    });
            }
        });

        // Delete account (from dropdown)
        document.addEventListener('click', function(e) {
            var del = e.target.closest('.delete-account');
            if (del && !del.disabled) {
                e.preventDefault();
                if (!confirm('Are you sure? You will not be able to revert this!')) return;
                var url = del.getAttribute('data-url');
                var id = del.getAttribute('data-id');
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(function(r) {
                    if (r.ok || r.status === 204) {
                        var row = document.querySelector('tr.chart-account-row[data-account-id="' + id + '"]');
                        if (row && row.parentNode) row.parentNode.removeChild(row);
                    } else {
                        return r.json().then(function(d) {
                            var msg = (d.errors && d.errors.error) ? d.errors.error : (d.message || 'Delete failed');
                            alert(msg);
                        }).catch(function() {
                            alert('Delete failed');
                        });
                    }
                }).catch(function() {
                    alert('Delete failed');
                });
            }
            if (e.target.closest('.edit-btn[disabled]')) {
                e.preventDefault();
                e.stopPropagation();
            }
        });

        // Toggle status (from dropdown)
        document.querySelectorAll('.toggle-status').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.stopPropagation();
                var url = this.getAttribute('data-url');
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                }).then(function(r) {
                    return r.json();
                }).then(function(data) {
                    if (data.success) {
                        var label = el.querySelector('i');
                        if (label) label.className = 'fa fa-' + (data.is_active ? 'pause-circle-o' : 'play-circle-o') + ' me-2';
                        el.innerHTML = (data.is_active ? '<i class="fa fa-pause-circle-o me-2"></i> Mark as Inactive' : '<i class="fa fa-play-circle-o me-2"></i> Mark as Active');
                        el.setAttribute('data-active', data.is_active ? '1' : '0');
                    }
                });
            });
        });
    });
</script>
@endsection