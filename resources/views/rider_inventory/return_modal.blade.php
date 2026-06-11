<form id="returnInventoryForm" action="{{ route('RiderInventory.returnStore', $assignment->id) }}" method="POST">
    @csrf
    <div class="mb-3">
        <strong>Item:</strong> {{ $assignment->inventoryItem->name ?? '-' }}<br>
        <strong>Rider:</strong> {{ $assignment->rider->name ?? '-' }}
    </div>
    <div class="form-group mb-3">
        <label for="return_date" class="required">Return Date</label>
        <input type="date" name="return_date" id="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
    </div>
    <div class="form-group mb-3">
        <label for="remarks">Remarks</label>
        <textarea name="remarks" id="remarks" class="form-control" rows="3"></textarea>
    </div>
    <div class="text-end">
        <button type="submit" class="btn btn-warning">Mark Returned</button>
    </div>
</form>

<script>
$('#returnInventoryForm').on('submit', function (e) {
    e.preventDefault();
    const form = $(this);
    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        success: function () {
            window.location.reload();
        },
        error: function (xhr) {
            alert(xhr.responseJSON?.message || 'Failed to return inventory item.');
        }
    });
});
</script>
