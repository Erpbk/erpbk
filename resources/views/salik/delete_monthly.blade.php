{!! Form::open(['route' => 'salik.deleteMonthly', 'method' => 'post', 'id' => 'formajax']) !!}
@csrf

<div class="row">
    <div class="col-md-12 mb-3">
        <label for="billing_month" class="form-label">Billing Month <span class="text-danger">*</span></label>
        <input type="month"
            name="billing_month"
            id="billing_month"
            class="form-control"
            value="{{ old('billing_month') }}"
            required>
        <small class="text-muted">Only unpaid salik records for this month will be deleted. Paid records are kept.</small>
    </div>

    <div class="col-md-12">
        <div class="alert alert-warning mb-0">
            <i class="ti ti-alert-triangle me-1"></i>
            This removes unpaid salik trips for the selected month and re-syncs related monthly invoices. Paid saliks are never deleted.
        </div>
    </div>
</div>

<div class="action-btn mt-3">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Delete Monthly Saliks', ['class' => 'btn btn-danger', 'onclick' => "return confirm('Delete all unpaid salik records for this month? Paid records will not be deleted.');"]) !!}
</div>

{!! Form::close() !!}
