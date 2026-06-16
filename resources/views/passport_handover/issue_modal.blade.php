<form action="{{ route('passportHandover.issueStore', ['type' => $holderType, 'id' => $holderId]) }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-6 form-group mb-3">
            <label>Employee/Rider Name</label>
            <input type="text" class="form-control" value="{{ $person->name }}" readonly>
        </div>
        <div class="col-md-6 form-group mb-3">
            <label>Passport Holder Name <span class="text-danger">*</span></label>
            <input type="text" name="holder_name" class="form-control" value="{{ $person->name }}" required>
        </div>
        <div class="col-md-6 form-group mb-3">
            <label>Passport Number</label>
            <input type="text" name="passport_number" class="form-control" value="{{ $person->passport ?? '' }}">
        </div>
        <div class="col-md-6 form-group mb-3">
            <label>Issue Date &amp; Time <span class="text-danger">*</span></label>
            <input type="datetime-local" name="note_date" class="form-control"
                value="{{ now()->format('Y-m-d\TH:i') }}" required>
        </div>
        <div class="col-md-6 form-group mb-3">
            <label>Handed Over By <span class="text-danger">*</span></label>
            <input type="text" name="handed_over_by" class="form-control"
                value="{{ auth()->user()->name ?? '' }}" required placeholder="Person who issued the passport">
        </div>
        <div class="col-md-6 form-group mb-3">
            <label>Received By <span class="text-danger">*</span></label>
            <select name="received_by" id="received_by" class="form-control form-select select2" required>
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
            <textarea name="remarks" class="form-control" rows="3" placeholder="Optional remarks"></textarea>
        </div>
        <div class="col-md-12 text-end">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-passport me-1"></i> Issue Passport &amp; Generate Document
            </button>
        </div>
    </div>
</form>

<script>
$(function() {
    $('#received_by').select2({
        dropdownParent: $('#modalTop'),
        placeholder: 'Select employee who collected the passport',
        allowClear: true,
        width: '100%'
    });
});
</script>
