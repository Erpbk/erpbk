<div class="action-buttons d-flex justify-content-end">
    <div class="action-dropdown-container">
        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
            <i class="ti ti-plus"></i>
            <span>SIM Actions</span>
            <i class="ti ti-chevron-down"></i>
        </button>
        <div class="action-dropdown-menu" id="addBikeDropdown">
            @can('sims_sim_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="md" data-title="Add New Sim" data-action="{{ route('sims.create') }}">
                <i class="ti ti-plus"></i>
                <div>
                    <div class="action-dropdown-item-text">Add Sim</div>
                    <div class="action-dropdown-item-desc">Add a new Sim to the system</div>
                </div>
            </a>
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Import Sim Data" data-action="{{ route('sims.import') }}">
                <i class="ti ti-file-upload"></i>
                <span>Import Sim Data</span>
            </a>
            @endcan
            @can('sims_export_data_create')
            <a class="action-dropdown-item" href="{{ route('sims.export') }}">
                <i class="ti ti-file-export"></i>
                <span>Export Sim Data</span>
            </a>
            @endcan
            @can('sims_companies_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add SIM company" data-action="{{ route('simCompanies.create') }}">
                <i class="ti ti-building"></i>
                <div>
                    <div class="action-dropdown-item-text">Add Company</div>
                    <div class="action-dropdown-item-desc">Create a new SIM company</div>
                </div>
            </a>
            @endcan
            @can('sims_invoices_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Create SIM Invoice" data-action="{{ route('simInvoices.create') }}">
                <i class="ti ti-file-invoice"></i>
                <div>
                    <div class="action-dropdown-item-text">Create Invoice</div>
                    <div class="action-dropdown-item-desc">Add a new SIM invoice</div>
                </div>
            </a>
            <a class="action-dropdown-item" href="{{ route('simInvoices.import.form') }}">
                <i class="ti ti-file-upload"></i>
                <div>
                    <div class="action-dropdown-item-text">Import Invoice</div>
                    <div class="action-dropdown-item-desc">Import a SIM invoice from Excel</div>
                </div>
            </a>
            @endcan
            @can('sims_payments_create')
            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New SIM Payment" data-action="{{ route('payments.create') }}?invoice_type=sim">
                <i class="ti ti-cash"></i>
                <div>
                    <div class="action-dropdown-item-text">Add Payment</div>
                    <div class="action-dropdown-item-desc">Record a payment to a SIM company</div>
                </div>
            </a>
            @endcan
            @if(request()->routeIs('sims.index'))
            <a class="action-dropdown-item openColumnControlSidebar" href="javascript:void(0);" data-size="sm" data-title="Column Control">
                <i class="ti ti-columns"></i>
                <div>
                    <div class="action-dropdown-item-text">Column Control</div>
                    <div class="action-dropdown-item-desc">Open column control modal</div>
                </div>
            </a>
            @endif
        </div>
    </div>
</div>
