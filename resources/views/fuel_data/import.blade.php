

<style>
    .upload-area {
        border: 2px dashed #ccc;
        border-radius: 10px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .upload-area:hover {
        border-color: #004aad;
        background-color: #f8f9fa;
    }
    
    .upload-area.dragover {
        border-color: #004aad;
        background-color: #e6f1ff;
    }
    
    .file-info {
        margin-top: 15px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }
    
    .progress-bar-container {
        margin-top: 20px;
        display: none;
    }
    
    .progress {
        height: 25px;
        border-radius: 5px;
    }
    
    .failed-rows-table {
        max-height: 400px;
        overflow-y: auto;
    }
</style>

<div class="content mt-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="ti ti-upload"></i> Import Fuel Data
                </h4>
                <a href="{{ route('fuel_data.index') }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Template Download Section -->
            <div class="alert alert-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="ti ti-info-circle"></i>
                        <strong>Need a template?</strong> Download the Excel template to get started.
                    </div>
                    <a href="{{ route('fuel_data.importSample') }}" class="btn btn-success">
                        <i class="ti ti-download"></i> Download Template
                    </a>
                </div>
            </div>
            
            <!-- Upload Form -->
            <form action="{{ route('fuel_data.import') }}" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  id="importForm">
                @csrf
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="file" class="form-label">
                                <i class="ti ti-file-excel"></i> Excel File <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   name="file" 
                                   id="file" 
                                   class="form-control @error('file') is-invalid @enderror" 
                                   accept=".xlsx,.xls,.csv"
                                   required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Allowed formats: .xlsx, .xls, .csv (Max 10MB)
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Drag & Drop Upload Area -->
                <div class="upload-area mb-3" id="uploadArea">
                    <i class="ti ti-cloud-upload" style="font-size: 48px; color: #004aad;"></i>
                    <h5 class="mt-3">Drag & Drop your Excel file here</h5>
                    <p class="text-muted">or click to browse</p>
                    <small class="text-muted">Supported formats: .xlsx, .xls, .csv</small>
                </div>
                
                <!-- File Info -->
                <div id="fileInfo" class="file-info" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="ti ti-file-excel text-success"></i>
                            <strong id="fileName"></strong>
                            <span id="fileSize" class="text-muted ms-2"></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger" id="removeFile">
                            <i class="ti ti-trash"></i> Remove
                        </button>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="progress-bar-container" id="progressBarContainer">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: 0%">0%</div>
                    </div>
                    <p class="text-muted mt-2" id="progressStatus">Processing...</p>
                </div>
                
                <!-- Submit Button -->
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="ti ti-upload"></i> Import Data
                    </button>
                    <button type="reset" class="btn btn-secondary" id="resetBtn">
                        <i class="ti ti-refresh"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Import Results Section -->
    <div id="importResults" style="display: none;">
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="ti ti-chart-bar"></i> Import Results
                </h5>
            </div>
            <div class="card-body">
                <div class="alert" id="resultAlert"></div>
                
                <div id="failedRowsContainer" style="display: none;">
                    <h6><i class="ti ti-alert-triangle"></i> Failed Rows</h6>
                    <div class="table-responsive failed-rows-table">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Row #</th>
                                    <th>Transaction No</th>
                                    <th>Bike Plate</th>
                                    <th>Card Number</th>
                                    <th>Reason</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody id="failedRowsBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Drag & Drop functionality
    const uploadArea = $('#uploadArea');
    const fileInput = $('#file');
    const fileInfo = $('#fileInfo');
    const fileName = $('#fileName');
    const fileSize = $('#fileSize');
    const removeFile = $('#removeFile');
    
    // Click on upload area to trigger file input
    uploadArea.on('click', function() {
        fileInput.click();
    });
    
    // Handle file selection
    fileInput.on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            displayFileInfo(file);
        }
    });
    
    // Drag & Drop events
    uploadArea.on('dragover', function(e) {
        e.preventDefault();
        uploadArea.addClass('dragover');
    });
    
    uploadArea.on('dragleave', function(e) {
        e.preventDefault();
        uploadArea.removeClass('dragover');
    });
    
    uploadArea.on('drop', function(e) {
        e.preventDefault();
        uploadArea.removeClass('dragover');
        
        const file = e.originalEvent.dataTransfer.files[0];
        if (file && (file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || 
                     file.type === 'application/vnd.ms-excel' ||
                     file.name.endsWith('.csv'))) {
            fileInput[0].files = e.originalEvent.dataTransfer.files;
            displayFileInfo(file);
        } else {
            Swal.fire({
                title: 'Invalid File',
                text: 'Please upload an Excel file (.xlsx, .xls, or .csv)',
                icon: 'error'
            });
        }
    });
    
    // Remove file
    removeFile.on('click', function() {
        fileInput.val('');
        fileInfo.hide();
        uploadArea.show();
    });
    
    // Display file information
    function displayFileInfo(file) {
        uploadArea.hide();
        fileName.text(file.name);
        fileSize.text((file.size / 1024).toFixed(2) + ' KB');
        fileInfo.show();
    }
    
    // Reset form
    $('#resetBtn').on('click', function() {
        fileInput.val('');
        fileInfo.hide();
        uploadArea.show();
        $('#importResults').hide();
    });
    
    // Form submission with AJAX
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $('#submitBtn');
        const progressBarContainer = $('#progressBarContainer');
        const progressBar = $('.progress-bar');
        const progressStatus = $('#progressStatus');
        
        // Validate file
        const file = fileInput[0].files[0];
        if (!file) {
            Swal.fire({
                title: 'Error',
                text: 'Please select a file to upload',
                icon: 'error'
            });
            return;
        }
        
        // Show progress bar
        progressBarContainer.show();
        progressBar.css('width', '0%').text('0%');
        progressStatus.text('Uploading file...');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progressBar.css('width', percent + '%').text(percent + '%');
                        progressStatus.text('Uploading... ' + percent + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                progressBar.css('width', '100%').text('100%');
                progressStatus.text('Processing complete!');
                
                setTimeout(function() {
                    // Display results
                    $('#importResults').show();
                    
                    if (response.success) {
                        // Show success message
                        $('#resultAlert').removeClass('alert-danger').addClass('alert-success');
                        $('#resultAlert').html(`
                            <i class="ti ti-check-circle"></i>
                            <strong>Import Completed!</strong><br>
                            Total Rows: ${response.data.total_rows}<br>
                            Successfully Imported: ${response.data.success_count}<br>
                            Failed: ${response.data.failed_count}
                        `);
                        
                        // Show failed rows if any
                        if (response.data.failed_rows && response.data.failed_rows.length > 0) {
                            $('#failedRowsContainer').show();
                            const tbody = $('#failedRowsBody');
                            tbody.empty();
                            
                            response.data.failed_rows.forEach(function(row) {
                                tbody.append(`
                                    <tr>
                                        <td>${row.row_number}</td>
                                        <td>${row.transaction_no || 'N/A'}</td>
                                        <td>${row.bike_plate || 'N/A'}</td>
                                        <td>${row.card_number || 'N/A'}</td>
                                        <td><span class="badge bg-danger">${row.reason}</span></td>
                                        <td>${row.details || '-'}</td>
                                    </tr>
                                `);
                            });
                        } else {
                            $('#failedRowsContainer').hide();
                        }
                        
                        // Optionally redirect after 3 seconds
                        if(response.data.failed_rows.length = 0){
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        }
                        
                    } else {
                        $('#resultAlert').removeClass('alert-success').addClass('alert-danger');
                        $('#resultAlert').html(`
                            <i class="ti ti-alert-circle"></i>
                            <strong>Import Failed!</strong><br>
                            ${response.message}
                        `);
                        
                        Swal.fire({
                            title: 'Error',
                            text: response.message,
                            icon: 'error'
                        });
                    }
                    
                    submitBtn.prop('disabled', false);
                    
                }, 1000);
            },
            error: function(xhr) {
                progressBarContainer.hide();
                submitBtn.prop('disabled', false);
                
                let errorMessage = 'An error occurred during import.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    title: 'Error',
                    text: errorMessage,
                    icon: 'error'
                });
                
                $('#importResults').show();
                $('#resultAlert').removeClass('alert-success').addClass('alert-danger');
                $('#resultAlert').html(`
                    <i class="ti ti-alert-circle"></i>
                    <strong>Import Failed!</strong><br>
                    ${errorMessage}
                `);
            }
        });
    });
});
</script>