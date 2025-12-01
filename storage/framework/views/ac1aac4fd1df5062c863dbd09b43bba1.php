

<?php $__env->startSection('title', 'Keeta Activities Import Errors'); ?>

<?php $__env->startSection('content'); ?>
<?php
$metaPayload = [
'status' => $status ?? null,
'errorMessage' => $errorMessage ?? null,
'cleared' => $cleared ?? null,
];

$selected = $selectedBatch ?? null;
?>
<div id="keeta-errors-meta" data-payload="<?php echo e(base64_encode(json_encode($metaPayload))); ?>"></div>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fa fa-truck"></i> Keeta Activities Import Summary
                    </h4>
                    <div>
                        <a href="<?php echo e(route('rider.keeta_activities_import')); ?>" class="btn btn-light btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to Import
                        </a>
                        <form action="<?php echo e(route('rider.keeta_activities_import_errors.clear')); ?>" method="POST" class="d-inline">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Clear all stored Keeta import errors?');">
                                <i class="fa fa-trash"></i> Clear All Errors
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(!$selected): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fa fa-info-circle"></i>
                        No recent Keeta imports were recorded in this session. Select an import batch below to review historical errors.
                    </div>
                    <?php else: ?>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded border">
                                <h3 class="mb-1"><?php echo e($selected->total_rows ?? 0); ?></h3>
                                <p class="mb-0 text-muted">Total Rows</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded border">
                                <h3 class="mb-1 text-success"><?php echo e($selected->imported_count ?? 0); ?></h3>
                                <p class="mb-0 text-muted">Imported</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded border">
                                <h3 class="mb-1 text-warning"><?php echo e($selected->skipped_count ?? 0); ?></h3>
                                <p class="mb-0 text-muted">Skipped</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded border">
                                <h3 class="mb-1 text-danger"><?php echo e($selected->error_count ?? 0); ?></h3>
                                <p class="mb-0 text-muted">Errors</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <h4 class="mb-2 mb-md-0">
                        <i class="fa fa-exclamation-triangle text-danger"></i>
                        Keeta Import Errors
                    </h4>
                    <form method="GET" action="<?php echo e(route('rider.keeta_activities_import_errors')); ?>" class="d-flex align-items-center flex-wrap">
                        <label class="mb-0 mr-2">Batch:</label>
                        <select name="batch_reference" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="">Latest</option>
                            <?php $__currentLoopData = $recentBatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batchItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($batchItem->batch_reference); ?>" <?php echo e(($batchReference === $batchItem->batch_reference) || (empty($batchReference) && $loop->first) ? 'selected' : ''); ?>>
                                <?php echo e($batchItem->batch_reference); ?> (<?php echo e($batchItem->total_rows); ?> rows)
                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <?php if($errors->isEmpty()): ?>
                    <div class="alert alert-success mb-0">
                        <i class="fa fa-check-circle"></i>
                        No error records were found for the selected batch.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="70">#</th>
                                    <th width="120">Excel Row</th>
                                    <th width="180">Courier ID</th>
                                    <th>Reason</th>
                                    <th>Row Snapshot</th>
                                    <th width="160">Logged At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $errors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                $payload = $error->payload;
                                if (!is_array($payload)) {
                                $decoded = json_decode($payload, true);
                                $payload = is_array($decoded) ? $decoded : [];
                                }
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo e($index + 1); ?></td>
                                    <td class="text-center"><span class="badge badge-info"><?php echo e($error->row_number ?? 'N/A'); ?></span></td>
                                    <td><code><?php echo e($error->courier_id ?? 'N/A'); ?></code></td>
                                    <td><?php echo e($error->reason); ?></td>
                                    <td>
                                        <details>
                                            <summary>View Data</summary>
                                            <div class="mt-2">
                                                <table class="table table-sm table-striped mb-0">
                                                    <tbody>
                                                        <?php $__currentLoopData = $payload; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <tr>
                                                            <th style="width: 180px;"><?php echo e(ucfirst(str_replace('_', ' ', $key))); ?></th>
                                                            <td><?php echo e(is_array($value) ? json_encode($value) : $value); ?></td>
                                                        </tr>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>
                                    </td>
                                    <td><?php echo e(optional($error->created_at)->format('Y-m-d H:i')); ?></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const metaEl = document.getElementById('keeta-errors-meta');

        if (!metaEl) {
            return;
        }

        let payload = {};

        try {
            payload = JSON.parse(atob(metaEl.dataset.payload || ''));
        } catch (error) {
            console.error('Failed to parse Keeta error meta payload', error);
            return;
        }

        const status = payload.status;
        const errorMessage = payload.errorMessage;
        const cleared = payload.cleared;

        if (status === 'failed' && errorMessage) {
            Swal.fire({
                icon: 'warning',
                title: 'Import Failed',
                text: errorMessage,
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        } else if (status === 'cleared' || cleared) {
            Swal.fire({
                icon: 'success',
                title: 'Errors Cleared',
                text: 'All Keeta import errors were removed successfully.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#28a745'
            });
        }
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rider_activities/import_keeta_errors.blade.php ENDPATH**/ ?>