<div class="d-flex gap-2 m-3">
    @can('cash_&_banks_banks_view')
        <a href="{{ route('banks.index') }}"
           class="btn btn-pill {{ request()->routeIs('banks.*') ? 'btn-primary' : 'btn-outline-secondary' }}">
            Banks
        </a>
    @endcan

    @can('cash_&_banks_cheques_view')
        <a href="{{ route('cheques.index') }}"
           class="btn btn-pill {{ request()->routeIs('cheques.*') ? 'btn-primary' : 'btn-outline-secondary' }}">
            Cheques
        </a>
    @endcan

    @can('cash_&_banks_payments_view')
        <a href="{{ route('payments.index') }}"
           class="btn btn-pill {{ request()->routeIs('payments.*') ? 'btn-primary' : 'btn-outline-secondary' }}">
            Payments
        </a>
    @endcan

    @can('cash_&_banks_receipts_view')
        <a href="{{ route('receipts.index') }}"
           class="btn btn-pill {{ request()->routeIs('receipts.*') ? 'btn-primary' : 'btn-outline-secondary' }}">
            Receipts
        </a>
    @endcan
</div>

