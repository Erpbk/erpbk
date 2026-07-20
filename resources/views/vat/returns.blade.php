@extends('layouts.app')
@section('title', 'VAT Return File')
@section('content')

@include('flash::message')

<div class="container-fluid card mb-1">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0"><i class="ti ti-file-export ti-lg text-body me-2"></i>VAT Return File</h5>
    <div class="d-flex gap-2">
      @canany(['vat_create', 'vat_edit'])
      <a href="javascript:void(0);" class="btn btn-primary btn-sm show-modal" data-size="xl" data-title="New VAT Payment Voucher (VP)" data-action="{{ route('vat.voucher.create') }}">
        <i class="ti ti-file-invoice me-1"></i> New VAT Payment Voucher
      </a>
      @endcanany
      <a href="{{ route('vat.index') }}" class="btn btn-outline-primary btn-sm"><i class="ti ti-receipt-tax me-1"></i> VAT Ledger</a>
    </div>
  </div>
  <div class="card-body">
    <p class="text-muted small mb-3">Filed VAT returns. Create a VAT Payment voucher to pay a return; the return will be marked as paid. All VP (VAT Payment) vouchers are listed below.</p>

    <ul class="nav nav-tabs mb-3" id="vatReturnsTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-unpaid" data-bs-toggle="tab" data-bs-target="#pane-unpaid" type="button" role="tab" aria-controls="pane-unpaid" aria-selected="false">
          <i class="ti ti-clock me-1"></i> Unpaid Returns <span class="badge bg-warning text-dark ms-1">{{ $unpaidReturns->count() }}</span>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-paid" data-bs-toggle="tab" data-bs-target="#pane-paid" type="button" role="tab" aria-controls="pane-paid" aria-selected="true">
          <i class="ti ti-circle-check me-1"></i> Paid Returns <span class="badge bg-success ms-1">{{ $paidReturns->count() }}</span>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-vp" data-bs-toggle="tab" data-bs-target="#pane-vp" type="button" role="tab" aria-controls="pane-vp" aria-selected="false">
          <i class="ti ti-file-invoice me-1"></i> VAT Payment Vouchers (VP) <span class="badge bg-primary ms-1">{{ $vpVouchers->total() }}</span>
        </button>
      </li>
    </ul>

    <div class="tab-content" id="vatReturnsTabContent">
      <div class="tab-pane fade" id="pane-paid" role="tabpanel" aria-labelledby="tab-paid">
        @if($paidReturns->isEmpty())
        <p class="text-muted small">No paid returns.</p>
        @else
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th>Quarter</th>
                <th>Year</th>
                <th>Filed at</th>
                <th class="text-end">Payable</th>
              </tr>
            </thead>
            <tbody>
              @foreach($paidReturns as $r)
              <tr>
                <td><a href="{{ route('vat.returns.show', $r) }}" class="text-decoration-none">{{ $r->quarter_label ?? 'Q' . $r->quarter_slot }}</a></td>
                <td><a href="{{ route('vat.returns.show', $r) }}" class="text-decoration-none">{{ $r->year }}</a></td>
                <td>{{ $r->filed_at ? $r->filed_at->format('d M Y H:i') : '—' }}</td>
                <td class="text-end">{{ number_format($r->payable_amount, 2) }}</td>

              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif
      </div>

      <div class="tab-pane fade active show" id="pane-unpaid" role="tabpanel" aria-labelledby="tab-unpaid">
        @if($unpaidReturns->isEmpty())
        <p class="text-muted small">No unpaid returns.</p>
        @else
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th>Quarter</th>
                <th>Year</th>
                <th>Filed at</th>
                <th class="text-end">Payable</th>
                <th style="width: 180px;">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($unpaidReturns as $r)
              <tr>
                <td><a href="{{ route('vat.returns.show', $r) }}" class="text-decoration-none">{{ $r->quarter_label ?? 'Q' . $r->quarter_slot }}</a></td>
                <td><a href="{{ route('vat.returns.show', $r) }}" class="text-decoration-none">{{ $r->year }}</a></td>
                <td>{{ $r->filed_at ? $r->filed_at->format('d M Y H:i') : '—' }}</td>
                <td class="text-end">{{ number_format($r->payable_amount, 2) }}</td>
                <td>
                  <form action="{{ route('vat.returns.destroy', $r) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this return? Its entries will show again in the VAT ledger.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif
      </div>

      <div class="tab-pane fade" id="pane-vp" role="tabpanel" aria-labelledby="tab-vp">
        <p class="text-muted small mb-2">All VP (VAT Payment) vouchers. Create one from an unpaid return to mark that return as paid.</p>
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr>
                <th>Voucher ID</th>
                <th>Date</th>
                <th>Billing Month</th>
                <th>Reference</th>
                <th class="text-end">Amount</th>
                <th>Created By</th>
                <th style="width: 90px;">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($vpVouchers as $vp)
              @php $voucherId = $vp->voucher_type . '-' . str_pad($vp->id, 4, '0', STR_PAD_LEFT); @endphp
              <tr>
                <td>
                  <a href="javascript:void(0);" class="show-modal" data-size="xl" data-title="{{ \App\Helpers\General::VoucherType($vp->voucher_type) ?? 'VP' }} #{{ $voucherId }}" data-action="{{ route('vouchers.show', $vp->id) }}">{{ $voucherId }}</a>
                </td>
                <td>{{ $vp->trans_date ? \App\Helpers\Common::DateFormat($vp->trans_date) : '—' }}</td>
                <td>{{ $vp->billing_month ? \App\Helpers\Common::MonthFormat($vp->billing_month) : '—' }}</td>
                <td>{{ $vp->reference_number ?? '—' }}</td>
                <td class="text-end">{{ number_format($vp->amount ?? 0, 2) }}</td>
                <td>{{ \App\Helpers\Common::UserName($vp->Created_By) }}</td>
                <td>
                  <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary show-modal" data-size="xl" data-title="{{ \App\Helpers\General::VoucherType($vp->voucher_type) ?? 'VP' }} #{{ $voucherId }}" data-action="{{ route('vouchers.show', $vp->id) }}">
                    <i class="ti ti-eye"></i> View
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">No VAT Payment vouchers yet. Create one from an unpaid return or from the button above.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if($vpVouchers->hasPages())
        <div class="d-flex justify-content-center mt-2">
          {{ $vpVouchers->links('pagination') }}
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection