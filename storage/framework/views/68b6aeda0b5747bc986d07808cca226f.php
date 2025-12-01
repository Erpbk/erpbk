

<?php
use Illuminate\Support\Str;
?>

<?php $__env->startSection('title', $importType === 'keeta' ? 'Keeta Import Errors' : 'Rider Activities Import Errors'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fa fa-exclamation-triangle"></i>
                        <?php echo e($importType === 'keeta' ? 'Keeta' : 'Rider Activities'); ?> Import Errors - Detailed Report
                    </h4>
                </div>
                <div class="card-body">
                    <?php if(empty($errors) && empty($summary)): ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        <strong>No errors to display.</strong>
                        <p class="mb-0">There are no recent import errors. This page shows errors from your last import attempt.</p>
                    </div>
                    <div class="text-center mt-4">
                        <a href="<?php echo e($importType === 'keeta' ? route('rider.keeta_activities_import') : route('rider.activities_import')); ?>" class="btn btn-primary">
                            <i class="fa fa-upload"></i> Go to Import Page
                        </a>
                    </div>
                    <?php else: ?>
                    <!-- Import Summary Card -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h2 class="mb-0"><?php echo e($summary['total_rows'] ?? 0); ?></h2>
                                    <p class="mb-0">Total Rows</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h2 class="mb-0"><?php echo e($summary['success_count'] ?? 0); ?></h2>
                                    <p class="mb-0">Imported Successfully</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h2 class="mb-0"><?php echo e($summary['skipped_count'] ?? 0); ?></h2>
                                    <p class="mb-0">Skipped</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h2 class="mb-0"><?php echo e($summary['error_count'] ?? 0); ?></h2>
                                    <p class="mb-0">Errors</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="btn-action-group">
                            <button onclick="window.print()" class="btn btn-secondary">
                                <i class="fa fa-print"></i> Print Report
                            </button>
                            <button onclick="exportToExcel()" class="btn btn-success">
                                <i class="fa fa-file-excel"></i> Export Errors to Excel
                            </button>
                            <?php if($importType === 'keeta' && !empty($errors)): ?>
                            <form action="<?php echo e(route('rider.clear_keeta_import_errors')); ?>" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to clear all unresolved errors?')">
                                    <i class="fa fa-broom"></i> Clear All Errors
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo e($importType === 'keeta' ? route('rider.keeta_activities_import') : route('rider.activities_import')); ?>" class="btn btn-primary">
                            <i class="fa fa-arrow-left"></i> Back to Import
                        </a>
                    </div>

                    <?php if($importType === 'keeta'): ?>
                    <?php if(!empty($errors)): ?>
                    <div class="row g-3 keeta-error-grid">
                        <?php $__currentLoopData = $errors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-12">
                            <div class="card keeta-error-card shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3 gap-2">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="badge rounded-pill bg-label-primary keeta-badge">Row <?php echo e($error['row'] ?? 'N/A'); ?></span>
                                            <span class="badge rounded-pill <?php echo e(Str::contains(strtolower($error['error_type']), 'invalid') ? 'bg-label-warning' : (Str::contains(strtolower($error['error_type']), 'empty') ? 'bg-label-secondary' : 'bg-label-danger')); ?> keeta-badge">
                                                <?php echo e($error['error_type']); ?>

                                            </span>
                                            <?php if(!empty($error['created_at'])): ?>
                                            <span class="badge rounded-pill bg-label-dark keeta-badge">
                                                <i class="fa fa-clock me-1"></i><?php echo e($error['created_at']); ?>

                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <form action="<?php echo e(route('rider.resolve_keeta_import_error', ['errorId' => $error['id']])); ?>" method="POST">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fa fa-check me-1"></i> Mark Resolved
                                                </button>
                                            </form>
                                            <?php $collapseId = 'rawData'.$index; ?>
                                            <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo e($collapseId); ?>" aria-expanded="false" aria-controls="<?php echo e($collapseId); ?>">
                                                <i class="fa fa-eye me-1"></i> Raw Data
                                            </button>
                                        </div>
                                    </div>

                                    <p class="keeta-error-message mb-3"><?php echo e($error['message']); ?></p>

                                    <div class="keeta-meta-chips d-flex flex-wrap gap-2 mb-3">
                                        <?php if(!empty($error['courier_id'])): ?>
                                        <span class="chip"><i class="fa fa-id-badge me-1"></i>Courier ID: <strong><?php echo e($error['courier_id']); ?></strong></span>
                                        <?php endif; ?>
                                        <?php if(!empty($error['date'])): ?>
                                        <span class="chip"><i class="fa fa-calendar me-1"></i>Date: <strong><?php echo e($error['date']); ?></strong></span>
                                        <?php endif; ?>
                                        <?php if(!empty($error['supervisor'])): ?>
                                        <span class="chip"><i class="fa fa-user-tie me-1"></i>Supervisor: <strong><?php echo e($error['supervisor']); ?></strong></span>
                                        <?php endif; ?>
                                        <?php if(!empty($error['invalid_column'])): ?>
                                        <span class="chip chip-warning"><i class="fa fa-exclamation-triangle me-1"></i><?php echo e($error['invalid_column']); ?>: <strong><?php echo e($error['invalid_value']); ?></strong></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="collapse" id="<?php echo e($collapseId); ?>">
                                        <div class="card card-body error-raw-data bg-light border">
                                            <h6 class="fw-bold mb-2"><i class="fa fa-code me-2"></i>Raw Row Data</h6>
                                            <pre class="mb-0"><?php echo e(json_encode($error['raw_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle me-2"></i>No unresolved errors were found for the latest import batch.
                    </div>
                    <?php endif; ?>

                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fa fa-lightbulb"></i>
                                Tips to Resolve Keeta Import Errors
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row gy-3">
                                <div class="col-md-6">
                                    <div class="resolution-step">
                                        <h6><span class="step-number">1</span> Review Excel Row</h6>
                                        <p>Open your Excel file and locate the row number indicated on the error card.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="resolution-step">
                                        <h6><span class="step-number">2</span> Validate Rider Details</h6>
                                        <p>Ensure the Courier ID exists and is correctly spelled. Confirm supervisor information is up to date.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="resolution-step">
                                        <h6><span class="step-number">3</span> Fix Data Format</h6>
                                        <p>Check dates, numeric fields, and required columns. Acceptable date format: <code>YYYY-MM-DD</code>.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="resolution-step">
                                        <h6><span class="step-number">4</span> Re-import</h6>
                                        <p>Save the corrected file and re-run the import. Mark resolved errors directly from this page.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php if(!empty($errors)): ?>
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">
                                <i class="fa fa-list"></i>
                                Detailed Error Analysis (<?php echo e(count($errors)); ?> errors found)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover mb-0" id="errorTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th class="text-center">Excel Row</th>
                                            <th>Error Category</th>
                                            <th>Specific Issue</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $errors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td class="text-center"><?php echo e($index + 1); ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-info">Row <?php echo e($error['row'] ?? 'N/A'); ?></span>
                                            </td>
                                            <td><span class="badge badge-danger"><?php echo e($error['error_type'] ?? 'N/A'); ?></span></td>
                                            <td><?php echo e($error['message'] ?? '-'); ?></td>
                                            <td><code><?php echo e($error['courier_id'] ?? $error['payout_type'] ?? '-'); ?></code></td>
                                        </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle me-2"></i>No import errors were found for the latest upload.
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script>
    // Export to Excel function
    function exportToExcel() {
        // Check if errors table exists
        var table = document.getElementById('errorTable');

        // If table doesn't exist, show an error message
        if (!table) {
            alert('No error table found to export.');
            return;
        }

        try {
            var html = table.outerHTML;
            var url = 'data:application/vnd.ms-excel;base64,' + btoa(unescape(encodeURIComponent(html)));
            var downloadLink = document.createElement("a");
            downloadLink.href = url;

            var type = "<?php echo e($importType === 'keeta' ? 'keeta' : 'rider_activities'); ?>";
            downloadLink.download = type + '_import_errors_' + new Date().getTime() + '.xls';

            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        } catch (error) {
            console.error('Excel Export Error:', error);

            // Provide more specific error messages
            var errorMessage = 'An error occurred while exporting to Excel.';
            if (error.message) {
                errorMessage += ' Details: ' + error.message;
            }

            alert(errorMessage);
        }
    }
</script>
<?php $__env->stopSection(); ?>

<style>
    @media print {

        .btn,
        .card-header,
        nav,
        .sidebar {
            display: none !important;
        }

        .card {
            border: 1px solid #000 !important;
            margin-bottom: 20px !important;
        }

        .table {
            font-size: 10px !important;
        }
    }

    .badge {
        font-size: 12px;
        padding: 5px 10px;
    }

    code {
        background: #f4f4f4;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 13px;
    }

    .btn-action-group>* {
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .keeta-error-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .keeta-badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.75rem;
    }

    .keeta-error-message {
        font-size: 0.95rem;
        color: #1f2937;
    }

    .keeta-meta-chips .chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #f3f4f6;
        border-radius: 999px;
        padding: 0.35rem 0.85rem;
        font-size: 0.8rem;
        color: #1f2937;
    }

    .keeta-meta-chips .chip-warning {
        background: #fff4e6;
        color: #b45309;
    }

    .resolution-step {
        background: #f8fafc;
        border-radius: 10px;
        padding: 1rem;
        border: 1px solid #e2e8f0;
    }

    .resolution-step .step-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #2563eb;
        color: #ffffff;
        font-size: 0.8rem;
        margin-right: 0.6rem;
    }

    .error-raw-data pre {
        max-height: 260px;
        overflow: auto;
        background: transparent;
        border: none;
        padding: 0;
        font-size: 0.8rem;
    }

    .keeta-error-grid {
        margin-top: 0.5rem;
    }
</style>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rider_activities/import_errors.blade.php ENDPATH**/ ?>