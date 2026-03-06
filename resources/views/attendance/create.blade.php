<style>
    /* Custom styles for create form */
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
</style>
    
<form action="{{ route('attendance.store') }}" method="POST" id="attendanceForm">
    @csrf

    <!-- User Type Selection -->
    <div class="row mb-4">
        <div class="col-md-6">
            <label for="ref_type" class="form-label fw-bold">
                User Type <span class="text-danger">*</span>
            </label>
            <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="ref_type" id="type_employee" 
                        value="employee"
                        autocomplete="off" required>
                <label class="btn btn-outline-primary" for="type_employee">
                    <i class="fas fa-user-tie me-2"></i>Employee
                </label>

                <input type="radio" class="btn-check" name="ref_type" id="type_rider" 
                        value="rider"
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
                <input type="date" class="form-control" 
                        id="date" name="date" value="{{ isset($date) ? $date : old('date', date('Y-m-d')) }}" required>
            </div>
        </div>

        <div class="col-md-6">
            <label for="status" class="form-label fw-bold required">
                Status
            </label>
            <select class="form-select select2" 
                    id="status" name="status" required>
                <option value="">-- Select Status --</option>
                <option value="present" {{ old('status') == 'present' ? 'selected' : '' }}>Present</option>
                <option value="absent" {{ old('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                <option value="late" {{ old('status') == 'late' ? 'selected' : '' }}>Late</option>
                <option value="half day" {{ old('status') == 'half day' ? 'selected' : '' }}>Half Day</option>
                <option value="on leave" {{ old('status') == 'on leave' ? 'selected' : '' }}>On Leave</option>
                <option value="holiday" {{ old('status') == 'holiday' ? 'selected' : '' }}>Holiday</option>
            </select>
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
                <input type="time" class="form-control" 
                        id="check_in" name="check_in" value="{{ old('check_in') }}" step="1">
            </div>
            <small class="text-muted">Format: HH:MM:SS</small>
        </div>

        <div class="col-md-6">
            <label for="check_out" class="form-label">
                Check Out Time
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-sign-out-alt text-danger"></i>
                </span>
                <input type="time" class="form-control" 
                        id="check_out" name="check_out" value="{{ old('check_out') }}" step="1">
            </div>
            <small class="text-muted">Format: HH:MM:SS</small>
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
        <textarea class="form-control" 
            id="notes" name="notes" rows="3" 
            placeholder="Enter any additional notes or remarks...">{{ old('notes') }}
        </textarea>
    </div>

    <!-- Form Actions -->
    <div class="d-flex justify-content-end">
        <div>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> Save
            </button>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    console.log('create for script loaded');
    var initialRefType = @json($refType ?? null);
    var initialRefId = @json($refId ?? null);
    if (initialRefType) {
        // Find and check the correct radio button
        $('input[name="ref_type"][value="' + initialRefType + '"]').prop('checked', true);
        // Manually trigger the change event to load users
        loadUsers(initialRefType);
        if (initialRefId) {
            // We need to wait for users to load then set the value
            var checkInterval = setInterval(function() {
                if ($('#form_ref_id option[value="' + initialRefId + '"]').length > 0) {
                    $('#form_ref_id').val(initialRefId).trigger('change');
                    clearInterval(checkInterval);
                }
            }, 100);
        }
    }
    // Load users when user type is selected
    $('input[name="ref_type"]').change(function() {
        var refType = $(this).val();
        loadUsers(refType);
    });

    $('.select2').select2({
        dropdownParent: $('#attendanceForm'),
        allowClear: true,
        width: '100%'
    });

    // If old value exists, trigger change
    var oldRefType = @json(old('ref_type'));
    var oldRefId = @json(old('ref_id'));
    if (oldRefType) {
        $('input[name="ref_type"][value="' + oldRefType + '"]').prop('checked', true).trigger('change');
        
        // Set old ref_id value after users are loaded
        if (oldRefId) {
            setTimeout(function() {
                $('#ref_id').val(oldRefId);
            }, 500);
        }
    }

    // Check-in/Check-out time validation
    $('#check_in, #check_out').change(function() {
        validateTimes();
        calculateWorkingHours();
    });

    // Form submission handling
    $('#attendanceForm').submit(function(e) {
        e.preventDefault();
        validateTimes();
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');
        $.ajax({
            url: $(this).attr('action'),
            method: $(this).attr('method'),
            data: $(this).serialize(),
            success: function(response) {
                toastr.success('Attendance record created successfully!');
                $('#submitBtn').html('<i class="fas fa-check"></i> Created').addClass('btn-success-pulse');
                setTimeout(function() {
                    $('#attendanceModal').modal('hide');
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Save');
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, messages) {
                        var input = $('[name="' + key + '"]');
                        input.addClass('is-invalid');
                        input.next('.invalid-feedback').remove();
                        input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                } else {
                    toastr.error('An error occurred while creating the record. Please try again.' + (xhr.responseJSON && xhr.responseJSON.message ? ' ' + xhr.responseJSON.message : ''));
                }
            }
        });

    });
});

// Function to load users based on type
function loadUsers(refType) {
    var select = $('#form_ref_id');
    var refId = @json($refId ?? 0);
    select.html('<option value="">Loading users...</option>').prop('disabled', true);
    
    if (refType) {
        $.ajax({
            url: '{{ route("attendance.users", "refType") }}'.replace("refType", refType),
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                select.html('<option value="">-- Select User --</option>');
                $.each(data, function(index, user) {
                    var selected = (refId == user.id) ? 'selected' : '';
                    select.append('<option value="' + user.id + '"' + selected + '>' + user.name + '</option>');
                });
                select.prop('disabled', false);
            },
            error: function() {
                select.html('<option value="">Error loading users</option>');
                alert('Failed to load users. Please try again.');
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
    
    if (checkIn && checkOut && checkOut <= checkIn) {
        $('#check_out').addClass('is-invalid');
        toastr.error('Warning: Check-out time must be after check-in time!');
    } else {
        $('#check_out').removeClass('is-invalid');
    }
        
    // If present status, ensure at least check-in is provided
    if ((status === 'present' || status === 'half day' || status === 'late') && !checkIn) {
        toastr.error('Check-in time is required for Present status!');
    } else if((status === 'absent' || status === 'holiday') && checkIn) {
        toastr.warning('Check-in time is not allowed for Absent status!');
    }
}
</script>