@extends('layouts.app')

@section('title','Monthly Salik Invoices')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 210px);
    }
</style>
@endpush
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Monthly Salik Summary</h3>
            </div>
            <div class="col-sm-6">
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
                            <a class="action-dropdown-item" href="{{ route('salik.import.form') }}">
                                <i class="ti ti-file-upload"></i>
                                <span>Import Saliks</span>
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('salik.partials.nav_tabs')

<div class="content mt-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Monthly Salik Invoices</h4>
                <form action="{{ route('salik.summary') }}" method="GET">
                    <input type="month" onchange="submit()" name="billing_month" id="billing_month" class="form-control" value="{{ request('billing_month') ?? '' }}">
                </form>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table dataTable" id="invoiceSummaryTable">
                    <thead class="text-center">
                        <tr>
                            <th>Invoice #</th>
                            <th>Charged To</th>
                            <th>Billing Month</th>
                            <th>Trip Count</th>
                            <th>Toll Amount ({{ \App\Helpers\Currency::code() }})</th>
                            <th>Admin Charges ({{ \App\Helpers\Currency::code() }})</th>
                            <th>VAT ({{ \App\Helpers\Currency::code() }})</th>
                            <th>Total ({{ \App\Helpers\Currency::code() }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summaries as $invoice)
                        @php
                            $billingMonth = \Carbon\Carbon::parse($invoice->billing_month)->format('Y-m');
                            $invoiceRoute = $invoice->rider_id
                                ? route('salik.rider_monthly_summary', [$invoice->rider_id, $billingMonth])
                                : route('salik.company_monthly_summary', [$invoice->rental_company_id, $billingMonth]);
                        @endphp
                        <tr class="text-center">
                            <td>
                                <a href="javascript:void(0);" data-action="{{ $invoiceRoute }}" class="show-modal-right" data-size="xl" data-title="Salik Invoice for {{ \Carbon\Carbon::parse($invoice->billing_month)->format('M Y') }}">
                                    {{ $invoice->inv_id }}
                                </a>
                            </td>
                            <td>
                                @if($invoice->rider)
                                <a href="{{ route('rider.ledger', $invoice->rider->id) }}" target="_blank">{{ $invoice->rider->name }}</a>
                                @elseif($invoice->rentalCompany)
                                <a href="{{ route('bikeRentCompanies.files', $invoice->rentalCompany->id) }}" target="_blank">{{ $invoice->rentalCompany->name }}</a>
                                @else
                                N/A
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($invoice->billing_month)->format('F Y') }}</td>
                            <td><span class="badge bg-info">{{ $invoice->transaction_count }}</span></td>
                            <td class="num">{{ number_format($invoice->total_amount, 2) }}</td>
                            <td class="num">{{ number_format($invoice->total_admin_charges, 2) }}</td>
                            <td class="num">{{ number_format($invoice->total_vat, 2) }}</td>
                            <td class="num"><strong>{{ number_format($invoice->total_grand, 2) }}</strong></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <h3>No invoice records found</h3>
                                <p class="text-muted">Try adjusting your filters</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <th colspan="3" class="text-end">Total:</th>
                            <th class="text-center">{{ number_format($summaries->sum('transaction_count')) }}</th>
                            <th class="num">{{ number_format($summaries->sum('total_amount'), 2) }}</th>
                            <th class="num">{{ number_format($summaries->sum('total_admin_charges'), 2) }}</th>
                            <th class="num">{{ number_format($summaries->sum('total_vat'), 2) }}</th>
                            <th class="num">{{ number_format($summaries->sum('total_grand'), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('third_party_scripts')
<script>
    $(document).ready(function() {
        $('#invoiceSummaryTable').DataTable({
            responsive: true,
            paging: true,
            pageLength: 50,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            searching: false,
            ordering: false,
            info: true,
            dom: '<"row"<"col-sm-12"tr>>' +
                '<"row mt-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6 d-flex justify-content-end"p>>',
        });
    });
</script>
@endpush
