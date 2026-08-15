@canany([
    'cash_&_banks_banks_create',
    'cash_&_banks_cheques_create',
    'cash_&_banks_payments_create',
    'cash_&_banks_receipts_create',
    'vouchers_create'
])
<div class="action-buttons d-flex justify-content-end">
    <div class="action-dropdown-container">
        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
            <i class="ti ti-plus"></i>
            <span>Cash & Banks Actions</span>
            <i class="ti ti-chevron-down"></i>
        </button>

        <div class="action-dropdown-menu" id="addBikeDropdown">
            @can('cash_&_banks_banks_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Bank Account" data-action="{{ route('banks.create') }}">
                <i class="fas fa-university"></i>
                <div>
                    <div class="action-dropdown-item-text">New Bank Account</div>
                    <div class="action-dropdown-item-desc">Add a new bank account</div>
                </div>
            </a>
            @endcan

            @can('cash_&_banks_cheques_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Cheque" data-action="{{ route('cheques.create') }}?id={{ request()->segment(3) }}">
                <i class="fas fa-money-check"></i>
                <div>
                    <div class="action-dropdown-item-text">New Cheque</div>
                    <div class="action-dropdown-item-desc">Add a new cheque</div>
                </div>
            </a>
            @endcan

            @can('cash_&_banks_payments_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Payment" data-action="{{ route('payments.create') }}?id={{ request()->segment(3) }}">
                <i class="fas fa-dollar-sign"></i>
                <div>
                    <div class="action-dropdown-item-text">New Payment</div>
                    <div class="action-dropdown-item-desc">Add a new cash-out payment</div>
                </div>
            </a>
            @endcan

            @can('cash_&_banks_receipts_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Receipt" data-action="{{ route('receipts.create') }}?id={{ request()->segment(3) }}">
                <i class="fa fa-receipt"></i>
                <div>
                    <div class="action-dropdown-item-text">New Receipt</div>
                    <div class="action-dropdown-item-desc">Add a new cash-in receipt</div>
                </div>
            </a>
            @endcan

            @can('vouchers_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Funds Transfer" data-action="{{ route('vouchers.create', ['vt' => 'TRF']) }}">
                <i class="ti ti-arrows-exchange"></i>
                <div>
                    <div class="action-dropdown-item-text">Funds Transfer</div>
                    <div class="action-dropdown-item-desc">Transfer funds between bank accounts</div>
                </div>
            </a>
            @endcan
        </div>
    </div>
</div>
@endcanany
