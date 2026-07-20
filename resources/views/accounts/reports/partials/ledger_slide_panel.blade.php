@php $__companySlug = \App\Support\CompanyRouteContext::slug(); @endphp

@push('page-styles')
<style>
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
  #ledgerSlidePanel.open { transform: translateX(0); }
  #ledgerSlidePanel .ledger-panel-header {
    flex-shrink: 0;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8f9fa;
  }
  #ledgerSlidePanel .ledger-panel-header h6 { margin: 0; font-weight: 600; }
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
  #ledgerSlidePanel .ledger-panel-close:hover { background: #e9ecef; color: #212529; }
  #ledgerSlidePanel .ledger-panel-body { flex: 1; overflow: auto; padding: 1rem; }
  #ledgerSlidePanel .ledger-placeholder { color: #6c757d; text-align: center; padding: 2rem 1rem; }
  #ledgerSlidePanel .ledger-table-scroll { max-height: 50vh; overflow: auto; }
  #ledgerSlidePanel .ledger-table thead th {
    position: sticky;
    top: 0;
    background: #f8f9fa;
    z-index: 1;
  }
</style>
@endpush

<div id="ledgerSlidePanel" class="no-print">
  <div class="ledger-panel-header">
    <h6>Ledger</h6>
    <button type="button" class="ledger-panel-close" id="ledgerPanelClose" title="Close" aria-label="Close">
      <i class="fa fa-times"></i>
    </button>
  </div>
  <div class="ledger-panel-body">
    <div class="ledger-placeholder" id="chartLedgerPlaceholder">Select an account to view its ledger</div>
    <div id="chartLedgerContent" style="display: none;"></div>
  </div>
</div>

@push('page-scripts')
<script type="text/javascript">
  (function() {
    var detailUrl = '{{ route("accounts.detail", ["company_slug" => $__companySlug, "id" => "__ACCOUNT_ID__"]) }}';
    var ledgerEntriesUrl = '{{ route("accounts.detail", ["company_slug" => $__companySlug, "id" => 0]) }}'.replace(/\/0(\?.*)?$/, '');
    var panel = document.getElementById('ledgerSlidePanel');
    var placeholder = document.getElementById('chartLedgerPlaceholder');
    var content = document.getElementById('chartLedgerContent');
    var closeBtn = document.getElementById('ledgerPanelClose');

    if (!panel || !placeholder || !content) {
      return;
    }

    function openLedgerPanel() {
      panel.classList.add('open');
    }

    function closeLedgerPanel() {
      panel.classList.remove('open');
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', closeLedgerPanel);
    }

    function loadLedgerIntoPanel(id) {
      if (!id) {
        return;
      }

      placeholder.style.display = 'none';
      content.style.display = 'block';
      content.innerHTML = '<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x text-muted"></i></div>';
      openLedgerPanel();

      fetch(detailUrl.replace('__ACCOUNT_ID__', id), {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          content.innerHTML = data.html || '<p class="text-muted">Unable to load ledger.</p>';
          var closeInContent = content.querySelector('.chart-detail-close');
          if (closeInContent) {
            closeInContent.addEventListener('click', closeLedgerPanel);
          }
        })
        .catch(function() {
          content.innerHTML = '<p class="text-danger">Failed to load ledger.</p>';
        });
    }

    document.addEventListener('click', function(e) {
      var ledgerLink = e.target.closest('.view-ledger');
      if (ledgerLink) {
        e.preventDefault();
        e.stopPropagation();
        loadLedgerIntoPanel(ledgerLink.getAttribute('data-id'));
        return;
      }

      var prevBtn = e.target.closest('#chartLedgerContent .ledger-page-prev');
      var nextBtn = e.target.closest('#chartLedgerContent .ledger-page-next');
      var btn = prevBtn || nextBtn;
      if (!btn || btn.disabled) {
        return;
      }

      e.preventDefault();
      var paginationEl = document.getElementById('ledgerPagination');
      if (!paginationEl) {
        return;
      }

      var accountId = paginationEl.getAttribute('data-account-id');
      var currency = paginationEl.getAttribute('data-currency') || 'bcy';
      var perPage = paginationEl.getAttribute('data-per-page') || '25';
      var page = btn.getAttribute('data-page');
      if (!page || !accountId) {
        return;
      }

      var tbody = document.getElementById('ledgerTableBody');
      if (tbody) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>';
      }

      fetch(ledgerEntriesUrl + '/' + accountId + '/ledger-entries?page=' + page + '&per_page=' + perPage + '&currency=' + currency, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (tbody) {
            tbody.innerHTML = data.html || '<tr><td colspan="6" class="text-center text-muted py-3">No entries.</td></tr>';
          }
          var p = data.pagination;
          if (!p || !paginationEl) {
            return;
          }
          var infoEl = paginationEl.querySelector('.ledger-pagination-info');
          if (infoEl) {
            infoEl.textContent = 'Showing ' + (p.from || 0) + '–' + (p.to || 0) + ' of ' + (p.total || 0);
          }
          var pageInfoEl = paginationEl.querySelector('.ledger-page-info');
          if (pageInfoEl) {
            pageInfoEl.textContent = 'Page ' + p.current_page + ' of ' + p.last_page;
          }
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
          if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Failed to load.</td></tr>';
          }
        });
    });
  })();
</script>
@endpush
