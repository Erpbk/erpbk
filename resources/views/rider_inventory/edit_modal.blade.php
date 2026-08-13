<form id="editInventoryForm" action="{{ route('RiderInventory.editStore', $assignment->id) }}" method="POST">
    @csrf
    <div class="mb-3">
        <strong>Rider:</strong> {{ $assignment->rider->name ?? '-' }}
        @if($assignment->rider)
        <span class="text-muted">({{ $assignment->rider->rider_id }})</span>
        @endif
    </div>

    <div class="form-group mb-3">
        <label for="inventory_item_id" class="required">Item</label>
        <select name="inventory_item_id" id="inventory_item_id" class="form-select form-select-sm select2" required>
            <option value="">Select Item</option>
            @foreach($availableItems as $item)
            <option value="{{ $item->id }}"
                data-price="{{ $item->price }}"
                {{ (string) $assignment->inventory_item_id === (string) $item->id ? 'selected' : '' }}>
                {{ $item->name }}
            </option>
            @endforeach
            @if($assignment->inventoryItem && !$availableItems->contains('id', $assignment->inventory_item_id))
            <option value="{{ $assignment->inventory_item_id }}" selected>
                {{ $assignment->inventoryItem->name }}
            </option>
            @endif
        </select>
    </div>

    <div class="form-group mb-3">
        <label for="customer_id" class="required">Customer</label>
        <select name="customer_id" id="customer_id" class="form-select form-select-sm select2" required>
            <option value="">Select Customer</option>
            @foreach($customers as $customer)
            <option value="{{ $customer->id }}" {{ (string) $assignment->customer_id === (string) $customer->id ? 'selected' : '' }}>
                {{ $customer->name }}{{ $customer->company_name ? ' — ' . $customer->company_name : '' }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="row">
        <div class="col-md-4 form-group mb-3">
            <label for="qty" class="required">Qty</label>
            <input type="number" name="qty" id="qty" class="form-control" min="1"
                value="{{ (int) ($assignment->qty ?? 1) }}" required>
        </div>
        <div class="col-md-4 form-group mb-3">
            <label for="amount" class="required">Unit Price</label>
            <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01"
                value="{{ number_format((float) $assignment->amount, 2, '.', '') }}" required>
        </div>
        <div class="col-md-4 form-group mb-3">
            <label for="assigned_date" class="required">Assigned Date</label>
            <input type="date" name="assigned_date" id="assigned_date" class="form-control"
                value="{{ $assignment->assigned_date?->format('Y-m-d') ?? date('Y-m-d') }}" required>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>

<script>
$(document).ready(function () {
    $('#editInventoryForm .select2').select2({
        allowClear: true,
        dropdownParent: $('#modalTopbody'),
        width: '100%',
    });

    $('#inventory_item_id').on('change', function () {
        var price = parseFloat($(this).find('option:selected').data('price')) || 0;
        if (price > 0) {
            $('#amount').val(price.toFixed(2));
        }
    });
});

$('#editInventoryForm').on('submit', function (e) {
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
            var msg = xhr.responseJSON?.message;
            if (!msg && xhr.responseJSON?.errors) {
                msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
            }
            alert(msg || 'Failed to update inventory assignment.');
        }
    });
});
</script>
