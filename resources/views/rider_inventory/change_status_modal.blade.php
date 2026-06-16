<form id="changeInventoryStatusForm" action="{{ route('RiderInventory.changeStatusStore', $assignment->id) }}" method="POST">
    @csrf
    <div class="mb-3">
        <strong>Item:</strong> {{ $assignment->inventoryItem->name ?? '-' }}<br>
        <strong>Rider:</strong> {{ $assignment->rider->name ?? '-' }}<br>
        <strong>Current Status:</strong>
        @if($assignment->status === 'returned')
            <span class="badge bg-success">Returned</span>
        @else
            <span class="badge bg-danger">Lost</span>
        @endif
    </div>

    <div class="form-group mb-3">
        <label for="target_status" class="required">Change To</label>
        <select name="target_status" id="target_status" class="form-control" required>
            <option value="">Select new status</option>
            @foreach($availableStatuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group mb-3" id="event_date_group" style="display: none;">
        <label for="event_date" class="required" id="event_date_label">Date</label>
        <input type="date" name="event_date" id="event_date" class="form-control"
            value="{{ ($assignment->return_date ?? $assignment->loss_date)?->format('Y-m-d') ?? date('Y-m-d') }}">
    </div>

    <div class="alert alert-warning d-none" id="lost_status_warning">
        Changing to <strong>Lost</strong> will debit
        <strong>{{ number_format((float) $assignment->amount, 2) }}</strong>
        to the rider and create an Inventory Loss (IL) voucher.
    </div>

    <div class="alert alert-info d-none" id="assigned_status_note">
        The item will be reverted to <strong>Assigned</strong>. Any loss voucher or return contract linked only to this item will be removed.
    </div>

    <div class="form-group mb-3">
        <label for="remarks">Remarks</label>
        <textarea name="remarks" id="remarks" class="form-control" rows="3">{{ $assignment->remarks }}</textarea>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary">Update Status</button>
    </div>
</form>

<script>
(function () {
    const $form = $('#changeInventoryStatusForm');
    const $target = $('#target_status');
    const $dateGroup = $('#event_date_group');
    const $dateInput = $('#event_date');
    const $dateLabel = $('#event_date_label');
    const $lostWarning = $('#lost_status_warning');
    const $assignedNote = $('#assigned_status_note');

    function updateStatusFields() {
        const status = $target.val();
        $lostWarning.addClass('d-none');
        $assignedNote.addClass('d-none');

        if (status === 'returned' || status === 'lost') {
            $dateGroup.show();
            $dateInput.prop('required', true);
            $dateLabel.text(status === 'lost' ? 'Loss Date' : 'Return Date');
        } else {
            $dateGroup.hide();
            $dateInput.prop('required', false);
        }

        if (status === 'lost') {
            $lostWarning.removeClass('d-none');
        } else if (status === 'assigned') {
            $assignedNote.removeClass('d-none');
        }
    }

    $target.on('change', updateStatusFields);

    $form.on('submit', function (e) {
        e.preventDefault();

        const status = $target.val();
        if (status === 'lost' && !confirm('Confirm status change to Lost? This will charge the rider and create an IL voucher.')) {
            return;
        }
        if (status === 'assigned' && !confirm('Revert this item to Assigned status? Related voucher or return contract data for this item will be cleared.')) {
            return;
        }

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            success: function () {
                window.location.reload();
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message
                    || (xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat().join('\n') : null)
                    || 'Failed to change inventory status.';
                alert(message);
            }
        });
    });
})();
</script>
