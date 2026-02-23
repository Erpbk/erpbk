@extends('layouts.app')

@section('title', 'Chart of Accounts')
@section('content')
@push('third_party_stylesheets')
<style>
  .chart-of-accounts-table {
    table-layout: fixed;
  }

  .chart-of-accounts-table thead th {
    font-weight: 600;
    white-space: nowrap;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
  }

  .chart-of-accounts-table .account-name-cell {
    min-width: 280px;
  }

  .chart-of-accounts-table .account-name-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
  }

  .chart-of-accounts-table .account-name-wrap .indent {
    display: inline-block;
    flex-shrink: 0;
  }

  .chart-of-accounts-table .account-name-wrap .tree-line {
    border-left: 1px solid #dee2e6;
    margin-right: 8px;
    min-width: 12px;
    height: 1em;
    align-self: stretch;
  }

  .chart-of-accounts-table .account-name-wrap .expand-btn {
    width: 20px;
    height: 20px;
    padding: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    color: #6c757d;
    font-size: 0.875rem;
    line-height: 1;
    flex-shrink: 0;
  }

  .chart-of-accounts-table .account-name-wrap .expand-btn:hover {
    color: var(--bs-primary);
  }

  .chart-of-accounts-table .account-name-wrap a.account-link {
    font-weight: 500;
    color: var(--bs-primary);
    text-decoration: underline;
  }

  .chart-of-accounts-table .account-name-wrap a.account-link:hover {
    color: var(--bs-primary-dark, #0d6efd);
  }

  .chart-of-accounts-table .lock-icon {
    color: #6c757d;
    font-size: 0.875rem;
    width: 20px;
    display: inline-block;
    text-align: center;
  }

  .chart-of-accounts-table .btn-actions {
    padding: 4px 8px;
  }

  .chart-of-accounts-table tbody tr.child-row {
    display: none;
  }

  .chart-of-accounts-table tbody tr.child-row.expanded {
    display: table-row;
  }

  .chart-of-accounts-table tbody tr.selected {
    background-color: rgba(105, 108, 255, 0.08) !important;
  }

  .chart-ledger-panel {
    min-height: 200px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 0.25rem 0.25rem;
  }

  .chart-ledger-panel .ledger-placeholder {
    padding: 2rem;
    color: #6c757d;
    text-align: center;
  }
  .chart-ledger-panel .ledger-table-scroll {
    max-height: 50vh;
    overflow: auto;
    -webkit-overflow-scrolling: touch;
  }
  .chart-ledger-panel .ledger-table thead th { position: sticky; top: 0; background: #f8f9fa; z-index: 1; }
</style>
@endpush

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h3>Chart of Accounts</h3>
      </div>
      <div class="col-sm-6">
        @can('account_create')
        <a class="btn btn-primary float-end action-btn show-modal" href="javascript:void(0);" data-action="{{ route('accounts.create') }}" data-size="lg" data-title="New Account"><i class="fa fa-plus me-1"></i> Add New</a>
        @endcan
        @can('trash_view')
        <a class="btn btn-outline-secondary float-end me-2" href="{{ route('accounts.trash') }}"><i class="fa fa-trash-o"></i> View Trash</a>
        @endcan
      </div>
    </div>
  </div>
</section>

<div class="content px-3">
  @include('flash::message')
  <div class="clearfix"></div>

  <div class="container-fluid px-0">
    <div class="card chart-of-accounts border-0 shadow-sm">
      <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
        <div class="d-flex align-items-center gap-2">
          <h4 class="mb-0 d-flex align-items-center">
            <span class="me-2">All Accounts</span>
            <i class="fa fa-caret-down text-muted small" aria-hidden="true"></i>
          </h4>
        </div>
        <div class="d-flex align-items-center gap-2">
          <form action="{{ request()->url() }}" method="GET" class="d-flex">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
              <input type="text" name="search" class="form-control" placeholder="Search accounts..." value="{{ request('search') }}" style="max-width: 200px;">
              <button type="submit" class="btn btn-outline-secondary d-none d-md-inline">Search</button>
            </div>
          </form>
          @can('account_create')
          <a class="btn btn-primary btn-sm action-btn show-modal" href="javascript:void(0);" data-action="{{ route('accounts.create') }}" data-size="lg" data-title="New Account"><i class="fa fa-plus me-1"></i> New</a>
          @endcan
          <div class="dropdown">
            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
              @can('trash_view')
              <li><a class="dropdown-item" href="{{ route('accounts.trash') }}"><i class="fa fa-trash-o me-2"></i> View Trash</a></li>
              @endcan
              <li><a class="dropdown-item" href="{{ route('accounts.ledger') }}"><i class="fa fa-book me-2"></i> Ledger</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 chart-of-accounts-table">
          <thead class="table-light">
            <tr>
              <th class="account-name-cell ps-3" style="width: 32px;">
                <i class="fa fa-filter text-muted small" aria-hidden="true"></i>
              </th>
              <th class="account-name-cell">Account Name</th>
              <th class="text-nowrap" style="width: 100px;"><i class="fa fa-sort text-muted small" aria-hidden="true"></i> Account Code</th>
              <th class="text-nowrap" style="width: 140px;">Account Type</th>
              <th class="text-nowrap" style="width: 80px;">Documents</th>
              <th class="text-nowrap" style="width: 160px;">Parent Account Name</th>
              <th style="width: 60px;" class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody id="chartAccountsTbody">
            @forelse($accounts as $row)
            @php
            $account = $row->account;
            $depth = $row->depth ?? 0;
            $hasChildren = $account->relationLoaded('children') ? $account->children->isNotEmpty() : $account->children()->exists();
            $isMainParent = $account->parent_id === null;
            $isLocked = ($account->is_locked ?? false) || $isMainParent;
            @endphp
            <tr class="{{ $depth > 0 ? 'child-row' : '' }}" data-account-id="{{ $account->id }}" data-parent-id="{{ $account->parent_id ?? '' }}" data-depth="{{ $depth }}" data-has-children="{{ $hasChildren ? '1' : '0' }}">
              <td class="ps-3 align-middle">
                <input type="checkbox" class="form-check-input account-row-checkbox" value="{{ $account->id }}" aria-label="Select" onclick="event.stopPropagation();">
              </td>
              <td class="account-name-cell align-middle">
                <div class="account-name-wrap" style="padding-left: {{ $depth * 24 }}px;">
                  @for ($i = 0; $i < $depth; $i++)
                    <span class="tree-line indent"></span>
                    @endfor
                    @if ($hasChildren)
                    <button type="button" class="expand-btn" aria-label="Expand/collapse" title="Expand/collapse">+</button>
                    @else
                    <span class="indent" style="width: 20px; display: inline-block;"></span>
                    @endif
                    @if ($isLocked)
                    <i class="fa fa-lock lock-icon" title="Locked / Main parent"></i>
                    @endif
                    <a href="javascript:void(0);" class="account-link chart-account-name" data-id="{{ $account->id }}">{{ $account->name }}</a>
                </div>
              </td>
              <td class="text-nowrap align-middle">{{ $account->account_code ?? '—' }}</td>
              <td class="text-nowrap align-middle">{{ $account->account_type ?? '—' }}</td>
              <td class="text-nowrap align-middle text-muted">—</td>
              <td class="text-nowrap align-middle text-muted">{{ $account->parent->name ?? '—' }}</td>
              <td class="text-end pe-3 align-middle">
                @if (!$isLocked)
                <div class="dropdown">
                  <button type="button" class="btn btn-sm btn-icon btn-outline-secondary btn-actions dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Actions" onclick="event.stopPropagation();"><i class="fa fa-cog"></i></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    @can('account_edit')
                    <li><a class="dropdown-item show-modal" href="javascript:void(0);" data-action="{{ route('accounts.edit', $account->id) }}" data-size="lg" data-title="Edit Account"><i class="fa fa-edit me-2"></i> Edit</a></li>
                    @if($account->is_locked)
                    <li><a class="dropdown-item toggle-lock" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.toggleLock', $account->id) }}"><i class="fa fa-unlock me-2"></i> Unlock</a></li>
                    @endif
                    <li><a class="dropdown-item toggle-status" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.toggleStatus', $account->id) }}" data-active="{{ $account->status == 1 ? '1' : '0' }}"><i class="fa fa-{{ $account->status == 1 ? 'pause-circle-o' : 'play-circle-o' }} me-2"></i> {{ $account->status == 1 ? 'Mark as Inactive' : 'Mark as Active' }}</a></li>
                    @endcan
                    <li><a class="dropdown-item view-ledger" href="javascript:void(0);" data-id="{{ $account->id }}"><i class="fa fa-book me-2"></i> Ledger</a></li>
                    @can('account_delete')
                    @if(!$isMainParent)
                    <li>
                      <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger delete-account" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.destroy', $account->id) }}"><i class="fa fa-trash me-2"></i> Delete</a></li>
                    @endif
                    @endcan
                  </ul>
                </div>
                @else
                <a href="javascript:void(0);" class="view-ledger btn btn-sm btn-link text-primary p-0" data-id="{{ $account->id }}">Ledger</a>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No accounts found.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card chart-ledger-panel mt-0" id="chartLedgerPanel">
      <div class="ledger-placeholder" id="chartLedgerPlaceholder">Select an account to view its ledger</div>
      <div id="chartLedgerContent" class="p-4" style="display: none;"></div>
    </div>
  </div>
</div>
@endsection

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  (function() {
    var detailUrl = '{{ route("accounts.detail", ["id" => 0]) }}'.replace(/\/0$/, '');
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var tbody = document.getElementById('chartAccountsTbody');
    var ledgerPlaceholder = document.getElementById('chartLedgerPlaceholder');
    var ledgerContent = document.getElementById('chartLedgerContent');

    function loadLedger(accountId, currency) {
      currency = currency || 'bcy';
      var url = detailUrl + '/' + accountId + '?currency=' + encodeURIComponent(currency);
      ledgerPlaceholder.style.display = 'none';
      ledgerContent.style.display = 'block';
      ledgerContent.innerHTML = '<div class="text-center py-5 text-muted"><i class="fa fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading ledger...</p></div>';

      fetch(url, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        })
        .then(function(r) {
          return r.json();
        })
        .then(function(data) {
          if (data.html) {
            ledgerContent.innerHTML = data.html;
            [].slice.call(ledgerContent.querySelectorAll('.show-modal')).forEach(function(el) {
              if (window.attachModal) window.attachModal(el);
            });
            var closeBtn = ledgerContent.querySelector('.chart-detail-close');
            if (closeBtn) closeBtn.addEventListener('click', function() {
              ledgerContent.style.display = 'none';
              ledgerContent.innerHTML = '';
              ledgerPlaceholder.style.display = 'block';
              document.querySelectorAll('#chartAccountsTbody tr.selected').forEach(function(r) {
                r.classList.remove('selected');
              });
            });
            ledgerContent.querySelectorAll('.chart-filter-currency').forEach(function(btn) {
              btn.addEventListener('click', function() {
                ledgerContent.querySelectorAll('.chart-filter-currency').forEach(function(b) {
                  b.classList.remove('active');
                });
                this.classList.add('active');
                var aid = ledgerContent.querySelector('.chart-account-detail-panel') && ledgerContent.querySelector('.chart-account-detail-panel').getAttribute('data-account-id');
                if (aid) loadLedger(aid, this.getAttribute('data-currency'));
              });
            });
            ledgerContent.querySelectorAll('.delete-account').forEach(function(link) {
              link.addEventListener('click', function(e) {
                e.preventDefault();
                var url = link.getAttribute('data-url');
                if (typeof Swal !== 'undefined') {
                  Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                  }).then(function(result) {
                    if (result.isConfirmed) {
                      var fd = new FormData();
                      fd.append('_token', csrf);
                      fd.append('_method', 'DELETE');
                      fetch(url, {
                        method: 'POST',
                        body: fd,
                        headers: {
                          'Accept': 'application/json'
                        }
                      }).then(function(res) {
                        return res.json();
                      }).then(function(res) {
                        if (res.message) {
                          Swal.fire({
                            icon: 'success',
                            title: 'Done',
                            html: res.message
                          });
                          setTimeout(function() {
                            location.reload();
                          }, 1500);
                        } else if (res.errors) Swal.fire({
                          icon: 'error',
                          title: 'Error',
                          text: (res.errors && res.errors.error) ? res.errors.error : 'Could not delete.'
                        });
                      }).catch(function() {
                        location.reload();
                      });
                    }
                  });
                }
              });
            });
          }
        })
        .catch(function() {
          ledgerContent.innerHTML = '<div class="text-center py-5 text-danger">Failed to load ledger.</div>';
        });
    }

    // Ledger AJAX pagination (no page reload)
    var ledgerEntriesBaseUrl = '{{ url("accounts/detail") }}';
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
      fetch(ledgerEntriesBaseUrl + '/' + accountId + '/ledger-entries?page=' + page + '&per_page=' + perPage + '&currency=' + currency, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (tbody) tbody.innerHTML = data.html || '<tr><td colspan="6" class="text-center text-muted py-3">No entries.</td></tr>';
        var p = data.pagination;
        if (!p || !paginationEl) return;
        var infoEl = paginationEl.querySelector('.ledger-pagination-info');
        if (infoEl) infoEl.textContent = 'Showing ' + (p.from || 0) + '\u2013' + (p.to || 0) + ' of ' + (p.total || 0);
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

    function toggleChildren(accountId, show) {
      var rows = tbody.querySelectorAll('tr[data-parent-id="' + accountId + '"]');
      rows.forEach(function(row) {
        if (show) {
          row.classList.add('expanded');
          row.style.display = 'table-row';
        } else {
          row.classList.remove('expanded');
          row.style.display = 'none';
          var id = row.getAttribute('data-account-id');
          if (id) toggleChildren(id, false);
        }
      });
    }

    if (tbody) {
      tbody.addEventListener('click', function(e) {
        var target = e.target;
        if (target.closest('.expand-btn')) {
          e.preventDefault();
          var btn = target.closest('.expand-btn');
          var tr = btn.closest('tr');
          var accountId = tr.getAttribute('data-account-id');
          var isExpanded = tr.querySelector('.expand-btn').textContent === '-';
          if (isExpanded) {
            toggleChildren(accountId, false);
            btn.textContent = '+';
          } else {
            toggleChildren(accountId, true);
            btn.textContent = '-';
          }
          return;
        }
        if (target.closest('.chart-account-name')) {
          e.preventDefault();
          var link = target.closest('.chart-account-name');
          var id = link.getAttribute('data-id');
          if (!id) return;
          var tr = link.closest('tr');
          var hasChildren = tr.getAttribute('data-has-children') === '1';
          if (hasChildren) {
            var btn = tr.querySelector('.expand-btn');
            var isExpanded = btn && btn.textContent === '-';
            if (btn) {
              if (isExpanded) {
                toggleChildren(id, false);
                btn.textContent = '+';
              } else {
                toggleChildren(id, true);
                btn.textContent = '-';
              }
            }
          }
          document.querySelectorAll('#chartAccountsTbody tr.selected').forEach(function(r) {
            r.classList.remove('selected');
          });
          tr.classList.add('selected');
          loadLedger(id);
          return;
        }
      });
    }

    document.addEventListener('click', function(e) {
      if (e.target.closest('.view-ledger')) {
        e.preventDefault();
        var link = e.target.closest('.view-ledger');
        var id = link.getAttribute('data-id');
        if (id) {
          document.querySelectorAll('#chartAccountsTbody tr.selected').forEach(function(r) {
            r.classList.remove('selected');
          });
          var tr = tbody.querySelector('tr[data-account-id="' + id + '"]');
          if (tr) tr.classList.add('selected');
          loadLedger(id);
        }
      }
      if (e.target.closest('.delete-account')) {
        e.preventDefault();
        var link = e.target.closest('.delete-account');
        var url = link.getAttribute('data-url');
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
          }).then(function(result) {
            if (result.isConfirmed) {
              var formData = new FormData();
              formData.append('_token', csrf);
              formData.append('_method', 'DELETE');
              fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                  'Accept': 'application/json'
                }
              }).then(function(res) {
                return res.json();
              }).then(function(res) {
                if (res.message) {
                  Swal.fire({
                    icon: 'success',
                    title: 'Done',
                    html: res.message
                  });
                  setTimeout(function() {
                    location.reload();
                  }, 1500);
                } else if (res.errors) Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: (res.errors && res.errors.error) ? res.errors.error : 'Could not delete.'
                });
              }).catch(function() {
                location.reload();
              });
            }
          });
        }
      }
      if (e.target.closest('.toggle-lock')) {
        e.preventDefault();
        var link = e.target.closest('.toggle-lock');
        var fd = new FormData();
        fd.append('_token', csrf);
        fetch(link.getAttribute('data-url'), {
          method: 'POST',
          body: fd,
          headers: {
            'Accept': 'application/json'
          }
        }).then(function(r) {
          return r.json();
        }).then(function(res) {
          if (res.success) location.reload();
        });
      }
      if (e.target.closest('.toggle-status')) {
        e.preventDefault();
        var link = e.target.closest('.toggle-status');
        var fd = new FormData();
        fd.append('_token', csrf);
        fetch(link.getAttribute('data-url'), {
          method: 'POST',
          body: fd,
          headers: {
            'Accept': 'application/json'
          }
        }).then(function(r) {
          return r.json();
        }).then(function(res) {
          if (res.success) location.reload();
        });
      }
    });
  })();
</script>
@endpush