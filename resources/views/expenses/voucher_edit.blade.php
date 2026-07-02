@php
  $__companySlug = \App\Support\CompanyRouteContext::slug();
  $expenseVoucherUpdateParams = ['id' => $voucher->id];
  if (!empty($__companySlug)) {
    $expenseVoucherUpdateParams['company_slug'] = $__companySlug;
  }
@endphp
<form id="expenseVoucherForm" action="{{ route('expenses.voucher.update', $expenseVoucherUpdateParams) }}" method="POST">
    @csrf
    @method('PUT')
    @include('expenses.voucher_edit_fields')
    <div class="modal-footer">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save me-1"></i> Update Voucher
        </button>
    </div>
</form>
