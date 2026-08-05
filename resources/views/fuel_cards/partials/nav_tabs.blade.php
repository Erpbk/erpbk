<div class="d-flex gap-2 m-3">
    @can('fuel_cards_card_view')
    <a href="{{ route('fuelCards.index') }}"
       class="btn btn-pill {{ request()->routeIs('fuelCards.*') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Cards
    </a>
    @endcan
    @can('fuel_cards_companies_view')
    <a href="{{ route('fuelCompanies.index') }}"
       class="btn btn-pill {{ request()->routeIs('fuelCompanies.*') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Companies
    </a>
    @endcan
    @can('fuel_cards_transactions_view')
    <a href="{{ route('fuel_data.index') }}"
       class="btn btn-pill {{ request()->routeIs('fuel_data.index') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Transactions
    </a>
    <a href="{{ route('fuel_data.summary') }}"
       class="btn btn-pill {{ request()->routeIs('fuel_data.summary') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Monthly Summary
    </a>
    @endcan
</div>
