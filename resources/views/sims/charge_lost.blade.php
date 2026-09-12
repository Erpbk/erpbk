{!! Form::open(['url' => route('sims.chargeLost', $sim->id), 'method' => 'post', 'id' => 'formajax']) !!}

<style>
.sim-lost-form .sim-lost-hint {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 16px;
    color: #9a3412;
    font-size: 13px;
    line-height: 1.45;
}
.sim-lost-form .sim-lost-hint i {
    font-size: 18px;
    margin-top: 1px;
    color: #dc2626;
}
.sim-lost-form .sim-lost-amount {
    text-align: center;
    padding: 8px 0 12px;
}
.sim-lost-form .sim-lost-amount .label {
    color: #94a3b8;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.sim-lost-form .sim-lost-amount .value {
    color: #b91c1c;
    font-size: 26px;
    font-weight: 700;
    line-height: 1.2;
}
.sim-lost-form .sim-lost-next {
    font-size: 12px;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 14px;
}
.sim-lost-form .sim-lost-next .head {
    font-weight: 600;
    margin-bottom: 8px;
    color: #1f2937;
}
.sim-lost-form .sim-lost-next li {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    margin-bottom: 6px;
}
.sim-lost-form .sim-lost-next li:last-child {
    margin-bottom: 0;
}
.sim-lost-form .sim-lost-next i {
    color: #16a34a;
    margin-top: 2px;
}
</style>

@php
    $personTypeLabel = ($personType ?? '') === 'employee' ? 'Employee' : 'Rider';
    $personCode = null;
    if ($person) {
        $personCode = ($personType ?? '') === 'employee'
            ? ($person->employee_id ?? $person->id)
            : ($person->rider_id ?? $person->id);
    }
@endphp

<div class="card-body sim-lost-form px-0 pb-0">
    <div class="sim-lost-hint">
        <i class="ti ti-alert-triangle"></i>
        <div>
            If the SIM is lost or not returned, enter the amount to debit the {{ strtolower($personTypeLabel) }}.
            An <strong>Inventory Loss (IL)</strong> voucher is generated.
            The SIM is then marked <strong>Lost</strong> and cannot be assigned again.
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-sm-6">
            <div class="text-muted small">SIM Number</div>
            <div class="fw-semibold">{{ $sim->number }}</div>
        </div>
        <div class="col-sm-6">
            <div class="text-muted small">SIM Company</div>
            <div class="fw-semibold">{{ $sim->telecomCompany?->name ?? '—' }}</div>
        </div>
    </div>

    @if(!$person)
    <div class="alert alert-warning mb-0">
        This SIM has never been assigned to a rider or employee, so there is nobody to charge.
        Deactivate the SIM instead.
    </div>
    @else
    <div class="row">
        <div class="form-group col-sm-6 mb-3">
            {!! Form::label('person_display', $personTypeLabel . ':') !!}
            <input type="text" class="form-control"
                   value="{{ ($person->name ?? '—') . ' (' . ($personCode ?? '—') . ')' }}" readonly>
        </div>

        <div class="form-group col-sm-6 mb-3">
            {!! Form::label('amount', 'Amount (AED):', ['class' => 'required']) !!}
            <input type="number" name="amount" id="lostAmountInput" class="form-control"
                   step="0.01" min="0.01" placeholder="Enter amount" required autocomplete="off">
        </div>

        <div class="form-group col-sm-6 mb-3">
            {!! Form::label('lost_date', 'Loss Date:', ['class' => 'required']) !!}
            <input type="date" name="lost_date" id="lost_date" class="form-control"
                   value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
        </div>

        <div class="form-group col-sm-6 mb-3">
            {!! Form::label('billing_month', 'Billing Month:', ['class' => 'required']) !!}
            <input type="month" name="billing_month" id="billing_month" class="form-control"
                   value="{{ date('Y-m') }}" required>
            <small class="text-muted">Posted on the IL voucher and {{ strtolower($personTypeLabel) }} ledger.</small>
        </div>

        <div class="form-group col-sm-12 mb-3">
            {!! Form::label('remarks', 'Remarks:') !!}
            <textarea name="remarks" id="remarks" class="form-control" rows="2"
                      placeholder="Reason the SIM was lost or not returned" maxlength="1000"></textarea>
        </div>
    </div>

    <div class="row g-3 align-items-start">
        <div class="col-md-5">
            <div class="sim-lost-amount">
                <div class="label">Charge Amount</div>
                <div class="value">AED <span id="lostAmountPreview">0.00</span></div>
                <div class="text-muted mt-1" style="font-size: 11px;">Posted to the {{ strtolower($personTypeLabel) }} account.</div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="sim-lost-next">
                <div class="head">What happens next?</div>
                <ul class="list-unstyled mb-0">
                    <li><i class="ti ti-check"></i><span>The amount you enter is debited to the {{ strtolower($personTypeLabel) }}.</span></li>
                    <li><i class="ti ti-check"></i><span>An Inventory Loss (IL) voucher is generated automatically.</span></li>
                    <li><i class="ti ti-check"></i><span>SIM status becomes Lost and cannot be assigned.</span></li>
                </ul>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="action-btn pt-3">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-danger" @disabled(!$person)>
        <i class="ti ti-file-invoice me-1"></i> Generate IL Voucher
    </button>
</div>

{!! Form::close() !!}

@if($person)
<script>
(function() {
    var amountInput = document.getElementById('lostAmountInput');
    var amountPreview = document.getElementById('lostAmountPreview');
    if (!amountInput || !amountPreview) {
        return;
    }

    var syncAmount = function() {
        var value = parseFloat(amountInput.value);
        amountPreview.textContent = isNaN(value) ? '0.00' : value.toFixed(2);
    };
    amountInput.addEventListener('input', syncAmount);
    syncAmount();
})();
</script>
@endif
