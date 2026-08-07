<div class="d-flex gap-2 m-3">
    @can('rta_fines_unpaid_view')
    <a href="{{ route('rtaFines.tickets') }}"
       class="btn btn-pill {{ request()->routeIs('rtaFines.tickets') || request()->routeIs('rtaFines.index') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Unpaid Fines
    </a>
    @endcan
    @can('rta_fines_paid_view')
    <a href="{{ route('rtaFines.paid') }}"
       class="btn btn-pill {{ request()->routeIs('rtaFines.paid') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Paid Fines
    </a>
    @endcan
</div>
