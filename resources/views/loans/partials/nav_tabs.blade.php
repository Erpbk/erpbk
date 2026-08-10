<div class="d-flex gap-2 m-3">
    @can('loans_view')
    <a href="{{ route('loans.index') }}"
       class="btn btn-pill {{ request()->routeIs('loans.index') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Loans
    </a>
    <a href="{{ route('loans.upcomingInstallments') }}"
       class="btn btn-pill {{ request()->routeIs('loans.upcomingInstallments') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Upcoming Installments
    </a>
    <a href="{{ route('loans.interestSummary') }}"
       class="btn btn-pill {{ request()->routeIs('loans.interestSummary') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Interest Summary
    </a>
    @endcan
</div>
