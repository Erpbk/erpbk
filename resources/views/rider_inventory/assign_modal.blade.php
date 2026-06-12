<form id="assignInventoryForm" action="{{ route('RiderInventory.assignStore', $rider->id) }}" method="POST">
    @csrf
    <div class="form-group mb-3">
        <label for="inventory_item_id" class="required">Inventory Item</label>
        <select name="inventory_item_id" id="inventory_item_id" class="form-control select2" required>
            <option value="">Select Item</option>
            @foreach($availableItems as $item)
            <option value="{{ $item->id }}" data-price="{{ $item->item_price }}">
                {{ $item->name }} ({{ number_format((float) $item->item_price, 2) }})
            </option>
            @endforeach
        </select>
    </div>
    <div class="form-group mb-3">
        <label for="assigned_date" class="required">Assigned Date</label>
        <input type="date" name="assigned_date" id="assigned_date" class="form-control" value="{{ date('Y-m-d') }}" required>
    </div>
    <div class="text-end">
        <button type="submit" class="btn btn-primary">Assign</button>
    </div>
</form>

<script>
$(document).ready(function() {
    $('.select2').select2({
        dropdownParent: $('#assignInventoryForm'),
        allowClear: true
    });
});
$('#assignInventoryForm').on('submit', function (e) {
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
            alert(xhr.responseJSON?.message || 'Failed to assign inventory item.');
        }
    });
});
</script>
