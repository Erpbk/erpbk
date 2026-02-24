<!-- Basic Information -->
    <div class="row mb-4">

        <!-- Status -->
        <div class="col-md-12 mb-3">
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" 
                        type="checkbox" 
                        id="is_active" 
                        name="is_active" 
                        value="1"
                        {{ (isset($branch) && $branch->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>

        <!-- Branch Name -->
        <div class="col-md-6">
            <label class="form-label required" for="name">Branch Name</label>
            <input type="text" 
                    class="form-control @error('name') is-invalid @enderror" 
                    id="name" 
                    name="name" 
                    value="{{ old('name') ?? (isset($branch) ? $branch->name : '') }}" 
                    placeholder="Enter branch name">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Branch Name -->
        <div class="col-md-6">
            <label class="form-label required" for="code">Branch Code</label>
            <input type="text" 
                    class="form-control @error('code') is-invalid @enderror" 
                    id="code" 
                    name="code" 
                    value="{{ old('code') ?? (isset($branch) ? $branch->code : '') }}" 
                    placeholder="Enter branch code">
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Branch Type -->
        <div class="col-md-6">
            <label class="form-label required" for="branch_type">Branch Type</label>
            <select class="form-select @error('branch_type') is-invalid @enderror select2" 
                    id="branch_type" 
                    name="branch_type">
                <option value="">Select Type</option>
                @foreach($branchTypes as $value => $label)
                    <option value="{{ $value }}" {{ (old('branch_type') == $value) || (isset($branch) && $branch->branch_type == $value) ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('branch_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Parent Branch -->
        <div class="col-md-6">
            <label class="form-label" for="parent_branch_id">Parent Branch</label>
            <select class="form-select  @error('parent_branch_id') is-invalid @enderror select2" 
                    id="parent_branch_id" 
                    name="parent_branch_id">
                <option value="">None (Root Level)</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ (old('parent_branch_id') == $parent->id) || (isset($branch) && $branch->parent_branch_id == $parent->id) ? 'selected' : '' }}>
                        {{ $parent->name }} ({{ $parent->type }})
                    </option>
                @endforeach
            </select>
            @error('parent_branch_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Contact -->
        <div class="col-md-6">
            <label class="form-label" for="contact">Contact Number</label>
            <input type="text" 
                    class="form-control @error('contact') is-invalid @enderror" 
                    id="contact" 
                    name="contact" 
                    value="{{ old('contact') ?? (isset($branch) ? $branch->contact : '') }}" 
                    placeholder="Enter contact person or phone number">
            @error('contact')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Address Information -->
    <div class="row mb-4">

        <!-- Address -->
        <div class="col-md-6">
            <label class="form-label" for="address">Full Address</label>
            <textarea class="form-control @error('address') is-invalid @enderror" 
                        id="address" 
                        name="address" 
                        rows="3" 
                        placeholder="Enter complete address">{{ old('address') ?? (isset($branch) ? $branch->address : '') }}</textarea>
            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Description -->
        <div class="col-md-6">
            <label class="form-label" for="description">Description/Notes</label>
            <textarea class="form-control @error('description') is-invalid @enderror" 
                        id="description" 
                        name="description" 
                        rows="3" 
                        placeholder="Enter any additional notes or description">{{ old('description') ?? (isset($branch) ? $branch->description : '') }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Optional: Add any special notes about this branch</small>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="row mt-4">
        <div class="col text-end">
            <button type="submit" class="btn btn-primary">
                <i class="icon-base ti ti-device-floppy me-1"></i> Save
            </button>
        </div>
    </div>

<script>
$(document).ready(function() {
    // Initialize Select2 for parent branch dropdown
    $('.select2').select2({
        dropdownParent: $('#formajax'),
        allowClear: true,
        width: '100%',
    });

    // Form validation
    $('#formajax').submit(function(e) {
        var name = $('#name').val();
        var branchType = $('#branch_type').val();
        
        if (!name.trim()) {
            e.preventDefault();
            toastr.error('Branch name is required.');
            $('#name').focus();
            return false;
        }
        
        if (!branchType) {
            e.preventDefault();
            toastr.error('Please select a branch type.');
            $('#branch_type').focus();
            return false;
        }
    });

    // Branch type change handler
    $('#branch_type').change(function() {
        var type = $(this).val();
        var warning = '';
        
        switch(type) {
            case 'headquarters':
                warning = 'Note: Only one headquarters branch can exist. Marking this headquarters will change existing headquarters to regular branch.';
                break;
            case 'warehouse':
                warning = 'Warehouse branches are primarily for inventory storage.';
                break;
            case 'grage':
                warning = 'Garage branches are for vehicle maintenance and repairs.';
                break;
        }
        
        if (warning) {
            toastr.info(warning);
        }
    });

    // Character counter for description
    $('#description').on('input', function() {
        var maxLength = 255;
        var currentLength = $(this).val().length;
        
        if (currentLength > maxLength) {
            $(this).val($(this).val().substring(0, maxLength));
        }
    });
});
</script>