@extends('layouts.app')

@section('title','Monthly Fuel Summary')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 210px);
    }
</style>
@endpush
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="container">
        <div class="row mb-2">
            <div class="col-sm-6">
            </div>
            <div class="col-sm-6">
                @can('fuel_cards_transactions_create')
                    <div class="action-buttons d-flex justify-content-end">
                        <div class="action-dropdown-container">
                            <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                                <i class="ti ti-plus"></i>
                                <span>Add New</span>
                                <i class="ti ti-chevron-down"></i>
                            </button>
                            <div class="action-dropdown-menu" id="addBikeDropdown">
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add Fuel Transaction" data-action="{{ route('fuel_data.create') }}">
                                    <i class="ti ti-plus"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Transaction</div>
                                        <div class="action-dropdown-item-desc">Add a new Fuel Transaction</div>
                                    </div>
                                </a>
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Invoice" data-action="{{ route('fuel_data.import') }}">
                                    <i class="ti ti-arrow-up"></i>
                                     <div class="action-dropdown-item-text">Import Fuel Data</div>
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</section>
<div class="d-flex gap-2 m-3">
    <a href="{{ route('fuel_data.index') }}" 
       class="btn btn-pill {{ request()->routeIs('fuel_data.index') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Transactions
    </a>
    <a href="{{ route('fuel_data.summary') }}" 
       class="btn btn-pill {{ request()->routeIs('fuel_data.summary') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Monthly Summary
    </a>
</div>
<div class="content mt-3">
    @include('flash::message')
    <div class="clearfix"></div>
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Monthly Fuel Summary</h4>
                    <form action="{{ route('fuel_data.summary') }}" method="GET">
                        <input type="month" onchange="submit()" name="billing_month" id="billing_month" class="form-control" value="{{ request('billing_month') ?? '' }}">
                    </form>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table  dataTable" id="invoiceSummaryTable">
                        <thead class="text-center">
                            <tr role="row">
                                <th>Invoice #</th>
                                <th>Rider Name</th>
                                <th>Billing Month</th>
                                <th>Transaction Count</th>
                                <th>Total Quantity (L)</th>
                                <th>Subtotal ({{ \App\Helpers\Currency::code() }})</th>
                                <th>VAT ({{ \App\Helpers\Currency::code() }})</th>
                                <th>Total Amount ({{ \App\Helpers\Currency::code() }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summaries as $index => $invoice)
                            <tr class="text-center">
                                <td>
                                    <a href="javascript:void(0);" data-action="{{ route('fuel_data.rider_monthly_summary', [$invoice->rider_id, $invoice->billing_month]) }}" class="show-modal-right" data-size="xl" data-title="Fuel Invoice for {{ $invoice->billing_month->format('M Y') }}">
                                        {{ $invoice->inv_id }}
                                    </a>
                                </td>
                                <td><a href="{{ route('rider.ledger', $invoice->rider->id) }}" target="_blank">{{ $invoice->rider->name ?? 'N/A' }}</a></td>
                                <td>{{ \Carbon\Carbon::parse($invoice->billing_month)->format('F Y') }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $invoice->transaction_count }}
                                    </span>
                                </td>
                                <td class="num">{{ number_format($invoice->total_qty, 2) }}</td>
                                <td class="num">{{ number_format($invoice->total_subtotal, 2) }}</td>
                                <td class="num">{{ number_format($invoice->total_vat, 2) }}</td>
                                <td class="num">
                                    <strong>{{ number_format($invoice->total_amount, 2) }}</strong>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="text-center py-5">
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
                                <th class="num">{{ number_format($summaries->sum('total_qty'), 2) }}</th>
                                <th class="num">{{ number_format($summaries->sum('total_subtotal'), 2) }}</th>
                                <th class="num">{{ number_format($summaries->sum('total_vat'), 2) }}</th>
                                <th class="num">{{ number_format($summaries->sum('total_amount'), 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if(method_exists($summaries, 'links'))
                    <div class="mt-3">
                        {!! $invoices->links('components.global-pagination') !!}
                    </div>
                @endif
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
            pageLength: 50,  // Changed to 10 for testing
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            searching: false,
            ordering: false,
            info: true,
            dom: '<"row"<"col-sm-12"tr>>' +
                '<"row mt-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6 d-flex justify-content-end"p>>',
            language: {
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "First",
                    previous: "Previous",
                    next: "Next",
                    last: "Last"
                },
                lengthMenu: "Show _MENU_ entries"
            }
        });
    });
</script>
@endpush