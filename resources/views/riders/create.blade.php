@extends('riders.view')

@section('page_content')

{!! Form::open(['route' => 'riders.store','id'=>'formajax', 'class' => 'form-with-fixed-footer']) !!}
<input type="hidden" id="redirect_url" value="{{route('riders.index')}}" />
<div class="card-body card-body-with-footer">

    <div class="row">
        @include('riders.fields')
    </div>

</div>
<div class="card-footer bg-light border-top fixed-footer">
    <div class="d-flex justify-content-end gap-3">
        <a href="{{ route('riders.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Information</button>
    </div>
</div>

{!! Form::close() !!}


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

    /* Form validation styles */
    .form-control.is-invalid {
        border-color: #dc3545 !important;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    .invalid-feedback {
        display: none;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
        font-weight: 500;
    }

    .invalid-feedback[style*="display: block"] {
        display: block !important;
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

@endsection
@push('page-scripts')


<!-- Select2 CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                    url: '{{ route("riders.addRecruiter") }}',
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

        // Fast AJAX Form Submission with double-submit prevention
        let isSubmitting = false;

        $('#formajax').on('submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation()

            // Prevent double submission
            if (isSubmitting) {
                return false;
            }
            isSubmitting = true;

            const form = $(this);
            const submitButton = form.find('button[type="submit"]');
            const originalText = submitButton.html();

            // Show immediate loading state
            submitButton.html('<i class="fa fa-spinner fa-spin me-2"></i>Creating...').prop('disabled', true);

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
                    if (response.success) {
                        // Show success immediately
                        showNotification('Rider created successfully!', 'success');

                        // Keep button disabled to prevent re-submission
                        submitButton.html('<i class="fa fa-check me-2"></i>Created!');

                        // Redirect after short delay
                        setTimeout(function() {
                            window.location.href = $('#redirect_url').val();
                        }, 800);
                    } else {
                        // Handle unsuccessful response
                        isSubmitting = false;
                        submitButton.html(originalText).prop('disabled', false);
                        showNotification(response.message || 'Failed to create rider. Please try again.', 'error');
                    }
                },
                error: function(xhr) {
                    // Re-enable form submission on error
                    isSubmitting = false;
                    submitButton.html(originalText).prop('disabled', false);

                    // Clear previous error states
                    $('.form-control').removeClass('is-invalid');
                    $('.invalid-feedback').hide();

                    if (xhr.status === 422) {
                        // Validation errors
                        let errorMessage = '';
                        console.log('error caught\n');
                        console.log(xhr.responseJSON.errors);

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = 'Please fix the following errors:\n';
                            
                            // Track all error messages to show them all
                            let allErrorMessages = [];
                            
                            // Display inline errors for each field
                            Object.keys(errors).forEach(function(key) {
                                // Get ALL error messages for this field (not just first)
                                errors[key].forEach(function(errorMsg) {
                                    allErrorMessages.push('• ' + errorMsg);
                                });
                                
                                // Highlight the specific field with error
                                const fieldElement = $('[name="' + key + '"]');
                                if (fieldElement.length) {
                                    fieldElement.addClass('is-invalid');
                                    
                                    // Show inline error message (show first error for this field)
                                    const errorDiv = $('#' + key + '_error');
                                    if (errorDiv.length) {
                                        errorDiv.text(errors[key][0]).show().css('display', 'block');
                                    }
                                }
                                
                                // Special handling for rider_id field
                                if (key === 'rider_id') {
                                    $('#rider_id_field').addClass('is-invalid').focus();
                                    $('#rider_id_error').text(errors[key][0]).show().css('display', 'block');
                                }
                                
                                // Special handling for courier_id field
                                if (key === 'courier_id') {
                                    $('#courier_id_field').addClass('is-invalid');
                                    $('#courier_id_error').text(errors[key][0]).show().css('display', 'block');
                                }
                            });
                            
                            // Add all collected error messages to the notification
                            errorMessage += allErrorMessages.join('\n');
                            
                        }else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }else {
                            errorMessage = 'Validation error occurred. Please check your inputs.';
                        }
                        
                        showNotification(errorMessage, 'error');
                    } 
                    else if (xhr.status === 500) {
                        const message = xhr.responseJSON && xhr.responseJSON.message ?
                            xhr.responseJSON.message :
                            'Server error occurred. Please try again.';
                        showNotification(message, 'error');
                    } else {
                        const message = xhr.responseJSON && xhr.responseJSON.message ?
                            xhr.responseJSON.message :
                            'An error occurred while creating. Please try again.';
                        showNotification(message, 'error');
                    }
                },
                timeout: 30000 // 30 seconds timeout
            });
        });

        // Function to show notifications
        function showNotification(message, type) {
            // Clear any existing notifications first
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notif => {
                if (notif.parentNode) {
                    notif.parentNode.removeChild(notif);
                }
            });

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
            <div class="notification-content">
                <i class="ti ti-${type === 'success' ? 'check' : 'x'}"></i>
                <span style="white-space: pre-line;">${message}</span>
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
            max-width: 400px;
        `;

            // Add to page
            document.body.appendChild(notification);

            // Remove after 5 seconds for errors, 3 seconds for success
            const duration = type === 'error' ? 10000 : 3000;
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, duration);
        }
    });
</script>

@endpush