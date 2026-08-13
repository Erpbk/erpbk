@php
    $openQty = max(1, (int) ($assignment->qty ?? 1));
    $unitAmount = (float) $assignment->amount;
@endphp
<form id="lostInventoryForm" action="{{ route('RiderInventory.markLost', $assignment->id) }}" method="POST">
    @csrf
    <div class="alert alert-warning">
        Marking items as <strong>Lost</strong> will debit
        <strong id="lost-charge-preview">{{ number_format($unitAmount * $openQty, 2) }}</strong>
        to rider <strong>{{ $assignment->rider->name ?? '-' }}</strong>
        and generate an Inventory Loss (IL) voucher.
    </div>
    <div class="mb-2"><strong>Item:</strong> {{ $assignment->inventoryItem->name ?? '-' }}</div>
    <div class="mb-2"><strong>Open Qty:</strong> {{ $openQty }} &middot; <strong>Unit Price:</strong> {{ number_format($unitAmount, 2) }}</div>
    <div class="form-group mb-3">
        <label for="lost_qty" class="required">Lost Qty</label>
        <input type="number" name="qty" id="lost_qty" class="form-control"
            value="{{ $openQty }}" min="1" max="{{ $openQty }}" required
            data-unit-amount="{{ $unitAmount }}"
            {{ $openQty === 1 ? 'readonly' : '' }}>
        @if($openQty > 1)
        <small class="text-muted">Partial loss is allowed; remaining qty stays assigned.</small>
        @endif
    </div>
    <div class="form-group mb-3">
        <label for="return_date" class="required">Loss Date</label>
        <input type="date" name="return_date" id="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
    </div>
    <div class="form-group mb-3">
        <label for="remarks">Remarks</label>
        <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Reason for loss"></textarea>
    </div>
    <div class="text-end">
        <button type="submit" class="btn btn-danger">Mark as Lost &amp; Charge Rider</button>
    </div>
</form>

<script>
(function () {
    var qtyInput = document.getElementById('lost_qty');
    var preview = document.getElementById('lost-charge-preview');
    function updateChargePreview() {
        if (!qtyInput || !preview) return;
        var unit = parseFloat(qtyInput.getAttribute('data-unit-amount')) || 0;
        var qty = parseInt(qtyInput.value, 10) || 0;
        preview.textContent = (unit * qty).toFixed(2);
    }
    if (qtyInput) {
        qtyInput.addEventListener('input', updateChargePreview);
        qtyInput.addEventListener('change', updateChargePreview);
    }
})();

$('#lostInventoryForm').on('submit', function (e) {
    e.preventDefault();
    if (!confirm('Confirm inventory loss? This will charge the rider and create an IL voucher.')) {
        return;
    }
    const form = $(this);
    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        success: function () {
            window.location.reload();
        },
        error: function (xhr) {
            alert(xhr.responseJSON?.message || 'Failed to process inventory loss.');
        }
    });
});
</script>
