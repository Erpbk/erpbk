@php
  $voucher_number = 'EXP-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
  $totalD = 0;
  $totalC = 0;
  $i = 0;
  $settings = company_table('settings')->pluck('value', 'name')->toArray();
  $__companySlug = \App\Support\CompanyRouteContext::slug();
  $expenseVoucherRouteParams = ['id' => $voucher->id];
  $voucherShowParams = ['voucher' => $voucher->id];
  if (!empty($__companySlug)) {
    $expenseVoucherRouteParams['company_slug'] = $__companySlug;
    $voucherShowParams['company_slug'] = $__companySlug;
  }
@endphp
<div class="voucher-modal-content">
  {{-- Action bar: Published ribbon, Edit, PDF/Print, etc. --}}
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-label-success">Published</span>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      @can('expenses_edit')
        <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary show-modal" data-size="xl" data-title="Edit Expense Voucher {{ $voucher_number }}" data-action="{{ route('expenses.voucher.edit', $expenseVoucherRouteParams) }}" data-collapse-sidebar="1"><i class="ti ti-edit me-1"></i> Edit</a>
      @endcan
      <a href="{{ route('vouchers.show', $voucherShowParams) }}?print=1" target="_blank" class="btn btn-sm btn-outline-secondary" rel="noopener"><i class="ti ti-file-description me-1"></i> PDF/Print</a>
    </div>
  </div>

  {{-- Company info (compact) --}}
  <div class="mb-3">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <img src="{{ $companyLogoUrl ?? asset('assets/img/logo-full.png') }}" alt="" width="120" class="img-fluid" onerror="this.style.display='none'">
      <div>
        <h6 class="mb-0 fw-semibold">{{ $settings['company_name'] ?? config('app.name') }}</h6>
        <p class="text-muted small mb-0">{{ $settings['company_address'] ?? '' }}</p>
        @if(!empty($settings['vat_number'])) <p class="text-muted small mb-0">TRN {{ $settings['vat_number'] }}</p> @endif
      </div>
    </div>
  </div>

  {{-- Voucher type and number, Date, Amount, Reference --}}
  @php $vf = static fn (string $f): bool => field_visible('expenses', $f); @endphp
  <div class="mb-3">
    <h5 class="mb-2">Expense Voucher</h5>
    <div class="row g-2">
      <div class="col-auto">
        <span class="text-muted">#</span><strong class="ms-1">{{ $voucher_number }}</strong>
      </div>
      @if($vf('trans_date'))<div class="col-auto text-muted">Date: {{ \Carbon\Carbon::parse($voucher->trans_date)->format('d M Y') }}</div>@endif
      @if($vf('amount'))<div class="col-auto fw-semibold">Amount: {{ \App\Helpers\Currency::format($voucher->amount, 2) }}</div>@endif
      @if($vf('reference_number') && $voucher->reference_number)
        <div class="col-12 text-muted small">Reference Number: {{ $voucher->reference_number }}</div>
      @endif
    </div>
  </div>

  {{-- Notes --}}
  @if($vf('remarks') && $voucher->remarks)
    <div class="mb-3">
      <label class="form-label small text-uppercase text-muted mb-1">Notes</label>
      <p class="mb-0">{{ $voucher->remarks }}</p>
    </div>
  @endif

  {{-- Line items table --}}
  <div class="table-responsive mb-3">
    <table class="table table-bordered table-sm font-sans-serif">
      <thead class="table-light">
        <tr>
          <th style="width: 30px;">#</th>
          <th>Account</th>
          <th>Particulars</th>
          <th class="text-end">Debits</th>
          <th class="text-end">Credits</th>
        </tr>
      </thead>
      <tbody>
        @foreach($voucher->transactions as $item)
          @php $i++; $totalD += $item->debit ?? 0; $totalC += $item->credit ?? 0; @endphp
          <tr>
            <td class="text-center">{{ $i }}</td>
            <td>{{ $item->account ? $item->account->account_code . ' - ' . $item->account->name : '—' }}</td>
            <td>{{ $item->narration ?? '—' }}</td>
            <td class="text-end">{{ \App\Helpers\Account::show_bal_format($item->debit ?? 0) }}</td>
            <td class="text-end">{{ \App\Helpers\Account::show_bal_format($item->credit ?? 0) }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot class="table-light">
        <tr>
          <td colspan="3" class="text-end">Sub Total</td>
          <td class="text-end">{{ \App\Helpers\Account::show_bal_format($totalD) }}</td>
          <td class="text-end">{{ \App\Helpers\Account::show_bal_format($totalC) }}</td>
        </tr>
        <tr class="fw-semibold">
          <td colspan="3" class="text-end">Total</td>
          <td class="text-end">{{ \App\Helpers\Currency::symbol() }} {{ \App\Helpers\Account::show_bal_format($totalD) }}</td>
          <td class="text-end">{{ \App\Helpers\Currency::symbol() }} {{ \App\Helpers\Account::show_bal_format($totalC) }}</td>
        </tr>
      </tfoot>
    </table>
  </div>

  {{-- Signature lines --}}
  <div class="row g-3 mb-3 no-print">
    <div class="col-md-4"><hr class="my-0"><span class="small text-muted">Authorized Signature</span></div>
    <div class="col-md-4"><hr class="my-0"><span class="small text-muted">Accountant Signature</span></div>
    <div class="col-md-4"><hr class="my-0"><span class="small text-muted">Customer Signature</span></div>
  </div>

  {{-- Attachments --}}
  <div class="text-center py-2 border-top">
    @if($voucher->attach_file)
      @php
        $attach_url = url('storage/vouchers/' . $voucher->attach_file);
      @endphp
      <a href="{{ $attach_url }}" target="_blank" class="text-decoration-none text-body d-inline-flex align-items-center gap-1">
        <i class="ti ti-paperclip ti-sm"></i>
        <span>1 Attachment(s) added</span>
        <i class="ti ti-arrow-up ti-xs text-muted"></i>
      </a>
    @else
      <span class="text-muted d-inline-flex align-items-center gap-1">
        <i class="ti ti-paperclip ti-sm"></i>
        0 Attachment(s) added
      </span>
    @endif
  </div>

  {{-- Data for panel footer --}}
  <div id="voucher-panel-current" data-number="{{ $voucher_number }}" data-amount="{{ \App\Helpers\Currency::format($voucher->amount, 2) }}" style="display: none;" aria-hidden="true"></div>
</div>
