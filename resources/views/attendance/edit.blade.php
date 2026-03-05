<form action="{{ route('attendance.update', $attendance->id) }}" method="POST" id="attendanceEditForm">
    @csrf
    @method('PUT')

    <!-- User Type Selection -->
    <div class="row mb-4">
        <div class="col-md-6">
            <label for="ref_type" class="form-label fw-bold">
                User Type <span class="text-danger">*</span>
            </label>
            <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="ref_type" id="type_employee" 
                        value="employee" {{ ($attendance->ref_type ?? old('ref_type')) == 'employee' ? 'checked' : '' }} 
                        autocomplete="off" required>
                <label class="btn btn-outline-primary" for="type_employee">
                    <i class="fas fa-user-tie me-2"></i>Employee
                </label>

                <input type="radio" class="btn-check" name="ref_type" id="type_rider" 
                        value="rider" {{ ($attendance->ref_type ?? old('ref_type')) == 'rider' ? 'checked' : '' }} 
                        autocomplete="off" required>
                <label class="btn btn-outline-primary" for="type_rider">
                    <i class="fas fa-motorcycle me-2"></i>Rider
                </label>
            </div>
            @error('ref_type')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- User Selection -->
        <div class="col-md-6">
            <label for="ref_id" class="form-label fw-bold required">
                Select User
            </label>
            <select class="form-select @error('ref_id') is-invalid @enderror select2" 
                    id="form_ref_id" name="ref_id" required>
                <option value="">-- Select user type first --</option>
                @if($attendance->ref_type && $attendance->user)
                    <option value="{{ $attendance->ref_id }}" selected>{{ $attendance->user->name }}</option>
                @endif
            </select>
            @error('ref_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Date Selection -->
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="date" class="form-label fw-bold">
                Date <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                <input type="date" class="form-control @error('date') is-invalid @enderror" 
                        id="date" name="date" value="{{ old('date', $attendance->date->format('Y-m-d')) }}" required>
            </div>
            @error('date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="status" class="form-label fw-bold required">
                Status
            </label>
            <select class="form-select @error('status') is-invalid @enderror select2" 
                    id="status" name="status" required>
                <option value="">-- Select Status --</option>
                <option value="present" {{ old('status', $attendance->status) == 'present' ? 'selected' : '' }}>Present</option>
                <option value="absent" {{ old('status', $attendance->status) == 'absent' ? 'selected' : '' }}>Absent</option>
                <option value="late" {{ old('status', $attendance->status) == 'late' ? 'selected' : '' }}>Late</option>
                <option value="half day" {{ old('status', $attendance->status) == 'half day' ? 'selected' : '' }}>Half Day</option>
                <option value="on leave" {{ old('status', $attendance->status) == 'on leave' ? 'selected' : '' }}>On Leave</option>
                <option value="holiday" {{ old('status', $attendance->status) == 'holiday' ? 'selected' : '' }}>Holiday</option>
            </select>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <!-- Time Section -->
    <div class="row">
        <div class="col-md-6">
            <label for="check_in" class="form-label">
                Check In Time
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-sign-in-alt text-success"></i>
                </span>
                <input type="time" class="form-control @error('check_in') is-invalid @enderror" 
                        id="check_in" name="check_in" value="{{ old('check_in', $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i:s') : '') }}" step="1">
            </div>
            <small class="text-muted">Format: HH:MM:SS</small>
            @error('check_in')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="check_out" class="form-label">
                Check Out Time
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-sign-out-alt text-danger"></i>
                </span>
                <input type="time" class="form-control @error('check_out') is-invalid @enderror" 
                        id="check_out" name="check_out" value="{{ old('check_out', $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i:s') : '') }}" step="1">
            </div>
            <small class="text-muted">Format: HH:MM:SS</small>
            @error('check_out')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Quick Time Buttons -->
    <div class="row mt-3">
        <div class="col-12">
            <label class="form-label small text-muted">Quick Actions:</label>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="setCurrentTime('check_in')">
                    <i class="fas fa-clock"></i><span class="px-2"> Now as Check In</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="setCurrentTime('check_out')">
                    <i class="fas fa-clock"></i><span class="px-2"> Now as Check Out</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setWorkingHours()">
                    <i class="fas fa-calculator"></i><span class="px-2"> Set 10am - 6pm</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Notes Section -->
    <div class="mb-4">
        <label for="notes" class="form-label fw-bold">
            <i class="fas fa-sticky-note me-2"></i>Notes / Remarks
        </label>
        <textarea class="form-control @error('notes') is-invalid @enderror" 
            id="notes" name="notes" rows="3" 
            placeholder="Enter any additional notes or remarks...">{{ old('notes', $attendance->notes) }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Form Actions -->
    <div class="d-flex justify-content-end gap-2">
        <div>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> Update
            </button>
        </div>
    </div>
</form>

<style>
    /* Custom styles for edit form */
    .btn-check:checked + .btn-outline-primary {
        background-color: #4e73df;
        color: white;
    }
    
    .btn-check:checked + .btn-outline-info {
        background-color: #36b9cc;
        color: white;
    }
    
    .btn-outline-primary:hover {
        background-color: #4e73df;
        color: white;
    }
    
    .btn-outline-info:hover {
        background-color: #36b9cc;
        color: white;
    }
    
    .input-group-text {
        background-color: #f8f9fc;
    }
    
    .card.bg-light {
        background-color: #f8f9fc !important;
    }
    
    /* Form validation styles */
    .was-validated .form-control:invalid,
    .form-control.is-invalid {
        border-color: #e74a3b;
        background-image: none;
    }
    
    .was-validated .form-control:valid,
    .form-control.is-valid {
        border-color: #1cc88a;
    }
    
    /* Animation for form sections */
    .card {
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    /* Quick action buttons */
    .btn-group .btn {
        margin-right: 2px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .btn-group .btn {
            flex: 1 1 auto;
            margin-bottom: 5px;
        }
    }
    
    /* Loading overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        display: none;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #4e73df;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Success animation */
    @keyframes success-pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .btn-success-pulse {
        animation: success-pulse 0.5s ease-in-out;
    }
    
    /* Gap utility */
    .gap-2 {
        gap: 0.5rem;
    }
</style>

<script>
$(document).ready(function() {
    // Load users when user type is selected
    $('input[name="ref_type"]').change(function() {
        var refType = $(this).val();
        loadUsers(refType);
    });

    // Initialize Select2
    $('.select2').select2({
        dropdownParent: $('#attendanceEditForm'),
        allowClear: true,
        width: '100%'
    });

    // Load initial users based on existing ref_type
    var initialRefType = '{{ $attendance->ref_type }}';
    if (initialRefType) {
        loadUsers(initialRefType, '{{ $attendance->ref_id }}');
    }

    // Check-in/Check-out time validation
    $('#check_in, #check_out').change(function() {
        validateTimes();
    });

    // Form submission handling
    $('#attendanceEditForm').submit(function(e) {
        e.preventDefault();
        
        // Validate times before submission
        if (!validateTimes()) {
            return;
        }
        
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST', // Using POST because we have _method=PUT
            data: $(this).serialize(),
            success: function(response) {
                toastr.success('Attendance record updated successfully!');
                $('#submitBtn').html('<i class="fas fa-check"></i> Updated').addClass('btn-success-pulse');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Update');
                
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    // Clear previous errors
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback').remove();
                    
                    // Display new errors
                    $.each(errors, function(key, messages) {
                        var input = $('[name="' + key + '"]');
                        input.addClass('is-invalid');
                        
                        // Handle radio buttons specially
                        if (key === 'ref_type') {
                            input.closest('.col-md-6').append('<div class="invalid-feedback d-block">' + messages[0] + '</div>');
                        } else {
                            input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                        }
                    });
                    
                    toastr.error('Please fix the validation errors.');
                } else {
                    toastr.error('An error occurred while updating the record. Please try again.' + (xhr.responseJSON && xhr.responseJSON.message ? ' ' + xhr.responseJSON.message : ''));
                }
            }
        });
    });

    // Cancel button confirmation
    $('#cancelBtn').click(function(e) {
        if ($('#attendanceEditForm').serialize() !== '{{ http_build_query(old()) }}') {
            if (!confirm('You have unsaved changes. Are you sure you want to leave?')) {
                e.preventDefault();
                return false;
            }
        }
    });
});

// Function to load users based on type
function loadUsers(refType, selectedUserId = null) {
    var select = $('#form_ref_id');
    select.html('<option value="">Loading users...</option>').prop('disabled', true);
    
    if (refType) {
        $.ajax({
            url: '{{ route("attendance.users", "refType") }}'.replace("refType", refType),
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                select.html('<option value="">-- Select User --</option>');
                $.each(data, function(index, user) {
                    var selected = (selectedUserId && user.id == selectedUserId) ? 'selected' : '';
                    select.append('<option value="' + user.id + '" ' + selected + '>' + user.name + '</option>');
                });
                select.prop('disabled', false);
                
                // Trigger change to ensure any dependent logic runs
                select.trigger('change');
            },
            error: function() {
                select.html('<option value="">Error loading users</option>');
                toastr.error('Failed to load users. Please try again.');
            }
        });
    } else {
        select.html('<option value="">-- Select user type first --</option>').prop('disabled', true);
    }
}

// Function to set current time to input field
function setCurrentTime(fieldId) {
    var now = new Date();
    var hours = String(now.getHours()).padStart(2, '0');
    var minutes = String(now.getMinutes()).padStart(2, '0');
    var seconds = String(now.getSeconds()).padStart(2, '0');
    var currentTime = hours + ':' + minutes + ':' + seconds;
    
    $('#' + fieldId).val(currentTime).trigger('change');
    
    // Visual feedback
    $('#' + fieldId).addClass('is-valid');
    setTimeout(function() {
        $('#' + fieldId).removeClass('is-valid');
    }, 2000);
}

// Function to set default working hours (10 AM to 6 PM)
function setWorkingHours() {
    $('#check_in').val('10:00:00');
    $('#check_out').val('18:00:00');
    $('#check_in, #check_out').trigger('change');
    
    // Visual feedback
    $('#check_in, #check_out').addClass('is-valid');
    setTimeout(function() {
        $('#check_in, #check_out').removeClass('is-valid');
    }, 2000);
}

// Function to validate check-in and check-out times
function validateTimes() {
    var checkIn = $('#check_in').val();
    var checkOut = $('#check_out').val();
    var status = $('#status').val();
    var isValid = true;
    
    // Clear previous validation states
    $('#check_in, #check_out').removeClass('is-invalid');
    
    // Validate time order
    if (checkIn && checkOut && checkOut <= checkIn) {
        $('#check_out').addClass('is-invalid');
        toastr.error('Check-out time must be after check-in time!');
        isValid = false;
    }
    
    // Validate based on status
    if (status) {
        if (['present', 'half day', 'late'].includes(status) && !checkIn) {
            $('#check_in').addClass('is-invalid');
            toastr.error('Check-in time is required for ' + status + ' status!');
            isValid = false;
        } else if (['absent', 'holiday'].includes(status) && checkIn) {
            $('#check_in').addClass('is-invalid');
            toastr.warning('Check-in time is not allowed for ' + status + ' status!');
            // This is a warning, not an error - form can still submit
        }
    }
    
    return isValid;
}
</script>