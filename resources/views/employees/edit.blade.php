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
</style>

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
        <form class="employee-form" id="personal-form">
            @csrf
            @if(isset($employee))
                @method('PUT')
            @endif
            
            <div class="row">
                <div class="col-md-3 form-group mb-3">
                    <label class="required">Full Name</label>
                    <input type="text" class="form-control form-control-sm" name="name" 
                           value="{{ old('name', $employee->name ?? '') }}" required>
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Date of Birth</label>
                    <input type="date" class="form-control form-control-sm" name="dob" 
                           value="{{ old('dob', isset($employee->dob) ? $employee->dob->format('Y-m-d') : '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Nationality</label>
                    <select class="form-control form-control-sm select2" name="nationality_id">
                        <option value="">Select Nationality</option>
                        @foreach($nationalities as $nationality)
                            <option value="{{ $nationality->id }}" 
                                {{ old('nationality_id', $employee->nationality_id ?? '') == $nationality->id ? 'selected' : '' }}>
                                {{ $nationality->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Personal Email</label>
                    <input type="email" class="form-control form-control-sm" name="personal_email" 
                           value="{{ old('personal_email', $employee->personal_email ?? '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Personal Contact</label>
                    <input type="text" class="form-control form-control-sm" name="personal_contact" 
                           value="{{ old('personal_contact', $employee->personal_contact ?? '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Emergency Contact</label>
                    <input type="text" class="form-control form-control-sm" name="emergency_contact" 
                           value="{{ old('emergency_contact', $employee->emergency_contact ?? '') }}">
                </div>
                
                <div class="col-md-6 form-group mb-3">
                    <label>Address</label>
                    <textarea class="form-control form-control-sm" name="address" rows="3">{{ old('address', $employee->address ?? '') }}</textarea>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Employment Details Card -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-briefcase ti-sm" style="background: #a002aa38; color: #a002aa;"></i>
                <b>Employment Details</b>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <form class="employee-form" id="employment-form">
            @csrf
            @if(isset($employee))
                @method('PUT')
            @endif
            
            <div class="row">
                
                <div class="col-md-3 form-group mb-3">
                    <label>Department</label>
                    <select class="form-control form-control-sm select2" name="department_id">
                        <option value="">Select Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" 
                                {{ old('department_id', $employee->department_id ?? '') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Designation</label>
                    <input type="text" class="form-control form-control-sm" name="designation" 
                           value="{{ old('designation', $employee->designation ?? '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Branch</label>
                    <select class="form-control form-control-sm select2" name="branch_id">
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" 
                                {{ old('branch_id', $employee->branch_id ?? '') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }} ({{ $branch->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Date of Joining</label>
                    <input type="date" class="form-control form-control-sm" name="doj" 
                           value="{{ old('doj', isset($employee->doj) ? $employee->doj->format('Y-m-d') : '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Salary (AED)</label>
                    <input type="number" step="0.01" class="form-control form-control-sm" name="salary" 
                           value="{{ old('salary', $employee->salary ?? '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Company Email</label>
                    <input type="email" class="form-control form-control-sm" name="company_email" 
                           value="{{ old('company_email', $employee->company_email ?? '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Status</label>
                    <select class="form-control form-control-sm select2" name="status">
                        <option value="active" {{ old('status', $employee->status ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $employee->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="on leave" {{ old('status', $employee->status ?? '') == 'on leave' ? 'selected' : '' }}>On Leave</option>
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
                <i class="ti ti-id ti-sm" style="background: #3a3a3c52; color: #3a3a3c;"></i>
                <b>Documents (Emirates ID | Passport | Visa)</b>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <form class="employee-form" id="documents-form">
            @csrf
            @if(isset($employee))
                @method('PUT')
            @endif
            
            <div class="row">
                <div class="col-md-3 form-group mb-3">
                    <label>Emirates ID</label>
                    <input type="text" class="form-control form-control-sm" name="emirate_id" oninput="formatEmirateId(this)"
                        placeholder="XXX-XXXX-XXXXXXX-X"   value="{{ old('emirate_id', $employee->emirate_id ?? '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Passport Number</label>
                    <input type="text" class="form-control form-control-sm" name="passport" 
                           value="{{ old('passport', $employee->passport ?? '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Visa Sponsor</label>
                    <input type="text" class="form-control form-control-sm" name="visa_sponsor" 
                           value="{{ old('visa_sponsor', $employee->visa_sponsor ?? '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Visa Occupation</label>
                    <input type="text" class="form-control form-control-sm" name="visa_occupation" 
                           value="{{ old('visa_occupation', $employee->visa_occupation ?? '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Emirates ID Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="emirate_expiry" 
                           value="{{ old('emirate_expiry', isset($employee->emirate_expiry) ? $employee->emirate_expiry->format('Y-m-d') : '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Passport Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="passport_expiry" 
                           value="{{ old('passport_expiry', isset($employee->passport_expiry) ? $employee->passport_expiry->format('Y-m-d') : '') }}">
                </div>
                
                <div class="col-md-3 form-group mb-3">
                    <label>Visa Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="visa_expiry" 
                           value="{{ old('visa_expiry', isset($employee->visa_expiry) ? $employee->visa_expiry->format('Y-m-d') : '') }}">
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
        <form class="employee-form" id="notes-form">
            @csrf
            @if(isset($employee))
                @method('PUT')
            @endif
            
            <div class="row">
                <div class="col-md-12">
                    <textarea class="form-control form-control-sm" name="notes" rows="4">{{ old('notes', $employee->notes ?? '') }}</textarea>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Form Actions -->
<div class="form-actions">
    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">
            <i class="ti ti-x me-1"></i>Cancel
        </a>
        <button type="button" class="btn btn-primary" id="save-employee">
            <i class="ti ti-device-floppy me-1"></i>
            {{ isset($employee) ? 'Update Employee' : 'Create Employee' }}
        </button>
    </div>
</div>

@endsection

@push('third_party_scripts')
<script>
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
        placeholder: 'Select an option'
    });

    // Handle save button click
    $('#save-employee').click(function() {
        // Collect data from all forms
        const personalData = $('#personal-form').serializeArray();
        const employmentData = $('#employment-form').serializeArray();
        const documentsData = $('#documents-form').serializeArray();
        const notesData = $('#notes-form').serializeArray();
        
        // Combine all data
        const formData = new FormData();
        
        // Add CSRF token
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        
        @if(isset($employee))
            formData.append('_method', 'PUT');
        @endif
        
        // Append all form data
        [...personalData, ...employmentData, ...documentsData, ...notesData].forEach(item => {
            if (item.name !== '_token' && item.name !== '_method') {
                formData.append(item.name, item.value);
            }
        });
        
        // Add loading state
        const btn = $(this);
        const originalText = btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        // Determine URL and method
        const url = '{{ isset($employee) ? route("employees.update", $employee->id) : route("employees.store") }}';
        const method = 'POST'; // Using POST with _method override for PUT
        
        // Make AJAX request
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    
                    // Redirect to employee list or show page
                    setTimeout(() => {
                        window.location.href = '{{ route("employees.index") }}';
                    }, 1500);
                } else {
                    toastr.error('Error saving employee information');
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
                    toastr.error('Error saving employee information');
                }
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Form validation on input
    $('input, select, textarea').on('change', function() {
        $(this).removeClass('is-invalid');
    });
});
</script>
@endpush