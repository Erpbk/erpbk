

<?php $__env->startSection('title', 'Missing Salik Records'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .missing-records h3 {
        color: #ffffff !important;
    }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="ti ti-alert-triangle text-warning me-2"></i>
                            Missing Salik Records
                        </h4>
                        <div class="d-flex gap-2">
                            <a href="<?php echo e(route('salik.index')); ?>" class="btn btn-secondary">
                                <i class="ti ti-arrow-left me-1"></i>Back to Salik
                            </a>
                            <a href="<?php echo e(route('salik.import.form', 1)); ?>" class="btn btn-primary">
                                <i class="ti ti-upload me-1"></i>Import Salik
                            </a>
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#analyzeExcelModal">
                                <i class="ti ti-file-search me-1"></i>Analyze Excel
                            </button>
                            <a href="<?php echo e(route('salik.export.missing.records')); ?>" class="btn btn-success">
                                <i class="ti ti-download me-1"></i>Export to Excel
                            </a>
                            <button type="button" class="btn btn-danger" onclick="clearOldFailedImports()">
                                <i class="ti ti-trash me-1"></i>Clear Old Records
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body ">
                    <!-- Summary Cards -->
                    <div class="row mb-4 missing-records">
                        <div class="col-md-2">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1"><?php echo e(isset($failedImports) ? $failedImports->total() : count($missingRecords)); ?></h3>
                                    <p class="mb-0">Total Missing Records</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1">AED <?php echo e(number_format($totalAmount ?? 0, 2)); ?></h3>
                                    <p class="mb-0">Total Amount</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1"><?php echo e(count(array_filter($missingRecords, fn($r) => $r['reason'] === 'No bike found with this plate number'))); ?></h3>
                                    <p class="mb-0">Missing Bikes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1"><?php echo e(count(array_filter($missingRecords, fn($r) => $r['reason'] === 'No rider assigned for this trip date'))); ?></h3>
                                    <p class="mb-0">Missing Riders</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1"><?php echo e(count(array_filter($missingRecords, fn($r) => $r['reason'] === 'No account found for rider'))); ?></h3>
                                    <p class="mb-0">Missing Accounts</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1"><?php echo e($importStats['successful_imports'] ?? 0); ?></h3>
                                    <p class="mb-0">Successful Imports</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Missing Records Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Transaction ID</th>
                                    <th>Trip Date</th>
                                    <th>Plate Number</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <th>Details</th>
                                    <th>Row #</th>
                                    <th>Import Date</th>
                                    <th>Suggested Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $missingRecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="missing-record-row">
                                    <td><?php echo e($index + 1); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo e($record['transaction_id']); ?></span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?php echo e(\App\Helpers\General::DateFormat($record['trip_date'])); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo e($record['plate_number']); ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">
                                            AED <?php echo e(number_format($record['amount'], 2)); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?php echo e($record['status']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo e($record['reason']); ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo e($record['details']); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo e($record['row_number'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo e(isset($record['import_date']) ? \App\Helpers\General::DateTimeFormat($record['import_date']) : 'N/A'); ?>

                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-info"><?php echo e($record['suggested_action']); ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        <i class="ti ti-check-circle text-success me-2"></i>
                                        No missing records found! All Salik records have been successfully imported.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if(count($missingRecords) > 0): ?>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total Amount:</td>
                                    <td class="fw-bold text-success">
                                        AED <?php echo e(number_format($totalAmount ?? 0, 2)); ?>

                                    </td>
                                    <td colspan="6"></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>

                    </div>

                    <!-- Pagination -->
                    <?php if(isset($failedImports) && $failedImports->hasPages()): ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-muted">
                                Showing <?php echo e($failedImports->firstItem()); ?> to <?php echo e($failedImports->lastItem()); ?> of <?php echo e($failedImports->total()); ?> results
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label for="per_page" class="form-label mb-0 text-muted">Per page:</label>
                                <select id="per_page" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                                    <option value="10" <?php echo e(request('per_page') == 10 ? 'selected' : ''); ?>>10</option>
                                    <option value="15" <?php echo e(request('per_page') == 15 || !request('per_page') ? 'selected' : ''); ?>>15</option>
                                    <option value="25" <?php echo e(request('per_page') == 25 ? 'selected' : ''); ?>>25</option>
                                    <option value="50" <?php echo e(request('per_page') == 50 ? 'selected' : ''); ?>>50</option>
                                    <option value="100" <?php echo e(request('per_page') == 100 ? 'selected' : ''); ?>>100</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <?php echo e($failedImports->links()); ?>

                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Export Options -->
                    <?php if(count($missingRecords) > 0): ?>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Export Missing Records</h6>
                                    <div class="d-flex gap-2">
                                        <a href="<?php echo e(route('salik.export.missing.records')); ?>" class="btn btn-success">
                                            <i class="ti ti-download me-1"></i>Export to Excel
                                        </a>
                                        <button type="button" class="btn btn-info" onclick="window.print()">
                                            <i class="ti ti-printer me-1"></i>Print Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Excel Analysis Modal -->
<div class="modal fade" id="analyzeExcelModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Analyze Excel File for Missing Records</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="analyzeExcelForm" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Select Excel File</label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Upload your Salik Excel file to analyze for potential issues before import.</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-search me-1"></i>Analyze File
                        </button>
                    </div>
                </form>

                <!-- Analysis Results -->
                <div id="analysisResults" class="mt-4" style="display: none;">
                    <h6>Analysis Results</h6>
                    <div id="analysisContent"></div>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script>
    // Function to change per page count
    function changePerPage(perPage) {
        const url = new URL(window.location);
        url.searchParams.set('per_page', perPage);
        window.location.href = url.toString();
    }

    // Global function for clearing failed imports - Version 1.1
    function clearOldFailedImports() {
        const clearAll = confirm('Do you want to clear ALL failed import records?\n\nClick OK to clear ALL records\nClick Cancel to clear only old records (older than 30 days)');

        if (clearAll || confirm('Are you sure you want to clear old failed import records (older than 30 days)? This action cannot be undone.')) {
            $.ajax({
                url: '<?php echo e(route("salik.clear.failed.imports")); ?>',
                type: 'POST',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>',
                    clear_all: clearAll
                },
                success: function(response) {
                    if (response.success) {
                        alert('Success: ' + response.message);
                        location.reload(); // Reload the page to show updated data
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'An error occurred while clearing records';
                    alert('Error: ' + errorMsg);
                }
            });
        }
    }

    // Global function for displaying analysis results
    function displayAnalysisResults(response) {
        const resultsDiv = $('#analysisResults');
        const contentDiv = $('#analysisContent');

        let html = `
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h4>${response.summary.high_severity}</h4>
                        <small>High Severity Issues</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4>${response.summary.medium_severity}</h4>
                        <small>Medium Severity Issues</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4>${response.summary.low_severity}</h4>
                        <small>Low Severity Issues</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>Total Rows Analyzed:</strong> ${response.total_rows}
        </div>
    `;

        if (response.potential_issues.length > 0) {
            html += `
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Row</th>
                            <th>Transaction ID</th>
                            <th>Plate Number</th>
                            <th>Trip Date</th>
                            <th>Amount</th>
                            <th>Issue</th>
                            <th>Severity</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

            response.potential_issues.forEach(function(issue) {
                const severityClass = issue.severity === 'High' ? 'danger' :
                    issue.severity === 'Medium' ? 'warning' : 'info';

                html += `
                <tr>
                    <td>${issue.row}</td>
                    <td>${issue.transaction_id || 'N/A'}</td>
                    <td>${issue.plate_number || 'N/A'}</td>
                    <td>${issue.trip_date || 'N/A'}</td>
                    <td>${issue.amount || 'N/A'}</td>
                    <td>${issue.issue}</td>
                    <td><span class="badge bg-${severityClass}">${issue.severity}</span></td>
                </tr>
            `;
            });

            html += `
                    </tbody>
                                        </table>
                    </div>


        `;
        } else {
            html += `
            <div class="alert alert-success">
                <i class="ti ti-check-circle me-2"></i>
                <strong>Great!</strong> No issues found in the Excel file. It's ready for import.
            </div>
        `;
        }

        contentDiv.html(html);
        resultsDiv.show();
    }

    $(document).ready(function() {
        // Handle Excel analysis form submission
        $('#analyzeExcelForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();

            // Show loading state
            submitBtn.html('<i class="ti ti-loader me-1"></i>Analyzing...').prop('disabled', true);

            $.ajax({
                url: '<?php echo e(route("salik.analyze.excel")); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        displayAnalysisResults(response);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'An error occurred while analyzing the file';
                    alert('Error: ' + errorMsg);
                },
                complete: function() {
                    // Reset button state
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .missing-record-row:hover {
        background-color: #fff3cd !important;
    }

    .badge {
        font-size: 0.8em;
    }

    .table th {
        font-size: 0.9em;
        font-weight: 600;
    }

    .table td {
        font-size: 0.9em;
        vertical-align: middle;
    }

    @media print {

        .btn,
        .card-header {
            display: none !important;
        }
    }
</style>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/salik/missing_records.blade.php ENDPATH**/ ?>