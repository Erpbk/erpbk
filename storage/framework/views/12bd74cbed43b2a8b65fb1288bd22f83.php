<form action="<?php echo e(route('rider.keeta_activities_import')); ?>" method="POST" enctype="multipart/form-data" id="keeta-import-form">
    <?php echo csrf_field(); ?>
    <div class="row">
        <div class="col-12">
            <a href="<?php echo e(url('sample/keeta_activities_sample.csv')); ?>" class="text-success w-100" download="Keeta Activities Sample">
                <i class="fa fa-file-download text-success"></i> &nbsp; Download Keeta Sample File
            </a>
            <p class="text-muted mt-2">
                <small>
                    The file must contain the official Keeta headers (Date, Courier ID, Courier First Name, Courier Last Name, Supervisor, Vehicle Type, etc.).
                    Only Excel/CSV formats are accepted.
                </small>
            </p>
        </div>
        <div class="col-12 mt-3 mb-3">
            <label class="mb-3 pl-2" for="keeta-import-file">Select file</label>
            <input type="file" id="keeta-import-file" name="file" class="form-control mb-3" style="height: 40px;" accept=".csv,.xlsx,.xls" required />
        </div>
    </div>
    <button type="submit" name="submit" class="btn btn-primary w-100">Start Keeta Import</button>
    <a href="<?php echo e(route('rider.keeta_activities_import_errors')); ?>" class="btn btn-info mt-2 w-100">
        View Keeta Import Errors
    </a>
</form>

<?php
$summary = $summary ?? session('keeta_import_summary');
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if(session('success')): ?>
        Swal.fire({
            icon: 'success',
            title: 'Import Successful',
            text: <?php echo json_encode(session('success'), 15, 512) ?>,
            confirmButtonText: 'OK',
            confirmButtonColor: '#28a745'
        });
        <?php endif; ?>

        <?php if(session('info')): ?>
        Swal.fire({
            icon: 'info',
            title: 'Import Notice',
            text: <?php echo json_encode(session('info'), 15, 512) ?>,
            confirmButtonText: 'OK',
            confirmButtonColor: '#0d6efd'
        });
        <?php endif; ?>

        <?php if(session('warning')): ?>
        Swal.fire({
            icon: 'warning',
            title: 'Import Completed with Warnings',
            text: <?php echo json_encode(session('warning'), 15, 512) ?>,
            confirmButtonText: 'Review',
            confirmButtonColor: '#ffc107'
        });
        <?php endif; ?>

        <?php if(session('error')): ?>
        Swal.fire({
            icon: 'error',
            title: 'Import Failed',
            text: <?php echo json_encode(session('error'), 15, 512) ?>,
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc3545'
        });
        <?php endif; ?>

        <?php if(!empty($summary)): ?>
        const summary = <?php echo json_encode($summary, 15, 512) ?>;
        const errorCount = summary.error_count ?? 0;

        if (errorCount > 0) {
            let errorRows = '';
            (summary.errors || []).forEach(function(error) {
                errorRows += `
                        <tr>
                            <td class="text-center"><span class="badge bg-primary">Row ${error.row ?? 'N/A'}</span></td>
                            <td>${error.error_type ?? '-'}</td>
                            <td>${error.message ?? '-'}</td>
                            <td><code>${error.courier_id ?? 'N/A'}</code></td>
                            <td>${error.date ?? 'N/A'}</td>
                        </tr>
                    `;
            });

            Swal.fire({
                icon: 'warning',
                title: 'Import Completed with ' + errorCount + ' Error(s)',
                html: `
                        <div class="text-start">
                            <p><strong>Total Rows:</strong> ${summary.total_rows ?? 0}</p>
                            <p><strong>Imported:</strong> ${summary.imported_count ?? 0}</p>
                            <p><strong>Skipped:</strong> ${summary.skipped_count ?? 0}</p>
                            <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Excel Row</th>
                                            <th>Error Type</th>
                                            <th>Details</th>
                                            <th>Courier ID</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>${errorRows}</tbody>
                                </table>
                            </div>
                        </div>
                    `,
                showCancelButton: true,
                confirmButtonText: 'View Detailed Report',
                cancelButtonText: 'Close',
                confirmButtonColor: '#17a2b8',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open('<?php echo e(route('
                        rider.keeta_activities_import_errors ')); ?>', '_blank');
                }
            });
        } else if ((summary.imported_count ?? 0) > 0) {
            Swal.fire({
                icon: 'success',
                title: 'Keeta Activities Imported',
                html: `
                        <div class="text-center">
                            <div class="mb-3" style="background: #d4edda; padding: 20px; border-radius: 5px; border: 2px solid #28a745;">
                                <h4 style="color: #155724; margin-bottom: 15px;">✅ Keeta Activities Imported Successfully!</h4>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Total Rows:</strong><br>
                                        <span style="color: #007bff; font-size: 22px; font-weight: bold;">${summary.total_rows ?? 0}</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Imported:</strong><br>
                                        <span style="color: #28a745; font-size: 22px; font-weight: bold;">${summary.imported_count ?? 0}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `,
                confirmButtonText: 'Great!',
                confirmButtonColor: '#28a745',
                width: '520px'
            });
        }
        <?php endif; ?>
    });
</script><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rider_activities/import_keeta.blade.php ENDPATH**/ ?>