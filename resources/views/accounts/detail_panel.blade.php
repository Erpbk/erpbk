@php
  $isCredit = $closingBalance < 0;
  $absBalance = abs($closingBalance);
  $ledgerPaginator = $ledgerPaginator ?? null;
  $perPage = $ledgerPaginator ? $ledgerPaginator->perPage() : 25;
  $__companySlug = \App\Support\CompanyRouteContext::slug();
  $isGlobalLinked = $account->isLinkedToGlobalAccount();
@endphp
<div class="chart-account-detail-panel" data-account-id="{{ $account->id }}">
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div class="min-w-0">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
        <span class="badge bg-label-secondary">{{ $account->account_type ?: 'Account' }}</span>
        @if($account->account_code)
        <span class="text-muted small font-monospace">{{ $account->account_code }}</span>
        @endif
        @if($account->is_fixed || $isGlobalLinked)
        <span class="badge bg-label-primary">Fixed</span>
        @endif
        @if($account->status == 1)
        <span class="badge bg-label-success">Active</span>
        @else
        <span class="badge bg-label-secondary">Inactive</span>
        @endif
      </div>
      <h5 class="mb-0 fw-bold text-truncate">{{ $account->name }}</h5>
    </div>
    <div class="d-flex align-items-center gap-1 flex-shrink-0">
      @can('accounts_coa_edit')
      @if(!$isGlobalLinked && !$account->is_locked)
      <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary action-btn show-modal"
        data-action="{{ route('accounts.edit', ['company_slug' => $__companySlug, 'id' => $account->id]) }}"
        data-size="lg" data-title="Edit Account"><i class="fa fa-pencil me-1"></i> Edit</a>
      @endif
      @endcan
      <div class="dropdown">
        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" data-bs-toggle="dropdown" aria-expanded="false" title="More options">
          <i class="fa fa-ellipsis-v"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          @can('accounts_coa_edit')
          @if(!$isGlobalLinked)
          @if(!$account->is_locked)
          <li><a class="dropdown-item show-modal" href="javascript:void(0);" data-action="{{ route('accounts.edit', ['company_slug' => $__companySlug, 'id' => $account->id]) }}" data-size="lg" data-title="Edit Account"><i class="fa fa-edit me-2"></i> Edit</a></li>
          @endif
          <li><a class="dropdown-item toggle-status" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.toggleStatus', ['company_slug' => $__companySlug, 'id' => $account->id]) }}" data-active="{{ $account->status == 1 ? '1' : '0' }}"><i class="fa fa-{{ $account->status == 1 ? 'pause-circle-o' : 'play-circle-o' }} me-2"></i> {{ $account->status == 1 ? 'Mark as Inactive' : 'Mark as Active' }}</a></li>
          @endif
          @endcan
          <li><a class="dropdown-item" href="{{ route('accounts.ledger', ['company_slug' => $__companySlug]) }}?account={{ $account->id }}"><i class="fa fa-book me-2"></i> Full ledger</a></li>
          @can('accounts_coa_delete')
          @if(!$isGlobalLinked && !$account->is_locked)
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger delete-account" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.destroy', ['company_slug' => $__companySlug, 'id' => $account->id]) }}"><i class="fa fa-trash me-2"></i> Delete</a></li>
          @endif
          @endcan
        </ul>
      </div>
      <button type="button" class="btn btn-sm btn-icon btn-outline-secondary chart-detail-close" title="Close" aria-label="Close">
        <i class="fa fa-times"></i>
      </button>
    </div>
  </div>

  <div class="card border shadow-none mb-3">
    <div class="card-body py-3">
      <p class="text-muted small text-uppercase mb-1">Closing balance</p>
      <p class="fs-4 fw-bold mb-0 text-primary">{{ number_format($absBalance, 2) }} <span class="fs-6 fw-semibold">{{ $isCredit ? 'Cr' : 'Dr' }}</span></p>
      @if($account->notes)
      <p class="text-muted small mb-0 mt-2">{{ $account->notes }}</p>
      @endif
    </div>
  </div>

  <div class="ledger-section">
    <div class="mb-2">
      <h6 class="mb-0">Ledger</h6>
    </div>
    <div class="ledger-table-scroll border rounded bg-white">
      <table class="table table-sm table-hover mb-0 ledger-table">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Voucher No.</th>
            <th>Transaction Details</th>
            <th>Type</th>
            <th class="text-end">Debit</th>
            <th class="text-end">Credit</th>
          </tr>
        </thead>
        <tbody id="ledgerTableBody">
          @if($ledgerPaginator && $ledgerPaginator->count() > 0)
            @include('accounts._ledger_entries_rows', ['transactions' => $ledgerPaginator->items()])
          @else
            <tr>
              <td colspan="6" class="text-center text-muted py-4">No transactions yet.</td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>
    @if($ledgerPaginator && $ledgerPaginator->total() > 0)
    <div id="ledgerPagination" class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2 pt-2"
      data-account-id="{{ $account->id }}" data-per-page="{{ $perPage }}">
      <div class="ledger-pagination-info text-muted small">
        Showing {{ $ledgerPaginator->firstItem() }}–{{ $ledgerPaginator->lastItem() }} of {{ $ledgerPaginator->total() }}
      </div>
      <div class="ledger-pagination-controls btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-secondary ledger-page-prev" {{ $ledgerPaginator->onFirstPage() ? 'disabled' : '' }} data-page="{{ $ledgerPaginator->currentPage() - 1 }}" title="Previous"><i class="fa fa-chevron-left"></i></button>
        <span class="btn btn-outline-secondary disabled ledger-page-info">Page {{ $ledgerPaginator->currentPage() }} of {{ $ledgerPaginator->lastPage() }}</span>
        <button type="button" class="btn btn-outline-secondary ledger-page-next" {{ !$ledgerPaginator->hasMorePages() ? 'disabled' : '' }} data-page="{{ $ledgerPaginator->currentPage() + 1 }}" title="Next"><i class="fa fa-chevron-right"></i></button>
      </div>
    </div>
    @endif
  </div>

  <div class="mt-3">
    <a href="{{ $ledgerUrl }}" class="text-primary small" target="_blank">Open full ledger report <i class="fa fa-external-link"></i></a>
  </div>
</div>
