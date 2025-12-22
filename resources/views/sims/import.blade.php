@extends('layouts.app')
@section('title', 'Import RTA Fines')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Import Sims</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a class="btn btn-primary float-right action-btn" href="{{ route('sims.index') }}" style="color: white; background-color: #007bff;">
                    Back to Sim List
                </a>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="salikImportForm" action="{{ route('sims.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="file">Excel File</label>
                                    <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.csv,.xls" required>
                                    <small class="form-text text-muted">Upload Excel file with Sim data</small>
                                    <a href="{{ asset('samples/sim_import_sample.xlsx') }}" class="btn btn-sm btn-outline-primary mt-2" download>
                                        <i class="fas fa-download"></i> Download Sim Template
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Excel Template Format Section -->
                        <div class="row" id="excelFormatSection">
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

                        <!-- Import Process Section -->
                        <div class="row" id="importProcessSection">
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
                                <i class="fas fa-upload"></i> Import RTA Fines
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
                $result.html(
                    '<div class="alert alert-danger">' +
                    '<h5><i class="fas fa-exclamation-triangle"></i> File Required</h5>' +
                    '<div class="mt-2">Please select a file to import.</div>' +
                    '</div>'
                ).show();
                return;
            }

            // Validate file type
            var fileName = $('#file').val();
            var fileExtension = fileName.split('.').pop().toLowerCase();
            if (fileExtension !== 'xlsx' && fileExtension !== 'csv' && fileExtension !== 'xls') {
                $result.html(
                    '<div class="alert alert-danger">' +
                    '<h5><i class="fas fa-exclamation-triangle"></i> Invalid File</h5>' +
                    '<div class="mt-2">Please select a valid Excel (.xlsx) or CSV (.csv) file.</div>' +
                    '</div>'
                ).show();
                return;
            }

            // Show progress, hide info sections, disable button
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
                        // Build detailed results HTML
                        var results = response.results || {};
                        var stats = results.stats || {};
                        var failedFines = results.failed || [];
                        var importedFines = results.processed || [];

                        var resultsHtml = '';
                        resultsHtml += '<div class="card border-success mb-3">';
                        resultsHtml += '    <div class="card-header bg-success text-white">';
                        resultsHtml += '        <h5 class="mb-0">';
                        resultsHtml += '            <i class="fas fa-check-circle"></i> Import Completed';
                        resultsHtml += '        </h5>';
                        resultsHtml += '    </div>';
                        resultsHtml += '    <div class="card-body">';
                        resultsHtml += '        <div class="row">';
                        resultsHtml += '            <div class="col-md-6">';
                        resultsHtml += '                <h6>📊 Import Statistics</h6>';
                        resultsHtml += '                <table class="table table-sm">';
                        resultsHtml += '                    <tbody>';
                        resultsHtml += '                        <tr>';
                        resultsHtml += '                            <td><strong>Total Rows Processed:</strong></td>';
                        resultsHtml += '                            <td class="text-right">' + (stats.total || 0) + '</td>';
                        resultsHtml += '                        </tr>';
                        resultsHtml += '                        <tr class="table-success">';
                        resultsHtml += '                            <td><strong>✅ Successfully Imported:</strong></td>';
                        resultsHtml += '                            <td class="text-right">' + (stats.imported || 0) + '</td>';
                        resultsHtml += '                        </tr>';
                        resultsHtml += '                        <tr class="table-danger">';
                        resultsHtml += '                            <td><strong>❌ Failed to Import:</strong></td>';
                        resultsHtml += '                            <td class="text-right">' + (stats.failed || 0) + '</td>';
                        resultsHtml += '                        </tr>';
                        resultsHtml += '                    </tbody>';
                        resultsHtml += '                </table>';
                        resultsHtml += '            </div>';
                        resultsHtml += '            ';
                        resultsHtml += '            <div class="col-md-6">';
                        resultsHtml += '                <h6>⚠️ Skip Details</h6>';
                        resultsHtml += '                <table class="table table-sm">';
                        resultsHtml += '                    <tbody>';

                        // Add skip reasons
                        var skipReasons = [{
                                key: 'duplicate_db',
                                label: 'Duplicate in Database'
                            },
                            {
                                key: 'duplicate_excel',
                                label: 'Duplicate in File'
                            },
                            {
                                key: 'missing_data',
                                label: 'Missing Data'
                            },
                            {
                                key: 'no_bike',
                                label: 'Bike Not Found'
                            },
                            {
                                key: 'no_rider',
                                label: 'No Rider Found For Bike'
                            }
                        ];

                        for (var i = 0; i < skipReasons.length; i++) {
                            var reason = skipReasons[i];
                            if (stats[reason.key] > 0) {
                                resultsHtml += '<tr>';
                                resultsHtml += '    <td>' + reason.label + ':</td>';
                                resultsHtml += '    <td class="text-right">' + stats[reason.key] + '</td>';
                                resultsHtml += '</tr>';
                            }
                        }

                        resultsHtml += '                    </tbody>';
                        resultsHtml += '                </table>';
                        resultsHtml += '            </div>';
                        resultsHtml += '        </div>';

                        // Add failed rows details
                        if (failedFines.length > 0) {
                            resultsHtml += '<div class="mt-3">';
                            resultsHtml += '    <button type="button" class="btn btn-sm btn-outline-danger mb-2 w-100 text-left" onclick="toggleFailedRows()" id="failedRowsToggle">';
                            resultsHtml += '        <i class="fas fa-file-excel"></i> Show Failed Rows (' + failedFines.length + ')';
                            resultsHtml += '        <i class="fas fa-chevron-down float-right"></i>';
                            resultsHtml += '    </button>';
                            resultsHtml += '    ';
                            resultsHtml += '    <div id="failedRows" style="display: none;">';
                            resultsHtml += '        <div class="alert alert-warning py-2">';
                            resultsHtml += '            <small><i class="fas fa-info-circle"></i> These rows were skipped due to errors</small>';
                            resultsHtml += '        </div>';
                            resultsHtml += '        <div style="max-height: 200px; overflow-y: auto;">';
                            resultsHtml += '            <table class="table table-sm table-bordered table-hover">';
                            resultsHtml += '                <thead class="bg-light">';
                            resultsHtml += '                    <tr>';
                            resultsHtml += '                        <th width="15%">Row #</th>';
                            resultsHtml += '                        <th width="25%">Ticket #</th>';
                            resultsHtml += '                        <th width="20%">Bike Plate</th>';
                            resultsHtml += '                        <th>Error Reason</th>';
                            resultsHtml += '                    </tr>';
                            resultsHtml += '                </thead>';
                            resultsHtml += '                <tbody>';

                            for (var j = 0; j < failedFines.length; j++) {
                                var fine = failedFines[j];
                                resultsHtml += '<tr>';
                                resultsHtml += '    <td>' + (fine.excel_row || 'N/A') + '</td>';
                                resultsHtml += '    <td>' + (fine.ticket_number || 'N/A') + '</td>';
                                resultsHtml += '    <td>' + (fine.plate_number || 'N/A') + '</td>';
                                resultsHtml += '    <td class="text-danger"><small>' + (fine.reason || 'Unknown error') + '</small></td>';
                                resultsHtml += '</tr>';
                            }

                            resultsHtml += '                </tbody>';
                            resultsHtml += '            </table>';
                            resultsHtml += '        </div>';
                            resultsHtml += '    </div>';
                            resultsHtml += '</div>';
                        }

                        // Add Successful rows details
                        if (stats.imported > 0) {
                            resultsHtml += '<div class="mt-3">';
                            resultsHtml += '    <button type="button" class="btn btn-sm btn-outline-success mb-2 w-100 text-left" onclick="toggleSuccessRows()" id="successRowsToggle">';
                            resultsHtml += '        <i class="fas fa-file-excel"></i> Show Successfully Imported Fine(s) (' + importedFines.length + ')';
                            resultsHtml += '        <i class="fas fa-chevron-down float-right"></i>';
                            resultsHtml += '    </button>';
                            resultsHtml += '    ';
                            resultsHtml += '    <div id="successRows" style="display: none;">';
                            resultsHtml += '        <div class="alert alert-success py-2">';
                            resultsHtml += '            <small><i class="fas fa-info-circle"></i> These fines were imported successfully </small>';
                            resultsHtml += '        </div>';
                            resultsHtml += '        <div style="max-height: 200px; overflow-y: auto;">';
                            resultsHtml += '            <table class="table table-sm table-bordered table-hover">';
                            resultsHtml += '                <thead class="bg-light">';
                            resultsHtml += '                    <tr>';
                            resultsHtml += '                        <th width="10%">ID #</th>';
                            resultsHtml += '                        <th width="20%">Ticket #</th>';
                            resultsHtml += '                        <th width="15%">Bike Plate</th>';
                            resultsHtml += '                        <th width="5%">Fine</th>';
                            resultsHtml += '                        <th>Details</th>';
                            resultsHtml += '                    </tr>';
                            resultsHtml += '                </thead>';
                            resultsHtml += '                <tbody>';

                            for (var j = 0; j < importedFines.length; j++) {
                                var fine = importedFines[j];
                                resultsHtml += '<tr>';
                                resultsHtml += '    <td>' + (fine.id || 'N/A') + '</td>';
                                resultsHtml += '    <td>' + (fine.ticket_number || 'N/A') + '</td>';
                                resultsHtml += '    <td>' + (fine.plate_number || 'N/A') + '</td>';
                                resultsHtml += '    <td>' + (fine.amount || 'N/A') + '</td>';
                                resultsHtml += '    <td>' + (fine.detail || 'N/A') + '</td>';
                                resultsHtml += '</tr>';
                            }

                            resultsHtml += '                </tbody>';
                            resultsHtml += '            </table>';
                            resultsHtml += '        </div>';
                            resultsHtml += '    </div>';
                            resultsHtml += '</div>';
                        }

                        // Add success message if any imported
                        if (stats.imported > 0) {
                            resultsHtml += '<div class="alert alert-success mt-3 py-2">';
                            resultsHtml += '    <i class="fas fa-check"></i> ';
                            resultsHtml += '    ' + stats.imported + ' fine(s) successfully imported and now available in the fines list.';
                            resultsHtml += '</div>';
                        } else {
                            resultsHtml += '<div class="alert alert-warning mt-3 py-2">';
                            resultsHtml += '    <i class="fas fa-exclamation-triangle"></i> ';
                            resultsHtml += '    No fines were imported. Please check your file and try again.';
                            resultsHtml += '</div>';
                        }

                        // Add buttons with proper event handlers
                        resultsHtml += '<div class="text-center mt-3">';
                        resultsHtml += '    <a href="{{ route("sims.index") }}" class="btn btn-primary">';
                        resultsHtml += '        <i class="fas fa-list"></i> View Imported Fines';
                        resultsHtml += '    </a>';
                        resultsHtml += '    <button type="button" class="btn btn-secondary ml-2" id="closeResultsBtn">';
                        resultsHtml += '        <i class="fas fa-times"></i> Close';
                        resultsHtml += '    </button>';
                        resultsHtml += '</div>';

                        resultsHtml += '    </div>';
                        resultsHtml += '</div>';

                        // Display the detailed results
                        $result.html(resultsHtml).show();

                        // Reset form
                        $form[0].reset();

                        // Add event listener for close button
                        setTimeout(function() {
                            $('#closeResultsBtn').on('click', function() {
                                $result.hide();
                            });
                        }, 100);

                    } else {
                        $result.html(
                            '<div class="alert alert-danger">' +
                            '<h5><i class="fas fa-exclamation-triangle"></i> Import Failed</h5>' +
                            '<div class="mt-2">' + (response.message || 'Unknown error occurred') + '</div>' +
                            '</div>'
                        ).show();
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
                        errorMessage = '<ul class="mb-0"><li>' + errors.join('</li><li>') + '</li></ul>';
                    }

                    $result.html(
                        '<div class="alert alert-danger">' +
                        '<h5><i class="fas fa-exclamation-triangle"></i> Import Error</h5>' +
                        '<div class="mt-2">' + errorMessage + '</div>' +
                        '</div>'
                    ).show();
                }
            });
        });

        // Test button functionality
        $('#testBtn').on('click', function() {
            var $result = $('#importResult');

            if (!$('#file').val()) {
                $result.html(
                    '<div class="alert alert-danger">' +
                    '<h5><i class="fas fa-exclamation-triangle"></i> File Required</h5>' +
                    '<div class="mt-2">Please select a file to test.</div>' +
                    '</div>'
                ).show();
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
                    $result.html(
                        '<div class="alert alert-info">' +
                        '<h5><i class="fas fa-check-circle"></i> Test Completed</h5>' +
                        '<div class="mt-2">' + response.message + '<br>Check the Laravel logs for detailed file information.</div>' +
                        '</div>'
                    ).show();
                },
                error: function(xhr) {
                    var errorMessage = xhr.responseJSON ? xhr.responseJSON.error : 'Test failed';
                    $result.html(
                        '<div class="alert alert-danger">' +
                        '<h5><i class="fas fa-exclamation-triangle"></i> Test Failed</h5>' +
                        '<div class="mt-2">' + errorMessage + '</div>' +
                        '</div>'
                    ).show();
                }
            });
        });
    });

    // Toggle function for failed rows
    window.toggleFailedRows = function() {
        var div = document.getElementById('failedRows');
        var toggleBtn = document.getElementById('failedRowsToggle');

        if (!div || !toggleBtn) return;

        var chevron = toggleBtn.querySelector('.fa-chevron-down');
        if (!chevron) {
            chevron = toggleBtn.querySelector('.fa-chevron-up');
        }

        if (div.style.display === 'none') {
            div.style.display = 'block';
            if (chevron) {
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
            }
        } else {
            div.style.display = 'none';
            if (chevron) {
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
            }
        }
    };

    window.toggleSuccessRows = function() {
        var div = document.getElementById('successRows');
        var toggleBtn = document.getElementById('successRowsToggle');

        if (!div || !toggleBtn) return;

        var chevron = toggleBtn.querySelector('.fa-chevron-down');
        if (!chevron) {
            chevron = toggleBtn.querySelector('.fa-chevron-up');
        }

        if (div.style.display === 'none') {
            div.style.display = 'block';
            if (chevron) {
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
            }
        } else {
            div.style.display = 'none';
            if (chevron) {
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
            }
        }
    };
</script>
@endsection