@extends('layouts.app')

@section('title', 'Import Bikes')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Import Bikes</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('bikes.index') }}">Bikes</a></li>
                    <li class="breadcrumb-item active">Import</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        @include('flash::message')

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Import Bikes from Excel</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Duplicate Prevention:</strong> Bikes with existing Plate Number, Chassis Number, or Engine Number will be rejected and not imported.
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Error Reporting:</strong> If there are any errors during import, you will see the exact Excel row number (e.g., Row 2, Row 3) and the specific error message for each failed row.
                        </div>

                        <form action="{{ route('bikes.processImport') }}" method="POST" enctype="multipart/form-data" id="importBikesForm">
                            @csrf

                            <div class="form-group">
                                <label for="file">Select Excel or CSV File</label>
                                <input type="file"
                                    class="form-control @error('file') is-invalid @enderror"
                                    id="file"
                                    name="file"
                                    accept=".xlsx,.xls,.csv"
                                    required>
                                @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Excel (.xlsx, .xls) or CSV files are allowed. Maximum file size: 50MB
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
                                <a href="{{ route('bikes.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Bikes
                                </a>
                            </div>
                        </form>

                        <div id="importProgress" style="display: none;">
                            <div class="progress mt-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
                            </div>
                            <p class="text-center mt-2">Importing bikes, please wait...</p>
                        </div>
                    </div>
                </div>

                @if(session()->has('import_errors'))
                <div class="card mt-3">
                    <div class="card-header bg-warning">
                        <h3 class="card-title">Import Errors</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>Import completed with errors:</strong>
                        </div>
                        <ul class="list-group">
                            @foreach(session('import_errors') as $error)
                            <li class="list-group-item text-danger">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Important Notes</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-book"></i> Import Guidelines:</h6>
                            <ul class="mb-2">
                                <li>Make sure to use the exact column headers as shown in the template</li>
                                <li>Dates should be in YYYY-MM-DD format (e.g., 2024-01-15)</li>
                                <li>Status should be 1 for Active or 0 for Inactive</li>
                                <li><strong>Duplicate Check:</strong> Bikes with existing Plate Number, Chassis Number, or Engine Number will NOT be imported</li>
                                <li>Rider, Company, and Customer names will be matched with existing records</li>
                                <li>Empty rows will be skipped automatically</li>
                            </ul>

                            <h6><i class="fas fa-exclamation-circle"></i> Error Messages:</h6>
                            <ul class="mb-0">
                                <li><strong>Row Numbers:</strong> Error messages show the exact Excel file row number</li>
                                <li><strong>Example:</strong> "Row 2" means the second row in your Excel file (first data row after headers)</li>
                                <li><strong>Fix Errors:</strong> Correct the errors in your Excel file and re-upload</li>
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
                        <div class="alert alert-light border">
                            <h6 class="mb-2"><i class="fas fa-table"></i> <strong>Excel Row Reference:</strong></h6>
                            <small>
                                <table class="table table-sm table-bordered mb-0" style="font-size: 0.85rem;">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                            <th style="width: 60px;">Excel Row</th>
                                            <th>Content</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-center"><strong>Row 1</strong></td>
                                            <td>Headers (plate, vehicle_type, etc.)</td>
                                        </tr>
                                        <tr class="table-info">
                                            <td class="text-center"><strong>Row 2</strong></td>
                                            <td>First bike data</td>
                                        </tr>
                                        <tr class="table-info">
                                            <td class="text-center"><strong>Row 3</strong></td>
                                            <td>Second bike data</td>
                                        </tr>
                                        <tr class="table-info">
                                            <td class="text-center"><strong>Row 4</strong></td>
                                            <td>Third bike data...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </small>
                        </div>

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

                        <div class="mt-3 align-items-center justify-content-center">
                            <a href="{{ route('bikes.download-template') }}" class="btn btn-info btn-sm btn-block">
                                <i class="fas fa-download"></i> Download Excel Template
                            </a>
                            <a href="{{ asset('bikes_import_data.csv') }}" class="btn btn-success btn-sm btn-block" download>
                                <i class="fas fa-file-csv"></i> Download CSV Template
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('third_party_stylesheets')
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

    .progress {
        height: 20px;
    }

    .progress-bar-animated {
        animation-duration: 1.5s;
    }

    /* Error table styling in SweetAlert */
    .swal2-popup .table {
        font-size: 0.9rem;
    }

    .swal2-popup .table thead th {
        background-color: #dc3545;
        color: white;
        font-weight: bold;
    }

    .swal2-popup .table tbody td {
        vertical-align: middle;
    }

    .swal2-html-container {
        overflow: visible !important;
    }
</style>
@endpush

@push('page-scripts')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Handle form submission
        $('#importBikesForm').on('submit', function(e) {
            e.preventDefault();

            // Show progress bar and hide submit button
            $('#importSubmitBtn').prop('disabled', true);
            $('#importProgress').show();

            // Create form data object
            var formData = new FormData(this);

            // Submit form via AJAX
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Hide progress bar
                    $('#importProgress').hide();

                    if (response.success) {
                        // Show success message
                        let title = 'Import Successful';
                        let text = response.message || 'Bikes imported successfully.';

                        if (response.reset) {
                            title = 'Data Reset & Import Successful';
                        }

                        Swal.fire({
                            icon: 'success',
                            title: title,
                            text: text,
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Redirect to bikes index
                                window.location.href = "{{ route('bikes.index') }}";
                            }
                        });
                    } else {
                        // Show error message with details
                        let title = 'Import Completed with Errors';
                        let text = response.message || 'There were errors during the import.';

                        if (response.reset) {
                            title = 'Data Reset & Import Completed with Errors';
                        }

                        // Build error list HTML
                        let errorHtml = '<div class="text-left">';
                        errorHtml += '<p><strong>' + text + '</strong></p>';

                        if (response.errors && response.errors.length > 0) {
                            errorHtml += '<div class="alert alert-danger" style="max-height: 400px; overflow-y: auto;">';
                            errorHtml += '<strong>Error Details:</strong>';
                            errorHtml += '<table class="table table-sm table-bordered mt-2 mb-0">';
                            errorHtml += '<thead><tr><th style="width: 100px;">Excel Row</th><th>Error Message</th></tr></thead>';
                            errorHtml += '<tbody>';

                            response.errors.forEach(function(error) {
                                // Extract row number from error message (e.g., "Row 2: ...")
                                let rowMatch = error.match(/Row (\d+):/);
                                let rowNumber = rowMatch ? rowMatch[1] : 'N/A';
                                let errorMessage = error.replace(/Row \d+:\s*/, '');

                                errorHtml += '<tr>';
                                errorHtml += '<td class="text-center"><strong>' + rowNumber + '</strong></td>';
                                errorHtml += '<td>' + errorMessage + '</td>';
                                errorHtml += '</tr>';
                            });

                            errorHtml += '</tbody></table>';
                            errorHtml += '</div>';
                        }

                        errorHtml += '</div>';

                        Swal.fire({
                            icon: 'warning',
                            title: title,
                            html: errorHtml,
                            width: '800px',
                            confirmButtonText: 'OK',
                            customClass: {
                                popup: 'text-left'
                            }
                        });

                        // Re-enable submit button
                        $('#importSubmitBtn').prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    // Hide progress bar
                    $('#importProgress').hide();

                    // Show error message
                    var errorMessage = 'An error occurred during import.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Import Failed',
                        text: errorMessage,
                        confirmButtonText: 'OK'
                    });

                    // Re-enable submit button
                    $('#importSubmitBtn').prop('disabled', false);
                }
            });
        });

        // File input change event to validate file type and size
        $('#file').on('change', function() {
            var fileInput = $(this)[0];
            var fileSize = fileInput.files[0] ? fileInput.files[0].size / 1024 / 1024 : 0; // Size in MB
            var fileType = fileInput.files[0] ? fileInput.files[0].name.split('.').pop().toLowerCase() : '';

            // Validate file type
            if (fileType !== 'xlsx' && fileType !== 'xls' && fileType !== 'csv') {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File Type',
                    text: 'Please select an Excel file (.xlsx, .xls) or CSV file (.csv)',
                    confirmButtonText: 'OK'
                });
                $(this).val(''); // Clear the file input
                return false;
            }

            // Validate file size (max 50MB)
            if (fileSize > 50) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: 'File size must not exceed 50MB',
                    confirmButtonText: 'OK'
                });
                $(this).val(''); // Clear the file input
                return false;
            }
        });
    });
</script>
@endpush