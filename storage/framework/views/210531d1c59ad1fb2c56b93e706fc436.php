<!-- Rider Info Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white fs-5 fw-bold p-4">Rider Info</div>
    <div class="card-body">
        <div class="row">
            <!-- Rider ID -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('rider_id', 'Rider ID:',['class'=>'required']); ?>

                <?php echo Form::number('rider_id', null, ['class' => 'form-control','required', 'id' => 'rider_id_field']); ?>

                <div class="invalid-feedback" id="rider_id_error" style="display: none;"></div>
                <?php $__errorArgs = ['rider_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <span class="text-danger"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- Courier ID -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('courier_id', 'Courier ID:'); ?>

                <?php echo Form::number('courier_id', null, ['class' => 'form-control', 'id' => 'courier_id_field']); ?>

                <div class="invalid-feedback" id="courier_id_error" style="display: none;"></div>
                <?php $__errorArgs = ['courier_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <span class="text-danger"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- Name -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('name', 'Name:',['class'=>'required']); ?>

                <?php echo Form::text('name', null, ['class' => 'form-control', 'maxlength' => 191, 'required']); ?>

            </div>

            <!-- Rider Contact -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('personal_contact', 'Rider Contact:'); ?>

                <?php echo Form::tel('personal_contact', null, ['class' => 'form-control', 'placeholder' => '05XXXXXXXX', 'maxlength' => 10]); ?>

            </div>
            <!-- Personal Email -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('personal_email', 'Personal Email:',['class'=>'required']); ?>

                <?php echo Form::email('personal_email', null, ['class' => 'form-control', 'placeholder' => 'Enter Email ID','maxlength' => 191, 'required']); ?>

            </div>

            <!-- Nationality -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('nationality', 'Nationality:',['class'=>'required']); ?>

                <?php echo Form::select('nationality',
                App\Models\Countries::list()->toArray(),
                null,
                [
                'class' => 'form-control form-select select2',
                'required',
                'placeholder' => 'Select Nationality'
                ]); ?>

            </div>

            <!-- Ethnicity -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('ethnicity', 'Ethnicity:'); ?>

                <?php echo Form::select('ethnicity',
                Common::Dropdowns('ethnicity'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select Ethnicity'
                ]); ?>

            </div>
            <!-- Date of Birth -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('dob', 'Date Of Birth:'); ?>

                <?php echo Form::date('dob', null, ['class' => 'form-control','id'=>'dob']); ?>

            </div>
        </div>
    </div>
</div>

<!-- Job Info Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white fs-5 fw-bold p-4">Job Info</div>
    <div class="card-body">
        <div class="row">

            <!-- Company Contact -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('company_contact', 'Company Contact:'); ?>

                <?php echo Form::tel('company_contact', null, ['class' => 'form-control', 'placeholder' => '05XXXXXXXX', 'maxlength' => 10]); ?>

            </div>

            <!-- Date of Joining -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('doj', 'Date Of Joining:',['class'=>'required']); ?>

                <?php echo Form::date('doj', null, ['class' => 'form-control','id'=>'doj','required']); ?>

            </div>
            <!-- CDM Deposit ID -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('cdm_deposit_id', 'CDM Deposit ID:'); ?>

                <?php echo Form::text('cdm_deposit_id', null, ['class' => 'form-control', 'maxlength' => 191]); ?>

            </div>

            <!-- Mashreq ID -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('mashreq_id', 'Mashreq Id:'); ?>

                <?php echo Form::text('mashreq_id', null, ['class' => 'form-control', 'maxlength' => 191]); ?>

            </div>

            <!-- Branded Plate No -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('branded_plate_no', 'Branded Plate No:'); ?>

                <?php echo Form::text('branded_plate_no', null, ['class' => 'form-control', 'maxlength' => 191]); ?>

            </div>
            <!-- Fleet Supervisor -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('fleet_supervisor', 'Fleet Supervisor:',['class'=>'required']); ?>

                <?php echo Form::select('fleet_supervisor',
                Common::Dropdowns('fleet-supervisor'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select Fleet Supervisor',
                'required'
                ]); ?>

            </div>

            <!-- Salary Model -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('salary_model', 'Salary Model:',['class'=>'required']); ?>

                <?php echo Form::select('salary_model',
                Common::Dropdowns('salary-model'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select Salary Model',
                'required'
                ]); ?>

            </div>
            <div class="form-group col-sm-4">
                <?php echo Form::label('VID', 'Vendor:',['class'=>'required']); ?>

                <?php echo Form::select('VID',App\Models\Vendors::dropdown(),null,
                ['class' => 'form-select', 'required']); ?>

            </div>
            <div class="form-group col-sm-4">
                <?php echo Form::label('recuriter', 'Recruiter:',['class'=>'required']); ?>

                <?php echo Form::select('recuriter',
                ['' => 'Select or type a new recruiter'] + Common::Dropdowns('recuriter'),
                null,
                ['class' => 'form-select recruiter-select', 'required', 'id' => 'recruiter_select']); ?>

            </div>
            <div class="form-group col-sm-4">
                <label>VAT</label>
                <div class="form-check">
                    <input type="hidden" name="vat" value="2" />
                    <input type="checkbox" name="vat" id="vat" class="form-check-input" value="1" <?php if(isset($riders)): ?> <?php if($riders->vat == 1): ?> checked <?php endif; ?> <?php else: ?> <?php endif; ?>/>
                    <label for="vat" class="pt-0">Apply on Invoice</label>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Visa Info Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white fs-5 fw-bold p-4">Visa Info</div>
    <div class="card-body">
        <div class="row">
            <!-- Visa Sponsor -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('visa_sponsor', 'Visa Sponsor:'); ?>

                <?php echo Form::text('visa_sponsor', null, ['class' => 'form-control', 'placeholder' => 'Enter Visa Sponsor', 'maxlength' => 50]); ?>

            </div>

            <!-- Visa Occupation -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('visa_occupation', 'Visa Occupation:',['class'=>'required']); ?>

                <?php echo Form::text('visa_occupation', null, ['class' => 'form-control', 'placeholder' => 'Enter Visa Occupation','maxlength' => 50, 'required' ]); ?>

            </div>

            <!-- Visa Status -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('visa_status', 'Visa Status:'); ?>

                <?php echo Form::select('visa_status',
                Common::Dropdowns('visa-status'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select Visa Status'
                ]); ?>

            </div>
        </div>

        <div class="row">
            <!-- Emirate ID -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('emirate_id', 'Emirate ID:',['class'=>'required']); ?>

                <!-- <?php echo Form::text('emirate_id', null, ['class' => 'form-control','id'=>'emirate_id','placeholder' => '784-2000-6871718-8', 'required']); ?> -->
                <?php echo Form::text('emirate_id', null, [
                'class' => 'form-control',
                'required',
                'id' => 'emirate_id',
                'placeholder' => '784-2000-6871718-8',
                'oninput' => 'formatEmirateId(this)',
                'maxlength' => '18'
                ]); ?>

            </div>

            <!-- Emirate Expiry -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('emirate_exp', 'Emirate Expiry:',['class'=>'required']); ?>

                <?php echo Form::date('emirate_exp', null, ['class' => 'form-control','id'=>'emirate_exp','required']); ?>

            </div>

            <!-- License No -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('license_no', 'License No:',['class'=>'required']); ?>

                <?php echo Form::text('license_no', null, ['class' => 'form-control', 'maxlength' => 50]); ?>

            </div>
        </div>

        <div class="row">
            <!-- License Expiry -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('license_expiry', 'License Expiry:',['class'=>'required']); ?>

                <?php echo Form::date('license_expiry', null, ['class' => 'form-control','id'=>'license_expiry']); ?>

            </div>

            <!-- Passport -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('passport', 'Passport:',['class'=>'required']); ?>

                <?php echo Form::text('passport', null, ['class' => 'form-control', 'maxlength' => 50]); ?>

            </div>

            <!-- Passport Expiry -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('passport_expiry', 'Passport Expiry:',['class'=>'required']); ?>

                <?php echo Form::date('passport_expiry', null, ['class' => 'form-control','id'=>'passport_expiry']); ?>

            </div>
        </div>

        <div class="row">
            <!-- Passport Handover -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('passport_handover', 'Passport Handover:',['class'=>'required']); ?>

                <?php echo Form::select('passport_handover',
                Common::Dropdowns('passport-handover'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select Passport Handover'
                ]); ?>

            </div>
        </div>
    </div>
</div>

<!-- Labor Info Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white fs-5 fw-bold p-4">Labor Info</div>
    <div class="card-body">
        <div class="row">
            <!-- Person Code -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('person_code', 'Person Code:'); ?>

                <?php echo Form::text('person_code', null, ['class' => 'form-control', 'maxlength' => 50]); ?>

            </div>

            <!-- Labor Card Number -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('labor_card_number', 'Labor Card Number:'); ?>

                <?php echo Form::text('labor_card_number', null, ['class' => 'form-control', 'maxlength' => 100]); ?>

            </div>

            <!-- Labor Card Expiry -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('labor_card_expiry', 'Labor Card Expiry:'); ?>

                <?php echo Form::date('labor_card_expiry', null, ['class' => 'form-control','id'=>'labor_card_expiry']); ?>

            </div>
        </div>

        <div class="row">
            <!-- Insurance -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('insurance', 'Insurance:'); ?>

                <?php echo Form::select('insurance',
                Common::Dropdowns('insurance'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select insurance'
                ]); ?>

            </div>

            <!-- Insurance Expiry -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('insurance_expiry', 'Insurance Expiry:'); ?>

                <?php echo Form::date('insurance_expiry', null, ['class' => 'form-control','id'=>'insurance_expiry']); ?>

            </div>

            <!-- Policy No -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('policy_no', 'Policy No:'); ?>

                <?php echo Form::text('policy_no', null, ['class' => 'form-control', 'maxlength' => 255]); ?>

            </div>
        </div>

        <div class="row">
            <!-- Wps -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('wps', 'Wps:'); ?>

                <?php echo Form::select('wps',
                Common::Dropdowns('wps'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select wps'
                ]); ?>

            </div>

            <!-- C3 Card -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('c3_card', 'C3 Card:'); ?>

                <?php echo Form::select('c3_card',
                Common::Dropdowns('c3-card'),
                null,
                [
                'class' => 'form-select',
                'placeholder' => 'Select C3 Card'
                ]); ?>

            </div>
        </div>
    </div>
</div>

<!-- Additional Information Section -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white fs-5 fw-bold p-4">Additional Information</div>
    <div class="card-body">
        <div class="row">
            <!-- Rider Reference -->
            <div class="form-group col-sm-4">
                <?php echo Form::label('rider_reference', 'Rider Reference:',['class'=>'required']); ?>

                <?php echo Form::text('rider_reference', null, ['class' => 'form-control', 'required']); ?>

            </div>
            <!-- Other Details -->
            <div class="form-group col-sm-12">
                <?php echo Form::label('other_details', 'Other Details:'); ?>

                <?php echo Form::textarea('other_details', null, ['class' => 'form-control', 'rows' => 2]); ?>

            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('page-styles'); ?>
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Select2 custom styles */
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
        color: #495057;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 10px;
    }

    .select2-dropdown {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff;
        color: white;
    }

    .select2-loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(255, 255, 255, 0.9);
        padding: 8px 16px;
        border-radius: 4px;
        font-size: 12px;
        color: #007bff;
        z-index: 1000;
    }

    /* Notification styles */
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }

        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .notification-content {
        display: flex;
        align-items: center;
        gap: 8px;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('page-scripts'); ?>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 for recruiter field with tagging
        $('#recruiter_select').select2({
            tags: true,
            placeholder: 'Select or type a new recruiter',
            allowClear: true,
            width: '100%',
            createTag: function(params) {
                var term = $.trim(params.term);

                if (term === '' || term === 'Select or type a new recruiter') {
                    return null;
                }

                return {
                    id: term,
                    text: term + ' (new)',
                    newTag: true
                };
            },
            templateResult: function(data) {
                if (data.newTag) {
                    return $('<span><i class="fa fa-plus"></i> Add "' + data.text.replace(' (new)', '') + '"</span>');
                }
                return data.text;
            },
            templateSelection: function(data) {
                return data.text.replace(' (new)', '');
            }
        });

        // Handle selection change to add new recruiters
        $('#recruiter_select').on('select2:select', function(e) {
            var data = e.params.data;

            if (data.newTag) {
                var recruiterName = data.text.replace(' (new)', '');

                // Show loading state
                var $select = $(this);
                var $container = $select.parent();
                $container.append('<div class="select2-loading">Adding recruiter...</div>');

                // Make AJAX request to add new recruiter
                $.ajax({
                    url: '<?php echo e(route("riders.addRecruiter")); ?>',
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        recruiter_name: recruiterName
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            showNotification('Recruiter "' + response.recruiter_name + '" added successfully!', 'success');

                            // Update the option to remove (new) tag
                            var $option = $select.find('option[value="' + recruiterName + '"]');
                            $option.text(recruiterName);
                            $option.removeAttr('data-new-tag');

                            // Trigger change to update display
                            $select.trigger('change');
                        } else {
                            showNotification('Error: ' + response.message, 'error');
                            // Remove the invalid option
                            $select.find('option[value="' + recruiterName + '"]').remove();
                            $select.val(null).trigger('change');
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'Failed to add recruiter';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        showNotification('Error: ' + errorMessage, 'error');
                        // Remove the invalid option
                        $select.find('option[value="' + recruiterName + '"]').remove();
                        $select.val(null).trigger('change');
                    },
                    complete: function() {
                        // Remove loading state
                        $container.find('.select2-loading').remove();
                    }
                });
            }
        });

        // Fast AJAX Form Submission (if not already handled)
        if ($('#formajax').length && !$._data($('#formajax')[0], 'events')?.submit) {
            $('#formajax').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitButton = form.find('button[type="submit"]');
                const originalText = submitButton.html();

                // Show immediate loading state
                submitButton.html('<i class="fa fa-spinner fa-spin me-2"></i>Saving...').prop('disabled', true);

                // Get form data
                const formData = new FormData(this);

                // Fast AJAX submission
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        // Show success immediately
                        showNotification('Information saved successfully!', 'success');

                        // Redirect after short delay
                        setTimeout(function() {
                            const redirectUrl = $('#redirect_url').val() || response.redirect_url;
                            if (redirectUrl) {
                                window.location.href = redirectUrl;
                            } else {
                                location.reload();
                            }
                        }, 800);
                    },
                    error: function(xhr) {
                        // Handle errors
                        submitButton.html(originalText).prop('disabled', false);

                        if (xhr.status === 422) {
                            // Validation errors
                            const errors = xhr.responseJSON.errors;
                            let errorMessage = 'Please fix the following errors:\n';
                            Object.keys(errors).forEach(function(key) {
                                errorMessage += '• ' + errors[key][0] + '\n';
                            });
                            showNotification(errorMessage, 'error');
                        } else if (xhr.status === 500) {
                            showNotification('Server error occurred. Please try again.', 'error');
                        } else {
                            showNotification('An error occurred while saving. Please try again.', 'error');
                        }
                    },
                    timeout: 30000 // 30 seconds timeout
                });
            });
        }

        // Function to show notifications
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
            <div class="notification-content">
                <i class="ti ti-${type === 'success' ? 'check' : 'x'}"></i>
                <span>${message}</span>
            </div>
        `;

            // Add styles
            notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : '#dc3545'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease;
            max-width: 300px;
        `;

            // Add to page
            document.body.appendChild(notification);

            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
    });
</script>
<?php $__env->stopPush(); ?><?php /**PATH /var/www/laravel/resources/views/riders/fields.blade.php ENDPATH**/ ?>