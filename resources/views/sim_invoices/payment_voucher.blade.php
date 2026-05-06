{!! Form::open(['route' => ['simInvoices.paymentVoucher.store', $invoice->id], 'id' => 'formajax']) !!}
<input type="hidden" id="reload_page" value="0">
<input type="hidden" id="redirect_url" value="">

<div class="card-body">
    <div class="row">
        <div class="col-md-6 form-group">
            <label>Invoice #</label>
            <input type="text" class="form-control" value="{{ $invoice->invoice_number ?? ('SIMI' . str_pad($invoice->id, 8, '0', STR_PAD_LEFT)) }}" readonly>
        </div>
        <div class="col-md-6 form-group">
            <label>Vendor</label>
            <input type="text" class="form-control" value="{{ $invoice->vendor->name ?? '-' }}" readonly>
        </div>
        <div class="col-md-4 form-group">
            <label>Payment Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="trans_date" value="{{ date('Y-m-d') }}" required>
        </div>
        <div class="col-md-8 form-group">
            <label>Bank Account <span class="text-danger">*</span></label>
            {!! Form::select('bank_account_id', $bankAccounts, null, ['class' => 'form-select form-select-sm select2', 'required' => true]) !!}
        </div>
        <div class="col-md-12 form-group">
            <label>Remarks</label>
            <input type="text" class="form-control" name="remarks" value="Payment against SIM Invoice #{{ $invoice->invoice_number ?? $invoice->id }}">
        </div>
        <div class="col-md-4 form-group">
            <label>Amount</label>
            <input type="text" class="form-control" value="{{ \App\Helpers\Currency::format($invoice->total_amount ?? 0, 2) }}" readonly>
        </div>
    </div>
</div>

<div class="card-footer">
    {!! Form::submit('Create Voucher', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('simInvoices.show', $invoice->id) }}" class="btn btn-default">Cancel</a>
</div>

{!! Form::close() !!}

<script>
$(function () {
    if ($.fn.select2) {
        var $modalBody = $('#formajax').closest('.modal-body');
        $('select[name="bank_account_id"]').select2({
            dropdownParent: $modalBody.length ? $modalBody : $('body'),
            width: '100%'
        });
    }
});
</script>
