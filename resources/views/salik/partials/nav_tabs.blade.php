<div class="d-flex gap-2 m-3">
    <a href="{{ route('salik.index') }}"
       class="btn btn-pill {{ request()->routeIs('salik.index') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Saliks
    </a>
    <a href="{{ route('salik.summary') }}"
       class="btn btn-pill {{ request()->routeIs('salik.summary') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Monthly Summary
    </a>
    @can('rta_saliks_payment_view')
    <a href="{{ route('salik.payments') }}"
       class="btn btn-pill {{ request()->routeIs('salik.payments') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Payments
    </a>
    @endcan
</div>
