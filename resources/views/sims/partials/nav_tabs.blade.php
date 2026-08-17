<div class="d-flex gap-2 m-3">
    @can('sims_sim_view')
    <a href="{{ route('sims.index') }}"
       class="btn btn-pill {{ request()->routeIs('sims.*') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Sims
    </a>
    @endcan
    @can('sims_companies_view')
    <a href="{{ route('simCompanies.index') }}"
       class="btn btn-pill {{ request()->routeIs('simCompanies.*') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Companies
    </a>
    @endcan
    @can('sims_invoices_view')
    <a href="{{ route('simInvoices.index') }}"
       class="btn btn-pill {{ request()->routeIs('simInvoices.*') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Invoices
    </a>
    @endcan
    @can('sims_payments_view')
    <a href="{{ route('sim.payments') }}"
       class="btn btn-pill {{ request()->routeIs('sim.payments') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Payments
    </a>
    @endcan
</div>
