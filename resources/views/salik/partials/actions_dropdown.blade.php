<div class="action-buttons d-flex justify-content-end">
    <div class="action-dropdown-container">
        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
            <i class="ti ti-plus"></i>
            <span>Salik Actions</span>
            <i class="ti ti-chevron-down"></i>
        </button>
        <div class="action-dropdown-menu" id="addBikeDropdown">
            @can('rta_saliks_salik_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Salik" data-action="{{ route('salik.create') }}">
                <i class="ti ti-plus"></i>
                <div>
                    <div class="action-dropdown-item-text">Add New Salik</div>
                    <div class="action-dropdown-item-desc">Add a new salik against a bike</div>
                </div>
            </a>
            @endcan
            @can('rta_saliks_payment_create')
            <a class="action-dropdown-item" href="{{ route('salik.payment') }}">
                <i class="ti ti-cash"></i>
                <div>
                    <div class="action-dropdown-item-text">Salik Payment</div>
                    <div class="action-dropdown-item-desc">Record payment against unpaid saliks</div>
                </div>
            </a>
            @endcan
            @if((user_can('rta_saliks_salik_create') || user_can('rta_saliks_salik_edit')) && (user_can('rta_saliks_payment_create') || user_can('rta_saliks_payment_edit')))
            <a class="action-dropdown-item show-modal" href="javascript:void(0);"
               data-size="lg" data-title="Salik Top-Up"
               data-action="{{ route('salik.topUp.create') }}">
                <i class="ti ti-wallet"></i>
                <div>
                    <div class="action-dropdown-item-text">Top-Up</div>
                    <div class="action-dropdown-item-desc">Create a payment voucher to top up Salik wallet</div>
                </div>
            </a>
            @endif
            @can('rta_saliks_salik_create')
            <a class="action-dropdown-item" href="{{ route('salik.import.form') }}">
                <i class="ti ti-file-upload"></i>
                <span>Import Saliks</span>
            </a>
            <a class="action-dropdown-item" href="{{ route('salik.missing.records') }}">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Missing Salik Records</span>
            </a>
            @endcan
            @can('rta_saliks_salik_delete')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="md" data-title="Delete Monthly Saliks" data-action="{{ route('salik.deleteMonthlyForm') }}">
                <i class="ti ti-trash"></i>
                <div>
                    <div class="action-dropdown-item-text">Delete Monthly Saliks</div>
                    <div class="action-dropdown-item-desc">Remove unpaid saliks for a billing month</div>
                </div>
            </a>
            @endcan
        </div>
    </div>
</div>
