<form id="assignInventoryForm" action="{{ route('RiderInventory.assignStore', $rider->id) }}" method="POST">
    @csrf
    <div class="form-group mb-3">
        <label for="inventory_item_ids" class="required">Inventory Items</label>
        <select name="inventory_item_ids[]" id="inventory_item_ids" class="form-control select2" multiple required>
            @foreach($availableItems as $item)
            <option value="{{ $item->id }}" data-price="{{ $item->price }}"
                {{ in_array((string) $item->id, array_map('strval', old('inventory_item_ids', [])), true) ? 'selected' : '' }}>
                {{ $item->name }} ({{ number_format((float) $item->price, 2) }})
            </option>
            @endforeach
        </select>
        <small class="text-muted">Select one or more items to assign in a single action.</small>
    </div>
    <div class="form-group mb-3">
        <label for="customer_id" class="required">Vendor</label>
        <select name="customer_id" id="customer_id" class="form-control select2" required>
            <option value="">Select Vendor (Customer)</option>
            @foreach($customers as $customer)
            <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                {{ $customer->name }}{{ $customer->company_name ? ' — ' . $customer->company_name : '' }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="form-group mb-3">
        <label for="assigned_date" class="required">Assigned Date</label>
        <input type="date" name="assigned_date" id="assigned_date" class="form-control" value="{{ old('assigned_date', date('Y-m-d')) }}" required>
    </div>
    <div class="text-end">
        <button type="submit" class="btn btn-primary">Assign</button>
    </div>
</form>

<script>
$(document).ready(function() {
    $('#inventory_item_ids').select2({
        dropdownParent: $('#assignInventoryForm'),
        allowClear: true,
        placeholder: 'Select inventory items...',
        width: '100%'
    });

    $('#customer_id').select2({
        dropdownParent: $('#assignInventoryForm'),
        allowClear: true,
        placeholder: 'Select vendor...',
        width: '100%'
    });
});

$('#assignInventoryForm').on('submit', function (e) {
    e.preventDefault();
    const form = $(this);
    const selectedItems = $('#inventory_item_ids').val() || [];

    if (!selectedItems.length) {
        alert('Please select at least one inventory item.');
        return;
    }

    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        success: function () {
            window.location.reload();
        },
        error: function (xhr) {
            const message = xhr.responseJSON?.message
                || (xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                    : 'Failed to assign inventory item(s).');
            alert(message);
        }
    });
});
</script>
