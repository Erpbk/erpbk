<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr>
            <th colspan="11" class="text-start">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Employee Invoices</h5>
                    <span id="current-month-total" class="badge bg-primary fs-6">
                        Current Month Total: {{ number_format($currentMonthTotal, 1) }}
                    </span>
                </div>
            </th>
        </tr>
        <tr>
            <th>Id</th>
            <th>Inv Date</th>
            <th>Billing Month</th>
            <th>Employee</th>
            <th>Descriptions</th>
            <th>Project</th>
            <th>Subtotal</th>
            <th>Vat</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $r)
            <tr class="text-center">
                <td>{{ $r->id }}</td>
                <td>{{ \Carbon\Carbon::parse($r->inv_date)->format('d M Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($r->billing_month)->format('M Y') }}</td>
                <td>{{ optional($r->employee)->employee_id }} - {{ optional($r->employee)->name }}</td>
                <td>{{ $r->descriptions }}</td>
                <td>{{ $r->zone }}</td>
                <td>AED {{ number_format($r->subtotal, 2) }}</td>
                <td>{{ number_format($r->vat ?? 0, 2) }}</td>
                <td>AED {{ number_format($r->total_amount, 2) }}</td>
                <td>
                    @if($r->status == 1)
                        <span class="badge bg-success">Paid</span>
                    @else
                        <span class="badge bg-danger">Unpaid</span>
                    @endif
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-text-secondary rounded-pill border-0 p-2 me-n1 waves-effect" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @can('employeeinvoice_view')
                                <a href="{{ route('employeeInvoices.show', $r->id) }}" class="dropdown-item waves-effect" target="_blank">
                                    <i class="fa fa-eye mx-1"></i> View
                                </a>
                            @endcan
                            @can('employeeinvoice_edit')
                                <a href="javascript:void(0);" data-action="{{ route('employeeInvoices.edit', $r->id) }}" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Update Invoice">
                                    <i class="fa fa-edit mx-1"></i> Update
                                </a>
                            @endcan
                            @can('employeeinvoice_delete')
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

