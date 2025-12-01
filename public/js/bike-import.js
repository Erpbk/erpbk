$(document).ready(function() {
    // Handle Import Bikes modal link
    $(document).on('click', '.show-modal[data-action*="bikes.import"]', function(e) {
        e.preventDefault();
        
        const modalSize = $(this).data('size') || 'lg';
        const modalTitle = $(this).data('title') || 'Import Bikes';
        const modalAction = $(this).data('action');
        
        // Set modal size
        $('#modalTop').removeClass('modal-sm modal-lg modal-xl').addClass('modal-' + modalSize);
        
        // Set modal title
        $('#modalTopTitle').text(modalTitle);
        
        // Show modal
        $('#modalTop').modal('show');
        
        // Show loading indicator
        $('#modalTopbody').html('<div class="d-flex justify-content-center align-items-center" style="min-height: 200px;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        // Load content via AJAX
        $.ajax({
            url: modalAction,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                $('#modalTopbody').html(response);
            },
            error: function(xhr) {
                $('#modalTopbody').html('<div class="alert alert-danger">Error loading content. Please try again.</div>');
                console.error('Error loading modal content:', xhr.responseText);
            }
        });
    });
});
