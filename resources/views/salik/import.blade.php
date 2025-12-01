@extends('layouts.app')
@section('title', 'Import Salik Records')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Import Salik Records</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a class="btn btn-primary float-right action-btn" href="{{ route('salik.tickets', $account->id) }}" style="color: white; background-color: #007bff;">
                    Back to Salik List
                </a>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Import for Account: {{ $account->name }}</h3>
                </div>
                <div class="card-body">
                    <form id="salikImportForm" action="{{ route('salik.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="salik_account_id" value="{{ $account->id }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="file">Excel File</label>
                                    <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.csv" required>
                                    <small class="form-text text-muted">Upload Excel file with Salik data</small>
                                    <a href="{{ asset('templates/salik_template.xlsx') }}" class="btn btn-sm btn-outline-primary mt-2" download>
                                        <i class="fas fa-download"></i> Download Excel Template
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="admin_charge_per_salik">Admin Charge per Salik</label>
                                    <input type="number" name="admin_charge_per_salik" id="admin_charge_per_salik"
                                        class="form-control" step="0.01" min="0" value="{{ $account->admin_charges ?? 0 }}">
                                    <small class="form-text text-muted">Admin charge to be applied per Salik transaction</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Excel Template Format:</h5>
                                    <ul>
                                        <li><strong>Use the provided Excel template</strong> - Download it using the button above</li>
                                        <li><strong>Required Fields:</strong> Transaction ID, Trip Date, Plate Number, Amount</li>
                                        <li><strong>Optional Fields:</strong> Trip Time, Transaction Post Date, Toll Gate, Direction, Tag Number</li>
                                        <li><strong>Date Format:</strong> Use YYYY-MM-DD format for dates</li>
                                        <li><strong>Plate Numbers:</strong> Must match existing bikes in the system</li>
                                        <li><strong>Do not modify</strong> the header row or column structure</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> Import Process:</h5>
                                    <ul>
                                        <li><strong>Rider Matching:</strong> System retrieves rider_id from bikes table by plate number</li>
                                        <li><strong>Account Lookup:</strong> Gets account ID from accounts table using ref_id = rider_id</li>
                                        <li><strong>History Check:</strong> If bike history has a rider for the trip date, uses that; otherwise uses current bike rider</li>
                                        <li><strong>Voucher Creation:</strong> Creates vouchers per rider group (not per individual transaction)</li>
                                        <li><strong>Error Handling:</strong> Problematic records are automatically skipped - import continues processing</li>
                                        <li><strong>Skip Reasons:</strong> Missing data, duplicates, unknown bikes, no riders, no accounts</li>
                                        <li><strong>Partial Import:</strong> Valid records are imported even if some fail</li>
                                        <li><strong>Detailed Feedback:</strong> Success message shows imported count and skip reasons</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-success" id="importBtn">
                                <i class="fas fa-upload"></i> Import Salik Records
                            </button>
                            <button type="button" class="btn btn-info ml-2" id="testBtn">
                                <i class="fas fa-bug"></i> Test File
                            </button>
                        </div>

                        <!-- Progress indicator -->
                        <div id="importProgress" class="text-center" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2">Processing import... Please wait.</p>
                        </div>

                        <!-- Result messages -->
                        <div id="importResult" class="mt-3" style="display: none;"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection
@section('page-script')
<script>
    $(document).ready(function() {
        $('#salikImportForm').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            var $form = $(this);
            var $btn = $('#importBtn');
            var $progress = $('#importProgress');
            var $result = $('#importResult');

            // Validate file selection
            if (!$('#file').val()) {
                $result.html('<div class="alert alert-danger">Please select a file to import.</div>').show();
                return;
            }

            // Validate file type
            var fileName = $('#file').val();
            var fileExtension = fileName.split('.').pop().toLowerCase();
            if (fileExtension !== 'xlsx' && fileExtension !== 'csv') {
                $result.html('<div class="alert alert-danger">Please select a valid Excel (.xlsx) or CSV (.csv) file.</div>').show();
                return;
            }

            // Show progress, hide result, disable button
            $progress.show();
            $result.hide();
            $btn.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $progress.hide();
                    $btn.prop('disabled', false);

                    if (response.success) {
                        $result.html(
                            '<div class="alert alert-success">' +
                            '<i class="fas fa-check-circle"></i> ' + response.message +
                            (response.imported_count ? ' (' + response.imported_count + ' records imported)' : '') +
                            '</div>'
                        ).show();

                        // Reset form
                        $form[0].reset();

                        // Optional: Redirect after success
                        setTimeout(function() {
                            window.location.href = "{{ route('salik.tickets', $account->id) }}";
                        }, 2000);
                    } else {
                        $result.html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' + response.message + '</div>').show();
                    }
                },
                error: function(xhr) {
                    $progress.hide();
                    $btn.prop('disabled', false);

                    var errorMessage = 'Import failed. Please try again.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = [];
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            errors.push(value[0]);
                        });
                        errorMessage = errors.join('<br>');
                    }

                    $result.html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' + errorMessage + '</div>').show();
                }
            });
        });

        // Test button functionality
        $('#testBtn').on('click', function() {
            var $result = $('#importResult');

            if (!$('#file').val()) {
                $result.html('<div class="alert alert-danger">Please select a file to test.</div>').show();
                return;
            }

            var formData = new FormData();
            formData.append('file', $('#file')[0].files[0]);
            formData.append('_token', $('input[name="_token"]').val());

            $.ajax({
                url: "{{ route('salik.test.import') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $result.html('<div class="alert alert-info">Test completed: ' + response.message + '<br>Check the Laravel logs for detailed file information.</div>').show();
                },
                error: function(xhr) {
                    var errorMessage = xhr.responseJSON ? xhr.responseJSON.error : 'Test failed';
                    $result.html('<div class="alert alert-danger">Test failed: ' + errorMessage + '</div>').show();
                }
            });
        });
    });
</script>
@endsection