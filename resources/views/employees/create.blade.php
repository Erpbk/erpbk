@extends('employees.view')

@section('page-content')
<style>
    .edit-form {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
        border-radius: 23px;
        padding: 1.5rem;
    }

    .section-form .form-control-sm {
        font-size: 12px;
    }

    .card-header i {
        padding: 8px;
        border-radius: 50%;
        margin-right: 8px;
    }

    /* Select2 styling for small forms */
    .select2-container .select2-selection--single {
        height: 31px;
        font-size: 12px;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 29px;
        padding-left: 8px;
        padding-right: 20px;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 29px;
        right: 3px;
    }

    .select2-dropdown {
        font-size: 12px;
    }

    .select2-container--bootstrap4 .select2-selection--single {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }

    /* Form actions */
    .form-actions {
        position: sticky;
        bottom: 20px;
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        z-index: 100;
        margin-top: 20px;
    }

    .profile-image-container {
        text-align: center;
        margin-bottom: 20px;
    }

    .profile-image-preview {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #dee2e6;
        padding: 5px;
        margin-bottom: 10px;
    }
</style>

<!-- Profile Image Card (Special Card for Photo) -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-camera ti-sm" style="background: #cadaef; color: #024baa;"></i>
                <b>Profile Photo</b>
            </div>
            <div>
                <label for="profile_image" class="btn btn-primary btn-sm">
                    <i class="ti ti-upload me-1"></i>Choose Photo
                </label>
                <input type="file" 
                    class="d-none" 
                    id="profile_image" 
                    name="profile_image" 
                    accept="image/*"
                    onchange="previewImage(this)">
            </div>
        </div>
    </div>
</div>

<!-- Personal Information Card -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-user ti-sm" style="background: #cadaef; color: #024baa;"></i>
                <b>Personal Information</b>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <form id="personal-form" class="section-form">
            @csrf
            <div class="row">
                
                <div class="col-md-4 form-group mb-3">
                    <label class="required">Full Name</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           name="name" 
                           value="{{ old('name') }}" 
                           placeholder="John Doe" 
                           required>
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label class="required">Nationality</label>
                    <select class="form-control form-control-sm select2" name="nationality_id" required>
                        <option value="">Select Nationality</option>
                        @foreach($nationalities as $nationality)
                            <option value="{{ $nationality->id }}" {{ old('nationality_id') == $nationality->id ? 'selected' : '' }}>
                                {{ $nationality->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label class="required">Date of Birth</label>
                    <input type="date" 
                           class="form-control form-control-sm" 
                           name="dob" 
                           value="{{ old('dob') }}" 
                           required>
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label class="required">Personal Email</label>
                    <input type="email" 
                           class="form-control form-control-sm" 
                           name="personal_email" 
                           value="{{ old('personal_email') }}" 
                           placeholder="john@gmail.com" 
                           required>
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Personal Contact</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           name="personal_contact" 
                           value="{{ old('personal_contact') }}" 
                           placeholder="+971 XX XXX XXXX">
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Emergency Contact</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           name="emergency_contact" 
                           value="{{ old('emergency_contact') }}" 
                           placeholder="Emergency contact number">
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Address</label>
                    <textarea class="form-control form-control-sm" 
                              name="address" 
                              rows="3" 
                              placeholder="Full address">{{ old('address') }}</textarea>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Company Information Card -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-briefcase ti-sm" style="background: #a002aa38; color: #a002aa;"></i>
                <b>Company Information</b>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <form id="company-form" class="section-form">
            @csrf
            <div class="row">
                <div class="col-md-4 form-group mb-3">
                    <label class="required">Employee ID</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           name="employee_id" 
                           value="{{ old('employee_id', $empId) }}" 
                           readonly>
                </div>
                <div class="col-md-4 form-group mb-3">
                    <label class="required">Company Email</label>
                    <input type="email" 
                           class="form-control form-control-sm" 
                           name="company_email" 
                           value="{{ old('company_email') }}" 
                           placeholder="john@company.com" 
                           required>
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label class="required">Branch</label>
                    <select class="form-control form-control-sm select2" name="branch_id" required>
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->code . ' - ' . $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Department</label>
                    <select class="form-control form-control-sm select2" name="department_id">
                        <option value="">Select Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Designation</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           name="designation" 
                           value="{{ old('designation') }}" 
                           placeholder="Software Engineer">
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Salary (AED)</label>
                    <input type="number" 
                           step="0.01" 
                           class="form-control form-control-sm" 
                           name="salary" 
                           value="{{ old('salary') }}" 
                           placeholder="5000.00">
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label class="required">Date of Joining</label>
                    <input type="date" 
                           class="form-control form-control-sm" 
                           name="doj" 
                           value="{{ old('doj') }}" 
                           required>
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Status</label>
                    <select class="form-control form-control-sm select2" name="status">
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="on leave" {{ old('status') == 'on leave' ? 'selected' : '' }}>On Leave</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Account Information Card -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-credit-card ti-sm" style="background: #3a3a3c52; color: #3a3a3c;"></i>
                <b>Account Information</b>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <form id="account-form" class="section-form">
            @csrf
            <div class="row">
                <div class="col-md-4 form-group mb-3">
                    <label class="required">Account</label>
                    <select class="form-control form-control-sm select2" id="account" name="account" required>
                        <option value="">Select Account</option>
                        <option value="new">Create New Account</option>
                        <option value="existing">Link Existing Account</option>
                    </select>
                </div>
                
                <div class="col-md-4 form-group mb-3 d-none" id="existing-account-section">
                    <label class="required">Select Account</label>
                    <select class="form-control form-control-sm select2" id="account_id" name="account_id">
                        <option value="">Select Account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->account_code . ' - ' . $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Documents Card -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-id ti-sm" style="background: #ffc10726; color: #ffc107;"></i>
                <b>Documents (Emirates ID | Passport | Visa)</b>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <form id="documents-form" class="section-form">
            @csrf
            <div class="row">
                <div class="col-md-4 form-group mb-3">
                    <label>Emirates ID</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           id="emirate_id" 
                           name="emirate_id" 
                           oninput="formatEmirateId(this)"
                           value="{{ old('emirate_id') }}" 
                           >
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Emirates ID Expiry</label>
                    <input type="date" 
                           class="form-control form-control-sm" 
                           name="emirate_expiry" 
                           value="{{ old('emirate_expiry') }}">
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Passport Number</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           name="passport" 
                           value="{{ old('passport') }}" 
                           placeholder="Passport number">
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Passport Expiry</label>
                    <input type="date" 
                           class="form-control form-control-sm" 
                           name="passport_expiry" 
                           value="{{ old('passport_expiry') }}">
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Visa Sponsor</label>
                    <datalist id="sponsorOptions">
                        <option value="Express Fast Delivery Service">
                    </datalist>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           name="visa_sponsor" 
                           list="sponsorOptions"
                           value="{{ old('visa_sponsor') }}" 
                           placeholder="Sponsor name">
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Visa Occupation</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           name="visa_occupation" 
                           value="{{ old('visa_occupation') }}" 
                           placeholder="Occupation on visa">
                </div>
                
                <div class="col-md-4 form-group mb-3">
                    <label>Visa Expiry</label>
                    <input type="date" 
                           class="form-control form-control-sm" 
                           name="visa_expiry" 
                           value="{{ old('visa_expiry') }}">
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Notes Card -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-note ti-sm" style="background: #6c757d38; color: #6c757d;"></i>
                <b>Additional Notes</b>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <form id="notes-form" class="section-form">
            @csrf
            <div class="row">
                <div class="col-md-12">
                    <textarea class="form-control form-control-sm" name="notes" rows="4" placeholder="Additional notes">{{ old('notes') }}</textarea>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Form Actions -->
<div class="form-actions">
    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('employees.index') }}" class="btn btn-secondary me-2">
            <i class="ti ti-x me-1"></i>Cancel
        </a>
        <button type="button" class="btn btn-primary" id="save-employee">
            <i class="ti ti-device-floppy me-1"></i> Create Employee
        </button>
    </div>
</div>

@endsection

@push('third_party_scripts')
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#output').attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function formatEmirateId(input) {
    // Remove all non-digits
    let value = input.value.replace(/\D/g, '');
    
    // Apply formatting
    if (value.length > 0) {
        if (value.length <= 3) {
            value = value;
        } else if (value.length <= 7) {
            value = value.slice(0, 3) + '-' + value.slice(3);
        } else if (value.length <= 14) {
            value = value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7);
        } else {
            value = value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 14) + '-' + value.slice(14, 15);
        }
    }
    
    input.value = value;
}

$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        width: '100%',
        placeholder: 'Select an option',
        allowClear: true
    });

    // Account selection handler
    $('#account').on('change', function() {
        const selectedValue = $(this).val();
        
        if (selectedValue === 'existing') {
            $('#existing-account-section').removeClass('d-none');
            $('#account_id').prop('required', true);
        } else if (selectedValue === 'new') {
            $('#existing-account-section').addClass('d-none');
            $('#account_id').prop('required', false).val('').trigger('change');
        } else {
            $('#existing-account-section').addClass('d-none');
            $('#account_id').prop('required', false).val('').trigger('change');
        }
    });

    // Date validation
    $('#dob, #doj, #emirate_expiry, #passport_expiry, #visa_expiry').on('change', function() {
        const selectedDate = new Date($(this).val());
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if ($(this).attr('id') === 'dob') {
            if (selectedDate > today) {
                toastr.warning('Date of birth cannot be in the future');
                $(this).val('');
            }
        }
    });

    // Handle save button click
    $('#save-employee').click(function() {
        // Collect data from all forms
        const personalData = $('#personal-form').serializeArray();
        const companyData = $('#company-form').serializeArray();
        const accountData = $('#account-form').serializeArray();
        const documentsData = $('#documents-form').serializeArray();
        const notesData = $('#notes-form').serializeArray();
        
        // Create FormData for file upload
        const formData = new FormData();
        
        // Add CSRF token
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        
        // Add profile image if selected
        const profileImage = $('#profile_image')[0].files[0];
        if (profileImage) {
            formData.append('profile_image', profileImage);
        }
        
        // Append all form data
        [...personalData, ...companyData, ...accountData, ...documentsData, ...notesData].forEach(item => {
            if (item.name !== '_token') {
                formData.append(item.name, item.value);
            }
        });
        
        // Add loading state
        const btn = $(this);
        const originalText = btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i> Creating...').prop('disabled', true);
        
        // Make AJAX request
        $.ajax({
            url: '{{ route("employees.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    
                    // Redirect to employee list
                    setTimeout(() => {
                        window.location.href = '{{ route("employees.index") }}';
                    }, 1500);
                } else {
                    toastr.error('Error creating employee');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = 'Validation errors:\n';
                    Object.keys(errors).forEach(function(key) {
                        errorMessage += errors[key][0] + '\n';
                    });
                    toastr.error(errorMessage);
                } else {
                    toastr.error('Error creating employee');
                }
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Remove validation errors on input
    $('input, select, textarea').on('change input', function() {
        $(this).removeClass('is-invalid');
    });
});
</script>
@endpush