<div class="action-buttons d-flex justify-content-end">
    <div class="action-dropdown-container">
        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
            <i class="ti ti-plus"></i>
            <span>Fuel Actions</span>
            <i class="ti ti-chevron-down"></i>
        </button>
        <div class="action-dropdown-menu" id="addBikeDropdown">
            @can('fuel_cards_card_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Card" data-action="{{ route('fuelCards.create') }}">
                <i class="ti ti-plus"></i>
                <div>
                    <div class="action-dropdown-item-text">Add Fuel Card</div>
                    <div class="action-dropdown-item-desc">Add a new Fuel Card</div>
                </div>
            </a>
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="md" data-title="Import Fuel Card Data" data-action="{{ route('fuelCards.import') }}">
                <i class="ti ti-file-upload"></i>
                <span>Import Fuel Card Data</span>
            </a>
            @endcan
            @can('fuel_cards_export_data_create')
            <a class="action-dropdown-item" href="{{ route('fuelCards.export') }}">
                <i class="ti ti-file-export"></i>
                <span>Export Fuel Card Data</span>
            </a>
            @endcan
            @can('fuel_cards_companies_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);"
               data-size="lg" data-title="Add New Fuel Company"
               data-action="{{ route('fuelCompanies.create') }}">
                <i class="ti ti-building"></i>
                <div>
                    <div class="action-dropdown-item-text">Add Fuel Company</div>
                    <div class="action-dropdown-item-desc">Create a new fuel company</div>
                </div>
            </a>
            @endcan
            @if(user_can('fuel_cards_companies_create') || user_can('fuel_cards_companies_edit'))
            <a class="action-dropdown-item show-modal" href="javascript:void(0);"
               data-size="lg" data-title="Fuel Company Top-Up"
               data-action="{{ route('fuelCompanies.topUp.create') }}">
                <i class="ti ti-wallet"></i>
                <div>
                    <div class="action-dropdown-item-text">Company Top-Up</div>
                    <div class="action-dropdown-item-desc">Create a payment voucher to top up a fuel company</div>
                </div>
            </a>
            @endif
            @can('fuel_cards_transactions_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add Fuel Transaction" data-action="{{ route('fuel_data.create') }}">
                <i class="ti ti-gas-station"></i>
                <div>
                    <div class="action-dropdown-item-text">Add Transaction</div>
                    <div class="action-dropdown-item-desc">Add a new Fuel Transaction</div>
                </div>
            </a>
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Import Fuel Data" data-action="{{ route('fuel_data.import') }}">
                <i class="ti ti-arrow-up"></i>
                <div>
                    <div class="action-dropdown-item-text">Import Fuel Data</div>
                    <div class="action-dropdown-item-desc">Import fuel transactions from file</div>
                </div>
            </a>
            @endcan
            @can('fuel_cards_transactions_delete')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="md" data-title="Delete Monthly Data" data-action="{{ route('fuel_data.deleteMonthlyForm') }}">
                <i class="ti ti-trash"></i>
                <div>
                    <div class="action-dropdown-item-text">Delete Monthly Data</div>
                    <div class="action-dropdown-item-desc">Remove fuel data for a billing month</div>
                </div>
            </a>
            @endcan
        </div>
    </div>
</div>
