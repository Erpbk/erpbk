{!! Form::open(['url' => route('fuelCards.chargeLost', $card->id), 'method' => 'post', 'id' => 'formajax']) !!}

<style>
.fc-lost-form .fc-lost-hint {
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
.fc-lost-form .fc-lost-hint i {
    font-size: 18px;
    margin-top: 1px;
    color: #dc2626;
}
.fc-lost-form .fc-lost-amount {
    text-align: center;
    padding: 8px 0 12px;
}
.fc-lost-form .fc-lost-amount .label {
    color: #94a3b8;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.fc-lost-form .fc-lost-amount .value {
    color: #b91c1c;
    font-size: 26px;
    font-weight: 700;
    line-height: 1.2;
}
.fc-lost-form .fc-lost-next {
    font-size: 12px;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 14px;
}
.fc-lost-form .fc-lost-next .head {
    font-weight: 600;
    margin-bottom: 8px;
    color: #1f2937;
}
.fc-lost-form .fc-lost-next li {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    margin-bottom: 6px;
}
.fc-lost-form .fc-lost-next li:last-child {
    margin-bottom: 0;
}
.fc-lost-form .fc-lost-next i {
    color: #16a34a;
    margin-top: 2px;
}
</style>

<div class="card-body fc-lost-form px-0 pb-0">
    <div class="fc-lost-hint">
        <i class="ti ti-alert-triangle"></i>
        <div>
            If the card is lost or not returned, enter the amount to debit the rider.
            An <strong>Inventory Loss (IL)</strong> voucher is generated.
            The card is then marked <strong>Lost</strong> and cannot be assigned again.
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-sm-6">
            <div class="text-muted small">Card Number</div>
            <div class="fw-semibold">{{ $card->card_number }}</div>
        </div>
        <div class="col-sm-6">
            <div class="text-muted small">Fuel Company</div>
            <div class="fw-semibold">{{ $card->fuelCompany?->name ?? '—' }}</div>
        </div>
    </div>

    @if(!$rider)
    <div class="alert alert-warning mb-0">
        This card has never been assigned to a rider, so there is nobody to charge.
        Deactivate the card instead.
    </div>
    @else
    <div class="row">
        <div class="form-group col-sm-6 mb-3">
            {!! Form::label('rider_display', 'Rider:') !!}
            <input type="text" class="form-control" value="{{ ($rider->name ?? '—') . ' (' . ($rider->rider_id ?? '—') . ')' }}" readonly>
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
            <small class="text-muted">Posted on the IL voucher and rider ledger.</small>
        </div>

        <div class="form-group col-sm-12 mb-3">
            {!! Form::label('remarks', 'Remarks:') !!}
            <textarea name="remarks" id="remarks" class="form-control" rows="2"
                      placeholder="Reason the card was lost or not returned" maxlength="1000"></textarea>
        </div>
    </div>

    <div class="row g-3 align-items-start">
        <div class="col-md-5">
            <div class="fc-lost-amount">
                <div class="label">Charge Amount</div>
                <div class="value">AED <span id="lostAmountPreview">0.00</span></div>
                <div class="text-muted mt-1" style="font-size: 11px;">Posted to the rider account.</div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="fc-lost-next">
                <div class="head">What happens next?</div>
                <ul class="list-unstyled mb-0">
                    <li><i class="ti ti-check"></i><span>The amount you enter is debited to the rider.</span></li>
                    <li><i class="ti ti-check"></i><span>An Inventory Loss (IL) voucher is generated automatically.</span></li>
                    <li><i class="ti ti-check"></i><span>Card status becomes Lost and cannot be assigned.</span></li>
                </ul>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="action-btn pt-3">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-danger" @disabled(!$rider)>
        <i class="ti ti-file-invoice me-1"></i> Generate IL Voucher
    </button>
</div>

{!! Form::close() !!}

@if($rider)
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
