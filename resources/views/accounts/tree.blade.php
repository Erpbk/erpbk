@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('content')
@php $__companySlug = \App\Support\CompanyRouteContext::slug(); @endphp
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

    #ledgerSlidePanel .coa-parent-hint {
        border: 1px dashed #ced4da;
        border-radius: 0.375rem;
        background: #f8f9fa;
        padding: 1.5rem;
        text-align: center;
        color: #6c757d;
    }

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

    #chartAccountsTable .chart-account-row.child-row {
        display: none;
    }

    #chartAccountsTable .chart-account-row.child-row.expanded {
        display: table-row !important;
    }

    #chartAccountsTable .chart-account-row:hover {
        background-color: #f8f9fa;
    }

    #chartAccountsTable .chart-account-row.selected {
        background-color: rgba(105, 108, 255, 0.1) !important;
    }

    #chartAccountsTable .chart-account-row.is-parent .view-ledger {
        color: #495057;
        font-weight: 600;
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

    .chart-table-filter-bar {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-bottom: none;
        padding: 0.75rem 1rem;
        border-radius: 0.25rem 0.25rem 0 0;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    /* Hidden pads so shared column-control (expects 2 trailing fixed cols) stays aligned */
    #chartAccountsTable th.coa-cc-pad,
    #chartAccountsTable td.coa-cc-pad {
        width: 0 !important;
        max-width: 0 !important;
        padding: 0 !important;
        border: none !important;
        overflow: hidden;
        font-size: 0;
        line-height: 0;
    }

    /* Tree expand arrows (same pattern as Profit & Loss) */
    #chartAccountsTable .tree-cell {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    #chartAccountsTable .tree-toggle {
        width: 22px;
        height: 22px;
        border: 1px solid #d0d7de;
        border-radius: 4px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        cursor: pointer;
        flex: 0 0 22px;
    }
    #chartAccountsTable .tree-toggle i {
        transition: transform 0.2s ease;
    }
    #chartAccountsTable .chart-account-row.is-collapsed .tree-toggle i {
        transform: rotate(-90deg);
    }
    #chartAccountsTable .tree-leaf-spacer {
        width: 22px;
        height: 22px;
        display: inline-block;
        flex: 0 0 22px;
    }
    #chartAccountsTable .chart-account-row.is-parent {
        cursor: pointer;
    }
    #chartAccountsTable .chart-account-row.loading-children .tree-toggle {
        opacity: 0.6;
        pointer-events: none;
    }
</style>
@endpush

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h3 class="mb-0">Chart of Accounts</h3>
            </div>
            <div class="col-sm-6 text-sm-end">
                @can('accounts_coa_create')
                <a class="btn btn-primary action-btn show-modal" href="javascript:void(0);"
                    data-action="{{ route('accounts.create', ['company_slug' => $__companySlug]) }}"
                    data-size="lg" data-title="New Account">
                    <i class="fa fa-plus me-1"></i> Add New
                </a>
                @endcan
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>

    @php
    $tableColumns = [
        ['data' => 'account_name', 'title' => 'Account Name'],
        ['data' => 'account_code', 'title' => 'Account Code'],
        ['data' => 'branch', 'title' => 'Branch'],
        ['data' => 'account_type', 'title' => 'Account Type'],
        ['data' => 'parent_account', 'title' => 'Parent Account'],
        ['data' => 'status', 'title' => 'Status'],
        ['data' => 'action', 'title' => 'Actions'],
        ['data' => 'search', 'title' => 'Search'],
        ['data' => 'control', 'title' => 'Control'],
    ];
    @endphp
    @include('components.column-control-panel', [
        'tableId' => 'chartAccountsTable',
        'tableColumns' => $tableColumns,
        'tableIdentifier' => 'chart_accounts_table',
    ])

    <div class="card">
        <div class="container-fluid p-0">
            <div class="chart-table-filter-bar">
                <div class="input-group input-group-sm flex-grow-1" style="max-width: 300px;">
                    <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                    <input type="text" id="chartQuickSearch" class="form-control" placeholder="Search by name, code, type..." autocomplete="off">
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto openColumnControlSidebar" title="Column Control">
                    <i class="fa fa-columns me-1"></i> Columns
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="chartAccountsTable">
                    <thead>
                        <tr>
                            <th data-col="account-name" style="width: 32%;">Account Name</th>
                            <th data-col="account-code" class="text-nowrap" style="width: 12%;">Account Code</th>
                            <th data-col="branch" class="text-nowrap" style="width: 12%;">Branch</th>
                            <th data-col="account-type" class="text-nowrap" style="width: 12%;">Account Type</th>
                            <th data-col="parent-account" class="text-nowrap" style="width: 14%;">Parent Account</th>
                            <th data-col="status" class="text-nowrap" style="width: 10%;">Status</th>
                            <th data-col="actions" class="text-end text-nowrap" style="width: 8%;">Actions</th>
                            <th class="coa-cc-pad" aria-hidden="true"></th>
                            <th class="coa-cc-pad" aria-hidden="true"></th>
                        </tr>
                    </thead>
                    <tbody id="chartAccountsTbody">
                        @include('accounts.tree_tbody', [
                            'accounts' => $accounts,
                            'globalLinkedAccountIds' => $globalLinkedAccountIds ?? [],
                            'includeChildren' => false,
                        ])
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Right-side sliding ledger panel --}}
<div id="ledgerSlidePanel">
    <div class="ledger-panel-header">
        <h6>Account details</h6>
        <button type="button" class="ledger-panel-close" id="ledgerPanelClose" title="Close" aria-label="Close"><i class="fa fa-times"></i></button>
    </div>
    <div class="ledger-panel-body">
        <div class="ledger-placeholder" id="chartLedgerPlaceholder">Select an account to view its ledger</div>
        <div id="chartLedgerContent" style="display: none;"></div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var detailUrl = '{{ route("accounts.detail", ["company_slug" => $__companySlug, "id" => "__ID__"]) }}';
    var ledgerEntriesUrl = '{{ route("accounts.detail", ["company_slug" => $__companySlug, "id" => 0]) }}'.replace(/\/0(\?.*)?$/, '');
    var treeChildrenUrl = '{{ route("accounts.treeChildren", ["company_slug" => $__companySlug, "id" => "__ID__"]) }}';
    var treeSearchUrl = '{{ route("accounts.treeSearch", ["company_slug" => $__companySlug]) }}';
    var panel = document.getElementById('ledgerSlidePanel');
    var placeholder = document.getElementById('chartLedgerPlaceholder');
    var content = document.getElementById('chartLedgerContent');
    var closeBtn = document.getElementById('ledgerPanelClose');
    var tbody = document.getElementById('chartAccountsTbody');
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var rootsHtmlCache = tbody.innerHTML;
    var searchMode = false;

    function openLedgerPanel() {
        panel.classList.add('open');
    }

    function closeLedgerPanel() {
        panel.classList.remove('open');
        document.querySelectorAll('#chartAccountsTbody tr.chart-account-row.selected').forEach(function(r) {
            r.classList.remove('selected');
        });
    }

    function setSelectedRow(tr) {
        document.querySelectorAll('#chartAccountsTbody tr.chart-account-row.selected').forEach(function(r) {
            r.classList.remove('selected');
        });
        if (tr) tr.classList.add('selected');
    }

    function bindDetailPanelChrome() {
        [].slice.call(content.querySelectorAll('.show-modal')).forEach(function(el) {
            if (window.attachModal) window.attachModal(el);
        });
        var closeInContent = content.querySelector('.chart-detail-close');
        if (closeInContent) {
            closeInContent.addEventListener('click', closeLedgerPanel);
        }
    }

    function loadLedgerIntoPanel(id) {
        if (!id) return;
        placeholder.style.display = 'none';
        content.style.display = 'block';
        content.innerHTML = '<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x text-muted"></i></div>';
        openLedgerPanel();

        var url = detailUrl.replace('__ID__', id);
        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) {
            return r.json().then(function(data) {
                return { ok: r.ok, data: data };
            });
        })
        .then(function(res) {
            if (!res.ok) {
                content.innerHTML = '<div class="coa-parent-hint"><p class="mb-0">' +
                    (res.data && res.data.message ? res.data.message : 'Unable to load account details.') +
                    '</p></div>';
                return;
            }
            content.innerHTML = (res.data && res.data.html) ? res.data.html : '<p class="text-muted">Unable to load ledger.</p>';
            bindDetailPanelChrome();
        })
        .catch(function() {
            content.innerHTML = '<p class="text-danger">Failed to load ledger.</p>';
        });
    }

    closeBtn.addEventListener('click', closeLedgerPanel);

    function setChildrenVisible(accountId, show) {
        var childRows = tbody.querySelectorAll('tr.chart-account-row.child-row[data-parent-id="' + accountId + '"]');
        childRows.forEach(function(r) {
            if (show) {
                r.classList.add('expanded');
                r.style.display = 'table-row';
            } else {
                r.classList.remove('expanded');
                r.style.display = 'none';
                if (r.getAttribute('data-has-children') === '1') {
                    syncToggleState(r, false);
                    var id = r.getAttribute('data-account-id');
                    if (id) setChildrenVisible(id, false);
                }
            }
        });
    }

    function syncToggleState(row, expanded) {
        if (!row) return;
        var toggle = row.querySelector('.tree-toggle');
        if (!toggle) return;
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        if (expanded) {
            row.classList.add('expanded');
            row.classList.remove('is-collapsed');
        } else {
            row.classList.remove('expanded');
            row.classList.add('is-collapsed');
        }
    }

    function reapplyColumnSettings() {
        if (window.ColumnController && typeof window.ColumnController.reapplySettings === 'function') {
            window.ColumnController.reapplySettings();
        }
    }

    function loadChildrenForRow(row) {
        return new Promise(function(resolve, reject) {
            if (!row || row.getAttribute('data-has-children') !== '1') {
                resolve();
                return;
            }
            if (row.getAttribute('data-children-loaded') === '1') {
                resolve();
                return;
            }
            var accountId = row.getAttribute('data-account-id');
            var depth = (parseInt(row.getAttribute('data-depth'), 10) || 0) + 1;
            var url = treeChildrenUrl.replace('__ID__', accountId) + '?depth=' + depth;
            row.classList.add('loading-children');
            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
            .then(function(res) {
                row.classList.remove('loading-children');
                if (!res.ok) {
                    reject(new Error((res.data && res.data.message) || 'Failed to load children'));
                    return;
                }
                var html = (res.data && res.data.html) ? res.data.html : '';
                if (html) {
                    row.insertAdjacentHTML('afterend', html);
                }
                row.setAttribute('data-children-loaded', '1');
                reapplyColumnSettings();
                resolve();
            })
            .catch(function(err) {
                row.classList.remove('loading-children');
                reject(err);
            });
        });
    }

    function toggleParentRow(row) {
        if (!row || row.getAttribute('data-has-children') !== '1') return;
        if (row.classList.contains('loading-children')) return;

        var accountId = row.getAttribute('data-account-id');
        var wantExpand = row.classList.contains('is-collapsed') || !row.classList.contains('expanded');

        if (!wantExpand) {
            syncToggleState(row, false);
            setChildrenVisible(accountId, false);
            return;
        }

        row.classList.add('loading-children');
        loadChildrenForRow(row).then(function() {
            syncToggleState(row, true);
            setChildrenVisible(accountId, true);
        }).catch(function() {
            syncToggleState(row, false);
            if (window.Swal) Swal.fire('Error', 'Failed to load child accounts', 'error');
        }).finally(function() {
            row.classList.remove('loading-children');
        });
    }

    tbody.addEventListener('click', function(e) {
        var menuLedger = e.target.closest('.dropdown-menu .view-ledger');
        if (menuLedger) {
            e.preventDefault();
            var menuRow = menuLedger.closest('tr.chart-account-row');
            var menuId = menuLedger.getAttribute('data-id');
            setSelectedRow(menuRow);
            if (menuId) loadLedgerIntoPanel(menuId);
            return;
        }

        if (e.target.closest('.dropdown, .show-modal, .lock-toggle, .toggle-status, .delete-account, .table-actions-dropdown')) {
            return;
        }

        var row = e.target.closest('tr.chart-account-row');
        if (!row) return;

        var hasChildren = row.getAttribute('data-has-children') === '1';

        if (hasChildren || e.target.closest('.tree-toggle')) {
            e.preventDefault();
            setSelectedRow(row);
            toggleParentRow(row);
            return;
        }

        var link = e.target.closest('.view-ledger');
        var id = (link && link.getAttribute('data-id')) || row.getAttribute('data-account-id');
        setSelectedRow(row);
        if (id) loadLedgerIntoPanel(id);
    });

    // Quick search (server-side — only matches + ancestors are rendered)
    var quickSearchEl = document.getElementById('chartQuickSearch');
    var searchTimer = null;
    if (quickSearchEl) {
        quickSearchEl.addEventListener('input', function() {
            var q = (this.value || '').trim();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                if (!q) {
                    searchMode = false;
                    tbody.innerHTML = rootsHtmlCache;
                    reapplyColumnSettings();
                    return;
                }
                searchMode = true;
                tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-3"><i class="fa fa-spinner fa-spin me-2"></i>Searching...</td></tr>';
                fetch(treeSearchUrl + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    tbody.innerHTML = data.html || '<tr><td colspan="9" class="text-center text-muted py-4">No accounts found.</td></tr>';
                    reapplyColumnSettings();
                })
                .catch(function() {
                    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4">Search failed.</td></tr>';
                });
            }, 280);
        });
    }

    // Ledger pagination
    document.addEventListener('click', function(e) {
        var prevBtn = e.target.closest('#chartLedgerContent .ledger-page-prev');
        var nextBtn = e.target.closest('#chartLedgerContent .ledger-page-next');
        var btn = prevBtn || nextBtn;
        if (!btn || btn.disabled) return;
        e.preventDefault();
        var paginationEl = document.getElementById('ledgerPagination');
        if (!paginationEl) return;
        var accountId = paginationEl.getAttribute('data-account-id');
        var perPage = paginationEl.getAttribute('data-per-page') || '25';
        var page = btn.getAttribute('data-page');
        if (!page || !accountId) return;
        var ledgerTbody = document.getElementById('ledgerTableBody');
        if (ledgerTbody) ledgerTbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>';
        fetch(ledgerEntriesUrl + '/' + accountId + '/ledger-entries?page=' + page + '&per_page=' + perPage, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (ledgerTbody) ledgerTbody.innerHTML = data.html || '<tr><td colspan="6" class="text-center text-muted py-3">No entries.</td></tr>';
            var p = data.pagination;
            if (!p || !paginationEl) return;
            var infoEl = paginationEl.querySelector('.ledger-pagination-info');
            if (infoEl) infoEl.textContent = 'Showing ' + (p.from || 0) + '–' + (p.to || 0) + ' of ' + (p.total || 0);
            var pageInfoEl = paginationEl.querySelector('.ledger-page-info');
            if (pageInfoEl) pageInfoEl.textContent = 'Page ' + p.current_page + ' of ' + p.last_page;
            var prev = paginationEl.querySelector('.ledger-page-prev');
            var next = paginationEl.querySelector('.ledger-page-next');
            if (prev) { prev.disabled = p.current_page <= 1; prev.setAttribute('data-page', p.current_page - 1); }
            if (next) { next.disabled = p.current_page >= p.last_page; next.setAttribute('data-page', p.current_page + 1); }
        })
        .catch(function() {
            if (ledgerTbody) ledgerTbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Failed to load.</td></tr>';
        });
    });

    // Lock toggle
    document.addEventListener('click', function(e) {
        var lock = e.target.closest('.lock-toggle');
        if (!lock) return;
        e.preventDefault();
        e.stopPropagation();
        var accountId = lock.getAttribute('data-account-id');
        var url = lock.getAttribute('data-url');
        if (!url) return;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            lock.innerHTML = data.is_locked
                ? '<i class="fa fa-unlock me-2"></i> Unlock'
                : '<i class="fa fa-lock me-2"></i> Lock';
            var row = tbody.querySelector('tr.chart-account-row[data-account-id="' + accountId + '"]');
            if (row) {
                var lockIcon = row.querySelector('.tree-lock i');
                if (lockIcon) lockIcon.className = 'fas ' + (data.is_locked ? 'fa-lock text-secondary' : 'fa-unlock text-success');
            }
        });
    });

    // Delete
    document.addEventListener('click', function(e) {
        var del = e.target.closest('.delete-account');
        if (!del || del.disabled) return;
        e.preventDefault();
        e.stopPropagation();
        var url = del.getAttribute('data-url');
        var id = del.getAttribute('data-id');
        var runDelete = function() {
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function(r) {
                if (r.ok || r.status === 204) {
                    var row = tbody.querySelector('tr.chart-account-row[data-account-id="' + id + '"]');
                    if (row && row.parentNode) row.parentNode.removeChild(row);
                    closeLedgerPanel();
                    placeholder.style.display = 'block';
                    content.style.display = 'none';
                    content.innerHTML = '';
                } else {
                    return r.json().then(function(d) {
                        var msg = (d.errors && d.errors.error) ? d.errors.error : (d.message || 'Delete failed');
                        if (window.Swal) Swal.fire('Error', msg, 'error');
                        else alert(msg);
                    });
                }
            }).catch(function() {
                if (window.Swal) Swal.fire('Error', 'Delete failed', 'error');
                else alert('Delete failed');
            });
        };
        if (window.Swal) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You will not be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!'
            }).then(function(result) {
                if (result.isConfirmed) runDelete();
            });
        } else if (confirm('Are you sure?')) {
            runDelete();
        }
    });

    // Toggle status
    document.addEventListener('click', function(e) {
        var el = e.target.closest('.toggle-status');
        if (!el) return;
        e.preventDefault();
        e.stopPropagation();
        var url = el.getAttribute('data-url');
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            el.innerHTML = data.is_active
                ? '<i class="fa fa-pause-circle-o me-2"></i> Mark as Inactive'
                : '<i class="fa fa-play-circle-o me-2"></i> Mark as Active';
            el.setAttribute('data-active', data.is_active ? '1' : '0');
        });
    });
});
</script>
@endsection
