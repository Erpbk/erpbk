<div class="modal-header">
    <h5 class="modal-title">Import Bikes</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="container-fluid">
        @include('flash::message')

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Import Bikes from Excel</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('bikes.processImport') }}" method="POST" enctype="multipart/form-data" id="importBikesForm">
                            @csrf

                            <div class="form-group">
                                <label for="file">Select Excel File</label>
                                <input type="file"
                                    class="form-control @error('file') is-invalid @enderror"
                                    id="file"
                                    name="file"
                                    accept=".xlsx,.xls"
                                    required>
                                @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Only Excel files (.xlsx, .xls) are allowed. Maximum file size: 50MB
                                </small>
                            </div>

                            <div class="form-group mt-3">
                                <label for="payment_from">Payment From Account</label>
                                <select class="form-control" id="payment_from" name="payment_from" required>
                                    <option value="">Select Account</option>
                                    @if(isset($bank_accounts))
                                    @foreach($bank_accounts as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                    @endif
                                </select>
                                <small class="form-text text-muted">
                                    Select the account to use for payments
                                </small>
                            </div>

                            @role('admin')
                            <div class="form-group mt-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="reset_data" name="reset_data" value="1">
                                    <label class="custom-control-label" for="reset_data">Reset all bike data before import</label>
                                </div>
                                <small class="form-text text-danger">
                                    <i class="fas fa-exclamation-triangle"></i> Warning: This will delete ALL existing bikes and their history!
                                </small>
                            </div>
                            @endrole

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary" id="importSubmitBtn">
                                    <i class="fas fa-upload"></i> Import Bikes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="importErrorsContainer" style="display: none;">
                    <div class="card mt-3">
                        <div class="card-header bg-warning">
                            <h3 class="card-title">Import Errors</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <strong>Import completed with errors:</strong>
                            </div>
                            <ul class="list-group" id="importErrorsList">
                                <!-- Errors will be inserted here -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Instructions</h3>
                    </div>
                    <div class="card-body">
                        <h5>Required Fields:</h5>
                        <ul class="list-unstyled">
                            <li><strong>Plate Number</strong> - Must be unique</li>
                        </ul>

                        <h5>Optional Fields:</h5>
                        <ul class="list-unstyled">
                            <li>Vehicle Type</li>
                            <li>Chassis Number</li>
                            <li>Color</li>
                            <li>Model</li>
                            <li>Model Type</li>
                            <li>Engine</li>
                            <li>Bike Code</li>
                            <li>Emirates</li>
                            <li>Warehouse</li>
                            <li>Status (1 = Active, 0 = Inactive)</li>
                            <li>Registration Date</li>
                            <li>Expiry Date</li>
                            <li>Insurance Expiry</li>
                            <li>Insurance Company</li>
                            <li>Policy Number</li>
                            <li>Contract Number</li>
                            <li>Traffic File Number</li>
                            <li>Rider Name (will match with existing riders)</li>
                            <li>Company Name (will match with existing companies)</li>
                            <li>Customer Name (will match with existing customers)</li>
                            <li>Notes</li>
                        </ul>

                        <div class="mt-3">
                            <a href="{{ route('bikes.download-template') }}" class="btn btn-info btn-sm" target="_blank">
                                <i class="fas fa-download"></i> Download Sample Template
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Important Notes</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <ul class="mb-0">
                                <li>Make sure to use the exact column headers as shown in the template</li>
                                <li>Dates should be in YYYY-MM-DD format</li>
                                <li>Status should be 1 for Active or 0 for Inactive</li>
                                <li>If a bike with the same plate number exists, it will be updated</li>
                                <li>Rider, Company, and Customer names will be matched with existing records</li>
                                <li>Empty rows will be skipped</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Use an immediately invoked function expression to avoid conflicts
    (function() {
        // Handle form submission via AJAX - use direct DOM method to avoid duplicate handlers
        document.getElementById('importBikesForm').addEventListener('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            // Use vanilla JS fetch instead of jQuery AJAX to avoid conflicts
            fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                })
                .then(response => {
                    // Show loading indicator
                    Swal.fire({
                        title: 'Importing...',
                        html: 'Please wait while we process your file',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    return response.json();
                })
                .then(data => {
                    // Hide loading indicator
                    Swal.close();

                    // Check if there are errors
                    if (data.errors) {
                        // Handle errors
                        const errorsList = document.getElementById('importErrorsList');
                        if (errorsList) {
                            errorsList.innerHTML = '';
                            data.errors.forEach(error => {
                                const li = document.createElement('li');
                                li.className = 'list-group-item text-danger';
                                li.textContent = error;
                                errorsList.appendChild(li);
                            });
                        }

                        const errorsContainer = document.getElementById('importErrorsContainer');
                        if (errorsContainer) errorsContainer.style.display = 'block';

                        let title = 'Import Completed with Errors';
                        let text = `Success: ${data.success_count}, Errors: ${data.error_count}`;

                        if (data.reset) {
                            title = 'Data Reset & Import Completed with Errors';
                            text = 'All previous bike data was reset. ' + text;
                        }

                        Swal.fire({
                            icon: 'warning',
                            title: title,
                            text: text,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        // Handle success
                        let title = 'Import Successful';
                        let text = `Bikes imported successfully. ${data.success_count} records processed.`;

                        if (data.reset) {
                            title = 'Data Reset & Import Successful';
                            text = 'All previous bike data was reset. ' + text;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: title,
                            text: text,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Close the modal
                            const modal = document.querySelector('#modalTop');
                            if (modal && typeof bootstrap !== 'undefined') {
                                const bsModal = bootstrap.Modal.getInstance(modal);
                                if (bsModal) bsModal.hide();
                            }

                            // Reload the page
                            window.location.reload();
                        });
                    }
                })
                .catch(error => {
                    // Hide loading indicator
                    Swal.close();

                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Import Failed',
                        text: 'An error occurred during import.',
                        confirmButtonText: 'OK'
                    });

                    console.error('Error:', error);
                })
        });
    })();
</script>

<style>
    .card-header.bg-warning {
        background-color: #ffc107 !important;
        color: #212529;
    }

    .list-group-item.text-danger {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }

    .alert-info ul {
        margin-bottom: 0;
    }

    .modal-dialog {
        max-width: 90%;
    }
</style>