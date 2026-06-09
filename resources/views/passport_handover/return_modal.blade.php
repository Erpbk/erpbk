<form action="{{ route('passportHandover.returnStore', ['type' => $holderType, 'id' => $holderId]) }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-6 form-group mb-3">
            <label>Employee/Rider Name</label>
            <input type="text" class="form-control" value="{{ $person->name }}" readonly>
        </div>
        <div class="col-md-6 form-group mb-3">
            <label>Passport Holder Name</label>
            <input type="text" class="form-control" value="{{ $history->holder_name }}" readonly>
        </div>
        <div class="col-md-6 form-group mb-3">
            <label>Passport Number</label>
            <input type="text" class="form-control" value="{{ $history->passport_number }}" readonly>
        </div>
        <div class="col-md-6 form-group mb-3">
            <label>Issued On</label>
            <input type="text" class="form-control"
                value="{{ $history->note_date ? $history->note_date->format('d M Y H:i') : '-' }}" readonly>
        </div>
        <div class="col-md-6 form-group mb-3">
            <label>Return Date &amp; Time <span class="text-danger">*</span></label>
            <input type="datetime-local" name="return_date" class="form-control"
                value="{{ now()->format('Y-m-d\TH:i') }}" required>
        </div>
        <div class="col-md-6 form-group mb-3">
            <label>Returned By <span class="text-danger">*</span></label>
            <input type="text" name="returned_by" class="form-control"
                value="{{ $person->name }}" required placeholder="Person who returned the passport">
        </div>
        <div class="col-md-6 form-group mb-3">
            <label>Return Received By <span class="text-danger">*</span></label>
            <select name="return_received_by" id="return_received_by" class="form-control form-select select2" required>
                <option value="">Select Employee</option>
                @foreach($employees as $employee)
                <option value="{{ $employee->name }}"
                    @if(($defaultReceivedBy ?? '') === $employee->name) selected @endif>
                    {{ $employee->employee_id }} - {{ $employee->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-12 form-group mb-3">
            <label>Remarks</label>
            <textarea name="remarks" class="form-control" rows="3" placeholder="Optional return remarks"></textarea>
        </div>
        <div class="col-md-12 text-end">
            <button type="submit" class="btn btn-warning">
                <i class="ti ti-arrow-back-up me-1"></i> Return Passport &amp; Generate Document
            </button>
        </div>
    </div>
</form>

<script>
$(function() {
    $('#return_received_by').select2({
        dropdownParent: $('#modalTop'),
        placeholder: 'Select employee who received the passport',
        allowClear: true,
        width: '100%'
    });
});
</script>
