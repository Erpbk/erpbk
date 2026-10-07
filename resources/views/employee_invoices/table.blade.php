<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr>
            <th>Invoice #</th>
            <th>Inv Date</th>
            <th>Billing Month</th>
            <th>Employee</th>
            <th>Descriptions</th>
            <th>Project</th>
            <th>Subtotal ({{ \App\Helpers\Currency::code() }})</th>
            <th>Vat ({{ \App\Helpers\Currency::code() }})</th>
            <th>Total ({{ \App\Helpers\Currency::code() }})</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $r)
            <tr class="text-center">
                <td><a href="javascript:void(0);" data-action="{{ route('employeeInvoices.show', $r->id) }}" data-size="xl" class="show-modal-right">{{ $r->invoice_number }}</a></td>
                <td>{{ \Carbon\Carbon::parse($r->inv_date)->format('d M Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($r->billing_month)->format('M Y') }}</td>
                <td>{{ optional($r->employee)->employee_id }} - {{ optional($r->employee)->name }}</td>
                <td>{{ $r->descriptions }}</td>
                <td>{{ $r->zone }}</td>
                <td>{{ number_format($r->subtotal, 2) }}</td>
                <td>{{ number_format($r->vat ?? 0, 2) }}</td>
                <td>{{ number_format($r->total_amount, 2) }}</td>
                <td>
                    @if($r->status == 1)
                        @include('invoices.partials.status_badge', ['status' => 'paid'])
                    @elseif($r->status == 3 || ($r->paid_amount ?? 0) > 0)
                        @include('invoices.partials.status_badge', ['status' => 'partial'])
                        @if(($r->balance ?? 0) > 0.01)
                            <small>({{ \App\Helpers\Currency::format($r->balance) }} due)</small>
                        @endif
                    @else
                        @include('invoices.partials.status_badge', ['status' => 'unpaid'])
                    @endif
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-text-secondary rounded-pill border-0 p-2 me-n1 waves-effect" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @php
                                $eiStatus = (int) ($r->status ?? 0);
                                $eiLocked = in_array($eiStatus, [1, 3], true);
                                $eiPaymentId = $eiLocked
                                    ? \App\Support\InvoicePaymentLink::latestId(
                                        $r,
                                        optional($r->employee)->account_id ? (int) $r->employee->account_id : null
                                    )
                                    : null;
                            @endphp
                            @if(! $eiLocked)
                            @can('employees_invoice_edit')
                                <a href="javascript:void(0);" data-action="{{ route('employeeInvoices.edit', $r->id) }}" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Update Invoice">
                                    <i class="fa fa-edit mx-1"></i> Update
                                </a>
                            @endcan
                            @endif
                            @if($eiLocked && $eiPaymentId)
                            @canany(['employees_payments_edit', 'cash_&_banks_payments_edit'])
                                <a href="javascript:void(0);" data-action="{{ route('payments.edit', $eiPaymentId) }}" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Edit Payment">
                                    <i class="fa fa-money-bill mx-1 text-success"></i> Edit Payment
                                </a>
                            @endcanany
                            @endif
                            @if($eiStatus !== 1)
                            @can('employees_payments_create')
                                <a href="javascript:void(0);" data-action="{{ route('payments.create', ['employee_payment' => 1, 'invoice_id' => $r->id]) }}" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Add Payment">
                                    <i class="fa fa-money-bill mx-1"></i> Add Payment
                                </a>
                            @endcan
                            @can('employees_invoice_edit')
                                <a href="javascript:void(0);" data-action="{{ route('employeeInvoices.markAsSettled', $r->id) }}" class="dropdown-item waves-effect show-modal" data-size="lg" data-title="Mark Settled (no payment)">
                                    <i class="fa fa-check-double mx-1 text-primary"></i> Mark Settled (no payment)
                                </a>
                            @endcan
                            @endif
                            @can('employees_invoice_delete')
                                <a href="javascript:void(0);" onclick="confirmDelete('{{ route('employeeInvoices.delete', $r->id) }}')" class="dropdown-item waves-effect">
                                    <i class="fa fa-trash mx-1"></i> Delete
                                </a>
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif

<script>
    function confirmDelete(url) {
        if (confirm('Are you sure you want to delete this invoice?')) {
            window.location.href = url;
        }
    }
</script>

