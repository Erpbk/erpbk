@extends('employees.view')

@section('page-content')
  <div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h5 class="mb-0">Salary</h5>
      @can('employees_payments_create')
      <a href="javascript:void(0);" data-action="{{ $paymentCreateUrl }}" data-size="xl" data-title="Salary Payment"
        class="btn btn-primary btn-sm show-modal">
        <i class="ti ti-cash me-1"></i> Salary Payment
      </a>
      @endcan
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="border rounded p-3 h-100">
            <div class="text-muted small text-uppercase mb-1">Base Salary</div>
            <div class="fs-4 fw-semibold">
              {{ number_format((float) ($employee->salary ?? 0), 2) }} {{ \App\Helpers\Currency::code() }}
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded p-3 h-100">
            <div class="text-muted small text-uppercase mb-1">Employee ID</div>
            <div class="fs-5 fw-semibold">{{ $employee->employee_id }}</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded p-3 h-100">
            <div class="text-muted small text-uppercase mb-1">Status</div>
            <div class="fs-5 fw-semibold text-capitalize">{{ $employee->status ?? '—' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0">Recent Salary Invoices</h5>
      @can('employees_invoice_view')
      <a href="{{ route('employee.invoices', $employee->id) }}" class="btn btn-outline-secondary btn-sm">View all</a>
      @endcan
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>Invoice #</th>
              <th>Billing Month</th>
              <th class="text-end">Total</th>
              <th class="text-end">Paid</th>
              <th class="text-end">Balance</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentInvoices as $invoice)
              <tr>
                <td>
                  <a href="javascript:void(0);" class="show-modal-right" data-size="xl" data-action="{{ route('employeeInvoices.show', $invoice->id) }}">
                    {{ $invoice->invoice_number }}
                  </a>
                </td>
                <td>{{ $invoice->billing_month ? \Carbon\Carbon::parse($invoice->billing_month)->format('M Y') : '—' }}</td>
                <td class="text-end">{{ number_format((float) ($invoice->total_amount ?? $invoice->amount ?? 0), 2) }}</td>
                <td class="text-end">{{ number_format((float) ($invoice->paid_amount ?? 0), 2) }}</td>
                <td class="text-end">{{ number_format((float) ($invoice->balance ?? 0), 2) }}</td>
                <td>
                  @include('invoices.partials.status_badge', ['status' => $invoice->paymentStatusKey()])
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No salary invoices yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
