<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr>
            <th>Id</th>
            <th>Invoice #</th>
            <th>Inv Date</th>
            <th>Billing Month</th>
            <th>Vendor</th>
            <th>SIMs</th>
            <th>Subtotal</th>
            <th>Vat</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th width="150px">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $invoice)
            <tr class="text-center">
                <td>{{ $invoice->id }}</td>
                <td><a href="javascript:void(0);" data-action="{{ route('simInvoices.show', $invoice->id) }}" class="show-modal-right">{{ $invoice->invoice_number ?? 'SIMI' . str_pad($invoice->id, 8, '0', STR_PAD_LEFT) }}</a></td>
                <td>{{ \Carbon\Carbon::parse($invoice->inv_date)->format('d M Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($invoice->billing_month)->format('M Y') }}</td>
                <td>{{ $invoice->vendor->name ?? '-' }}</td>
                <td><span class="badge bg-info">{{ $invoice->items->count() }} sim(s)</span></td>
                <td>{{ \App\Helpers\Currency::format($invoice->subtotal ?? 0, 2) }}</td>
                <td>{{ \App\Helpers\Currency::format($invoice->vat ?? 0, 2) }}</td>
                <td><strong>{{ \App\Helpers\Currency::format($invoice->total_amount ?? 0, 2) }}</strong></td>
                <td>
                    @if($invoice->status == 1)
                        <span class="badge bg-success">Paid</span>
                    @elseif($invoice->status == 3)
                        <span class="badge bg-warning">Partially Paid</span>
                    @else
                        <span class="badge bg-danger">Unpaid</span>
                    @endif
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" data-bs-toggle="dropdown">
                            <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @can('sim_invoice_edit')
                                <a href="javascript:void(0);" data-action="{{ route('simInvoices.edit', $invoice->id) }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Edit Invoice">
                                    <i class="fa fa-edit mx-1"></i> Edit
                                </a>
                            @endcan
                            @can('sim_invoice_create')
                                <a href="javascript:void(0);" data-action="{{ route('simInvoices.createFromClone', $invoice->id) }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Clone Invoice (Next Month)">
                                    <i class="fa fa-copy mx-1 text-primary"></i> Clone (Next Month)
                                </a>
                            @endcan
                            @can('payments_view')
                                @if((int) $invoice->status !== 1)
                                    <a href="javascript:void(0);" data-action="{{ route('payments.create') }}?invoice_type=sim&invoice_id={{ $invoice->id }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Add Payment">
                                        <i class="fa fa-money mx-1 text-success"></i> Add Payment
                                    </a>
                                @endif
                            @endcan
                            @can('sim_invoice_payment_voucher')
                                @if((int) $invoice->status !== 1)
                                    <a href="javascript:void(0);" data-action="{{ route('simInvoices.paymentVoucher.create', $invoice->id) }}" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Create Payment Voucher">
                                        <i class="fa fa-credit-card mx-1 text-success"></i> Payment Voucher
                                    </a>
                                @endif
                            @endcan
                            @can('sim_invoice_delete')
                                {!! Form::open(['route' => ['simInvoices.destroy', $invoice->id], 'method' => 'DELETE', 'style' => 'display:inline;']) !!}
                                {!! Form::button('<i class="fa fa-trash mx-1"></i> Delete', [
                                    'type' => 'submit',
                                    'class' => 'dropdown-item waves-effect border-0 bg-transparent w-100 text-start',
                                    'onclick' => "return confirm('Are you sure you want to delete this invoice?');"
                                ]) !!}
                                {!! Form::close() !!}
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="text-center">No invoices found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif
