<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="row">
    <div class="col-sm-9 px-2">
        <div class="row">
            <!-- Name Field -->
            <div class="form-group col-sm-6">
                {!! Form::label('name', 'Name:') !!}
                {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
            </div>
            <!-- Barcode Field -->
            <div class="form-group col-sm-5">
                {!! Form::label('barcode', 'Barcode:') !!}
                {!! Form::text('barcode', null, ['class' => 'form-control']) !!}
            </div>

            <!-- Cost Field -->
            <div class="form-group col-sm-3">
                {!! Form::label('cost', 'Cost:') !!}
                {!! Form::number('cost', null, ['class' => 'form-control cost-input', 'step'=>'any', 'id' => 'cost_input']) !!}
            </div>
            <!-- Price Field -->
            <div class="form-group col-sm-3">
                {!! Form::label('price', 'Price:') !!}
                {!! Form::number('price', null, ['class' => 'form-control price-input', 'step'=>'any', 'id' => 'price_input']) !!}
            </div>

            <div class="form-group col-sm-3">
                {!! Form::label('margin', 'Margin %:') !!}
                {!! Form::number('margin', null, ['class' => 'form-control margin-input', 'step'=>'any', 'id' => 'margin_input', 'readonly' => true]) !!}
            </div>
            <!-- Vat Field -->
            <div class="form-group col-sm-2">
                {!! Form::label('vat', 'VAT(%):') !!}
                {!! Form::number('vat', null, ['class' => 'form-control','step'=>'any']) !!}
            </div>
            <div class="col-sm-6"></div>
            <!-- Status Field -->
            <div class="form-group col-sm-2 mt-3">
                <label>Status</label>
                <div class="status-toggle-container">
                    <input type="hidden" name="status" value="2"/>
                    <label class="toggle-switch">
                        <input type="checkbox" name="status" id="status" value="1" 
                            @isset($items) @if($items->status == 1) checked @endif @else checked @endisset/>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Maintain Inventory Field -->
            <div class="form-group col-sm-3 mt-3">
                <label>Maintain Inventory</label>
                <div class="status-toggle-container">
                    <input type="hidden" name="is_maintained" value="{{ false }}"/>
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_maintained" id="" value="1" 
                            @isset($items) @if($items->is_maintained == 1) checked @endif @endisset/>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-3">
        <!-- Attachment Field - Square Box with Image Preview -->
        <div class="form-group col-sm-6">
            <div class="attachment-preview-container d-flex flex-column align-items-center justify-content-center">
                <!-- Square preview box -->
                <div class="square-preview-box" id="squarePreviewBox" onclick="document.getElementById('attachmentInput').click()">
                    <div class="preview-content" id="previewContent">
                        <i class="fa fa-image upload-icon"></i>
                        <span class="upload-text">Click to upload</span>
                    </div>
                    <img id="imagePreview" class="preview-image" style="display: none;" alt="Preview">
                </div>
                {!! Form::file('attachment', ['class' => 'form-control d-none', 'id' => 'attachmentInput', 'accept' => 'image/*']) !!}
                <!-- Hidden field to track existing image in edit mode -->
                <input type="hidden" id="existingAttachment" value="@isset($items){{ $items->attachment }}@endisset">
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Selected Owners Cards Container -->
    <div class="form-group col-sm-12">
        <label>Selected Owner Types:</label>
        <div id="selected-owners-container" class="d-flex flex-wrap gap-1" style="gap: 6px; min-height: 45px;">
            <!-- Selected owner cards will appear here -->
        </div>
        <input type="hidden" name="owner" id="owner-values-input" value="">
        <small class="text-muted" id="no-owners-message">No owner types selected yet.</small>
    </div>

    <!-- Owner Type Selection -->
    <div class="form-group col-sm-12">
        {!! Form::label('owner_type', 'Select Owner Types:') !!}
        <select id="owner_type" class="form-control">
            <option value="">Select an owner type...</option>
            <option value="rider">Rider</option>
            <option value="garage">Garage</option>
            <option value="supplier">Supplier</option>
            <option value="customer">Customer</option>
            <option value="employee">employee</option>
            <option value="leasingCompany">Leasing Company</option>
        </select>
        <small class="text-muted">Click on an option to add it. Selected types will appear below.</small>
    </div>
</div>

<!-- Detail Field -->
<div class="form-group col-sm-12">
    {!! Form::label('detail', 'Detail:') !!}
    {!! Form::textarea('detail', null, ['class' => 'form-control','rows'=>3]) !!}
</div>

<script>
$(document).ready(function () {
    // Initialize select2
    $('#owner_type').select2({
        dropdownParent: $('#modalTopbody'),
        allowClear: true,
        placeholder: "Select an owner type..."
    });

    // ========== MARGIN CALCULATION ==========
    function calculateMargin() {
        let cost = parseFloat($('#cost_input').val()) || 0;
        let price = parseFloat($('#price_input').val()) || 0;
        
        if (cost > 0 && price > 0) {
            // Margin % = ((Price - Cost) / Price) * 100
            let margin = ((price - cost) / cost) * 100;
            $('#margin_input').val(margin.toFixed(2));
        } else {
            $('#margin_input').val('');
        }
    }
    
    // Calculate margin on cost or price change
    $('#cost_input, #price_input').on('input', function() {
        calculateMargin();
    });
    
    // Initial calculation in edit mode
    calculateMargin();
    
    // Array to store selected owners
    let selectedOwners = [];
    
    // Load existing owners if in edit mode
    @isset($items)
        @if($items->owner)
            let existingOwners = @json($items->owner);
            if (Array.isArray(existingOwners)) {
                selectedOwners = existingOwners;
            } else if (typeof existingOwners === 'object') {
                // Handle object format like {"customer":["1"],"rider":["2"]}
                Object.keys(existingOwners).forEach(key => {
                    if (!selectedOwners.includes(key)) {
                        selectedOwners.push(key);
                    }
                });
            }
            updateSelectedOwnersDisplay();
        @endif
    @endisset
    
    // Handle selection from dropdown
    $('#owner_type').on('change', function() {
        let selectedValue = $(this).val();
        
        if (selectedValue && !selectedOwners.includes(selectedValue)) {
            // Add to selected owners array
            selectedOwners.push(selectedValue);
            updateSelectedOwnersDisplay();
        }
        
        // Reset select2
        $(this).val('').trigger('change');
    });
    
    // Function to update the display of selected owners (SMALLER CARDS)
    function updateSelectedOwnersDisplay() {
        let container = $('#selected-owners-container');
        let hiddenInput = $('#owner-values-input');
        
        // Clear container
        container.empty();
        
        if (selectedOwners.length === 0) {
            $('#no-owners-message').show();
            hiddenInput.val('');
            return;
        }
        
        $('#no-owners-message').hide();
        
        // Sort owners alphabetically
        let sortedOwners = [...selectedOwners].sort();
        
        // Owner display names and colors
        let ownerConfig = {
            'rider': { label: 'Rider', color: '#17a2b8', icon: 'fa-motorcycle' },
            'garage': { label: 'Garage', color: '#dc3545', icon: 'fa-car' },
            'supplier': { label: 'Supplier', color: '#007bff', icon: 'fa-truck' },
            'customer': { label: 'Customer', color: '#28a745', icon: 'fa-user' },
            'leasingCompany': { label: 'Leasing', color: '#ffc107', icon: 'fa-building' }
        };
        
        // Create smaller cards for each selected owner
        sortedOwners.forEach(function(owner) {
            let config = ownerConfig[owner] || { 
                label: owner.charAt(0).toUpperCase() + owner.slice(1).substring(0, 8), 
                color: '#6c757d',
                icon: 'fa-tag'
            };
            
            let card = $(`
                <div class="card owner-card-sm" data-owner="${owner}" style="border-left: 3px solid ${config.color};">
                    <div class="card-body py-0 px-2 d-flex align-items-center justify-content-between" style="height: 28px;">
                        <div class="d-flex align-items-center">
                            <i class="fa ${config.icon} fa-xs" style="color: ${config.color};"></i>
                            <span class="ms-1 small fw-medium">${config.label}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2 remove-owner-sm" data-owner="${owner}" style="font-size: 10px; line-height: 1;">
                            <i class="fa fa-times-circle"></i>
                        </button>
                    </div>
                </div>
            `);
            
            container.append(card);
        });
        
        // Update hidden input with JSON value
        hiddenInput.val(JSON.stringify(selectedOwners));
    }
    
    // Handle removal of owner cards (updated selector)
    $(document).on('click', '.remove-owner-sm', function() {
        let ownerToRemove = $(this).data('owner');
        let index = selectedOwners.indexOf(ownerToRemove);
        
        if (index !== -1) {
            selectedOwners.splice(index, 1);
            updateSelectedOwnersDisplay();
        }
    });
    
    // Form submission - ensure owners are properly saved
    $('form').on('submit', function() {
        let hiddenInput = $('#owner-values-input');
        if (selectedOwners.length > 0) {
            hiddenInput.val(JSON.stringify(selectedOwners));
        } else {
            hiddenInput.val('');
        }
    });
    
    // ========== ATTACHMENT SQUARE BOX WITH IMAGE PREVIEW ==========
    const attachmentInput = document.getElementById('attachmentInput');
    const imagePreview = document.getElementById('imagePreview');
    const previewContent = document.getElementById('previewContent');
    const squarePreviewBox = document.getElementById('squarePreviewBox');
    
    // Function to display preview from file or existing image
    function displayPreview(fileOrUrl, isFile = true) {
        if (isFile && fileOrUrl) {
            // Handle file object
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                previewContent.style.display = 'none';
            };
            reader.readAsDataURL(fileOrUrl);
        } else if (!isFile && fileOrUrl) {
            // Handle existing image URL from edit mode
            imagePreview.src = fileOrUrl;
            imagePreview.style.display = 'block';
            previewContent.style.display = 'none';
        } else {
            // No image, show upload placeholder
            imagePreview.style.display = 'none';
            previewContent.style.display = 'flex';
        }
    }
    
    // Handle file selection
    if (attachmentInput) {
        attachmentInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                displayPreview(file, true);
            } else if (file) {
                alert('Please select a valid image file (JPG, PNG, GIF).');
                attachmentInput.value = '';
                displayPreview(null, false);
            } else {
                displayPreview(null, false);
            }
        });
    }
    
    // Load existing image in edit mode
    @isset($items)
        @if($items->attachment)
            // If attachment is a path/URL, display it
            let existingAttachmentPath = '{{ Storage::url($items->attachment) }}';
            if (existingAttachmentPath) {
                // Wait a moment for DOM to be ready
                setTimeout(function() {
                    displayPreview(existingAttachmentPath, false);
                }, 100);
            }
        @endif
    @endisset
    
    // Make the square box clickable to trigger file input
    if (squarePreviewBox) {
        squarePreviewBox.style.cursor = 'pointer';
    }
    
    // Add CSS styles
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            /* Smaller Owner Cards */
            .owner-card-sm {
                display: inline-flex;
                background-color: #f8f9fa;
                border-radius: 20px;
                padding: 0;
                transition: all 0.2s ease;
                cursor: default;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }
            .owner-card-sm:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                background-color: #fff;
            }
            .owner-card-sm .btn-link {
                text-decoration: none;
                opacity: 0.6;
            }
            .owner-card-sm .btn-link:hover {
                opacity: 1;
                transform: scale(1.1);
            }
            #selected-owners-container {
                border: 1px dashed #dee2e6;
                border-radius: 8px;
                padding: 6px 10px;
                background-color: #fafbfc;
                min-height: 45px;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
            }
            
            /* Margin Input Readonly Style */
            .margin-input[readonly] {
                background-color: #e9ecef;
                cursor: not-allowed;
            }
            
            /* Attachment Square Box Styles */
            .attachment-preview-container {
                width: 100%;
            }
            .square-preview-box {
                width: 200px;
                height: 200px;
                background-color: #f8f9fa;
                border: 2px dashed #dee2e6;
                border-radius: 12px;
                overflow: hidden;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                cursor: pointer;
            }
            .square-preview-box:hover {
                border-color: #007bff;
                background-color: #f1f3f5;
                transform: scale(1.02);
            }
            .preview-content {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                color: #6c757d;
                text-align: center;
            }
            .upload-icon {
                font-size: 48px;
                margin-bottom: 8px;
                color: #adb5bd;
            }
            .upload-text {
                font-size: 14px;
                font-weight: 500;
            }
            .preview-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            .square-preview-box .remove-image-btn {
                position: absolute;
                top: 5px;
                right: 5px;
                background: rgba(0,0,0,0.6);
                color: white;
                border: none;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                font-size: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 10;
                transition: all 0.2s ease;
            }
            .square-preview-box .remove-image-btn:hover {
                background: rgba(220,53,69,0.9);
                transform: scale(1.1);
            }
            
            /* Toggle Switch Styles */
            .status-toggle-container {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .toggle-switch {
                position: relative;
                display: inline-block;
                width: 52px;
                height: 28px;
                cursor: pointer;
            }
            
            .toggle-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .toggle-slider {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #dc3545;
                border-radius: 34px;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                cursor: pointer;
            }
            
            .toggle-slider:before {
                position: absolute;
                content: "";
                height: 22px;
                width: 22px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                border-radius: 50%;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            .toggle-switch input:checked + .toggle-slider {
                background-color: #28a745;
            }
            
            .toggle-switch input:checked + .toggle-slider:before {
                transform: translateX(24px);
            }
            
            .toggle-switch:hover .toggle-slider {
                opacity: 0.85;
            }
            
            .toggle-switch input:focus + .toggle-slider {
                box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3);
            }
        `)
        .appendTo('head');
        
    // Add remove image overlay for better UX
    function addRemoveButtonToPreview() {
        if ($('.square-preview-box .remove-image-btn').length === 0) {
            const removeBtn = $('<button type="button" class="remove-image-btn" title="Remove image"><i class="fa fa-times"></i></button>');
            removeBtn.on('click', function(e) {
                e.stopPropagation();
                attachmentInput.value = '';
                imagePreview.style.display = 'none';
                previewContent.style.display = 'flex';
                imagePreview.src = '';
                $('#existingAttachment').val('');
                $(this).remove();
            });
            $('.square-preview-box').append(removeBtn);
        }
    }
    
    // Observe changes to image preview display
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'style' && imagePreview.style.display === 'block') {
                addRemoveButtonToPreview();
            } else if (mutation.attributeName === 'style' && imagePreview.style.display === 'none') {
                $('.square-preview-box .remove-image-btn').remove();
            }
        });
    });
    
    if (imagePreview) {
        observer.observe(imagePreview, { attributes: true });
        if (imagePreview.style.display === 'block') {
            addRemoveButtonToPreview();
        }
    }
    
    setTimeout(function() {
        if (imagePreview.style.display === 'block') {
            addRemoveButtonToPreview();
        }
    }, 200);
});
</script>