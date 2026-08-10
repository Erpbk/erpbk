<div class="d-flex gap-2 m-3 flex-wrap">
    <a href="{{ route('loans.show', $loan->id) }}"
       class="btn btn-pill {{ request()->routeIs('loans.show') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Overview
    </a>
    <a href="{{ route('loan.files', $loan->id) }}"
       class="btn btn-pill {{ request()->routeIs('loan.files') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Documents
    </a>
    @if($loan->account_id)
    <a href="{{ route('loans.ledger', $loan->id) }}"
       class="btn btn-pill {{ request()->routeIs('loans.ledger') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Ledger
    </a>
    @else
    <span class="btn btn-pill btn-outline-secondary disabled" title="Available after disbursement">Ledger</span>
    @endif
</div>
