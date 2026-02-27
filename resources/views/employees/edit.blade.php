@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Employee Details</h5>
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data" id="formajax">
                        @csrf
                        @method('PUT')
                        <!-- Personal Information Section -->
                        <div class="row mb-2 p-4">
                            <div class="col-md-8"></div>
                            
                            <div class="col-md-4 mb-4">
                                <label class="form-label text-primary" for="profile_image">Profile Image</label>
                                <input type="file" 
                                       class="form-control @error('profile_image') is-invalid @enderror" 
                                       id="profile_image" 
                                       name="profile_image" 
                                       accept="image/*">
                                <small class="text-info">Upload a profile image (JPG, PNG, GIF - max 2MB)</small>
                                @error('profile_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-2 p-4">
                            <div class="col-12">
                                <h6 class="fw-semibold text-primary">Personal Information</h6>
                                <hr class="mt-0">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="employee_id">Employee ID <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('employee_id') is-invalid @enderror" 
                                       id="employee_id" 
                                       name="employee_id" 
                                       value="{{ $employee->employee_id }}" 
                                       placeholder="EMP-001" 
                                       readonly>
                                @error('employee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ $employee->name}}" 
                                       placeholder="John Doe" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="nationality_id">Nationality <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('nationality_id') is-invalid @enderror" 
                                        id="nationality_id" 
                                        name="nationality_id" 
                                        required>
                                    <option value="">Select Nationality</option>
                                    @foreach($nationalities as $nationality)
                                        <option value="{{ $nationality->id }}" {{ $employee->nationality_id == $nationality->id ? 'selected' : '' }}>
                                            {{ $nationality->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('nationality_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="dob">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control @error('dob') is-invalid @enderror" 
                                       id="dob" 
                                       name="dob" 
                                       value="{{ $employee->dob ? $employee->dob->format('Y-m-d') : null }}"
                                       required>
                                @error('dob')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="personal_email">Personal Email <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control @error('personal_email') is-invalid @enderror" 
                                       id="personal_email" 
                                       name="personal_email" 
                                       value="{{ $employee->personal_email ?? null }}" 
                                       placeholder="john@gmail.com" 
                                       required>
                                @error('personal_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="personal_contact">Personal Contact</label>
                                <input type="text" 
                                       class="form-control @error('personal_contact') is-invalid @enderror" 
                                       id="personal_contact" 
                                       name="personal_contact" 
                                       value="{{ $employee->personal_contact ?? null }}" 
                                       placeholder="+971 XX XXX XXXX">
                                @error('personal_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="emergency_contact">Emergency Contact</label>
                                <input type="text" 
                                       class="form-control @error('emergency_contact') is-invalid @enderror" 
                                       id="emergency_contact" 
                                       name="emergency_contact" 
                                       value="{{ $employee->emergency_contact ?? null }}" 
                                       placeholder="Emergency contact number">
                                @error('emergency_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Company Information Section -->
                        <div class="row mb-2 p-4">
                            <div class="col-12">
                                <h6 class="fw-semibold text-primary">Company Information</h6>
                                <hr class="mt-0">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="company_email">Company Email <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control @error('company_email') is-invalid @enderror" 
                                       id="company_email" 
                                       name="company_email" 
                                       value="{{ $employee->company_email ?? null }}" 
                                       placeholder="john@company.com" 
                                       required>
                                @error('company_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="company_contact">Company Contact</label>
                                <input type="text" 
                                       class="form-control @error('company_contact') is-invalid @enderror" 
                                       id="company_contact" 
                                       name="company_contact" 
                                       value="{{ $employee->company_contact ?? null }}" 
                                       placeholder="04 XXX XXXX">
                                @error('company_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="branch_id">Branch <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('branch_id') is-invalid @enderror" 
                                        id="branch_id" 
                                        name="branch_id" 
                                        required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $employee->branch_id == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->code . ' - ' . $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="department_id">Department</label>
                                <select class="form-select select2 @error('department_id') is-invalid @enderror" 
                                        id="department_id" 
                                        name="department_id">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ $employee->department_id == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="designation">Designation</label>
                                <input type="text" 
                                       class="form-control @error('designation') is-invalid @enderror" 
                                       id="designation" 
                                       name="designation" 
                                       value="{{ $employee->designation ?? null }}" 
                                       placeholder="Software Engineer">
                                @error('designation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="salary">Salary (AED)</label>
                                <input type="number" 
                                       step="0.01" 
                                       class="form-control @error('salary') is-invalid @enderror" 
                                       id="salary" 
                                       name="salary" 
                                       value="{{ $employee->salary ?? null }}" 
                                       placeholder="5000.00">
                                @error('salary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="doj">Date of Joining <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control @error('doj') is-invalid @enderror" 
                                       id="doj" 
                                       name="doj" 
                                       value="{{ $employee->doj ? $employee->doj->format('Y-m-d') : null }}" 
                                       required>
                                @error('doj')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" 
                                        name="status">
                                    <option value="active" {{ $employee->status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $employee->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="on_leave" {{ $employee->status == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Document Information Section -->
                        <div class="row mb-2 p-4">
                            <div class="col-12">
                                <h6 class="fw-semibold text-primary">Document Information</h6>
                                <hr class="mt-0">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="emirate_id">Emirates ID</label>
                                <input type="text" 
                                       class="form-control @error('emirate_id') is-invalid @enderror" 
                                       id="emirate_id" 
                                       name="emirate_id" 
                                       oninput="formatEmirateId(this)"
                                       value="{{ $employee->emirate_id ?? null }}" 
                                       placeholder="XXX-XXXX-XXXXXXX-X">
                                @error('emirate_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="emirate_expiry">Emirates ID Expiry</label>
                                <input type="date" 
                                       class="form-control @error('emirate_expiry') is-invalid @enderror" 
                                       id="emirate_expiry" 
                                       name="emirate_expiry" 
                                       value="{{ $employee->emirate_expiry ? $employee->emirate_expiry->format('Y-m-d') : null }}">
                                @error('emirate_expiry')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="passport">Passport Number</label>
                                <input type="text" 
                                       class="form-control @error('passport') is-invalid @enderror" 
                                       id="passport" 
                                       name="passport" 
                                       value="{{ $employee->passport ?? null }}" 
                                       placeholder="Passport number">
                                @error('passport')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="passport_expiry">Passport Expiry</label>
                                <input type="date" 
                                       class="form-control @error('passport_expiry') is-invalid @enderror" 
                                       id="passport_expiry" 
                                       name="passport_expiry" 
                                       value="{{ $employee->passport_expiry ? $employee->passport_expiry->format('Y-m-d') : null }}">
                                @error('passport_expiry')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="visa_sponsor">Visa Sponsor</label>
                                <datalist id="sponsorOptions">
                                    <option value="Express Fast Delivery Service">
                                </datalist>
                                <input type="text" 
                                       class="form-control @error('visa_sponsor') is-invalid @enderror" 
                                       id="visa_sponsor" 
                                       name="visa_sponsor" 
                                       list="sponsorOptions"
                                       value="{{ $employee->visa_sponsor ?? null }}" 
                                       placeholder="Sponsor name">
                                @error('visa_sponsor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="visa_occupation">Visa Occupation</label>
                                <input type="text" 
                                       class="form-control @error('visa_occupation') is-invalid @enderror" 
                                       id="visa_occupation" 
                                       name="visa_occupation" 
                                       value="{{ $employee->visa_occupation ?? null }}" 
                                       placeholder="Occupation on visa">
                                @error('visa_occupation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="visa_expiry">Visa Expiry</label>
                                <input type="date" 
                                       class="form-control @error('visa_expiry') is-invalid @enderror" 
                                       id="visa_expiry" 
                                       name="visa_expiry" 
                                       value="{{ $employee->visa_expiry ? $employee->visa_expiry->format('Y-m-d') : null }}">
                                @error('visa_expiry')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Additional Information Section -->
                        <div class="row mb-2 p-4">
                            <div class="col-12">
                                <h6 class="fw-semibold text-primary">Additional Information</h6>
                                <hr class="mt-0">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="address">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          id="address" 
                                          name="address" 
                                          rows="3" 
                                          placeholder="Full address">{{ $employee->address ?? null }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="notes">Notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" 
                                          name="notes" 
                                          rows="3" 
                                          placeholder="Additional notes">{{ $employee->notes ?? null }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="reset" class="btn btn-secondary me-1">
                                    <i class="fa fa-reset me-1"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save me-1"></i> Create Employee
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Preview image before upload
    $('#profile_image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // You can add image preview here if needed
                console.log('Image selected:', file.name);
            }
            reader.readAsDataURL(file);
        }
    });

    $('.select2').select2({
        width: '100%',
        placeholder: 'Select an option',
        allowClear: true
    });
});

function formatEmirateId(input) {
    // Remove all non-digits
    let value = input.value.replace(/\D/g, '');
    
    // Apply formatting
    if (value.length > 0) {
        // Format: 784-XXXX-XXXXXXX-X
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
</script>
@endsection