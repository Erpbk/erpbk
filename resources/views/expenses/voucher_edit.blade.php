<form id="expenseVoucherForm" action="{{ route('expenses.voucher.update', $voucher->id) }}" method="POST">
    @csrf
    @method('PUT')
    @include('expenses.voucher_edit_fields')
    <div class="modal-footer">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save me-1"></i> Update Voucher
        </button>
    </div>
</form>
