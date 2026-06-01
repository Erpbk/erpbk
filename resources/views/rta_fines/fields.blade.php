<script src="{{ asset('js/modal_custom.js') }}"></script>
<div class="row">
    <div class="col-md-9">
        <div class="row">


            <!-- Rider Id Field -->
            <div class="form-group col-sm-4">
                <label class="">Bike:</label>
                <select class="form-select select2" required onchange="selectbike(this.value)" id="bike_id" name="bike_id">
                    <option value=""></option>
                    @foreach($bikes as $b)
                    <option value="{{ $b->id }}" {{ (isset($rtaFines) && $rtaFines->bike_id == $b->id) ? 'selected' : '' }}>
                        {{ $b->plate }} - {{ $b->leasingCompany ? $b->leasingCompany->name : 'N/A' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-sm-4">
                <label class="">Rider:</label>
                <select class="form-select select2" id="rider_account" name="rider_id">
                </select>
            </div>
            <div class="form-group col-sm-4">
                <label class="">Rental Company:</label>
                <select class="form-select select2" id="company_account" name="rental_company_id">
                </select>
            </div>
        
        </div>
        <div class="row">

            <!-- Ticket No Field -->
            <div class="form-group col-sm-4">
                {!! Form::label('ticket_no', 'Ticket No:', ['class' => 'required']) !!}
                {!! Form::text('ticket_no', $rtaFines->ticket_no ?? '', ['class' => 'form-control', 'maxlength' => 50, 'required']) !!}
            </div>

            <!-- Trip Date Field -->
            <div class="form-group col-sm-4">
                {!! Form::label('trip_date', 'Trip Date:' , ['class' => 'required']) !!}
                {!! Form::date('trip_date', $rtaFines->trip_date ?? 'null', ['class' => 'form-control', 'required']) !!}
            </div>

            <!-- Trip Time Field -->
            <div class="form-group col-sm-4">
                {!! Form::label('trip_time', 'Trip Time:', ['class' => 'required']) !!}
                {!! Form::time('trip_time', $rtaFines->trip_time ?? 'null', ['class' => 'form-control', 'required']) !!}
            </div>

            <!-- Billing Month Field -->
            <div class="form-group col-sm-4">
                {!! Form::label('billing_month', 'Billing Month:', ['class' => 'required']) !!}
                {!! Form::month('billing_month', isset($rtaFines) && $rtaFines->billing_month ? \Carbon\Carbon::parse($rtaFines->billing_month)->format('Y-m') : null, ['class' => 'form-control' , 'required']) !!}
            </div>

            <!-- Reference Number -->
            <div class="form-group col-sm-4">
                {!! Form::label('reference_number', 'Reference Number:') !!}
                {!! Form::text('reference_number', $rtaFines->reference_number ?? '' , ['class' => 'form-control','step'=>'any']) !!}
            </div>

            <div class="form-group col-sm-4">
                {!! Form::label('rta_account_id', 'Credit Account:', ['class' => 'required']) !!}
                <input type="hidden" name="rta_account_id" value="{{ $rtaFineAccount->id }}">
                <input type="text" name="rta_account" value="{{ $rtaFineAccount->name ?? '' }}" readonly class="form-control">
            </div>
            <div class="form-group col-sm-4">
                {!! Form::label('black_points', 'Black Points:') !!}
                <input type="text" name="black_points" value="{{ $rtaFines->black_points ?? '' }}" class="form-control">
            </div>
            <!-- Maintain Inventory Field -->
            <div class="form-group col-sm-3 mt-4">
                <label>Impound Fine</label>
                <div class="status-toggle-container">
                    <input type="hidden" name="is_impound" value="{{ false }}"/>
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_impound" id="" value="1" 
                            @isset($items) @if($items->is_maintained == 1) checked @endif @endisset/>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-1"></div>
    <div class="col-md-2">
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
                {!! Form::file('attachment_path', ['class' => 'form-control d-none', 'id' => 'attachmentInput', 'accept' => 'image/*']) !!}
            </div>
        </div>
    </div>
</div>

<!-- Detail Field -->
<div class="row">
    <div class="form-group col-sm-6">
        {!! Form::label('detail', 'Detail:', ['class' => 'required']) !!}
        {!! Form::textarea('detail', $rtaFines->detail ?? '', ['class' => 'form-control', 'maxlength' => 500, 'rows' => 3, 'required']) !!}
    </div>
</div>
<div class="row mt-4 mb-4">
    <div class="col-sm-12">
        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount (AED)</th>
                    <th>VAT (%)</th>
                    <th>VAT (AED)</th>
                    <th>Total (AED)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $fineVat = $rtaFines ? ($rtaFines->fine_vat/100)*$rtaFines->amount : 0;
                    $serviceVat = $rtaFines ? $rtaFines->service_charges*($rtaFines->service_vat/100) : 0;
                    $adminVat = $rtaFines ? $rtaFines->admin_fee*($rtaFines->admin_fee/100) : 0;
                @endphp
                <!-- Fine Row -->
                <tr>
                    <td>
                        <strong>Fine</strong>
                    </td>
                    <td>
                        {!! Form::number('amount', $rtaFines->amount ?? null, ['class' => 'form-control amount-input', 'step' => 'any', 'id' => 'fine_amount', 'placeholder' => '0.00']) !!}
                    </td>
                    <td>
                        {!! Form::number('fine_vat', $rtaFines->fine_vat ?? 0, ['class' => 'form-control vat-percent', 'step' => 'any', 'id' => 'fine_vat_percent', 'placeholder' => '0', 'readonly']) !!}
                    </td>
                    <td>
                        {!! Form::number('fine_vat_amount', $fineVat, ['class' => 'form-control vat-amount', 'step' => 'any', 'id' => 'fine_vat_amount', 'readonly' => 'readonly', 'placeholder' => '0.00', 'readonly']) !!}
                    </td>
                    <td>
                        {!! Form::number('fine_total', $rtaFines ? $rtaFines->amount + $fineVat : 0, ['class' => 'form-control total-amount', 'step' => 'any', 'id' => 'fine_total', 'readonly' => 'readonly', 'placeholder' => '0.00']) !!}
                    </td>
                </tr>
                
                <!-- Service Charges Row -->
                <tr>
                    <td>
                        <strong>Service Charges</strong>
                    </td>
                    <td>
                        {!! Form::number('service_charges', $rtaFines->service_charges ?? null, ['class' => 'form-control amount-input', 'step' => 'any', 'id' => 'service_amount', 'placeholder' => '0.00']) !!}
                    </td>
                    <td>
                        {!! Form::number('service_vat', $rtaFines->service_vat ?? 0, ['class' => 'form-control vat-percent', 'step' => 'any', 'id' => 'service_vat_percent', 'placeholder' => '0']) !!}
                    </td>
                    <td>
                        {!! Form::number('service_vat_amount', $serviceVat, ['class' => 'form-control vat-amount', 'step' => 'any', 'id' => 'service_vat_amount', 'readonly' => 'readonly', 'placeholder' => '0.00']) !!}
                    </td>
                    <td>
                        {!! Form::number('service_total', $rtaFines ? $rtaFines->service_charges + $serviceVat : 0, ['class' => 'form-control total-amount', 'step' => 'any', 'id' => 'service_total', 'readonly' => 'readonly', 'placeholder' => '0.00']) !!}
                    </td>
                </tr>
                
                <!-- Admin Charges Row -->
                <tr>
                    <td>
                        <strong>Admin Charges</strong>
                    </td>
                    <td>
                        {!! Form::number('admin_fee', $rtaFines->admin_fee ?? null, ['class' => 'form-control amount-input', 'step' => 'any', 'id' => 'admin_amount', 'placeholder' => '0.00']) !!}
                    </td>
                    <td>
                        {!! Form::number('admin_vat', $rtaFines->admin_vat ?? 0, ['class' => 'form-control vat-percent', 'step' => 'any', 'id' => 'admin_vat_percent', 'placeholder' => '0']) !!}
                    </td>
                    <td>
                        {!! Form::number('admin_vat_amount', $adminVat, ['class' => 'form-control vat-amount', 'step' => 'any', 'id' => 'admin_vat_amount', 'readonly' => 'readonly', 'placeholder' => '0.00']) !!}
                    </td>
                    <td>
                        {!! Form::number('admin_total', $rtaFines ? $rtaFines->admin_fee + $adminVat : 0, ['class' => 'form-control total-amount', 'step' => 'any', 'id' => 'admin_total', 'readonly' => 'readonly', 'placeholder' => '0.00']) !!}
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Grand Total:</strong></td>
                    <td>
                        {!! Form::number('vat', 0, ['class' => 'form-control', 'step' => 'any', 'id' => 'vat_total', 'readonly' => 'readonly', 'style' => 'font-weight: bold; background: #f0f0f0;']) !!}
                    </td>
                    <td>
                        {!! Form::number('total_amount', 0, ['class' => 'form-control', 'step' => 'any', 'id' => 'grand_total', 'readonly' => 'readonly', 'style' => 'font-weight: bold; background: #f0f0f0;']) !!}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">
    // Function to calculate VAT and Total for a row
    function calculateRow(amountId, vatPercentId, vatAmountId, totalId) {
        let amount = parseFloat($('#' + amountId).val()) || 0;
        let vatPercent = parseFloat($('#' + vatPercentId).val()) || 0;
        
        let vatAmount = (amount * vatPercent) / 100;
        let total = amount + vatAmount;
        
        $('#' + vatAmountId).val(vatAmount.toFixed(2));
        $('#' + totalId).val(total.toFixed(2));
        
        calculateGrandTotal();
    }
    
    // Function to calculate grand total
    function calculateGrandTotal() {
        let fineTotal = parseFloat($('#fine_total').val()) || 0;
        let serviceTotal = parseFloat($('#service_total').val()) || 0;
        let adminTotal = parseFloat($('#admin_total').val()) || 0;
        let fineVat = parseFloat($('#fine_vat_amount').val()) || 0;
        let ServiceVat = parseFloat($('#service_vat_amount').val()) || 0;
        let AdminVat = parseFloat($('#admin_vat_amount').val()) || 0;

        
        let grandTotal = fineTotal + serviceTotal + adminTotal;
        let vatTotal = fineVat + ServiceVat + AdminVat;
        $('#grand_total').val(grandTotal.toFixed(2));
        $('#vat_total').val(vatTotal.toFixed(2));
    }


    function selectbike(id) {
        if (id) {
            var tripDate = $('#trip_date').val();
            $.ajax({
                type: 'get',
                url: '{{ route("rtaFines.getrider","") }}/' + id,
                data: { trip_date: tripDate},
                dataType: 'json',
                success: function(res) {
                    // Handle riders dropdown
                    if (res.riders) {
                        $('#rider_account').html(res.riders);
                        // Update Select2 with new options
                        $('#rider_account').select2({
                            allowClear: true,
                            dropdownParent: $('#modalTopbody')
                        });
                        $('#rider_account').closest('.form-group').show(); // Show the form group
                        $('#rider_account').closest('.select2-container').show();
                    } else {
                        $('#rider_account').html('');
                        $('#rider_account').closest('.form-group').hide();
                        $('#rider_account').closest('.select2-container').hide();
                    }
                    
                    // Handle companies dropdown
                    if (res.companies) {
                        $('#company_account').html(res.companies);
                        $('#company_account').select2({
                            allowClear: true,
                            dropdownParent: $('#modalTopbody')
                        });
                        $('#company_account').closest('.form-group').show();
                        $('#company_account').closest('.select2-container').show();
                    } else {
                        $('#company_account').html('');
                        $('#company_account').closest('.form-group').hide();
                        $('#company_account').closest('.select2-container').hide();
                    }
                },
                error: function(xhr, status, error) {
                    $('#rider_account').closest('.form-group').show();
                    $('#rider_account').closest('.select2-container').show();
                    $('#rider_account').html('<option value="">Please select a bike first</option>');
                    $('#company_account').html('');
                    $('#company_account').closest('.form-group').hide();
                    $('#company_account').closest('.select2-container').hide();
                }
            });
        } else {
            $('#rider_account').closest('.form-group').show();
            $('#rider_account').closest('.select2-container').show();
            $('#rider_account').html('<option value="">Please select a bike first</option>');
            $('#company_account').html('');
            $('#company_account').closest('.form-group').hide();
            $('#company_account').closest('.select2-container').hide();
        }
    }
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            allowClear: true,
            dropdownParent: $('#modalTopbody')
        });
        
        // Check if bike is selected on page load (edit mode)
        var selectedBikeId = @if(isset($rtaFines)) '{{ $rtaFines->bike_id }}' @else false @endif;
        if (selectedBikeId) {
            console.log('in edit form , trying to select rider;');
            selectbike(selectedBikeId);
        }
        // Hide both by default
        $('#rider_account').closest('.form-group').hide();
        $('#rider_account').closest('.select2-container').hide();
        $('#company_account').closest('.form-group').hide();
        $('#company_account').closest('.select2-container').hide();
        
        $('#trip_date').on('input change', function(){
            selectedBikeId = $('#bike_id').val();
            if (selectedBikeId) {
                selectbike(selectedBikeId);
            }
        });

        // Bind events for Fine row
        $('#fine_amount, #fine_vat_percent').on('input', function() {
            calculateRow('fine_amount', 'fine_vat_percent', 'fine_vat_amount', 'fine_total');
        });
        
        // Bind events for Service Charges row
        $('#service_amount, #service_vat_percent').on('input', function() {
            calculateRow('service_amount', 'service_vat_percent', 'service_vat_amount', 'service_total');
        });
        
        // Bind events for Admin Charges row
        $('#admin_amount, #admin_vat_percent').on('input', function() {
            calculateRow('admin_amount', 'admin_vat_percent', 'admin_vat_amount', 'admin_total');
        });
        
        // Initial calculations
        calculateRow('fine_amount', 'fine_vat_percent', 'fine_vat_amount', 'fine_total');
        calculateRow('service_amount', 'service_vat_percent', 'service_vat_amount', 'service_total');
        calculateRow('admin_amount', 'admin_vat_percent', 'admin_vat_amount', 'admin_total');

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
        @isset($rtaFines)
            @if($rtaFines->attachment_path)
                // If attachment is a path/URL, display it
                let existingAttachmentPath = '{{ storage_url($rtaFines->attachment_path) }}';
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