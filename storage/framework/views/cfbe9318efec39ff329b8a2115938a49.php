<?php $__env->startSection('page_content'); ?>

<!-- Using global fixed-footer styles -->

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

<?php echo Form::model($riders, ['route' => ['riders.update', $riders->id], 'method' => 'patch','id'=>'formajax', 'class' => 'form-with-fixed-footer']); ?>

<input type="hidden" id="redirect_url" value="<?php echo e(route('riders.index')); ?>" />
<div class="card-body card-body-with-footer">
    <div class="row">
        <?php echo $__env->make('riders.fields', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
</div>

<div class="card-footer fixed-footer">
    <div class="d-flex justify-content-end gap-3">
        <a href="<?php echo e(route('riders.index')); ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Information</button>
    </div>
</div>

<?php echo Form::close(); ?>


</div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('page-scripts'); ?>

<!-- Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Fast AJAX Form Submission -->
<script>
    $(document).ready(function() {
        // Intercept form submission for fast AJAX processing
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
                    showQuickNotification('Rider information updated successfully!', 'success');

                    // Redirect after short delay
                    setTimeout(function() {
                        window.location.href = $('#redirect_url').val();
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
                        showQuickNotification(errorMessage, 'error');
                    } else if (xhr.status === 500) {
                        showQuickNotification('Server error occurred. Please try again.', 'error');
                    } else {
                        showQuickNotification('An error occurred while saving. Please try again.', 'error');
                    }
                },
                timeout: 30000 // 30 seconds timeout
            });
        });

        // Quick notification function
        function showQuickNotification(message, type) {
            // Remove any existing notifications
            $('.quick-notification').remove();

            const notification = $(`
            <div class="quick-notification alert alert-${type === 'success' ? 'success' : 'danger'}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border: none;
                border-radius: 8px;
                animation: slideInRight 0.3s ease;
            ">
                <div class="d-flex align-items-center">
                    <i class="ti ti-${type === 'success' ? 'check' : 'x'} me-2"></i>
                    <span style="white-space: pre-line;">${message}</span>
                </div>
            </div>
        `);

            $('body').append(notification);

            // Auto remove after 4 seconds
            setTimeout(function() {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        }
    });
</script>

<!-- Animation styles -->
<style>
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .quick-notification {
        animation: slideInRight 0.3s ease;
    }

    /* Loading button styles */
    .btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
</style>

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

<?php $__env->stopPush(); ?>
<?php echo $__env->make('riders.view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/riders/edit.blade.php ENDPATH**/ ?>