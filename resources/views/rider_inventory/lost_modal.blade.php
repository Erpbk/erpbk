<form id="lostInventoryForm" action="{{ route('RiderInventory.markLost', $assignment->id) }}" method="POST">
    @csrf
    <div class="alert alert-warning">
        Marking this item as <strong>Lost</strong> will debit
        <strong>{{ number_format($assignment->lineTotal(), 2) }}</strong>
        to rider <strong>{{ $assignment->rider->name ?? '-' }}</strong>
        and generate an Inventory Loss (IL) voucher.
    </div>
    <div class="mb-2"><strong>Item:</strong> {{ $assignment->inventoryItem->name ?? '-' }}</div>
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
