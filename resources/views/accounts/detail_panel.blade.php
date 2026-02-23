@php
$currencyLabel = $currency === 'fcy' ? 'FCY' : 'BCY';
$isCredit = $closingBalance < 0;
  $absBalance=abs($closingBalance);
  $ledgerPaginator=$ledgerPaginator ?? null;
  $perPage=$ledgerPaginator ? $ledgerPaginator->perPage() : 25;
  @endphp
  <div class="chart-account-detail-panel" data-account-id="{{ $account->id }}">
    <div class="d-flex justify-content-between align-items-baseline mb-3">
      <div>
        <p class="text-muted small mb-0">{{ $account->account_type }}</p>
        <h5 class="mb-0 fw-bold">{{ $account->name }}</h5>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary chart-detail-close" title="Close" aria-label="Close"><i class="fa fa-times"></i></button>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 mb-3 justify-content-end align-items-baseline">
      @can('account_edit')
      <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary action-btn show-modal" data-action="{{ route('accounts.edit', $account->id) }}" data-size="lg" data-title="Edit Account"><i class="fa fa-pencil me-1"></i> Edit</a>
      @endcan
      <div class="dropdown">
        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" data-bs-toggle="dropdown" aria-expanded="false" title="More options"><i class="fa fa-ellipsis-v"></i></button>
        <ul class="dropdown-menu dropdown-menu-end">
          @can('account_edit')
          <li><a class="dropdown-item show-modal" href="javascript:void(0);" data-action="{{ route('accounts.edit', $account->id) }}" data-size="lg" data-title="Edit Account"><i class="fa fa-edit me-2"></i> Edit</a></li>
          <li><a class="dropdown-item toggle-lock" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.toggleLock', $account->id) }}"><i class="fa fa-{{ $account->is_locked ? 'unlock' : 'lock' }} me-2"></i> {{ $account->is_locked ? 'Unlock' : 'Lock' }}</a></li>
          <li><a class="dropdown-item toggle-status" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.toggleStatus', $account->id) }}" data-active="{{ $account->status == 1 ? '1' : '0' }}"><i class="fa fa-{{ $account->status == 1 ? 'pause-circle-o' : 'play-circle-o' }} me-2"></i> {{ $account->status == 1 ? 'Mark as Inactive' : 'Mark as Active' }}</a></li>
          @endcan
          <li><a class="dropdown-item" href="{{ route('accounts.ledger') }}?account={{ $account->id }}"><i class="fa fa-book me-2"></i> Ledger</a></li>
          @can('account_delete')
          <li>
            <hr class="dropdown-divider">
          </li>
          <li><a class="dropdown-item text-danger delete-account" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.destroy', $account->id) }}"><i class="fa fa-trash me-2"></i> Delete</a></li>
          @endcan
        </ul>
      </div>
    </div>
    <div class="mb-4">
      <p class="text-muted small text-uppercase mb-1">Closing Balance</p>
      <p class="fs-4 fw-bold mb-0 text-primary">{{ number_format($absBalance, 2) }} {{ $isCredit ? '(Cr)' : '(Dr)' }}</p>
    </div>
    <div class="mb-3">
      <label class="text-muted small">Description</label>
      <p class="mb-0 border-bottom pb-2">{{ $account->notes ?: '--' }}</p>
    </div>
    <div class="ledger-section">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Ledger</h6>
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" class="btn btn-outline-secondary chart-filter-currency {{ $currency === 'fcy' ? '' : 'active' }}" data-currency="bcy">BCY</button>
          <button type="button" class="btn btn-outline-secondary chart-filter-currency {{ $currency === 'fcy' ? 'active' : '' }}" data-currency="fcy">FCY</button>
        </div>
      </div>
      <div class="ledger-table-scroll">
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
              <td colspan="6" class="text-center text-muted py-3">No transactions yet.</td>
            </tr>
            @endif
          </tbody>
        </table>
      </div>
      @if($ledgerPaginator && $ledgerPaginator->total() > 0)
      <div id="ledgerPagination" class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2 pt-2 border-top" data-account-id="{{ $account->id }}" data-currency="{{ $currency }}" data-per-page="{{ $perPage }}">
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
    <div class="mt-2">
      <a href="{{ $ledgerUrl }}" class="text-primary" target="_blank">Open full ledger report <i class="fa fa-external-link small"></i></a>
    </div>
  </div>